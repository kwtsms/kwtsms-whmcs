<?php

/**
 * kwtSMS WHMCS Module — SmsHelper
 *
 * Description: The single entry point for all SMS sends in the module.
 *   send() handles single number, array of numbers, and 200+ bulk transparently.
 *   No hook or caller reimplements any logic from this pipeline.
 *
 * Pipeline (12 steps):
 *   1.  gateway_enabled check     -- abort: "SMS is disabled", log to attempts
 *   2.  credentials configured    -- abort: "Gateway not configured", log to attempts
 *   3.  normalize phones          -- prepend default_country_code if needed; strip non-digits
 *   4.  coverage filter           -- check prefix against coverage_cache CSV; skip uncovered
 *   5.  balance check (24h TTL)   -- read last_balance; fetch fresh from API if last_sync > 24h
 *   6.  build KwtSMS client       -- sender and test_mode set in constructor (NOT in send())
 *   7.  client->send()            -- library: local validation, deduplicate, clean, bulk, API call
 *   8.  log to mod_kwtsms_log     -- every attempt (success and failure), full phone unmasked
 *   9.  update last_balance       -- from response balance-after field
 *  10.  log to debug log          -- if debug_log_enabled = 1
 *
 * Note on library API (kwtsms/kwtsms-php v1.7):
 *   KwtSMS constructor: ($username, $password, $sender_id, $test_mode, $log_file)
 *   $client->send($mobile, $message, $sender=null) handles validation, dedup, cleaning, bulk
 *   Test mode and sender are set in constructor, not in send()
 *   MessageUtils::clean_message() and PhoneUtils::normalize_phone() are static helpers
 *
 * Related files: lib/Logger.php, lib/GatewayManager.php, hooks.php
 *
 * @package kwtsms
 */

declare(strict_types=1);

namespace KwtSMS\WHMCS;

use KwtSMS\KwtSMS;
use KwtSMS\PhoneUtils;

class SmsHelper
{
    /**
     * Send SMS through the full pipeline.
     *
     * @param string|string[] $phones        Single phone number or array of phone numbers
     * @param string          $message       Message text (templates already resolved by TemplateParser)
     * @param string          $event         Event key e.g. 'invoice_paid', 'gateway_test'
     * @param string          $recipientType 'customer' or 'admin'
     * @param int|null        $clientid      WHMCS client ID (null for admin recipients)
     * @return array{success: bool, sent: int, skipped: int, error?: string}
     */
    public static function send(
        string|array $phones,
        string $message,
        string $event,
        string $recipientType = 'customer',
        ?int $clientid = null
    ): array {
        // Step 1: Gateway enabled check
        if (GatewayManager::get('gateway_enabled') !== '1') {
            Logger::logAttempt('gateway_disabled', 'SMS is disabled', '', $event, $clientid);
            Logger::logDebug('info', __METHOD__, 'Aborted: gateway disabled', ['event' => $event]);
            return ['success' => false, 'sent' => 0, 'skipped' => 0, 'error' => 'SMS is disabled'];
        }

        // Step 2: Credentials configured check
        if (!GatewayManager::isConfigured()) {
            Logger::logAttempt('gateway_not_configured', 'Gateway not configured', '', $event, $clientid);
            Logger::logDebug('info', __METHOD__, 'Aborted: gateway not configured');
            return ['success' => false, 'sent' => 0, 'skipped' => 0, 'error' => 'Gateway not configured'];
        }

        $phonesArray = is_array($phones) ? $phones : [$phones];
        $defaultCountryCode = ltrim(GatewayManager::get('default_country_code'), '0');
        $coverageCSV = GatewayManager::get('coverage_cache');
        $coveredPrefixes = $coverageCSV !== '' ? array_filter(array_map('trim', explode(',', $coverageCSV))) : [];

        // Step 3: Normalize phones -- prepend default country code to local numbers
        $normalized = [];
        foreach ($phonesArray as $phone) {
            $normalized[] = self::normalizePhone((string) $phone, $defaultCountryCode);
        }

        // Step 4: Coverage filter -- remove uncovered numbers, abort if none remain
        $covered = [];
        foreach ($normalized as $phone) {
            if (self::isCovered($phone, $coveredPrefixes)) {
                $covered[] = $phone;
            } else {
                Logger::logAttempt('coverage_skip', 'Country prefix not in account coverage', $phone, $event, $clientid);
                Logger::logDebug('info', __METHOD__, 'Coverage skip', ['phone' => $phone, 'event' => $event]);
            }
        }

        if (empty($covered)) {
            return ['success' => false, 'sent' => 0, 'skipped' => count($normalized), 'error' => 'No covered numbers'];
        }

        // Step 5: Balance check with 24h TTL
        $lastBalance = (float) GatewayManager::get('last_balance');
        $lastSync = GatewayManager::get('last_sync');
        $syncAge = $lastSync !== '' ? (time() - (int) strtotime($lastSync)) : PHP_INT_MAX;

        if ($syncAge > 86400) {
            // Cache is stale: fetch fresh balance from API
            $username = GatewayManager::get('api_username');
            $password = GatewayManager::get('api_password');
            // 2026-11-05 14:53 +03: app token for the User-Agent, #939
            $tempClient = new KwtSMS($username, $password, 'KWT-SMS', false, '', GatewayManager::appName());
            $freshBalance = $tempClient->balance();
            if ($freshBalance !== null) {
                $lastBalance = $freshBalance;
                GatewayManager::set('last_balance', (string) $lastBalance);
                GatewayManager::set('last_sync', date('Y-m-d H:i:s'));
            }
        }

        if ($lastBalance <= 0) {
            Logger::logAttempt('balance_zero', 'Balance is zero', '', $event, $clientid);
            return ['success' => false, 'sent' => 0, 'skipped' => count($covered), 'error' => 'Recharge at kwtsms.com'];
        }

        // Step 6: Build KwtSMS client -- sender and test_mode in constructor
        $username = GatewayManager::get('api_username');
        $password = GatewayManager::get('api_password');
        $senderId = GatewayManager::get('selected_senderid');
        $testMode = GatewayManager::get('test_mode') === '1';

        // Disable library file logging -- we use our DB Logger
        // 2026-11-05 14:53 +03: app token for the User-Agent, #939
        $client = new KwtSMS($username, $password, $senderId, $testMode, '', GatewayManager::appName());

        // Step 7: client->send() -- library handles: local validation, deduplicate, clean_message, bulk (>200), ERR013 backoff
        $phoneStr = implode(',', $covered);
        $apiReply = [];
        $result = 'ERROR';
        $msgid = null;
        $balanceAfter = null;
        $errorCode = null;

        try {
            $response = $client->send($phoneStr, $message);
            $apiReply = $response;

            if (isset($response['result']) && $response['result'] === 'OK') {
                $result = 'OK';
                $msgid = $response['msg-id'] ?? null;
                $balanceAfter = isset($response['balance-after']) ? (float) $response['balance-after'] : null;
            } else {
                $errorCode = $response['code'] ?? 'UNKNOWN';
            }
        } catch (\Exception $e) {
            $apiReply = ['exception' => $e->getMessage()];
            $errorCode = 'EXCEPTION';
            Logger::logDebug('error', __METHOD__, 'send() exception', ['exception' => $e->getMessage()]);
        }

        // Step 8: Log to mod_kwtsms_log (every send, success or failure, full phone unmasked)
        Logger::log([
            'clientid'       => $clientid,
            'recipient_type' => $recipientType,
            'event'          => $event,
            'phone'          => $phoneStr,
            'message'        => $message,
            'api_reply'      => json_encode($apiReply) ?: '{}',
            'result'         => $result,
            'msgid'          => $msgid,
            'balance_after'  => $balanceAfter,
            'error_code'     => $errorCode,
        ]);

        // Step 9: Update last_balance from balance-after in response
        if ($balanceAfter !== null) {
            GatewayManager::set('last_balance', (string) $balanceAfter);
        }

        // Step 10: Debug log
        Logger::logDebug('info', __METHOD__, "send() complete: {$result}", [
            'event'    => $event,
            'phones'   => count($covered),
            'result'   => $result,
            'error'    => $errorCode,
        ]);

        $sent = $result === 'OK' ? count($covered) : 0;
        return [
            'success' => $result === 'OK',
            'sent'    => $sent,
            'skipped' => count($phonesArray) - $sent,
            'error'   => $errorCode,
            'msgid'   => $msgid,
            'balance_after' => $balanceAfter,
        ];
    }

    /**
     * Normalize a phone number for sending:
     * 1. Use PhoneUtils::normalize_phone() -- converts Arabic/Hindi digits, strips non-digits, strips leading zeros
     * 2. Prepend default country code if the result looks like a local number (< 10 digits)
     */
    public static function normalizePhone(string $phone, string $defaultCountryCode = ''): string
    {
        $phone = PhoneUtils::normalize_phone($phone);

        // Prepend country code to local numbers (< 10 digits after normalization)
        if ($defaultCountryCode !== '' && strlen($phone) < 10) {
            $phone = $defaultCountryCode . $phone;
        }

        return $phone;
    }

    /**
     * Check if a phone number matches any prefix in the coverage list.
     * Sorts by length descending to match the most specific prefix first.
     *
     * @param string[] $coveredPrefixes
     */
    /**
     * Check if a phone number matches any prefix in the coverage list.
     * Sorts by length descending to match the most specific prefix first.
     *
     * @param string[] $coveredPrefixes
     */
    private static function isCovered(string $phone, array $coveredPrefixes): bool
    {
        if (empty($coveredPrefixes)) {
            // No coverage data: pass through to let the API handle it
            return true;
        }

        usort($coveredPrefixes, static fn ($a, $b) => strlen($b) - strlen($a));

        foreach ($coveredPrefixes as $prefix) {
            if ($prefix !== '' && str_starts_with($phone, $prefix)) {
                return true;
            }
        }
        return false;
    }
}

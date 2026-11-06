<?php

/**
 * kwtSMS WHMCS Module: Logger
 *
 * Description: Writes structured records to the three log tables.
 *   log()        -> mod_kwtsms_log (all SMS sends, success and failure, full phone unmasked)
 *   logAttempt() -> mod_kwtsms_attempts (security/block events only)
 *   logDebug()   -> mod_kwtsms_debug_log (internal debug, only when debug_log_enabled = 1)
 *
 * Related files: lib/SmsHelper.php, lib/GatewayManager.php, kwtsms.php
 *
 * @package kwtsms
 */

declare(strict_types=1);

namespace KwtSMS\WHMCS;

use WHMCS\Database\Capsule;

class Logger
{
    private const MODULE = 'kwtsms';

    /**
     * Log a completed SMS send to mod_kwtsms_log.
     * Logs every send attempt (success and failure). Full phone, full API reply, unmasked.
     * API credentials are never stored here.
     *
     * @param array{
     *   clientid?: int|null,
     *   recipient_type: 'customer'|'admin',
     *   event: string,
     *   phone: string,
     *   message: string,
     *   api_reply: string,
     *   result: string,
     *   msgid?: string|null,
     *   balance_after?: float|null,
     *   error_code?: string|null
     * } $data
     */
    public static function log(array $data): void
    {
        try {
            Capsule::table('mod_kwtsms_log')->insert([
                'clientid'       => $data['clientid'] ?? null,
                'recipient_type' => $data['recipient_type'],
                'event'          => $data['event'],
                'phone'          => $data['phone'],
                'message'        => $data['message'],
                'api_reply'      => $data['api_reply'],
                'result'         => $data['result'],
                'msgid'          => $data['msgid'] ?? null,
                'balance_after'  => $data['balance_after'] ?? null,
                'error_code'     => $data['error_code'] ?? null,
                'created_at'     => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            // Never throw from logger -- logging must never break the SMS flow
            logActivity('kwtSMS: Logger::log failed: ' . $e->getMessage());
        }
    }

    /**
     * Log a security or blocking event to mod_kwtsms_attempts.
     *
     * @param string $action One of: gateway_disabled, gateway_not_configured,
     *                       invalid_phone, coverage_skip, duplicate_removed,
     *                       balance_zero, rate_limit_hit
     */
    public static function logAttempt(
        string $action,
        string $detail,
        string $phone = '',
        string $event = '',
        ?int $clientid = null
    ): void {
        try {
            Capsule::table('mod_kwtsms_attempts')->insert([
                'clientid'   => $clientid,
                'ip'         => $_SERVER['REMOTE_ADDR'] ?? '',
                'phone'      => $phone,
                'event'      => $event,
                'action'     => $action,
                'detail'     => $detail,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            logActivity('kwtSMS: Logger::logAttempt failed: ' . $e->getMessage());
        }
    }

    /**
     * Log internal debug info to mod_kwtsms_debug_log.
     * Only writes when debug_log_enabled = 1 in tbladdonmodules.
     *
     * @param 'info'|'warning'|'error' $level
     * @param mixed $context Extra data to JSON-encode
     */
    public static function logDebug(string $level, string $function, string $message, mixed $context = null): void
    {
        $enabled = Capsule::table('tbladdonmodules')
            ->where('module', self::MODULE)
            ->where('setting', 'debug_log_enabled')
            ->value('value');

        if ($enabled !== '1') {
            return;
        }

        try {
            Capsule::table('mod_kwtsms_debug_log')->insert([
                'level'      => $level,
                'function'   => $function,
                'message'    => $message,
                'context'    => $context !== null ? json_encode($context) : null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            logActivity('kwtSMS: Logger::logDebug failed: ' . $e->getMessage());
        }
    }
}

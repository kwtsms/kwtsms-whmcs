<?php

/**
 * kwtSMS WHMCS Module: GatewayManager
 *
 * Description: Manages gateway state in tbladdonmodules.
 *   login()        -- authenticates with kwtSMS API, fetches and caches senderids + coverage
 *   logout()       -- clears all credentials and cached data
 *   reload()       -- re-fetches balance, senderids, coverage without re-entering credentials
 *   get(key)       -- reads a setting from tbladdonmodules (returns empty string if missing)
 *   set(key, val)  -- writes a setting to tbladdonmodules (upsert)
 *   isConfigured() -- true if API credentials are present
 *   isEnabled()    -- true if gateway_enabled = 1
 *
 * Note on library API (kwtsms/kwtsms-php v1.7):
 *   $client->balance()   -> returns ?float (the balance value directly, null on failure)
 *   $client->senderids() -> returns array; on OK: $result['senderids'] is array of sender ID strings
 *   $client->coverage()  -> returns array; on OK: $result['prefixes'] is array of prefix strings
 *
 * Related files: lib/SmsHelper.php, lib/CronHelper.php, templates/ajax/gateway_action.php
 *
 * @package kwtsms
 */

declare(strict_types=1);

namespace KwtSMS\WHMCS;

use KwtSMS\KwtSMS;
use WHMCS\Database\Capsule;

class GatewayManager
{
    private const MODULE = 'kwtsms';

    /**
     * Module version: the one source for kwtsms_config() and the User-Agent token.
     * 2026-11-05 14:53 +03: moved here from kwtsms_config() so hooks can read it without the module file, #939.
     */
    public const VERSION = '1.0.0';

    /**
     * Product token passed as the kwtsms-php app_name (User-Agent): "kwtsms-whmcs/<VERSION>".
     * 2026-11-05 14:53 +03: added so kwtSMS can count accounts per integration (name and version only,
     * no personal data), #939.
     */
    public static function appName(): string
    {
        return 'kwtsms-whmcs/' . self::VERSION;
    }

    /**
     * Authenticate with kwtSMS API. On success, fetch and cache senderids + coverage.
     * Returns success flag, balance, CSV caches, and error message if any.
     *
     * @return array{success: bool, balance?: float, senderids?: string, coverage?: string, error?: string}
     */
    public static function login(string $username, string $password): array
    {
        try {
            // 2026-11-05 14:53 +03: app token for the User-Agent, #939
            $client = new KwtSMS($username, $password, 'KWT-SMS', false, '', self::appName());

            $balance = $client->balance();

            if ($balance === null) {
                return ['success' => false, 'error' => 'Login failed. Check your credentials.'];
            }

            // Save credentials and balance
            self::set('api_username', $username);
            self::set('api_password', $password);
            self::set('last_balance', (string) $balance);
            self::set('last_sync', date('Y-m-d H:i:s'));

            // Fetch and cache sender IDs
            $senderidResult = $client->senderids();
            $senderidsCSV = '';
            $senderOk = isset($senderidResult['result']) && $senderidResult['result'] === 'OK'
                && isset($senderidResult['senderids']) && is_array($senderidResult['senderids']);
            if ($senderOk) {
                $senderidsCSV = implode(',', $senderidResult['senderids']);
            }
            self::set('senderids_cache', $senderidsCSV);

            // Auto-select first sender ID if none selected yet
            if (empty(self::get('selected_senderid')) && !empty($senderidsCSV)) {
                $firstId = explode(',', $senderidsCSV)[0] ?? '';
                self::set('selected_senderid', $firstId);
            }

            // Fetch and cache coverage prefixes
            $coverageResult = $client->coverage();
            $coverageCSV = '';
            $coverageOk = isset($coverageResult['result']) && $coverageResult['result'] === 'OK'
                && isset($coverageResult['prefixes']) && is_array($coverageResult['prefixes']);
            if ($coverageOk) {
                $coverageCSV = implode(',', $coverageResult['prefixes']);
            }
            self::set('coverage_cache', $coverageCSV);

            return [
                'success'   => true,
                'balance'   => $balance,
                'senderids' => $senderidsCSV,
                'coverage'  => $coverageCSV,
            ];
        } catch (\Exception $e) {
            Logger::logDebug('error', __METHOD__, 'Login exception: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Login failed. Check your credentials.'];
        }
    }

    /**
     * Clear all gateway credentials and cached data from tbladdonmodules.
     */
    public static function logout(): void
    {
        $keys = [
            'api_username', 'api_password', 'last_balance', 'last_sync',
            'senderids_cache', 'coverage_cache', 'selected_senderid',
        ];
        foreach ($keys as $key) {
            self::set($key, '');
        }
    }

    /**
     * Re-fetch balance, senderids, and coverage using saved credentials.
     * Updates last_sync timestamp.
     *
     * @return array{success: bool, balance?: float, senderids?: string, coverage?: string, error?: string}
     */
    public static function reload(): array
    {
        $username = self::get('api_username');
        $password = self::get('api_password');

        if (empty($username) || empty($password)) {
            return ['success' => false, 'error' => 'Gateway not configured.'];
        }

        try {
            // 2026-11-05 14:53 +03: app token for the User-Agent, #939
            $client = new KwtSMS($username, $password, 'KWT-SMS', false, '', self::appName());

            $balance = $client->balance();
            if ($balance !== null) {
                self::set('last_balance', (string) $balance);
            }

            $senderidResult = $client->senderids();
            if (isset($senderidResult['result']) && $senderidResult['result'] === 'OK' && isset($senderidResult['senderids'])) {
                self::set('senderids_cache', implode(',', (array) $senderidResult['senderids']));
            }

            $coverageResult = $client->coverage();
            if (isset($coverageResult['result']) && $coverageResult['result'] === 'OK' && isset($coverageResult['prefixes'])) {
                self::set('coverage_cache', implode(',', (array) $coverageResult['prefixes']));
            }

            self::set('last_sync', date('Y-m-d H:i:s'));

            return [
                'success'   => true,
                'balance'   => $balance ?? 0.0,
                'senderids' => self::get('senderids_cache'),
                'coverage'  => self::get('coverage_cache'),
            ];
        } catch (\Exception $e) {
            Logger::logDebug('error', __METHOD__, 'Reload exception: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Reload failed.'];
        }
    }

    /**
     * Read a single setting from tbladdonmodules.
     * Returns empty string if the setting does not exist.
     */
    public static function get(string $key): string
    {
        return (string) Capsule::table('tbladdonmodules')
            ->where('module', self::MODULE)
            ->where('setting', $key)
            ->value('value');
    }

    /**
     * Write a single setting to tbladdonmodules (insert or update).
     */
    public static function set(string $key, string $value): void
    {
        Capsule::table('tbladdonmodules')->updateOrInsert(
            ['module' => self::MODULE, 'setting' => $key],
            ['value' => $value]
        );
    }

    /**
     * True if both api_username and api_password are present.
     */
    public static function isConfigured(): bool
    {
        return !empty(self::get('api_username')) && !empty(self::get('api_password'));
    }

    /**
     * True if gateway_enabled = 1.
     */
    public static function isEnabled(): bool
    {
        return self::get('gateway_enabled') === '1';
    }
}

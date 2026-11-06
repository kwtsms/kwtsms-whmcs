<?php

/**
 * kwtSMS WHMCS Module: CronHelper
 *
 * Description: Daily cron sync for balance, sender IDs, and coverage.
 *   syncGateway() is called by the DailyCronJob hook in hooks.php.
 *   Delegates to GatewayManager::reload() which handles the API calls.
 *
 * Related files: lib/GatewayManager.php, hooks.php
 *
 * @package kwtsms
 */

declare(strict_types=1);

namespace KwtSMS\WHMCS;

class CronHelper
{
    /**
     * Sync balance, sender IDs, and coverage from kwtSMS API.
     * Only runs if gateway is configured (credentials present).
     * Logs result to WHMCS activity log and debug log.
     */
    public static function syncGateway(): void
    {
        if (!GatewayManager::isConfigured()) {
            Logger::logDebug('info', __METHOD__, 'Skipping cron sync: gateway not configured');
            return;
        }

        $result = GatewayManager::reload();

        if ($result['success']) {
            Logger::logDebug('info', __METHOD__, 'Daily cron sync complete', [
                'balance'   => $result['balance'] ?? null,
                'senderids' => $result['senderids'] ?? null,
            ]);
            logActivity('kwtSMS: Daily sync complete. Balance: ' . ($result['balance'] ?? '?') . ' credits');
        } else {
            Logger::logDebug('error', __METHOD__, 'Daily cron sync failed', [
                'error' => $result['error'] ?? 'unknown',
            ]);
            logActivity('kwtSMS: Daily sync failed: ' . ($result['error'] ?? 'unknown error'));
        }
    }
}

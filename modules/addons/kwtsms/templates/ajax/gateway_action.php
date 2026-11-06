<?php

/**
 * kwtSMS WHMCS Module: AJAX: gateway_action
 *
 * Handles gateway management actions from the admin Settings tab.
 * All actions require a valid WHMCS session token (POST 'token').
 *
 * Actions: login, logout, reload, save_settings, save_senderid
 */

declare(strict_types=1);

use KwtSMS\WHMCS\GatewayManager;
use KwtSMS\WHMCS\Logger;

header('Content-Type: application/json');

// CSRF validation
$token = (string) ($_POST['token'] ?? '');
if (empty($_SESSION['token']) || !hash_equals($_SESSION['token'], $token)) {
    echo json_encode(['success' => false, 'error' => 'Invalid token.']);
    exit;
}

$action = preg_replace('/[^a-z_]/', '', (string) ($_POST['action'] ?? ''));

switch ($action) {
    case 'login':
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = trim((string) ($_POST['password'] ?? ''));
        if ($username === '' || $password === '') {
            echo json_encode(['success' => false, 'error' => 'Username and password are required.']);
            break;
        }
        $result = GatewayManager::login($username, $password);
        echo json_encode($result);
        break;

    case 'logout':
        GatewayManager::logout();
        echo json_encode(['success' => true]);
        break;

    case 'reload':
        $result = GatewayManager::reload();
        echo json_encode($result);
        break;

    case 'save_settings':
        // Whitelist of allowed settings keys
        $allowed = [
            'gateway_enabled', 'test_mode', 'debug_log_enabled',
            'default_country_code', 'admin_phones', 'admin_evt_admin_new_order',
        ];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $_POST)) {
                GatewayManager::set($key, trim((string) $_POST[$key]));
            }
        }
        echo json_encode(['success' => true]);
        break;

    case 'save_senderid':
        $senderid = trim((string) ($_POST['senderid'] ?? ''));
        // Validate against cached sender IDs
        $cached = GatewayManager::get('senderids_cache');
        $valid  = array_filter(array_map('trim', explode(',', $cached)));
        if (!empty($valid) && !in_array($senderid, $valid, true)) {
            echo json_encode(['success' => false, 'error' => 'Invalid sender ID.']);
            break;
        }
        GatewayManager::set('selected_senderid', $senderid);
        echo json_encode(['success' => true]);
        break;

    case 'save_templates':
        // Save event toggles + template content for Phase 1 events
        $events = ['client_registration', 'invoice_paid', 'admin_new_order'];
        foreach ($events as $evt) {
            $toggleKey  = 'evt_' . $evt;
            $contentKey = 'tpl_' . $evt;
            // Toggle: explicit '1' or '0' from JS
            if (array_key_exists($toggleKey, $_POST)) {
                GatewayManager::set($toggleKey, $_POST[$toggleKey] === '1' ? '1' : '0');
            }
            if (array_key_exists($contentKey, $_POST)) {
                GatewayManager::set($contentKey, (string) $_POST[$contentKey]);
            }
        }
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action.']);
}
exit;

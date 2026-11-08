<?php

/**
 * kwtSMS WHMCS Module: AJAX: clear_log
 *
 * Truncates a specified kwtSMS log table.
 * Allowed tables: mod_kwtsms_log, mod_kwtsms_attempts, mod_kwtsms_debug_log
 */

declare(strict_types=1);

use WHMCS\Database\Capsule;

header('Content-Type: application/json');

// CSRF validation
$token = (string) ($_POST['token'] ?? '');
// 2026-11-08 08:17 +03: token from kwtsms_csrf_token(); WHMCS 9.1 no longer stores $_SESSION['token'] (whmcs-91-compat)
$expected = kwtsms_csrf_token();
if ($expected === '' || !hash_equals($expected, $token)) {
    echo json_encode(['success' => false, 'error' => 'Invalid token.']);
    exit;
}

$logTable = (string) ($_POST['log'] ?? '');
$allowed  = ['mod_kwtsms_log', 'mod_kwtsms_attempts', 'mod_kwtsms_debug_log'];

if (!in_array($logTable, $allowed, true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid log table.']);
    exit;
}

try {
    Capsule::table($logTable)->truncate();
    echo json_encode(['success' => true]);
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Failed to clear log.']);
}
exit;

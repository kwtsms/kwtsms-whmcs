<?php

/**
 * kwtSMS WHMCS Module: AJAX: test_sms
 *
 * Sends a test SMS to a given phone number through the full SmsHelper pipeline.
 * Uses the currently configured test_mode and sender ID.
 */

declare(strict_types=1);

use KwtSMS\WHMCS\SmsHelper;

header('Content-Type: application/json');

// CSRF validation
$token = (string) ($_POST['token'] ?? '');
if (empty($_SESSION['token']) || !hash_equals($_SESSION['token'], $token)) {
    echo json_encode(['success' => false, 'error' => 'Invalid token.']);
    exit;
}

$phone = trim((string) ($_POST['phone'] ?? ''));
if ($phone === '') {
    echo json_encode(['success' => false, 'error' => 'Phone number is required.']);
    exit;
}

$message = 'kwtSMS: This is a test message from your WHMCS integration. Gateway is working correctly.';

$result = SmsHelper::send($phone, $message, 'gateway_test', 'admin', null);
echo json_encode($result);
exit;

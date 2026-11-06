<?php

/**
 * kwtSMS WHMCS Module: Hooks
 *
 * Description: All WHMCS hook registrations for the kwtSMS addon.
 *   This file is auto-loaded by WHMCS from /modules/addons/kwtsms/.
 *   No manual registration is needed.
 *
 * Phase 1 hooks:
 *   ClientAdd               -- SMS to new client on registration
 *   InvoicePaid             -- SMS to client when invoice is paid
 *   AfterShoppingCartCheckout -- SMS to admin(s) on new order
 *   DailyCronJob            -- daily balance/senderid/coverage sync
 *
 * All SMS sends delegate to SmsHelper::send(). No hook reimplements send logic.
 *
 * Related files: lib/SmsHelper.php, lib/TemplateParser.php, lib/CronHelper.php,
 *   lib/GatewayManager.php
 *
 * @package kwtsms
 */

declare(strict_types=1);

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . '/vendor/autoload.php';

use WHMCS\Database\Capsule;
use KwtSMS\WHMCS\SmsHelper;
use KwtSMS\WHMCS\TemplateParser;
use KwtSMS\WHMCS\CronHelper;
use KwtSMS\WHMCS\GatewayManager;
use KwtSMS\WHMCS\Logger;

/**
 * ClientAdd hook -- fires when a new client account is created.
 * Sends a welcome SMS to the client's registered phone number.
 *
 * Available $vars keys: userid, firstname, lastname, email, phonenumber,
 *   companyname, address1, city, state, postcode, country, currency, language
 */
add_hook('ClientAdd', 1, function (array $vars): void {
    $clientId = (int) ($vars['userid'] ?? 0);
    $phone = trim($vars['phonenumber'] ?? '');

    if (empty($phone)) {
        Logger::logDebug('info', 'hook:ClientAdd', 'No phone number on client, skipping', ['clientid' => $clientId]);
        return;
    }

    $placeholders = [
        '{firstname}' => htmlspecialchars($vars['firstname'] ?? '', ENT_QUOTES, 'UTF-8'),
        '{fullname}'  => htmlspecialchars(trim(($vars['firstname'] ?? '') . ' ' . ($vars['lastname'] ?? '')), ENT_QUOTES, 'UTF-8'),
    ];

    $message = TemplateParser::parse('client_registration', $placeholders);
    if ($message === null) {
        Logger::logDebug('info', 'hook:ClientAdd', 'Template disabled or empty, skipping', ['clientid' => $clientId]);
        return;
    }

    SmsHelper::send($phone, $message, 'client_registration', 'customer', $clientId);
});

/**
 * InvoicePaid hook -- fires when an invoice is marked as paid.
 * Sends payment confirmation SMS to the invoice owner's phone.
 *
 * Available $vars keys: invoiceid, userid
 * Must query tblclients and tblinvoices for phone/amount details.
 */
add_hook('InvoicePaid', 1, function (array $vars): void {
    $invoiceId = (int) ($vars['invoiceid'] ?? 0);

    if ($invoiceId === 0) {
        return;
    }

    $invoice = Capsule::table('tblinvoices')
        ->where('id', $invoiceId)
        ->first(['userid', 'total', 'duedate', 'currencysymbol']);

    if ($invoice === null) {
        Logger::logDebug('warning', 'hook:InvoicePaid', 'Invoice not found', ['invoiceid' => $invoiceId]);
        return;
    }

    $clientId = (int) $invoice->userid;

    $client = Capsule::table('tblclients')
        ->where('id', $clientId)
        ->first(['firstname', 'lastname', 'phonenumber']);

    if ($client === null || empty($client->phonenumber)) {
        Logger::logDebug('info', 'hook:InvoicePaid', 'No phone on client, skipping', ['clientid' => $clientId]);
        return;
    }

    $amount = ($invoice->currencysymbol ?? '') . number_format((float) $invoice->total, 2);
    $placeholders = [
        '{firstname}'      => htmlspecialchars($client->firstname ?? '', ENT_QUOTES, 'UTF-8'),
        '{fullname}'       => htmlspecialchars(trim(($client->firstname ?? '') . ' ' . ($client->lastname ?? '')), ENT_QUOTES, 'UTF-8'),
        '{invoiceid}'      => (string) $invoiceId,
        '{invoiceamount}'  => $amount,
        '{invoiceduedate}' => $invoice->duedate ?? '',
    ];

    $message = TemplateParser::parse('invoice_paid', $placeholders);
    if ($message === null) {
        Logger::logDebug('info', 'hook:InvoicePaid', 'Template disabled or empty, skipping', ['invoiceid' => $invoiceId]);
        return;
    }

    SmsHelper::send($client->phonenumber, $message, 'invoice_paid', 'customer', $clientId);
});

/**
 * AfterShoppingCartCheckout hook -- fires after a new order is placed.
 * Sends new order alert SMS to the configured admin phone numbers.
 *
 * Available $vars keys: OrderID, UserID, OrderDetails (array), PaymentMethod
 * Admin phones are newline-separated in tbladdonmodules: admin_phones
 * Admin event must be enabled: admin_evt_admin_new_order = 1
 */
add_hook('AfterShoppingCartCheckout', 1, function (array $vars): void {
    $orderId = (int) ($vars['OrderID'] ?? 0);
    $userId  = (int) ($vars['UserID'] ?? 0);

    // Check admin event enabled
    $adminEvtEnabled = GatewayManager::get('admin_evt_admin_new_order');
    if ($adminEvtEnabled !== '1') {
        return;
    }

    $adminPhones = trim(GatewayManager::get('admin_phones'));
    if (empty($adminPhones)) {
        Logger::logDebug('info', 'hook:AfterShoppingCartCheckout', 'No admin phones configured, skipping');
        return;
    }

    // Fetch client name for the template
    $client = null;
    if ($userId > 0) {
        $client = Capsule::table('tblclients')
            ->where('id', $userId)
            ->first(['firstname', 'lastname']);
    }

    $fullName  = $client ? trim(($client->firstname ?? '') . ' ' . ($client->lastname ?? '')) : 'Guest';
    $placeholders = [
        '{orderid}'   => (string) $orderId,
        '{fullname}'  => htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'),
        '{firstname}' => htmlspecialchars($client->firstname ?? '', ENT_QUOTES, 'UTF-8'),
    ];

    $message = TemplateParser::parse('admin_new_order', $placeholders);
    if ($message === null) {
        // Fallback message if template is disabled or empty
        $message = 'New order #' . $orderId . ' by ' . $fullName . '.';
    }

    // Parse newline-separated admin phone list
    $phones = array_filter(array_map('trim', explode("\n", $adminPhones)));
    if (empty($phones)) {
        return;
    }

    SmsHelper::send(array_values($phones), $message, 'admin_new_order', 'admin', null);
});

/**
 * DailyCronJob hook -- fires once per day when WHMCS cron runs.
 * Refreshes balance, sender IDs, and coverage cache.
 * $vars contains 'time' (Unix timestamp of cron run).
 */
add_hook('DailyCronJob', 1, function (array $vars): void {
    CronHelper::syncGateway();
});

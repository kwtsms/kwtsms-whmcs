<?php

/*
 * FILE:      tests/e2e/events.php
 * CREATED:   2026-11-08 08:40 +03
 * TASK:      whmcs-e2e (Boss 2026-11-08 08:26: test all client-side events, save the tests for easy redo)
 * AUTHOR:    M Alhassoun
 * WHAT:      Runs inside the WHMCS container (php /e2e/events.php <command>) and uses WHMCS's own local API and
 *            database layer for what the browser-level script (admin.py) cannot do: prepare the test shop, pay an
 *            invoice, add a client without a phone, and read the kwtSMS log rows. Prints JSON.
 * WHY:       localAPI runs the same WHMCS core code (and fires the same hooks) as the admin screens.
 * USAGE:     php /e2e/events.php prepare | pay <invoiceid> | addclient <email> [phone] | rows <after_id> |
 *            attempts <after_id> | lastinvoice <clientid> | orders <clientid> | version | configure (JSON on stdin) | client <email> | settings
 * DEPENDS:   WHMCS at /var/www/html (init.php), the kwtSMS module activated.
 */

declare(strict_types=1);

use WHMCS\Database\Capsule;

require '/var/www/html/init.php';

$cmd = $argv[1] ?? '';
$out = [];

switch ($cmd) {
    case 'prepare':
        // Test shop: no captcha, Kuwait default, bank transfer gateway, one product with a monthly price.
        foreach (['CaptchaSetting' => '', 'DefaultCountry' => 'KW', 'SystemURL' => 'http://localhost:8080/'] as $k => $v) {
            Capsule::table('tblconfiguration')->updateOrInsert(['setting' => $k], ['value' => $v]);
        }
        if (!Capsule::table('tblpaymentgateways')->where('gateway', 'banktransfer')->exists()) {
            foreach (['name' => 'Bank Transfer', 'type' => 'Invoices', 'visible' => 'on', 'instructions' => 'E2E'] as $k => $v) {
                Capsule::table('tblpaymentgateways')->insert(['gateway' => 'banktransfer', 'setting' => $k, 'value' => $v, 'order' => 1]);
            }
        }
        $gid = Capsule::table('tblproductgroups')->where('name', 'E2E')->value('id');
        if (!$gid) {
            $gid = Capsule::table('tblproductgroups')->insertGetId(['name' => 'E2E', 'slug' => 'e2e', 'headline' => '', 'tagline' => '', 'orderfrmtpl' => '', 'disabledgateways' => '', 'hidden' => 0, 'order' => 1]);
        }
        $pid = Capsule::table('tblproducts')->where('name', 'E2E Plan')->value('id');
        if (!$pid) {
            $r = localAPI('AddProduct', ['name' => 'E2E Plan', 'gid' => $gid, 'type' => 'other', 'paytype' => 'recurring',
                'pricing' => [1 => ['monthly' => '10.00']]]);
            $pid = $r['pid'] ?? null;
            $out['addproduct'] = $r['result'] ?? 'error';
        }
        $out['pid'] = $pid;
        $out['maxlog'] = (int) Capsule::table('mod_kwtsms_log')->max('id');
        break;

    case 'pay':
        $r = localAPI('AddInvoicePayment', ['invoiceid' => (int) $argv[2], 'transid' => 'E2E-' . time(), 'gateway' => 'banktransfer']);
        $out = ['result' => $r['result'] ?? '', 'message' => $r['message'] ?? '',
                'status' => Capsule::table('tblinvoices')->where('id', (int) $argv[2])->value('status')];
        break;

    case 'addclient':
        $data = ['firstname' => 'E2E', 'lastname' => 'NoPhone', 'email' => $argv[2], 'address1' => 'Street 1',
                 'city' => 'Kuwait', 'state' => 'Capital', 'postcode' => '13001', 'country' => 'KW',
                 'password2' => bin2hex(random_bytes(8)) . 'Aa1!', 'skipvalidation' => true];
        if (isset($argv[3])) {
            $data['phonenumber'] = $argv[3];
        }
        $r = localAPI('AddClient', $data);
        $out = ['result' => $r['result'] ?? '', 'clientid' => $r['clientid'] ?? null, 'message' => $r['message'] ?? ''];
        break;

    case 'client':
        $c = Capsule::table('tblclients')->where('email', $argv[2])->first(['id', 'phonenumber', 'country']);
        $out = $c ? (array) $c : [];
        break;

    case 'configure':
        // Server-side configuration through the module's own GatewayManager, for versions whose admin screens
        // cannot run on this WHMCS (1.0.0 on 9.1). Reads {"username","password","settings":{...}} from stdin.
        require_once '/var/www/html/modules/addons/kwtsms/vendor/autoload.php';
        $in = json_decode((string) stream_get_contents(STDIN), true) ?: [];
        $r = \KwtSMS\WHMCS\GatewayManager::login((string) ($in['username'] ?? ''), (string) ($in['password'] ?? ''));
        foreach (($in['settings'] ?? []) as $k => $v) {
            \KwtSMS\WHMCS\GatewayManager::set((string) $k, (string) $v);
        }
        $out = ['login' => !empty($r['success'])];
        break;

    case 'version':
        $out = ['version' => Capsule::table('tbladdonmodules')->where('module', 'kwtsms')->where('setting', 'version')->value('value')];
        break;

    case 'orders':
        $out = ['orders' => Capsule::table('tblorders')->where('userid', (int) $argv[2])->count()];
        break;

    case 'lastinvoice':
        $out = ['invoiceid' => Capsule::table('tblinvoices')->where('userid', (int) $argv[2])->max('id')];
        break;

    case 'settings':
        // Module settings without secrets; volatile values (balance, sync time) and version are not compared.
        foreach (Capsule::table('tbladdonmodules')->where('module', 'kwtsms')->get(['setting', 'value']) as $s) {
            if (preg_match('/password|balance|sync|^access$|^version$/i', $s->setting)) {
                continue;
            }
            $out[$s->setting] = $s->value;
        }
        break;

    case 'rows':
        $out = Capsule::table('mod_kwtsms_log')->where('id', '>', (int) $argv[2])->orderBy('id')
            ->get(['id', 'clientid', 'event', 'phone', 'message', 'result', 'error_code', 'api_reply'])->toArray();
        break;

    case 'attempts':
        $out = Capsule::table('mod_kwtsms_attempts')->where('id', '>', (int) $argv[2])->orderBy('id')->get()->toArray();
        break;

    default:
        fwrite(STDERR, "usage: php events.php prepare|pay|addclient|client|lastinvoice|rows|attempts\n");
        exit(2);
}

echo json_encode($out, JSON_UNESCAPED_UNICODE), "\n";

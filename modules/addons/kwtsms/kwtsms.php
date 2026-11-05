<?php

/**
 * kwtSMS WHMCS Module — Main Entry Point
 *
 * Description: WHMCS Addon Module entry point. Defines the required functions:
 *   kwtsms_config()      -- module metadata (name, version, author)
 *   kwtsms_activate()    -- creates DB tables, seeds default config
 *   kwtsms_deactivate()  -- drops tables, removes config
 *   kwtsms_output()      -- renders the 7-tab admin UI
 *
 * WHMCS auto-loads this file when the addon is accessed. All function names
 * must be prefixed with the module folder name (kwtsms_).
 *
 * Related files: hooks.php, lib/SmsHelper.php, lib/GatewayManager.php,
 *   lib/Logger.php, lib/TemplateParser.php, templates/admin/*.tpl
 *
 * @package kwtsms
 */

declare(strict_types=1);

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;
use KwtSMS\WHMCS\GatewayManager;
use KwtSMS\WHMCS\Logger;
use KwtSMS\WHMCS\TemplateParser;

require_once __DIR__ . '/vendor/autoload.php';

/**
 * Module config metadata. Config fields are managed via the custom output() UI,
 * not through WHMCS standard fields -- gateway settings require dynamic dropdowns.
 *
 * @return array<string, string|array<string, mixed>>
 */
function kwtsms_config(): array
{
    return [
        'name'        => 'kwtSMS',
        'description' => 'Send automated SMS to clients and admins via kwtSMS (kwtsms.com). Kuwait market A2P SMS gateway.',
        'version'     => GatewayManager::VERSION, // 2026-11-05 14:53 +03: single version source, #939
        'author'      => 'kwtSMS',
        'language'    => 'english',
        'fields'      => [],
    ];
}

/**
 * Called when admin activates the module from Setup > Addon Modules.
 * Creates the three custom log tables and seeds default configuration.
 *
 * @return array{status: string, description: string}
 */
function kwtsms_activate(): array
{
    try {
        // mod_kwtsms_log -- all SMS sends (success and failure), full phone unmasked
        if (!Capsule::schema()->hasTable('mod_kwtsms_log')) {
            Capsule::schema()->create('mod_kwtsms_log', function ($table) {
                $table->increments('id');
                $table->integer('clientid')->nullable();
                $table->enum('recipient_type', ['customer', 'admin']);
                $table->string('event', 50);
                $table->string('phone', 30);
                $table->text('message');
                $table->text('api_reply');
                $table->string('result', 10);
                $table->string('msgid', 64)->nullable();
                $table->decimal('balance_after', 10, 2)->nullable();
                $table->string('error_code', 20)->nullable();
                $table->dateTime('created_at');
            });
        }

        // mod_kwtsms_attempts -- security and blocking events (no SMS content)
        if (!Capsule::schema()->hasTable('mod_kwtsms_attempts')) {
            Capsule::schema()->create('mod_kwtsms_attempts', function ($table) {
                $table->increments('id');
                $table->integer('clientid')->nullable();
                $table->string('ip', 45);
                $table->string('phone', 30);
                $table->string('event', 50);
                $table->string('action', 30);
                $table->text('detail');
                $table->dateTime('created_at');
            });
        }

        // mod_kwtsms_debug_log -- internal debug, only written when debug_log_enabled = 1
        if (!Capsule::schema()->hasTable('mod_kwtsms_debug_log')) {
            Capsule::schema()->create('mod_kwtsms_debug_log', function ($table) {
                $table->increments('id');
                $table->enum('level', ['info', 'warning', 'error']);
                $table->string('function', 100);
                $table->text('message');
                $table->text('context')->nullable();
                $table->dateTime('created_at');
            });
        }

        // Seed default configuration values
        $defaults = array_merge([
            'gateway_enabled'           => '0',
            'test_mode'                 => '1',
            'debug_log_enabled'         => '0',
            'default_country_code'      => '965',
            'evt_client_registration'   => '1',
            'evt_invoice_paid'          => '1',
            'evt_admin_new_order'       => '1',
            'admin_evt_admin_new_order' => '1',
        ], TemplateParser::defaultTemplates());

        foreach ($defaults as $key => $value) {
            Capsule::table('tbladdonmodules')->updateOrInsert(
                ['module' => 'kwtsms', 'setting' => $key],
                ['value' => $value]
            );
        }

        return ['status' => 'success', 'description' => 'kwtSMS module activated. Go to the addon and configure your gateway settings.'];
    } catch (\Exception $e) {
        return ['status' => 'error', 'description' => 'Activation failed: ' . $e->getMessage()];
    }
}

/**
 * Called when admin deactivates the module.
 * Drops the three custom tables and removes all tbladdonmodules config entries.
 *
 * @return array{status: string, description: string}
 */
function kwtsms_deactivate(): array
{
    try {
        Capsule::schema()->dropIfExists('mod_kwtsms_log');
        Capsule::schema()->dropIfExists('mod_kwtsms_attempts');
        Capsule::schema()->dropIfExists('mod_kwtsms_debug_log');
        Capsule::table('tbladdonmodules')->where('module', 'kwtsms')->delete();

        return ['status' => 'success', 'description' => 'kwtSMS module deactivated and all data removed.'];
    } catch (\Exception $e) {
        return ['status' => 'error', 'description' => 'Deactivation failed: ' . $e->getMessage()];
    }
}

/**
 * Admin area output -- renders the 7-tab admin UI.
 * $vars['modulelink'] is the base URL for this module's admin page.
 * AJAX requests append &ajax=<endpoint> to this URL.
 *
 * @param array<string, mixed> $vars WHMCS-provided variables
 */
function kwtsms_output(array $vars): void
{
    $moduleLink = $vars['modulelink'];

    // Handle AJAX requests
    $ajaxAction = isset($_GET['ajax']) ? preg_replace('/[^a-z_]/', '', (string) $_GET['ajax']) : '';
    if ($ajaxAction !== '') {
        $ajaxFile = __DIR__ . '/templates/ajax/' . $ajaxAction . '.php';
        if (file_exists($ajaxFile)) {
            include $ajaxFile;
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unknown endpoint.']);
        }
        return;
    }

    // Determine active tab (sanitize: letters only)
    $tab = isset($_GET['tab']) ? preg_replace('/[^a-z]/', '', (string) $_GET['tab']) : 'dashboard';
    $allowedTabs = ['dashboard', 'settings', 'templates', 'integrations', 'logs', 'help'];
    if (!in_array($tab, $allowedTabs, true)) {
        $tab = 'dashboard';
    }

    // Load language file
    $lang = [];
    $langFile = __DIR__ . '/lang/english.php';
    if (file_exists($langFile)) {
        require $langFile;
        $lang = $_ADDONLANG ?? [];
    }

    // Collect all template variables (no credentials exposed to HTML)
    $tplVars = [
        'modulelink'           => $moduleLink,
        'tab'                  => $tab,
        'allowedTabs'          => $allowedTabs,
        'isConfigured'         => GatewayManager::isConfigured(),
        'lang'                 => $lang,
        'gateway_enabled'      => GatewayManager::get('gateway_enabled'),
        'test_mode'            => GatewayManager::get('test_mode'),
        'last_balance'         => GatewayManager::get('last_balance'),
        'last_sync'            => GatewayManager::get('last_sync'),
        'selected_senderid'    => GatewayManager::get('selected_senderid'),
        'senderids_cache'      => GatewayManager::get('senderids_cache'),
        'api_username'         => GatewayManager::get('api_username'), // display only, not password
        'default_country_code' => GatewayManager::get('default_country_code'),
        'debug_log_enabled'    => GatewayManager::get('debug_log_enabled'),
        'admin_phones'         => GatewayManager::get('admin_phones'),
        'admin_evt_new_order'  => GatewayManager::get('admin_evt_admin_new_order'),
        'whmcs_token'          => isset($_SESSION['token']) ? $_SESSION['token'] : '',
    ];

    $tplPath = __DIR__ . '/templates/admin/' . $tab . '.tpl';
    if (!file_exists($tplPath)) {
        $tplPath = __DIR__ . '/templates/admin/dashboard.tpl';
    }

    ob_start();
    extract($tplVars, EXTR_SKIP);
    include $tplPath;
    $output = ob_get_clean();
    echo $output;
}

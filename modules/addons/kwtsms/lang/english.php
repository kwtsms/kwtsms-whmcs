<?php
/**
 * kwtSMS WHMCS Module: Language: English
 *
 * Description: All UI strings for the admin interface in English.
 * Related files: lang/arabic.php, templates/admin/*.tpl
 *
 * @package kwtsms
 */

declare(strict_types=1);

$_ADDONLANG = [
    // Tab names
    'tab_dashboard'     => 'Dashboard',
    'tab_settings'      => 'Settings',
    'tab_templates'     => 'Templates',
    'tab_integrations'  => 'Integrations',
    'tab_logs'          => 'Logs',
    'tab_help'          => 'Help',

    // Dashboard
    'dashboard_title'       => 'kwtSMS Dashboard',
    'gateway_status'        => 'Gateway Status',
    'status_connected'      => 'Connected',
    'status_disconnected'   => 'Not Connected',
    'gateway_enabled_label' => 'Gateway Enabled',
    'test_mode_label'       => 'Test Mode',
    'current_balance'       => 'Current Balance',
    'selected_senderid'     => 'Sender ID',
    'last_sync_label'       => 'Last Sync',
    'sent_today'            => 'Sent Today',
    'failed_today'          => 'Failed Today',
    'view_logs'             => 'View Logs',
    'configure_first'       => 'Please configure the gateway in Settings to start sending SMS.',

    // Settings - Gateway
    'gateway_settings'      => 'Gateway Settings',
    'api_username'          => 'API Username',
    'api_password'          => 'API Password',
    'btn_login'             => 'Login',
    'btn_logout'            => 'Logout',
    'btn_reload'            => 'Reload',
    'sender_id_label'       => 'Sender ID',
    'country_code_label'    => 'Default Country Code',
    'toggle_test_mode'      => 'Test Mode',
    'toggle_gateway'        => 'Gateway On/Off',
    'balance_label'         => 'Balance',
    'credits_label'         => 'credits',
    'last_sync_info'        => 'Last sync',

    // Test SMS
    'test_sms_title'    => 'Test SMS',
    'test_sms_phone'    => 'Phone Number',
    'test_sms_message'  => 'Message',
    'btn_send_test'     => 'Send Test',
    'test_sms_hint'     => 'Runs through the full send pipeline. Respects test mode and gateway toggle.',
    'test_sms_sending'  => 'Sending...',

    // Settings - General
    'general_settings'       => 'General Settings',
    'debug_log_enabled'      => 'Debug Log',
    'admin_phones_label'     => 'Admin Phone Numbers',
    'admin_phones_hint'      => 'One phone number per line. Receives admin SMS alerts.',
    'btn_save_settings'      => 'Save Settings',
    'settings_saved'         => 'Settings saved.',

    // Logs tab
    'logs_sent'         => 'Sent Messages',
    'logs_attempts'     => 'Attempts',
    'logs_debug'        => 'Debug Log',
    'btn_clear_log'     => 'Clear',
    'confirm_clear'     => 'Are you sure you want to clear this log?',
    'log_col_date'      => 'Date',
    'log_col_event'     => 'Event',
    'log_col_recipient' => 'Recipient',
    'log_col_phone'     => 'Phone',
    'log_col_message'   => 'Message',
    'log_col_result'    => 'Result',
    'log_col_msgid'     => 'Msg ID',
    'log_col_balance'   => 'Balance After',
    'log_col_error'     => 'Error Code',
    'log_col_action'    => 'Action',
    'log_col_detail'    => 'Detail',
    'log_col_ip'        => 'IP',
    'log_col_level'     => 'Level',
    'log_col_function'  => 'Function',
    'log_empty'         => 'No records found.',
    'showing_records'   => 'Showing %d of %d records',

    // Status labels
    'on'                => 'On',
    'off'               => 'Off',
    'yes'               => 'Yes',
    'no'                => 'No',
    'enabled'           => 'Enabled',
    'disabled'          => 'Disabled',
    'active'            => 'Active',
    'inactive'          => 'Inactive',

    // Error / success messages (generic to prevent enumeration)
    'saved'             => 'Settings saved.',
    'login_success'     => 'Connected to kwtSMS.',
    'logout_success'    => 'Logged out.',
    'reload_success'    => 'Gateway data refreshed.',
    'login_failed'      => 'Login failed. Check your credentials.',
    'not_configured'    => 'Gateway not configured.',
    'sms_disabled'      => 'SMS is disabled.',
    'balance_zero'      => 'Balance is zero. Recharge at kwtsms.com.',
    'no_covered_numbers' => 'No covered numbers.',
    'cleared'           => 'Log cleared.',
    'error_generic'     => 'An error occurred. Please try again.',
];

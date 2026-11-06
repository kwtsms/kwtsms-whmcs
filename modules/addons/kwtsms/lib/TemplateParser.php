<?php

/**
 * kwtSMS WHMCS Module: TemplateParser
 *
 * Description: Resolves {placeholder} tokens in EN/AR message templates.
 *   Loads the template from tbladdonmodules by event key and language.
 *   parse() returns the final message string ready to pass to SmsHelper::send().
 *   Returns null if the event is disabled or the template is empty.
 *
 * Related files: lib/SmsHelper.php, hooks.php
 *
 * @package kwtsms
 */

declare(strict_types=1);

namespace KwtSMS\WHMCS;

use WHMCS\Database\Capsule;

class TemplateParser
{
    private const MODULE = 'kwtsms';

    /**
     * Load template from tbladdonmodules, substitute placeholders, return message.
     * Returns null if event toggle is off or template is empty/missing.
     *
     * @param string $event    Event key e.g. 'invoice_paid'
     * @param array<string, string> $vars Placeholder map e.g. ['{firstname}' => 'Ali']
     * @param string $language 'en' (default) or 'ar'
     */
    public static function parse(string $event, array $vars, string $language = 'en'): ?string
    {
        // Check per-event enabled toggle
        $enabled = Capsule::table('tbladdonmodules')
            ->where('module', self::MODULE)
            ->where('setting', 'evt_' . $event)
            ->value('value');

        if ($enabled !== '1') {
            return null;
        }

        $settingKey = $language === 'ar' ? 'tpl_' . $event . '_ar' : 'tpl_' . $event;

        $template = Capsule::table('tbladdonmodules')
            ->where('module', self::MODULE)
            ->where('setting', $settingKey)
            ->value('value');

        if (empty($template)) {
            return null;
        }

        // Global placeholders available to all templates
        $companyName = (string) (Capsule::table('tblconfiguration')
            ->where('setting', 'CompanyName')
            ->value('value') ?? '');

        $placeholders = array_merge([
            '{companyname}' => $companyName,
            '{date}'        => date('Y-m-d'),
        ], $vars);

        return str_replace(array_keys($placeholders), array_values($placeholders), $template);
    }

    /**
     * Default templates seeded into tbladdonmodules on module activation.
     * Keys match tbladdonmodules setting names (tpl_{event} and tpl_{event}_ar).
     *
     * @return array<string, string>
     */
    public static function defaultTemplates(): array
    {
        return [
            'tpl_client_registration'    => 'Hi {firstname}, welcome to {companyname}! Your account has been created successfully.',
            'tpl_client_registration_ar' => 'مرحباً {firstname}، أهلاً بك في {companyname}! تم إنشاء حسابك بنجاح.',
            'tpl_invoice_paid'           =>
                'Hi {firstname}, invoice #{invoiceid} for {invoiceamount} has been paid. Thank you, {companyname}.',
            'tpl_invoice_paid_ar'        => 'مرحباً {firstname}، تم دفع الفاتورة #{invoiceid} بمبلغ {invoiceamount}. شكراً، {companyname}.',
            'tpl_admin_new_order'        => 'New order #{orderid} placed by {fullname}. Login to review: {companyname}.',
        ];
    }
}

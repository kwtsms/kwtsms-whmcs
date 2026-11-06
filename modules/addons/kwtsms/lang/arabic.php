<?php
/**
 * kwtSMS WHMCS Module: Language: Arabic
 *
 * Description: Arabic translations for the admin interface (RTL).
 * Related files: lang/english.php, templates/admin/*.tpl
 *
 * @package kwtsms
 */

declare(strict_types=1);

$_ADDONLANG = [
    'tab_dashboard'     => 'لوحة التحكم',
    'tab_settings'      => 'الإعدادات',
    'tab_templates'     => 'القوالب',
    'tab_integrations'  => 'التكاملات',
    'tab_logs'          => 'السجلات',
    'tab_help'          => 'المساعدة',

    'dashboard_title'       => 'لوحة kwtSMS',
    'gateway_status'        => 'حالة البوابة',
    'status_connected'      => 'متصل',
    'status_disconnected'   => 'غير متصل',
    'gateway_enabled_label' => 'البوابة مفعّلة',
    'test_mode_label'       => 'وضع الاختبار',
    'current_balance'       => 'الرصيد الحالي',
    'selected_senderid'     => 'معرف المرسل',
    'last_sync_label'       => 'آخر مزامنة',
    'sent_today'            => 'مُرسَل اليوم',
    'failed_today'          => 'فشل اليوم',
    'view_logs'             => 'عرض السجلات',
    'configure_first'       => 'يرجى إعداد البوابة من الإعدادات لبدء إرسال الرسائل.',

    'gateway_settings'      => 'إعدادات البوابة',
    'api_username'          => 'اسم المستخدم',
    'api_password'          => 'كلمة المرور',
    'btn_login'             => 'تسجيل الدخول',
    'btn_logout'            => 'تسجيل الخروج',
    'btn_reload'            => 'تحديث',
    'sender_id_label'       => 'معرف المرسل',
    'country_code_label'    => 'كود الدولة الافتراضي',
    'toggle_test_mode'      => 'وضع الاختبار',
    'toggle_gateway'        => 'تفعيل / تعطيل البوابة',
    'balance_label'         => 'الرصيد',
    'credits_label'         => 'رصيد',
    'last_sync_info'        => 'آخر مزامنة',

    'test_sms_title'    => 'اختبار الرسائل',
    'test_sms_phone'    => 'رقم الهاتف',
    'test_sms_message'  => 'نص الرسالة',
    'btn_send_test'     => 'إرسال اختبار',
    'test_sms_hint'     => 'يستخدم مسار الإرسال الكامل. يحترم وضع الاختبار وحالة البوابة.',
    'test_sms_sending'  => 'جاري الإرسال...',

    'general_settings'       => 'الإعدادات العامة',
    'debug_log_enabled'      => 'سجل التصحيح',
    'admin_phones_label'     => 'أرقام هواتف المديرين',
    'admin_phones_hint'      => 'رقم واحد لكل سطر. يستقبل تنبيهات SMS للمديرين.',
    'btn_save_settings'      => 'حفظ الإعدادات',
    'settings_saved'         => 'تم حفظ الإعدادات.',

    'logs_sent'         => 'الرسائل المرسلة',
    'logs_attempts'     => 'محاولات الإرسال',
    'logs_debug'        => 'سجل التصحيح',
    'btn_clear_log'     => 'مسح',
    'confirm_clear'     => 'هل أنت متأكد من مسح هذا السجل؟',
    'log_col_date'      => 'التاريخ',
    'log_col_event'     => 'الحدث',
    'log_col_recipient' => 'المستلم',
    'log_col_phone'     => 'الهاتف',
    'log_col_message'   => 'الرسالة',
    'log_col_result'    => 'النتيجة',
    'log_col_msgid'     => 'معرف الرسالة',
    'log_col_balance'   => 'الرصيد بعد الإرسال',
    'log_col_error'     => 'كود الخطأ',
    'log_col_action'    => 'الإجراء',
    'log_col_detail'    => 'التفاصيل',
    'log_col_ip'        => 'IP',
    'log_col_level'     => 'المستوى',
    'log_col_function'  => 'الدالة',
    'log_empty'         => 'لا توجد سجلات.',
    'showing_records'   => 'عرض %d من %d سجل',

    'on'                => 'مفعّل',
    'off'               => 'معطّل',
    'yes'               => 'نعم',
    'no'                => 'لا',
    'enabled'           => 'مفعّل',
    'disabled'          => 'معطّل',
    'active'            => 'نشط',
    'inactive'          => 'غير نشط',

    'saved'             => 'تم حفظ الإعدادات.',
    'login_success'     => 'تم الاتصال بـ kwtSMS.',
    'logout_success'    => 'تم تسجيل الخروج.',
    'reload_success'    => 'تم تحديث بيانات البوابة.',
    'login_failed'      => 'فشل تسجيل الدخول. تحقق من البيانات.',
    'not_configured'    => 'البوابة غير مهيأة.',
    'sms_disabled'      => 'الرسائل معطّلة.',
    'balance_zero'      => 'الرصيد صفر. اشحن على kwtsms.com.',
    'no_covered_numbers' => 'لا أرقام مشمولة بالتغطية.',
    'cleared'           => 'تم مسح السجل.',
    'error_generic'     => 'حدث خطأ. يرجى المحاولة مرة أخرى.',
];

<?php

namespace SabaPayamak;

// جلوگیری از دسترسی مستقیم به فایل
if (!defined('ABSPATH')) exit;

function sabapayamak_validate_options($input)
{
    $default_options = get_option('sabapayamak_options', Helpers::sabapayamak_default_options());

    // نام کاربری خالی یا مقدار پیش‌فرض نباشد
    if (empty(trim($input['ks_user_name']))) {
        $input['ks_user_name'] = $default_options['ks_user_name'];
        add_settings_error('options-general', ' -ks_user_name', 'نام کاربری را وارد کنید.', 'error' );
    }
    
    // رمز وب‌سرویس خالی یا مقدار پیش‌فرض نباشد
    if (empty(trim($input['ks_password']))) {
        $input['ks_password'] = $default_options['ks_password'];
        add_settings_error('options-general', 'invalid-ks_password', 'رمز وب‌سرویس را وارد کنید.', 'error' );
    }

    // شماره مجازی خالی یا مقدار پیش‌فرض نباشد
    if (empty(trim($input['ks_vnumber']))) {
        $input['ks_vnumber'] = $default_options['ks_vnumber'];
        add_settings_error('options-general', 'invalid-ks_vnumber', 'شماره مجازی را وارد کنید.', 'error' );
    }

    // اگر دامنه وب‌سرویس خالی بود، پیش‌فرض ثبت شود
    if (empty(trim($input['ks_web_service_domain']))) {
        $input['ks_web_service_domain'] = $default_options['ks_web_service_domain'];
    }

    // اگر دامنه ای‌پی‌آی خالی بود، پیش‌فرض ثبت شود
    if (empty(trim($input['ks_api_domain']))) {
        $input['ks_api_domain'] = $default_options['ks_api_domain'];
    }

    // اگر متد ارسال انتخاب نشده بود، پیش‌فرض انتخاب شود
    if (empty(trim($input['ks_send_method']))) {
        $input['ks_send_method'] = $default_options['ks_send_method'];
    }

    // اگر نحوه اتصال انتخاب نشده بود، پیش‌فرض انتخاب شود
    if (empty(trim($input['ks_api_method']))) {
        $input['ks_api_method'] = $default_options['ks_api_method'];
    }

    return $input;
}
<?php

namespace SabaPayamak;

// جلوگیری از دسترسی مستقیم به فایل
if (!defined('ABSPATH')) exit;

// اضافه کردن منوی تنظیمات
function ks_register_setting()
{
    if (!current_user_can("manage_options"))
        return;
    
    register_setting('sabapayamak_options', 'sabapayamak_options', 'SabaPayamak\sabapayamak_validate_options');

    add_settings_section('sabapayamak_settings_section','تنظیمات سامانه صباپیامک', 'SabaPayamak\settings_section_callback','sabapayamak');
    add_settings_field('ks_user_name','نام کاربری','SabaPayamak\textbox_callback_function','sabapayamak','sabapayamak_settings_section',['id'=>'ks_user_name', 'type'=>'text', 'label'=>'نام کاربری شما در سامانه صباپیامک', 'placeholder'=>'نام کاربری را وارد کنید']);
    add_settings_field('ks_password','رمز وب‌سرویس','SabaPayamak\textbox_callback_function','sabapayamak','sabapayamak_settings_section',['id'=>'ks_password', 'type'=>'password','label'=>'رمز وب‌سرویس شما در سامانه صباپیامک', 'placeholder'=>'رمز وب‌سرویس را وارد کنید']);
    add_settings_field('ks_vnumber','شماره مجازی','SabaPayamak\textbox_callback_function','sabapayamak','sabapayamak_settings_section',['id'=>'ks_vnumber', 'type'=>'text','label'=>'شماره مجازی شما در سامانه صباپیامک', 'placeholder'=>'شماره مجازی را وارد کنید', 'numbers_only'=>true]);
    add_settings_field('ks_web_service_domain','دامنه وب‌سرویس','SabaPayamak\textbox_callback_function','sabapayamak','sabapayamak_settings_section',['id'=>'ks_web_service_domain','label'=>'اگر دامنه اختصاصی دارید وارد کنید']);
    add_settings_field('ks_api_domain','دامنه ای‌پی‌آی','SabaPayamak\textbox_callback_function','sabapayamak','sabapayamak_settings_section',['id'=>'ks_api_domain','label'=>'اگر دامنه اختصاصی برای ای‌پی‌آی دارید وارد کنید']);
    add_settings_field('ks_send_method','روش ارسال','SabaPayamak\radio_callback_function','sabapayamak','sabapayamak_settings_section',['id'=>'ks_send_method','label'=>'روش ارسال پیامک از طریق سامانه صباپیامک']);
    add_settings_field('ks_api_method','نحوه اتصال','SabaPayamak\radio_callback_function','sabapayamak','sabapayamak_settings_section',['id'=>'ks_api_method','label'=>'نحوه اتصال به سامانه صباپیامک']);

    add_settings_section('sabapayamak_wp_section','تنظیمات وردپرس', 'SabaPayamak\settings_section_callback','sabapayamak');
    $mobile_user_meta = Helpers::$default_mobile_user_meta;
    add_settings_field('ks_mobile_user_meta','یوزر متای شماره همراه','SabaPayamak\textbox_callback_function','sabapayamak','sabapayamak_wp_section',['id'=>'ks_mobile_user_meta', 'type'=>'text', 'label'=>"برای استفاده از متای پیش‌فرض ($mobile_user_meta) این فیلد را خالی بگذارید."]);
    add_settings_field('ks_force_reg_edit_field','الزامی بودن فیلد شماره همراه','SabaPayamak\checkbox_callback_function','sabapayamak','sabapayamak_wp_section',['id'=>'ks_force_reg_edit_field', 'label'=>"با انتخاب این گزینه فیلد شماره همراه به صورت یک فیلد الزامی به فرم‌های ثبت‌نام و ویرایش کاربر اضافه می‌شود."]);
}
add_action('admin_init', 'SabaPayamak\ks_register_setting');

<?php
/*
 * Plugin Name:     sabapayamak
 * Description:     افزونه ارسال پیامک در وردپرس - <a href="http://sabapayamak.com" target="_blank">سامانه پیام کوتاه صباپیامک</a>
 * Version:         1.0.0
 * Requires PHP:    7.4
 * Author:          شرکت کارانس
 * Author URI:      https://karans.co
 */

namespace SabaPayamak;

// جلوگیری از دسترسی مستقیم به فایل
if (!defined('ABSPATH')) exit;

define("SABAPAYAMAK_DIR_PATH", plugin_dir_path(__FILE__));
define("SABAPAYAMAK_DIR_URL", plugin_dir_url(__FILE__));
define("SABAPAYAMAK_WP_PLUGINS_DIR_PATH", plugin_dir_path(__DIR__));

require SABAPAYAMAK_DIR_PATH . "includes/security.php";
require SABAPAYAMAK_DIR_PATH . "includes/general.php";
require SABAPAYAMAK_DIR_PATH . "includes/db.php";
require SABAPAYAMAK_DIR_PATH . "includes/ui.php";
require SABAPAYAMAK_DIR_PATH . "includes/l2fa.php";
require SABAPAYAMAK_DIR_PATH . "includes/events.php";
require SABAPAYAMAK_DIR_PATH . "includes/sms.php";
require SABAPAYAMAK_DIR_PATH . "includes/SabapayamakApi.php";
require SABAPAYAMAK_DIR_PATH . "includes/date.php";
require SABAPAYAMAK_DIR_PATH . "includes/sms_log.php";

require SABAPAYAMAK_DIR_PATH . "woocommerce/WoocommerceIR_SMS.php";

/**
 * جاوا اسکریپت پابلیک
 */
function ks_add_js($hook)
{
    $src = SABAPAYAMAK_DIR_URL . "public/js/script.js";
    wp_enqueue_script('sabapayamak-public-js', $src, array(), null, false);
}
add_action('wp_enqueue_scripts', 'SabaPayamak\ks_add_js');

//  پنل مدیریت
if (is_admin()) {

    require SABAPAYAMAK_DIR_PATH . "admin/settings-register.php";
    require SABAPAYAMAK_DIR_PATH . "admin/settings-callback.php";
    require SABAPAYAMAK_DIR_PATH . "admin/settings-validate.php";
    require SABAPAYAMAK_DIR_PATH . "admin/table.php";

    if (Helpers::is_options_changed()) {
        // اگر تنظیمات توسط کاربر تغییر کرد، افزونه غیرفعال شود تا مجدداً تأیید شود
        update_option("sabapayamak_options_valid", 0);
        
        delete_option("sabapayamak_api_token");

        delete_transient("sabapayamak_options_confirm_otp");
    }

    // استایل ادمین
    function ks_add_admin_css()
    {
        $src = SABAPAYAMAK_DIR_URL . "admin/css/style.css";
        wp_enqueue_style('sabapayamak-admin-css', $src, array(), null, false);
    }
    add_action('admin_enqueue_scripts', 'SabaPayamak\ks_add_admin_css');

    // جاوااسکریپت ادمین
    function ks_add_admin_js($hook)
    {
        $src = SABAPAYAMAK_DIR_URL . "admin/js/script.js";
        wp_enqueue_script('sabapayamak-admin-js', $src, array(), null, false);
    }
    add_action('admin_enqueue_scripts', 'SabaPayamak\ks_add_admin_js');

    Helpers::add_mobile_to_users_table();
}

L2FA::init();

if(Helpers::get_sabapayamak_options() && Helpers::get_sabapayamak_options()['force_reg_edit_field'])
    Helpers::force_reg_edit_field();

Events::handle_events();

// هنگام فعال شدن افزونه
function ks_on_activation()
{
    if (DB::prepare_db()) {
        set_transient('ks_admin_activation_notice', true, 10);
    } 
    
    Helpers::delete_sabapayamak_options();
}
register_activation_hook(__FILE__, 'SabaPayamak\ks_on_activation');

// نمایش پیام هنگام فعال شدن افزونه
function ks_admin_activation_notice()
{
    if (get_transient('ks_admin_activation_notice')) {
        echo    "<div class='notice notice-success is-dismissible'>
                <p>صباپیامک با موفقیت فعال شد. جهت استفاده، اطلاعات کاربری را در بخش تنظیمات وارد کنید.</p>
                </div>";
    }
}
add_action('admin_notices', 'SabaPayamak\ks_admin_activation_notice');

// هنگام غیرفعال شدن افزونه
function ks_on_deactivation()
{
    Helpers::delete_sabapayamak_options();
}
register_deactivation_hook(__FILE__, 'SabaPayamak\ks_on_deactivation');

// شورت‌کد ارسال پیامک
add_shortcode('sabapayamak_send_sms', 'SabaPayamak\Helpers::send_sms_shortcode');
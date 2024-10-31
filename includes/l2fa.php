<?php

namespace SabaPayamak;

// جلوگیری از دسترسی مستقیم به فایل
if (!defined('ABSPATH')) exit;

class L2FA{

    private static $cookie_name = "sabapayamak_l2fa";

    private static $l2fa_errors;

    /**
     * اجرای متدهای مربوط به ورود دو مرحله‌ای در صورت فعال شدن توسط کاربر
     */
    public static function init()
    {
        if(!L2FA::is_l2fa_enabled())
            return;

        // پس از لاگین موفق کاربر
        add_action('wp_login', 'SabaPayamak\L2FA::after_login', 10, 2); 

        // متدهای مربوط به صفحه تأیید کد
        if (self::is_l2fa_url()) {
            add_action('init', "SabaPayamak\L2FA::handle_lf2a_actions");
            add_action("login_form", "SabaPayamak\L2FA::l2fa_form");
            add_filter('wp_login_errors', 'SabaPayamak\L2FA::l2fa_errors', 100, 3);
        }

        // اسکریپت‌های مورد نیاز
        add_action('login_footer', 'SabaPayamak\L2FA::l2fa_scripts');
    }

    /**
     * به‌روز رسانی تنظیمات ورود دومرحله‌ای
     */
    public static function update_l2fa()
    {
        if (isset($_POST['ks_l2fa_submit'])) {

            if(!wp_verify_nonce($_POST['_wpnonce']))
                wp_die("خطا در تصدیق درخواست.");

            $checked = isset($_POST['ks_l2fa_enabled']) ? true : false;

            if($checked){
                $roles = isset($_POST['ks_l2fa_roles']) ?  array_map('sanitize_text_field', $_POST['ks_l2fa_roles']) : "";
                update_option("ks_l2fa_roles", $roles);

                $l2fa_mandatory = isset($_POST['ks_l2fa_mandatory']) ? true : false;
                update_option("ks_l2fa_mandatory", $l2fa_mandatory);

                $pattern = isset($_POST['ks_pattern_input']) ? sanitize_textarea_field($_POST['ks_pattern_input']) : "";
                
                if (isset($_POST['ks_pattern_input'])) {
                    if (self::is_pattern_valid($pattern)) {
                        update_option('ks_l2fa_pattern_input', $pattern);
                    } else {
                        Helpers::add_notice("تگ رمز یک‌بار مصرف را در الگوی پیامک وارد کنید.", "error");
                        return;
                    }
                }
            }

            update_option('ks_l2fa_enabled', $checked);

            Helpers::add_notice("تغییرات با موفقیت ثبت شد.", "success");
        }
    }

    /**
     * آیا ورود دومرحله‌ای توسط مدیر سایت فعال شده است؟
     */
    public static function is_l2fa_enabled()
    {
        return get_option("ks_l2fa_enabled");
    }

    /**
     * ری‌دایرکت پس از ورود کاربر از قسمت لاگین وردپرس
     */
    public static function wp_redirect($redirect_to, $request, $user)
    {
        $mobile_number = Helpers::get_user_mobile_number($user->ID);
        return wp_login_url() . "?action=l2fa" . "&redirect_to=$redirect_to&mobile_number=$mobile_number";
    }

    /**
     * ری‌دایرکت پس از ورود کاربر از قسمت لاگین ووکامرس
     */
    public static function wc_redirect($redirect, $user)
    {
        $mobile_number = Helpers::get_user_mobile_number($user->ID);
        return wp_login_url() . "?action=l2fa" . "&redirect_to=$redirect&mobile_number=$mobile_number";
    }

    /**
     * آیا آدرس فعلی مربوط به صفحه ورود دومرحله‌ای است؟
     */
    public static function is_l2fa_url()
    {
        return  Helpers::remove_url_protocol(Helpers::get_current_url(false)) == Helpers::remove_url_protocol(wp_login_url())
                && isset($_GET["action"])
                && $_GET["action"] == "l2fa";
    }

    /**
     * آیا آدرس فعلی مربوط به صفحه ثبت نام کاربر است؟
     */
    public static function is_register_url()
    {
        return  Helpers::remove_url_protocol(Helpers::get_current_url(false)) == Helpers::remove_url_protocol(wp_login_url())
                && isset($_GET["action"])
                && $_GET["action"] == "register";
    }

    /**
     * اضافه کردن خطا به خطاهای صفحه لاگین وردپرس
     */
    public static function l2fa_errors($errors, $redirect_to)
    {
        $errors->errors = array();
        
        if (isset(self::$l2fa_errors))
            $errors->add(array_keys(self::$l2fa_errors)[0], array_values(self::$l2fa_errors)[0]);

        return $errors;
    } 

    /**
     * عملیات مربوط به ورود دومرحله‌ای پس از لاگین کردن کاربر
     */
    public static function after_login($user_login, $user)
    {
        $logged_in_user_id = $user->ID;

        $roles = get_option('ks_l2fa_roles');
        if (!$roles || !Helpers::is_user_roles_in_roles($user->roles, array_keys(get_option('ks_l2fa_roles'))))
            return;

        $random_code = Helpers::get_random_code();

        $pattern = get_option("ks_l2fa_pattern_input");
        $message = Helpers::replace_tags_with_values(
                $pattern,
                array(
                    "{OTP_CODE}"    => Helpers::replace_digits_en2fa($random_code)
                ),
                $user->ID
            );

        $user_mobile_number = Helpers::get_user_mobile_number($logged_in_user_id);

        if(empty($user_mobile_number)){
            $is_l2fa_mandatory = get_option("ks_l2fa_mandatory");
            if($is_l2fa_mandatory)
                wp_die("شماره همراه جهت ورود دومرحله‌ای ثبت نشده است.");
            else
                return;
        }
        
        $sms = new SMS();
        $result = $sms->send_sms("پیامک تأیید ورود دومرحله‌ای", $user_mobile_number, $message);

        $cookie_name = self::$cookie_name;
        $ip = helpers::get_user_ip();
        $user_agent =  helpers::get_user_agent();
        $transient = "$cookie_name#$logged_in_user_id#$ip#$user_agent";
        set_transient($transient, $random_code, 2 * MINUTE_IN_SECONDS);

        $hash = str_replace('#', '' , Security::hash($logged_in_user_id));
        
        setcookie(self::$cookie_name, base64_encode("$logged_in_user_id#$hash"), time() + (2 * MINUTE_IN_SECONDS), "/", "", false, true);

        wp_logout();

        add_filter('login_redirect', 'SabaPayamak\L2FA::wp_redirect', 100, 3);
        if (Helpers::is_woocommerce_active()) {
            add_filter('woocommerce_login_redirect', 'SabaPayamak\L2FA::wc_redirect', 100, 2); 
        }
    }

    /**
     * هندل کردن اکشن‌های مربوط به صفحه ورود دومرحله‌ای
     */
    public static function handle_lf2a_actions()
    {
        $ip = helpers::get_user_ip();
        $user_agent =  helpers::get_user_agent();
        $cookie_name = self::$cookie_name;

        if(isset($_POST["ks_l2fa_confirm_btn"])){
            
            if(!wp_verify_nonce($_POST['_wpnonce']))
                wp_die("خطا در تصدیق درخواست.");

            if (isset($_COOKIE[$cookie_name])) {
                $cookie = sanitize_text_field(base64_decode($_COOKIE[$cookie_name]));
                $user_id = explode('#', $cookie)[0];
                $saved_otp_code = get_transient("$cookie_name#$user_id#$ip#$user_agent");
                if ($saved_otp_code !== false && self::is_cookie_valid($cookie)) {
                    $otp_input = filter_var(Helpers::replace_digits_fa2en($_POST["ks_l2fa_otp"]), FILTER_VALIDATE_INT, array('options' => array('min_range' => 1)));
                    if ($saved_otp_code === strval($otp_input)) {
                        wp_set_auth_cookie($user_id);
                        
                        do_action("ks_l2fa_login", $user_id);
                        
                        delete_transient("$cookie_name#$user_id");
                        unset($_COOKIE[self::$cookie_name]); 
                        $redirect_to = isset($_POST["redirect_to"]) ? wp_sanitize_redirect($_POST["redirect_to"]) : home_url();
                        wp_redirect($redirect_to);
                        exit();
                    }
                    else{
                        self::$l2fa_errors = empty(trim($otp_input)) ? array("empty_otp" => "رمز یک بار مصرف را وارد کنید.") : array("incorrect_otp" => "رمز وارد شده نادرست است.");
                    }
                }
            }
        }

        if(isset($_COOKIE[self::$cookie_name])){
            $cookie = sanitize_text_field(base64_decode($_COOKIE[self::$cookie_name]));
            $user_id = explode('#', $cookie)[0];
            $transient = "$cookie_name#$user_id#$ip#$user_agent";
            $saved_otp_code = get_transient($transient);
            if($saved_otp_code){
                ?>
                <script>
                    var ks_countdown_seconds = <?php echo esc_js(Helpers::get_transient_timeout($transient) - time());?>;
                    var ks_otpIntervalId = setInterval(function() {
                        ks_countdown_seconds -= 1;
                    if(ks_countdown_seconds > 0 )
                        document.getElementById("ks_countdown").innerHTML = ks_countdown_seconds + " ثانیه تا منقضی شدن کد";
                    else {
                        clearInterval(ks_otpIntervalId);
                        window.location.href = window.location.href;
                    }
                    }, 1000); 
                </script>
                <?php
            }
            else{
                wp_redirect(wp_login_url());
                exit();
            }
        }
        else{
            wp_redirect(wp_login_url());
            exit();
        }
    }

    /**
     * ویرایش فرم لاگین به فرم ورود دومرحله‌ای
     */
    public static function l2fa_form()
    {
        if (isset($_Get["redirect_to"])) {
            $redirect_to = wp_sanitize_redirect($_Get["redirect_to"]);
            echo "<input type='hidden' name='redirect_to' value='" . esc_url($redirect_to) . "'>";
        }

        $mobile_number = (isset($_GET['mobile_number']) && Helpers::is_mobile_valid($_GET['mobile_number'])) ? " (" . sanitize_text_field($_GET['mobile_number']) . ")" : "";

        ?>
            <p>
                <label for="ks_l2fa_otp">رمز یک بار مصرف ارسال شده به شماره همراه خود<?php echo esc_attr($mobile_number); ?> را وارد کنید</label>
                <input type="text" id="ks_l2fa_otp" name="ks_l2fa_otp" class="input only-numbers-allowed" value="" maxlength="6" />
                <input class="button button-primary button-large" type="submit" name="ks_l2fa_confirm_btn" value="تأیید">
                <p id="ks_countdown"></p>
            </p>
            <script>document.getElementById('ks_l2fa_otp').focus();</script>
        <?php
        
        wp_nonce_field();

        add_action('login_footer', 'SabaPayamak\hide_login_form_elements');
        // حذف  فیلدهای فرم لاگین وردپرس
        function hide_login_form_elements()
        {
            ?>
                <script>
                    document.querySelector("#user_login").parentNode.remove();
                    document.querySelector(".user-pass-wrap").remove();
                    document.querySelector(".forgetmenot").remove();
                    document.querySelector("#wp-submit").remove();
                    document.querySelector("#nav").remove();
                    document.querySelector("#backtoblog").remove();
                    document.querySelector('#loginform').removeAttribute("action");
                </script>
            <?php
        }
    }

    /**
     * افزودن اسکریپت‌های مورد نیاز 
     */
    public static function l2fa_scripts()
    {
        echo "<script src='" . esc_url(WP_PLUGIN_URL . "/sabapayamak/public/js/script.js") . "'></script>";
    }

    /**
     * آیا الگوی وارد شده جهت پیامک ورود دومرحله‌ای معتبر است؟
     */
    public static function is_pattern_valid($pattern)
    {
        return strpos($pattern, '{OTP_CODE}') !== false ;
    }

    /**
     * آیا کوکی مربوط به ورود دومرحله‌ای معتبر است؟
     */
    public static function is_cookie_valid($cookie)
    {
        $cookie_array = explode('#', $cookie);
        $id = $cookie_array[0];
        $hash = $cookie_array[1];
        $is_cookie_valid = hash_equals(str_replace('#', '', Security::hash($id)), $hash);
        return $is_cookie_valid;
    }

}
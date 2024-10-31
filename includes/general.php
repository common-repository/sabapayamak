<?php

namespace SabaPayamak;

use DateTime;

// جلوگیری از دسترسی مستقیم به فایل
if (!defined('ABSPATH')) exit;

class Helpers
{   
    public static $default_mobile_user_meta = "mobile_number";
    
    /**
     * تنظیمات پیش‌فرض مربوط به سامانه صباپیامک
     */
    public static function sabapayamak_default_options()
    {
        return array(
            'ks_user_name'          => '',
            'ks_password'           => '',
            'ks_vnumber'            => '',
            'ks_web_service_domain' => 'http://my.sabapayamak.com',
            'ks_api_domain'         => 'https://api.sabapayamak.com',
            'ks_send_method'        => 'POST',
            'ks_api_method'         => 'API'
        );
    }

    /**
     * گرفتن آپشن‌های مربوط به سامانه صباپیامک
     */
    public static function get_sabapayamak_options()
    {
        $options = get_option("sabapayamak_options");
        if($options === false)
            return false;

        $saba_options["user_name"] = $options["ks_user_name"];
        $saba_options["password"] = $options["ks_password"];
        $saba_options["v_number"] = $options["ks_vnumber"];
        $saba_options["web_service_domain"] = $options["ks_web_service_domain"];
        $saba_options["api_domain"] = $options["ks_api_domain"];
        $saba_options["send_method"] = $options["ks_send_method"];
        $saba_options["api_method"] = $options["ks_api_method"];
        $saba_options["mobile_user_meta"] = empty($options["ks_mobile_user_meta"]) ? self::$default_mobile_user_meta : $options["ks_mobile_user_meta"];
        $saba_options["force_reg_edit_field"] = empty($options["ks_force_reg_edit_field"]) ? 0 : 1;

        return $saba_options;
    }

    /**
     * آیا تنظیمات مربوط به سامانه صباپیامک تغییر کرده است؟
     */
    public static function is_options_changed()
    {
        $options_changed = false;
        
        if (
            isset($_POST["option_page"])
            && $_POST["option_page"] == "sabapayamak_options"
            && isset($_POST["action"])
            && $_POST["action"] == "update"
        ) {
            $before = get_option("sabapayamak_options");
            $posted = array_map('sanitize_text_field', $_POST["sabapayamak_options"]);
            
            $options_needing_verification = array(
                "ks_user_name",
                "ks_password",
                "ks_vnumber",
                "ks_web_service_domain",
                "ks_api_domain",
                "ks_send_method",
                "ks_api_method"
            );

            foreach ($options_needing_verification as $key => $option) {
                if ($before[$option] != $posted[$option])
                    $options_changed = true;
            }
        }

        return $options_changed;
    }

    /**
     * اکشن‌های مربوط به پیامک تأیید تنظیمات سامانه صباپیامک
     */
    public static function option_validation_actions()
    {
        if (isset($_POST["ks_send_confirm_otp_btn"])) {
            $code = self::replace_digits_en2fa(self::get_random_code());
            $message = "کاربر گرامی کد شما جهت تأیید اطلاعات کاربری در افزونه صباپیامک $code می‌باشد.";
            $to = sanitize_text_field($_POST["ks_send_confirm_otp_number"]);

            if(empty($to)){
                self::add_notice("شماره همراه را وارد کنید.", "error");
                return;
            }

            if(!self::is_mobile_valid($to)){
                self::add_notice("شماره همراه معتبر نمی‌باشد.", "error");
                return;
            }
            
            $sms = new SMS();
            $result = $sms->send_sms("پیامک تأیید تنظیمات افزونه", $to, $message);

            if ($result){
                set_transient("sabapayamak_options_confirm_otp", $code, 2 * MINUTE_IN_SECONDS);
                self::add_notice(ResultStatus::Ok, "success");
            }
            else{
                self::add_notice(DB::get_last_sms_log()[0]['result'], "error");
            }
        }

        if (isset($_POST["ks_confirm_options_btn"])) {
            $entered_otp = Helpers::replace_digits_en2fa(absint($_POST["ks_confirm_options_code"]));
            if(empty($entered_otp)){
                self::add_notice("کد را وارد کنید.", "error");
            }
            elseif ($entered_otp === get_transient("sabapayamak_options_confirm_otp")) {
                update_option("sabapayamak_options_valid", 1);
                delete_transient("sabapayamak_options_confirm_otp");
                Helpers::add_notice("تنظیمات تأیید شد.","success");
            }
            else{
                self::add_notice("کد وارد شده معتبر نمی‌باشد.", "error");
            }

        }
    }

    /**
     * آیا تنظیمات مربوط به سامانه صباپیامک از طریق پیامک تأیید شده است؟
     */
    public static function is_sabapayamak_options_validated()
    {
        return get_option("sabapayamak_options_valid");
    }

    /**
     * گرفتن زمان یک ترنسینت
     */
    public static function get_transient_timeout($transient)
    {
        return get_option( "_transient_timeout_$transient", 0 );
    }

    /**
     * پیام مربوط به وضعیت قعلی افزونه
     */
    public static function get_sabapayamak_status_msg()
    {
        if (!self::get_sabapayamak_options())
            return "<p class='cancel-mark' style='color:crimson;'>غیرفعال (تنظیمات ثبت نشده است)</p>";
        
        if (!self::is_sabapayamak_options_validated())
            return "<p class='cancel-mark' style='color:crimson;'>غیرفعال (تنظیمات تأیید نشده است)</p>";
        
        return "<p class='check-mark' style='color:forestgreen;'>تنظیمات تأیید شده است</p>";
    }

    /**
     * جایگزین کرد تگ‌ها با مقادیر در الگوی پیامک
     */
    public static function replace_tags_with_values($pattern, Array $tags_values_array, $user_id = null)
    {
        $tags_to_replace = array(
            "{DATE}"        => Helpers::get_current_shamsi_date(),
            "{TIME}"        => Helpers::get_current_time()
        );

        if ($user_id) {
            $tags_to_replace = array_merge($tags_to_replace, array(
                "{USER_ID}"     => self::replace_digits_en2fa($user_id),
                "{USER_NAME}"   => get_user_by('id', $user_id)->user_login
            ));
        }

        $tags_to_replace = array_merge($tags_to_replace, $tags_values_array);

        foreach ($tags_to_replace as $key => $value) {
            $pattern = str_replace($key, $value, $pattern);
        }

        return $pattern;
    }

    /**
     * گرفتن کد تصادفی 
     */
    public static function get_random_code()
    {
        return random_int(100000, 999999);
    }

    /**
     * گرفتن تاریخ فعلی شمسی
     */
    public static function get_current_shamsi_date()
    {
        return self::replace_digits_en2fa(jdate("Y/m/d"));
    }
    
    /**
     * گرفتن ساعت فعلی
     */
    public static function get_current_time()
    {
        return self::replace_digits_en2fa(wp_date('H:i:s'));
    }

    /**
     * تبدیل تاریخ میلادی به شمسی
     */
    public static function miladi_to_shamsi_date(DateTime $date, $delimiter = "/", $include_time = true)
    {
        $jalali_YMD = gregorian_to_jalali(
            $date->format('Y'),
            $date->format('m'),
            $date->format('d')
        );

        $jalali_date = implode($delimiter, $jalali_YMD);

        if ($include_time) {
            $time = array(
                $date->format('H'),
                $date->format('i'),
                $date->format('s'),
            );
            $jalali_date = implode(":", $time) . " " . $jalali_date;
        }

        return $jalali_date;
    }

    /**
     * جایگزین کردن اعداد انگلیسی با فارسی
     */
    public static function replace_digits_en2fa(string $input)
    {
        $fa_digits = ['۱','۲','۳','۴','۵','۶','۷','۸','۹','۰'];
        $en_digits = ['1','2','3','4','5','6','7','8','9','0'];

        $output = str_replace($en_digits, $fa_digits, $input);

        return $output; 
    } 

    /**
     * جایگزین کردن اعداد فارسی با انگلیسی
     */
    public static function replace_digits_fa2en(string $input)
    {
        $fa_digits = ['۱','۲','۳','۴','۵','۶','۷','۸','۹','۰'];
        $en_digits = ['1','2','3','4','5','6','7','8','9','0'];

        $output = str_replace($fa_digits, $en_digits, $input);

        return $output; 
    }
    
    /**
     * جایگزین کردن کاراکتر خط جدید
     */
    public static function replace_new_line($input, $replacement = ",")
    {
        $input = str_replace("\r\n", $replacement, $input);
        $input = str_replace("\r", $replacement, $input);
        $input = str_replace("\n", $replacement, $input);
        return $input;
    }

    /**
     * آیا صفحه کنونی، صفحه صباپیامک است
     */
    public static function is_sabapayamak_page()
    {
        return isset($_GET['page']) && $_GET['page'] == 'sabapayamak';
    }
    
    /**
     * پاکسازی پارامترهای یوآرال
     */
    public static function clean_url($params_to_keep = ["page", "tab", "orderby", "order", "paged", "events_table_nonce"])
    {
        $uri_parts = explode('?', esc_url($_SERVER['REQUEST_URI']), 2);
        $url = esc_url($_SERVER['HTTP_HOST']) . $uri_parts[0] . "?";

        $get_params = [];

        foreach ($_GET as $key => $value) {
            if ((in_array($key, $params_to_keep)))
                $get_params[sanitize_text_field($key)] = sanitize_text_field($value);
        }

        $query_string_array = [];

        foreach ($get_params as $key => $value) {
            array_push($query_string_array, $key . "=" . $value);
        }

        $query_string = join("&", $query_string_array);

        $url .= $query_string;

        $url = self::maybe_https($url);
        return $url;
    }

    /**
     * گرفتن یوآرال فعلی
     */
    public static function get_current_url($include_query_strings = true)
    {
        if ($include_query_strings) 
            $url = esc_url($_SERVER['HTTP_HOST'] . "$_SERVER[REQUEST_URI]");
        else
            $url = esc_url($_SERVER['HTTP_HOST'] . "$_SERVER[PHP_SELF]");

        $url = self::maybe_https($url);
        return $url;
    }

    /**
     * اضافه کردن پیام اعلان
     */
    public static function add_notice($message, $type)
    {
        echo "<script type='text/javascript'>
            document.addEventListener('DOMContentLoaded', function () {
                ks_addNotice('" . esc_js($message) . "', '" . esc_js($type) . "');
            });
            </script>";
    }

    /**
     * آیا اکستنشن سوپ در سایت فعال است؟
     */
    public static function is_soap_enabled()
    {
        return extension_loaded('soap');
    }
    
    /**
     * گرفتن متای شماره همراه کاربر
     */
    public static function get_mobile_user_meta()
    {
        $mobile_user_meta = (!self::get_sabapayamak_options()) ? "" : self::get_sabapayamak_options()["mobile_user_meta"];

        if (empty($mobile_user_meta)) 
            $mobile_user_meta = Helpers::$default_mobile_user_meta;

        return $mobile_user_meta;
    }

    /**
     * گرفتن شماره همراه کاربر
     */
    public static function get_user_mobile_number($user_id)
    {
        // برای مشتریان ووکامرس از billing_phone استفاده می‌شود
        if (self::is_woocommerce_active() && self::is_user_roles_in_roles(self::get_user_roles($user_id), ["Customer"])) 
            $mobile_meta = 'billing_phone';
        else
            $mobile_meta = self::get_mobile_user_meta();

        return get_user_meta($user_id, $mobile_meta, true);
    }

    /**
     * گرفتن نام کاربر
     */
    public static function get_user_first_name($user_id)
    {
        $first_name_user_meta = "first_name";
        
        return get_user_meta($user_id, $first_name_user_meta, true);
    }

    /**
     * گرفتن نام خانوادگی کاربر
     */
    public static function get_user_last_name($user_id)
    {
        $last_name_user_meta = "last_name";
        
        return get_user_meta($user_id, $last_name_user_meta, true);
    }

    /**
     * آیا شماره همراه معتبر است؟
     */
    public static function is_mobile_valid($mobile_number)
    {
        $result = preg_match("/^(\+98|0|0098)?9\d{9}$/", Helpers::replace_digits_fa2en($mobile_number));
        return $result;
    }

    /**
     * نمایش فیلد متای موبایل در منوهای ثبت نام و ویرایش کاربر
     */
    public static function add_mobile_user_meta()
    {

        // اضافه کردن موبایل به فیلدهای اطلاعات کاربر
        function add_mobile_user_profile_field($user){

            // برای مشتریان ووکامرس از billing_phone استفاده می‌شود
            if (Helpers::is_woocommerce_active() && Helpers::is_user_roles_in_roles(Helpers::get_user_roles($user->ID), ["Customer"])) 
                return;

            $mobile_user_meta = Helpers::get_mobile_user_meta();
            $mobile_number = isset($user->ID) ? get_the_author_meta($mobile_user_meta, $user->ID) : "";
            ?>
              <table class="form-table">
                  <tr>
                      <th><label for="<?php echo esc_attr($mobile_user_meta); ?>">شماره همراه</label></th>
                      <td>
                          <input type="text" name="<?php echo esc_attr($mobile_user_meta); ?>" class="only-numbers-allowed" value="<?php echo esc_attr($mobile_number); ?>" id="<?php echo esc_attr($mobile_user_meta); ?>" maxlength="11" /><br />
                          <span class="description">شماره همراه جهت ارسال پیامک</span>
                      </td>
                  </tr>
              </table>
            <?php
        }
        add_action( 'show_user_profile', 'SabaPayamak\add_mobile_user_profile_field' );
        add_action( 'edit_user_profile', 'SabaPayamak\add_mobile_user_profile_field' );
        add_action( "user_new_form", "SabaPayamak\add_mobile_user_profile_field" );
        
        // قابلیت ذخیره موبایل
        function save_mobile_user_profile_field($user_id){
            if(!current_user_can('manage_options'))
                return false;

            // برای مشتریان ووکامرس از billing_phone استفاده می‌شود
            if (Helpers::is_woocommerce_active() && Helpers::is_user_roles_in_roles(Helpers::get_user_roles($user_id), ["Customer"])) 
                return;
        
            update_user_meta($user_id, Helpers::get_mobile_user_meta(), sanitize_text_field($_POST[Helpers::get_mobile_user_meta()]));
        }
        // add_action('user_register', 'SabaPayamak\save_mobile_user_profile_field');
        add_action('profile_update', 'SabaPayamak\save_mobile_user_profile_field');

        // خطای ولیدیشن در صورت معتبر نبودن فیلد
        function validate_mobile_number_field($errors, $update, $user)
        {
            // برای مشتریان ووکامرس از billing_phone استفاده می‌شود
            if (Helpers::is_woocommerce_active() && Helpers::is_user_roles_in_roles(Helpers::get_user_roles($user->ID), ["Customer"])) 
                return;

            if (empty($_POST[Helpers::get_mobile_user_meta()]))
                $errors->add('mobile_number_not_valid', "<strong>خطا</strong>: شماره همراه را وارد کنید.");
            elseif (!Helpers::is_mobile_valid(($_POST[Helpers::get_mobile_user_meta()])))
                $errors->add('mobile_number_not_valid', "<strong>خطا</strong>: شماره همراه معتبر نیست.");
        }
        add_action( 'user_profile_update_errors', 'SabaPayamak\validate_mobile_number_field', 10, 3);
    }

    /**
     * اضافه کردن موبایل به جدول کاربران
     */
    public static function add_mobile_to_users_table()
    {
        // اضافه کردن ستون موبایل به جدول کاربران
        function add_mobile_column_head($column) {
            $column[Helpers::get_mobile_user_meta()] = 'شماره همراه';
            return $column;
        }
        add_filter('manage_users_columns', 'SabaPayamak\add_mobile_column_head');

        // اضافه کردن اطلاعات موبایل به جدول کاربران
        function add_mobile_column_val( $val, $column_name, $user_id)
        {
            if($column_name == Helpers::get_mobile_user_meta()){
                $number = Helpers::get_user_mobile_number($user_id);
                return empty($number) ? "ثبت نشده" : $number ;
            }

            return $val;
        }
        add_filter('manage_users_custom_column', 'SabaPayamak\add_mobile_column_val', 10, 3);
    }

    /**
     * همه تگ‌های مورد استفاده در الگوی پیامک
     */
    public static function get_tags()
    {
        $tags = array(
            array(
                "name"          => "رمز یک‌بار مصرف",
                "tag"           => "OTP_CODE",
                "event_name"    =>  array(
                    "L2FA",
                )
            ),
            array(
                "name"          => "تاریخ",
                "tag"           => "DATE",
                "event_name"    =>  array(
                    "L2FA",
                    "user_login",
                    "new_user",
                    "user_updated",
                    "user_deleted",
                    "new_comment",
                    "comment_trashed",
                    "comment_deleted",
                    "comment_updated",
                    "post_created",
                    "post_trashed",
                    "post_deleted",
                    "post_updated",
                    "plugin_activated",
                    "plugin_deactivated",
                    "plugin_deleted"
                )
            ),
            array(
                "name"          => "زمان",
                "tag"           => "TIME",
                "event_name"    =>  array(
                    "L2FA",
                    "user_login",
                    "new_user",
                    "user_updated",
                    "user_deleted",
                    "new_comment",
                    "comment_trashed",
                    "comment_deleted",
                    "comment_updated",
                    "post_created",
                    "post_trashed",
                    "post_deleted",
                    "post_updated",
                    "plugin_activated",
                    "plugin_deactivated",
                    "plugin_deleted"
                )
            ),
            array(
                "name"          => "شناسه کاربری",
                "tag"           => "USER_ID",
                "event_name"    =>  array(
                    "user_login",
                    "new_user",
                    "user_updated",
                    "user_deleted",
                    "post_created"
                )
            ),
            array(
                "name"          => "نام کاربری",
                "tag"           => "USER_NAME",
                "event_name"    =>  array(
                    "L2FA",
                    "user_login",
                    "new_user",
                    "user_updated",
                    "user_deleted",
                    "post_created"
                )
            ),
            array(
                "name"          => "نام نظردهنده",
                "tag"           => "COMMENT_AUTHOR",
                "event_name"    =>  array(
                    "new_comment",
                    "comment_trashed",
                    "comment_deleted",
                    "comment_updated"
                )
            ),
            array(
                "name"          => "متن نظر",
                "tag"           => "COMMENT_CONTENT",
                "event_name"    =>  array(
                    "new_comment",
                    "comment_trashed",
                    "comment_deleted",
                    "comment_updated"
                )
            ),
            array(
                "name"          => "عنوان پست نظر",
                "tag"           => "COMMENT_POST_TITLE",
                "event_name"    =>  array(
                    "new_comment",
                    "comment_trashed",
                    "comment_deleted",
                    "comment_updated"
                )
            ),
            array(
                "name"          => "شناسه پست",
                "tag"           => "POST_ID",
                "event_name"    =>  array(
                    "post_created",
                    "post_trashed",
                    "post_deleted",
                    "post_updated"
                )
            ),
            array(
                "name"          => "عنوان پست",
                "tag"           => "POST_TITLE",
                "event_name"    =>  array(
                    "post_created",
                    "post_trashed",
                    "post_deleted",
                    "post_updated"
                )
            ),
            array(
                "name"          => "نام افزونه",
                "tag"           => "PLUGIN_NAME",
                "event_name"    =>  array(
                    "plugin_activated",
                    "plugin_deactivated",
                    "plugin_deleted"
                )
            ),
        );

        return $tags;
    }

    /**
     * فیلتر کردن تگ‌ها بر اساس نام رویداد
     */
    public static function get_tags_by_event_name($event_name)
    {
        $array = array();
        $array = array_filter(
            self::get_tags(),
            function($element) use ($event_name){
                return in_array($event_name, $element['event_name']);
            }
        );

        return $array;
    }

    /**
     * شورت‌کد ارسال پیامک
     */
    public static function send_sms_shortcode($attr)
    {
        $args = shortcode_atts( array(
                'number'        => '',
                'message'       => '',
                'description'   => 'ارسال پیامک از طریق شورت‌کد صباپیامک'
            ), $attr );
            
        $sms = new SMS();
        $result = $sms->send_sms($args['description'], $args['number'], $args['message']);
    
        return $result;
    }

    /**
     * آیا این افزونه فعال است؟
     */
    public static function is_plugin_active($plugin)
    {
        return in_array($plugin, (array)get_option('active_plugins', array()));
    }

    /**
     * آیا افزونه ووکامرس فعال است؟
     */
    public static function is_woocommerce_active()
    {
        return self::is_plugin_active("woocommerce/woocommerce.php");
    }

    /**
     * حذف کردن آپشن‌های افزونه
     */
    public static function delete_sabapayamak_options()
    {
        delete_option('sabapayamak_options');
        delete_option("sabapayamak_options_valid");
        delete_option("ks_l2fa_enabled");
        delete_option("ks_l2fa_roles");

        // مربوط به افزونه پیامک ووکامرس
        delete_option( 'pwoosms_table_archive' );
        delete_option( 'pwoosms_table_contacts' );
        delete_option( 'pwoosms_hide_about_page' );
        delete_option( 'pwoosms_redirect_about_page' );
    }

    /**
     * گرفتن نقش‌های کاربر
     */
    public static function get_user_roles($user_id)
    {
        $user_roles = get_userdata($user_id)->roles;
        return $user_roles;
    }

    /**
     * گرفتن نقش‌های تعریف شده در وردپرس
     */
    public static function get_wp_roles()
    {
        $all_roles = array_column(get_editable_roles(), 'name');
        return $all_roles;
    }

    /**
     * چک کردن اینکه نقش‌های کاربر در آرایه نقش‌ها هست
     */
    public static function is_user_roles_in_roles($user_roles, $roles_array)
    {
        $roles_array = array_map('strtolower', $roles_array);
        foreach ($user_roles as $role) {
            if(in_array(strtolower($role), $roles_array))
                return true;
        }
    }

    /**
     * اکشن‌های تب ارسال پیامک
     */
    public static function send_tab_actions()
    {
        if (isset($_POST['ks_send_sms'])) {

            if(!wp_verify_nonce($_POST['_wpnonce']))
                wp_die("خطا در تصدیق درخواست.");

            $send_type = sanitize_text_field($_POST['ks_send_type']);
            switch ($send_type) {
                case 'ks_numbers':
                    $mobile_numbers = sanitize_textarea_field($_POST['ks_sms_numbers']);
                    if (empty(trim($mobile_numbers))) {
                        Helpers::add_notice("شماره‌ها را وارد کنید.", "error");
                        return;
                    }

                    $mobile_numbers = array_filter(array_map("trim", explode(",", Helpers::replace_new_line($mobile_numbers))));
                    $mobile_numbers = array_filter($mobile_numbers,"SabaPayamak\Helpers::is_mobile_valid");
                    if (empty($mobile_numbers)) {
                        Helpers::add_notice("شماره‌ها معتبر نیستند.", "error");
                        return;
                    }
                    
                    break;
                
                case 'ks_users':
                    $mobile_numbers = array_values(Helpers::get_users_with_mobile_number());
                    break;
                
                case 'ks_roles':
                    $selected_roles = isset($_POST['ks_l2fa_roles']) ? array_map('sanitize_text_field', $_POST['ks_l2fa_roles']) : "";
                    if (empty($selected_roles)){
                        Helpers::add_notice("حداقل یک نقش را انتخاب کنید.", "error");
                        return;
                    }
                    
                    $users = Helpers::get_users_with_mobile_number();
                    $mobile_numbers = array();
                    foreach ($users as $id => $mobile) {
                        $user_roles = helpers::get_user_roles($id);
                        if (Helpers::is_user_roles_in_roles($user_roles, array_keys($selected_roles))) 
                            array_push($mobile_numbers, $mobile);
                    }

                    if(empty($mobile_numbers)){
                        Helpers::add_notice("شماره‌ای برای کاربران نقش‌های انتخابی یافت نشد.", "error");
                        return;
                    }
                    
                    break;
                
                default:
                    Helpers::add_notice("نوع ارسال را انتخاب کنید.", "error");
                    return;
                    break;
            }

            $message = sanitize_textarea_field($_POST['ks_sms_input']);

            if (empty($message)) {
                Helpers::add_notice("متن پیامک را وارد کنید.", "error");
                return;
            }

            $sms = new SMS();
            $result = $sms->send_sms("ارسال دستی", $mobile_numbers, $message);
            if ($result)
                Helpers::add_notice(ResultStatus::Ok, "success");
            else
                Helpers::add_notice(ResultStatus::NoResult, "error");
        }
    }

    /**
     * گرفتن کاربرانی که دارای شماره هستند به صورت آرایه آی‌دی کاربر و شماره همراه
     */
    public static function get_users_with_mobile_number()
    {
        $all_users = get_users();

        $users_with_mobile = array();

        foreach ($all_users as $user) {
            $mobile_number = self::get_user_mobile_number($user->ID);
            if (self::is_mobile_valid(($mobile_number)))
                $users_with_mobile[$user->ID] = $mobile_number;
        }

        return $users_with_mobile;
    }
    
    /**
     * اصلاح شماره همراه برای ارسال دسته‌جمعی
     */
    public static function repair_mobile_number($mobile_number)
    {
        $mobile_number = trim($mobile_number);
        if ($mobile_number == "")
            return "0";
        
        $correct_number =self::replace_digits_fa2en($mobile_number);
        
        if (strpos($correct_number, "+9809") === 0)
            $correct_number = substr($correct_number, 4);
        elseif (strpos($correct_number, "+989") === 0)
            $correct_number =substr($correct_number, 3);
        elseif (strpos($correct_number, "00") === 0)
            $correct_number = substr($correct_number, 2);
        elseif (strpos($correct_number, "+46") === 0)
            $correct_number = substr($correct_number, 1);
        elseif (strpos($correct_number, "989") === 0)
            $correct_number = substr($correct_number, 2);
        elseif (strpos($correct_number, "9809") === 0)
            $correct_number = substr($correct_number, 3);
        elseif (strpos($correct_number, "09") === 0)
            $correct_number = substr($correct_number, 1);
        elseif (strpos($correct_number, "98") === 0)
            $correct_number = substr($correct_number, 2);

        return trim($correct_number);
    }

    /**
     * الزامی کردن فیلد شماره همراه در فرم‌های ثبت نام و ویرایش کاربر
     */
    public static function force_reg_edit_field()
    {
        // تغییرات فرم ثبت نام
        add_action('register_form', 'SabaPayamak\Helpers::register_form');
        
        // ولیدیشن موبایل هنگام ثبت نام
        add_filter('registration_errors', 'SabaPayamak\Helpers::mobile_validation_errors', 100, 3);

        // اضافه کردن فیلد موبایل به متای کاربر پس از تکمیل ثبت نام
        add_action('user_register', 'SabaPayamak\Helpers::update_user_meta_number');

        Helpers::add_mobile_user_meta();
    }
    
    /**
     * اضافه کردن فیلد شماره همراه به صفحه ثبت نام کاربر
     */
    public static function register_form()
    {
        $mobile_number = isset($_POST["ks_register_number"]) ? sanitize_text_field($_POST["ks_register_number"]) : "" ;
        
        ?>
            <p>
                <label for="ks_register_number">شماره همراه</label>
                <input type="text" id="ks_register_number" name="ks_register_number" class="input only-numbers-allowed" value="<?php echo esc_attr($mobile_number); ?>" maxlength="11" />
            </p>
        <?php
    }

    /**
     * اضافه کردن خطای ولیدیشن شماره همراه کاربر به خطاهای صفحه ثبت نام کاربر
     */
    public static function mobile_validation_errors($errors, $sanitized_user_login, $user_email)
    {
       if (isset($_POST["ks_register_number"])){
            if (empty($_POST["ks_register_number"]))
                $errors->add("mobile_number_required", "<strong>خطا</strong>: شماره همراه را وارد کنید.");
            elseif (!Helpers::is_mobile_valid($_POST["ks_register_number"])) 
                $errors->add("mobile_number_not_valid", "<strong>خطا</strong>: شماره همراه معتبر نیست.");
       }

       return $errors;
    }

    /**
     * به‌روز رسانی متای شماره همراه کاربر
     */
    public static function update_user_meta_number($user_id)
    {
        if (!empty($_POST['ks_register_number'])) {
            $mobile_user_meta = self::get_mobile_user_meta();
            $mobile_number = sanitize_text_field($_POST['ks_register_number']);

            add_action('shutdown', function () use ($user_id, $mobile_user_meta, $mobile_number) {
                update_user_meta($user_id, $mobile_user_meta, $mobile_number);
            });
        }
    }

    /**
     * گرفتن آدرس آی‌پی کاربر
     */
    public static function get_user_ip()
    {
        return sanitize_text_field($_SERVER['REMOTE_ADDR']);
    }

    /**
     * گرفتن یوزر ایجنت مرورگر کاربر
     */
    public static function get_user_agent()
    {
        return isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : "";
    }

    /**
     * اصلاح پروتکل اچ‌تی‌تی‌پی‌اس
     */
    public static function maybe_https($url)
    {
        if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
            str_replace("http://", "https://", $url);

        return $url;
    }

    /**
     * حذف پروتکل برای مقایسه
     */
    public static function remove_url_protocol($url){
        $url = str_replace("http://", "", $url);
        $url = str_replace("https://", "", $url);

        return $url;
    }
}
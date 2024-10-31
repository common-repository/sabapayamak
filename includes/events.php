<?php

namespace SabaPayamak;

// جلوگیری از دسترسی مستقیم به فایل
if (!defined('ABSPATH')) exit;

class Events
{
    private static $deleting_user;
    private static $deleting_plugin;
    
    /**
     * تغییر فعال/غیرفعال بودن ارسال پیامک برای رویداد تعریف شده
     */
    public static function event_handler_change_active($id, $is_active)
    {
        $result = DB::event_handler_change_active($id, $is_active);
        if ($result == 1) {
            Helpers::add_notice("تغییر وضعیت با موفقیت انجام شد", "success");
        }
        elseif ($result === false) {
            Helpers::add_notice("خطا در تغییر وضعیت", "error");
        }
    }

    /**
     * اضافه کردن هندلر برای رویداد
     */
    public static function add_event_handler()
    {
        if (isset($_REQUEST["ks_add_event_handler"]) && self::event_handler_validation()) {
            if (!wp_verify_nonce($_REQUEST['_wpnonce'])){
                Helpers::add_notice("خطا در تصدیق درخواست", "error");
                return;
            }

            $mobile_number = Helpers::is_mobile_valid($_REQUEST["ks_mobile_number"]) ? sanitize_textarea_field($_REQUEST["ks_mobile_number"]) : "";

            $result = DB::add_event_handler(absint($_REQUEST["ks_event_select"]), 0, sanitize_textarea_field($_REQUEST["ks_pattern_input"]) , $mobile_number, sanitize_text_field($_REQUEST["ks_send_to"]));
            if ($result == "1") {
                Helpers::add_notice("رویداد با موفقیت ثبت شد.", "success");
            } else {
                Helpers::add_notice("خطا در ثبت رویداد", "error");
            }
        }
    }
    
    /**
     * ویرایش کردن هندلر رویداد ارسال پیامک
     */
    public static function edit_event_handler()
    {
        if (isset($_REQUEST["ks_edit_event_handler"]) && self::event_handler_validation()) {

            if (!wp_verify_nonce($_REQUEST['_wpnonce'])){
                Helpers::add_notice("خطا در تصدیق درخواست", "error");
                return;
            }

            $mobile_number = Helpers::is_mobile_valid($_REQUEST["ks_mobile_number"]) ? sanitize_textarea_field($_REQUEST["ks_mobile_number"]) : "";

            $result = DB::edit_event_handler(absint($_REQUEST["ks_event_select"]), sanitize_textarea_field($_REQUEST["ks_pattern_input"]), $mobile_number, sanitize_text_field($_REQUEST["ks_send_to"]), absint($_REQUEST["ks_event_handler_id"]));
            if ($result == "1") 
                Helpers::add_notice("رویداد با موفقیت ویرایش شد.", "success");
            elseif ($result === false) 
                Helpers::add_notice("خطا در ویرایش رویداد", "error");
        }
    }

    /**
     * هذف هندلر رویداد ارسال پیامک
     */
    public static function delete_event_handler()
    {
        if ($_REQUEST['ks_action'] == "delete_event" && isset($_REQUEST["eventID"]))
        {
            if(!wp_verify_nonce($_REQUEST['_wpnonce'])){
                Helpers::add_notice("خطا در تصدیق درخواست", "error");
                return;
            }

            $result = DB::delete_event_handler(absint($_REQUEST["eventID"]));
            if ($result == "1") 
                Helpers::add_notice("رویداد با موفقیت حذف شد.", "success");
            elseif ($result === false) 
                Helpers::add_notice("خطا در حذف رویداد", "error");
        }
    }

    /**
     * ولیدیشن تنظیمات هندلر رویداد ارسال پیامک
     */
    private static function event_handler_validation()
    {
        $is_valid = true;

        $send_to = sanitize_text_field($_POST["ks_send_to"]);
        if(!in_array($send_to, array("user","number"))){
            Helpers::add_notice("مقصد ارسال پیامک انتخاب نشده است.", "error");
            return false;
        }

        $selected_event_id = absint($_POST["ks_event_select"]);
        if(empty($selected_event_id)){
            $is_valid = false;
            Helpers::add_notice("رویداد انتخاب نشده است.", "error");
        }
        else{
            $event = DB::get_event_by_id($selected_event_id);
            if (!$event['can_use_user'] && $send_to == "user") {
                $is_valid = false;
                Helpers::add_notice("شماره همراه کاربر برای این رویداد تعریف نشده است.", "error");
            }
        }
        
        if(empty(sanitize_textarea_field($_POST["ks_pattern_input"]))){
            $is_valid = false;
            Helpers::add_notice("متن الگوی پیامک خالی است.", "error");
        }

        if (empty($send_to)){
            $is_valid = false;
            Helpers::add_notice("مقصد ارسال پیامک انتخاب نشده است.", "error");
        }

        if ($send_to == "number"){
            $mobile_numbers = sanitize_textarea_field($_POST["ks_mobile_number"]);
            if(empty($mobile_numbers)){
                $is_valid = false;
                Helpers::add_notice("شماره همراه را وارد کنید.", "error");
            }
            else{
                $mobile_array = explode(",", Helpers::replace_new_line($mobile_numbers));
                if (count($mobile_array) > count(array_filter($mobile_array, "SabaPayamak\Helpers::is_mobile_valid"))) {
                    $is_valid = false;
                    Helpers::add_notice("شماره همراه معتبر نیست.", "error");
                }
            }
        }
        
        return $is_valid;
    }
    
    /**
     * رویدادهای پیش‌فرض
     */
    public static function get_default_events()
    {
        $default_events = array(
            array(
                'category'      => 'WP',
                'name'          => 'user_login',
                'displayName'   => 'ورود به سایت',
                'description'   => 'ارسال پیامک هنگام ورود به سایت (لاگین)',
                'can_use_user'  => true,
                'hook'          => 'wp_login',
                'priority'      => 10,
                'accepted_args' => 2
            ),
            array(
                'category'      => 'WP',
                'name'          => 'new_user',
                'displayName'   => 'کاربر جدید',
                'description'   => 'ارسال پیامک هنگام ثبت نام کاربر جدید',
                'can_use_user'  => true,
                'hook'          => 'user_register',
                'priority'      => 10,
                'accepted_args' => 1
            ),
            array(
                'category'      => 'WP',
                'name'          => 'user_updated',
                'displayName'   => 'به‌روز‌رسانی کاربر',
                'description'   => 'ارسال پیامک هنگام به‌روز‌رسانی اطلاعات پروفایل کاربر',
                'can_use_user'  => true,
                'hook'          => 'profile_update',
                'priority'      => 10,
                'accepted_args' => 2
            ),
            array(
                'category'      => 'WP',
                'name'          => 'user_deleted',
                'displayName'   => 'حذف کاربر',
                'description'   => 'ارسال پیامک هنگام حذف شدن کاربر',
                'can_use_user'  => false,
                'hook'          => 'deleted_user',
                'priority'      => 10,
                'accepted_args' => 1
            ),
            array(
                'category'      => 'WP',
                'name'          => 'new_comment',
                'displayName'   => 'نظر جدید',
                'description'   => 'ارسال پیامک هنگام ثبت نظر جدید',
                'can_use_user'  => false,
                'hook'          => 'wp_insert_comment',
                'priority'      => 10,
                'accepted_args' => 2
            ),
            array(
                'category'      => 'WP',
                'name'          => 'comment_trashed',
                'displayName'   => 'انتقال نظر به زباله‌دان',
                'description'   => 'ارسال پیامک هنگام انتقال نظر به زباله‌دان',
                'can_use_user'  => false,
                'hook'          => 'trashed_comment',
                'priority'      => 10,
                'accepted_args' => 2
            ),
            array(
                'category'      => 'WP',
                'name'          => 'comment_deleted',
                'displayName'   => 'حذف نظر',
                'description'   => 'ارسال پیامک هنگام حذف نظر',
                'can_use_user'  => false,
                'hook'          => 'deleted_comment',
                'priority'      => 10,
                'accepted_args' => 2
            ),
            array(
                'category'      => 'WP',
                'name'          => 'comment_updated',
                'displayName'   => 'ویرایش نظر',
                'description'   => 'ارسال پیامک هنگام ویرایش نظر',
                'can_use_user'  => false,
                'hook'          => 'edit_comment',
                'priority'      => 10,
                'accepted_args' => 2
            ),
            array(
                'category'      => 'WP',
                'name'          => 'post_created',
                'displayName'   => 'انتشار پست',
                'description'   => 'ارسال پیامک هنگام انتشار پست',
                'can_use_user'  => false,
                'hook'          => 'wp_insert_post',
                'priority'      => 10,
                'accepted_args' => 3
            ),
            array(
                'category'      => 'WP',
                'name'          => 'post_trashed',
                'displayName'   => 'انتقال پست به زباله‌دان',
                'description'   => 'ارسال پیامک هنگام انتقال پست به زباله‌دان',
                'can_use_user'  => false,
                'hook'          => 'trashed_post',
                'priority'      => 10,
                'accepted_args' => 1
            ),
            array(
                'category'      => 'WP',
                'name'          => 'post_deleted',
                'displayName'   => 'حذف پست',
                'description'   => 'ارسال پیامک هنگام حذف پست',
                'can_use_user'  => false,
                'hook'          => 'delete_post',
                'priority'      => 10,
                'accepted_args' => 2
            ),
            array(
                'category'      => 'WP',
                'name'          => 'post_updated',
                'displayName'   => 'به‌روز‌رسانی پست',
                'description'   => 'ارسال پیامک هنگام به‌روز‌رسانی پست',
                'can_use_user'  => false,
                'hook'          => 'post_updated',
                'priority'      => 10,
                'accepted_args' => 3
            ),
            array(
                'category'      => 'WP',
                'name'          => 'plugin_activated',
                'displayName'   => 'فعال شدن افزونه',
                'description'   => 'ارسال پیامک هنگام فعال شدن افزونه',
                'can_use_user'  => false,
                'hook'          => 'activated_plugin',
                'priority'      => 10,
                'accepted_args' => 2
            ),
            array(
                'category'      => 'WP',
                'name'          => 'plugin_deactivated',
                'displayName'   => 'غیرفعال شدن افزونه',
                'description'   => 'ارسال پیامک هنگام غیرفعال شدن افزونه',
                'can_use_user'  => false,
                'hook'          => 'deactivated_plugin',
                'priority'      => 10,
                'accepted_args' => 2
            ),
            array(
                'category'      => 'WP',
                'name'          => 'plugin_deleted',
                'displayName'   => 'حذف افزونه',
                'description'   => 'ارسال پیامک هنگام حذف افزونه',
                'can_use_user'  => false,
                'hook'          => 'deleted_plugin',
                'priority'      => 10,
                'accepted_args' => 2
            ),
        );

        return $default_events;
    }

    /**
     * بلافاصله قبل از حذف افزونه
     */
    public static function on_deleting_plugin($plugin_file)
    {
        self::$deleting_plugin = array(
            "file" => $plugin_file,
            "data" => get_plugin_data(SABAPAYAMAK_WP_PLUGINS_DIR_PATH ."$plugin_file")
        );
    }

    /**
     * بلافاصله قبل از حذف کاربر
     */
    public static function on_deleting_user($id, $reassign, $user)
    {
        self::$deleting_user = array(
            "id"    => $id,
            "user"  => $user
        );
    }
 
    /**
     * ارسال پیامک‌های تنظیم شده برای رویداد
     */
    public static function send_event_sms($event_name, $user_id = null, $tags_values_array = array()){

        $event_handlers = DB::get_event_handlers_by_event_name($event_name);
        foreach ($event_handlers as $event_handler) {
            if ($event_handler["isActive"] == 0)
                continue;

            if ($event_handler["send_to"] == "number") 
                $to = explode(",", Helpers::replace_new_line($event_handler["number"]));
            elseif($event_handler["send_to"] == "user")
                $to = Helpers::get_user_mobile_number($user_id);

            if(empty($to))
                continue;
            
            $message = Helpers::replace_tags_with_values(
                $event_handler["pattern"],
                $tags_values_array,
                $user_id
            );
            
            $sms = new SMS();
            $sms->send_sms($event_handler["displayName"], $to, $message);
        }
    }
   
    /**
     * هندل کردن رویدادهای ارسال پیامک
     */
    public static function handle_events()
    {
        if (!Helpers::is_sabapayamak_options_validated())
            return;

        $events = DB::get_events();
        foreach ($events as $key => $event) {
            $action_callback = "handle_{$event['name']}";
            if (method_exists("SabaPayamak\Events", $action_callback)) 
                add_action($event['hook'], ['SabaPayamak\Events', $action_callback], $event['priority'], $event['accepted_args']);
        }

        if(L2FA::is_l2fa_enabled()){
            remove_action('wp_login', 'SabaPayamak\Events::handle_user_login');
            add_action('ks_l2fa_login', 'SabaPayamak\Events::handle_user_login_l2fa');
        }

        add_action('delete_plugin', 'SabaPayamak\Events::on_deleting_plugin');
        add_action('delete_user', 'SabaPayamak\Events::on_deleting_user', 10, 3);
    }

    /**
     * هندل کردن رویداد ارسال پیامک هنگام لاگین
     */
    public static function handle_user_login($user_login, $user)
    {
        self::send_event_sms(str_replace("handle_", "", __FUNCTION__), $user->ID);
    }

    /**
     * هندل کردن رویداد ارسال پیامک هنگام لاگین از طریق ورود دومرحله‌ای
     */
    public static function handle_user_login_l2fa($user_id)
    {
        self::send_event_sms("user_login", $user_id);
    }

    /**
     * هندل کردن رویداد ارسال پیامک هنگام ثبت نام کاربر جدید
     */
    public static function handle_new_user($user_id)
    {
        $event_name = str_replace("handle_", "", __FUNCTION__);
        add_action('shutdown', function () use ($user_id, $event_name) {
            self::send_event_sms($event_name, $user_id);
        });
    }

    /**
     * هندل کردن رویداد ارسال پیامک هنگام به‌روز رسانی اطلاعات پروفایل کاربری
     */
    public static function handle_user_updated($user_id, $old_user_data)
    {
        if(did_action('user_register')) // جلوگیری از ارسال مجدد هنگام ثبت نام کاربر
            return;

        if (get_user_by('id', $user_id)->data == $old_user_data->data) 
            return;

        self::send_event_sms(str_replace("handle_", "", __FUNCTION__), $user_id);
    }

    /**
     * هندل کردن رویداد ارسال پیامک هنگام حذف کاربر
     */
    public static function handle_user_deleted($user_id)
    {
        self::send_event_sms(
            str_replace("handle_", "", __FUNCTION__),
            null,
            array(
                "{USER_ID}"     => $user_id,
                "{USER_NAME}"   => (self::$deleting_user["user"])->user_login
            )
        );
    }

    /**
     * هندل کردن رویداد ارسال پیامک هنگام ثبت نظر جدید
     */
    public static function handle_new_comment($comment_id, $comment)
    {
        if(!in_array($comment->comment_type, ["comment", "review"]))
            return;
            
        self::send_event_sms(
            str_replace("handle_", "", __FUNCTION__),
            null,
            array(
                "{COMMENT_AUTHOR}"      => $comment->comment_author,
                "{COMMENT_CONTENT}"     => $comment->comment_content,
                "{COMMENT_POST_TITLE}"  => get_post($comment->comment_post_ID)->post_title
            )
        );
    }

    /**
     * هندل کردن رویداد ارسال پیامک هنگام انتقال نظر به زباله‌دان
     */
    public static function handle_comment_trashed($comment_id, $comment)
    {
        if(!in_array($comment->comment_type, ["comment", "review"]))
            return;

        self::send_event_sms(
            str_replace("handle_", "", __FUNCTION__),
            null,
            array(
                "{COMMENT_AUTHOR}"      => $comment->comment_author,
                "{COMMENT_CONTENT}"     => $comment->comment_content,
                "{COMMENT_POST_TITLE}"  => get_post($comment->comment_post_ID)->post_title
            )
        );
    }

    /**
     * هندل کردن رویداد ارسال پیامک هنگام حذف نظر
     */
    public static function handle_comment_deleted($comment_id, $comment)
    {
        if(!in_array($comment->comment_type, ["comment", "review"]))
            return;
            
        self::send_event_sms(
            str_replace("handle_", "", __FUNCTION__),
            null,
            array(
                "{COMMENT_AUTHOR}"      => $comment->comment_author,
                "{COMMENT_CONTENT}"     => $comment->comment_content,
                "{COMMENT_POST_TITLE}"  => get_post($comment->comment_post_ID)->post_title
            )
        );
    }

    /**
     * هندل کردن رویداد ارسال پیامک هنگام ویرایش نظر
     */
    public static function handle_comment_updated($comment_id, $comment_array)
    {
        if(!in_array($comment_array['comment_type'], ["comment", "review"]))
            return;
            
        self::send_event_sms(
            str_replace("handle_", "", __FUNCTION__),
            null,
            array(
                "{COMMENT_AUTHOR}"      => $comment_array['comment_author'],
                "{COMMENT_CONTENT}"     => $comment_array['comment_content'],
                "{COMMENT_POST_TITLE}"  => get_post($comment_array['comment_post_ID'])->post_title
            )
        );
    }

    /**
     * هندل کردن رویداد ارسال پیامک هنگام انتشار پست جدید
     */
    public static function handle_post_created($post_ID, $post, $update)
    {

        if(!in_array($post->post_type, ["post", "page"]))
            return;

        if ($post->post_status != "publish")
            return;

        if (!$post->post_parent == 0) // بابت ریویژن‌های پست ارسال نشود
            return;

        self::send_event_sms(
            str_replace("handle_", "", __FUNCTION__),
            $post->post_author,
            array(
                "{POST_ID}"     => $post_ID,
                "{POST_TITLE}"  => $post->post_title
            )
        );
    }

    /**
     * هندل کردن رویداد ارسال پیامک هنگام انتقال پست به زباله‌دان
     */
    public static function handle_post_trashed($post_ID)
    {
        if(!in_array(get_post($post_ID)->post_type, ["post", "page"]))
            return;

        self::send_event_sms(
            str_replace("handle_", "", __FUNCTION__),
            null,
            array(
                "{POST_ID}"     => $post_ID,
                "{POST_TITLE}"  => get_post($post_ID)->post_title
            )
        );
    }

    /**
     * هندل کردن رویداد ارسال پیامک هنگام حذف پست
     */
    public static function handle_post_deleted($post_ID, $post)
    {
        if(!in_array($post->post_type, ["post", "page"]))
            return;

        if (in_array($post->post_title, ["پیش‌نویس خودکار"]) || in_array($post->post_status, ["auto-draft"]) )
            return;

        if (!$post->post_parent == 0) // بابت ریویژن‌های پست ارسال نشود
            return;
        
        self::send_event_sms(
            str_replace("handle_", "", __FUNCTION__),
            null,
            array(
                "{POST_ID}"     => $post_ID,
                "{POST_TITLE}"  => $post->post_title
            )
        );
    
    }

    /**
     * هندل کردن رویداد ارسال پیامک هنگام به‌روز رسانی پست
     */
    public static function handle_post_updated($post_ID, $post_after, $post_before)
    {
        if(!in_array(get_post($post_ID)->post_type, ["post", "page"]))
            return;

        if (in_array($post_after->post_status, ["trash"]))
            return;

        if (!in_array($post_before->post_status, ["publish"]))
            return;
        
        if (!$post_after->post_parent == 0) // بابت ریویژن‌های پست ارسال نشود
            return;

        self::send_event_sms(
            str_replace("handle_", "", __FUNCTION__),
            null,
            array(
                "{POST_ID}"     => $post_ID,
                "{POST_TITLE}"  => get_post($post_ID)->post_title
            )
        );
    }

    /**
     * هندل کردن رویداد ارسال پیامک هنگام فعال شدن افزونه
     */
    public static function handle_plugin_activated($plugin, $network_wide)
    {
        self::send_event_sms(
            str_replace("handle_", "", __FUNCTION__),
            null,
            array(
                "{PLUGIN_NAME}" => get_plugin_data(SABAPAYAMAK_WP_PLUGINS_DIR_PATH ."$plugin")['Name']
            )
        );
    }

    /**
     * هندل کردن رویداد ارسال پیامک هنگام غیرفعال شدن افزونه
     */
    public static function handle_plugin_deactivated($plugin, $network_wide)
    {
        $deactivating_plugin_name = get_plugin_data(SABAPAYAMAK_WP_PLUGINS_DIR_PATH ."$plugin")['Name'];
        if(in_array($deactivating_plugin_name, array("صباپیامک","sabapayamak")))
            return;

        self::send_event_sms(
            str_replace("handle_", "", __FUNCTION__),
            null,
            array(
                "{PLUGIN_NAME}" => $deactivating_plugin_name
            )
        );
    }

    /**
     * هندل کردن رویداد ارسال پیامک هنگام غیرفعال شدن افزونه
     */
    public static function handle_plugin_deleted($plugin_file, $deleted)
    {
        if(!$deleted)
            return;

        if(self::$deleting_plugin["file"] != $plugin_file)
            return;

        $deleting_plugin_name = self::$deleting_plugin['data']['Name'];
        if($deleting_plugin_name == "صباپیامک")
            return;

        self::send_event_sms(
            str_replace("handle_", "", __FUNCTION__),
            null,
            array(
                "{PLUGIN_NAME}" => $deleting_plugin_name
            )
        );
    }
}
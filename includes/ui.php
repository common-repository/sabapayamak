<?php

namespace SabaPayamak;

use WP_SMS\Gateway\aradsms;

// جلوگیری از دسترسی مستقیم به فایل
if (!defined('ABSPATH')) exit;

class KS_UI
{
    public static function admin_tab_home()
    { 
        Helpers::option_validation_actions();
         ?>
        <div class="card ks-card">
            <form action="options.php" method="POST">
                <?php
                settings_fields('sabapayamak_options');
                do_settings_sections('sabapayamak');
                ?>
                <p class="submit">
                    <input onclick="ks_enableLoadingAnimation();" type="submit" name="submit_sabapayamak_options" id="submit_sabapayamak_options" class="button button-primary" value="ثبت تنظیمات">
                </p>
            </form>
        </div>
        <div class="card ks-card">
                <p>وضعیت فعلی افزونه:</p>
                <p><strong><?php echo wp_kses((Helpers::get_sabapayamak_status_msg()), array('p' => array('class' => array(), 'style'=>array()))) ?></strong></p>
                
        <?php

                if (Helpers::is_sabapayamak_options_validated()) {
                    ?>
                    <p>میزان اعتبار:</p>
                    <p>
                    <?php
                        $sms = new SMS();
                        $credit = $sms->get_credit();
                        if ($credit === false) {
                            ?>
                            خطا در دریافت اعتبار
                            <button onclick="window.location.href=window.location.href;" >تلاش مجدد</button>
                            <?php
                        }
                        else {
                            echo Helpers::replace_digits_en2fa($credit);
                            ?> پاکت<?php
                        }
                        ?>
                    </p>
                    <?php
                }


        if (Helpers::get_sabapayamak_options() == true) {
            if(get_transient("sabapayamak_options_confirm_otp")){
                ?>
                <script>
                    var ks_countdown_seconds = <?php echo esc_js(Helpers::get_transient_timeout("sabapayamak_options_confirm_otp") - time());?>;
                    var ks_otpIntervalId = setInterval(function() {
                        ks_countdown_seconds-=1;
                    if(ks_countdown_seconds > 0 )
                        document.getElementById("ks_countdown").innerHTML = ks_countdown_seconds + " ثانیه تا منقضی شدن کد";
                    else {
                        clearInterval(ks_otpIntervalId);
                        window.location.href = window.location.href;
                    }
                    }, 1000); 
                </script>
                <form method="POST">
                <p>کد ارسال شده را وارد کرده و بر روی دکمه تأیید کلیک کنید:</p>
                    <input type="text" name="ks_confirm_options_code" id="ks_confirm_options_code" class="only-numbers-allowed">
                    <input onclick="ks_enableLoadingAnimation();" type="submit" name="ks_confirm_options_btn" id="ks_confirm_options_btn" class="button button-primary" value="تأیید ">
                <p id="ks_countdown"></p>
                </form>
                <?php
            }
            else{
            ?>
                <form method="POST">
                    <p>جهت ارسال پیامک آزمایشی و تأیید اطلاعات، شماره خود را وارد نموده و بر روی دکمه کلیک کنید:</p>
                    <input type="text" name="ks_send_confirm_otp_number" id="ks_send_confirm_otp_number" class="only-numbers-allowed" maxlength="11">
                    <input onclick="ks_enableLoadingAnimation();" type="submit" name="ks_send_confirm_otp_btn" id="ks_send_confirm_otp_btn" class="button button-primary" value="ارسال پیامک آزمایشی">
                </form>
            </div>
            <?php
            }
 
        }
        else{
            ?>
                <p>اطلاعات کاربری را وارد کرده و بر روی دکمه ثبت تنظیمات کلیک کنید.</p>
            <?php
        }
    }

    public static function admin_tab_l2fa()
    {
        if(!Helpers::is_sabapayamak_options_validated()){
            self::options_not_validated_msg();
            return;
        }

        L2FA::update_l2fa();

        $l2fa_enabled = get_option('ks_l2fa_enabled');
        $l2fa_checked = ($l2fa_enabled) ? checked($l2fa_enabled, 1, false) : '' ;
        
        $l2fa_mandatory = get_option("ks_l2fa_mandatory");
        $l2fa_mandatory_checked = ($l2fa_mandatory) ? checked($l2fa_mandatory, 1, false) : '' ;

        ?>
        <form autocomplete="off" method="POST">
            <table class="form-table" role="presentation">
                <tbody>
                    <th scope="row">ورود دومرحله‌ای:</th>
                    <td>
                        <input onclick='ks_toggleL2faSettings();' id="ks_l2fa_enabled" name="ks_l2fa_enabled" type="checkbox" value="1" <?php echo esc_html($l2fa_checked); ?>>
                        <label for="ks_l2fa_enabled">ورود کاربران به سایت (لاگین) از طریق تأیید پیامک انجام شود.</label>
                    </td>
                </tbody>
            </table>
            <div id="ks_l2fa_settings">
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">نقش‌هایی که باید با تأیید پیامک وارد شوند:</th>
                        <td>
                            <p>
                            <?php 
                                $l2fa_roles = get_option('ks_l2fa_roles');
                                self::roles_snippet($l2fa_roles);
                            ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">الزامی بودن تأیید پیامک:</th>
                        <td>
                            <p>
                                <input id="ks_l2fa_mandatory" name="ks_l2fa_mandatory" type="checkbox" value="1" <?php echo esc_html($l2fa_mandatory_checked); ?>">
                                <label for="ks_l2fa_mandatory">با انتخاب این گزینه کاربران نقش‌های انتخابی بالا که شماره همراه برای آن‌ها تعریف نشده امکان ورود به سایت نخواهند داشت.</label>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php
                self::pattern_snippet(get_option("ks_l2fa_pattern_input", ""), "L2FA");
            ?>
            </div>
            <p class="submit">
                <input onclick="ks_enableLoadingAnimation();" type="submit" name="ks_l2fa_submit" id="submit" class="button button-primary" value="ذخیرهٔ تغییرات">
            </p>
            <?php wp_nonce_field() ?>
        </form>
        <?php
    }

    public static function admin_tab_events()
    {
        if(!Helpers::is_sabapayamak_options_validated()){
            self::options_not_validated_msg();
            return;
        }

        if ( isset($_REQUEST['ks_action'])  &&  ($_REQUEST['ks_action'] != "change_active")  &&  ($_REQUEST['ks_action'] != "delete_event") ) {
            switch ($_REQUEST['ks_action']) {

                case 'add_event':
                    self::admin_add_event();
                    break;

                case 'edit_event':
                    Events::edit_event_handler();
                    if ( isset($_REQUEST["eventID"]) ) {
                        $event_id = absint($_REQUEST["eventID"]);
                        $event = DB::get_event_handler_by_id($event_id);
                        if ($event != null) {
                            self::admin_edit_event($event["id"], $event["event_id"], $event["pattern"], $event["number"], $event["send_to"]);
                        }
                    }
                    break;

                default:
                    break;
            }
        }
        else {
            if (isset($_GET['ks_action']) && $_GET['ks_action'] == "change_active" && !isset($_REQUEST['event'])) 
                Events::event_handler_change_active(absint($_GET['eventID']), absint($_GET['isActive']));
            elseif (isset($_GET['ks_action']) && $_GET['ks_action'] == "delete_event" && !isset($_REQUEST['event'])) 
                Events::delete_event_handler();
            ?>
            <p>
                در این قسمت می‌توانید تنظیم کنید که هنگام رویدادی مشخص پیامک ارسال شود.
            </p>
            <form method="get">
                <input type='hidden' name='page' value='sabapayamak' />
                <input type='hidden' name='tab' value='events' />
                <button onclick="ks_enableLoadingAnimation();" type="submit" id="ks_add_event" class="button button-primary" name="ks_action" value="add_event">افزودن رویداد جدید</button>
            </form>
            <?php

            $events_table = new Events_Table();
            
            $events_table->prepare_items();

            ?>
                <form id="events-table" class="karans-table" method="post">
                    <input type="hidden" name="page" value="<?php echo esc_attr(sanitize_text_field($_REQUEST['page'])) ?>" />
                    <input type="hidden" name="tab" value="<?php echo esc_attr(sanitize_text_field($_REQUEST['tab'])) ?>" />
                    <?php $events_table->display();?>
                    <?php wp_nonce_field(); ?>
                </form>
                <script>ks_confirmDeleteBulk();</script>
            <?php
        }
    }

    public static function admin_tab_send()
    {
        if(!Helpers::is_sabapayamak_options_validated()){
            self::options_not_validated_msg();
            return;
        }

        Helpers::send_tab_actions();

        ?>
        <p>از طریق این قسمت می‌توانید به شماره دلخواه پیامک ارسال نمایید.</p>
        <form id="ks_send_sms_form" method="POST">
            <table class="form-table ks_send_form" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">ارسال به:</th>
                        <td>
                            <p>
                                <select name="ks_send_type" id="ks_send_type" onchange="ks_ShowRelatedOptionBlock(this); ks_hideSendFormSelectValidation();">
                                    <option value=""></option>
                                    <option value="ks_numbers"<?php echo (isset($_POST['ks_send_type']) && $_POST['ks_send_type'] == "ks_numbers") ? " selected = 'selected'" : ""; ?>>شماره‌ها</option>
                                    <option value="ks_users"<?php echo (isset($_POST['ks_send_type']) && $_POST['ks_send_type'] == "ks_users") ? " selected = 'selected'" : ""; ?>>همه کاربران</option>
                                    <option value="ks_roles"<?php echo (isset($_POST['ks_send_type']) && $_POST['ks_send_type'] == "ks_roles") ? " selected = 'selected'" : ""; ?>>نقش‌ها</option>
                                </select>
                                <span id="ks_send_type_validation" class="ks_validation"></span>
                                <br>
                                <br>
                                <div id="ks_numbers" style="display: none;">
                                    <textarea class="ks_textarea" id="ks_sms_numbers" name="ks_sms_numbers" dir="ltr" ><?php echo isset($_POST['ks_sms_numbers']) ? esc_textarea($_POST['ks_sms_numbers']) : '' ;?></textarea>
                                    <p>
                                        <label for="ks_sms_numbers">شماره ها را با کاما جدا کنید یا در هر خط یک شماره وارد کنید.</label>
                                    </p>
                                </div>
                                <div id="ks_roles" style="display: none;">
                                    <?php
                                        $checked_roles = isset($_POST['ks_l2fa_roles']) ? array_map('sanitize_text_field', $_POST['ks_l2fa_roles']) : "";
                                        self::roles_snippet($checked_roles);
                                    ?>
                                </div>
                                <div id="ks_users" style="display: none;">
                                    <p>
                                        تعداد کاربران دارای شماره: <?php echo esc_html(Helpers::replace_digits_en2fa(count(Helpers::get_users_with_mobile_number()))); ?>
                                    </p>
                                </div>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">متن پیامک:</th>
                        <td>
                            <textarea class="ks_textarea ks_sms_input ks_textarea_count" id="ks_sms_input" name="ks_sms_input" ><?php echo isset($_POST['ks_sms_input']) ? esc_textarea($_POST['ks_sms_input']) : '' ;?></textarea>
                            <div id="send-form-msg-foot">
                                <span style="float: right;" id="ks_sms_input_validation" class="ks_validation"></span>
                                <span id="ks_sms_counter" style="float: left;"></span>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"></th>
                        <td>
                            <p class="submit">
                                <input onclick="ks_enableLoadingAnimation();" type="submit" name="ks_send_sms" id="submit" class="button button-primary" value="ارسال پیامک">
                            </p>    
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php wp_nonce_field(); ?>
        </form>
        <?php
        if(isset($_POST['is_bulk_send']) && $_POST['is_bulk_send'] == true){
        ?>
            <script>
				document.addEventListener("DOMContentLoaded", function () {
                    document.getElementById('ks_send_type').value = 'ks_numbers';
                    document.getElementById('ks_numbers').style.display = 'block';
				});
			</script>
        <?php
        }
    }
    
    public static function admin_tab_log()
    {
        if (isset($_GET['action']) && $_GET['action'] == 'log_details') {
            self::log_detail();
            return;
        }

        SMS_log::handle_actions();

        add_thickbox();

        $SMS_log_table = new SMS_Log_Table();
        $SMS_log_table->prepare_items();
        $search_filter = $SMS_log_table->has_search ? $SMS_log_table->search_filter : "";

            if($SMS_log_table->has_search){
        ?>
            <p> نتیجه جستجو در متن پیامک و شماره‌ها برای عبارت "<?php echo esc_html($SMS_log_table->search_filter); ?>":</p>
        <?php
            }
            else{
        ?>
            <p>جدول گزارش کلیه پیامک‌های ارسال شده از طریق افزونه:</p>
        <?php
            }   
            if(Helpers::is_woocommerce_active()){
        ?>
            <a onclick="ks_enableLoadingAnimation();" style="float: left;" class="button button-primary" href="<?php echo esc_url(Helpers::clean_url([])); ?>page=sabapayamak&tab=archive">مشاهده آرشیو پیامک‌های ووکامرس</a>
            <br>
            <br>
        <?php
            }
        ?>
        
        <form id="logs-table" class="karans-table" method="post">

        <p class="search-box">
            <label class="screen-reader-text" for="search_id-search-input">جستجوی گیرنده:</label>
            <input type="search" id="ks_search_sms_log" name="s" value="<?php echo esc_attr($search_filter); ?>">
            <input type="submit" id="ks_log-search-submit" class="button" value="جستجو" onclick="ks_enableLoadingAnimation();">
        </p> 

        <?php
            if($SMS_log_table->has_search){
        ?>
            <a class="page-title-action" onclick="ks_enableLoadingAnimation(); window.location.href=window.location.href;" style="float: left; top: 0px; margin-left: 5px;" href="#">بازگشت به لیست گزارش
            همه پیامک‌های افزونه</a>
        <?php
        }
        ?>
        
        <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']) ?>" />
        <input type="hidden" name="tab" value="<?php echo esc_attr($_REQUEST['tab']) ?>" />
        <?php $SMS_log_table->display();?>
        <?php wp_nonce_field(); ?>
        </form>
        <script>ks_confirmDeleteBulk();</script>
        <script> // بابت مشکل ریسپانسیو نبودن تیک‌باکس
            const thickBoxLinks = document.querySelectorAll("a.thickbox");
            thickBoxLinks.forEach(element => {
                element.href = element.href.replace('ks_tb_w', Math.floor(window.innerWidth * 0.8)).replace('ks_tb_h', Math.floor((window.innerHeight * 0.8)));
            });
        </script>
        <?php
    }

    public static function log_detail()
    {
        $SMS_log_detail_table = new SMS_Log_Detail_Table();
        $SMS_log_detail_table->prepare_items();

        ?>
            <form id="logs-table" class="karans-table" method="get">
            <?php $SMS_log_detail_table->display();?>
            </form>
            <script>
                const ks1 = document.getElementById('wpadminbar'); if(ks1) ks1.style.display = 'none';
                const ks2 = document.getElementById('adminmenumain'); if(ks2) ks2.style.display = 'none';
                const ks3 = document.getElementById('wpfooter'); if(ks3) ks3.style.display = 'none';
                const ks4 = document.getElementById('wpcontent'); if(ks4) ks4.style.marginRight = '0';
                const ks5 = document.querySelector('.nav-tab-wrapper'); if(ks5) ks5.style.display = 'none';

                document.addEventListener("DOMContentLoaded", function () {
                    document.getElementById('wpadminbar').style.display = 'none';
                    document.getElementById('adminmenumain').style.display = 'none';
                    document.getElementById('wpfooter').style.display = 'none';
                    document.getElementById('wpcontent').style.marginRight = '0';
                    document.querySelector('.nav-tab-wrapper').style.display = 'none';
                });
            </script>
        <?php
    }

    public static function admin_add_event()
    {
        Events::add_event_handler();
        ?>    
        <form id="ks_add-event-form" class="ks_event_form" method="POST">
            <?php
            self::events_select_snippet();
            echo "<br>";
            echo "<br>";

            $category = "";
            if (isset($_POST["ks_event_select"])) {
                $event = DB::get_event_by_id(absint($_POST["ks_event_select"]));
                if ($event) 
                    $category = $event["category"];
            }
            
            self::pattern_snippet("", $category);
            echo "<br>";
            $number = isset($_POST['ks_mobile_number']) ? sanitize_text_field($_POST['ks_mobile_number']) : "" ;
            self::number_snippet($number);
            ?>
            <br>
            <br>
            <input class="button button-primary" type="submit" name="ks_add_event_handler" value="افزودن رویداد جدید">
            <a onclick="ks_enableLoadingAnimation();" href="<?php echo esc_url(Helpers::clean_url()); ?>" type="button" value="بازگشت" class="button">بازگشت</a>
        </form>
        <?php
    }

    public static function admin_edit_event($event_handler_id, $selected_id, $pattern_input, $number, $send_to)
    {
        ?>    
        <form id="ks_edit-event-form" class="ks_event_form" method="POST">
            <?php
            self::events_select_snippet($selected_id);
            echo "<br>";
            echo "<br>";
            $event_name = DB::get_event_handler_by_id($event_handler_id)["name"];
            self::pattern_snippet($pattern_input, $event_name);
            echo "<br>";
            self::number_snippet($number, $send_to);
            ?>
            <br>
            <br>
            <input type="hidden" name="ks_event_handler_id" value="<?php echo esc_attr($event_handler_id) ?>">
            <input class="button button-primary" type="submit" name="ks_edit_event_handler" value="ویرایش رویداد">
            <a onclick="ks_enableLoadingAnimation();" href="<?php echo esc_url(Helpers::clean_url()); ?>" type="button" value="بازگشت" class="button">بازگشت</a>
        </form>
        <?php
    }

    public static function notice_placeholder_snippet()
    {
        echo "<div id='ks_notices_placeholder'></div>";
    }

    public static function events_select_snippet($selected_id = null)
    {
        $selected_id = empty($_POST["ks_event_select"]) ? $selected_id : absint($_POST["ks_event_select"]);
        $events = DB::get_events();
        $can_user_array = array();
        foreach ($events as $key => $event) {
            if ($event['can_use_user']) 
                array_push($can_user_array, $event['id']);
        }
        ?>
        <script>
            var ks_can_user_array = <?php echo wp_kses(json_encode($can_user_array),[]); ?>;
        </script>
        <p>
            رویدادی که می‌خواهید برای آن پیامک ارسال شود را انتخاب کنید:
        </p>
        <select name="ks_event_select" id="ks_event_select" onchange="ks_ShowRelatedOptionBlock(this); ks_hideUserNumberRadio(this); ks_hideEventSelectValidation();">
        <option value="">انتخاب رویداد</option>
        <?php
            foreach ($events as $event) {
                $selected = ($selected_id != null && $event["id"] == $selected_id) ? " selected = 'selected'" : "";
                
                echo "<option value='" . esc_attr($event['id']) . "' $selected >" . esc_attr($event['displayName']) . ': ' . esc_attr($event['description']) .  '</option>';
            }
        ?>
        </select>
        <span id="ks_event-select-validation" class="ks_validation"></span>

        <?php
    }

    public static function pattern_snippet($pattern_input = "", $event_name = "")
    {   
        $pattern_input = empty($_POST["ks_pattern_input"]) ? $pattern_input : sanitize_textarea_field($_POST["ks_pattern_input"]);
        ?>
            <p>
                با استفاده از دکمه‌ها، الگوی دلخواه پیامک را در کادر زیر ثبت کنید:
            </p>
            <table class="ks_pattern-table" id="ks_pattern-table">
                <tr>
                    <th colspan="2">
                        <?php
                            if ($event_name == "L2FA") {
                                echo "<div class='tags'>";
                                    echo "<div><ul>";
                                        $tags = Helpers::get_tags_by_event_name($event_name);
                                        foreach ($tags as $tag) {
                                            echo "<li>";
                                            echo "<button type='button' class='button ks_pattern_" . esc_attr($tag['tag']) . "'>" . esc_attr($tag['name']) . "</button>";
                                            echo "</li>";
                                        }
                                    echo "</ul></div></div>";
                            }
                            else{
                                $events = DB::get_events();
                                foreach ($events as $key => $event) {
                                    $tags = Helpers::get_tags_by_event_name($event['name']);
                                    echo "<div class='tags'>";
                                    echo "<div id='" . esc_attr($event['id']) . "' style='display: none;'><ul>";
                                        foreach ($tags as $tag) {
                                            echo "<li>";
                                            echo "<button type='button' class='button ks_pattern_" . esc_attr($tag['tag']) . "'>" . esc_attr($tag['name']) . "</button>";
                                            echo "</li>";
                                        }
                                    echo "</ul></div></div>";
                                }
                            }
                        ?>
                    </th>
                </tr>
                <tr>
                    <td>
                        <p>
                            متن الگو:
                        </p>
                        <textarea class="ks_textarea ks_pattern_input ks_sms_input" id="ks_pattern_input" name="ks_pattern_input" oninput="ks_hideEventPatternValidation();"><?php echo esc_textarea($pattern_input); ?></textarea>
                    </td>
                    <td>
                        <p>
                            پیش‌نمایش:
                        </p> 
                        <textarea class="ks_textarea ks_textarea_count" readonly id="ks_pattern_preview"></textarea>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <span id="ks_sms_counter"></span>
                    </td>
                </tr>
            </table>
            <span id="ks_event-pattern-validation" class="ks_validation"></span>
        <?php
    }

    public static function number_snippet($number = "", $send_to = "")
    {
        $send_to = empty($send_to) ? 'number' : $send_to;
        ?>
        <table class="form-table ks_send_form" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">ارسال پیامک به:</th>
                        <td>
                            <div id="ks_can_send_to_user">
                                <input type="radio" id="ks_send_to_user" name="ks_send_to" <?php echo ($send_to == 'user') ? 'checked' : ''; ?> value="user"> 
                                <label for="ks_send_to_user">شماره همراه کاربر</label>
                            </div>
                            <br>
                            <div>
                                <input style="vertical-align: top; margin-top: 0;" type="radio" id="ks_send_to_number" name="ks_send_to" <?php echo ($send_to == 'number') ? 'checked' : ''; ?> value="number">
                                <label style="vertical-align: top;" for="ks_send_to_number">این شماره‌ها (در هر خط یک شماره):</label>
                                <div>
                                    <textarea name="ks_mobile_number" id="ks_mobile_number" class="only-numbers-allowed" cols="20" rows="5" oninput="ks_hideEventNumberValidation();"><?php esc_attr($number); ?></textarea>
                                    <span id="ks_event-mobile-validation" class="ks_validation"></span>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
        </table>
            <?php
        wp_nonce_field();
    }

    public static function roles_snippet($checked_roles = null)
    {
        $wp_roles = Helpers::get_wp_roles();
        
        foreach ($wp_roles as $role) {
            $role_checked = ($checked_roles && in_array($role, array_keys($checked_roles))) ? "checked = '1'" : "" ;
            
            $role_fa = translate_user_role($role);

            echo "<input id='ks_l2fa_role_" . esc_attr($role) . "' name='ks_l2fa_roles[" . esc_attr($role) . "]' type='checkbox' " . esc_attr($role_checked) . " />";
            echo "<label for='ks_l2fa_role_" . esc_attr($role) . "'>" . esc_attr($role_fa) . " (" . esc_attr($role) . ")</label>";
            echo "<br />";
        }
    }

    public static function options_not_validated_msg()
    {
        if (!Helpers::get_sabapayamak_options()) 
            Helpers::add_notice("ابتدا در بخش تنظیمات اطلاعات کاربری را وارد نموده و پیامک آزمایشی را ارسال کنید.", "warning");
        else
            Helpers::add_notice("ابتدا در بخش تنظیمات پیامک آزمایشی را ارسال و تأیید کنید.", "warning");
    }
}
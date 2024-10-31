<?php

namespace SabaPayamak;

use Twilio\TwiML\Messaging\Message;

// جلوگیری از دسترسی مستقیم به فایل
if (!defined('ABSPATH')) exit;

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Events_Table extends \WP_List_Table {
    
    function __construct(){
        global $status, $page;
                
        parent::__construct( array(
            'singular'  => 'event',     
            'plural'    => 'events',    
            'ajax'      => false        
        ) );
        
    }

    function column_default($item, $column_name){
        switch($column_name){

            case 'action':
                $nonce = wp_create_nonce();
                $edit_url = sprintf('<span class="edit"><a onclick="ks_enableLoadingAnimation();" href="?page=%s&tab=%s&ks_action=%s&eventID=%s">' . __('Edit') .'</a></span>', sanitize_text_field($_REQUEST['page']), sanitize_text_field($_REQUEST['tab']),'edit_event',$item['id']);
                $delete_url = "<a class='delete' onclick='return ks_confirmDelete();' style='color:crimson;' href=" . Helpers::clean_url() . "&ks_action=delete_event&eventID=" . $item['id'] . "&_wpnonce=$nonce>پاک کردن</a></div>";
                $value = $edit_url . " | " . $delete_url;
                return $value;
                break;

            case 'isActive':
                if ($item[$column_name] == 1) {
                    $value = sprintf('<a class="check-mark" onclick="ks_enableLoadingAnimation();" style="color:forestgreen;" href="' . Helpers::clean_url() . '&ks_action=%s&eventID=%s&isActive=%s">' . __('فعال') .'</a>','change_active',$item['id'],$item['isActive']);
                }
                else{
                    $value = sprintf('<a class="cancel-mark" onclick="ks_enableLoadingAnimation();" style="color:crimson;" href="' . Helpers::clean_url() . '&ks_action=%s&eventID=%s&isActive=%s">' . __('غیرفعال') .'</a>','change_active',$item['id'],$item['isActive']);
                }
                return $value;
                break;

            case 'pattern':
                $value = "<p class='pattern'>$item[$column_name]</p>";
                return $value;
                break;
            
            case 'number':
                if ($item["send_to"] == "number") {
                    $value = $item[$column_name];
                }
                elseif ($item["send_to"] == "user") {
                    $value = "شماره همراه کاربر";
                }
                return Helpers::replace_digits_en2fa($value);
                break;

            default:
                $value = $item[$column_name];
                break;
        }
        return  Helpers::replace_digits_en2fa($value);
    }

    function get_columns(){
        $columns = array(
            'cb'        => '<input type="checkbox" />',
            'displayName'   => 'نام',
            'description'   => 'شرح',
            'pattern'       => 'الگوی پیام',
            'number'        => 'ارسال به',
            'isActive'      => 'وضعیت فعلی',
            'action'        => 'عملیات'
        );
        return $columns;
    }

    function column_cb($item){
        return sprintf( '<input type="checkbox" name="%1$s[]" value="%2$s" />', $this->_args['singular'], $item['id'] );
    }

    function get_bulk_actions() {
        $actions = array(
            'delete'        => 'حذف',
            'set_active'    => 'فعال کردن',
            'set_inactive'  => 'غیر فعال کردن'
        );
        return $actions;
    }

    function process_bulk_action() {
        $action = $this->current_action();

        $event_handler_ids = isset($_REQUEST['event']) ? array_map('absint',$_REQUEST['event']) : array();
        if ($action) {
            if (!wp_verify_nonce($_REQUEST['_wpnonce'])){
                Helpers::add_notice("خطا در تصدیق درخواست", "error");
                return;
            }
            if (empty($event_handler_ids)) {
                Helpers::add_notice("رویدادهای مورد نظر خود را انتخاب کنید.", "warning");
                return;
            }
        }
       
        switch ($action) {
            case 'delete':
                if (DB::delete_event_handler_bulk($event_handler_ids) == count($event_handler_ids)) 
                    Helpers::add_notice("رویدادهای انتخابی با موفقیت حذف شدند.", "success");
                break;
            
            case 'set_active':
                if (DB::event_handler_change_active_bulk($event_handler_ids, 1) !== false) 
                    Helpers::add_notice("رویدادهای انتخابی با موفقیت فعال شدند.", "success");
                else
                    Helpers::add_notice("خطا در انجام عملیات", "error");
                break;
            
            case 'set_inactive':
                if (DB::event_handler_change_active_bulk($event_handler_ids, 0) !== false) 
                    Helpers::add_notice("رویدادهای انتخابی با موفقیت غیر فعال شدند.", "success");
                else
                    Helpers::add_notice("خطا در انجام عملیات", "error");
                break;
            
            default:
                break;
        }
    }

    function get_sortable_columns() {
        $sortable_columns = array(
            'displayName'   => array('displayName',false),
            'number'        => array('number',false),
            'isActive'      => array('isActive',false)
        );
        return $sortable_columns;
    }

    function prepare_items() {

        $per_page = (!empty($_REQUEST['per_page'])) ? absint($_REQUEST['per_page']) : 50;
        
        $columns = $this->get_columns();
        $hidden = array("id");
        $sortable = $this->get_sortable_columns();
        
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        $this->process_bulk_action();
        
        $data = DB::get_event_handlers();

        function ev_usort_reorder($a,$b){
            $orderby = (!empty($_REQUEST['orderby'])) ? sanitize_text_field($_REQUEST['orderby']) : 'id';
            $order = (!empty($_REQUEST['order'])) ? sanitize_text_field($_REQUEST['order']) : 'desc';
            $result = strcmp($a[$orderby], $b[$orderby]);
            return ($order==='asc') ? $result : -$result;
        }
        usort($data, 'SabaPayamak\ev_usort_reorder');
        
        $current_page = $this->get_pagenum();

        $total_items = count($data);
        
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);

        $this->items = $data;
 
        $this->set_pagination_args(array(
            'total_items' => $total_items,                  
            'per_page'    => $per_page,                     
            'total_pages' => ceil($total_items/$per_page)   
        ));
    }

}

class SMS_Log_Table extends \WP_List_Table {
    
    public $has_search;
    public $search_filter;

    function __construct(){
        global $status, $page;
                
        parent::__construct( array(
            'singular'  => 'log',     
            'plural'    => 'logs',    
            'ajax'      => false        
        ) );

        if (isset($_REQUEST['s']) && !empty($_REQUEST['s'])){
            $this->has_search = true;
            $this->search_filter = sanitize_text_field($_REQUEST['s']);
        }
        else {
            $this->has_search = false;
        }
        
    }

    function column_default($item, $column_name){

        switch($column_name){
            case 'action':
                $nonce = wp_create_nonce();
                $value = "<a class='delete' onclick='return ks_confirmDelete();' style='color:crimson;' href=" . Helpers::clean_url() . "&ks_action=delete_sms_log_record&log_id=$item[id]&_wpnonce=$nonce>پاک کردن</a>";
                return $value;
                break;
            
            case 'reg_date':
                $value = Helpers::miladi_to_shamsi_date(new \DateTime($item[$column_name]));
                break;
            
            case 'message':
                $value = "<p class='message'>$item[$column_name]</p>";
                return $value;
                break;
                        
            case 'numbers':
                $value = "<p class='numbers'>$item[$column_name]</p>";
                break;

            case 'result':
                if ($item['isSuccess'] == 1) {
                    $url = esc_url($_SERVER['REQUEST_URI']) . "&action=log_details&log_id=$item[id]&TB_iframe=true&width=ks_tb_w&height=ks_tb_h";
                    $value = "<p class='check-mark' style='color:forestgreen;'>$item[$column_name]<a href='$url' class='thickbox'> (جزئیات)</a><p>";
                }
                else{
                    $value = "<p class='cancel-mark' style='color:crimson;'>$item[$column_name]<p>";
                }
                return $value;
                break;

            default:
                $value = $item[$column_name];
                break;
        }
        return  Helpers::replace_digits_en2fa($value);
    }

    function get_columns(){
        $columns = array(
            'cb'        => '<input type="checkbox" />',
            'reg_date'      => 'زمان',
            'message'       => 'متن پیامک',
            'description'   => 'نام رویداد',
            'numbers'       => 'شماره‌ها',
            'numberCount'   => 'تعداد شماره',
            'result'        => 'نتیجه',
            'action'        => 'عملیات'
        );
        return $columns;
    }

    function column_cb($item){
        return sprintf( '<input type="checkbox" name="%1$s[]" value="%2$s" />', $this->_args['singular'], $item['id'] );
    }

    function get_bulk_actions() {
        $actions = array(
            'delete'        => 'حذف'
        );
        return $actions;
    }

    function process_bulk_action() {
        $action = $this->current_action();

        $log_ids = isset($_REQUEST['log']) ? array_map('absint', $_REQUEST['log']) : array();
        if ($action) {
            if (!wp_verify_nonce($_REQUEST['_wpnonce'])){
                Helpers::add_notice("خطا در تصدیق درخواست", "error");
                return;
            }
            if (empty($log_ids)) {
                Helpers::add_notice("آیتم‌های مورد نظر خود را انتخاب کنید.", "warning");
                return;
            }
        }

        switch ($action) {
            case 'delete':
                if (DB::delete_sms_log_bulk($log_ids) == count($log_ids)){
                    $deleted_numbers = Helpers::replace_digits_en2fa(count($log_ids));
                    Helpers::add_notice("تعداد $deleted_numbers آیتم با موفقیت حذف شد.", "success");
                }
                break;
            
            default:
                break;
        }
    }

    function get_sortable_columns() {
        $sortable_columns = array(
            'reg_date'      => array('reg_date',false),
            'description'   => array('description',false),
            'result'        => array('result',false),
        );
        return $sortable_columns;
    }

    function prepare_items() {

        $per_page = (!empty($_REQUEST['per_page'])) ? absint($_REQUEST['per_page']) : 50;
        
        $columns = $this->get_columns();
        $hidden = array("id");
        $sortable = $this->get_sortable_columns();
        
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        $this->process_bulk_action();
        
        if ($this->has_search) {
            $data = SMS_Log::get_logs_filter($this->search_filter);
            $per_page = 1000;
        }
        else{
            $data = SMS_Log::get_logs();
        }

        function log_usort_reorder($a,$b){
            $orderby = (!empty($_REQUEST['orderby'])) ? sanitize_text_field($_REQUEST['orderby']) : 'reg_date'; 
            $order = (!empty($_REQUEST['order'])) ? sanitize_text_field($_REQUEST['order']) : 'desc';
            $result = strcmp($a[$orderby], $b[$orderby]); 
            return ($order==='asc') ? $result : -$result; 
        }
        usort($data, 'SabaPayamak\log_usort_reorder');
        
        $current_page = $this->get_pagenum();

        $total_items = count($data);
        
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);

        $this->items = $data;
 
        $this->set_pagination_args(array(
            'total_items' => $total_items,                  
            'per_page'    => $per_page,                     
            'total_pages' => ceil($total_items/$per_page)   
        ));
    }
}

class SMS_Log_Detail_Table extends \WP_List_Table {
    
    function __construct(){
        global $status, $page;
                
        parent::__construct( array(
            'singular'  => 'log',     
            'plural'    => 'logs',    
            'ajax'      => false        
        ) );
        
    }

    function column_default($item, $column_name){

        switch($column_name){
            case 'action':
                $value = "<a class='delete' onclick='return ks_confirmDelete();' style='color:crimson;' href=" . Helpers::clean_url() . "&action=delete_sms_log_record&log_id=$item[id]>پاک کردن</a>";
                return $value;
                break;
            
            case 'reg_date':
                $value = Helpers::miladi_to_shamsi_date(new \DateTime($item[$column_name]));
                break;
            
            case 'message':
                $value = "<p>$item[$column_name]</p>";
                break;

            default:
                $value = $item[$column_name];
                break;
        }
        return  Helpers::replace_digits_en2fa($value);
    }

    function get_columns(){
        $columns = array(
            'table_num'     => '#',
            'reg_date'      => 'زمان',
            'number'        => 'شماره',
            'message'       => 'نتیجه',
        );
        return $columns;
    }

    function get_sortable_columns() {
        $sortable_columns = array(
            // 'number'        => array('number',false),
        );
        return $sortable_columns;
    }

    function prepare_items() {

        $per_page = (!empty($_REQUEST['per_page'])) ? absint($_REQUEST['per_page']) : 10;
        
        $columns = $this->get_columns();
        $hidden = array("id");
        $sortable = $this->get_sortable_columns();
        
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        $this->process_bulk_action();
        
        $data = SMS_Log::get_log_details(absint(Helpers::replace_digits_fa2en($_GET['log_id'])));

        function log_detail_usort_reorder($a,$b){
            $orderby = (!empty($_REQUEST['orderby'])) ? sanitize_text_field($_REQUEST['orderby']) : 'reg_date'; 
            $order = (!empty($_REQUEST['order'])) ? sanitize_text_field($_REQUEST['order']) : 'desc';
            $result = strcmp($a[$orderby], $b[$orderby]); 
            return ($order==='asc') ? $result : -$result; 
        }
        usort($data, 'SabaPayamak\log_detail_usort_reorder');

        for ($i = 0; $i < count($data); $i++) {
            $data[$i]["table_num"] = $i + 1;
        }
        
        $current_page = $this->get_pagenum();

        $total_items = count($data);
        
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);

        $this->items = $data;
 
        $this->set_pagination_args(array(
            'total_items' => $total_items,                  
            'per_page'    => $per_page,                     
            'total_pages' => ceil($total_items/$per_page)   
        ));
    }

}
<?php

namespace SabaPayamak;

// جلوگیری از دسترسی مستقیم به فایل
if (!defined('ABSPATH')) exit;

class SMS_Log
{
    public static function get_logs()
    {
        return DB::get_sms_logs();
    }

    public static function get_logs_filter($filter)
    {
        return DB::get_sms_logs_filter($filter);
    }

    public static function get_log_details($log_record_id)
    {
        return DB::get_sms_log_details($log_record_id);
    }

    public static function add_log_record(sms_log_record $record)
    {
        $log_record_id = DB::add_sms_log($record);
        if ($record->send_success && $log_record_id) {
            DB::add_sms_log_single($record, $log_record_id);
        }
    }

    public static function delete_log_record($log_id)
    {
        return DB::delete_sms_log($log_id);
    }

    public static function handle_actions()
    {
        if (isset($_REQUEST['ks_action']) && $_REQUEST['ks_action'] == "delete_sms_log_record") {

            if (!wp_verify_nonce($_REQUEST['_wpnonce'])){
                Helpers::add_notice("خطا در تصدیق درخواست", "error");
                return;
            }

            $result = SMS_Log::delete_log_record(absint($_REQUEST['log_id']));
            if ($result == "1") 
                Helpers::add_notice("آیتم با موفقیت حذف شد.", "success");
            elseif ($result === false) 
                Helpers::add_notice("خطا در حذف آیتم", "error");
        }
    }
}

class sms_log_record
{
    public string $description;
    public string $message;
    public string $numbers;
    public int $number_count;
    public bool $send_success;
    public string $send_result;
    public array $result_array;
    public string $api_method;
    public string $http_method;
}
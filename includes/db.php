<?php

namespace SabaPayamak;

// جلوگیری از دسترسی مستقیم به فایل
if (!defined('ABSPATH')) exit;

class DB{
    
    private static function events_table()
    {
        global $wpdb;

        return "{$wpdb->prefix}sabapayamak_event";
    }

    private static function handlers_table()
    {
        global $wpdb;

        return "{$wpdb->prefix}sabapayamak_handler";
    }

    private static function sms_log_table()
    {
        global $wpdb;

        return "{$wpdb->prefix}sabapayamak_sms_log";
    }

    private static function sms_log_table_single()
    {
        global $wpdb;

        return "{$wpdb->prefix}sabapayamak_sms_log_single";
    }

    public static function prepare_db()
    {
        return 
            self::build_db() &&
            self::insert_db_values();
    }

    private static function build_db()
    {
        return
            self::create_events_table() &&
            self::create_handlers_table() &&
            self::create_sms_log_table() &&
            self::create_sms_log_table_single();
    }

    private static function insert_db_values()
    {
        return self::insert_default_events();
    }

    private static function insert_default_events()
    {
        global $wpdb;
        $events_table = self::events_table();

        $events = self::get_events();

        $default_events = Events::get_default_events();

        if ($events) {
            foreach ($default_events as $key => $default_event) {
                if (in_array($default_event["name"], array_column($events, "name"))) 
                    unset($default_events[$key]);
            }
        }

        if (empty($default_events))
            return true;

        $values_array = array();
        foreach ($default_events as $key => $default_event) {
            $values_array[] =  "('" . implode("','", $default_event) . "')";
        }
        $values = implode(", ", $values_array);

        $query =  "INSERT INTO $events_table (
                        category,
                        name,
                        displayName,
                        description,
                        can_use_user,
                        hook,
                        priority,
                        accepted_args
                    )
                    VALUES $values "
        ;
        $result = $wpdb->query($query);
        return $result;
    }

    private static function create_events_table()
    {
        global $wpdb;
        $events_table = self::events_table();

        $query = "CREATE TABLE IF NOT EXISTS $events_table(
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            category VARCHAR(50) NOT NULL,
            name VARCHAR(50) NOT NULL,
            displayName VARCHAR(50) NOT NULL,
            description VARCHAR(200) NOT NULL,
            can_use_user BIT NOT NULL,
            hook VARCHAR(100) NOT NULL,
            priority INT UNSIGNED NOT NULL,
            accepted_args INT UNSIGNED NOT NULL,
            CONSTRAINT uc_name UNIQUE (name)
            ) COLLATE utf8mb4_general_ci;";

        $result = $wpdb->query($query);
        return $result;
    }

    private static function create_handlers_table()
    {
        global $wpdb;
        $events_table = self::events_table();
        $handlers_table = self::handlers_table();

        $query = "CREATE TABLE IF NOT EXISTS $handlers_table(
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            event_id INT UNSIGNED NOT NULL,
            isActive BIT NOT NULL,
            pattern VARCHAR(500) NOT NULL,
            send_to VARCHAR(20) NOT NULL,
            number  VARCHAR(200) NOT NULL,
            reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (event_id) REFERENCES $events_table(id) ON DELETE CASCADE
            ) COLLATE utf8mb4_general_ci;";

        $result = $wpdb->query($query);
        return $result;
    }
    
    private static function create_sms_log_table()
    {
        global $wpdb;
        $sms_log_table = self::sms_log_table();

        $query = "CREATE TABLE IF NOT EXISTS $sms_log_table(
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            description VARCHAR(200) NOT NULL,
            message VARCHAR(200) NOT NULL,
            numbers TEXT NOT NULL,
            numberCount INT UNSIGNED NOT NULL,
            isSuccess BIT NOT NULL,
            result VARCHAR(500) NOT NULL,
            httpMethod VARCHAR(50) NOT NULL,
            apiMethod VARCHAR(50) NOT NULL,
            reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) COLLATE utf8mb4_general_ci;";
            
        $result = $wpdb->query($query);
        return $result;
    }
    
    private static function create_sms_log_table_single()
    {
        global $wpdb;
        $sms_log_table_single = self::sms_log_table_single();
        $sms_log_table = self::sms_log_table();

        $query = "CREATE TABLE IF NOT EXISTS $sms_log_table_single(
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            log_record_id INT UNSIGNED NOT NULL,
            status VARCHAR(10),
            message VARCHAR(100),
            number VARCHAR(500) NOT NULL,
            recID VARCHAR(20) NOT NULL,
            reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (log_record_id) REFERENCES $sms_log_table(id) ON DELETE CASCADE
            ) COLLATE utf8mb4_general_ci;";
            
        $result = $wpdb->query($query);
        return $result;
    }

    public static function get_events()
    {
        global $wpdb;
        $events_table = self::events_table();

        $query = "SELECT * FROM $events_table";

        $result = $wpdb->get_results($query, ARRAY_A);
        return $result;
    }

    public static function get_event_by_id($id)
    {
        global $wpdb;
        $events_table = self::events_table();

        $query =  "SELECT * FROM $events_table
                     WHERE $events_table.id = %d";

        $prepared_query = $wpdb->prepare($query, $id);

        $result = $wpdb->get_row($prepared_query, ARRAY_A);
        return $result;
    }

    public static function get_event_handlers()
    {
        global $wpdb;
        $events_table = self::events_table();
        $handlers_table = self::handlers_table();

        $query =  "SELECT * FROM $events_table
                     INNER JOIN $handlers_table
                     ON $events_table.id = $handlers_table.event_id
                     ORDER BY $handlers_table.id DESC";

        $result = $wpdb->get_results($query, ARRAY_A);
        return $result;
    }

    public static function get_event_handler_by_id($id)
    {
        global $wpdb;
        $events_table = self::events_table();
        $handlers_table = self::handlers_table();

        $query =  "SELECT * FROM $events_table
                     INNER JOIN $handlers_table
                     ON $events_table.id = $handlers_table.event_id
                     WHERE $handlers_table.id = %d";

        $prepared_query = $wpdb->prepare($query, $id);

        $result = $wpdb->get_row($prepared_query, ARRAY_A);
        return $result;
    }

    public static function get_event_handlers_by_event_name($event_name)
    {
        global $wpdb;
        $events_table = self::events_table();
        $handlers_table = self::handlers_table();

        $query =  "SELECT * FROM $events_table
                     INNER JOIN $handlers_table
                     ON $events_table.id = $handlers_table.event_id
                     WHERE $events_table.name = '%s'";

        $prepared_query = $wpdb->prepare($query, $event_name);

        $result = $wpdb->get_results($prepared_query, ARRAY_A);
        return $result;
    }

    public static function add_event_handler($event_id, $is_active, $pattern, $number, $send_to)
    {
        global $wpdb;
        $handlers_table = self::handlers_table();

        $query  = "INSERT INTO $handlers_table
                     (event_id, isActive, pattern, number, send_to)
                     VALUES (%d, %d, '%s', '%s', '%s')";

        $prepared_query = $wpdb->prepare($query, array($event_id, $is_active, $pattern, $number, $send_to));

        $result = $wpdb->query($prepared_query);
        return $result;
    }

    public static function edit_event_handler($event_id, $pattern, $number, $send_to, $event_handler_id)
    {
        global $wpdb;
        $handlers_table = self::handlers_table();
        
        $query  = "UPDATE $handlers_table
                     SET event_id = %d , pattern = '%s', number = '%s', send_to = '%s'
                     WHERE id = %d";

        $prepared_query = $wpdb->prepare($query, array($event_id, $pattern, $number, $send_to, $event_handler_id));

        $result = $wpdb->query($prepared_query);
        return $result;
    }

    public static function delete_event_handler($event_handler_id)
    {
        global $wpdb;
        $handlers_table = self::handlers_table();
        
        $query  = "DELETE FROM $handlers_table
                     WHERE id = %d";

        $prepared_query = $wpdb->prepare($query, $event_handler_id);

        $result = $wpdb->query($prepared_query);
        return $result;
    }

    public static function delete_event_handler_bulk($event_handler_ids)
    {
        global $wpdb;
        $handlers_table = self::handlers_table();

        $int_placeholders = "(" . implode(',', (array_fill(0, count($event_handler_ids), "%d"))) . ")";
        
        $query  = "DELETE FROM $handlers_table
                     WHERE id in $int_placeholders";

        $prepared_query = $wpdb->prepare($query, $event_handler_ids);

        $result = $wpdb->query($prepared_query);
        return $result;
    }

    public static function event_handler_change_active($id, $is_active)
    {
        global $wpdb;
        $handlers_table = self::handlers_table();
        
        $is_active = ($is_active == 1) ? 0 : 1 ;

        $query  = "UPDATE $handlers_table
                     SET isActive = %d
                     WHERE id = %d";
        
        $prepared_query = $wpdb->prepare($query, array($is_active, $id));    

        $result = $wpdb->query($prepared_query);
        return $result;
    }

    public static function event_handler_change_active_bulk($event_handler_ids, $is_active)
    {
        global $wpdb;
        $handlers_table = self::handlers_table();

        $int_placeholders = "(" . implode(',', (array_fill(0, count($event_handler_ids), "%d"))) . ")";
        
        $is_active = ($is_active == 1) ? 1 : 0 ;

        $query  = "UPDATE $handlers_table
                     SET isActive = %d
                     WHERE id in $int_placeholders";
        
        $prepared_query = $wpdb->prepare($query, array_merge(array($is_active), $event_handler_ids));    

        $result = $wpdb->query($prepared_query);
        return $result;
    }
    
    public static function get_sms_logs()
    {
        global $wpdb;
        $sms_log_table = self::sms_log_table();

        $query =  "SELECT * FROM $sms_log_table
                     ORDER BY $sms_log_table.id DESC";

        $result = $wpdb->get_results($query, ARRAY_A);
        return $result;
    }
    
    public static function get_sms_logs_filter($filter)
    {
        global $wpdb;
        $sms_log_table = self::sms_log_table();

        $filter_fa = Helpers::replace_digits_en2fa($filter);
        $filter_en = Helpers::replace_digits_fa2en($filter);

        $query =  "SELECT * FROM $sms_log_table
                     WHERE message LIKE '%%%s%%'
                        OR message LIKE '%%%s%%'
                        OR numbers LIKE '%%%s%%'
                        OR numbers LIKE '%%%s%%'
                     ORDER BY $sms_log_table.id DESC";

        $prepared_query = $wpdb->prepare($query, array($filter_fa, $filter_en, $filter_fa, $filter_en));

        $result = $wpdb->get_results($prepared_query, ARRAY_A);
        return $result;
    }
    
    public static function get_last_sms_log()
    {
        global $wpdb;
        $sms_log_table = self::sms_log_table();

        $query =  "SELECT * FROM $sms_log_table
                     ORDER BY $sms_log_table.id DESC
                     LIMIT 1";

        $result = $wpdb->get_results($query, ARRAY_A);
        return $result;
    }
    
    public static function get_sms_log_details($log_record_id)
    {
        global $wpdb;
        $sms_log_table_single = self::sms_log_table_single();

        $query =  "SELECT * FROM $sms_log_table_single
                     WHERE log_record_id = %d
                     ORDER BY $sms_log_table_single.id";

        $prepared_query = $wpdb->prepare($query, $log_record_id);

        $result = $wpdb->get_results($prepared_query, ARRAY_A);

        return $result;
    }

    public static function add_sms_log(sms_log_record $log_record)
    {
        global $wpdb;
        $sms_log_table = self::sms_log_table();

        $time = wp_date('Y-m-d H:i:s');

        $query  = "INSERT INTO $sms_log_table
                     (description, message, numbers, numberCount, isSuccess, result, httpMethod, apiMethod, reg_date)
                     VALUES (%s, '%s', '%s', %d, %d, '%s', '%s', '%s', '%s')";

        $prepared_query = $wpdb->prepare($query, array($log_record->description, $log_record->message, $log_record->numbers, $log_record->number_count, $log_record->send_success, $log_record->send_result, $log_record->http_method, $log_record->api_method, $time));

        $result = $wpdb->query($prepared_query);
        if ($result)
            $result = $wpdb->insert_id;
        
        return $result;
    }

    public static function add_sms_log_single(sms_log_record $log_record, int $log_record_id)
    {
        global $wpdb;
        $sms_log_table_single = self::sms_log_table_single();

        $time = wp_date('Y-m-d H:i:s');
        
        foreach ($log_record->result_array as $key => $single_result) {
            
            $query  = "INSERT INTO $sms_log_table_single
                            (log_record_id, status, message, number, recID, reg_date)
                            VALUES (%d, '%s', '%s', '%s', '%s', '%s')";

            $prepared_query = $wpdb->prepare($query, array($log_record_id, $single_result->status, $single_result->message, $single_result->number, $single_result->recID, $time));
    
            $wpdb->query($prepared_query);
        }
    }

    public static function delete_sms_log($sms_log_record_id)
    {
        global $wpdb;
        $sms_log_table = self::sms_log_table();

        $query  = "DELETE FROM $sms_log_table
                     WHERE id = %d";

        $prepared_query = $wpdb->prepare($query, $sms_log_record_id);

        $result = $wpdb->query($prepared_query);
        return $result;
    }

    public static function delete_sms_log_bulk($sms_log_record_ids)
    {
        global $wpdb;
        $sms_log_table = self::sms_log_table();

        $int_placeholders = "(" . implode(',', (array_fill(0, count($sms_log_record_ids), "%d"))) . ")";
        
        $query  = "DELETE FROM $sms_log_table
                     WHERE id in $int_placeholders";

        $prepared_query = $wpdb->prepare($query, $sms_log_record_ids);

        $result = $wpdb->query($prepared_query);
        return $result;
    }
}
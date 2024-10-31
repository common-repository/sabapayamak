<?php

namespace SabaPayamak;

use Exception;
use WP_SMS\Gateway\smsc;

// جلوگیری از دسترسی مستقیم به فایل
if (!defined('ABSPATH')) exit;

class SMS{

    public $options;

    public function __construct()
    {
        $this->options = Helpers::get_sabapayamak_options();
    }

    /**
     * ارسال پیامک به آرایه‌ای از شماره‌ها
     * @param string $description موضوع ارسال
     * @param array $to آرایه‌ای از شماره‌ها
     * @param string $message متن پیامک
     * @return bool نتیجه ارسال موفق/ناموفق
     */
    public function send_sms($description, $to, $message)
    {
        if (gettype($to) == "string")
            $to = array($to);

        $to = array_filter( $to, "SabaPayamak\Helpers::is_mobile_valid");
        $to = array_map("SabaPayamak\Helpers::repair_mobile_number", $to);
        $to = array_unique($to);
        
        $api_method = $this->options["api_method"];
        $send_method = $this->options["send_method"];

        if(count($to) == 0)
            return false;

        $is_success = true;
        
        while (count($to) > 0) {

            $sms_log_record = new sms_log_record();
            $sms_log_record->description = $description;
            $sms_log_record->message = $message;
            $sms_log_record->api_method = $api_method;
            $sms_log_record->http_method = $send_method;
            
            $to_part = array_slice($to, 0, 80);
            $to = array_diff($to, $to_part);

            $sms_log_record->numbers = implode(" ", $to_part);
            $sms_log_record->number_count = count($to_part);
            
            if ($api_method == "API")
            {
                if ($send_method == HTTP_method::GET) 
                    $this->send_sms_api_get($to_part, $message, $sms_log_record);
                elseif ($send_method == HTTP_method::POST) 
                    $this->send_sms_api_post($to_part, $message, $sms_log_record);
                else{
                    $sms_log_record->send_success = false;
                    $sms_log_record->send_result = "روش ارسال پیامک مشخص نشده است.";
                }
            }
            elseif ($api_method == "web_service")
            {
                if ($send_method == HTTP_method::GET) 
                    $this->send_sms_HTTP($to_part, $message, $sms_log_record);
                elseif ($send_method == HTTP_method::POST) 
                    $this->send_sms_web_service($to_part, $message, $sms_log_record);
                else{
                    $sms_log_record->send_success = false;
                    $sms_log_record->send_result = "روش ارسال پیامک مشخص نشده است.";
                }
            }
            else{
                $sms_log_record->send_success = false;
                $sms_log_record->send_result = "نحوه اتصال به سامانه پیامک مشخص نشده است.";
            }

            if ($sms_log_record->send_success == false)
                $is_success = false;

            SMS_Log::add_log_record($sms_log_record);
        }

        return $is_success;
    }

    public function get_credit()
    {
        $url = $this->options["web_service_domain"] . "/API/GetCredit.ashx?username=%s&password=%s";

        $url = sprintf($url, $this->options["user_name"], $this->options["password"]);

        $response = wp_safe_remote_get($url);
        
        if (is_wp_error($response)){
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $http_status_code = $response["response"]["code"];

        if ($http_status_code == HttpStatus::Ok) {
            return $body;
        }
        else{
            return false;
        }
    }

    private function send_sms_api_get(array $to, string $message, sms_log_record $sms_log_record)
    {
        $to = implode(",", $to);

        $url = $this->options["api_domain"] . "/api/v1/message?UserName=%s&Password=%s&From=%s&To=%s&Text=%s";
        
        $url = sprintf($url, $this->options["user_name"], $this->options["password"], $this->options["v_number"], $to, urlencode($message));
        
        $response = wp_safe_remote_get($url);
        
        if (is_wp_error($response)){
            $sms_log_record->send_success = false;
            $sms_log_record->send_result = implode(", ", $response->get_error_codes()) . " - " . implode(", ", $response->get_error_messages());
            return;
        }
        
        $response_obj = json_decode(wp_remote_retrieve_body($response));

        if ($response_obj->status == HttpStatus::Ok){
            $sms_log_record->send_success = true;
            $sms_log_record->send_result = ResultStatus::Ok;
            
            $result_array = array();
            $results = $response_obj->data->detail ?? array();
            foreach ($results as $key => $result) {
                $sms_result = new SMS_Result();
                $sms_result->status = $result->status;
                $sms_result->message = $result->message;
                $sms_result->number = $result->number;
                $sms_result->recID = $result->recID;
                array_push($result_array, $sms_result);
            }
            $sms_log_record->result_array = $result_array;
        }
        else{
            $sms_log_record->send_success = false;
            $sms_log_record->send_result = $this->api_get_error_msg($response_obj);
        }            
    }

    private function send_sms_api_post(array $to, string $message, sms_log_record $sms_log_record)
    {
        $token_array = $this->get_token();
        if ($token_array["success"] === false) {
            $sms_log_record->send_success = false;
            $sms_log_record->send_result = $token_array["error"];
            return;
        }
        $token = $token_array["token"];
        
        $service = new SabapayamakApi($this->options["api_domain"]);

        $response = $service->SendMessage($message, $to, $token);
        
        if (is_wp_error($response)){
            $sms_log_record->send_success = false;
            $sms_log_record->send_result = implode(", ", $response->get_error_codes()) . " - " . implode(", ", $response->get_error_messages());
            return;
        }

        $response = json_decode($response['body']);
        if ($response->status == HttpStatus::UnAuthorized) {
            $token_array = $this->refresh_token();
            if ($token_array["success"] === false) {
                $sms_log_record->send_success = false;
                $sms_log_record->send_result = $token_array["error"];
                return;
            }
            $token = $token_array["token"];
            
            $response = $service->SendMessage($message, array($to), $token);
        }
        
        if ($response->status == HttpStatus::Ok){
            $sms_log_record->send_success = true;
            $sms_log_record->send_result = ResultStatus::Ok;

            $result_array = array();
            $results = $response->data->detail ?? array();
            foreach ($results as $key => $result) {
                $sms_result = new SMS_Result();
                $sms_result->status = $result->status;
                $sms_result->message = $result->message;
                $sms_result->number = $result->number;
                $sms_result->recID = $result->recID;
                array_push($result_array, $sms_result);
            }
            $sms_log_record->result_array = $result_array;
        }
        else{
            $sms_log_record->send_success = false;
            $sms_log_record->send_result = $this->api_get_error_msg($response);
        }   
    }

    private function send_sms_HTTP(array $to, string $message, sms_log_record $sms_log_record)
    {
        $url = $this->options["web_service_domain"] . "/API/SendSms.ashx?username=%s&password=%s&from=%s&to=%s&text=%s";

        $url = sprintf($url, $this->options["user_name"], $this->options["password"], $this->options["v_number"], implode(",", $to), urlencode($message));

        $response = wp_safe_remote_get($url);
        
        if (is_wp_error($response)){
            $result = implode(", ", $response->get_error_codes()) . " - " . implode(", ", $response->get_error_messages());
            $sms_log_record->send_result = $result;
            $sms_log_record->send_success = false;
            return;
        }

        $success = false;
        $result = "";
        
        $body = wp_remote_retrieve_body($response);
        $http_status_code = $response["response"]["code"];

        if ($http_status_code == HttpStatus::Ok) {
            switch ($body) {
                case '': // 0 may cast to ''
                    $result = ResultStatus::InvalidUserOrPass;
                    break;

                case '0':
                    $result = ResultStatus::InvalidUserOrPass;
                    break;

                case '1':
                    $result = ResultStatus::NoCharge;
                    break;

                case '2':
                    $result = ResultStatus::SendLimit;
                    break;

                case '3':
                    $result = ResultStatus::EmptyUserName;
                    break;

                case '4':
                    $result = ResultStatus::EmptyPassword;
                    break;

                case '5':
                    $result = ResultStatus::EmptyVNumber;
                    break;

                case '6':
                    $result = ResultStatus::EmptyReceiver;
                    break;

                case '7':
                    $result = ResultStatus::InvalidText;
                    break;

                case '8':
                    $result = ResultStatus::FlashNotAllowed;
                    break;

                case '9':
                    $result = ResultStatus::InvalidSender;
                    break;

                case '10':
                    $result = ResultStatus::SystemDown;
                    break;

                case '11':
                    $result = ResultStatus::InvalidParameter;
                    break;

                default:
                
                    $result_array = array();
                    $recID_array = explode(",", $body);
                    if (count($to) == count($recID_array)) {
                        $success = true;
                        $result = ResultStatus::Ok;
                        
                        for ($i=0; $i < count($recID_array) ; $i++) { 
                            $sms_result = new SMS_Result();
                            $sms_result->status = 0;
                            $sms_result->message = ResultStatus::Ok;
                            $sms_result->number = $to[$i];
                            $sms_result->recID = $recID_array[$i];
                            array_push($result_array, $sms_result);
                        }
                        $sms_log_record->result_array = $result_array;
                    }
                    else{
                        $result = ResultStatus::NoResult;
                    }
                    
                    break;
            }
        }
        else{
            $result = $this->http_code_to_msg($http_status_code);
        }
        
        $sms_log_record->send_result = $result;
        $sms_log_record->send_success = $success;
    }

    private function send_sms_web_service(array $to, string $message, sms_log_record $sms_log_record)
    {
        if (!Helpers::is_soap_enabled()) {
            return array("ماژول SOAP بر روی سایت فعال نیست");
        }
        
        $result_array = array();

        $url = $this->options["web_service_domain"] . "/API/Send.asmx?WSDL";
        
        $success = false;
        $result = "";

        try {
            $soap_client = new \SoapClient($url);
            $params = array(
                "username" => $this->options["user_name"],  // string
                "password" => $this->options["password"],   // string
                "from"     => $this->options["v_number"],   // string
                "to"       => implode(",", $to),            // ArrayOfString
                "text"     => $message,                     // string
                "flash"    => false,                        // boolean
                "status"   => "",                           // base64Binary
                "recId"    => "",                           // ArrayOfLong
            );

            $response = $soap_client->SendSms($params);
            $code = $response->SendSmsResult;
            
            switch ($code) {
                case '-1':
                    $result = ResultStatus::InvalidUserOrPass;
                    break;

                case '0':
                    
                    if ($response->recId == new \stdClass()) { // ارسال نشده و نتیجه خالی است
                        $result = ResultStatus::NoResult;
                    }
                    else{
                        $result_array = array();
                        $recID_array = explode(",",  $response->recId);

                        if (count($to) == count($recID_array)) {
                            $success = true;
                            $result = ResultStatus::Ok;
        
                            for ($i=0; $i < count($recID_array) ; $i++) { 
                                $sms_result = new SMS_Result();
                                $sms_result->status = 0;
                                $sms_result->message = ResultStatus::Ok;
                                $sms_result->number = $to[$i];
                                $sms_result->recID = $recID_array[$i];
                                array_push($result_array, $sms_result);
                            }
                            $sms_log_record->result_array = $result_array;
                        }
                        else{
                            $result = ResultStatus::NoResult;
                        }
                    }   

                    break;

                case '1':
                    $result = ResultStatus::NoCharge;
                    break;

                case '2':
                    $result = ResultStatus::SendLimit;
                    break;

                case '3':
                    $result = ResultStatus::InvalidSender;
                    break;

                case '11':
                    $result = ResultStatus::InvalidParameter;
                    break;

                default:
                    $result = ResultStatus::NoResult;
                    break;
            }
        } catch (Exception $ex) {
            $result = "خطای SOAP: " . $ex->getMessage();
        }

        $sms_log_record->send_result = $result;
        $sms_log_record->send_success = $success;
    }

    private function get_token()
    {
        $token = get_option("sabapayamak_api_token");
        if ($token === false){
            $token_array = $this->refresh_token();
            return $token_array;
        }
        else {
            return array(
                "success"   => true,
                "token"     => $token
            );
        }
    }

    private function refresh_token()
    {
        $service = new SabapayamakApi($this->options["api_domain"]);
        $result = $service->GetToken($this->options["user_name"], $this->options["password"], $this->options["v_number"], 365);

        if (is_wp_error($result)){
            return array(
                "success"   => false,
                "error"     => implode(", ", $result->get_error_codes()) . " - " . implode(", ", $result->get_error_messages())
            );
        }

        $body = json_decode($result['body']);
        $token = isset($body->data) ? $body->data->token : null;

        if ($token == null) {
            $error = ( !isset($result['error']) || $result['error'] == null) ? json_decode($result['body'])->errors[0]->message : $result->errors[0]->message;
            
            return array(
                "success"   => false,
                "error"     => $error
            );
        } 
        else {
            update_option("sabapayamak_api_token", $token);

            return array(
                "success"   => true,
                "token"     => $token
            );
        }
    }

    private function http_code_to_msg($http_status_code)
    {
        switch ($http_status_code) {
            case HttpStatus::BadRequest:
                $msg = ResultStatus::BadRequest;
                break;

            case HttpStatus::NoContent:
                $msg = ResultStatus::NoContent;
                break;

            case HttpStatus::NotFound:
                $msg = ResultStatus::NotFound;
                break;

            case HttpStatus::UnAuthorized:
                $msg = ResultStatus::UnAuthorized;
                break;

            case HttpStatus::Forbidden:
                $msg = ResultStatus::Forbidden;
                break;

            case HttpStatus::InternalServerError:
                $msg = ResultStatus::InternalServerError;
                break;

            default:
                $msg = "خطای " . $http_status_code;
                break;
        }

        return $msg;
    }

    private function api_get_error_msg($response_obj)
    {      
        $errors = $response_obj->errors;
        if ($errors != null) {
            $error_type = gettype($errors);
            if ($error_type == "array") 
                $result = $errors[0]->message;
            elseif ($error_type == "object")
                $result = json_encode($errors);

            return $result;
        }

        $result = ResultStatus::NoResult;
        return $result;
    }


}

class SMS_Result
{
    public string $status;
    public string $message;
    public string $number;
    public string $recID;
}
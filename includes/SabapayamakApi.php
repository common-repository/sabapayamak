<?php

namespace SabaPayamak;
use Exception;

// جلوگیری از دسترسی مستقیم به فایل
if (!defined('ABSPATH')) exit;

class SabapayamakApi
{
    const VERSION = "1.0.0";
    public function __construct($apiPath)
    {
        if (is_null($apiPath)) {
            die('apiPath is empty');
            exit;
        }
        $this->apiPath = trim($apiPath);
    }   

	
        protected function get_path($method)
    {
        if($method == "getToken")
        {
            return sprintf($this->apiPath."/api/v1/user/authenticate");
        }
        if($method == "getCredit")
        {
            return sprintf($this->apiPath."/api/v1/credit");
        }
        if($method == "getCreditByDate")
        {
            return sprintf($this->apiPath."/api/v1/credit");
        }
        if($method == "getCreditByCount")
        {
            return sprintf($this->apiPath."/api/v1/credit");
        }
        if($method == "getCreditForSendSms")
        {
            return sprintf($this->apiPath."/api/v1/credit/send-sms");
        }
        if($method == "getCreditForRecivedSms")
        {
            return sprintf($this->apiPath."/api/v1/credit/recived");
        }
        if($method == "getCreditForCharge")
        {
            return sprintf($this->apiPath."/api/v1/credit/charge");
        }
        if($method == "getCreditForMoneyBack")
        {
            return sprintf($this->apiPath."/api/v1/credit/money-back");
        }
        if($method == "getMessagesByDate")
        {
            return sprintf($this->apiPath."/api/v1/credit/messages");
        }
        if($method == "getMessageById")
        {
            return sprintf($this->apiPath."/api/v1/credit/messages");
        }
        if($method == "getMessagesByNumber")
        {
            return sprintf($this->apiPath."/api/v1/credit/messages/number");
        }
        if($method == "postMessage")
        {
            return sprintf($this->apiPath."/api/v1/message");
        }
        if($method == "getDeliveriesById")
        {
            return sprintf($this->apiPath."/api/v1//api/v1/deliveries");
        }
        if($method == "getRecivedMessageByDate")
        {
            return sprintf($this->apiPath."/api/v1/recived-messages");
        }
        if($method == "getRecivedMessageByNumber")
        {
            return sprintf($this->apiPath."/api/v1/recived-messages");
        }
        if($method == "getUnreadRecivedMessageByNumber")
        {
            return sprintf($this->apiPath."/api/v1/recived-messages");
        }
        if($method == "getRecivedMessageByVNumber")
        {
            return sprintf($this->apiPath."/api/v1/recived-messages/virtaul-number");
        }
        if($method == "getUnreadRecivedMessageByVNumber")
        {
            return sprintf($this->apiPath."/api/v1/recived-messages/virtaul-number");
        }
        if($method == "getUnreadRecivedMessage")
        {
            return sprintf($this->apiPath."/api/v1/recived-messages/unread");
        }

        
    }
	protected function PostWithToken($url, $data,$token)
    {        
        try{

            $authorization = "Authorization: Bearer ".$token;

            $args = array(
                'headers'     => array(
                    'Authorization' => $authorization,
                    'Content-Type'  => 'application/json',
                ),
                'body'        => json_encode($data),
                'method'      => 'POST',
                'data_format' => 'body',
                'sslverify' => false
            );

            $response = wp_safe_remote_post($url, $args);
            return $response;

        }
        catch(Exception $e) {
            return $e->getMessage();
          }  
    }

    protected function Post($url, $data)
    {          
        try{
            $args = array(
                'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
                'body'        => json_encode($data),
                'method'      => 'POST',
                'data_format' => 'body',
            );

            $response = wp_safe_remote_post($url, $args);
            return $response;
        }
        catch(Exception $e) {
            return $e->getMessage();
        }      
    }

    protected function GetWithToken($url,$token)
    {        
        try{
            $authorization = "Authorization: Bearer ".$token;
            $args = array(
                'headers'       => array(
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'charset: utf-8',
                    $authorization
                )
            );

            $response = wp_safe_remote_get($url, $args);
            return $response;

        }
        catch(Exception $e) {
            return $e->getMessage();
          }  
    }

    public function GetToken($username, $password, $virtualnumber, $validday){
        $path   = $this->get_path("getToken");
        $params = array(
            "username" => $username,
            "password" => $password,
            "virtualnumber" => $virtualnumber,
            "tokenvalidday" => $validday
        );

        return $this->post($path, $params);
    }
    public function GetCredit($token){
        $path   = $this->get_path("getCredit");
        return $this->GetWithToken($path, $token);
    }
    public function GetCreditByDate($startdate,$enddate,$token){
        $path   = $this->get_path("getCreditByDate")."?StartDate=".$startdate."&EndDate=".$enddate;
        return $this->GetWithToken($path, $token);
    }
    public function GetCreditByCount($count,$token){
        $path   = $this->get_path("getCreditByCount")."/".$count;
        return $this->GetWithToken($path, $token);
    }
    public function GetCreditForSendSms($count,$token){
        $path   = $this->get_path("getCreditForSendSms")."/".$count;
        return $this->GetWithToken($path, $token);
    }
    public function GetCreditForRecivedSms($count,$token){
        $path   = $this->get_path("getCreditForRecivedSms")."/".$count;
        return $this->GetWithToken($path, $token);
    }
    public function GetCreditForCharge($count,$token){
        $path   = $this->get_path("getCreditForCharge")."/".$count;
        return $this->GetWithToken($path, $token);
    }
    public function GetCreditForMoneyBack($count,$token){
        $path   = $this->get_path("getCreditForMoneyBack")."/".$count;
        return $this->GetWithToken($path, $token);
    }
    public function GetMessagesByDate($startdate,$enddate,$token){
        $path   = $this->get_path("getMessagesByDate")."?StartDate=".$startdate."&EndDate=".$enddate;
        return $this->GetWithToken($path, $token);
    }
    public function GetMessageById($id,$token){
        $path   = $this->get_path("getMessageById")."/".$id;
        return $this->GetWithToken($path, $token);
    }

    public function GetMessageByNumber($number,$token){
        $path   = $this->get_path("getMessagesByNumber")."/".$number;
        return $this->GetWithToken($path, $token);
    }
    public function SendMessage($text,$numbers,$token){
        $path   = $this->get_path("postMessage");
        $params = array(
            "text" => $text,
            "numbers" => $numbers
        );
       return $this->PostWithToken($path, $params, $token);

    }
    public function GetDeliveriesById($id,$token){
        $path   = $this->get_path("getDeliveriesById")."/".$id;
        return $this->GetWithToken($path, $token);
    }
    public function GetRecivedMessageByDate($startdate,$enddate,$token){
        $path   = $this->get_path("getRecivedMessageByDate")."?StartDate=".$startdate."&EndDate=".$enddate;
        return $this->GetWithToken($path, $token);
    }
    public function GetRecivedMessageByNumber($number,$token){
        $path   = $this->get_path("getRecivedMessageByNumber")."/".$number;
        return $this->GetWithToken($path, $token);
    }
    public function GetUnreadRecivedMessageByNumber($number,$token){
        $path   = $this->get_path("getUnreadRecivedMessageByNumber")."/".$number."/unread";
        return $this->GetWithToken($path, $token);
    }
    public function GetRecivedMessageByVNumber($vnumber,$token){
        $path   = $this->get_path("getRecivedMessageByVNumber")."/".$vnumber;
        return $this->GetWithToken($path, $token);
    }
    public function GetUnreadRecivedMessageByVNumber($vnumber,$token){
        $path   = $this->get_path("getUnreadRecivedMessageByVNumber")."/".$vnumber."/unread";
        return $this->GetWithToken($path, $token);
    }
    public function GetUnreadRecivedMessage($token){
        $path   = $this->get_path("getUnreadRecivedMessage");
        return $this->GetWithToken($path, $token);
    }
}

abstract class HttpStatus
{
    const Ok = "200";
    const NoContent = "204";
    const BadRequest = "400";
    const NotFound = "404";
    const UnAuthorized = "401";
    const Forbidden = "403";
    const InternalServerError = "500";
}

abstract class ResultStatus
{
    const Ok = "با موفقیت ارسال شد";
    const InvalidReciver = "شماره گیرنده نادرست است";
    const InvalidSender = "شماره فرستنده نادرست است";
    const InvalidEncodeing = "پارامتر انکودینگ نامعتبر است";
    const InvalidMClass = "پارامتر mclass نامعتبر است";
    const InvalidUDH = "پارامتر UDH نامعتبر است";
    const InvalidText = "محتویات پیامک خالی است";
    const NoCharge = "مانده اعتبار ریالی مورد نیاز برای ارسال پیامک کافی نیست";
    const ServerBusy = "سرور در هنگام ارسال پیام مشغول بر طرف نمودن ایراد داخلی بوده است";
    const DisabledAccount = "حساب غیر فعال است";
    const ExpireAccount = "حساب منقضی شده است";
    const InvalidUserOrPass = "نام کاربری و یا کلمه عبور نا معتبر است";
    const InvalidRequest = "درخواست معتبر نیست";
    const InvalidSenderError = "شماره فرستنده به حساب تعلق ندارد";
    const AccessFaild = "این سرویس برای حساب فعال نشده است";
    const RetryAgain = "در حال حاضر امکان پردازش درخواست جدید وجود ندارد،لطفا دوباره سعی کنید";
    const InvalidUID = "شناسه پیامک نا معتبر است";
    const InvalidMethod = "نام متد درخواستی معتبر نیست";
    const BlackList = "شماره گیرنده در لیست سیاه اپراتور قرار دارد";
    const PreNumberBlocked = "شماره گیرنده بر اساس پیش شماره در حال حاضر در پروایدر مسدود است";
    const InvalidIP = "آدرس IP مبدا، اجازه دسترسی به این سرویس را ندارد";
    const InvalidMessagePart = "تعداد بخش‌های پیامک بیش از حد مجاز استاندارد (۲۶۵ عدد) است";
    const InvalidMessageBodies = "طول آرایه پارامتر messageBodies با طول آرایه گیرندگان تطابق ندارد";
    const InvalidMessageClass = "طول آرایه پارامتر messageClass با طول آرایه گیرندگان تطابق ندارد";
    const InvalidSenderNumbers = "طول آرایه پارامتر senderNumbers با طول آرایه گیرندگان تطابق ندارد";
    const InvalidUDHs = "طول آرایه پارامتر udhs با طول آرایه گیرندگان تطابق ندارد";
    const InvalidPriorities = "طول آرایه پارامتر priorities با طول آرایه گیرندگان تطابق ندارد";
    const InvalidRecipents = "آرایه‌ی گیرندگان خالی است";
    const InvalidParameter = "طول آرایه پارامتر گیرندگان بیشتر از طول مجاز است";
    const InvalidSenders = "آرایه‌ی فرستندگان خالی است";
    const InvalidEncodings = "طول آرایه پارامتر encoding با طول آرایه گیرندگان تطابق ندارد";
    const InvalidCheckingMessageIds = "طول آرایه پارامتر checkingMessageIds با طول آرایه گیرندگان تطابق ندارد";
    const SuccessToken = "کلید امنیتی شما با موفقیت ایجاد شد";
    const NoContent = "داده ای وجود ندارد";
    const Exception = "خطایی اتفاق افتاده است،لطفا با پشتیبانی تماس بگیرید";
    const Unathorize = "کلید امنیتی شما منقضی شده یا  اشتباه است";
    const UserOrPasswordWrong = "نام کاربری یا رمز عبور اشتباه است";
    const DomainWrong = "شما نمیتوانید از طریق این دامنه درخواست بدهید";
    const VnumerWrong = "شماره ارسال اشتباه است";
    const VnumerStatusWrong = "وضعیت شماره ارسال مناسب ارسال نیست";

    const SendLimit = "حساب كاربری دارای محدودیت ارسال است";
    const EmptyUserName = "پارامتر نام كاربری خالی است";
    const EmptyPassword = "پارامتر رمز عبور خالی است";
    const EmptyVNumber = "پارامتر شماره مجازی فرستنده خالی است";
    const EmptyReceiver = "پارامتر شماره موبایل مخاطب خالی است";
    const FlashNotAllowed = "ارسال به صورت فلش مجاز نیست";
    const SystemDown = "سیستم قطع است و پیام ارسال نمی‌شود";
    const BadRequest = "درخواست ارسال شده ناصحیح است";
    const NotFound = "آدرس نادرست است";
    const UnAuthorized = "درخواست نیازمند تصدیق می‌باشد";
    const Forbidden = "مجوز دسترسی وجود ندارد";
    const InternalServerError = "خطای داخلی سرور";
    const NoResult = "خطا در ارسال پیامک";
}

abstract class HTTP_method
{
    const GET = "GET";
    const POST = "POST";
}
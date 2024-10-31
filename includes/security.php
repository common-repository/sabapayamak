<?php

namespace SabaPayamak;

// جلوگیری از دسترسی مستقیم به فایل
if (!defined('ABSPATH')) exit;

class Security{

    private static function get_salt()
    {
        $domain = parse_url(home_url())['host'];

        $sabapayamak_password = Helpers::get_sabapayamak_options()['password'];

        $salt = "$domain#$sabapayamak_password";

        return $salt;
    }

    public static function hash(string $plain_text)
    {
        $salt = self::get_salt();
        $cipher_text = crypt($plain_text, $salt);
        return $cipher_text;
    }
}
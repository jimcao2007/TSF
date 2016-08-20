<?php

namespace Util;
class Auth
{
    public static function setCookie($key,$value,$expire)
    {
        $_COOKIE[$key] = $value;
        $expire+=time();
        @setcookie($key,$value,$expire,'/');
    }


    public static function getCookie($key)
    {
        if(empty($_COOKIE[$key]))
        {
            return false;
        }
        return $_COOKIE[$key];
    }

    public static function setSession($key,$value)
    {
        $_SESSION[$key] = $value;
    }

    public static function getSession($key)
    {
        if(empty($_SESSION[$key]))
        {
            return false;
        }

        return $_SESSION[$key];
    }

    public static function delSession($key)
    {
        unset($_SESSION[$key]);
    }


}
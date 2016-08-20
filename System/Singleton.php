<?php

class Singleton
{
    protected static $_singletons;

    /**
     * @param $key
     * @return Mysql
     */
    public static function getMysql($key)
    {
        if(empty(self::$_singletons['mysql'][$key]))
        {
            return false;
        }
        return self::$_singletons['mysql'][$key];
    }


    public static function setMysql($key,$config)
    {
        if(!empty(self::$_singletons['mysql'][$key]))
        {
            return true;
        }
        $mysql = new Mysql($config);
        self::$_singletons['mysql'][$key] = $mysql;
        return true;
    }


    /**
     * @param $key
     * @return \RedisCache
     */
    public static function getRedisCache($key)
    {
        if(empty(self::$_singletons['redis'][$key]))
        {
            return false;
        }
        return self::$_singletons['redis'][$key];
    }


    public static function setRedisCache($key,$config)
    {
        if(!empty(self::$_singletons['redis'][$key]))
        {
            return true;
        }
        $obj = new RedisCache($config);
        self::$_singletons['redis'][$key] = $obj;
        return true;
    }


    /**
     * @param $key
     * @return \ThirdPart\JPush
     */
    public static function getJPush($key)
    {
        if(empty(self::$_singletons['jpush'][$key]))
        {
            return false;
        }
        return self::$_singletons['jpush'][$key];
    }


    public static function setJPush($key,$config)
    {
        if(!empty(self::$_singletons['jpush'][$key]))
        {
            return true;
        }
        $obj = new \ThirdPart\Jpush($config);
        self::$_singletons['jpush'][$key] = $obj;
        return true;
    }

    /**
     * @param $key
     * @return \ThirdPart\Weixin
     */
    public static function getWeixin($key)
    {
        if(empty(self::$_singletons['weixin'][$key]))
        {
            return false;
        }
        return self::$_singletons['weixin'][$key];
    }


    public static function setWeixin($key,$config)
    {
        if(!empty(self::$_singletons['weixin'][$key]))
        {
            return true;
        }
        $obj = new \ThirdPart\Weixin($config);
        self::$_singletons['weixin'][$key] = $obj;
        return true;
    }


    public static function setOther($key,$class_name,$config=array())
    {
        if(empty($config))
        {
            $obj = new $class_name();
        }
        else
        {
            $obj = new $class_name($config);
        }
        self::$_singletons['other'][$key] = $obj;
        return true;
    }

    public static function getOther($key)
    {
        return self::$_singletons['other'][$key];
    }

    public static function setExist($key,$class)
    {
        self::$_singletons['exist'][$key] = $class;
    }

    public static function getExist($key)
    {
        return self::$_singletons['exist'][$key];
    }


}
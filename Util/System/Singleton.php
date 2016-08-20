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

    public static function getModel($name,$obj)
    {

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

    public static function set($key,$class_name,$config=array())
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

    public static function get($key)
    {
        return self::$_singletons['other'][$key];
    }
}
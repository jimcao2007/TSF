<?php

class S
{
    protected static $_singletons;

    protected static $mysql;
    protected static $redis;

    /**
     * @var \HttpBase
     */
    public static $http;

    /**
     * @var \swoole_server;
     */
    public static $swoole;

    public static $config;
    protected static $config_file;
    public static $format;
    protected static $model_objs;


    public static function init($config_file='')
    {
        $default_config_file = TSF_PATH . '/System/Config.php';
        $default_config = include($default_config_file);
        if(!empty($file) && file_exists($file))
        {
            $app_config = include($config_file);
            $default_config = array_merge($default_config,$app_config);
        }
        self::$config = $default_config;
    }

    /**
     * @param $key
     * @return Mysql
     */
    public static function getMysql($key)
    {
        if(empty(self::$mysql[$key]))
        {
            return false;
        }
        return self::$mysql[$key];
    }


    public static function setMysql($key,$config)
    {
        if(!empty(self::$mysql[$key]))
        {
            return true;
        }
        $mysql = new Mysql($config);
        self::$mysql[$key] = $mysql;
        return true;
    }

    /**
     * @param $key
     * @return \RedisCache
     */
    public static function getRedis($key)
    {
        if(empty(self::$redis[$key]))
        {
            return false;
        }
        return self::$redis[$key];
    }


    public static function setRedis($key,$config)
    {
        if(!empty(self::$redis[$key]))
        {
            return true;
        }
        $obj = new RedisCache($config);
        self::$redis[$key] = $obj;
        return true;
    }


    public static function Code($key)
    {
        return isset(self::$config['SYSTEM']['ERR_CODE'][$key]['code']) ? self::$config['SYSTEM']['ERR_CODE'][$key]['code'] : -999999;
    }


    /**
     * @param $model_name
     * @return \Model
     */
    public static function M($model_name)
    {
        if(empty(self::$model_objs[$model_name]))
        {
            return false;
        }
        return self::$model_objs[$model_name];
    }

    public static function setModel($model_name,$table_name,$mysql)
    {
        if(empty(self::$model_objs[$model_name]))
        {
            $class_name = '\Model\\'.$model_name;
            if(class_exists($class_name))
            {
                $m_obj = new $class_name($table_name,$mysql);
            }
            else{
                $m_obj = new \Model($table_name,$mysql);
            }
            self::$model_objs[$model_name] = $m_obj;
        }
        return self::$model_objs[$model_name];
    }

}
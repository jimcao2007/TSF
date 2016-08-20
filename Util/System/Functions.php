<?php

class Eg{

    /**
     * @var \HttpBase
     */
    public static $http;

    /**
     * @var \swoole_server;
     */
    public static $swoole;
}


class M
{
    protected static $model_objs;

    /**
     * @param $model_name
     * @return \Model
     */
    public static function get($model_name)
    {
        if(empty(self::$model_objs[$model_name]))
        {
            return false;
        }
        return self::$model_objs[$model_name];
    }

    public static function register($model_name,$table_name,$mysql)
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



class C
{
    public static $config;
    protected static $config_file;

    public static function init($file='')
    {
        $default_config_file = TS_PATH . '/System/Config.php';
        $default_config = include($default_config_file);
        if(!empty($file) && file_exists($file))
        {
            $app_config = include($file);
            $default_config = array_merge($default_config,$app_config);
        }
        self::$config = $default_config;
    }

    public static function Code($key)
    {
        return isset(self::$config['SYSTEM']['ERR_CODE'][$key]['code']) ? self::$config['SYSTEM']['ERR_CODE'][$key]['code'] : -999999;
    }

}

class F
{
    public static $format;
    public static function success()
    {

    }
}
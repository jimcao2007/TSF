<?php

class ConfigMan
{
    protected static $config;
    protected static $config_file;

    public static function setConfFile($file)
    {
        self::$config_file = $file;
    }


    public static function getConfig($type,$key)
    {
        if(empty(self::$config))
        {
            if(empty(self::$config_file))
            {
                self::$config_file = PROJ_PATH . 'Common/Config/Config.php';
            }
            self::$config = include(self::$config_file);
        }
        return self::$config[$type][$key];
    }

    public static function getConfigByType($type)
    {
        if(empty(self::$config))
        {
            if(empty(self::$config_file))
            {
                self::$config_file = PROJ_PATH . 'Common/Config/Config.php';
            }
            self::$config = include(self::$config_file);
        }
        return self::$config[$type];
    }
}
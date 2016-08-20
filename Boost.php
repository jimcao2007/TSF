<?php

class Boost
{
    protected static $config;
    /**
     * 递归包含文件
     * @param $dir
     * @return bool
     */
    public static function includeAllFiles($dir) {
        if (!is_dir($dir)) {
            return false;
        }
        //打开目录
        $handle = opendir($dir);
        while (($file = readdir($handle)) !== false) {

            if ($file == "." || $file == "..") {
                continue;
            }
            $file = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_file($file))
            {
                $pathinfo = pathinfo($file );
                //包含php文件
                if(!empty($pathinfo['extension']) && $pathinfo['extension'] == 'php')
                {
                    require_once($file);
                }
            }
            elseif (is_dir($file))
            {
                self::includeAllFiles($file);
            }
        }
    }

    public static function includeFilesArray($dir_arr)
    {
        foreach($dir_arr as $dir)
        {
            self::includeAllFiles($dir);
        }
    }

    public static function init()
    {
        Boost::includeAllFiles(CORE_PATH);
        if(defined('APP_PATH'))
        {
            Boost::includeAllFiles(APP_PATH . 'Model/');
            Boost::includeAllFiles(APP_PATH . 'Api/');
            Boost::includeAllFiles(APP_PATH . 'Controller/');
            \Logger::setLogPath(APP_PATH . 'Log/');

        }

    }

    public static function initConfig()
    {
        self::$config = require_once (APP_PATH . 'Config/Config.php');
        if(!empty($config['SINGLETON']))
        {
            foreach($config['SINGLETON'] as $k=> $c)
            {
                switch($c['class'])
                {
                    case 'Mysql':
                        Singleton::setMysql($k,$c['config']);
                    break;

                    case 'RedisCache':
                        Singleton::setRedisCache($k,$c['config']);
                        break;

                    default:
                        Singleton::setOther($k,$c['class'],$c['config']);
                    break;
                }
            }
        }
        self::includeAllFiles(dirname(__FILE__));
        if(!empty($config['INCLUDE_DIR']))
        {
            self::includeFilesArray($config['INCLUDE_DIR']);
        }

        Logger::setLogLevel($config['LOG_LEVEL']);
        Logger::setLogPath($config['LOG_PATH']);
    }

    public static function getConfig()
    {
        return self::$config;
    }

}

//加载内核所有文件
//Boost::includeAllFiles(dirname(__FILE__));

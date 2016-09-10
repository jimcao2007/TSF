<?php

class TSF
{
    /**
     * 递归包含文件
     * @param $dir
     * @return bool
     */
    public static function import($dir) {
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
                self::import($file);
            }
        }
    }

    public static function includeFilesArray($dir_arr)
    {
        foreach($dir_arr as $dir)
        {
            self::import($dir);
        }
    }

    public static function init()
    {
        if(defined('TSF_PATH'))
        {
            self::import(TSF_PATH);
        }

        $default_config_file = TSF_PATH . '/System/Config.php';
        $default_config = include($default_config_file);
        S::$config = $default_config;


        if(defined('APP_PATH'))
        {
            self::import(APP_PATH . 'Model/');
            self::import(APP_PATH . 'Common/');
            self::import(APP_PATH . 'Controller/');
            \Logger::setLogPath(APP_PATH . 'Log/');

            $app_config_file = APP_PATH . '/Config/Config.php';
            if(file_exists($app_config_file))
            {
                $app_config = include($app_config_file);
                $default_config = array_merge($default_config,$app_config);
            }
            S::$config = $default_config;
        }
    }


}

<?php

class Boost
{
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
        Boost::includeAllFiles(TS_PATH);
        if(defined('APP_PATH'))
        {
            Boost::includeAllFiles(APP_PATH . 'Model/');
            Boost::includeAllFiles(APP_PATH . 'Api/');
            Boost::includeAllFiles(APP_PATH . 'Common/');
            Boost::includeAllFiles(APP_PATH . 'Controller/');
            \Logger::setLogPath(APP_PATH . 'Log/');
        }
    }


}

//加载内核所有文件
//Boost::includeAllFiles(dirname(__FILE__));

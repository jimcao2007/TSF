<?php

class View
{
    public static $is_display = false;

    /**
     * 显示模板页面，并把data赋值给模板
     * @param $tpl_file  文件名，相对于app目录下面的template目录。
     * @param $data
     */
    public static function display($tpl_file,$data)
    {
        self::$is_display = true;
        include($tpl_file);
    }

    public static function showText($data='')
    {
        self::$is_display = true;
        echo $data;
        return true;
    }

    public static function setDisplay()
    {
        self::$is_display = true;
    }

    public static function clear()
    {
        self::$is_display = false;
    }



}
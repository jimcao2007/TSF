<?php

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
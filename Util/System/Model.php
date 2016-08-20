<?php
/**
 * Created by PhpStorm.
 * User: caoyongji
 * Date: 14-10-23
 * Time: 下午3:33
 */

class Model
{
    /**
     * @var \Mysql
     */
    protected $mysql;
    protected $table_name;

    public function __construct($table_name,$mysql)
    {
        $this->table_name = $table_name;
        $this->mysql = $mysql;
    }

    public function add($data)
    {
        return $this->mysql->insert($this->table_name,$data);
    }

    public function insertOrUpdate($data)
    {
        return $this->mysql->insertOrUpdate($this->table_name,$data);
    }

    public function getOneData($condition,$options=[])
    {
        $condition = $this->parseCondition($condition);
        $fields = isset($options['fields']) ? $options['fields'] : '';
        if(isset($options['order']))
        {
            $condition .= ' ORDER BY '.$options['order'];
        }
        return $this->mysql->getOneRows($this->table_name,$fields,$condition);
    }

    public function getPageRows($condition,$page,$page_size,$options=[])
    {
        $condition = $this->parseCondition($condition);
        $fields = isset($options['fields']) ? $options['fields'] : '';
        if(isset($options['order']))
        {
            $condition .= ' ORDER BY '.$options['order'];
        }
        return $this->mysql->getPageRows($this->table_name,$fields,$condition,$page,$page_size);
    }

    public function getRows($condition,$options=[])
    {
        $condition = $this->parseCondition($condition);
        $fields = isset($options['fields']) ? $options['fields'] : '';

        if(empty($condition))
        {
            $condition = 1;
        }
        if(isset($options['order']))
        {
            $condition .= ' ORDER BY '.$options['order'];
        }
        return $this->mysql->getRows($this->table_name,$fields,$condition);
    }

    public function updateOne($condition,$data)
    {
        $condition = $this->parseCondition($condition);
        return $this->mysql->update($this->table_name,$data,$condition);
    }

    public function remove($condition)
    {
        $condition = $this->parseCondition($condition);
        return $this->mysql->remove($this->table_name,$condition);
    }

    protected function parseCondition($condition)
    {
        $condition_str = '';
        if(empty($condition))
        {
            return ' 1 ';
        }

        if(is_array($condition))
        {
            foreach($condition as $k=>$v)
            {
                $filter_key = TString::addslashes($k);
                $filter_value = TString::addslashes($v);
                $condition_str .= " `$filter_key`='$filter_value' AND";
            }
            $condition_str = substr($condition_str,0,-3);
        }
        else
        {
            $condition_str = $condition;
        }
        return $condition_str;
    }

}
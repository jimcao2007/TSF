<?php
/**
 * Created by PhpStorm.
 * User: jim@magicare.me
 * Date: 14-11-4
 * Time: 15:02
 */

class RedisCache
{
    protected $redis;
    protected $config;
    protected $conn_status = false;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function __destruct()
    {
        if(!empty($this->redis) && false != $this->conn_status)
        {
            $this->conn_status = false;
            $this->redis->close();
            $this->redis = null;
        }
    }

    public function getRedis()
    {
        return $this->redis;
    }

    protected function connRedisInstance()
    {
        $this->redis = new \Redis();
        $conn_ret = false;
        for($i=0;$i<2;$i++)
        {
            $conn_ret = $this->redis->connect($this->config['host'], $this->config['port']);
            if($conn_ret === true)
            {
                break;
            }
            continue;
        }

        if($conn_ret === false )
        {
            \Logger::err(Dispatch::ERR_SYSTEM, "redis connect err: ".json_encode($this->config));
            return false;
        }

        if(!empty($this->config['password']))
        {
            $result = $this->redis->auth($this->config['password']);
            if(false === $result)
            {
                $err_msg = $this->redis->getLastError();
                \Logger::err(Dispatch::ERR_SYSTEM, "redis auth err_msg: ".$err_msg);
                return false;
            }
        }

        $this->conn_status = true;
        return true;
    }

    /**
     * 统一调用的方法，为了代码简洁和易维护，此处用call_user_func，代价是性能有小小的损耗。
     *
     * @param $method
     * @param $params
     * @return bool|mixed
     */
    public function query($method,$params)
    {
        if($this->conn_status === false || empty($this->redis))
        {
            $con_ret = $this->connRedisInstance();
            //连接失败
            if($con_ret === false)
            {
                return false;
            }
        }

        for($i=0;$i<3;$i++)
        {
            try
            {
                $ret = call_user_func_array(array($this->redis,$method),$params);
                if($ret === false)
                {
                    $err_msg = $this->redis->getLastError();
                    //\Logger::log('flow','err msg : '.$err_msg);

                    if(!empty($err_msg))
                    {
                        $ping_ret = $this->redis->ping();
                        //\Logger::log('flow','ping ret : '.$ping_ret);

                        if(empty($ping_ret))
                        {
                            $this->redis->close();
                            $this->redis = null;
                            $this->conn_status = false;
                            \Logger::errMsg('redis reconnect ...');
                            $con_ret = $this->connRedisInstance();
                            if($con_ret === true)
                            {
                                $ret = call_user_func_array(array($this->redis,$method),$params);
                                return $ret;
                            }
                        }
                    }
                }
                return $ret;
            }
            catch(Exception $e)
            {
                $this->redis->close();
                $this->redis = null;
                $this->conn_status = false;
                \Logger::errMsg('redis reconnect ...');
                $con_ret = $this->connRedisInstance();
                if($con_ret === false)
                {
                    return false;
                }
            }
        }
    }

    public function __call($method,$params)
    {
        return $this->query($method,$params);
    }

    //以下封装一些常用方法，当这些方法被调用时不走__call魔术方法，增加性能

    public function incr($key)
    {
        return $this->query('incr',[$key]);
    }


    public function set($key,$data,$timeout=0)
    {
        return $this->query('set',[$key,$data,$timeout]);
    }

    public function setnx($key,$data)
    {
        return $this->query('setnx',[$key,$data]);
    }
    public function get($key)
    {
        return $this->query('get',[$key]);
    }

    public function del($key)
    {
        return $this->query('del',[$key]);
    }

    public function exists($key)
    {
        return $this->query('exists',[$key]);
    }

    public function getMultiple($keys)
    {
        return $this->query('getMultiple',[$keys]);
    }

    public function hGet($key,$hash_key)
    {
        return $this->query('hGet',[$key,$hash_key]);
    }

    public function hGetAll($key)
    {
        return $this->query('hGetAll',[$key]);
    }

    public function hSet($key, $hash_key, $value)
    {
        return $this->query('hSet',[$key, $hash_key, $value]);
    }

    public function hDel($key, $hash_key)
    {
        return $this->query('hDel',[$key, $hash_key]);
    }

    public function hKeys($key)
    {
        return $this->query('hKeys',[$key]);
    }

    public function hLen($key)
    {
        return $this->query('hLen',[$key]);
    }

    public function sAdd($key, $elem)
    {
        return $this->query('sAdd',[$key,$elem]);
    }

    public function sMembers($key)
    {
        return $this->query('sMembers',[$key]);
    }

    public function sRem($key, $elem)
    {
        return $this->query('sRem',[$key, $elem]);
    }

    public function sIsMember($key, $elem)
    {
        return $this->query('sIsMember',[$key,$elem]);
    }

    public function sCard($key)
    {
        return $this->query('sCard',[$key]);
    }

    public function sPop($key)
    {
        return $this->query('sPop',[$key]);
    }

    public function lRange($key, $start, $end)
    {
        return $this->query('lRange',[$key,$start, $end]);
    }

    public function lLen($key)
    {
        return $this->query('lLen',[$key]);
    }

    public function lPush($key,$elem)
    {
        return $this->query('lPush',[$key,$elem]);
    }

    public function rPush($key, $elem)
    {
        return $this->query('rPush',[$key,$elem]);
    }

    public function lPop($key)
    {
        return $this->query('lPop',[$key]);
    }

    public function zRange($key, $start, $end, $withscores = null)
    {
        return $this->query('zRange',[$key, $start, $end, $withscores]);
    }

    public function zRem($key, $elem)
    {
        return $this->query('zRem',[$key,$elem]);
    }

    public function zCard($key)
    {
        return $this->query('zCard',[$key]);
    }

    public function zAdd($key, $score, $elem)
    {
        return $this->query('zAdd',[$key, $score, $elem]);
    }

    public function doAction($action, $key, $param_1='', $param_2='', $param_3='', $param_4='')
    {
        return $this->query('doAction',[$action, $key, $param_1 , $param_2 , $param_3 , $param_4]);
    }


}
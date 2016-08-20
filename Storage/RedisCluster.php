<?php

class RedisCluster
{
    protected $redis_objs;
    protected $config;
    protected $node_key;
    protected $node_value;
    protected $node_map;

    const HASH_MODE_VALUE = 20480;

    public function __construct($config)
    {
        $redis_hosts = $config['hosts'];
        $password = $config['password'];

        $this->config['password'] = $password;
        $this->config['virtual_node'] = $config['virtual_node'];

        foreach($redis_hosts as $host)
        {
            $ip = $host['ip'];
            $port = $host['port'];
            $host_key = $ip.":".$port;
            $this->config['hosts'][$host_key] = 1;
        }

        return true;
    }

    //会失败的操作放在初始化函数中，初始化必须全部成功
    public function init()
    {
        foreach($this->config['hosts'] as $host_key=>$value)
        {
            $result = $this->connRedisServer($host_key);
            if(false === $result)
            {
                echo "Failed to Connect to $host_key\n";
                return false;
            }
        }

        return $this->generateVirtualNode();
    }

    //将所有缓存刷到磁盘
    public function save()
    {
        foreach($this->redis_objs as $redis)
        {
            $redis->save();
        }

        return true;
    }


    public function debug()
    {
        echo "config:\n".json_encode($this->config)."\n";
        //echo "node_map:\n".json_encode($this->node_map)."\n";
        //echo "node_key:\n".json_encode($this->node_key)."\n";
        echo "node_value:\n".json_encode($this->node_value)."\n";
    }

    protected function debugTrace()
    {
        $trace = debug_backtrace();
        foreach($trace as $value)
        {
            echo $value['file'].":".$value['line'].':'.$value['class'].':'.$value['function']."\n";
        }

        return true;
    }

    //根据配置中节点和虚拟节点数量，计算出每个虚拟节点在环上的值；每个虚拟节点映射的实际节点。
    protected function generateVirtualNode()
    {
        $redis_hosts = $this->config['hosts'];
        $virtual_node = $this->config['virtual_node'];

        $virtual_nodes = [];
        foreach($redis_hosts as $host_key=>$value)
        {
            for($i = 0; $i < $virtual_node; $i++)
            {
                $virtual_node_name = $host_key.':'.$i;
                $virtual_nodes[] = $virtual_node_name;
                $this->node_map[$virtual_node_name] = $host_key;
            }
        }

        //shuffle($virtual_nodes);

        $virtual_node_values = [];
        foreach($virtual_nodes as $virtual_node_name)
        {
            $crc_value = crc32(hash('md5',$virtual_node_name))%self::HASH_MODE_VALUE;
            $virtual_node_values[$virtual_node_name] = $crc_value;
        }

        asort($virtual_node_values);
        $this->node_key = array_keys($virtual_node_values);
        $this->node_value = array_values($virtual_node_values);

        return true;
    }

    //计算字符串的hash值
    protected function getHashCode($key)
    {
        if(!is_string($key))
        {
            return false;
        }

        //测试表明构造hash code的耗时可以忽略，网络开销巨大，100k数据，本机6s，跨网络208s
        $hash_code = crc32(hash('md5', $key, true))%self::HASH_MODE_VALUE;

        return $hash_code;
    }

    //测试redis实例连接是否还存在
    protected function connTest($redis_handle)
    {
        $key = md5('scofild$%&*HJBwqd');
        $result = $redis_handle->set($key, 'test', 1);
        if($result === false)
        {
            return false;
        }

        return true;
    }

    //连接redis服务器，并将其添加到连接列表
    protected function connRedisServer($host_key)
    {
        $redis = new \Redis();
        if(empty($redis))
        {
            return false;
        }

        $host = explode(':', $host_key);
        $conn = $redis->connect($host[0], $host[1]);
        if(empty($conn))
        {
            return false;
        }

        $password = $this->config['password'];
        $auth_result = $redis->auth($password);
        if(empty($auth_result))
        {
            echo "Failed to conn $host_key\n";
            return false;
        }

        $this->redis_objs[$host_key] = $redis;

        return true;
    }

    //加载服务器列表配置，更新现有配置；
    public function loadConfig($config)
    {
        $host_keys = [];
        foreach($config['hosts'] as $host)
        {
            $ip = $host['ip'];
            $port = $host['port'];
            $host_key = $ip.':'.$port;
            $host_keys[$host_key] = 1;
        }

        //删除不在配置中的当前使用server
        foreach($this->config['hosts'] as $host_key=>$value)
        {
            if(empty($host_keys[$host_key]))
            {
                $this->kickOutServer($host_key);
            }
        }

        //添加配置中未在使用的server
        foreach($host_keys as $host_key=>$value)
        {
            if(empty($this->config['hosts'][$host_key]))
            {
                $result = $this->addNewServer($host_key);
                if(false === $result)
                {
                    echo "failed to add server: $host_key\n";
                    continue;
                }
            }
        }

        return true;
    }

    //设置简单的key-value对
    public function set($key, $value, $timeout=0)
    {
        $redis_handle = $this->getRedisHandle($key);
        if(false === $redis_handle)
        {
            echo "Failed to getRedisHandle\n";
            return false;
        }

        try{
            $result = $redis_handle->set($key, $value, $timeout);
            if(false === $result)
            {
                echo "Failed to send set cmd\n";
                return false;

                //链接已断开，重新连接后发送
                unset($redis_handle);
                $host_key = $this->getHostKey($key);
                if(empty($host_key))
                {
                    return false;
                }
                $reconn_result = $this->connRedisServer($host_key);
                if(false === $reconn_result)
                {
                    //server could not be connected, kick it out
                    $this->kickOutServer($host_key);
                }

                //重新发送此缓存请求
                return $this->set($key, $value, $timeout);
            }
        }catch (RedisException $e)
        {
            $msg = $e->getMessage();
            echo "RedisException $msg\n";

            //链接已断开，重新连接后发送
            unset($redis_handle);
            $host_key = $this->getHostKey($key);
            if(empty($host_key))
            {
                return false;
            }
            $reconn_result = $this->connRedisServer($host_key);
            if(false === $reconn_result)
            {
                //server could not be connected, kick it out
                $this->kickOutServer($host_key);
            }
            $this->debug();

            //重新发送此缓存请求
            return $this->set($key, $value, $timeout);
        }

        return $result;
    }

    //剔除指定的服务器，并重新生成虚拟节点
    protected function kickOutServer($host_key)
    {
        if(!empty($this->config['hosts'][$host_key]))
        {
            unset($this->config['hosts'][$host_key]);
        }

        echo "kick out server: $host_key\n";

        return $this->generateVirtualNode();
    }

    //添加指定的服务器，并重新生成虚拟节点
    protected function addNewServer($host_key)
    {
        $result = $this->connRedisServer($host_key);
        if(false === $result)
        {
            return false;
        }

        $this->config['hosts'][$host_key] = 1;

        echo "add new server: $host_key\n";

        return $this->generateVirtualNode();
    }

    //根据字符串key找到对应的真实服务器标志
    protected function getHostKey($redis_key)
    {
        if(empty($this->node_value))
        {
            return false;
        }

        $hash_code = $this->getHashCode($redis_key);
        if(false === $hash_code)
        {
            return false;
        }

        $node_index = $this->find($hash_code, 0, count($this->node_value) - 1);
        if(empty($this->node_key[$node_index]) || empty($this->node_value[$node_index]))
        {
            return false;
        }

        $node_key = $this->node_key[$node_index];
        $node_value = $this->node_value[$node_index];

        if(empty($this->node_map[$node_key]))
        {
            return false;
        }

        $real_node_identify = $this->node_map[$node_key];

        if($node_index == 0)
        {
            if($hash_code > $node_value && $hash_code <= $this->node_value[count($this->node_value) - 1])
            {
                echo "Failed: $hash_code, $node_value";
            }
        }
        else
        {
            if($hash_code > $node_value && $hash_code <= $this->node_value[$node_index - 1])
            {
                echo "Failed: $hash_code, $node_value";
            }
        }

        return $real_node_identify;
    }

    //根据key找到对应的redis服务器
    public function getRedisHandle($redis_key)
    {

        $real_node_identify = $this->getHostKey($redis_key);
        if(false === $real_node_identify)
        {
            return false;
        }

        if(empty($this->redis_objs[$real_node_identify]))
        {
            return false;
        }

        return $this->redis_objs[$real_node_identify];
    }

    //二分查找法，查找虚拟节点的Index
    protected function find($value, $begin, $end)
    {
        if($value <= $this->node_value[$begin] || $value > $this->node_value[$end])
        {
            return $begin;
        }

        $middle = floor(($begin + $end)/2);
        if($this->node_value[$middle] < $value)
        {
            if($this->node_value[$middle + 1] > $value)
            {
                return $middle + 1;
            }
            return $this->find($value, $middle + 1, $end);
        }
        elseif($this->node_value[$middle] > $value)
        {
            if($this->node_value[$middle - 1] < $value)
            {
                return $middle;
            }
            return $this->find($value, $begin, $middle - 1);
        }
        else
        {
            return $middle;
        }
    }

}
<?php

class Swserver
{
    public $config;
    public $sw_server;
    public $local_ip;

    public function __construct($config)
    {
        $host = $config['host'];
        $port = $config['port'];
        if(!empty($config['unix_stream']))
        {
            $this->sw_server = new \swoole_server($host,$port,SWOOLE_PROCESS,SWOOLE_UNIX_STREAM);
        }
        else
        {
            $this->sw_server = new \swoole_server($host,$port);
        }

        if(empty($config['swoole']['log_file']))
        {
            $config['swoole']['log_file'] = $config['log_path'].'swoole.log';
        }
        $this->local_ip = \swoole_get_local_ip();
        $this->sw_server->set($config['swoole']);
        $this->config = $config;
    }

    public function start()
    {
        $pid_file_name= $this->config['pid_file'];
        if(file_exists($pid_file_name))
        {
            die("server has start\n");
        }
        echo "server start\n";
        $this->sw_server->on('Start', array($this,'onStart'));
        $this->sw_server->on('Connect', array($this,'onConnect'));
        $this->sw_server->on('Receive', array($this,'onReceive'));
        $this->sw_server->on('Close', array($this,'onClose'));
        $this->sw_server->on('Shutdown', array($this,'onShutdown'));
        $this->sw_server->on('Timer', array($this,'onTimer'));
        $this->sw_server->on('WorkerStart', array($this,'onWorkerStart'));
        $this->sw_server->on('WorkerStop', array($this,'onWorkerStop'));
        $this->sw_server->on('Task', array($this,'onTask'));
        $this->sw_server->on('Finish', array($this,'onFinish'));
        $this->sw_server->on('MasterConnect', array($this,'onMasterConnect'));
        $this->sw_server->on('MasterClose', array($this,'onMasterClose'));
        $this->sw_server->on('pipeMessage', array($this,'onPipeMessage'));
        $this->sw_server->start();
    }

    public function stop()
    {
        $pid_file_name= $this->config['pid_file'];
        if(!file_exists($pid_file_name))
        {
            echo("server has not started\n");
            return;
        }
        $pid = file_get_contents($pid_file_name);
        echo "now kill ".$pid;
        /*
        $pid_file = file_get_contents($pid_file_name);
        $pids = explode("\n",$pid_file);
        foreach($pids as $pid)
        {
            $pid = intval($pid);
            if(empty($pid))
            {
                continue;
            }
            posix_kill(intval($pid),9);
        }
        */
        exec("kill -15 $pid");
        echo "server stop\n";

    }

    public function run()
    {
        global $argv;
        switch($argv[1])
        {
            case 'start':
                return $this->start();
            case 'stop':
                return $this->stop();
            case 'restart':
                $this->stop();
                sleep(2);
                return $this->start();
            default:
                die("please input start | stop | restart\n");
        }
    }

    public function onStart($serv)
    {
        $pid_file_name= $this->config['pid_file'];

        //added by scofild
        if(file_exists($pid_file_name))
        {
            echo("server has not been stopped\n");
            echo("kill it now\n");
            $pid = file_get_contents($pid_file_name);
            //会递归的删除master进程下的所有子孙进程
            \Sdk\Common::stopAllProcess($pid);
        }
        //end

        file_put_contents($pid_file_name,$serv->master_pid."\n",FILE_APPEND);
        //file_put_contents(BOOST_PATH.'pid',$serv->manager_pid."\n",FILE_APPEND);
    }

    public function onConnect($serv, $fd, $from_id)
    {
    }

    public function onReceive($serv, $fd, $from_id, $data)
    {

    }



    public function onClose($serv, $fd, $from_id)
    {
        Logger::flush();
    }

    public function onShutdown($serv)
    {
        $pid_file_name= $this->config['pid_file'];
        @unlink($pid_file_name);
        Logger::flush();
    }

    public function onTimer($serv, $interval)
    {
    }

    public function onWorkerStart($serv, $worker_id)
    {
        Logger::setLogLevel($this->config['log_level']);
        Logger::setLogPath($this->config['log_path']);
        //file_put_contents(BOOST_PATH.'pid',posix_getpid()."\n",FILE_APPEND);
    }

    public function onWorkerStop($serv, $worker_id)
    {
        Logger::flush();
    }

    public function onTask($serv, $task_id, $from_id, $data)
    {

    }

    public function onPipeMessage($serv, $src_worker_id, $data)
    {

    }

    public function onFinish($serv, $data)
    {

    }

    public function onMasterConnect($serv, $fd, $from_id)
    {

    }

    public function onMasterClose($serv,$fd,$from_id)
    {

    }
}

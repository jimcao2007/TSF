<?php
class LibevServer
{
    protected $config;
    protected $child_pids;
    protected $proc_index=0;
    protected $pid;

    public function __construct($config)
    {
        $this->config = $config;
        Logger::setLogPath($this->config['log_path']);
    }

    public function start()
    {
        $pid_file_name= $this->config['pid_file'];
        if(file_exists($pid_file_name))
        {
            die("server has start\n");
        }
        echo "server start\n";


        if($this->config['daemon'])
        {
            $pid = pcntl_fork();
            if ($pid == -1)
            {
                die("fork(1) failed!\n");
            }

            if($pid > 0)
            {
                exit(0);
            }

            posix_setsid();

            $pid = pcntl_fork();
            if ($pid == -1)
            {
                die("fork(2) failed!\n");
            }
            elseif ($pid > 0)
            {
                //父进程退出, 剩下子进程成为最终的独立进程
                exit(0);
            }
        }

        for($i=1;$i<=$this->config['work_num'];$i++)
        {
            $pid = pcntl_fork();
            if($pid > 0)
            {
                $this->child_pids[$pid] = $pid;
            }
            else
            {
                $this->child_pids = null;
                $this->pid = posix_getpid();
                $this->proc_index = $i;
                $this->onWorkerStart();
                file_put_contents($this->config['pid_file'],$this->pid."\n",FILE_APPEND);
                Libev::loop();
                return;
            }
        }
        pcntl_signal(SIGTERM, array($this,'signalHandler'));
        pcntl_signal(SIGHUP, array($this,'signalHandler'));

        $this->pid = posix_getpid();
        file_put_contents($this->config['pid_file'],$this->pid."\n",FILE_APPEND);

        $this->onStart();
        Libev::loop();
    }

    public function signalHandler($sig)
    {
        switch ($sig) {
            case SIGTERM:
                $this->onShutdown();

            break;

            case SIGHUP:
                $this->onShutdown();
            break;
        }
    }


    public function stop()
    {
        $pid_file_name= $this->config['pid_file'];
        if(!file_exists($pid_file_name))
        {
            echo("server has not started\n");
            return;
        }
        $pid_content = file_get_contents($pid_file_name);
        $pids = explode("\n",$pid_content);

        foreach($pids as $pid)
        {
            if(empty($pid))
            {
                continue;
            }
            exec("kill -9 $pid");
        }

        echo "server stop\n";
        unlink($pid_file_name);
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

    public function onStart()
    {

    }

    public function onWorkerStart()
    {

    }

    public function onShutdown()
    {
        unlink($this->config['pid_file']);
    }



}
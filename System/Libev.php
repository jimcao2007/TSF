<?php
class Libev
{
    protected static $server;
    protected static $client;
    protected static $timer;
    protected static $eventid=0;

    public static function getAutoId()
    {
        self::$eventid++;
        return self::$eventid;
    }

    public static function closeClient($eventid)
    {
        if(empty(self::$client[$eventid]))
        {
            return;
        }
        $ev = self::$client[$eventid]['event'];
        $fd = self::$client[$eventid]['fd'];
        if($ev)
        {
            $ev->stop();
        }
        if($fd)
        {
            if(self::$client[$eventid]['is_socket'])
            {
                socket_close($fd);
            }
            else
            {
                fclose($fd);
            }
        }
        unset(self::$client[$eventid]);
    }

    public static function delClient($eventid)
    {
        $ev = self::$client[$eventid]['event'];
        $fd = self::$client[$eventid]['fd'];
        if($ev)
        {
            $ev->stop();
        }
        unset(self::$client[$eventid]);
    }

    public static function addClient($conn_fd,$cb_onrecv,$cb_onclose=null,$cb_onconn=null,$eventid=false,$is_socket=true)
    {
        if(empty($eventid))
        {
            $eventid = self::getAutoId();
        }

        $conn_ev = new \EvIo($conn_fd, \Ev::READ, function ($ev) use ($conn_fd,$cb_onrecv,$cb_onclose,$cb_onconn,$eventid,$is_socket){
            $data = '';

            if($is_socket)
            {
                while ($read = socket_read($conn_fd,1024))
                {
                    $data .= $read;
                }
            }
            else
            {
                while ($read = fread($conn_fd,1024))
                {
                    $data .= $read;
                }
            }

            if(empty($data))
            {
                if($cb_onclose)
                {
                    call_user_func_array($cb_onclose,array($eventid));
                }

                self::closeClient($eventid);
                return;
            }

            if($cb_onrecv)
            {
                call_user_func_array($cb_onrecv,array($eventid,$data));
            }


        });

        if($cb_onconn)
        {
            call_user_func_array($cb_onconn,array($eventid));
        }

        self::$client[$eventid]['event'] = $conn_ev;
        self::$client[$eventid]['fd'] = $conn_fd;
        self::$client[$eventid]['is_socket'] = $is_socket;
        //\Ev::run();
        return $eventid;
    }

    public static function addServer($fd,$cb_accept,$eventid=false)
    {
        if(empty($eventid))
        {
            $eventid = self::getAutoId();
        }

        $server_ev = new \EvIo($fd, \Ev::READ, function ($server_ev) use ($fd,$cb_accept){
            call_user_func_array($cb_accept,array($fd));
        });
        self::$server[$eventid]['event'] = $server_ev;
        self::$server[$eventid]['fd'] = $fd;
        //\Ev::run();
        return $eventid;
    }

    public static  function sendClient($eventid,$data)
    {
        if(!empty(self::$client[$eventid]['fd']))
        {
            if(self::$client[$eventid]['is_socket'])
            {
                socket_write(self::$client[$eventid]['fd'],$data);
            }
            else
            {
                fwrite(self::$client[$eventid]['fd'],$data);
            }
        }
    }

    public static function addTimer($append,$timeout,$callback,$eventid=false)
    {
        if(empty($eventid))
        {
            $eventid = self::getAutoId();
        }
        $timer = new \EvTimer($append, $timeout, function ($w)  use ($callback,$eventid,$timeout){
            if($timeout == 0){
                unset(self::$timer[$eventid]);
            }
            call_user_func_array($callback,array($eventid));
        });
        self::$timer[$eventid] = $timer;
        //\Ev::run();
        return $eventid;
    }

    public static function stopTimer($eventid)
    {
        if(isset(self::$timer[$eventid]))
        {
            self::$timer[$eventid]->stop();
            unset(self::$timer[$eventid]);
            return true;
        }
        return false;
    }

    public static function delTimer($eventid)
    {
        if(isset(self::$timer[$eventid]))
        {
            self::$timer[$eventid]->stop();
            unset(self::$timer[$eventid]);
        }
    }

    public static function getFd($eventid)
    {
        if(isset(self::$client[$eventid]['fd']))
        {
            return self::$client[$eventid]['fd'];
        }
        return false;
    }

    public static function loop()
    {
        \Ev::run();
    }



}
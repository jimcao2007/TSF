<?php

class TcpCli
{
    protected $host;
    protected $port;
    protected $sock;

    public function __construct($host,$port)
    {
        $this->host = $host;
        $this->port = $port;
        $this->sock = stream_socket_client("tcp://$host:$port", $errno, $errstr, 1.5);
        if (!$this->sock) {
            Logger::err(1001,"connect err");
        }
    }

    public function send($stream)
    {
        $resp = '';
        if(!$this->sock)
        {
            return false;
        }
        fwrite($this->sock, $stream);
        while (!feof($this->sock)) {
            $resp .= fread($this->sock, 1024);
        }
        fclose($this->sock);
        return $resp;
    }
}
<?php

class SdkHelper
{
    const RUN_MOD_DEBUG = 1;
    const RUN_MOD_CS = 2;

    public static function init()
    {

    }

    public static function sendByJson($data,$version=1,$host,$port)
    {
        $pkg_stream = Soaproto::packJson($data,$version);
        $timeout_conn = 1.5;    //连接超时1.5秒
        $resp_timeout = 5;      //响应超时
        $sock = stream_socket_client("tcp://$host:$port", $errno, $errstr, $timeout_conn);
        if($sock === false)
        {
            \Logger::backtrace('client conn err');
            return false;
        }
        stream_set_timeout($sock,$resp_timeout);
        fwrite($sock,$pkg_stream);
        $resp_stream = '';
        while(1)
        {
            $read_ret = fread($sock,1024);
            if(empty($read_ret))
            {
                break;
            }
            $resp_stream .= $read_ret;
        }

        if(Soaproto::checkStream($resp_stream) == false)
        {
            $info = stream_get_meta_data($sock);
            if ($info['timed_out']) {
                Logger::err(Dispatch::ERR_SYSTEM,'sock read timeout');
            }
            @fclose($sock);
            \Logger::backtrace('checkStream err');
            return false;
        }
        @fclose($sock);
        $resp_data =  Soaproto::unpackJson($resp_stream);
        return $resp_data['body'];
    }


    /**
     * @param $con string controller
     * @param $act string actor
     * @param $data array params
     * @param $host string
     * @param $port int
     * @param int $delay
     * @return bool
     */
    public static function send($con,$act,$data,$host,$port,$delay=0)
    {
        $req_data = array(
            'con'=>$con,
            'act'=>$act,
            'data'=>$data,

        );

        return self::sendByJson($req_data,1,$host,$port);

        /*
        if(ConfigMan::getConfig('Other','sdk_mod')==self::RUN_MOD_CS)
        {
            return self::sendByJson($req_data,1,$host,$port);
        }
        else
        {

            $send_stream = Soaproto::packJson($req_data,1);
            $resp_stream = Dispatch::run($send_stream);
            $resp_data = Soaproto::unpackJson($resp_stream);
            return $resp_data['body'];
        }
        */
    }


    public static function makeSecret($data,$secret)
    {
        $timestamp = time();
        $nonce = rand(11111111,99999999);
        $secret_data['data'] = $data;
        $secret_data['timestamp'] = $timestamp;
        $secret_data['nonce'] = $nonce;

        $secret_str = $secret.$timestamp.$nonce.serialize($data);
        $sign = md5($secret_str);
        $secret_data['sign'] = $sign;
        return $secret_data;
    }

    public static function checkSecret($secret_data,$secret,$timeout=3600)
    {
        if(    empty($secret_data['data'])
            || empty($secret_data['sign'])
            || empty($secret_data['timestamp'])
            || empty($secret_data['nonce']))
        {
            \Logger::errMsg("secret data empty");
            return false;
        }

        if(time()-$secret_data['timestamp'] > $timeout)
        {
            \Logger::errMsg("secret data timeout ".json_encode($secret_data));
            return false;
        }

        $secret_str = $secret.$secret_data['timestamp'].$secret_data['nonce'].serialize($secret_data['data']);
        $sign = md5($secret_str);
        if($sign != $secret_data['sign'])
        {
            \Logger::errMsg("secret data check sign err ".json_encode($secret_data));
            return false;
        }

        return $secret_data['data'];

    }



}
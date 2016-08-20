<?php

namespace Network;
class Curl
{
    public static function send($url,$params=[],$is_post=false)
    {
        $param_str='';
        if(is_array($params))
        {
            foreach ($params as $k=>$v)
            {
                $param_str.="$k=".urlencode($v).'&';
            }
            $param_str=substr($param_str,0,-1);
        }
        else
        {
            $param_str = $params;
        }

        $ch = curl_init();
        if($is_post)
        {
            curl_setopt($ch, CURLOPT_POST, 1);
            if($param_str)
            {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $param_str);
            }
        }
        else
        {
            if($param_str)
            {
                if(strpos($url,'?')=== false)
                {
                    $url .= '?'.$param_str;
                }
                else
                {
                    $url .= '&'.$param_str;
                }
            }
        }

        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL,$url);
        //curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //如果需要将结果直接返回到变量里，那加上这句。
        $resp = curl_exec($ch);
        //\Logger::log('flow',$resp);
        if($resp === false)
        {
            \Logger::log('error','curl send err | '.$url."\t".json_encode($params));
            return false;
        }

        $info = curl_getinfo($ch);
        if($info['http_code'] != 200)
        {
            \Logger::log('error','curl http_code err '.$info['http_code'].' | '.$url."\t".json_encode($params)."\t".$resp);
            return false;
        }

        if(empty($resp))
        {
            return false;
        }
        return $resp;
    }
}
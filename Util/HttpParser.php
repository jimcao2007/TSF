<?php
namespace Util;
class HttpParser
{
    const UA_WEIXIN = 'weixin';
    const UA_ANDROID = 'android';
    const UA_IPHONE = 'iphone';
    public static function getHead($data)
    {
        $head_pos  = strpos($data,"\r\n\r\n");
        $head_content = substr($data,0,$head_pos);
        $line_content = explode("\r\n",$head_content);
        $head = array();
        //var_dump($line_content);
        foreach($line_content as $i=>$l)
        {
            if(empty($l))
            {
                continue;
            }
            if($i==0)
            {
                $tmp = explode(' ',$l);
                $head['_head']['method'] = $tmp[0];
                $head['_head']['content'] = $l;
                continue;
            }
            $tmp = explode(': ',$l);
            $head[$tmp[0]] = $tmp[1];
        }
        return $head;
    }

    public static function replaceHead($head,&$data)
    {
        $old_head_pos = strpos($data,"\r\n\r\n");
        $nohead = substr($data,$old_head_pos+2);
        $new_head_content = '';
        foreach($head as $k=>$v)
        {
            if($k=='_head')
            {
                $new_head_content .=  $v['content'];
                continue;
            }
            $new_head_content .= $k.': '.$v."\r\n";
        }
        return $new_head_content.$nohead;
    }

    public static function getHost($data)
    {
        $first_line_pos = strpos($data,"\r\n");

        $first_line = substr($data,0,$first_line_pos);
        //echo $first_line."\n";
        $headword_pos = strpos($data," ");
        $headword = substr($data,0,$headword_pos);

        $is_http = true;
        switch(strtoupper($headword))
        {
            case 'GET':
            case 'POST':
                $is_http = true;
                break;

            case 'CONNECT':
                $is_http = false;
                break;
        }

        if($is_http)
        {
            $d = explode(' ',$first_line);
            $url = $d[1];
            $url = substr($url,strlen('http://'));
            $delimiter_pos = strpos($url,'/');
            $host = substr($url,0,$delimiter_pos);
            if(strpos($host,':'))
            {
                $temp = explode(':',$host);
                $host = $temp[0];
                $port = $temp[1];
            }
            else
            {
                $port = 80;
            }
        }
        else
        {
            $d = explode(' ',$first_line);
            $url = $d[1];
            $temp = explode(':',$url);
            $host = $temp[0];
            $port = $temp[1];
        }


        return array(
            'host'=>$host,
            'port'=>$port
        );
    }

    public static  function getFirstLine($data)
    {
        $headflag = strpos($data,"\r\n");

        $head = substr($data,0,$headflag);
        return $head;
    }

    public static function getToken()
    {
        return self::getHttpHead('Token');
    }

    public static function getHttpHead($name)
    {
        $key = 'HTTP_'.strtoupper($name);
        if(isset($_SERVER[$key]))
        {
            return $_SERVER[$key];
        }
        return false;
    }

    public static function getUserAgent()
    {
        if(!isset($_SERVER['HTTP_USER_AGENT']))
        {
            return false;
        }
        return strtolower($_SERVER['HTTP_USER_AGENT']);
    }

    public static function checkUserAgent($type)
    {
        $ua = self::getUserAgent();
        if(empty($ua))
        {
            return false;
        }

        switch($type)
        {
            case self::UA_WEIXIN:

                if(strpos($ua,'micromessenger')===false)
                {
                    return false;
                }
                return true;
            break;

            case self::UA_ANDROID:
                if(strpos($ua,'android')===false)
                {
                    return false;
                }
                return true;
                break;
            case self::UA_IPHONE:
                if(strpos($ua,'iphone')===false)
                {
                    return false;
                }
                return true;
                break;

            default:
                return false;

        }

    }
}
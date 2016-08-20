<?php
/**
 * Created by PhpStorm.
 * User: caoyongji
 * Date: 14-10-23
 * Time: 下午3:33
 */

class Soaproto
{
    const PROTO_TYPE_JSON = 1;
    const PROTO_TYPE_PROTOBUF = 2;

    public static function getHead($stream)
    {
        //包的头部有12字节
        if(strlen($stream) < 12)
        {
            return false;
        }

        $head_stream = substr($stream,0,12);

        $head = unpack('Nlen/Ntype/Nversion',$head_stream);
        return $head;
    }

    public static function checkStream($stream)
    {
        $head = self::getHead($stream);
        if($head == false)
        {
            return false;
        }

        if(strlen($stream) != $head['len'])
        {
            return false;
        }

        return true;

    }



    /**
     * 处理黏包
     * @param string $stream_all
     *
     */
    public static function dealStickyPackage($stream_all,$delimiter)
    {

        $offset = 0;
        $start_pos = 0;
        $pkg_arr = [];
        while(1)
        {
            if($offset > strlen($stream_all))
            {
                break;
            }

            $eof = strpos($stream_all,$delimiter,$offset);

            if($eof === false)
            {
                break;
            }
            $offset = $eof+strlen($delimiter);
            $stream = substr($stream_all,$start_pos,$offset-$start_pos);
            if(self::checkStream($stream)==false)
            {
                Logger::errMsg('checkStream err '.$stream);
                continue;
            }
            else
            {
                $start_pos = $offset;
                $pkg_arr[] = $stream;
            }
        }
        return $pkg_arr;
    }


    public static function dealRawSticky($stream_all,$delimiter)
    {
        $offset = 0;
        $start_pos = 0;
        $pkg_arr = [];
        while(1)
        {
            if($offset > strlen($stream_all))
            {
                break;
            }

            $eof = strpos($stream_all,$delimiter,$offset);

            if($eof === false)
            {
                break;
            }
            $offset = $eof+strlen($delimiter);
            $stream = substr($stream_all,$start_pos,$offset-$start_pos);

            $start_pos = $offset;
            $pkg_arr[] = $stream;

        }
        return $pkg_arr;
    }




    /**
     * 对json格式的协议进行解包
     * @param $stream
     * @return array
     */
    public static function unpackJson($stream)
    {
        $head = self::getHead($stream);
        $body_stream = substr($stream,12,-4);
        $body = json_decode($body_stream,true);
        return [
            'head'=>$head,
            'body'=>$body
        ];
    }

    public static function unpackJsonBody($stream)
    {
        $unpack = self::unpackJson($stream);
        return $unpack['body'];
    }

    /**
     * 对json数据进行打包
     * @param $data 数据
     * @param $version 协议版本
     * @return string 打包后的二进制
     */
    public static function packJson($data,$version=1)
    {
        $body_stream = json_encode($data);
        $len = strlen($body_stream)+16;
        return pack('N',$len).pack('N',self::PROTO_TYPE_JSON).pack('N',$version).$body_stream."\r\n\r\n";
    }



    /**
     * 对json格式的协议进行解包
     * @param $stream
     * @return array
     */
    public static function unpackSerial($stream)
    {
        $head = self::getHead($stream);
        $body_stream = substr($stream,12,-4);
        $body = unserialize($body_stream);
        return [
            'head'=>$head,
            'body'=>$body
        ];
    }

    public static function unpackSerialBody($stream)
    {
        $unpack = self::unpackSerial($stream);
        return $unpack['body'];
    }

    /**
     * 对json数据进行打包
     * @param $data 数据
     * @param $version 协议版本
     * @return string 打包后的二进制
     */
    public static function packSerail($data,$version=1)
    {
        $body_stream = serialize($data);
        $len = strlen($body_stream)+16;
        return pack('N',$len).pack('N',self::PROTO_TYPE_JSON).pack('N',$version).$body_stream."\r\n\r\n";
    }
}
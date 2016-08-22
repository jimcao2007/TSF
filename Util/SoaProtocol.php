<?php

class SoaProtocol
{
    const STAT_DELIMITER = 0x3A;
    const END_DELIMITER = "\r\n\r\n";


    /**
     * @param $body_stream
     * @param string $end_limiter
     * @param string $secret
     * @return string first_char+len+base64_encode(timestamp+nonce+sign+body_stream)+end_limiter
     */
    public static function packStream($body_stream,$secret='')
    {
        $timestamp = time();
        $nonce = rand(10000000,99999999);
        $sign = md5($body_stream.$timestamp.$nonce.$secret);
        $sign_base64_stream =  base64_encode(pack('N',$timestamp).pack('N',$nonce).$sign.$body_stream);

        $len = 1+4+strlen($sign_base64_stream)+strlen(self::END_DELIMITER);

        $stream_all =
            pack('C',self::STAT_DELIMITER).
            pack('N',$len).
            $sign_base64_stream.
            self::END_DELIMITER
        ;
        return $stream_all;
    }


    public static function unpackStream($stream_all)
    {
        $first_char_arr = unpack('C',substr($stream_all,0,1));
        $first_char = $first_char_arr[1];
        $len = unpack('N',substr($stream_all,1,4));
        $end_limiter_len = strlen(self::END_DELIMITER);
        $sign_base64_stream = substr($stream_all,1+4,0-$end_limiter_len);
        $sign_stream = base64_decode($sign_base64_stream);
        $timestamp_arr = unpack('N',substr($sign_stream,0,4));
        $timestamp = $timestamp_arr[1];
        $nonce_arr = unpack('N',substr($sign_stream,4,4));
        $nonce = $nonce_arr[1];
        $sign = substr($sign_stream,8,32);
        $body_stream = substr($sign_stream,40);

        $ret_arr = [
            'first_char' => $first_char,
            'len'=>$len,
            'timestamp'=>$timestamp,
            'nonce' => $nonce,
            'sign' => $sign,
            'body_stream' => $body_stream,
        ];
        return $ret_arr;
    }


    public static function checkStream($stream_all,$unpack_arr,$secret='')
    {
        if($unpack_arr['first_char'] != self::STAT_DELIMITER)
        {
            return false;
        }

        if($unpack_arr['len'] != strlen($stream_all))
        {
            return false;
        }

        $check_sign =  md5($unpack_arr['body_stream'].$unpack_arr['timestamp'].$unpack_arr['nonce'].$secret);
        if($check_sign != $unpack_arr['sign'])
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
    public static function dealStickyPackage($stream_all,$secret)
    {
        $delimiter = self::END_DELIMITER;
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
            $unpack_arr = self::unpackStream($stream);
            if(self::checkStream($stream,$unpack_arr,$secret)==false)
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



    public static function dealRawSticky($stream_all)
    {
        $delimiter = self::END_DELIMITER;
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


    public static function packJson($data,$secret='')
    {
        return self::packStream(json_encode($data),$secret);
    }

    public static function unpackJson($stream,$secret='')
    {
        $unpack_arr = self::unpackStream($stream);
        if(self::checkStream($stream,$unpack_arr,$secret))
        {
            return false;
        }
        $body_stream = $unpack_arr['body_stream'];
        return json_decode($body_stream,true);
    }

}
<?php

namespace Util;
class CryptDes
{
    public static function encrypt($input,$secret_key,$iv='')
    {
        $size = mcrypt_get_block_size(MCRYPT_DES,MCRYPT_MODE_CBC);
        $input = self::pkcs5_pad($input, $size);
        $key = str_pad($secret_key,8,'0');
        $td = mcrypt_module_open(MCRYPT_DES, '', MCRYPT_MODE_CBC, '');
        if(empty($iv))
        {
            $iv = md5($secret_key);
        }
        @mcrypt_generic_init($td, $key, $iv);
        $data = mcrypt_generic($td, $input);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $data = base64_encode($data);
        return $data;
    }

    public static function decrypt($encrypted,$secret_key,$iv='')
    {
        $encrypted = base64_decode($encrypted);
        $key = str_pad($secret_key,8,'0');
        $td = mcrypt_module_open(MCRYPT_DES,'',MCRYPT_MODE_CBC,'');
        $ks = mcrypt_enc_get_key_size($td);
        if(empty($iv))
        {
            $iv = md5($secret_key);
        }
        @mcrypt_generic_init($td, $key, $iv);
        $decrypted = mdecrypt_generic($td, $encrypted);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $y = self::pkcs5_unpad($decrypted);
        return $y;
    }

    protected static function pkcs5_pad ($text, $blocksize)
    {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    protected static function pkcs5_unpad($text)
    {
        $pad = ord($text{strlen($text)-1});
        if ($pad > strlen($text)) {
            return false;
        }
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad){
            return false;
        }
        return substr($text, 0, -1 * $pad);
    }

    protected static function PaddingPKCS7($data)
    {
        $block_size = mcrypt_get_block_size(MCRYPT_DES, MCRYPT_MODE_CBC);
        $padding_char = $block_size - (strlen($data) % $block_size);
        $data .= str_repeat(chr($padding_char),$padding_char);
        return $data;
    }
}
<?php
namespace Util;
class LibString
{
    /**
     * 检查是否是合法email地址
     *
     * @param string $str
     *
     * @return boolean
     */
    public static function isEmail($str)
    {
        $ret = filter_var( $str, FILTER_VALIDATE_EMAIL );
        if ($ret === false)
        {
            return false;
        }

        return true;
    }

    /**
     * 检查是否是合法网址
     *
     * @param string $str
     *
     * @return boolean
     */
    public static function isUrl($str)
    {
        $ret = filter_var( $str, FILTER_VALIDATE_URL );
        if ($ret === false)
        {
            return false;
        }

        return true;
    }


    /**
     * 检查是否是合法IP
     *
     * @param string $str
     *
     * @return boolean
     */
    public static function isIp($str)
    {
        $ret = filter_var( $str, FILTER_VALIDATE_IP );
        if ($ret === false)
        {
            return false;
        }

        return true;
    }

    /**
     * 去除换行字符
     * 包括 \n\r
     *
     * @param string $str
     *
     * @return string
     */
    public static function stripNewLines($str)
    {
        return preg_replace( '/[\n\r]/is', '', $str );
    }

    /**
     * 去除不可见字符
     * 包括 \a\f\e\0\t\x0B
     *
     * @param string $str
     *
     * @return string
     */
    public static function stripInvisible($str)
    {
        return preg_replace( '/[\a\f\e\0\t\x0B]/is', '', $str );
    }

    /**
     * 去除html标签
     *
     * @param string $str
     *
     * @return string
     */
    public static function stripHtmlTags($str)
    {
        return strip_tags( $str );
    }

    /**
     * 过滤html字符
     * 将<>"'&等字符转换成html实体
     *
     * @param string $str
     *
     * @return string
     */
    public static function filterHtmlChars($str)
    {
        return htmlspecialchars( $str, ENT_QUOTES );
    }

    /**
     * 获取URL中域名
     *
     * @param string $url
     *
     * @return string/false
     */
    public static function getUrlHost($url)
    {
        //非url
        if (!self::isUrl( $url ))
        {
            return false;
        }

        $tmp = parse_url( $url );

        //未找到host
        if (empty( $tmp['host'] ))
        {
            return false;
        }

        $host = $tmp['host'];

        //纠正获取到的host
        if (($pos = strpos( $host, '#' )))
        {
            $host = substr( $host, 0, $pos );
        }

        if (($pos = strpos( $host, '?' )))
        {
            $host = substr( $host, 0, $pos );
        }

        if (($pos = strpos( $host, '\\' )))
        {
            $host = substr( $host, 0, $pos );
        }

        return $host;
    }

    /**
     * HTML格式的回车换行转换为文本格式
     *
     * @param string $str
     * @return string
     */
    public static function br2newline($str) //_br2newline
    {
        $str = preg_replace( "#(?:\n|\r)?<br />(?:\n|\r)?#", "\n", $str );
        $str = preg_replace( "#(?:\n|\r)?<br>(?:\n|\r)?#", "\n", $str );

        return $str;
    }

    /**
     * 清除UBB
     *
     * @param unknown_type $str
     * @return unknown
     */
    public static function cleanUBB($str)
    {
        $pattern = array ();
        $pattern[] = '/\[(em)[^\[]*\[\/em\]/si';
        $pattern[] = '/\[(img)[^\[]*\[\/img\]/si';
        $pattern[] = '/\[(flash)[^\[]*\[\/flash\]/si';
        $pattern[] = '/\[(video)[^\[]*\[\/video\]/si';
        $pattern[] = '/\[(aucio)[^\[]*\[\/audio\]/si';
        $pattern[] = '/\[(em)[^\[]*\[\/em\]/si';
        $pattern[] = '/\[(qqshow)[^\[]*\[\/qqshow\]/si';
        $pattern[] = '/\[\/?(b|url|img|qqshow|flash|video|audio|ftc|ffg|fts|ft|email|center|u|i|marque|m|r|quote)[^\]]*\]/si';

        $replacement = array (
            '',
            '',
            '',
            '',
            '',
            ''
        );
        return preg_replace( $pattern, $replacement, $str );
    }



    public static function cutString($str, $length, $subfix = '...', $code = 'utf8')
    {
        if ('utf8' == $code)
        {
            $code = 'UTF-8';
        }
        if (iconv_strlen( $str, $code ) <= $length)
        {
            return $str;
        }
        return iconv_substr( $str, 0, $length, $code ) . $subfix;
    }


    /**
     * 文章内容等形式的过滤
     *
     * @param string $str
     * @return string
     */
    public static function filterContent($str) //ContentFilter($str)
    {
        $str = trim( $str );
        $str = preg_replace( '/[\a\f\e\0\t\x0B]/is', '', $str );
        $str = htmlspecialchars( $str, ENT_QUOTES );
        $str = self::filterTag( $str );
        $str = self::filterCommon( $str );
        $str = self::filterLine( $str );
        return $str;
    }

    /**
     * 简单的filterContent反转函数
     *
     * @param unknown_type $str
     */
    public static function unfilterContent($str)
    {
        $str = htmlspecialchars_decode( $str, ENT_QUOTES );
        //$str = preg_replace( '/&#([\d]+);/', chr('\\1'), $str );
        $str = preg_replace_callback(
            '/&#([\d]+);/',
            function ($matches){
                return chr($matches[0]);
            },
            $str );
        return $str;
    }


    public static function unfilterJson($json_str)
    {
        $json_unfilter = self::unfilterContent($json_str);
        $data = json_decode($json_unfilter,true);
        if(empty($data))
        {
            return $data;
        }
        $data = self::filterReq($data);
        return $data;
    }



    public static function filterReq($req_data)
    {
        //对请求参数进行安全过滤
        foreach($req_data as $k=>$v)
        {
            if(is_array($v))
            {
                $req_data[$k] = self::filterReq($v);
            }
            else
            {
                $req_data[$k] = self::filterContent($v);
            }
        }
        return $req_data;
    }


    /**
     * 将在CSS中执行js的标签剔除
     *
     * @param unknown_type $str
     */
    public static function filterCssXss($str) //_xss_css_filter($str)
    {
        $str = preg_replace( '/expression\(/im', '&#101xpr&#101ssion\(', $str );
        return $str;
    }

    /**
     * Email过滤
     *
     * @param string $str
     * @return string
     */
    public static function filterEmail($str) //EmailFilter($str)
    {
        $str = trim( $str );
        $str = preg_replace( '/[\a\f\n\e\0\r\t\x0B\;\#\*\'\"<>&\%\!\(\)\{\}\[\]\?\\/\s]/is', '', $str );
        $str = self::filterCommon( $str );

        if (substr_count( $str, '@' ) > 1)
        {
            return FALSE;
        }

        if (preg_match( '/^.+\@(\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,4}|[0-9]{1,4})(\]?)$/', $str ))
        {
            return $str;
        }
        else
        {
            return FALSE;
        }
    }

    /**
     * HASH码过滤
     *
     * @param string $str
     * @return string
     */
    public static function filterHash($str) //HashFilter($str)
    {
        $str = self::strtolower( $str );
        return preg_replace( '/[^a-f0-9]/', '', $str );
    }

    /**
     * 过滤IP地址
     *
     * @param string $key
     * @return string
     */
    public static function filterIp($key) //filterIp($key)
    {
        $key = preg_replace( '/[^0-9.]/', '', $key );
        return preg_match( '/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/', $key ) ? $key : "0.0.0.0";
    }

    /**
     * 名称过滤
     *
     * @param string $str
     * @return string
     */
    public static function filterName($str) //NameFilter($str)
    {
        $str = trim( $str );
        $str = preg_replace( '/[\a\f\n\e\0\r\t\x0B]/is', '', $str );
        $str = htmlspecialchars( $str, ENT_QUOTES );
        $str = self::filterCommon( $str );
        return $str;
    }

    /**
     * 路径过滤
     *
     * @param string $str
     * @return string
     */
    public static function filterPath($str) //PathFilter($str)
    {
        $str = self::strtolower( $str );
        $str = preg_replace( '#[^a-z0-9\.\_\-\/]#', '', $str );
        return $str;
    }

    /**
     * 文本过滤
     *
     * @param string $str
     * @return string
     */
    public static function filterText($str) //TextFilter($str)
    {
        $str = trim( $str );
        $str = preg_replace( '/[\a\f\n\e\0\r\t\x0B]/is', '', $str );
        $str = htmlspecialchars( $str, ENT_QUOTES );
        $str = self::filterTag( $str );
        $str = self::filterCommon( $str );
        return $str;
    }

    /**
     * 标题过滤
     *
     * @param string $str
     * @return string
     */
    public static function filterTitle($str) //TitleFilter($str)
    {
        $str = trim( $str );
        $str = preg_replace( '/[\a\f\n\e\0\r\t\x0B]/is', '', $str );
        $str = htmlspecialchars( $str, ENT_QUOTES );
        $str = self::filterTag( $str );
        $str = self::filterCommon( $str );
        return $str;
    }

    /**
     * URL过滤为BASE64格式
     *
     * @param unknown_type $url
     * @return unknown
     */
    public static function filterUrl($url) //URLFilter($url)
    {
        $url = trim( $url );
        $url = str_replace( '\\', '%5C', $url );
        $url = str_replace( '>', '%3E', $url );
        $url = str_replace( '<', '%3C', $url );
        $url = str_replace( '"', '%22', $url );
        $url = str_replace( ' ', '%20', $url );
        $url = str_replace( "'", '%27', $url );
        return $url;
    }

    /**
     * 过滤JS中的on***类型标签，会过滤掉所有的on前缀标签，要小心
     */
    public static function filterJs($str) //js_filter($str)
    {
        $str = self::filterTag( $str );
        $str = self::filterCssXss( $str );
        $str = preg_replace( '/\son(.*?)=/im', ' &#111;n$1', $str );
        return $str;
    }

    public static function filterWYSIWYG($str) //WYSIWYG_filter($str)
    {
        $str = self::strip_selected_tags( $str, array (
            'input',
            'textarea',
            'button',
            'select',
            'option'
        ) );
        $str = self::filterJs( $str );

        return $str;
    }



    /**
     * 输入一个时间戳，返回一个页面显示的字符串
     *
     * @param int $last_ts
     *        	最近一次的登陆时间戳
     *
     *        	$time_type
     *        	5:
     *        	// 1，当天的显示为： 今天
     *        	// 2，昨天的显示为： 昨天
     *        	// 3，更早的显示为： 三天前
     *        	6:
     *        	当天的显示为： 2010-6-10 16:30
     *
     *        	7:
     *        	// 1，	当天展示时间：	eg -- 16：30
     *        	//	2,	昨天:			eg -- 昨天
     *        	//	3,	其他				eg -- XX月XX日
     *
     *        	8: //手机侧iphone，android的显示
     *        	// 1，当天的显示为： 02:05
     *        	// 2，昨天的显示为： 昨天 02:05
     *        	// 3，更早的当年显示为： 01-12 02:05
     *        	// 3，更早的非当年显示为： 2010-01-12 02:05
     *
     *        	10: //PC Feed展示
     *        	// 1，一分钟之内显示为: 刚刚发表
     *        	// 2，大于一分钟小于一小时：49分钟前
     *        	// 3，当天的显示为： 02:05
     *        	// 4，昨天的显示为： 昨天 02:05
     *        	// 5，前天的显示为： 前天 02:05
     *        	// 6，更早的当年显示为： 1月12日 02:05
     *        	// 7，更早的非当年显示为： 2010-01-12 02:05
     *
     *        	default:
     *        	// 1，当天的显示为： 02:05
     *        	// 2，昨天的显示为： 昨天 02:05
     *        	// 3，更早的显示为： 01-12 02:05
     *
     *
     */
    public static function getSplitTime($last_ts, $time_type = 1)
    {
        $last_ts = intval( $last_ts );
        $cur_ts = time();
        if ($last_ts == 0)
        {
            $last_ts = time();
        }

        // today timestamp
        $cur_offset = ($cur_ts + 28800) / 86400; // 时区 +8
        $last_offset = ($last_ts + 28800) / 86400;
        $today_offset = floor( $cur_offset );

        switch ($time_type)
        {
            case 5 :
                if ($last_offset >= $today_offset)
                {
                    return '今天';
                }
                else if ($last_offset >= ($today_offset - 1))
                {
                    return '昨天';
                }
                else
                { // 以前
                    return '三天前';
                }
            case 6 :
                return date( "Y-m-d H:i", $last_ts );

            case 7 :
                if ($last_offset >= $today_offset)
                {
                    return date( 'H:i', $last_ts );
                }
                else if ($last_offset >= ($today_offset - 1))
                {
                    return '昨天';
                }
                else
                { // 以前
                    return date( 'm-d', $last_ts );
                }
            case 8 :
                if ($last_offset >= $today_offset)
                {
                    return date( 'H:i', $last_ts );
                }
                else if ($last_offset >= ($today_offset - 1))
                {
                    return '昨天 ' . date( 'H:i', $last_ts );
                }
                else
                { // 以前
                    $today_year = date( 'Y' );
                    $last_year = date( 'Y', $last_ts );
                    if ($last_year == $today_year)
                    {
                        return date( "m-d H:i", $last_ts );
                    }
                    else
                    {
                        return date( "Y-m-d H:i", $last_ts );
                    }
                }
            case 9 :
                if ($last_offset >= $today_offset)
                {
                    return date( 'H:i', $last_ts );
                }
                else if ($last_offset >= ($today_offset - 1))
                {
                    return '昨天';
                }
                else
                { // 以前
                    $today_year = date( 'Y' );
                    $last_year = date( 'Y', $last_ts );
                    if ($last_year == $today_year)
                    {
                        return date( "m-d", $last_ts );
                    }
                    else
                    {
                        return date( "Y-m-d", $last_ts );
                    }
                }
            case 10 :
                if ($cur_ts - $last_ts < 60)
                {
                    return '刚刚发表';
                }
                else if ($cur_ts - $last_ts >= 60 && $cur_ts - $last_ts < 3600)
                {
                    $min_intval = floor( ($cur_ts - $last_ts) / 60 );
                    return $min_intval . '分钟前';
                }
                else if ($cur_ts - $last_ts >= 3600 && $last_offset >= $today_offset)
                {
                    return date( 'H:i', $last_ts );
                }
                else if ($cur_ts - $last_ts >= 3600 && $last_offset >= ($today_offset - 1))
                {
                    return '昨天 ' . date( 'H:i', $last_ts );
                }
                else if ($last_offset >= ($today_offset - 2))
                {
                    return '前天 ' . date( 'H:i', $last_ts );
                }
                else
                {
                    $today_year = date( 'Y' );
                    $last_year = date( 'Y', $last_ts );
                    if ($last_year == $today_year)
                    {
                        $mon = date( 'n', $last_ts );
                        $day = date( 'j', $last_ts );
                        $hour_min = date( "H:i", $last_ts );
                        return $mon . '月' . $day . '日' . $hour_min;
                    }
                    else
                    {
                        return date( "Y-m-d H:i", $last_ts );
                    }
                }
            default :
                if ($last_offset >= $today_offset)
                {
                    return date( 'H:i', $last_ts );
                }
                else if ($last_offset >= ($today_offset - 1))
                {
                    return '昨天 ' . date( 'H:i', $last_ts );
                }
                else
                { // 以前
                    return date( "m-d H:i", $last_ts );
                }
        }
    }

    /**
     * 将stripslashes后的字符串中的\\再次替换为\\\\
     *
     * @param string $str
     * @return string
     */
    public static function safeSlashes($str)
    {
        return str_replace( '\\', "\\\\", self::stripslashes( $str ) );
    }

    public static function strip_selected_tags($text, $tags = array())
    {
        $args = func_get_args();
        $text = array_shift( $args );
        $tags = func_num_args() > 2 ? array_diff( $args, array (
            $text
        ) ) : ( array ) $tags;
        foreach ( $tags as $tag )
        {
            $text = str_ireplace( "<" . $tag, '&lt;' . $tag, $text );
            $text = str_ireplace( "</" . $tag, '&lt;/' . $tag, $text );
        }

        return $text;
    }

    /**
     * 转换URL中的 转义字符.如:%3a
     *
     * @param unknown_type $url
     * @return unknown
     */
    public static function toUrl($url)
    {
        $s = &$url;
        $pos = 0;
        $tmp_str = '';
        $to_url = '';
        //echo '$url'.$url;
        while ( $pos < strlen( $s ) )
        {
            $cur_char = substr( $s, $pos, 1 );
            if ($cur_char == '%')
            {
                $tmp_str = substr( $s, $pos + 1, 2 );

                $to_url .= chr( hexdec( $tmp_str ) );
                $pos = $pos + 3;
                continue;
            }

            $to_url .= $cur_char;

            $pos++;
        }
        //echo '$to_url'.$to_url;
        return $to_url;
    }

    /**
     * 一些字符串格式化
     *
     * @param string $str
     * @return string
     */
    public static function filterCommon($str) //_CommonFilter($str)
    {
        $str = str_replace( "&#032;", " ", $str );
        $str = preg_replace( "/\\\$/", "&#036;", $str );
        $str = self::addslashes( $str );
        return $str;
    }

    /**
     * 对于win系列回车以及换行做一下处理
     *
     * @param string $str
     * @return string
     */
    public static function filterLine($str) //_LineFilter($str)
    {
        return strtr( $str, array (
            "\r" => '',
            "\n" => "<br />"
        ) );
    }

    /**
     * 做一些字符转换，防止XSS等方面的问题
     *
     * @param string $str
     * @return string
     */
    public static function filterTag($str) //_TagFilter($str)
    {
        $str = str_ireplace( "javascript", "j&#097;v&#097;script", $str );
        $str = str_ireplace( "alert", "&#097;lert", $str );
        $str = str_ireplace( "about:", "&#097;bout:", $str );
        $str = str_ireplace( "onmouseover", "&#111;nmouseover", $str );
        $str = str_ireplace( "onclick", "&#111;nclick", $str );
        $str = str_ireplace( "onload", "&#111;nload", $str );
        $str = str_ireplace( "onsubmit", "&#111;nsubmit", $str );
        $str = str_ireplace( "<script", "&#60;script", $str );
        $str = str_ireplace( "onerror", "&#111;nerror", $str );
        $str = str_ireplace( "document.", "&#100;ocument.", $str );

        return $str;
    }

    /**
     * gb码json调用解码函数
     *
     * @param string $str
     * @return array
     */
    public static function gb_json_decode($str)
    {
        $arr = array ();
        $pos = 0;
        self::_recursion_decode_array( $arr, $str, $pos );
        return $arr[0];
    }

    /**
     * gb码json调用编码函数	本函数不再使用，请使用json_encode
     *
     * @param array $str
     * @return string
     */
    public static function gb_json_encode($arr)
    {
        $s = '';
        self::_recursion_encode_array( $arr, $s );
        return $s;
    }

    public static function html2space($str) //_html2space
    {
        $str = preg_replace( "/&nbsp;/m", " ", $str );
        return $str;
    }

    /**
     * validate signed numbers
     *
     * is_numeric() will pass float numbers
     * invtal() will convert a string begin with some numbers to these numbers
     * But we need validate more ...
     *
     * This function will pass all binary,octal,decimal,hexadecimal signed integer numbers
     * If you want to only use decimal signed integer numbers, please use intval() after SNS_LIB_String::is_number()'s check
     *
     * @param string $number
     * @return boolean
     */
    public static function isNumber($number)
    {
        if (!is_numeric( $number ))
        {
            return false;
        }
        if ($number == strval( intval( $number ) ))
        {
            return true;
        }

        return false;
    }

    public static function space2html($str) //_space2html
    {
        $str = preg_replace( "/\x20/m", "&nbsp;", $str );
        return $str;
    }

    /**
     * 切分用户姓名
     *
     * @author spencerliang@tencent.com
     * @param string $name
     *        	需要切分的姓名
     * @return array 按姓和名组合的数组
     */
    public static function split_name($name)
    {
        global $user_first_name_arr;
        require_once (SNSLIB_PATH . 'include/first_name.inc.php');
        if (isset( $f ))
        {
            $f = array_unique( $f );
            usort( $f, array (
                self,
                '_sort_first_name'
            ) );
            $user_first_name_arr = $f;
            unset( $f );
        }
        foreach ( $user_first_name_arr as $first_name )
        {
            $first_name = trim( $first_name );
            if ((substr( $name, 0, strlen( $first_name ) ) === $first_name) && (strlen( $first_name ) != strlen( $name )))
            {
                $last_name = substr( $name, strlen( $first_name ) );
                return array (
                    $name,
                    $first_name,
                    $last_name
                );
            }
        }
        // 假如无法匹配已知姓氏，按通用规则拆分
        $clen = iconv_strlen( $name, 'UTF-8' );
        switch ($clen)
        {
            case 1 :
                return array (
                    $name,
                    false,
                    false
                );
            case 2 :
            case 3 :
                return array (
                    $name,
                    iconv_substr( $name, 0, 1, 'UTF-8' ),
                    iconv_substr( $name, 1, 10, 'UTF-8' )
                );
            case 4 :
            case 5 :
                return array (
                    $name,
                    iconv_substr( $name, 0, 2, 'UTF-8' ),
                    iconv_substr( $name, 2, 10, 'UTF-8' )
                );
            case 6 :
                return array (
                    $name,
                    iconv_substr( $name, 0, 3, 'UTF-8' ),
                    iconv_substr( $name, 3, 10, 'UTF-8' )
                );
            default :
                return array (
                    $name,
                    false,
                    false
                );
        }
    }

    /**
     * 姓氏列表排序（用于排序姓氏数组的回调函数）
     *
     * @author spencerliang@tencent.com
     */
    private static function _sort_first_name($a, $b)
    {
        if (strlen( $a ) == strlen( $b ))
        {
            return 0;
        }
        return (strlen( $a ) > strlen( $b )) ? -1 : 1;
    }

    /**
     * 如果get_magic_quotes_gpc为true则去除slashes
     *
     * @param string $str
     * @return string
     */
    public static function stripslashes($str) //_stripslashes
    {
        if (get_magic_quotes_gpc())
        {
            $str = stripslashes( $str );
        }

        return $str;
    }

    public static function addslashes($str)
    {
        if (get_magic_quotes_gpc())
        {
            return $str;
        }
        $str = addslashes( $str );
        return $str;
    }

    /**
     * 大写转换为小写<br>
     * 在有汉字的情况下使用strtolower函数会导致错误，请使用此函数代替
     *
     * @param string $str
     * @return string
     */
    public static function strtolower($str) //_strtolower
    {
        return strtr( $str, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz" );
    }

    /**
     * 类似于 mb_strimwidth 的函数
     *
     * @param string $str
     * @param int $start
     * @param int $width
     * @param string $trimmaker
     * @param string $encoding
     * @return true: ok; false: fail.
     */
    public static function strimwidth($str, $start, $width, $trimmarker = '', $encoding = '')
    {
        if (empty( $encoding ))
        {
            $encoding = iconv_get_encoding( 'internal_encoding' );
        }

        $cstr = iconv( $encoding, 'GB18030//IGNORE', $str );
        $len = strlen( $cstr );
        $tmlen = strlen( $trimmarker );
        if ($width < $tmlen)
        {
            return $trimmarker;
        }

        $bt = $len > $start + $width;
        if ($start != 0 || $bt)
        {
            if ($bt)
            {
                $cstr = @iconv( 'GB18030', $encoding . '//IGNORE', substr( $cstr, $start, $width - $tmlen ) );
                $str = iconv_substr( $str, $start, iconv_strlen( $cstr ) ) . $trimmarker;
            }
            else
            {
                $cstr = @iconv( 'GB18030', $encoding . '//IGNORE', substr( $cstr, $start, $width ) );
                $str = iconv_substr( $str, $start, iconv_strlen( $cstr ) );
            }
        }
        return $str;
    }

    /**
     * 以特定格式返回当前时间
     * {DATE,FULL_TIME,TIME}
     *
     * @param int $timestamp
     * @param string $method
     * @return string
     */
    public static function timeFormat($timestamp, $method = 'TIME') //TimeFormat($timestamp, $method = 'TIME')
    {
        $timestamp = $timestamp > 0 ? $timestamp : $_SERVER['REQUEST_TIME'];

        switch ($method)
        {
            case 'DATE' :
                $format = 'Y-m-d';
                break;
            case 'FULL_TIME' :
                $format = 'Y-m-d H:i:s';
                break;
            case 'TIME' :
                $format = 'H:i:s';
                break;
            default :
                $format = 'Y-m-d H:i';
                break;
        }

        if (date_default_timezone_get() == 'UTC')
        {
            $time = $timestamp + (TIME_OFFSET + DST_TIME) * 3600;
            $date = gmdate( $format, $time );
        }
        else
        {
            $date = date( $format, $timestamp );
        }

        return $date;
    }

    /**
     * 把[emxxx]换成图片
     *
     * js ubbLiteReplace 的 php版本
     *
     * @param string $str
     * @return string
     *
     */
    public static function ubb_lite_replace($str, $option = array())
    {
        $op = array (
            'xss' => false,
            'image' => 'none',
            'link' => 'none',
            'img_max_width' => 800,
            'pop' => false
        );

        if (is_array( $option ) && !empty( $option ))
        {
            foreach ( $option as $k => $v )
            {
                $op[$k] = $v;
            }
        }

        $str = preg_replace( '/\[em\]e(\d{1,3})\[\/em\]/', '<img style="vertical-align:baseline  !important" src="http://imgcache.qq.com/qzone/em/e$1.gif" /><wbr>', $str );

        // 链接
        if ($op['link'] != 'none')
        {
            if ($op['link'] == 'qq')
            {
                $str = preg_replace( '/\[url=(http:\/\/.+?\.qq\.com\/.+?)\](.+?)\[\/url\]/i', "<a href='$1' target='_blank'>$2</a>", $str );
            }
            else
            {
                $str = preg_replace( '/\[url=(.*?)\](.+?)\[\/url\]/i', "<a href='$1' target='_blank'>$2</a>", $str );
            }
        }

        return $str;
    }

    /**
     * 与_TagFilter相反的作用
     *
     * @see _TagFilter
     * @param string $str
     * @return string
     */
    public static function unFilterTag($str) //_unTagFilter($str)
    {
        $str = str_ireplace( "j&#097;v&#097;script", "javascript", $str );
        $str = str_ireplace( "&#097;lert", "alert", $str );
        $str = str_ireplace( "&#097;bout:", "about:", $str );
        $str = str_ireplace( "&#111;nmouseover", "onmouseover", $str );
        $str = str_ireplace( "&#111;nclick", "onclick", $str );
        $str = str_ireplace( "&#111;nload", "onload", $str );
        $str = str_ireplace( "&#111;nsubmit", "onsubmit", $str );
        $str = str_ireplace( "&#60;script", "<script", $str );
        $str = str_ireplace( "&#100;ocument.", "document.", $str );

        return $str;
    }

    public static function unhtmlspecialchars($str) //_unhtmlspecialchars
    {
        $str = str_replace( "&amp;", "&", $str );
        $str = str_replace( "&lt;", "<", $str );
        $str = str_replace( "&gt;", ">", $str );
        $str = str_replace( "&quot;", '"', $str );
        $str = str_replace( "&#039;", "'", $str );

        return $str;
    }

    public static function makeGuid()
    {
        $randomString = openssl_random_pseudo_bytes(16);
        $time_low = bin2hex(substr($randomString, 0, 4));
        $time_mid = bin2hex(substr($randomString, 4, 2));
        $time_hi_and_version = bin2hex(substr($randomString, 6, 2));
        $clock_seq_hi_and_reserved = bin2hex(substr($randomString, 8, 2));
        $node = bin2hex(substr($randomString, 10, 6));

        /**
         * Set the four most significant bits (bits 12 through 15) of the
         * time_hi_and_version field to the 4-bit version number from
         * Section 4.1.3.
         * @see http://tools.ietf.org/html/rfc4122#section-4.1.3
         */
        $time_hi_and_version = hexdec($time_hi_and_version);
        $time_hi_and_version = $time_hi_and_version >> 4;
        $time_hi_and_version = $time_hi_and_version | 0x4000;

        /**
         * Set the two most significant bits (bits 6 and 7) of the
         * clock_seq_hi_and_reserved to zero and one, respectively.
         */
        $clock_seq_hi_and_reserved = hexdec($clock_seq_hi_and_reserved);
        $clock_seq_hi_and_reserved = $clock_seq_hi_and_reserved >> 2;
        $clock_seq_hi_and_reserved = $clock_seq_hi_and_reserved | 0x8000;

        return sprintf('%08s%04s%04x%04x%012s', $time_low, $time_mid, $time_hi_and_version, $clock_seq_hi_and_reserved, $node);
    } // guid


    public static function getTokenStr($key='')
    {
        $chars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $rand_char = '';
        $chars_len = strlen($chars);
        for($i=0;$i<16;$i++)
        {
            $rand_char .= rand(0,$chars_len);
        }
        $key_str =  $key.microtime(true).$rand_char.rand(111111,999999).'(FJ329&*#(f(DS)(@#!23489234sk232';
        return md5($key_str);

    }

    public static function makeGAAuthUrl($name,$secret)
    {
        return 'otpauth://totp/'.$name.'?secret='.$secret;
    }


    public static function getClientIp()
    {
        $ip=false;
        if(!empty($_SERVER['HTTP_CLIENT_IP']))
        {
            return self::isIp($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : $ip;
        }
        elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
        {
            return self::isIp($_SERVER['HTTP_X_FORWARDED_FOR'])?$_SERVER['HTTP_X_FORWARDED_FOR']:$ip;
        }
        else
        {
            return self::isIp($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:$ip;
        }
    }

    public static function randStrMd5()
    {
        return md5(microtime(true).rand(111111,999999).'@(#FUND)ou-314u10nfint-40824');
    }

    public static function uuid()
    {
        return md5(microtime(true).rand(111111,999999).'@(#FUND)ou-314u10nfint-40824');
    }



}
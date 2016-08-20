<?php
require_once(dirname(__FILE__).'/HttpBase.php');
class HttpCgi extends HttpBase
{
    public function __construct()
    {
        $raw_content = file_get_contents("php://input");
        $this->setRequest([],$_SERVER,$_GET,$_POST,$_COOKIE,$_FILES,$raw_content);
        foreach($_SERVER as $k=>$v)
        {
            if(strpos($k,'HTTP_')===0)
            {
                $key_name = substr($k,5);
                $this->header[$key_name] = $v;
            }
        }
    }

    public function sessionStart($cookie_timeout=43200)
    {
        session_set_cookie_params($cookie_timeout,"/",null,false,true);
        session_start();
        $this->session = $_SESSION;
    }

    public function setSession($key,$value)
    {
        $_SESSION[$key] = $value;
        $this->session = $_SESSION;
    }

    public function delSession($key)
    {
        unset($_SESSION[$key]);
        unset($this->session[$key]);
    }


    /**
     * @param $key
     * @param $value
     */
    public function setHeader($key, $value)
    {
        header($key.': '.$value);
    }

    /**
     * @param $key
     * @param string $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $http_only
     */
    public function setCookie($key, $value = '', $expire = 0 , $path = '/', $domain  = '', $secure = false ,$http_only = false)
    {
        setcookie($key, $value, $expire, $path, $domain, $secure, $http_only);
    }

    /**
     * @param $http_status_code
     */
    public function setStatus($http_status_code)
    {
        $msg = '';
        header("HTTP/1.1 $http_status_code $msg");
    }

    /**
     * @param int $level
     */
    public function gzip($level=1)
    {

    }

    /**
     * @param $data
     */
    public function write($data)
    {
        echo $data;
    }


    /**
     * @param $file_name
     */
    public function sendfile($file_name)
    {
        $file_content = file_get_contents($file_name);
        echo $file_content;
    }


    /**
     * @param $content
     */
    public function send($content)
    {
        echo $content;
    }

}
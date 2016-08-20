<?php
class HttpBase
{
    public $header;
    public $server;

    public $get;
    public $post;
    public $cookie;
    public $files;
    public $raw_content;
    public $session;


    /**
     * @param array $header
     * @param array $server
     * @param array $get
     * @param array $post
     * @param array $cookie
     * @param array $files
     * @param string $raw_content
     * @param array $session
     */
    public function setRequest($header=[],$server=[],$get=[],$post=[],$cookie=[],$files=[],$raw_content='')
    {
        $this->header=$header;
        $this->server = $server;
        $this->get = $get;
        $this->post = $post;
        $this->cookie = $cookie;
        $this->files = $files;
        $this->raw_content = $raw_content;
    }

    public function sessionStart($cookie_timeout=43200)
    {

    }

    public function getSession($key='')
    {
        if(empty($key))
        {
            return $this->session;
        }
        if(isset($this->session[$key]))
        {
            return $this->session[$key];
        }
        return null;
    }

    public function setSession($key,$value)
    {

    }


    public function delSession($key)
    {
        unset($this->session[$key]);
    }

    /**
     * @param $key
     * @param $value
     */
    public function setHeader($key, $value)
    {

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

    }


    /**
     * @param $http_status_code
     */
    public function setStatus($http_status_code)
    {

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

    }


    /**
     * @param $file_name
     */
    public function sendfile($file_name)
    {

    }


    /**
     * @param $content
     */
    public function send($content)
    {

    }

    public function display($file_name,$data)
    {

    }

    public function redirect($url)
    {
        $this->setHeader('Location',$url);
        return true;
    }
}
<?php
require_once(dirname(__FILE__).'/HttpBase.php');
class HttpSwoole extends HttpBase
{
    protected $swoole_req;
    protected $swoole_resp;
    protected $config;
    protected $session_id;

    public function __construct($swoole_req,$swoole_resp,$config)
    {
        $this->config = $config;
        $this->swoole_req = $swoole_req;
        $this->swoole_resp = $swoole_resp;

        $server_params = [];
        foreach($swoole_req->server as $k=>$v)
        {
            $uper_key = strtoupper($k);
            $server_params[$uper_key] = $v;
        }

        $header_params = [];
        foreach($swoole_req->header as $hk=>$hv)
        {
            $h_uper_key = strtoupper($hk);
            $header_params[$h_uper_key] = $hv;

            if(strpos($h_uper_key,'-')!==false)
            {
                $down_flag_uper_key = str_replace('-','_',$h_uper_key);
                $header_params[$down_flag_uper_key] = $hv;
            }
        }

        $http_get = isset($swoole_req->get) ? $swoole_req->get : [];
        $http_post = isset($swoole_req->post) ? $swoole_req->post : [];
        $http_file = isset($swoole_req->files) ? $swoole_req->files : [];
        $cookie = isset($swoole_req->cookie) ? $swoole_req->cookie: [];

        $this->setRequest($header_params,$server_params,$http_get,$http_post,$cookie,$http_file,$swoole_req->rawContent());
    }

    public function sessionStart($cookie_timeout=43200)
    {
        $session_key_name = isset($this->config['session_id']) ? $this->config['session_id'] : 'SWSESSID';
        if(empty($this->cookie[$session_key_name]))
        {
            $session_id = md5(TString::makeGuid());
            $this->setCookie($session_key_name,$session_id,time()+$cookie_timeout,'/','',false,true);
            $this->session_id = $session_id;
        }
        else
        {
            $this->session_id = $this->cookie[$session_key_name];
        }
        $this->sessionInit($this->session_id,$cookie_timeout);
        $this->loadSession();
    }

    protected function sessionInit($session_id,$cookie_timeout)
    {
        if(empty($this->config['session_type']) || $this->config['session_type'] == 'file')
        {
            $file_name = $this->getSessionFileName($session_id);
            if(!file_exists($file_name))
            {
                $session_data = [
                    'end_time' => time()+$cookie_timeout,
                    'data'=>[]
                ];
                file_put_contents($file_name,json_encode($session_data));
            }
        }
    }

    protected function getSessionFileName($session_id)
    {
        $session_file = md5($session_id.'()NPF$*)@#(_*$*(fh2pnu4ihUHF:#@893847');
        $file_name = $this->config['session_path'].'/'.$session_file;
        return $file_name;
    }

    protected function loadSession()
    {
        if(empty($this->config['session_type']) || $this->config['session_type'] == 'file')
        {
            $file_name = $this->getSessionFileName($this->session_id);
            $session_json = json_decode(file_get_contents($file_name),true);
            if(empty($session_json))
            {
                $this->session = [];
                return false;
            }

            if(time() > $session_json['end_time'])
            {
                $this->session = [];
                return false;
            }

            $this->session = $session_json['data'];
        }
    }

    public function setSession($key,$value)
    {
        $file_name = $this->getSessionFileName($this->session_id);
        $session_json = json_decode(file_get_contents($file_name),true);
        $this->session[$key] = $value;
        $session_json['data'] = $this->session;
        file_put_contents($file_name,json_encode($session_json));
    }

    public function delSession($key)
    {
        unset($this->session[$key]);
        $file_name = $this->getSessionFileName($this->session_id);
        $session_json = json_decode(file_get_contents($file_name),true);
        $session_json['data'] = $this->session;
        file_put_contents($file_name,json_encode($session_json));
    }


    /**
     * @param $key
     * @param $value
     */
    public function setHeader($key, $value)
    {
        $this->swoole_resp->header($key,$value);
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
        $this->swoole_resp->cookie($key, $value, $expire, $path, $domain, $secure, $http_only);
    }

    /**
     * @param $http_status_code
     */
    public function setStatus($http_status_code)
    {
        $this->swoole_resp->status($http_status_code);
    }

    /**
     * @param int $level
     */
    public function gzip($level=1)
    {
        $this->swoole_resp->gzip($level);
    }

    /**
     * @param $data
     */
    public function write($data)
    {
        $this->swoole_resp->write($data);
    }


    /**
     * @param $file_name
     */
    public function sendfile($file_name)
    {
        $this->swoole_resp->sendfile($file_name);
    }


    /**
     * @param $content
     */
    public function send($content)
    {
        $this->swoole_resp->end($content);
    }
    
    public function redirect($url)
    {
        $this->setHeader('Location',$url);
        $this->setStatus(302);
        return true;
    }
}
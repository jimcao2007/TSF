<?php

class Dispatch
{
    public static function filter($req_data)
    {
        //对请求参数进行安全过滤
        foreach($req_data as $k=>$v)
        {
            if(is_array($v))
            {
                $req_data[$k] = self::filter($v);
            }
            else
            {
                $req_data[$k] = TString::filterContent($v);
            }
        }
        return $req_data;
    }

    /**
     * @param $con
     * @param $act
     * @param $req_data
     * @param null $post_body
     * @param null $get
     * @param \HttpBase $http_obj
     * @return array
     */
    public static function route($con,$act,$req_data)
    {
        $con = self::filter($con);
        $act = self::filter($act);

        $con_class_name = 'Controller\\'.$con;

        if(!class_exists($con_class_name))
        {
            \Logger::flush();
            return F::success();
        }

        $req_data = self::filter($req_data);
        if(!empty($get))
        {
            $get = self::filter($get);
        }

        if(!empty($post_body))
        {
            $post_body = self::filter($post_body);
        }

        $controller_class = new $con_class_name($con,$act,$req_data);
        if(!method_exists($controller_class,$act))
        {
            \Logger::flush();
            $http_obj->setStatus(404);
            return false;
        }

        //先调用init
        $init_ret = $controller_class->init();
        if(empty($init_ret))
        {
            return false;
        }
        $controller_class->$act();
        \Logger::flush();
    }

    /*
    public static function run($stream)
    {
        $unpack_data = Soaproto::unpackJson($stream);

        switch($unpack_data['head']['type'])
        {
            case Soaproto::PROTO_TYPE_JSON:
                $result = self::runByJson($unpack_data['body']);
                $pack_stream = Soaproto::packJson($result,$unpack_data['head']['version']);
                return $pack_stream;
            break;
        }
    }
    */


    public static function format($code=self::SUCCESS,$msg='',$data=[])
    {
        return array(
            'status'=>$code,
            'info'=>$msg,
            'data'=>$data,
        );
    }


    public static function formatError($code,$msg='',$data=[])
    {
        if(empty($msg))
        {
            if(isset(self::$err_msg[$code]))
            {
                $msg = self::$err_msg[$code];
            }
        }
        return self::format($code,$msg,$data);
    }

    public static function formatSuccess($data=[],$msg='success')
    {
        return self::format(self::SUCCESS,$msg,$data);
    }


    public static function isSuccess($data)
    {
        if($data['status'] == self::SUCCESS)
        {
            return true;
        }
        return false;
    }


    public static function getDefaltValue($conf,$data)
    {
        $ret_data = [];
        foreach($conf as $k=>$v)
        {
            $ret_data[$k] = isset($data[$k]) ? $data[$k] : $conf[$k];
        }
        return $ret_data;
    }

    /**
     * @param $url
     * @param $config
     * @param \HttpBase $http_obj
     * @return array
     */
    public static function runByRoutePath($url,$config,$http_obj)
    {
        $url_params = explode('/',$url);
        foreach($url_params as $k=>$p)
        {
            if(empty($p))
            {
                unset($url_params[$k]);
            }
        }

        $url_params = array_values($url_params);

        $url_format = '/'.implode('/',$url_params).'/';

        $con = $act = '';
        foreach($config as $r=>$c)
        {
            if(strpos($url_format,$r)===0)
            {
                if(is_int($c['con']))
                {
                    $con = $url_params[$c['con']];
                }
                else{
                    $con = $c['con'];
                }

                if(is_int($c['act']))
                {
                    $act = $url_params[$c['act']];
                }
                else
                {
                    $act = $c['act'];
                }

                break;
            }
        }

        if(empty($con) || empty($act))
        {
            return \Dispatch::formatError(\Dispatch::ERR_PARAMS,'params error');
        }

        $http_obj->cookie = self::filter($http_obj->cookie);

        if($http_obj->server['REQUEST_METHOD'] == 'GET')
        {
            $req_data = $http_obj->get;
            $post_body = '';
        }
        else
        {
            $req_data = $http_obj->post; //$_POST隐含的使用条件是post部分要使用x=y&z=k的格式
            $post_body = $http_obj->raw_content; //获取http的整个body部分
        }

        $req_data['_url_params'] = $url_params;
        $req_data['con'] = $con;
        $req_data['act'] = $act;

        $http_obj->sessionStart();

        self::route($con,$act,$req_data,$post_body,$http_obj->get,$http_obj);
    }


    /*
    public static function runByJson($data)
    {
        $con = $data['con'];
        $act = $data['act'];
        return self::route($con,$act,$data);
    }


    public static function  runByHttpRewriteJson()
    {
        $path_args = '';
        $_COOKIE = self::filter($_COOKIE);
        $cont_act_param = $_SERVER['PATH_INFO'];
        if(empty($cont_act_param))
        {
            $con = 'Index';
            $act = 'index';
        }
        else
        {
            $param_explode = explode('/',$cont_act_param);

            if(empty($param_explode[1]) || empty($param_explode[2]))
            {
                //非模板输出则默认使用json
                if(View::$is_display == false)
                {
                    return self::jsonOut(Dispatch::formatError(Dispatch::ERR_PARAMS,'params err'));
                }
                else{
                    die('403');
                }
            }
            $con = $param_explode[1];
            $act = $param_explode[2];
            if(!empty($param_explode[3]))
            {
                $path_args = $param_explode[3];
            }
        }



        $post_body = null;
        if($_SERVER['REQUEST_METHOD'] == 'GET')
        {
            $req_data = $_GET;
        }
        else
        {
            $req_data = $_POST; //$_POST隐含的使用条件是post部分要使用x=y&z=k的格式
            //$req_data = array_merge($_GET,$_POST);
            $post_body = file_get_contents("php://input");//获取http的整个body部分
        }
        $req_data['con'] = $con;
        $req_data['act'] = $act;
        if(!empty($path_args))
        {
            $req_data['path_args'] = $path_args;
        }

        session_set_cookie_params(0,"/",null,false,true);
        session_start();
        $data = self::route($con,$act,$req_data,$post_body,$_GET);
        if(View::$is_display == false)
        {
            self::jsonOut($data);
        }
    }


    public static function runByUriJson($session=true)
    {
        $_COOKIE = self::filter($_COOKIE);

        $con = isset($_GET['con']) ? $_GET['con'] : 'Index';
        $act = isset($_GET['act']) ? $_GET['act'] : 'index';

        $post_body = null;
        if($_SERVER['REQUEST_METHOD'] == 'GET')
        {
            $req_data = $_GET;
        }
        else
        {
            //$req_data = $_POST;
            $req_data = array_merge($_GET,$_POST);
            $post_body = file_get_contents("php://input");
        }
        $req_data['con'] = $con;
        $req_data['act'] = $act;

        if($session)
        {
            session_set_cookie_params(0,"/",null,false,true);
            session_start();
        }

        $data = self::route($con,$act,$req_data,$post_body,$_GET);
        if(View::$is_display == false)
        {
            self::jsonOut($data);
        }
    }
    */



}
<?php

class Controller
{
    public $request;
    public $con;
    public $act;
    public $post_body;
    public $get;
    public $display = false;


    /**
     * @var \HttpBase
     */
    public $http;

    public function __construct($con,$act,$req,$post_body=null,$get=null,$http_obj=null)
    {
        $this->request = $req;
        $this->post_body = $post_body;
        $this->con = $con;
        $this->act = $act;
        $this->get = $get;
        $this->http = $http_obj;
    }

    public function init()
    {
        return Dispatch::formatSuccess();
    }

    final public function display($tpl,$data)
    {
        $this->http->display($tpl,$data);
        $this->display = true;
    }
}
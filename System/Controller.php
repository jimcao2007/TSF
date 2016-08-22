<?php
class Controller
{
    protected $con;
    protected $act;
    protected $request;
    public function __construct($con,$act,$request)
    {
        $this->con = $con;
        $this->act = $act;
        $this->request = $request;
    }

    public function _init()
    {
        return false;
    }


}
<?php
class Controller
{
    protected $con;
    protected $act;
    protected $params;
    public function __construct($con,$act,$params)
    {
        $this->con = $con;
        $this->act = $act;
        $this->params = $params;
    }

    public function _init()
    {
        return false;
    }


}
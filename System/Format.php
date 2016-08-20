<?php

class FormatBase
{
    public function formatData($data,$status,$info)
    {
        return [
            'status'=>$status,
            'info'=>$info,
            'data'=>$data
        ];
    }

    public function success($data,$info='',$status=null)
    {
        if($status===null)
        {
            $status = C::CODE('SUCCESS');
        }
        return $this->formatData($data,$status,$info);
    }

    public function error($status,$info='',$data=[])
    {
        if(empty($info) && self::$info_map[$status])
        {
            $info = self::$info_map[$status];
        }
        return self::format($status,$info,$data);
    }
}
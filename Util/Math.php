<?php

namespace Util;
class Math
{
    /**
     * 按百分比抽样
     *
     * @param float $rate  百分比数值，如 0.10
     * @return bool 命中返回true，否则返回false
     */
    public static function rand100($rate)
    {
        $rate_num = $rate*1000000;
        $rand = rand(0,1000000);
        if($rand <= $rate_num)
        {
            return true;
        }
        return false;
    }
}
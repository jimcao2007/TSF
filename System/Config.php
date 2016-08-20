<?php
return [
    'SYSTEM' => [
        'ERR_CODE' => [
            'SUCCESS' => ['code'=>0,'info'=>'成功'],
            'ERR_PARAMS' => ['code'=>300,'info'=>'参数错误'],
            'ERR_NO_LOGIN' => ['code'=>400,'info'=>'未登录'],
            'ERR_FORBIDDEN' => ['code'=>403,'info'=>'禁止访问'],
            'ERR_SYSTEM' => ['code'=>500,'info'=>'系统繁忙'],
        ],

        'RETURN_FORMAT' => [
            'STATUS' =>'status',
            'INFO' => 'info',
            'DATA' => 'data'
        ],

        'Format' => 'FormatBase',
        
        'EG' => [
            'http'=>'HttpCgi',
        ],
    ],



];
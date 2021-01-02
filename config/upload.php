<?php

defined('M') || define('M', 1048576);

/**
 * 素材附件
 */
return [
    //图片
    'image' => [
        'storage_path'    => public_path('poolAssets/uploads/images/' . date('Ymd') . '/'),
        'upload_max_size' => 1 * M, // 5M
        'prefix'          => '/poolAssets/uploads/images/'.date('Ymd'). '/',
        'allow_types'     => [
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'bmp' => 'image/bmp',
            'gif' => 'image/gif',
        ]
    ],

   //视频
    'video' => [
        'storage_path'    => public_path('poolAssets/uploads/videos/' . date('Ymd') . '/'),
        'upload_max_size' => 10 * M,
        'prefix'          => '/poolAssets/uploads/videos/'.date('Ymd'). '/',
        'allow_types'     => [
            'mp4' => 'video/mp4',
        ]
    ],

    //声音
    'voice' => [
        'storage_path'    => public_path('poolAssets/uploads/voices/' . date('Ymd') . '/'),
        'upload_max_size' => 2 * M,
        'prefix'          => '/poolAssets/uploads/voices/'.date('Ymd'). '/',
        'allow_types'     => [
            'mp3' => 'audio/mpeg',
            'wma' => 'audio/x-ms-wma',
            'wav' => 'audio/wav',
            'amr' => 'audio/amr',
        ]
    ],

    //文件
    'file' => [
        'storage_path'    => public_path('poolAssets/uploads/exports/' . date('Ymd') . '/'),
        'upload_max_size' => 10 * M,
        'prefix'          => '/poolAssets/uploads/exports/'.date('Ymd'). '/',
        'allow_types'     => [
            'xls'  => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]
    ],
];
<?php
/*
 * @Date: 2021-04-19 16:19:10
 * @LastEditors: Mok.CH
 * @LastEditTime: 2021-05-11 13:37:38
 * @FilePath: \sverp\config\filesystem.php
 */

return [
    // 默认磁盘
    'default' => env('filesystem.driver', 'local'),
    // 磁盘列表
    'disks'   => [
        'local'  => [
            'type' => 'local',
            // 'root' => app()->getRuntimePath() . 'storage',
            'root' => '../public/static' ,
        ],
        'public' => [
            // 磁盘类型
            'type'       => 'local',
            // 磁盘路径
            'root'       => app()->getRootPath() . 'public/storage',
            // 磁盘路径对应的外部URL路径
            'url'        => '/storage',
            // 可见性
            'visibility' => 'public',
        ],
        // 更多的磁盘配置信息
        // 文控系统文件存储位置
        'dcs' => [
            'type'       => 'local',
            'root'       => 'D:\dcs',
        ],
    ],
];

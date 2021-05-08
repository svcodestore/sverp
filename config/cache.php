<?php
/*
 * @Author: yanbuw1911
 * @Date: 2021-03-11 15:46:04
 * @LastEditors: yanbuw1911
 * @LastEditTime: 2021-04-28 15:23:55
 * @Description: Do not edit
 * @FilePath: /sverp/config/cache.php
 */
// +----------------------------------------------------------------------
// | 缓存设置
// +----------------------------------------------------------------------

return [
    // 默认缓存驱动
    'default' => env('cache.driver', 'file'),

    // 缓存连接方式配置
    'stores'  => [
        'file' => [
            // 驱动方式
            'type'       => 'File',
            // 缓存保存目录
            'path'       => '',
            // 缓存前缀
            'prefix'     => '',
            // 缓存有效期 0表示永久缓存
            'expire'     => 0,
            // 缓存标签前缀
            'tag_prefix' => 'tag:',
            // 序列化机制 例如 ['serialize', 'unserialize']
            'serialize'  => [],
        ],
        // 更多的缓存连接
        'redis' => [
            // 驱动方式
            'type'       => 'redis',
            'host'       => env('cache.redis_host', '127.0.0.1'),
            'password'   => env('cache.redis_password', 'startRedis')
        ],
    ],
];

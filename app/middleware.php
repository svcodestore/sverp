<?php
/*
 * @Author: yanbuw1911
 * @Date: 2020-10-27 17:03:42
 * @LastEditTime: 2020-12-10 12:06:47
 * @LastEditors: yanbuw1911
 * @Description: 
 * @FilePath: \backend\app\middleware.php
 */
// 全局中间件定义文件
return [
    // 全局请求缓存
    \think\middleware\CheckRequestCache::class,
    // 多语言加载
    // \think\middleware\LoadLangPack::class,
    // Session初始化
    // \think\middleware\SessionInit::class
    // 框架跨域 没效果。。。
    // \think\middleware\AllowCrossDomain::class,
    // 自定义全局跨域请求处理 无效。。。
    // \app\middleware\AllowCrossDomain::class,
];

<?php
/*
* @Author: yanbuw1911
* @Date: 2020-12-29 10:47:11
 * @LastEditTime: 2021-02-05 08:47:49
 * @LastEditors: yanbuw1911
* @Description:
 * @FilePath: /sverp/app/webApi/controller/Mis.php
*/

namespace app\webApi\controller;

class Mis
{
    public function downloadClient()
    {
        $platform = input('platform');

        $srcPath = app()->getRootPath() . '..' . DIRECTORY_SEPARATOR . 'clients' . DIRECTORY_SEPARATOR . $platform;

        $file = array_values(array_diff(scandir($srcPath), array('.', '..')));

        if ($file) {
            $filepath = $srcPath . DIRECTORY_SEPARATOR . $file[0];
            header('Content-Type: application/octet-stream');
            header('Accept-Ranges:bytes');
            $fileSize = filesize($filepath);
            header('Content-Length:' . $fileSize);
            header('Content-Disposition:attachment;filename=' . basename($filepath));
            $handle = fopen($filepath, 'rb'); //二进制文件用‘rb’模式读取
            while (!feof($handle)) { //循环到文件末尾 规定每次读取（向浏览器输出为$readBuffer设置的字节数）
                echo fread($handle, 1024);
            }
            fclose($handle); //关闭文件句柄
            exit;
        }
    }

    public function sysUpdate()
    {
        $cmd = '@echo off 
        D: 
        cd vhost/www.sverp.com && "C:\Program Files\Git\bin\git.exe" pull origin main && cd ../webapp && "C:\Program Files\Git\bin\git.exe" pull origin main && npm install && npm run build && npm run docs:build:man && npm run docs:build:dev';
        $res = exec($cmd);

        return $res;
    }
}

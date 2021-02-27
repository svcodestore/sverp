<?php
/*
* @Author: yanbuw1911
* @Date: 2020-12-29 10:47:11
 * @LastEditTime: 2021-02-27 10:30:36
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
        $cmd = 'update-pack.bat';
        exec($cmd, $res);

        return $res;
    }

    public function startNodeWeb()
    {
        $cmd = 'start-node-spare.bat';
        exec($cmd, $res);

        return $res;
    }

    public function stopNodeWeb()
    {
        $cmd = 'stop-node-spare.bat';
        exec($cmd, $res);

        return $res;
    }

    public function restartNodeWeb()
    {
        $cmd = 'restart-node-spare.bat';
        exec($cmd, $res);

        return $res;
    }
}

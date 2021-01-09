<?php
/*
 * @Date: 2020-12-28 14:21:44
 * @LastEditors: yu chen
 * @LastEditTime: 2020-12-29 15:45:58
 * @FilePath: \sverp\app\webApi\api\Index.php
 */

namespace app\webApi\api;
use app\webApi\controller\Record as recordController;
class Index
{
    public function index()
    {
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:*');
        header('Access-Control-Allow-Headers:*');
        header('Access-Control-Max-Age: 86400');

        return 1;
    }
    public function apiUpload()
    {
       return (new recordController)->upload();
    }
}

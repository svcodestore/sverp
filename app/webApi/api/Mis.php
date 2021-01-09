<?php
/*
* @Author: yanbuw1911
* @Date: 2020-12-29 10:59:57
 * @LastEditTime: 2020-12-29 13:51:58
 * @LastEditors: yanbuw1911
* @Description:
 * @FilePath: \backend\app\webApi\api\Mis.php
*/

namespace app\webApi\api;


use app\webApi\controller\Mis as ControllerMis;

class Mis
{
    public function downloadClient()
    {
        return (new ControllerMis())->downloadClient();
    }
}

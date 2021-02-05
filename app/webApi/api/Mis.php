<?php
/*
* @Author: yanbuw1911
* @Date: 2020-12-29 10:59:57
 * @LastEditTime: 2021-02-05 08:47:15
 * @LastEditors: yanbuw1911
* @Description:
 * @FilePath: /sverp/app/webApi/api/Mis.php
*/

namespace app\webApi\api;


use app\webApi\controller\Mis as ControllerMis;

class Mis
{
    public function downloadClient()
    {
        return (new ControllerMis())->downloadClient();
    }

    public function sysUpdate()
    {
        return (new ControllerMis())->sysUpdate();
    }
}

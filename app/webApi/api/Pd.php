<?php
/*
* @Date: 2021-04-29 13:19:01
 * @LastEditors: Mok.CH
 * @LastEditTime: 2021-04-29 16:30:47
 * @FilePath: \sverp\app\webApi\api\Pd.php
*/

namespace app\webApi\api;

use app\BaseController;
use app\webApi\controller\Pd as ControllerPd;

class Pd extends BaseController
{
    public function getCruInfo()
    {
        return (new ControllerPd)->getCruInfo();
    }

    public function getOrders()
    {
        return (new ControllerPd)->getOrderList();
    }

    public function test()
    {
        return (new ControllerPd)->getOrderList();
    }
}
<?php
/*
 * @Date: 2021-05-24 14:16:46
 * @LastEditors: yanbuw1911
 * @LastEditTime: 2021-06-30 10:56:43
 * @FilePath: /sverp/app/webApi/api/Order.php
 */

namespace app\webApi\api;

use app\BaseController;
use app\webApi\controller\Order as ControllerOrder;

class Order
{
    public function index()
    {
        return (new ControllerOrder)->index();
    }

    public function getOrders()
    {
        return (new ControllerOrder)->getOrders();
    }
}

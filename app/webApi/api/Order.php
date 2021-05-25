<?php
/*
 * @Date: 2021-05-24 14:16:46
 * @LastEditors: Mok.CH
 * @LastEditTime: 2021-05-24 14:23:24
 * @FilePath: \sverp\app\webApi\api\Order.php
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
}
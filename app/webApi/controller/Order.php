<?php
/*
 * @Date: 2021-05-24 14:12:23
 * @LastEditors: Mok.CH
 * @LastEditTime: 2021-05-25 14:24:31
 * @FilePath: \sverp\app\webApi\controller\Order.php
 */

namespace app\webApi\controller;

use think\facade\Log;
use app\webApi\model\Order as ModelOrder;

class Order
{
    public function index()
    {
        $params = request()->param();
        
        $model = new ModelOrder();
        if ($params['tableType'] == 'normal')
            $data = $model->getOrders($params);
        else
            $data = $model->orderDetailList($params);

        return json(['status'=> 'ok', 'data'=>$data]);
    }
}
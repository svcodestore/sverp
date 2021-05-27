<?php
/*
 * @Date: 2021-05-24 14:12:23
 * @LastEditors: Mok.CH
 * @LastEditTime: 2021-05-26 15:48:07
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
        $type = $params['tableType'];
        unset($params['tableType']);
        unset($params['company']);

        // 数据量大， 若搜索字段为空， 不进行全表数据返回
        $params = array_filter($params);

        if (count($params) === 0) return json(['code'=>1, 'status'=>'faild','msg'=>'搜索内容不能都为空']);

        $model = new ModelOrder();
        if ($type == 'normal')
            $data = $model->getOrders($params);
        else
            $data = $model->orderDetailList($params);

        return json(['status'=> 'ok', 'data'=>$data]);
    }
}
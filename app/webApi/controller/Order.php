<?php
/*
 * @Date: 2021-05-24 14:12:23
 * @LastEditors: yanbuw1911
 * @LastEditTime: 2021-06-30 15:43:02
 * @FilePath: /sverp/app/webApi/controller/Order.php
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

        if (count($params) === 0) return json(['code' => 1, 'status' => 'faild', 'msg' => '搜索内容不能都为空']);

        $model = new ModelOrder();
        if ($type == 'normal')
            $data = $model->getOrders($params);
        else
            $data = $model->orderDetailList($params);

        return json(['status' => 'ok', 'data' => $data]);
    }

    public function getOrders()
    {
        $KhPONo = input('post.KhPONo', '');
        $sp_No = input('post.sp_No', '');
        $khNo = input('post.khNo', '');
        $company = input('post.company', '1');

        $rtn['result'] = true;
        $rtn['data'] = (new ModelOrder)->getOrders2($KhPONo, $sp_No, $khNo, $company);

        return json($rtn);
    }
}

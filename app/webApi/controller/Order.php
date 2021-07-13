<?php
/*
 * @Date: 2021-05-24 14:12:23
 * @LastEditors: yanbuw1911
 * @LastEditTime: 2021-07-13 08:34:32
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

    public function syncPrice()
    {
        // $KhPONo = input('post.KhPONo', '');
        // $sp_No = input('post.sp_No', '');
        // $khNo = input('post.khNo', '');
        // $price = input('post.price', '');
        // $company = input('post.company', '1');

        // $rtn['result'] = true;

        // $cond = [
        //     [
        //         'KhPONo' => '86128',
        //         'sp_No' => 'B8192A',
        //         'khNo' => 'N320002402',
        //         'company' => '2',
        //         'price' => '15.4',
        //     ],
        //     [
        //         'KhPONo' => '86128',
        //         'sp_No' => 'B7933M',
        //         'khNo' => 'N320002301',
        //         'company' => '2',
        //         'price' => '12.95',
        //     ],
        //     [
        //         'KhPONo' => '86242',
        //         'sp_No' => 'B7982A',
        //         'khNo' => 'N210002697',
        //         'company' => '2',
        //         'price' => '13.95',
        //     ],
        //     [
        //         'KhPONo' => '86381',
        //         'sp_No' => 'B7287C',
        //         'khNo' => 'A1033297',
        //         'company' => '2',
        //         'price' => '15.6',
        //     ],
        //     [
        //         'KhPONo' => '86275',
        //         'sp_No' => 'B78860',
        //         'khNo' => 'A1530508',
        //         'company' => '2',
        //         'price' => '14.6',
        //     ],
        //     [
        //         'KhPONo' => '86800',
        //         'sp_No' => 'B7310M',
        //         'khNo' => 'A1038602',
        //         'company' => '2',
        //         'price' => '14',
        //     ],
        //     [
        //         'KhPONo' => '86381',
        //         'sp_No' => 'B5329B',
        //         'khNo' => 'A1513402',
        //         'company' => '2',
        //         'price' => '17.25',
        //     ],
        //     // [
        //     //     'KhPONo' => '86431',
        //     //     'sp_No' => 'B69650',
        //     //     'khNo' => 'N320002402',
        //     //     'company' => '2',
        //     //     'price' => '15.4',
        //     // ],
        //     [
        //         'KhPONo' => '86381',
        //         'sp_No' => 'B60700',
        //         'khNo' => 'A1302802',
        //         'company' => '2',
        //         'price' => '10.1',
        //     ],
        //     [
        //         'KhPONo' => '86332',
        //         'sp_No' => 'B7982B',
        //         'khNo' => 'N210002706',
        //         'company' => '2',
        //         'price' => '13.95',
        //     ],
        //     [
        //         'KhPONo' => '86200',
        //         'sp_No' => 'B8240A',
        //         'khNo' => 'N4440801',
        //         'company' => '2',
        //         'price' => '10.95',
        //     ],
        //     [
        //         'KhPONo' => '86381',
        //         'sp_No' => 'B5400A',
        //         'khNo' => 'A1513802',
        //         'company' => '2',
        //         'price' => '13.55',
        //     ],
        //     [
        //         'KhPONo' => '86481',
        //         'sp_No' => 'B69790',
        //         'khNo' => 'A1510202',
        //         'company' => '2',
        //         'price' => '15.1',
        //     ],
        // ];

        // $cond = array_merge(array_map(function ($e) {
        //     return [
        //         'KhPONo' => '86431',
        //         'sp_No' => 'B69650',
        //         'khNo' => 'N2475701-' . $e,
        //         'company' => '2',
        //         'price' => '11.05',
        //     ];
        // }, range(30, 46)), array_map(function ($e) {
        //     return [
        //         'KhPONo' => '86431',
        //         'sp_No' => 'B69650',
        //         'khNo' => 'N2475701-' . $e,
        //         'company' => '2',
        //         'price' => '15.7',
        //     ];
        // }, range(48, 60)));

        $cond = [
            [
                'KhPONo' => '86833',
                'sp_No' => 'B78860',
                'khNo' => 'A1530508',
                'company' => '2',
                'price' => '14.6',
            ]
        ];
        // dd($cond);

        $m = new ModelOrder;



        foreach ($cond as $c) {
            dump($m->syncPrice($c['KhPONo'], $c['sp_No'], $c['khNo'], $c['company'], $c['price']));
        }
        die;

        // $rtn['data'] = (new ModelOrder)->syncPrice('86431', 'B69650', 'N2475701', 2, $price);

        // return json($rtn);
    }
}

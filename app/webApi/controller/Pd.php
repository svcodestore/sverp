<?php
/*
 * @Date: 2021-04-29 13:01:09
 * @LastEditors: Mok.CH
 * @LastEditTime: 2021-05-18 10:31:25
 * @FilePath: \sverp\app\webApi\controller\Pd.php
 */
namespace app\webApi\controller;
use app\webApi\model\Pd as PdModel;

class Pd
{
    /**
     * 获取制单人
     */
    public function getCruInfo()
    {
        return json((new PdModel)->getCruInfo());
    }

    /**
     * 根据条件获取订单列表
     */
    public function getOrderList()
    {
     
        $search_options = [
            'sign_status' => input('sign_status'),
            'commit_status' => input('commit_status'),
            'pro_time_star' => input('pro_time_star'),
            'pro_time_end' => input('pro_time_end'),
            'sg_time_star' => input('sg_time_star'),
            'comply_name' => input('comply_name'),
            'cru' => input('cru'),
            'Bt_No' => input('Bt_No'),
            'Ask_Nos' => input('Ask_Nos'),
            'sp_catName' => input('sp_catName'),
        ];
        
        return json((new PdModel)->getOrderList($search_options));
    }
}
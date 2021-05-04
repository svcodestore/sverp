<?php
/*
 * @Date: 2021-04-29 13:01:09
 * @LastEditors: Mok.CH
 * @LastEditTime: 2021-04-30 08:21:34
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
        $sign_status = input('sign_status');
        $where_str = '';
        if ($sign_status == '2') {
            $where_str = " and  (pa.State = '0' or pa.State = '1')  ";
        } else {
            //订单状态条件
            $where_str = " and  pa.State = '" . $sign_status . "'  ";
        }
        $commit_status = input('commit_status');
        if ($commit_status == '2') {
            $where_str .= " and  (pa.LkState = '0' or pa.LkState = '1')  ";
        } else {
            $where_str .= " and  pa.LkState = '$commit_status'  ";
        }

        //时间限制条件
        if (input('pro_time_star') and input('pro_time_end')) {
            $pro_time_star = input('pro_time_star');
            $pro_time_end = input('pro_time_end');
            $where_str .= " and pb.Due_Date >= '" . $pro_time_star . "'  and  pb.Due_Date <=  '" . $pro_time_end . "' ";
        }
        if (input('sg_time_star') and input('sg_time_end')) {
            $sg_time_star = input('sg_time_star');
            $sg_time_end = input('sg_time_end');
            $where_str .= " and  pb.SG_ASK_DATE >= '" . $sg_time_star . "'  and  pb.SG_ASK_DATE <=  '" . $sg_time_end . "' ";
        }

        $complay = input('comply_name');
        //公司条件
        if ($complay != 'nothing') {
            if ($complay === 'A020') {
                $where_str .= " and  (pa.PC_ID='$complay' or pa.PC_ID='' or pa.PC_ID is null) ";
            } else {
                $where_str .= " and  pa.PC_ID='" . input('comply_name') . "' ";
            }
        }

        //制单人
        if (input('cru') != 'nothing') {
            $get_cru = mb_convert_encoding(input('cru'), 'GBK', 'utf-8');
            $where_str .= " and  pa.CRU='" . $get_cru . "' ";
        }

        //采购单号条件
        if (input('Bt_No')) {
            $where_str .= " and pa.Bt_No = '" . input('Bt_No') . "' ";
        }

        //请购单号条件
        if (input('Ask_Nos')) {
            $where_str .= " and  pb.Ask_Nos like '%" . input('Ask_Nos') . "%' ";
        }
        
        return json((new PdModel)->getOrderList($where_str));
    }
}
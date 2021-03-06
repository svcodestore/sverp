<?php
/*
 * @Date: 2021-05-24 09:42:46
 * @LastEditors: yanbuw1911
 * @LastEditTime: 2021-07-10 11:17:07
 * @FilePath: /sverp/app/webApi/model/Order.php
 */

namespace app\webApi\model;

use PDO;
use think\facade\Log;

class Order
{
    protected $db;

    public function __construct()
    {
        $company = request()->param('company');
        $db_name = 'sdwx_sj';
        if ($company == 2) {
            $db_name = 'JStw';
        }

        $this->db = pdosqlsrv([
            'dsn' => 'sqlsrv:server=192.168.123.245,1433;Database=' . $db_name,
        ]);
    }

    protected function getData($condition = [])
    {
        $where_str = $this->getConditionSQL($condition);
        $SQL = "
        SELECT 
            smSOBPlus.smSOBPlusmyField12,
            smOType.SC_Name,
            erpKh.kh_No as keHuBianHao,
            smSOA.KhPONo as KhPONo,
            smSOA.OrdA_ID as OrdA_ID,
            smSOA.Bt_Date as dingDanShiJian,
            smSOA.Bt_No as xiaoShouDanHao,
            smSOB.OrdB_ID as OrdB_ID,
            smSOB.P_Qty as dingDanShuLiang,
            smSOB.Due_Date as jiHuaJiaoQi,
            smSOQuan.Ship_Qty as leiJiChuHuo,
            erpSp.sp_No as cunHuoBianHao,
            btBook.P_Qty AS danCiChuHuo,
            smShipmentA.Bt_No as chuHuoDanHao,
            smShipmentA.Bt_Date as danCiChuHuoShiJian
        FROM smSOA
        INNER JOIN smSOB ON smSOA.OrdA_ID = smSOB.OrdA_ID
        INNER JOIN smSOQuan ON smSOQuan.OrdB_ID = smSOB.OrdB_ID
        INNER JOIN smSOBPlus ON smSOBPlus.OrdB_ID = smSOB.OrdB_ID
        INNER JOIN smOType ON smOType.SC_ID = smSOA.SC_ID
        INNER JOIN erpKh ON erpKh.Kh_ID = smSOA.Kh_ID
        INNER JOIN erpSp ON erpSp.SP_ID = smSOB.SP_ID
        INNER JOIN btBook ON btBook.OrdA_ID = smSOA.OrdA_ID AND btBook.OrdB_ID = smSOB.OrdB_ID
        INNER JOIN smShipmentA ON smShipmentA.Bt_ID = btBook.Bt_ID
        WHERE 1=1
            {$where_str}
        ";
        return $this->db->query($SQL)->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function getConditionSQL($condition = [])
    {
        $where_str = '';
        if (!empty($condition)) {
            if (!empty($condition['customOrderNum']))
                $where_str .= " and smSOA.KhPONo = '" . $condition['customOrderNum'] . "' ";

            if (!empty($condition['storageGoodsNum']))
                $where_str .= " and erpSp.sp_No like '" . $condition['storageGoodsNum'] . "%' ";

            if (!empty($condition['khGoodsNum']))
                $where_str .= " and smSOBPlus.smSOBPlusmyField12 like '" . $condition['khGoodsNum'] . "%' ";
        }

        return $where_str;
    }


    public function getOrders($search_condition = [])
    {
        $step1Data = $this->getData($search_condition);

        $result = [];
        foreach ($step1Data as $k => $v) {
            // 优化旧系统的多次数据库查询操作，兼容以前的结果结构 (1 对 多)
            $_temp = [
                'danCiChuHuo' => substr($v['danCiChuHuo'], 0, -5),
                'chuHuoDanHao' => $v['chuHuoDanHao'],
                'danCiChuHuoShiJian' => substr($v['danCiChuHuoShiJian'], 0, 10),
            ];

            $step1Data[$k]['SC_Name'] = mb_convert_encoding($v['SC_Name'], 'utf-8', 'GBK');
            $step1Data[$k]['cunHuoBianHao'] = mb_convert_encoding($v['cunHuoBianHao'], 'utf-8', 'GBK');
            $step1Data[$k]['dingDanShiJian'] = substr($v['dingDanShiJian'], 0, 10);
            $step1Data[$k]['jiHuaJiaoQi'] = substr($v['jiHuaJiaoQi'], 0, 10);
            $step1Data[$k]['dingDanShuLiang'] = substr($v['dingDanShuLiang'], 0, -5);
            $step1Data[$k]['leiJiChuHuo'] = substr($v['leiJiChuHuo'], 0, -5);
            $step1Data[$k]['cunHuoBianHao'] = substr($step1Data[$k]['cunHuoBianHao'], 0, 6);

            if (strpos($v['smSOBPlusmyField12'], '-')) {
                $step1Data[$k]['smSOBPlusmyField12'] = substr($v['smSOBPlusmyField12'], 0, strpos($v['smSOBPlusmyField12'], '-'));
            }

            // 利用可标识信息创建相同订单标识符
            $uni_key = md5(
                $v['keHuBianHao'] .
                    $v['KhPONo'] .
                    $v['xiaoShouDanHao'] .
                    $v['SC_Name'] .
                    $v['dingDanShiJian'] .
                    $v['jiHuaJiaoQi'] .
                    $v['cunHuoBianHao'] .
                    $v['smSOBPlusmyField12']
            );

            // 整合相同订单，分配出货的信息
            if (array_key_exists($uni_key, $result)) {
                $result[$uni_key]['lessTableInfo'][] = $_temp;
                $result[$uni_key]['lessTableMany'] = count($result[$uni_key]['lessTableInfo']);
                continue;
            }

            $step1Data[$k]['lessTableInfo'][] = $_temp;
            $step1Data[$k]['lessTableMany'] = count($step1Data[$k]['lessTableInfo']);
            $result[$uni_key] = $step1Data[$k];
        }

        $result = array_values($result);

        $step1Data = $result;

        $result_info_last = array();
        foreach ($step1Data as $key => $value) {
            $result_info_last[$key]['keHuBianHao'] = $value['keHuBianHao'];
            $result_info_last[$key]['KhPONo'] = $value['KhPONo'];
            $result_info_last[$key]['xiaoShouDanHao'] = $value['xiaoShouDanHao'];
            $result_info_last[$key]['SC_Name'] = $value['SC_Name'];
            $result_info_last[$key]['dingDanShiJian'] = $value['dingDanShiJian'];
            $result_info_last[$key]['jiHuaJiaoQi'] = $value['jiHuaJiaoQi'];
            $result_info_last[$key]['cunHuoBianHao'] = $value['cunHuoBianHao'];
            $result_info_last[$key]['smSOBPlusmyField12'] = $value['smSOBPlusmyField12'];
        }

        $result_info_true = array();
        foreach ($result_info_last as $key => $value) {
            $result_info_true[] = implode('_', $value);
        }

        $result_info_true = array_unique($result_info_true);

        $last_true_info = array();
        foreach ($result_info_true as $key => $value) {
            $tmpArray = explode('_', $value);
            $last_true_info[] = array(
                'keHuBianHao' => $tmpArray[0],
                'KhPONo' => $tmpArray[1],
                'xiaoShouDanHao' => $tmpArray[2],
                'SC_Name' => $tmpArray[3],
                'dingDanShiJian' => $tmpArray[4],
                'jiHuaJiaoQi' => $tmpArray[5],
                'cunHuoBianHao' => $tmpArray[6],
                'smSOBPlusmyField12' => $tmpArray[7],
                'dingDanShuLiang' => 0,
                'leiJiChuHuo' => 0,
                'lessTableInfo' => array()
            );
        }

        // 合并 同项， 推出 计划交期2, 暂时看不出实际用处
        // $total = count($last_true_info);
        // foreach($last_true_info as $key=>$item) {
        //     $index = $key + 1;
        //     // 最后一个元素
        //     if($index >= $total) {
        //         if(!empty($last_true_info[$index-1]['KhPONo'])) {
        //             $last_true_info[$key]['jiHuaJiaoQi2'] = '';
        //         }
        //         break;
        //     }
        //     $last_true_info[$key]['jiHuaJiaoQi2'] = '';

        //     // 下一个元素
        //     $tmp_info = $last_true_info[$index];
        //     /*
        //     keHuBianHao 1
        //     KhPONo
        //     xiaoShouDanHao 1
        //     SC_Name
        //     dingDanShiJian
        //     jiHuaJiaoQi
        //     cunHuoBianHao
        //     smSOBPlusmyField12
        //     */

        //     // 判断是否同一个客户，同一订单
        //     if($item['SC_Name'] == $tmp_info['SC_Name'] &&
        //         $item['dingDanShiJian'] == $tmp_info['dingDanShiJian'] &&
        //         $item['KhPONo'] == $tmp_info['KhPONo'] &&
        //         $item['smSOBPlusmyField12'] == $tmp_info['smSOBPlusmyField12'] &&
        //         $item['cunHuoBianHao'] == $tmp_info['cunHuoBianHao']
        //     ) {
        //         // 如果相同， 记录下一个计划交期， 并删除下一个元素
        //         $last_true_info[$key]['jiHuaJiaoQi2'] = $last_true_info[$index]['jiHuaJiaoQi'];
        //         unset($last_true_info[$index]);
        //     }
        // }
        // var_dump($last_true_info);exit;

        // 订单数量、 累计出货 求和， 合并 出货批次数据
        foreach ($last_true_info as $key => $value) {
            foreach ($step1Data as $k => $v) {
                if (
                    $value['KhPONo'] == $v['KhPONo'] &&
                    $value['cunHuoBianHao'] == substr($v['cunHuoBianHao'], 0, 6) &&
                    $value['SC_Name'] == $v['SC_Name']
                ) {
                    $last_true_info[$key]['dingDanShuLiang'] += intval($v['dingDanShuLiang']);
                    $last_true_info[$key]['leiJiChuHuo'] += intval($v['leiJiChuHuo']);
                    foreach ($v['lessTableInfo'] as $tk => $tv) {
                        $last_true_info[$key]['lessTableInfo'][] = $tv;
                    }
                }
            }
        }


        foreach ($last_true_info as $key => $value) {
            if (!empty($value['lessTableInfo'])) {
                foreach ($value['lessTableInfo'] as $sk => $sv) {
                    $last_true_info[$key]['lessTableInfoTrue'][$sv['chuHuoDanHao']][] = $sv;
                }
            } else {
                $last_true_info[$key]['lessTableInfoTrue'] = array();
            }
        }

        foreach ($last_true_info as $key => $value) {
            if (!empty($value['lessTableInfoTrue'])) {
                foreach ($value['lessTableInfoTrue'] as $sk => $sv) {
                    $sumNum = 0;
                    for ($i = 0; $i < count($sv); $i++) {
                        $sumNum += $sv[$i]['danCiChuHuo'];
                    }
                    $last_true_info[$key]['lastTrueLessInfo'][] = array(
                        'chuHuoDanHao' => $sv[0]['chuHuoDanHao'],
                        'danCiChuHuoShiJian' => $sv[0]['danCiChuHuoShiJian'],
                        'danCiChuHuo' => $sumNum,
                    );
                }
            } else {
                $last_true_info[$key]['lastTrueLessInfo'] = array();
            }
        }

        return $last_true_info;
    }


    public function orderDetailList($search_condition)
    {
        $result_info_tmp = $this->getData($search_condition);
        $result = [];
        foreach ($result_info_tmp as $key => $value) {
            // 优化旧系统的多次数据库查询操作，兼容以前的结果结构 (1 对 多)
            $_temp = [
                'danCiChuHuo' => substr($value['danCiChuHuo'], 0, -5),
                'chuHuoDanHao' => $value['chuHuoDanHao'],
                'danCiChuHuoShiJian' => substr($value['danCiChuHuoShiJian'], 0, 10),
            ];

            // 利用可标识信息创建相同订单标识符
            $uni_key = md5(
                $value['keHuBianHao'] .
                    $value['KhPONo'] .
                    $value['xiaoShouDanHao'] .
                    $value['SC_Name'] .
                    $value['dingDanShiJian'] .
                    $value['jiHuaJiaoQi'] .
                    $value['cunHuoBianHao'] .
                    $value['smSOBPlusmyField12']
            );

            // 整合相同订单，分配出货的信息
            if (array_key_exists($uni_key, $result)) {
                $result[$uni_key]['lessTableInfo'][] = $_temp;
                $result[$uni_key]['lessTableMany'] = count($result[$uni_key]['lessTableInfo']);
                continue;
            }

            // 只在第一次标识时，进行基本信息的转码 截取 操作
            $result_info_tmp[$key]['lessTableInfo'][] = $_temp;
            $result_info_tmp[$key]['SC_Name'] = mb_convert_encoding($value['SC_Name'], 'utf-8', 'GBK');
            $result_info_tmp[$key]['cunHuoBianHao'] = mb_convert_encoding($value['cunHuoBianHao'], 'utf-8', 'GBK');
            $result_info_tmp[$key]['dingDanShiJian'] = substr($value['dingDanShiJian'], 0, 10);
            $result_info_tmp[$key]['jiHuaJiaoQi'] = substr($value['jiHuaJiaoQi'], 0, 10);
            $result_info_tmp[$key]['dingDanShuLiang'] = substr($value['dingDanShuLiang'], 0, -5);
            $result_info_tmp[$key]['leiJiChuHuo'] = substr($value['leiJiChuHuo'], 0, -5);
            $result_info_tmp[$key]['lessTableMany'] = count($result_info_tmp[$key]['lessTableInfo']);
            $result[$uni_key] = $result_info_tmp[$key];
        }

        //去掉key 保证json出来是Array而不是Object
        $result = array_values($result);
        return $result;
    }

    /**
     * @param string $KhPONo 客户单号
     * @param string $sp_No 存货编号
     * @param string $khNo 客商编号
     * @param string $company 公司地点
     * @decription: 直接从数据库获取订单, 不传参数条件返回空数组
     */
    public function getOrders2(string $KhPONo, string $sp_No, string $khNo, string $company): array
    {
        // edited by yangwenbo at 2021/6/30
        if (!$KhPONo && !$sp_No && !$khNo) return [];

        $db_name = 'sdwx_sj';
        if ($company == 2) {
            $db_name = 'JStw';
        }

        $db = pdosqlsrv([
            'dsn' => 'sqlsrv:server=192.168.123.245,1433;Database=' . $db_name,
        ]);

        $cond = "";
        $KhPONo && ($cond .= "and smSOA.KhPONo = '$KhPONo'");
        $sp_No && ($cond .= "and erpSp.sp_No like '$sp_No%'");
        $khNo && ($cond .= "and smSOBPlus.smSOBPlusmyField12 like '$khNo%'");

        $sql = "select t.smSOBPlusmyField12,
                        t.SC_Name,
                        t.cunHuoBianHao,
                        t.keHuBianHao,
                        t.KhPONo,
                        t.dingDanShiJian,
                        t.xiaoShouDanHao,
                        t.dingDanShuLiang,
                        t.jiHuaJiaoQi,
                        t.chuHuoDanHao,
                        t.leiJiChuHuo,
                        t.chuHuoDanHao,
                        t.danCiChuHuoShiJian,
                        sum(t.danCiChuHuo) as danCiChuHuo
                from (select a.smSOBPlusmyField12,
                            a.SC_Name,
                            a.keHuBianHao,
                            a.KhPONo,
                            a.dingDanShiJian,
                            a.xiaoShouDanHao,
                            a.dingDanShuLiang,
                            a.jiHuaJiaoQi,
                            a.cunHuoBianHao,
                            a.leiJiChuHuo,
                            b.chuHuoDanHao,
                            b.danCiChuHuoShiJian,
                            b.danCiChuHuo
                    from (
                                select a.*, b.OrdB_ID
                                from (
                                        select substring(smSOBPlus.smSOBPlusmyField12, 0, 9) as smSOBPlusmyField12,
                                                smOType.SC_Name,
                                                erpKh.kh_No                                   as keHuBianHao,
                                                smSOA.KhPONo,
                                                smSOA.Bt_Date                                 as dingDanShiJian,
                                                smSOA.Bt_No                                   as xiaoShouDanHao,
                                                smSOA.OrdA_ID,
                                                --                                     smSOB.OrdB_ID,
                                                sum(smSOB.P_Qty)                              as dingDanShuLiang,
                                                smSOB.Due_Date                                as jiHuaJiaoQi,
                                                substring(erpSp.sp_No, 0, 7)                  as cunHuoBianHao,
                                                sum(smSOQuan.Ship_Qty)                        as leiJiChuHuo
                                        from smSOBPlus,
                                            smSOQuan,
                                            smOType,
                                            erpKh,
                                            smSOA,
                                            smSOB,
                                            erpSp
                                        where smSOBPlus.OrdB_ID = smSOB.OrdB_ID
                                        and smSOQuan.OrdB_ID = smSOB.OrdB_ID
                                        and smOType.SC_ID = smSOA.SC_ID
                                        and erpKh.Kh_ID = smSOA.Kh_ID
                                        and smSOA.OrdA_ID = smSOB.OrdA_ID
                                        and erpSp.SP_ID = smSOB.SP_ID
                                        {$cond}
                                        group by smOType.SC_Name,
                                                erpKh.kh_No,
                                                smSOA.KhPONo,
                                                smSOA.Bt_Date,
                                                smSOA.Bt_No,
                                                smSOA.OrdA_ID,
                                                smSOB.Due_Date,
                                                substring(erpSp.sp_No, 0, 7),
                                                substring(smSOBPlus.smSOBPlusmyField12, 0, 9)
                                    ) as a,
                                    (select substring(smSOBPlus.smSOBPlusmyField12, 0, 9) as smSOBPlusmyField12,
                                            smSOA.KhPONo,
                                            smSOB.OrdB_ID,
                                            substring(erpSp.sp_No, 0, 7)                  as cunHuoBianHao
                                    from smSOBPlus,
                                        smSOQuan,
                                        smOType,
                                        erpKh,
                                        smSOA,
                                        smSOB,
                                        erpSp
                                    where smSOBPlus.OrdB_ID = smSOB.OrdB_ID
                                        and smSOQuan.OrdB_ID = smSOB.OrdB_ID
                                        and smOType.SC_ID = smSOA.SC_ID
                                        and erpKh.Kh_ID = smSOA.Kh_ID
                                        and smSOA.OrdA_ID = smSOB.OrdA_ID
                                        and erpSp.SP_ID = smSOB.SP_ID
                                        {$cond}
                                    ) as b
                                where a.KhPONo = b.KhPONo
                                and a.smSOBPlusmyField12 = b.smSOBPlusmyField12
                                and a.cunHuoBianHao = b.cunHuoBianHao
                            ) as a
                                left join (select btBook.OrdA_ID      as oaid,
                                                btBook.OrdB_ID      as obid,
                                                smShipmentA.Bt_No   as chuHuoDanHao,
                                                smShipmentA.Bt_Date as danCiChuHuoShiJian,
                                                btBook.P_Qty        as danCiChuHuo
                                        from smShipmentA,
                                                btBook
                                        where smShipmentA.Bt_ID = btBook.Bt_ID
                                            and btBook.BT_CODE = 'smShip') as b on b.oaid = a.OrdA_ID and b.obid = a.OrdB_ID) as t
                group by t.smSOBPlusmyField12,
                        t.SC_Name,
                        t.cunHuoBianHao,
                        t.keHuBianHao,
                        t.KhPONo,
                        t.dingDanShiJian,
                        t.xiaoShouDanHao,
                        t.dingDanShuLiang,
                        t.jiHuaJiaoQi,
                        t.chuHuoDanHao,
                        t.leiJiChuHuo,
                        t.chuHuoDanHao,
                        t.danCiChuHuoShiJian
                order by 
                        t.dingDanShiJian desc
                        ";

        $result = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    public function syncPrice(string $KhPONo, string $sp_No, string $khNo, string $company, string $price)
    {
        $db_name = 'sdwx_sj';
        if ($company == 2) {
            $db_name = 'JStw';
        }

        $db = pdosqlsrv([
            'dsn' => 'sqlsrv:server=192.168.123.245,1433;Database=' . $db_name,
        ]);

        $cond = "";
        $KhPONo && ($cond .= "and smSOA.KhPONo = '$KhPONo'");
        $sp_No && ($cond .= "and erpSp.sp_No like '$sp_No%'");
        $khNo && ($cond .= "and smSOBPlus.smSOBPlusmyField12 like '$khNo%'");

        $sql = "select t.cunHuoBianHao, t.SP_ID, t.P_Price, t.tP_Price, t.old_P_Price, t.old_tP_Price
                from (select a.cunHuoBianHao,
                            d.SP_ID,
                            d.P_Price,
                            d.tP_Price,
                            d.FromDate,
                            c.Bt_Date,
                            b.P_Price  as old_P_Price,
                            b.tP_Price as old_tP_Price
                    from (select t.smSOBPlusmyField12,
                                t.OrdB_ID,
                                t.SC_Name,
                                t.cunHuoBianHao,
                                t.keHuBianHao,
                                t.KhPONo,
                                t.dingDanShiJian,
                                t.xiaoShouDanHao,
                                t.dingDanShuLiang,
                                t.jiHuaJiaoQi,
        --                         t.chuHuoDanHao,
                                t.leiJiChuHuo,
                                t.chuHuoDanHao,
                                t.danCiChuHuoShiJian,
                                sum(t.danCiChuHuo) as danCiChuHuo
                            from (select a.smSOBPlusmyField12,
                                        a.SC_Name,
                                        a.keHuBianHao,
                                        a.KhPONo,
                                        a.dingDanShiJian,
                                        a.xiaoShouDanHao,
                                        a.dingDanShuLiang,
                                        a.jiHuaJiaoQi,
                                        a.cunHuoBianHao,
                                        a.leiJiChuHuo,
                                        b.chuHuoDanHao,
                                        b.danCiChuHuoShiJian,
                                        b.danCiChuHuo,
                                        a.OrdB_ID
                                from (
                                        select a.*, b.OrdB_ID
                                        from (
                                                    select substring(smSOBPlus.smSOBPlusmyField12, 0, 11) as smSOBPlusmyField12,
                                                        smOType.SC_Name,
                                                        erpKh.kh_No                                   as keHuBianHao,
                                                        smSOA.KhPONo,
                                                        smSOA.Bt_Date                                 as dingDanShiJian,
                                                        smSOA.Bt_No                                   as xiaoShouDanHao,
                                                        smSOA.OrdA_ID,
                                                        --                                     smSOB.OrdB_ID,
                                                        sum(smSOB.P_Qty)                              as dingDanShuLiang,
                                                        smSOB.Due_Date                                as jiHuaJiaoQi,
                                                        substring(erpSp.sp_No, 0, 7)                  as cunHuoBianHao,
                                                        sum(smSOQuan.Ship_Qty)                        as leiJiChuHuo
                                                    from smSOBPlus,
                                                        smSOQuan,
                                                        smOType,
                                                        erpKh,
                                                        smSOA,
                                                        smSOB,
                                                        erpSp
                                                    where smSOBPlus.OrdB_ID = smSOB.OrdB_ID
                                                    and smSOQuan.OrdB_ID = smSOB.OrdB_ID
                                                    and smOType.SC_ID = smSOA.SC_ID
                                                    and erpKh.Kh_ID = smSOA.Kh_ID
                                                    and smSOA.OrdA_ID = smSOB.OrdA_ID
                                                    and erpSp.SP_ID = smSOB.SP_ID
                                                    {$cond}
                                                    group by smOType.SC_Name,
                                                            erpKh.kh_No,
                                                            smSOA.KhPONo,
                                                            smSOA.Bt_Date,
                                                            smSOA.Bt_No,
                                                            smSOA.OrdA_ID,
                                                            smSOB.Due_Date,
                                                            substring(erpSp.sp_No, 0, 7),
                                                            substring(smSOBPlus.smSOBPlusmyField12, 0, 11)
                                                ) as a,
                                                (select substring(smSOBPlus.smSOBPlusmyField12, 0, 11) as smSOBPlusmyField12,
                                                        smSOA.KhPONo,
                                                        smSOB.OrdB_ID,
                                                        substring(erpSp.sp_No, 0, 7)                  as cunHuoBianHao
                                                from smSOBPlus,
                                                    smSOQuan,
                                                    smOType,
                                                    erpKh,
                                                    smSOA,
                                                    smSOB,
                                                    erpSp
                                                where smSOBPlus.OrdB_ID = smSOB.OrdB_ID
                                                and smSOQuan.OrdB_ID = smSOB.OrdB_ID
                                                and smOType.SC_ID = smSOA.SC_ID
                                                and erpKh.Kh_ID = smSOA.Kh_ID
                                                and smSOA.OrdA_ID = smSOB.OrdA_ID
                                                and erpSp.SP_ID = smSOB.SP_ID
                                                {$cond}
                                                ) as b
                                        where a.KhPONo = b.KhPONo
                                            and a.smSOBPlusmyField12 = b.smSOBPlusmyField12
                                            and a.cunHuoBianHao = b.cunHuoBianHao
                                    ) as a
                                        left join (select btBook.OrdA_ID      as oaid,
                                                            btBook.OrdB_ID      as obid,
                                                            smShipmentA.Bt_No   as chuHuoDanHao,
                                                            smShipmentA.Bt_Date as danCiChuHuoShiJian,
                                                            btBook.P_Qty        as danCiChuHuo
                                                    from smShipmentA,
                                                        btBook
                                                    where smShipmentA.Bt_ID = btBook.Bt_ID
                                                        and btBook.BT_CODE = 'smShip') as b
                                                    on b.oaid = a.OrdA_ID and b.obid = a.OrdB_ID) as t
                            group by t.smSOBPlusmyField12,
                                    t.SC_Name,
                                    t.cunHuoBianHao,
                                    t.keHuBianHao,
                                    t.KhPONo,
                                    t.dingDanShiJian,
                                    t.xiaoShouDanHao,
                                    t.dingDanShuLiang,
                                    t.jiHuaJiaoQi,
        --                         t.chuHuoDanHao,
                                    t.leiJiChuHuo,
                                    t.chuHuoDanHao,
                                    t.danCiChuHuoShiJian,
                                    t.OrdB_ID
                            having leiJiChuHuo = '0'
                            and dingDanShiJian between convert(datetime, '2021-01-01 00:00:00.000') and convert(datetime, '2021-05-01 00:00:00.000')
                        ) as a,
                        smSOB as b,
                        smSOA as c,
                        smPrice as d
                    where a.OrdB_ID = b.OrdB_ID
                        and b.OrdA_ID = c.OrdA_ID
                        and b.SP_ID = d.SP_ID) as t
                where FromDate < Bt_Date";

        $result = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $SP_IDS = implode(',', array_map(function ($e) {
            return "'{$e['SP_ID']}'";
        }, $result));

        dump($SP_IDS);
        $sql = "update smSOB set P_Price = '{$price}', tP_Price = '{$price}' where SP_ID in ({$SP_IDS})";
        $r = $db->exec($sql);

        return $r;
    }
}

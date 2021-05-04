<?php
/*
 * @Date: 2021-04-29 13:03:41
 * @LastEditors: Mok.CH
 * @LastEditTime: 2021-04-29 15:09:07
 * @FilePath: \sverp\app\webApi\model\Pd.php
 */
namespace app\webApi\model;

use PDO;

class Pd
{
    protected $db;

    public function __construct()
    {
        $this->db = new PDO('odbc:Driver={SQL Server};Server=192.168.123.245,1433;Database=sdwx_sj', 'sa', 'Sql_2008');
    }

    public function getCruInfo()
    {
        $cru_info = $this->db->query("select CRU from pmPOA group by CRU")->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($cru_info as $k=>$v) {
            $cru_info[$k]['CRU'] = mb_convert_encoding($v['CRU'], 'utf-8', 'GBK');
        }
        return $cru_info;
    }

    public function getOrderList(string $where_str)
    {
        
        $SQL = "SELECT TOP 100
                    pa.Bt_No,Sp_No,sp.Sp_Name,pb.P_Qty,pa.Bt_Date,pb.Due_Date,
                    kh.Kh_Name,pb.KhCfg_Date,pb.Khraw_date,ppq.TR_Qty,ppq.In_Qty,
                    pa.CRU,eu.Unit_Name,pa.State,pa.LkState,pa.OrdA_ID,pb.OrdB_ID,
                    pb.sCP_Nos,pa.PC_ID,pb.SG_ASK_DATE,pb.Ask_Nos,
                    (SELECT TOP 1 Bt_Date  from pmTRA where Bt_No=
                        (
                            SELECT TOP 1 Bt_No FROM btBook AS btB WHERE pa.OrdA_ID = btB.OrdA_ID AND pb.OrdB_ID = btB.OrdB_ID AND btB.BT_CODE = 'pmTR'
                        )
                    ) AS shouhuotime,
                    (SELECT TOP 1 Bt_Date  from pmWhgateA where Bt_No=
                        (
                            SELECT TOP 1 Bt_No FROM btBook AS btB WHERE pa.OrdA_ID = btB.OrdA_ID AND pb.OrdB_ID = btB.OrdB_ID AND btB.BT_CODE = 'pmIn_TR'
                        )
                    ) AS rukutime
                FROM
                    pmPOA as pa, pmPOB as pb, erpSp as sp, erpKh as kh, pmPOQuan as ppq, erpUnit as eu, erpspItem as spi, erpspClass as spc
                WHERE
                    pb.OrdA_ID=pa.OrdA_ID 
                    and sp.Sp_id=pb.SP_ID 
                    and sp.Sp_id=spi.SP_ID 
                    and spi.Cat_Id=spc.Cat_Id 
                    and spi.Grp_Id=spc.Grp_Id 
                    and spc.Cat_Id in (8726412,8726413,8726414,8726415,8726416,8726417,8726418,8726407,8726408) 
                    and kh.Kh_ID=pa.Kh_ID 
                    and ppq.OrdB_ID=pb.OrdB_ID 
                    and pb.Unit_id=eu.Unit_id 
                    $where_str
            ";
        $info = $this->db->query($SQL)->fetchAll(PDO::FETCH_ASSOC);
        
        $count = count($info);
        //输出前处理编码，小数点等格式问题
        for ($i = 0; $i < $count; $i++) {
            if (is_array($info[$i])) {
                $info[$i]['Sp_Name'] = mb_convert_encoding($info[$i]['Sp_Name'], 'utf-8', 'GBK');
                $info[$i]['P_Qty'] = substr($info[$i]['P_Qty'], 0, -3);
                $info[$i]['Bt_Date'] = substr($info[$i]['Bt_Date'], 0, 10);
                $info[$i]['Due_Date'] = substr($info[$i]['Due_Date'], 0, 10);	// 计划交期
                $info[$i]['Kh_Name'] = mb_convert_encoding($info[$i]['Kh_Name'], 'utf-8', 'GBK');
                $info[$i]['KhCfg_Date'] = substr($info[$i]['KhCfg_Date'], 0, 10);
                $info[$i]['shouhuotime'] = substr($info[$i]['shouhuotime'], 0, 10);
                $info[$i]['rukutime'] = substr($info[$i]['rukutime'], 0, 10);
                $info[$i]['TR_Qty'] = substr($info[$i]['TR_Qty'], 0, -3);
                $info[$i]['In_Qty'] = substr($info[$i]['In_Qty'], 0, -3);
                $info[$i]['CRU'] = mb_convert_encoding($info[$i]['CRU'], 'utf-8', 'GBK');
                $info[$i]['Unit_Name'] = mb_convert_encoding($info[$i]['Unit_Name'], 'utf-8', 'GBK');
                $info[$i]['sCP_Nos'] = implode('.', explode(';', mb_convert_encoding($info[$i]['sCP_Nos'], 'utf-8', 'GBK'))) . '.';

                switch ($info[$i]['PC_ID']) {
                    case 'A020':
                        $info[$i]['PC_ID'] = '斯达文星';
                        break;

                    case 'A021':
                        $info[$i]['PC_ID'] = '斯达富';
                        break;

                    case 'A022':
                        $info[$i]['PC_ID'] = '杰士通';
                        break;

                    default:
                        $info[$i]['PC_ID'] = '斯达文星';
                        break;
                }

                $changshangtime = strtotime($info[$i]['KhCfg_Date']);
                $shouhuotime = strtotime($info[$i]['shouhuotime']);
                if ($info[$i]['SG_ASK_DATE'] != 'no') {
                    $sg_ask_time = strtotime($info[$i]['SG_ASK_DATE']);
                    $diff_time = ($shouhuotime - $sg_ask_time) / 86400;
                    if ($diff_time < 1000 && $diff_time > -1000 && $sg_ask_time > 0 && $shouhuotime > 0) {
                        $info[$i]['diff_time'] = $diff_time;
                        if ($diff_time > 3 || $diff_time < 0) {
                            $info[$i]['diff_time_status'] = 1;
                        } else {
                            $info[$i]['diff_time_status'] = 0;
                        }
                    } else {
                        $info[$i]['diff_time'] = '缺少数据';
                        $info[$i]['diff_time_status'] = 1;
                    }
                } else {
                    $info[$i]['diff_time'] = '缺少数据';
                    $info[$i]['diff_time_status'] = 1;
                }
            }
        }
        
        return $info;
    }
}
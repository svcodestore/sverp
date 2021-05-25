<?php
/*
 * @Date: 2021-05-24 09:42:46
 * @LastEditors: Mok.CH
 * @LastEditTime: 2021-05-25 14:25:18
 * @FilePath: \sverp\app\webApi\model\Order.php
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
            'username' => 'sa',
            'password' => 'Sql_2008',
            // 'dsn' => 'sqlsrv:server=192.168.123.245,1433;Database=sdwx_sj;'
            'dsn' => 'odbc:Driver={SQL Server};Server=192.168.123.245,1433;Database='.$db_name,
        ]);
    }


    protected function step1Data($where_str= '')
    {
        $SQL = "SELECT
                smSOBPlus.smSOBPlusmyField12,
                smOType.SC_Name,
                erpKh.kh_No as keHuBianHao,
                smSOA.KhPONo,
                smSOA.Bt_Date as dingDanShiJian,
                smSOA.Bt_No as xiaoShouDanHao,
                smSOA.OrdA_ID,
                smSOB.OrdB_ID,
                smSOB.P_Qty as dingDanShuLiang,
                smSOB.Due_Date as jiHuaJiaoQi,
                erpSp.sp_No as cunHuoBianHao,
                smSOQuan.Ship_Qty as leiJiChuHuo
            FROM
                smSOBPlus,
                smSOQuan,
                smOType,
                erpKh,
                smSOA,
                smSOB,
                erpSp
            WHERE
                smSOBPlus.OrdB_ID = smSOB.OrdB_ID
                AND smSOQuan.OrdB_ID = smSOB.OrdB_ID
                AND smOType.SC_ID = smSOA.SC_ID
                AND erpKh.Kh_ID = smSOA.Kh_ID
                AND smSOA.OrdA_ID = smSOB.OrdA_ID
                AND erpSp.SP_ID = smSOB.SP_ID
                {$where_str}
        ";

        return $this->db->query($SQL)->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function step2Data($orderA_ID, $orderB_ID)
    {
        $SQL = "SELECT
                    smShipmentA.Bt_No as chuHuoDanHao,
                    smShipmentA.Bt_Date as danCiChuHuoShiJian,
                    btBook.P_Qty as danCiChuHuo
                FROM
                    smShipmentA,
                    btBook
                WHERE
                    smShipmentA.Bt_ID = btBook.Bt_ID
                    AND btBook.BT_CODE = 'smShip'
                    AND btBook.OrdA_ID = '{$orderA_ID}'
                    AND btBook.OrdB_ID = '{$orderB_ID}'
            ";

        return $this->db->query($SQL)->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function getConditionSQL($condition = [])
    {
        $where_str = '';
        if (!empty($condition)) {
            if (isset($condition['customOrderNum'])) 
                $where_str.=" and smSOA.KhPONo = '".$condition['customOrderNum']."' ";

            if (isset($condition['storageGoodsNum']))
                $where_str.=" and erpSp.sp_No like '".$condition['storageGoodsNum']."%' ";

            if (isset($condition['khGoodsNum']))
                $where_str.=" and smSOBPlus.smSOBPlusmyField12 like '".$condition['khGoodsNum']."%' ";
        }

        return $where_str;
    }


    public function getOrders($search_condition = [])
    {
        $where_str = $this->getConditionSQL($search_condition);
        $step1Data = $this->step1Data($where_str);

        foreach ($step1Data as $k=>$v) {
            $resultTmpInfo = $this->step2Data($v['OrdA_ID'], $v['OrdB_ID']);
            if(!empty($resultTmpInfo)){
                foreach($resultTmpInfo as $key=>$value){
                    $resultTmpInfo[$key]['danCiChuHuo']=substr($value['danCiChuHuo'],0,-5);
                    $resultTmpInfo[$key]['danCiChuHuoShiJian']=substr($value['danCiChuHuoShiJian'],0,10);
                }
                $step1Data[$k]['lessTableInfo']=$resultTmpInfo;
            } else {
                $step1Data[$k]['lessTableInfo']=array();
            }

            $step1Data[$k]['SC_Name']=mb_convert_encoding($v['SC_Name'],'utf-8','GBK');
            $step1Data[$k]['cunHuoBianHao']=mb_convert_encoding($v['cunHuoBianHao'],'utf-8','GBK');
            $step1Data[$k]['dingDanShiJian']=substr($v['dingDanShiJian'],0,10);
            $step1Data[$k]['jiHuaJiaoQi']=substr($v['jiHuaJiaoQi'],0,10);
            $step1Data[$k]['dingDanShuLiang']=substr($v['dingDanShuLiang'],0,-5);
            $step1Data[$k]['leiJiChuHuo']=substr($v['leiJiChuHuo'],0,-5);
            $step1Data[$k]['lessTableMany']=count($step1Data[$k]['lessTableInfo']);

            $step1Data[$k]['cunHuoBianHao'] = substr($step1Data[$k]['cunHuoBianHao'],0,6);

            if(strpos($v['smSOBPlusmyField12'],'-')){
                $step1Data[$k]['smSOBPlusmyField12']=substr($v['smSOBPlusmyField12'],0,strpos($v['smSOBPlusmyField12'],'-'));
            }
        }


        $result_info_last=array();
        foreach($step1Data as $key=>$value){
            $result_info_last[$key]['keHuBianHao']=$value['keHuBianHao'];
            $result_info_last[$key]['KhPONo']=$value['KhPONo'];
            $result_info_last[$key]['xiaoShouDanHao']=$value['xiaoShouDanHao'];
            $result_info_last[$key]['SC_Name']=$value['SC_Name'];
            $result_info_last[$key]['dingDanShiJian']=$value['dingDanShiJian'];
            $result_info_last[$key]['jiHuaJiaoQi']=$value['jiHuaJiaoQi'];
            $result_info_last[$key]['cunHuoBianHao']=substr($value['cunHuoBianHao'],0,6);
            if(strpos($value['smSOBPlusmyField12'],'-')){
            $result_info_last[$key]['smSOBPlusmyField12']=substr($value['smSOBPlusmyField12'],0,strpos($value['smSOBPlusmyField12'],'-'));
            }else{
            $result_info_last[$key]['smSOBPlusmyField12']=$value['smSOBPlusmyField12'];
            }
        }
        
        $result_info_true=array();
        foreach($result_info_last as $key=>$value){
            $result_info_true[]=implode('_',$value);
        }
        
        $result_info_true=array_unique($result_info_true);
        
        $last_true_info=array();
        foreach($result_info_true as $key=>$value){
            $tmpArray=explode('_',$value);
            $last_true_info[]=array(
                                    'keHuBianHao'=>$tmpArray[0],
                                    'KhPONo'=>$tmpArray[1],
                                    'xiaoShouDanHao'=>$tmpArray[2],
                                    'SC_Name'=>$tmpArray[3],
                                    'dingDanShiJian'=>$tmpArray[4],
                                    'jiHuaJiaoQi'=>$tmpArray[5],
                                    'cunHuoBianHao'=>$tmpArray[6],
                                    'smSOBPlusmyField12'=>$tmpArray[7],
                                    'dingDanShuLiang'=>0,
                                    'leiJiChuHuo'=>0,
                                    'lessTableInfo'=>array()
                                    );
        }

        $total = count($last_true_info);
        foreach($last_true_info as $key=>$item) {

            
            $index = $key + 1;
            if($index >= $total) {
                if(!empty($last_true_info[$index-1]['KhPONo'])) {
                    $last_true_info[$key]['jiHuaJiaoQi2'] = '';
                }
                break;
            }
            $last_true_info[$key]['jiHuaJiaoQi2'] = '';
            
            $tmp_info = $last_true_info[$index];

            if($item['SC_Name'] == $tmp_info['SC_Name'] &&
                $item['dingDanShiJian'] == $tmp_info['dingDanShiJian'] &&
                $item['KhPONo'] == $tmp_info['KhPONo'] &&
                $item['smSOBPlusmyField12'] == $tmp_info['smSOBPlusmyField12'] &&
                $item['cunHuoBianHao'] == $tmp_info['cunHuoBianHao']
            ) {
                $last_true_info[$key]['jiHuaJiaoQi2'] = $last_true_info[$index]['jiHuaJiaoQi'];
                unset($last_true_info[$index]);
            }
        }
        
        
        foreach($last_true_info as $key=>$value){
            foreach($step1Data as $k=>$v){
                if($value['KhPONo']==$v['KhPONo'] && $value['cunHuoBianHao']==substr($v['cunHuoBianHao'],0,6) && $value['SC_Name']==$v['SC_Name']){
                    $last_true_info[$key]['dingDanShuLiang']+=intval($v['dingDanShuLiang']);
                    $last_true_info[$key]['leiJiChuHuo']+=intval($v['leiJiChuHuo']);
                    foreach($v['lessTableInfo'] as $tk=>$tv){
                        $last_true_info[$key]['lessTableInfo'][]=$tv;
                    }
                }
            }
        }
        
        foreach($last_true_info as $key=>$value){
            if(!empty($value['lessTableInfo'])){
                foreach($value['lessTableInfo'] as $sk=>$sv){
                    $last_true_info[$key]['lessTableInfoTrue'][$sv['chuHuoDanHao']][]=$sv;
                }
            }else{
                $last_true_info[$key]['lessTableInfoTrue']=array();
            }
        }
        
        foreach($last_true_info as $key=>$value){
            if(!empty($value['lessTableInfoTrue'])){
            foreach($value['lessTableInfoTrue'] as $sk=>$sv){
                $sumNum=0;
                for($i=0;$i<count($sv);$i++){
                    $sumNum+=$sv[$i]['danCiChuHuo'];
                }
                $last_true_info[$key]['lastTrueLessInfo'][]=array(
                                                'chuHuoDanHao'=>$sv[0]['chuHuoDanHao'],
                                                'danCiChuHuoShiJian'=>$sv[0]['danCiChuHuoShiJian'],
                                                'danCiChuHuo'=>$sumNum,
                                                );
            }
            }else{
                $last_true_info[$key]['lastTrueLessInfo']=array();
            }
        }

        return $last_true_info;
    }


    public function orderDetailList($search_condition)
    {
        $where_str = $this->getConditionSQL($search_condition);
        $result_info_tmp = $this->step1Data($where_str);

		foreach($result_info_tmp as $key=>$value){
			$resultTmpInfo=$this->step2Data($value['OrdA_ID'], $value['OrdB_ID']);
			if(!empty($resultTmpInfo)){
                foreach($resultTmpInfo as $k=>$v){
                    $resultTmpInfo[$k]['danCiChuHuo']=substr($v['danCiChuHuo'],0,-5);
                    $resultTmpInfo[$k]['danCiChuHuoShiJian']=substr($v['danCiChuHuoShiJian'],0,10);
                }
                $result_info_tmp[$key]['lessTableInfo']=$resultTmpInfo;
                $result_info_tmp[$key]['lessTableMany']=count($resultTmpInfo);
                $result_info_tmp[$key]['SC_Name']=mb_convert_encoding($value['SC_Name'],'utf-8','GBK');
                $result_info_tmp[$key]['cunHuoBianHao']=mb_convert_encoding($value['cunHuoBianHao'],'utf-8','GBK');
                $result_info_tmp[$key]['dingDanShiJian']=substr($value['dingDanShiJian'],0,10);
                $result_info_tmp[$key]['jiHuaJiaoQi']=substr($value['jiHuaJiaoQi'],0,10);
                $result_info_tmp[$key]['dingDanShuLiang']=substr($value['dingDanShuLiang'],0,-5);
                $result_info_tmp[$key]['leiJiChuHuo']=substr($value['leiJiChuHuo'],0,-5);
			}else{
				$result_info_tmp[$key]['SC_Name']=mb_convert_encoding($value['SC_Name'],'utf-8','GBK');
				$result_info_tmp[$key]['cunHuoBianHao']=mb_convert_encoding($value['cunHuoBianHao'],'utf-8','GBK');
				$result_info_tmp[$key]['dingDanShiJian']=substr($value['dingDanShiJian'],0,10);
				$result_info_tmp[$key]['jiHuaJiaoQi']=substr($value['jiHuaJiaoQi'],0,10);
				$result_info_tmp[$key]['dingDanShuLiang']=substr($value['dingDanShuLiang'],0,-5);
				$result_info_tmp[$key]['leiJiChuHuo']=substr($value['leiJiChuHuo'],0,-5);
				$result_info_tmp[$key]['lessTableInfo']=array();
			}
		}
        return $result_info_tmp;
    }


}
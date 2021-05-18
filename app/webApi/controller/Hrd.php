<?php

declare(strict_types=1);
/*
* @Author: yanbuw1911
* @Date: 2021-01-07 14:15:16
 * @LastEditTime: 2021-05-18 15:05:45
 * @LastEditors: yanbuw1911
* @Description:
 * @FilePath: /sverp/app/webApi/controller/Hrd.php
*/

namespace app\webApi\controller;

use app\webApi\model\Hrd as ModelHrd;
use Prodigy_DBF;

class Hrd
{
    public function getMaterialCategory()
    {
        $rtn['result'] = true;
        $rtn['data'] = (new ModelHrd())->materialCategory();

        return json($rtn);
    }

    public function getMaterialList()
    {
        $categoryId = input('post.categoryId');

        $rtn['result'] = true;
        $rtn['data'] = (new ModelHrd())->materialList($categoryId);

        return json($rtn);
    }

    public function saveMaterialUsedOpt()
    {
        $opt = input();
        $rtn['result'] = (new ModelHrd())->handleMaterialUsedOpt($opt);

        return json($rtn);
    }

    public function getOutboundOrder()
    {
        $rtn['result'] = true;
        $rtn['data'] = (new ModelHrd())->outboundOrder();

        return json($rtn);
    }

    public function getIndividualOutboundOrder()
    {
        $usrid = input('post.usrid');
        $rtn['result'] = true;
        $rtn['data'] = (new ModelHrd())->individualOutboundOrder($usrid);

        return json($rtn);
    }

    public function undoOutbound()
    {
        $outboundId = input('post.outboundId');
        $rtn['result'] = (new ModelHrd())->undoOutbound($outboundId);

        return json($rtn);
    }

    public function getOutboundMaterialList()
    {
        $materialId = input('post.materialId');
        $rtn['result'] = true;
        $rtn['data'] = (new ModelHrd())->outboundMaterialList($materialId);

        return json($rtn);
    }

    public function delOutboundMaterial()
    {
        $id = input('post.id');
        $rtn['result'] = (new ModelHrd())->delOutboundMaterial($id);

        return json($rtn);
    }

    public function getMaterialLogListById()
    {
        $materialId = input('post.materialId');
        $rtn['result'] = true;
        $rtn['data'] = (new ModelHrd())->materialLogListById($materialId);

        return json($rtn);
    }

    public function getMaterialLogListByUserid()
    {
        $userid = input('post.userid');
        $rtn['result'] = true;
        $rtn['data'] = (new ModelHrd())->materialLogListByUserid($userid);

        return json($rtn);
    }

    public function setMaterialStock()
    {
        $params = input();
        $rtn['result'] = (new ModelHrd())->setMaterialStock($params['data'], $params['usr']);

        return json($rtn);
    }

    public function setOutboundMaterialOrder()
    {
        $params = input();
        $rtn['result'] = (new ModelHrd())->outboundMaterialOrder($params['data'], $params['usr']);

        return json($rtn);
    }

    public function approveOutbound()
    {
        $data = input();
        $rtn['result'] = (new ModelHrd())->outboundApprove($data);

        return json($rtn);
    }

    public function materialLogSoftDel()
    {
        $id = input('post.id');
        $materialId = input('post.materialId');
        $oprtQty = input('post.oprtQty');
        $usrid = input('post.usrid');
        $rtn['result'] = (new ModelHrd())->materialLogSoftDel($id, $materialId, $oprtQty, $usrid);

        return json($rtn);
    }

    public function updateKpiInfoWorkers()
    {
        $KpiInfoWorkers = input();
        $redis = phpredis();
        $cacheKey = 'KpiInfoWorkers';
        $currDate = date('Y-m-d H:i:s', time());
        $cacheData = serialize([$currDate => $KpiInfoWorkers]);

        $result = $redis->lpush($cacheKey, $cacheData) > 0 ?? false;
        return json(['result' => $result]);
    }

    public function getKpiInfoWorkers()
    {
        $redis = phpredis();
        $cacheKey = 'KpiInfoWorkers';
        $list = $redis->lrange($cacheKey, 0, 1);

        $data = [];
        if (count($list) > 0) {
            $lastKpiInfoWorkers = unserialize($list[0]);
            $data[array_keys($lastKpiInfoWorkers)[0]] = array_values($lastKpiInfoWorkers)[0];
        }

        return json($data);
    }

    public function getDepts()
    {
        $rtn['result'] = true;
        $rtn['data'] = (new ModelHrd)->depts();

        return json($rtn);
    }

    public function saveDeptsOpt()
    {
        $opt = input();
        $rtn['result'] = (new ModelHrd())->handleDeptsOpt($opt);

        return json($rtn);
    }

    public function getTitles()
    {
        $rtn['result'] = true;
        $rtn['data'] = (new ModelHrd)->titles();

        return json($rtn);
    }

    public function saveTitlesOpt()
    {
        $opt = input();
        $rtn['result'] = (new ModelHrd())->handleTitlesOpt($opt);

        return json($rtn);
    }

    public function getPositions()
    {
        $rtn['result'] = true;
        $rtn['data'] = (new ModelHrd)->positions();

        return json($rtn);
    }

    public function savePositionsOpt()
    {
        $opt = input();
        $rtn['result'] = (new ModelHrd())->handlePositionsOpt($opt);

        return json($rtn);
    }

    public function getRanks()
    {
        $rtn['result'] = true;
        $rtn['data'] = (new ModelHrd)->ranks();

        return json($rtn);
    }

    public function saveRanksOpt()
    {
        $opt = input();
        $rtn['result'] = (new ModelHrd())->handleRanksOpt($opt);

        return json($rtn);
    }

    public function getKpiScopes()
    {
        $rtn['result'] = true;
        $rtn['data'] = (new ModelHrd)->kpiScopes();

        return json($rtn);
    }

    public function saveKpiScopesOpt()
    {
        $opt = input();
        $rtn['result'] = (new ModelHrd())->handleKpiScopesOpt($opt);

        return json($rtn);
    }

    public function getStaffs()
    {
        $info = [];
        if (PHP_OS_FAMILY === 'Windows') {
            $loc = input('post.loc');
            $path = '//192.168.123.252/data$/database/';

            $fmt = function ($str) {
                $s = str_replace(' ', '', @iconv('GBK', 'UTF-8', $str));
                $lastByte = substr($s, -1);
                if (ord($lastByte) < 10 || ord($lastByte) === 92) {
                    return substr($s, 0, -1);
                }

                return  $s;
            };

            $handler = new Prodigy_DBF($path . 'employee.DBF', $path . 'employee.FPT');
            while (($Record = $handler->GetNextRecord(true))) {

                $row['positionRankCode'] = $Record['NBJB'];
                $row['name'] = $fmt($Record['NAME']) === false ? '' : $fmt($Record['NAME']);
                $row['dept'] =  $fmt($Record['DEPTS']) === false ? '' : $fmt($Record['DEPTS']);
                $row['grp'] = $fmt($Record['GRP']) === false ? '' : $fmt($Record['GRP']);
                $row['subGrp'] = $fmt($Record['GRPS']) === false ? '' : $fmt($Record['GRPS']);
                $row['position'] = $fmt($Record['POSITIONS']) === false ? '' : $fmt($Record['POSITIONS']);
                $row['isLeaveJob'] = $fmt($Record['RETIRETYPE']) === false ? '' : $fmt($Record['RETIRETYPE']);
                $row['joinDate'] = $fmt($Record['ENTERDATE']) === false ? '' : $fmt($Record['ENTERDATE']);
                $row['staffNo'] = $fmt($Record['WORKNO']) === false ? '' : $fmt($Record['WORKNO']);
                // $row['born'] = $fmt($Record['BORN']);
                // $row['education'] = $fmt($Record['DEGREE']);

                if ($row['isLeaveJob'] === '在职') {
                    switch ($loc) {
                        case 'SV':
                            if ($row['staffNo'] > 99) {
                                $info[] = $row;
                            }
                            break;
                        case 'JS':
                            if (!is_numeric($row['staffNo']) && substr($row['staffNo'], 0, 2) === 'JS') {
                                $info[] = $row;
                            }
                            break;
                    }
                }
            }
        }

        return json($info);
    }
}

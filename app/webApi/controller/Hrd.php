<?php

declare(strict_types=1);
/*
* @Author: yanbuw1911
* @Date: 2021-01-07 14:15:16
 * @LastEditTime: 2021-03-03 13:49:25
 * @LastEditors: yanbuw1911
* @Description:
 * @FilePath: /sverp/app/webApi/controller/Hrd.php
*/

namespace app\webApi\controller;

use app\webApi\model\Hrd as ModelHrd;

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
}

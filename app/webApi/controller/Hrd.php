<?php

declare(strict_types=1);
/*
* @Author: yanbuw1911
* @Date: 2021-01-07 14:15:16
 * @LastEditTime: 2021-01-21 13:10:14
 * @LastEditors: yanbuw1911
* @Description:
 * @FilePath: \backend\app\webApi\controller\Hrd.php
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

    public function getOutboundMaterialList()
    {
        $materialId = input('post.materialId');
        $rtn['result'] = true;
        $rtn['data'] = (new ModelHrd())->outboundMaterialList($materialId);

        return json($rtn);
    }

    public function getMaterialLogList()
    {
        $materialId = input('post.materialId');
        $rtn['result'] = true;
        $rtn['data'] = (new ModelHrd())->materialLogList($materialId);

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
}

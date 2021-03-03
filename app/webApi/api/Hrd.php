<?php

declare(strict_types=1);
/*
* @Author: yanbuw1911
* @Date: 2021-01-07 14:18:07
 * @LastEditTime: 2021-03-03 13:23:55
 * @LastEditors: yanbuw1911
* @Description:
 * @FilePath: /sverp/app/webApi/api/Hrd.php
*/

namespace app\webApi\api;

use app\BaseController;
use app\webApi\controller\Hrd as ControllerHrd;

class Hrd extends BaseController
{
    public function getMaterialCategory()
    {
        return (new ControllerHrd())->getMaterialCategory();
    }

    public function getMaterialList()
    {
        return (new ControllerHrd())->getMaterialList();
    }

    public function saveMaterialUsedOpt()
    {
        return (new ControllerHrd())->saveMaterialUsedOpt();
    }

    public function getOutboundOrder()
    {
        return (new ControllerHrd())->getOutboundOrder();
    }

    public function getIndividualOutboundOrder()
    {
        return (new ControllerHrd())->getIndividualOutboundOrder();
    }

    public function undoOutbound()
    {
        return (new ControllerHrd())->undoOutbound();
    }

    public function getOutboundMaterialList()
    {
        return (new ControllerHrd())->getOutboundMaterialList();
    }

    public function getMaterialLogListById()
    {
        return (new ControllerHrd())->getMaterialLogListById();
    }

    public function getMaterialLogListByUserid()
    {
        return (new ControllerHrd())->getMaterialLogListByUserid();
    }

    public function setMaterialStock()
    {
        return (new ControllerHrd())->setMaterialStock();
    }

    public function setOutboundMaterialOrder()
    {
        return (new ControllerHrd())->setOutboundMaterialOrder();
    }

    public function approveOutbound()
    {
        return (new ControllerHrd())->approveOutbound();
    }

    public function materialLogSoftDel()
    {
        return (new ControllerHrd())->materialLogSoftDel();
    }
}

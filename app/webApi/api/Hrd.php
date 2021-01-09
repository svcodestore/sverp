<?php

declare(strict_types=1);
/*
* @Author: yanbuw1911
* @Date: 2021-01-07 14:18:07
 * @LastEditTime: 2021-01-08 13:00:46
 * @LastEditors: yanbuw1911
* @Description:
 * @FilePath: \backend\app\webApi\api\Hrd.php
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

    public function getOutboundMaterial()
    {
        return (new ControllerHrd())->getOutboundMaterial();
    }
}

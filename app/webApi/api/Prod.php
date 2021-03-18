<?php
/*
 * @Author: yanbuw1911
 * @Date: 2020-11-18 15:07:48
 * @LastEditTime: 2021-03-18 14:57:50
 * @LastEditors: yanbuw1911
 * @Description: 
 * @FilePath: /sverp/app/webApi/api/Prod.php
 */

namespace app\webApi\api;

use app\BaseController;
use app\webApi\controller\Prod as ControllerProd;

class Prod
{
    public function getCalenderData()
    {
        return (new ControllerProd())->getCalenderData();
    }

    public function getProdSchdData()
    {
        return (new ControllerProd())->getProdSchdData();
    }

    public function syncProdSchdParam()
    {
        return (new ControllerProd())->syncProdSchdParam();
    }

    public function autoSchedule()
    {
        return (new ControllerProd())->autoSchedule();
    }

    public function getPrdSchdReport()
    {
        return (new ControllerProd())->getPrdSchdReport();
    }

    public function getPrdSchdParam()
    {
        return (new ControllerProd())->getPrdSchdParam();
    }

    public function saveCalenderOpt()
    {
        return (new ControllerProd())->saveCalenderOpt();
    }

    public function setWorktime()
    {
        return (new ControllerProd())->setWorktime();
    }

    public function getProdItemSubphases()
    {
        return (new ControllerProd())->getProdItemSubphases();
    }

    public function getAutoSchdParam()
    {
        return json((new ControllerProd())->getAutoSchdParam());
    }
}

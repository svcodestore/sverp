<?php
/*
* @Author: yanbuw1911
* @Date: 2021-06-10 09:34:49
 * @LastEditors: yanbuw1911
 * @LastEditTime: 2021-06-25 13:55:01
* @Description: Do not edit
 * @FilePath: /sverp/app/webApi/api/JstwCommute.php
*/

namespace app\webApi\api;

use app\BaseController;
use app\webApi\controller\JstwCommute as ControllerJstwCommute;

class JstwCommute extends BaseController
{
    public function getDetailedDeduction()
    {
        return (new ControllerJstwCommute)->getDetailedDeduction();
    }

    public function getDetailedDeductionByVersion()
    {
        return (new ControllerJstwCommute)->getDetailedDeductionByVersion();
    }

    public function getInsuranceDeduction()
    {
        return (new ControllerJstwCommute)->getInsuranceDeduction();
    }

    public function saveDetailedDeductionOpt()
    {
        return (new ControllerJstwCommute)->saveDetailedDeductionOpt();
    }

    public function saveInsuranceDeductionOpt()
    {
        return (new ControllerJstwCommute)->saveInsuranceDeductionOpt();
    }

    public function getDeductionVerions()
    {
        return (new ControllerJstwCommute)->getDeductionVerions();
    }
}

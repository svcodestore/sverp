<?php
/*
* @Author: yanbuw1911
* @Date: 2021-06-10 09:27:56
 * @LastEditors: yanbuw1911
 * @LastEditTime: 2021-06-10 09:34:25
* @Description: Do not edit
 * @FilePath: \sverp\app\webApi\controller\JstwCommute.php
*/

namespace app\webApi\controller;

use app\webApi\model\JstwCommute as ModelJstwCommute;

class JstwCommute
{
    public function getDetailedDeduction()
    {
        $rtn['result'] = true;
        $rtn['data'] = (new ModelJstwCommute)->detailedDeduction();

        return json($rtn);
    }

    public function getInsuranceDeduction()
    {
        $rtn['result'] = true;
        $rtn['data'] = (new ModelJstwCommute)->insuranceDeduction();

        return json($rtn);
    }

    public function saveDetailedDeductionOpt()
    {
        $opt = input();
        $rtn['result'] = (new ModelJstwCommute)->handleDetailedDeductionOpt($opt);

        return json($rtn);
    }

    public function saveInsuranceDeductionOpt()
    {
        $opt = input();
        $rtn['result'] = (new ModelJstwCommute)->handleInsuranceDeductionOpt($opt);

        return json($rtn);
    }
}

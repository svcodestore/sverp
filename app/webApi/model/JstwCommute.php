<?php
/*
* @Author: yanbuw1911
* @Date: 2021-06-10 09:15:31
 * @LastEditors: yanbuw1911
 * @LastEditTime: 2021-06-25 14:12:44
* @Description: Do not edit
 * @FilePath: /sverp/app/webApi/model/JstwCommute.php
*/

namespace app\webApi\model;

use think\facade\Db;

class JstwCommute
{
    private const DB = 'jstw_homedb';

    public function detailedDeduction(): array
    {
        $t = self::DB . ".jstw_commute_deduction";
        $res = Db::table($t)->select()->toArray();

        return $res;
    }

    public function detailedDeductionInVersion(string $time): array
    {
        $t = self::DB . ".jstw_commute_deduction";
        $res = Db::table($t)->where('jcd_created_at', $time)->select()->toArray();

        return $res;
    }

    public function handleDetailedDeductionOpt(array $opt): bool
    {
        $t =  self::DB . ".jstw_commute_deduction";
        return Common::handleOpt($t, $opt);
    }

    public function insuranceDeduction(): array
    {
        $t = self::DB . ".jstw_commute_insurance";
        $sql = "SELECT * FROM $t ORDER BY CAST( jci_section AS DECIMAL )";
        $res = Db::query($sql);

        return $res;
    }

    public function handleInsuranceDeductionOpt(array $opt): bool
    {
        $t =  self::DB . ".jstw_commute_insurance";
        return Common::handleOpt($t, $opt);
    }

    public function deductionVerions(): array
    {
        $t =  self::DB . ".jstw_commute_deduction";
        $sql = "SELECT DISTINCT jcd_created_at FROM $t";

        return Db::query($sql);
    }
}

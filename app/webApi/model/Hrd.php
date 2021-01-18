<?php

declare(strict_types=1);
/*
* @Author: yanbuw1911
* @Date: 2021-01-07 14:07:28
 * @LastEditTime: 2021-01-16 17:16:59
 * @LastEditors: yanbuw1911
* @Description:
 * @FilePath: \backend\app\webApi\model\Hrd.php
*/

namespace app\webApi\model;

use think\facade\Db;

class Hrd
{
    public function materialCategory(): array
    {
        $t = 'hrdlib_material_used';
        $cond = [
            'hmu_material_parent' => 0
        ];
        $res = Db::table($t)->where($cond)->select()->toArray();

        return $res;
    }

    public function materialList(string $categoryId = ''): array
    {
        if ($categoryId) {

            $sql = "SELECT
                    * 
                FROM
                    hrdlib_material_used 
                WHERE hmu_material_parent = '$categoryId'
                ORDER BY
                    CAST( hmu_material_stock AS DECIMAL ) DESC";
        } else {

            $sql = "SELECT
                        a.*,
                        b.in_stock,
                        b.out_stock 
                    FROM
                        hrdlib_material_used AS a
                        LEFT JOIN (
                        SELECT
                            *,
                            sum(
                            IF
                            ( hml_operate_type = 'put', hml_operate_qty, 0 )) AS in_stock,
                            sum(
                            IF
                            ( hml_operate_type = 'out', hml_operate_qty, 0 )) AS out_stock 
                        FROM
                            hrdlib_material_log 
                        GROUP BY
                            hml_material_id 
                        ) AS b ON a.id = b.hml_material_id 
                    WHERE
                        a.hmu_material_unit IS NOT NULL 
                    ORDER BY
                        CAST( a.hmu_material_stock AS DECIMAL ) DESC";
        }

        $res = Db::query($sql);

        return $res;
    }

    public function handleMaterialUsedOpt(array $opt): bool
    {
        $t = 'hrdlib_material_used';

        return Common::handleOpt($t, $opt);
    }

    public function outboundOrder(): array
    {
        $t   = 'hrdlib_outbound_order';
        $res = Db::table($t)
            ->alias('a')
            ->join('commonlib_dept_workline d', 'a.hoo_applicant=d.cdw_code')
            ->field(['a.*', 'd.cdw_name'])
            ->order('hoo_is_approved')
            ->select()
            ->toArray();

        return $res;
    }

    public function outboundMaterial(string $outboundId): array
    {
        $t      = 'hrdlib_outbound_material';
        $fields = ['a.*', 'm.hmu_material_name', 'm.hmu_material_code', 'm.hmu_material_model', 'm.hmu_material_unit'];
        $res    = Db::table($t)
            ->where('hom_outbound_id', $outboundId)
            ->alias('a')
            ->join('hrdlib_material_used m', 'a.hom_material_id=m.id')
            ->field($fields)
            ->select()
            ->toArray();

        return $res;
    }

    public function outboundApprove(string $outboundId): bool
    {
        $t   = 'hrdlib_outbound_order';
        $res = Db::table($t)->where('id', $outboundId)->update(['hoo_is_approvee' => 1]);

        return $res !== false;
    }

    public function materialLogList(string $materialId): array
    {
        $t = 'hrdlib_material_log';
        $res = Db::table($t)->where('hml_material_id', $materialId)->alias('a')->join('hrdlib_material_used b', 'a.hml_material_id=b.id')->order('a.hml_join_date', 'desc')->select()->toArray();

        return $res;
    }
}

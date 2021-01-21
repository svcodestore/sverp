<?php

declare(strict_types=1);
/*
* @Author: yanbuw1911
* @Date: 2021-01-07 14:07:28
 * @LastEditTime: 2021-01-21 14:19:11
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
                WHERE hmu_material_parent = '$categoryId' OR POSITION('$categoryId' in hmu_material_name)
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

    public function outboundMaterialList(string $outboundId): array
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

    public function setMaterialStock(array $data, string $usr): bool
    {
        Db::startTrans();

        if ($data['operType'] === 'put') {
            $sql = "UPDATE hrdlib_material_used 
                    SET hmu_material_stock = hmu_material_stock + ?,
                    hmu_material_modifier = ?
                    WHERE
                    id = ?";
        } else if ($data['operType'] === 'set') {
            $sql = "UPDATE hrdlib_material_used 
                    SET hmu_material_stock = ?,
                    hmu_material_modifier = ?
                    WHERE
                    id = ?";
        }

        $res = Db::execute($sql, [$data['qty'], $usr, $data['id']]);
        $flag = false !== $res;
        if ($flag) {
            $flag = $this->materialStockLog($data, $usr);
        }
        if ($flag) {
            Db::commit();
        } else {
            Db::rollback();
        }

        return $flag;
    }

    public function materialStockLog(array $data, string $usr): bool
    {
        $t   = 'hrdlib_material_log';
        $row = [
            'hml_material_id'  => $data['materialId'],
            'hml_operate_qty'  => $data['qty'],
            'hml_operate_type' => $data['operType'],
            'hml_creator'      => $usr,
        ];
        $res = Db::table($t)->insert($row);

        return false !== $res;
    }

    public function outboundMaterialOrder(array $data, array $usr): bool
    {
        Db::startTrans();

        $flag = false;

        $row = [
            'hoo_order_id' => $data['orderNo'],
            'hoo_applicant' => $usr['dept'],
            'hoo_creator' => $usr['name'],
            'hoo_modifier' => $usr['name']
        ];
        $t = 'hrdlib_outbound_order';
        $id = Db::table($t)->insertGetId($row);

        if ($id !== false) {
            $rows = array_map(function ($e) use ($id) {
                return [
                    'hom_outbound_id' => $id,
                    'hom_material_id' => $e['materialId'],
                    'hom_apply_qty' => $e['qty'],
                    'hom_out_qty' => $e['qty'],
                    'hom_remark' => $e['remark'],
                ];
            }, $data['applyList']);
            $t1 = 'hrdlib_outbound_material';
            $res = Db::table($t1)->insertAll($rows);

            if ($res !== false) {
                $logRows = array_map(function ($e) use ($usr) {
                    return [
                        'hml_material_id'  => $e['materialId'],
                        'hml_operate_qty'  => $e['qty'],
                        'hml_operate_type' => 'out',
                        'hml_creator'      => $usr['name'],
                    ];
                }, $data['applyList']);
                $t2 = 'hrdlib_material_log';
                $flag =  false !== Db::table($t2)->insertAll($logRows);
            }
        }

        if ($flag) {
            Db::commit();
        } else {
            Db::rollback();
        }

        return $flag;
    }

    public function outboundApprove(array $data): bool
    {
        Db::startTrans();

        $flag =  false;

        $t   = 'hrdlib_outbound_order';
        $res = Db::table($t)
            ->where('id', $data['outboundId'])
            ->update(['hoo_is_approvee' => 1]);

        $flag = $res !== false;
        if ($flag) {
            if (isset($data['modify'])) {
                $flag = Common::handleOpt('hrdlib_outbound_material', $data['modify']);
            }
        }
        if ($flag) {
            Db::commit();
        } else {
            Db::rollback();
        }

        return $flag;
    }

    public function materialLogList(string $materialId): array
    {
        $t   = 'hrdlib_material_log';
        $res = Db::table($t)
            ->where('hml_material_id', $materialId)
            ->alias('a')
            ->join('hrdlib_material_used b', 'a.hml_material_id=b.id')
            ->order('a.hml_join_date', 'desc')
            ->select()
            ->toArray();

        return $res;
    }
}

<?php
/*
* @Author: yanbuw1911
* @Date: 2020-12-14 16:50:20
 * @LastEditTime: 2020-12-18 09:15:15
 * @LastEditors: yanbuw1911
* @Description:
 * @FilePath: \backend\app\webApi\model\Common.php
*/

namespace app\webApi\model;

use think\facade\Db;

class Common
{
    /**
     * @description: 处理表的编辑
     * @param string $tableName 表名
     * @param array $opt 变更数据
     * @return bool
     */
    public static function handleOpt(string $tableName, array $opt): bool
    {
        Db::startTrans();
        $flag = true;
        foreach ($opt as $optype => $data) {
            if ($optype == 'A') {
                $flag  = $flag && false !== self::hanleInsert($tableName, $data);
            }

            if ($optype == 'U') {
                foreach ($data as $v) {
                    $pkVal = array_keys($v)[0];
                    $row   = array_values($v)[0];

                    // 如果是数值或数值字符串，则默认主键为 id
                    if (is_numeric($pkVal)) {
                        $pkVal = ['id' => $pkVal];

                        // 否则读取主键
                    } else {
                        $pkVal = json_decode($pkVal, true);
                    }

                    $flag  = $flag && false !== self::handleUpdate($tableName, $pkVal, $row);
                }
            }

            if ($optype == 'D') {
                $flag  = $flag && false !== self::handleDelete($tableName, $data['id']);
            }
        }
        if ($flag) {
            Db::commit();
        } else {
            Db::rollback();
        }

        return $flag;
    }

    protected static function hanleInsert(string $tableName, array $rows): bool
    {
        return false !== Db::table($tableName)->insertAll($rows);
    }

    protected static function handleUpdate(string $tableName, array $pk, array $row): bool
    {
        return false !== Db::table($tableName)->where($pk)->update($row);
    }

    protected static function handleDelete(string $tableName, array $pks): bool
    {
        return false !== Db::table($tableName)->delete($pks);
    }
}

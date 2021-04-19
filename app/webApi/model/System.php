<?php
namespace app\webApi\model;

use think\facade\Db;

class System
{
    public function conDepts()
    {
        $t = "starvc_syslib.syslib_group_dept";
        $sql = "SELECT id, sgd_code, sgd_alias FROM $t WHERE sgd_is_dept = 1";
        $res = Db::query($sql);

        return $res;
    }
}
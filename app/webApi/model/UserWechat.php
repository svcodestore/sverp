<?php

namespace app\webApi\model;

use think\facade\Db;

class UserWechat
{
    protected $table_name = 'starvc_syslib.syslib_user_wechat';

    public function getUser($con_id)
    {
        return Db::table($this->table_name)->where('con_id', '=', $con_id)->find();
    }

    public function saveInfo($con_id, $data)
    {
        if ($this->getUser($con_id)) {
            return Db::table($this->table_name)->where('con_id', '=', $con_id)->update($data);
        }
        return Db::table($this->table_name)->insert($data);
    }
}

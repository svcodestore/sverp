<?php
/*
 * @Author: yanbuw1911
 * @Date: 2020-11-04 14:19:26
 * @LastEditTime: 2020-12-31 08:54:54
 * @LastEditors: yanbuw1911
 * @Description: 
 * @FilePath: \backend\app\webApi\model\Login.php
 */

namespace app\webApi\model;

use think\facade\Db;

class Login
{
    public function validate($usr, $pwd): array
    {
        $db   = 'starvc_syslib';
        $t1   = "$db.syslib_user_home";
        $t2   = "$db.syslibmap_user_api";
        $cond = [
            'con_id'       => $usr,
            'con_password' => $pwd,
            'con_status'   => 1
        ];
        $res = Db::table($t1)
            ->where($cond)->alias('a')
            ->leftJoin("$t2 b", 'a.con_id=b.sua_usrid')
            ->field('a.*,b.sua_black_api')
            ->select()
            ->toArray();

        return $res;
    }

    public function login($usr, $pwd): array
    {
        $res = $this->validate($usr, $pwd);

        if (!empty($res)) {
            $now =  date('Y-m-d H:i:s', time());
            $loginip = $_SERVER['REMOTE_ADDR'];
            $res[0]['sue_last_login_time'] = $now;
            $res[0]['sue_last_loginip'] = $loginip;
            Db::startTrans();

            $db = 'starvc_syslib';
            $t = "$db.syslib_user_extra";
            $cond = ['sue_uid' => $res[0]['id']];
            $updateData = [
                'sue_last_login_time' => $now,
                'sue_last_loginip' => $loginip,
            ];

            $result = Db::name($t)
                ->where($cond)
                ->data($updateData)
                ->update();
            if ($result === 1) {
                Db::commit();
            } else {
                $insertFlag = Db::table($t)->insert(
                    [
                        'sue_uid' => $res[0]['id'],
                        'sue_last_loginip' => $loginip,
                        'sue_last_login_time' => $now
                    ]
                );
                if ($insertFlag === 1) {
                    Db::commit();
                } else {
                    Db::rollback();
                    return false;
                }
            }
        }

        return $res;
    }
}

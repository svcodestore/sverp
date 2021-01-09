<?php
/*
 * @Author: yanbuw1911
 * @Date: 2020-11-05 13:18:24
 * @LastEditTime: 2020-12-30 13:31:55
 * @LastEditors: yanbuw1911
 * @Description: 
 * @FilePath: \backend\app\webApi\model\Improve.php
 */

namespace app\webApi\model;

use think\facade\Db;

class Improve
{
    public function softwareRequire(): array
    {
        $db = 'starvc_imprvlib';
        $t = $db . '.imprvlib_soft_requr';
        $res = Db::table($t)->select()->toArray();

        return $res;
    }

    public function userFavirotePages(string $usrid): array
    {
        $db = 'starvc_imprvlib';
        $t = $db . '.imprvlib_fav_page';
        $cond = ['ifp_user_id' => $usrid, 'ifp_fav_status' => 1];

        $res = Db::table($t)->where($cond)->select()->toArray();

        return $res;
    }

    public function setUserFavirotePage(string $menuid, string $usrid): bool
    {
        $db = 'starvc_imprvlib';
        $t = $db . '.imprvlib_fav_page';

        $sql = "INSERT INTO $t ( ifp_menu_id, ifp_user_id, ifp_fav_status )
                            VALUES
                                ( '$menuid', '$usrid', 1 ) 
                                ON DUPLICATE KEY UPDATE ifp_fav_status = 1";
        $res = Db::execute($sql);

        return 0 !== $res;
    }

    public function rmUserFavirotePage(string $menuid, string $usrid): bool
    {
        $db = 'starvc_imprvlib';
        $t = $db . '.imprvlib_fav_page';
        $cond = ['ifp_menu_id' => $menuid, 'ifp_user_id' => $usrid];

        $res = Db::table($t)->where($cond)->update(['ifp_fav_status' => 0]);

        return 0 !== $res;
    }
}

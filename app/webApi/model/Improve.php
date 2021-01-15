<?php
/*
 * @Author: yanbuw1911
 * @Date: 2020-11-05 13:18:24
 * @LastEditTime: 2021-01-15 13:46:43
 * @LastEditors: yanbuw1911
 * @Description: 
 * @FilePath: \backend\app\webApi\model\Improve.php
 */

namespace app\webApi\model;

use think\facade\Db;

class Improve
{
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

    public function softwareRequire(): array
    {
        $db    = 'starvc_imprvlib';
        $t     = $db . '.imprvlib_soft_requr';
        $t2    = $db . '.imprvlib_soft_daychk';
        $t3    = "starvc_syslib.syslib_user_home";
        $today = date('Y-m-d', time());
        $sql   = "SELECT
                        a.*,
                        b.isd_isok 
                    FROM
                        (SELECT t1.*,t2.con_name AS approver FROM $t AS t1 LEFT JOIN $t3 AS t2 ON t1.isr_approver = t2.con_id) AS a
                        LEFT JOIN ( SELECT * FROM $t2 WHERE DATE_FORMAT( isd_chk_time, '%Y-%m-%d' ) = '$today' ) AS b ON a.id = b.isd_softid 
                    ORDER BY
                        a.isr_join_date DESC";
        $res   = Db::query($sql);

        return $res;
    }

    public function dailyCheckList(string $softid): array
    {
        $t = "starvc_imprvlib.imprvlib_soft_daychk";
        $t2 = "starvc_syslib.syslib_user_home";
        $res = Db::table($t)->alias('a')->where(['isd_softid' => $softid])->join("$t2 b", 'a.isd_checker=b.con_id')->field(['a.*', 'b.con_name'])->order('a.isd_chk_time')->select()->toArray();

        return $res;
    }

    public function handleSoftwareRequireOpt(array $opt): bool
    {
        $t = 'starvc_imprvlib.imprvlib_soft_requr';

        return Common::handleOpt($t, $opt);
    }

    public function auditRequire(string $softid, string $usrid): bool
    {
        $t = 'starvc_imprvlib.imprvlib_soft_requr';

        return 0 !== Db::table($t)->where(['id' => $softid])->update(['isr_approver' => $usrid]);
    }

    public function setSoftwareRequireDayCheck(string $softid, string $checker): bool
    {
        $t = 'starvc_imprvlib.imprvlib_soft_daychk';
        $res = Db::table($t)->insert(['isd_softid' => $softid, 'isd_checker' => $checker]);

        return false !== $res;
    }

    public function softwareRequireDetail(string $softid): array
    {
        $t = 'starvc_imprvlib.imprvlib_soft_requr_detail';
        $sql = "SELECT
                    CAST( isrd_detail AS CHAR ( 10000 ) CHARACTER SET gbk ) AS isrd_detail 
                FROM
                    $t
                WHERE
                    isrd_softid = ?";
        $res = Db::query($sql, [$softid]);

        return $res;
    }

    public function saveSoftwareRequireDetail(string $softid, string $detail): bool
    {
        $t = 'starvc_imprvlib.imprvlib_soft_requr_detail';
        $sql = "INSERT INTO $t ( isrd_softid, isrd_detail )
                VALUES
                    ( ?, ? ) 
                    ON DUPLICATE KEY UPDATE isrd_detail = ?";
        $res = Db::execute($sql, [$softid, $detail, $detail]);

        return 0 !== $res;
    }

    public function softwareRequireDevLog(string $softid): array
    {
        $t = 'starvc_imprvlib.imprvlib_soft_dev_log';
        $sql = "SELECT
                    CAST( isdl_log AS CHAR ( 10000 ) CHARACTER SET utf8 ) AS isdl_log 
                FROM
                    $t
                WHERE
                    isdl_softid = ?";
        $res = Db::query($sql, [$softid]);

        return $res;
    }

    public function saveSoftwareRequireDevLog(string $softid, string $devLog): bool
    {
        $t = 'starvc_imprvlib.imprvlib_soft_dev_log';
        $sql = "INSERT INTO $t ( isdl_softid, isdl_log )
                VALUES
                    ( ?, ? ) 
                    ON DUPLICATE KEY UPDATE isdl_log = ?";
        $res = Db::execute($sql, [$softid, $devLog, $devLog]);

        return 0 !== $res;
    }
}

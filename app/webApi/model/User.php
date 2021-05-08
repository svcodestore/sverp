<?php

namespace app\webApi\model;

use think\facade\Db;

class User
{
    private const DBNAME = 'starvc_syslib.';
    private const USRTBL = 'syslib_user_home';
    private const USREXTRATBL = 'syslib_user_extra';
    private const USROLEMAPTBL = 'syslibmap_user_role';
    private const USRDEPTMAPTBL = 'syslibmap_user_dept';
    private const ROLEMENUMAPTBL = 'syslibmap_role_menu';
    private const DEPTMENUMAPTBL = 'syslibmap_dept_menu';
    private const MENUTBL = 'syslib_menu_home';
    private const GROUPTBL = 'syslib_group_dept';

    /**
     * @description: 用户表中的 id, con_id, con_name 组成的表格
     * @return array
     */
    public function users(): array
    {
        $tblUser = self::DBNAME . self::USRTBL;
        $fields = ['id', 'con_id', 'con_name', 'con_title'];
        $res = Db::table($tblUser)->field($fields)->where('con_status', 1)->select()->toArray();

        return $res;
    }

    /**
     * @description: 分组表中的 id, sgd_code, sgc_alias, sgd_is_dept 组成的表格
     * @return array
     */
    public function groups(): array
    {
        $t = self::DBNAME . self::GROUPTBL;
        $fields = ['id', 'sgd_code', 'sgd_alias', 'sgd_is_dept'];
        $res = Db::table($t)->field($fields)->where('sgd_status', 1)->select()->toArray();

        return $res;
    }

    /**
     * @description: 根据 ID 关键字查询用户信息， * 全部查询
     * @param string $usrid
     * @return array
     */
    public function userInfo(string $usrid): array
    {
        $tblUser = self::DBNAME . self::USRTBL;
        $tblUserExtra = self::DBNAME . self::USREXTRATBL;

        $cond = '';
        if ($usrid !== '*') {
            $cond = "WHERE a.con_id = '$usrid' OR a.id = '$usrid'";
        }

        $sql = "SELECT
                    a.*,
                    b.con_name AS modifier 
                FROM
                    (
                    SELECT
                        a.*,
                        b.con_name AS creator 
                    FROM
                        (
                        SELECT
                            a.*,
                            b.sue_last_login_time,
                            b.sue_last_loginip 
                        FROM
                            $tblUser AS a
                            LEFT JOIN $tblUserExtra AS b ON a.id = b.sue_uid 
                        $cond 
                        ) AS a
                        LEFT JOIN $tblUser AS b ON a.con_creator = b.con_id 
                    ) AS a
                    LEFT JOIN $tblUser AS b ON a.con_modifier = b.con_id
        ";
        $res = Db::query($sql);

        return $res;
    }

    public function handleUserOpt(array $opt): bool
    {
        return Common::handleOpt(self::DBNAME . self::USRTBL, $opt);
    }

    /**
     * @description: 用户的授权权限，包括角色，分组，部门，菜单 
     * @param string $usrid 用户表主键 id, 或者用户ID
     * @return array
     */
    public function userAuthInfo(string $usrid): array
    {
        $sql = "SELECT
                    a.*,
                    b.som_opet_name AS group_menu_allow,
                    b.som_opet_code as group_menu_allowcode,
                    b.som_assoc_menu AS group_menu 
                FROM
                    (
                    SELECT
                        a.*,
                        b.som_opet_name AS role_menu_allow,
                        b.som_opet_code as role_menu_allowcode,
                        b.som_assoc_menu AS role_menu 
                    FROM
                        (
                        SELECT
                            a.*,
                            b.id AS dept_opet_pkid,
                            b.map_do_oid AS dept_opetid 
                        FROM
                            (
                            SELECT
                                a.*,
                                b.id AS role_opet_pkid,
                                b.map_ro_oid AS role_opetid 
                            FROM
                                (
                                SELECT
                                    a.*,
                                    b.id AS blackapi_pkid,
                                    b.sua_black_api AS blackapi 
                                FROM
                                    (
                                    SELECT
                                        a.*,
                                        b.role_code AS role,
                                        b.role_desc AS role_name 
                                    FROM
                                        (
                                        SELECT
                                            a.*,
                                            b.map_ur_rid AS roleid,
                                            b.id AS usr2role_pkid 
                                        FROM
                                            (
                                            SELECT
                                                a.*,
                                                b.sgd_code AS group_code,
                                                sgd_alias AS group_name 
                                            FROM
                                                (
                                                SELECT
                                                    a.id AS usrid,
                                                    a.con_id AS usr,
                                                    a.con_name AS `name`,
                                                    a.con_dept AS dept,
                                                    b.id AS usr2group_pkid,
                                                    b.sud_did AS groupid 
                                                FROM
                                                    starvc_syslib.syslib_user_home AS a
                                                    LEFT JOIN (SELECT * FROM starvc_syslib.syslibmap_user_dept WHERE sud_status = 1) AS b ON a.id = b.sud_uid 
                                                WHERE
                                                    a.con_status = 1 
                                                    AND ( a.id = ? OR a.con_id = ? ) 
                                                ) AS a
                                                LEFT JOIN starvc_syslib.syslib_group_dept AS b ON a.groupid = b.id 
                                            WHERE
                                                b.sgd_status = 1 
                                                OR b.sgd_status IS NULL 
                                            ) AS a
                                            LEFT JOIN (SELECT * FROM starvc_syslib.syslibmap_user_role WHERE map_ur_status = 1) AS b ON a.usrid = b.map_ur_uid 
                                        ) AS a
                                        LEFT JOIN starvc_syslib.syslib_role_home AS b ON a.roleid = b.id 
                                    WHERE
                                        b.role_status = 1 
                                        OR b.role_status IS NULL 
                                    ) AS a
                                    LEFT JOIN starvc_syslib.syslibmap_user_api AS b ON a.usr = b.sua_usrid 
                                ) AS a
                                LEFT JOIN starvc_syslib.syslibmap_role_opet AS b ON a.roleid = b.map_ro_rid 
                            ) AS a
                            LEFT JOIN starvc_syslib.syslibmap_dept_opet AS b ON a.groupid = b.map_do_did 
                        ) AS a
                        LEFT JOIN (SELECT * FROM starvc_syslib.syslibmap_opet_menu WHERE som_status = 1) AS b ON a.role_opetid = b.id 
                    ) AS a
                    LEFT JOIN (SELECT * FROM starvc_syslib.syslibmap_opet_menu WHERE som_status = 1) AS b ON a.dept_opetid = b.id 
        ";
        $authInfo = Db::query($sql, [$usrid, $usrid]);

        return $authInfo;
    }

    /**
     * @description: 用户的所有权限，包括角色，分组，部门，菜单 
     * @param string $usrid 用户表主键 id, 或者用户ID
     * @return array
     */
    public function userAuthAllInfo(string $usrid): array
    {
        $sql = "SELECT
                    a.*,
                    b.som_opet_name AS group_menu_allow,
                    b.som_opet_code as group_menu_allowcode,
                    b.som_assoc_menu AS group_menu 
                FROM
                    (
                    SELECT
                        a.*,
                        b.som_opet_name AS role_menu_allow,
                        b.som_opet_code as role_menu_allowcode,
                        b.som_assoc_menu AS role_menu 
                    FROM
                        (
                        SELECT
                            a.*,
                            b.id AS dept_opet_pkid,
                            b.map_do_oid AS dept_opetid 
                        FROM
                            (
                            SELECT
                                a.*,
                                b.id AS role_opet_pkid,
                                b.map_ro_oid AS role_opetid 
                            FROM
                                (
                                SELECT
                                    a.*,
                                    b.id AS blackapi_pkid,
                                    b.sua_black_api AS blackapi 
                                FROM
                                    (
                                    SELECT
                                        a.*,
                                        b.role_code AS role,
                                        b.role_desc AS role_name 
                                    FROM
                                        (
                                        SELECT
                                            a.*,
                                            b.map_ur_status AS role_status,
                                            b.map_ur_rid AS roleid,
                                            b.id AS usr2role_pkid 
                                        FROM
                                            (
                                            SELECT
                                                a.*,
                                                b.sgd_code AS group_code,
                                                b.sgd_alias AS group_name 
                                            FROM
                                                (
                                                SELECT
                                                    a.id AS usrid,
                                                    a.con_id AS usr,
                                                    a.con_name AS `name`,
                                                    a.con_dept AS dept,
                                                    b.id AS usr2group_pkid,
                                                    b.sud_status AS group_status,
                                                    b.sud_did AS groupid 
                                                FROM
                                                    starvc_syslib.syslib_user_home AS a
                                                    LEFT JOIN (SELECT * FROM starvc_syslib.syslibmap_user_dept WHERE sud_status = 1) AS b ON a.id = b.sud_uid 
                                                WHERE
                                                    a.con_status = 1 
                                                    AND ( a.id = ? OR a.con_id = ? ) 
                                                ) AS a
                                                LEFT JOIN starvc_syslib.syslib_group_dept AS b ON a.groupid = b.id 
                                            WHERE
                                                b.sgd_status = 1 
                                                OR b.sgd_status IS NULL 
                                            ) AS a
                                            LEFT JOIN (SELECT * FROM starvc_syslib.syslibmap_user_role WHERE map_ur_status = 1) AS b ON a.usrid = b.map_ur_uid 
                                        ) AS a
                                        LEFT JOIN starvc_syslib.syslib_role_home AS b ON a.roleid = b.id 
                                    WHERE
                                        b.role_status = 1 
                                        OR b.role_status IS NULL 
                                    ) AS a
                                    LEFT JOIN starvc_syslib.syslibmap_user_api AS b ON a.usr = b.sua_usrid 
                                ) AS a
                                LEFT JOIN starvc_syslib.syslibmap_role_opet AS b ON a.roleid = b.map_ro_rid 
                            ) AS a
                            LEFT JOIN starvc_syslib.syslibmap_dept_opet AS b ON a.groupid = b.map_do_did 
                        ) AS a
                        LEFT JOIN (SELECT * FROM starvc_syslib.syslibmap_opet_menu WHERE som_status = 1) AS b ON a.role_opetid = b.id 
                    ) AS a
                    LEFT JOIN (SELECT * FROM starvc_syslib.syslibmap_opet_menu WHERE som_status = 1) AS b ON a.dept_opetid = b.id 
        ";
        $authInfo = Db::query($sql, [$usrid, $usrid]);

        return $authInfo;
    }

    public function userAuthMenu(string $usrid)
    {
        $authInfo = $this->userAuthInfo($usrid);

        $menuIdArr = [];
        foreach ($authInfo as $info) {
            $info['group_menu'] !== null && ($menuIdArr[][$info['group_menu_allowcode']] = $info['group_menu']);
            $info['role_menu'] !== null && ($menuIdArr[][$info['role_menu_allowcode']] = $info['role_menu']);
        }

        if ($menuIdArr) {
            $menus = [];

            foreach (array_values(array_unique($menuIdArr, SORT_REGULAR)) as $menuid) {
                $id = array_values($menuid)[0];

                if ($id === '0') {
                    $sql = "SELECT
                                * 
                            FROM
                                ( SELECT * FROM starvc_syslib.syslib_menu_home WHERE mnu_status = 1 AND pid = :pid ) a,
                                ( SELECT :allowCode ) b";
                    $param = [
                        'pid' => $id,
                        'allowCode' => array_keys($menuid)[0]
                    ];
                    $menuArr = Db::query($sql, $param);
                    $this->getMenuChildren($menuArr, $menus);
                } else {
                    $sql = "SELECT
                                * 
                            FROM
                                ( SELECT * FROM starvc_syslib.syslib_menu_home WHERE mnu_status = 1 AND id = :id ) a,
                                ( SELECT :allowCode ) b";
                    $param = [
                        'id' => $id,
                        'allowCode' => array_keys($menuid)[0]
                    ];
                    $menuArr = Db::query($sql, $param);
                    $this->getMenuParent($menuArr[0], $menus);
                }
            }

            $menus = array_values(array_unique($menus, SORT_REGULAR));

            // 去重菜单
            foreach ($menus as $k => $v) {
                foreach ($menus as $key => $value) {
                    if ($v['id'] === $value['id'] && $v['?'] !== $value['?']) {
                        if ($v['?'] === '000') {
                            unset($menus[$key]);
                        } else if ($value['?'] === '000') {
                            unset($menus[$k]);
                        }
                    }
                }
            }

            return $menus;
        }

        return null;
    }

    public function getMenuChildren(array $menuArr, &$menus): array
    {

        $sql = "SELECT
                        * 
                    FROM
                        ( SELECT * FROM starvc_syslib.syslib_menu_home WHERE mnu_status = 1 AND pid = :pid ) a,
                        ( SELECT :allowCode ) b";
        foreach ($menuArr as $v) {
            $match = array_filter($menus, function ($e) use ($v) {
                return $e['id'] === $v['id'];
            });
            if (!$match) {
                $menus[] = $v;
            } else {
                $match = array_values($match)[0];
                if ($match['?'] !== '000' && $v['?'] === '000') {
                    $menus[array_search($match, $menus)] = $v;
                }
            }

            $resMenuArr = Db::query($sql, ['pid' => $v['id'], 'allowCode' => $v['?']]);
            if (count($resMenuArr) > 0) {
                $this->getMenuChildren($resMenuArr, $menus);
            }
        }

        return $menus;
    }

    public function getMenuParent(array $menuArr, &$menus): array
    {
        $menus[] = $menuArr;
        $sql = "SELECT
                        * 
                    FROM
                        ( SELECT * FROM starvc_syslib.syslib_menu_home WHERE mnu_status = 1 AND id = :id ) a,
                        ( SELECT :allowCode ) b";
        $resMenuArr = Db::query($sql, ['id' => $menuArr['pid'], 'allowCode' => $menuArr['?']]);

        if (count($resMenuArr) > 0) {
            $this->getMenuParent($resMenuArr[0], $menus);
        }

        return $menus;
    }

    public function handleUserRoleOpt($opt): bool
    {
        return Common::handleOpt(self::DBNAME . self::USROLEMAPTBL, $opt);
    }

    public function handleUserGroupOpt($opt): bool
    {
        return Common::handleOpt(self::DBNAME . self::USRDEPTMAPTBL, $opt);
    }

    /**
     * 根据权限分组代号，获取用户基本信息
     * @param string $code
     * @return array
     */
    public function getUsersByGroupCode(string $code)
    {   
        $uh_table = self::DBNAME . self::USRTBL;
        $ud_table = self::DBNAME . self::USRDEPTMAPTBL;
        $gd_table = self::DBNAME . self::GROUPTBL;
        $sql = "
        SELECT 
            * 
        FROM 
            {$uh_table}
        WHERE id IN 
        (
            SELECT 
                sud_uid 
            FROM 
                {$ud_table}
            WHERE 
                sud_did = (SELECT id FROM {$gd_table} WHERE sgd_code = ?)
        )";

        return Db::query($sql, [$code]);
    }
}

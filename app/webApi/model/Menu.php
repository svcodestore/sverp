<?php
/*
 * @Author: yanbuw1911
 * @Date: 2020-11-06 16:11:27
 * @LastEditTime: 2020-12-16 14:45:02
 * @LastEditors: yanbuw1911
 * @Description: 
 * @FilePath: \backend\app\webApi\model\Menu.php
 */

namespace app\webApi\model;

use think\facade\Db;

class Menu
{
    protected $db = 'starvc_syslib';
    protected $menuTbl = 'syslib_menu_home';

    public function allMenuList(): array
    {
        $menuTbl = $this->db . '.' . $this->menuTbl;
        $userTbl = $this->db . '.syslib_user_home';
        $sql = "SELECT
                    a.*,
                    b.con_name AS modifier 
                FROM
                    (
                    SELECT
                        a.*,
                        b.con_name AS creator 
                    FROM
                        $menuTbl AS a
                        LEFT JOIN $userTbl AS b ON ( a.mnu_creator ) = b.con_id 
                    ) AS a
                    LEFT JOIN $userTbl AS b ON a.mnu_modifier = b.con_id";
        $res = Db::query($sql);

        return $res;
    }

    public function handleMenuOpt(array $menuOpt): bool
    {
        return Common::handleOpt($this->db . '.' . $this->menuTbl, $menuOpt);
    }
}

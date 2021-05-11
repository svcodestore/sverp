<?php
/*
* @Author: yanbuw1911
* @Date: 2020-11-26 14:15:15
 * @LastEditTime: 2021-05-11 08:24:02
 * @LastEditors: Mok.CH
* @Description:
 * @FilePath: \sverp\app\webApi\model\Privilege.php
*/

namespace app\webApi\model;

use think\facade\Db;
use think\facade\Log;

class Privilege
{
    /**
     * @description: 角色列表
     * @return array
     */
    public function roles(): array
    {
        $db = 'starvc_syslib';
        $t = "$db.syslib_role_home";

        $res = Db::table($t)->select()->toArray();

        return $res;
    }

    /**
     * @description: 分组列表
     * @return array
     */
    public function groups(): array
    {
        $db = 'starvc_syslib';
        $t = "$db.syslib_group_dept";

        $res = Db::table($t)->select()->toArray();

        return $res;
    }

    /**
     * @description: 权限操作列表
     * @return array
     */
    public function operations(): array
    {
        $db = 'starvc_syslib';
        $t = "$db.syslib_operation_home";

        $res = Db::table($t)->select()->toArray();

        return $res;
    }

    /**
     * @description: 权限操作到菜单映射
     * @return array
     */
    public function opet2Menu(): array
    {
        $db = 'starvc_syslib';
        $t = "$db.syslibmap_opet_menu";

        $res = Db::table($t)->select()->toArray();

        return $res;
    }

    /**
     * @description: 角色到权限操作映射
     * @return array
     */
    public function role2Opet(): array
    {
        $db = 'starvc_syslib';
        $t = "$db.syslibmap_role_opet";

        $res = Db::table($t)->select()->toArray();

        return $res;
    }

    /**
     * @description: 分组到权限操作映射
     * @return array
     */
    public function group2Opet(): array
    {
        $db = 'starvc_syslib';
        $t = "$db.syslibmap_dept_opet";

        $res = Db::table($t)->select()->toArray();

        return $res;
    }

    /**
     * @description: 用户、组、角色黑名单 API
     * @return array
     */
    public function blackApiList(): array
    {
        $t = 'starvc_syslib.syslibmap_user_api';
        $res = Db::table($t)->select()->toArray();

        return $res;
    }

    /**
     * @description: 处理角色表的编辑
     * @param array $opt 变更数据
     * @return bool
     */
    public function handleRoleOpt($opt): bool
    {
        $db = 'starvc_syslib';
        $t = "$db.syslib_role_home";
        
        foreach($opt as $h=>$ot) {
            foreach($ot as $k=>$o) {
                if (empty($o['role_join_date'])) {
                    $opt[$h][$k]['role_join_date'] = date('Y-m-d H:i:s', time());
                }
    
                if(empty($o['role_mod_date'])) {
                    $opt[$h][$k]['role_mod_date'] = date('Y-m-d H:i:s', time());
                }
            }
        }
        
        return Common::handleOpt($t, $opt);
    }

    /**
     * @description: 处理角色表的编辑
     * @param array $opt 变更数据
     * @return bool
     */
    public function handleGroupOpt(array $opt): bool
    {
        $db = 'starvc_syslib';
        $t = "$db.syslib_group_dept";

        return Common::handleOpt($t, $opt);
    }

    /**
     * @description: 处理角色表的编辑
     * @param array $opt 变更数据
     * @return bool
     */
    public function handleOperationOpt($opt): bool
    {
        $db = 'starvc_syslib';
        $t = "$db.syslib_operation_home";

        return Common::handleOpt($t, $opt);
    }

    /**
     * @description: 处理操作菜单表的编辑
     * @param array $opt 变更数据
     * @return bool
     */
    public function handleOpet2MenuOpt(array $opt): bool
    {
        $db = 'starvc_syslib';
        $t = "$db.syslibmap_opet_menu";

        return Common::handleOpt($t, $opt);
    }

    /**
     * @description: 处理角色操作表的编辑
     * @param array $opt 变更数据
     * @return bool
     */
    public function handleRole2OpetOpt(array $opt): bool
    {
        $db = 'starvc_syslib';
        $t = "$db.syslibmap_role_opet";

        return Common::handleOpt($t, $opt);
    }

    /**
     * @description: 处理分组操作表的编辑
     * @param array $opt 变更数据
     * @return bool
     */
    public function handleGroup2OpetOpt(array $opt): bool
    {
        $db = 'starvc_syslib';
        $t = "$db.syslibmap_dept_opet";

        return Common::handleOpt($t, $opt);
    }

    /**
     * @description: 处理黑名单接口表的编辑
     * @param array $opt 变更数据
     * @return bool
     */
    public function handleBlackApiOpt(array $opt): bool
    {
        $db = 'starvc_syslib';
        $t = "$db.syslibmap_user_api";

        return Common::handleOpt($t, $opt);
    }
}

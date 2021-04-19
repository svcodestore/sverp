<?php
/*
* @Author: yanbuw1911
* @Date: 2020-11-26 15:24:56
 * @LastEditTime: 2020-12-15 15:45:00
 * @LastEditors: yanbuw1911
* @Description:
 * @FilePath: \backend\app\webApi\api\Privilege.php
*/

namespace app\webApi\api;

use app\BaseController;
use app\webApi\controller\Privilege as ControllerPrivilege;

class Privilege extends BaseController
{
    /**
     * @description: 角色列表
     * @return json
     */
    public function getRoles()
    {
        return (new ControllerPrivilege())->getRoles();
    }

    /**
     * @description: 分组列表
     * @return json
     */
    public function getGroups()
    {
        return (new ControllerPrivilege())->getGroups();
    }

    /**
     * @description: 权限列表
     * @return json
     */
    public function getOperations()
    {
        return (new ControllerPrivilege())->getOperations();
    }

    /**
     * @description: 系统接口列表
     * @return json
     */
    public function getApiList()
    {
        return (new ControllerPrivilege())->getApiList();
    }

    /**
     * @description: 权限菜单映射列表
     * @return json
     */
    public function getOpetMenuMap()
    {
        return (new ControllerPrivilege())->getOpetMenuMap();
    }

    /**
     * @description: 角色权限映射列表
     * @return json
     */
    public function getRoleOpetMap()
    {
        return (new ControllerPrivilege())->getRoleOpetMap();
    }

    /**
     * @description: 分组权限映射列表
     * @return json
     */
    public function getGroupOpetMap()
    {
        return (new ControllerPrivilege())->getGroupOpetMap();
    }

    /**
     * @description: 用户、组、角色黑名单列表
     * @return json
     */
    public function getBlackApiList()
    {
        return (new ControllerPrivilege())->getBlackApiList();
    }

    /**
     * @description: 保存角色表的编辑
     * @return json
     */
    public function saveRoleOpt()
    {
        return (new ControllerPrivilege())->saveRoleOpt();
    }

    /**
     * @description: 保存分组表的编辑
     * @return json
     */
    public function saveGroupOpt()
    {
        return (new ControllerPrivilege())->saveGroupOpt();
    }

    /**
     * @description: 保存权限操作表的编辑
     * @return json
     */
    public function saveOperationOpt()
    {
        return (new ControllerPrivilege())->saveOperationOpt();
    }

    /**
     * @description: 保存操作菜单表的编辑
     * @return json
     */
    public function saveOpetMenuMapOpt()
    {
        return (new ControllerPrivilege())->saveOpetMenuMapOpt();
    }

    /**
     * @description: 保存角色操作表的编辑
     * @return json
     */
    public function saveRoleOpetMapOpt()
    {
        return (new ControllerPrivilege())->saveRoleOpetMapOpt();
    }

    /**
     * @description: 保存分组操作表的编辑
     * @return json
     */
    public function saveGroupOpetMapOpt()
    {
        return (new ControllerPrivilege())->saveGroupOpetMapOpt();
    }

    /**
     * @description: 保存黑名单接口表的编辑
     * @return json
     */
    public function saveBlackApiOpt()
    {
        return (new ControllerPrivilege())->saveBlackApiOpt();
    }
}

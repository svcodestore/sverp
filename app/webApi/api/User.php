<?php
/*
 * @Author: yanbuw1911
 * @Date: 2020-11-04 08:56:43
 * @LastEditTime: 2020-12-18 11:35:55
 * @LastEditors: yanbuw1911
 * @Description: 
 * @FilePath: \backend\app\webApi\api\User.php
 */

namespace app\webApi\api;

use app\BaseController;
use app\webApi\controller\User as ControllerUser;

class User extends BaseController
{
    public $controller;

    protected function initialize()
    {
        parent::initialize();
        $this->controller = new ControllerUser();
    }

    public function getUserInfo()
    {
        return $this->controller->getUserInfo();
    }

    public function saveUserOpt()
    {
        return $this->controller->saveUserOpt();
    }

    public function getUserAuthMenu()
    {
        return $this->controller->getUserAuthMenu();
    }

    public function getUserAuthInfo()
    {
        return $this->controller->getUserAuthInfo();
    }

    public function getUserAuthAllInfo()
    {
        return $this->controller->getUserAuthAllInfo();
    }

    public function getUsers()
    {
        return $this->controller->getUsers();
    }

    public function getGroups()
    {
        return $this->controller->getGroups();
    }

    public function saveUserRoleOpt()
    {
        return $this->controller->saveUserRoleOpt();
    }

    public function saveUserGroupOpt()
    {
        return $this->controller->saveUserGroupOpt();
    }
}

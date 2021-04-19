<?php
namespace app\webApi\api;

use app\BaseController;
use app\webApi\controller\Menu as ControllerMenu;

class Menu extends BaseController
{
    public function getAllMenuList ()
    {
        return (new ControllerMenu())->getAllMenuList();
    }

    public function saveMenuOpt ()
    {
        return (new ControllerMenu())->saveMenuOpt();
    }
}
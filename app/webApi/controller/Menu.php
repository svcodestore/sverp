<?php

namespace app\webApi\controller;

use app\webApi\model\Menu as ModelMenu;

class Menu
{
    public function getAllMenuList()
    {
        $res = (new ModelMenu())->allMenuList();
        $rtn['result'] = true;
        $rtn['data'] = $res;

        return json($rtn);
    }

    public function saveMenuOpt()
    {
        $opt = input('post.menuOpt');

        $res = (new ModelMenu())->handleMenuOpt($opt);
        $rtn['result'] = $res;

        return json($rtn);
    }
}

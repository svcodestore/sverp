<?php
/*
 * @Author: yanbuw1911
 * @Date: 2020-11-04 09:00:03
 * @LastEditTime: 2020-12-18 11:35:42
 * @LastEditors: yanbuw1911
 * @Description: 
 * @FilePath: \backend\app\webApi\controller\User.php
 */

namespace app\webApi\controller;

use app\webApi\model\User as ModelUser;

class User
{
    public function getUserInfo()
    {
        $usrid = input('post.usrid');
        if (empty($usrid)) {
            $rtn['result'] = false;
            return json($rtn);
        }

        $res = (new ModelUser())->userInfo($usrid);

        $rtn['result'] = true;
        $rtn['data'] = $res;

        return json($rtn);
    }

    public function saveUserOpt()
    {
        $input = input();
        $res = (new ModelUser())->handleUserOpt($input);

        $rtn['result'] = $res;

        return json($rtn);
    }

    public function getUserAuthMenu(string $usrPkid = '')
    {
        if (empty($usrPkid)) {
            $id = input('post.usrPkid');
            if (empty($id)) {
                $rtn['result'] = false;
                return json($rtn);
            }
            $usrPkid = $id;
        }

        $res = (new ModelUser())->userAuthMenu($usrPkid);
        $rtn['result'] = true;
        $rtn['data'] = $res;

        return json($rtn);
    }

    public function getUsers()
    {
        $rtn['result'] = true;
        $rtn['data'] = (new ModelUser())->users();

        return json($rtn);
    }

    public function getGroups()
    {
        $rtn['result'] = true;
        $rtn['data'] = (new ModelUser())->groups();

        return json($rtn);
    }

    public function getUserAuthInfo()
    {
        $id = input('post.usrPkid');
        if (empty($id)) {
            $rtn['result'] = false;
            return json($rtn);
        }

        $rtn['result'] = true;
        $rtn['data'] = (new ModelUser())->userAuthInfo($id);
        return json($rtn);
    }

    public function getUserAuthAllInfo()
    {
        $id = input('post.usrPkid');
        if (empty($id)) {
            $rtn['result'] = false;
            return json($rtn);
        }

        $rtn['result'] = true;
        $rtn['data'] = (new ModelUser())->userAuthAllInfo($id);
        return json($rtn);
    }

    public function saveUserRoleOpt()
    {
        $input = input();

        $rtn['result'] = (new ModelUser())->handleUserRoleOpt($input);

        return json($rtn);
    }

    public function saveUserGroupOpt()
    {
        $input = input();

        $rtn['result'] = (new ModelUser())->handleUserGroupOpt($input);

        return json($rtn);
    }
}

<?php
/*
 * @Author: yanbuw1911
 * @Date: 2020-11-04 09:00:03
 * @LastEditTime: 2021-05-08 15:21:46
 * @LastEditors: Mok.CH
 * @Description: 
 * @FilePath: \sverp\app\webApi\controller\User.php
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

    public function getUsersByGroupCode()
    {
        $code = request()->param('code', null);
        if (empty($code)) return json(['code'=>1, 'msg'=>'param code required!']);
        $data = (new ModelUser())->getUsersByGroupCode($code);
        return json(['data'=>$data]);
    }
}

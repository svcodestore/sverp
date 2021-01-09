<?php
/*
 * @Author: yanbuw1911
 * @Date: 2020-11-04 13:57:28
 * @LastEditTime: 2020-12-24 14:14:12
 * @LastEditors: yanbuw1911
 * @Description: 
 * @FilePath: \backend\app\webApi\controller\Login.php
 */

namespace app\webApi\controller;

use app\webApi\model\Login as ModelLogin;
use app\webApi\model\User;

class Login
{
    public function login()
    {
        $info = input();
        if (empty($info)) {
            exit;
        }

        $usr = $info['username'];
        $pwd = $info['password'];

        $res = (new ModelLogin())->login($usr, $pwd);
        if (count($res) > 1) {
            foreach ($res as $k => $v) {
                foreach ($res as $key => $value) {
                    if ($v['id'] === $value['id'] && $value['sua_black_api'] !== null) {
                        $res[0]['black_api'][] = $value['sua_black_api'];
                    }
                    if (!isset($res[$k]['black_api'])) {
                        unset($res[$k]);
                    }
                }
            }
        }
        $rtn['result'] = false;
        if ($res && !empty($res)) {
            if (isset($res[0]['black_api'])) {
                $res[0]['black_api'] = array_unique($res[0]['black_api'], SORT_REGULAR);
            }
            $res = $res[0];
            $rtn['result'] = true;
            $rtn['data']['token'] = authorizedToken($res);
            $rtn['data']['userinfo'] = $res;
            $rtn['data']['userMenus'] = (new User())->userAuthMenu($res['id']);
        }

        return json($rtn);
    }
}

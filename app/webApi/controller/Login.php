<?php
/*
 * @Author: yanbuw1911
 * @Date: 2020-11-04 13:57:28
 * @LastEditTime: 2021-05-18 13:45:36
 * @LastEditors: Mok.CH
 * @Description: 
 * @FilePath: \sverp\app\webApi\controller\Login.php
 */

namespace app\webApi\controller;

use think\facade\Log;
use app\webApi\model\User;
use app\webApi\model\Login as ModelLogin;

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
            // 只用可以标识用户的字符段,以及一个登录变量,来产生token,缩小token的字节数 (875 => 329)
            $token_res = [
                'con_id' => $res['con_id'],
                'con_name' => $res['con_name'],
                'sue_last_login_time' => $res['sue_last_login_time']
            ];
            $rtn['result'] = true;
            $rtn['data']['token'] = authorizedToken($token_res);
            $rtn['data']['userinfo'] = $res;
            $rtn['data']['userMenus'] = (new User())->userAuthMenu($res['id']);
        }

        return json($rtn);
    }
}

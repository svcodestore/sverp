<?php
/*
 * @Author: yanbuw1911
 * @Date: 2020-11-04 14:34:04
 * @LastEditTime: 2020-12-18 15:01:46
 * @LastEditors: yanbuw1911
 * @Description: 
 * @FilePath: \backend\app\webApi\api\Login.php
 */

namespace app\webApi\api;

use app\webApi\controller\Login as ControllerLogin;

class Login
{
    public function __construct()
    {
        if ($_SERVER["REQUEST_METHOD"] == "OPTIONS" && $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'] == "Access-Token") {
            exit;
        }
    }

    public function login()
    {
        return (new ControllerLogin())->login();
    }
}

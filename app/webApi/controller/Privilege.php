<?php
/*
* @Author: yanbuw1911
* @Date: 2020-11-26 15:20:34
 * @LastEditTime: 2021-01-11 13:59:57
 * @LastEditors: yanbuw1911
* @Description:
 * @FilePath: \backend\app\webApi\controller\Privilege.php
*/

namespace app\webApi\controller;

use app\webApi\model\Privilege as ModelPrivilege;

class Privilege
{
    public function getRoles()
    {
        $res = (new ModelPrivilege())->roles();
        $rtn['result'] = true;
        $rtn['data'] = $res;

        return json($rtn);
    }

    public function getGroups()
    {
        $res = (new ModelPrivilege())->groups();
        $rtn['result'] = true;
        $rtn['data'] = $res;

        return json($rtn);
    }

    public function getOperations()
    {
        $res = (new ModelPrivilege())->operations();
        $rtn['result'] = true;
        $rtn['data'] = $res;

        return json($rtn);
    }

    public function getApiList()
    {
        $apiDir = app()->getAppPath() . 'api';
        $apiFiles = array_diff(scandir($apiDir), array('.', '..'));
        $nsPrefix = 'app\webApi\api\\';

        $apiList = [];
        foreach ($apiFiles as $apiFile) {
            $classname = substr($apiFile, 0, -4);
            $nsClass = $nsPrefix  . $classname;
            $reflect = new \ReflectionClass($nsClass);
            $reflectMethods = $reflect->getMethods();
            foreach ($reflectMethods as $reflectMethod) {
                $modifier = \Reflection::getModifierNames($reflectMethod->getModifiers());
                $funcname = $reflectMethod->{'name'};
                if ($modifier[0] === 'public' && $funcname !== '__construct') {
                    $api = DIRECTORY_SEPARATOR . strtolower($classname) . DIRECTORY_SEPARATOR . $funcname;
                    $apiList[] = ['path' => $api, 'doc' => $reflectMethod->getDocComment() ?: ''];
                }
            }
        }

        return json($apiList);
    }

    public function getOpetMenuMap()
    {
        $rtn['result'] = true;
        $rtn['data'] = (new ModelPrivilege())->opet2Menu();

        return json($rtn);
    }

    public function getRoleOpetMap()
    {
        $rtn['result'] = true;
        $rtn['data'] = (new ModelPrivilege())->role2Opet();

        return json($rtn);
    }

    public function getGroupOpetMap()
    {
        $rtn['result'] = true;
        $rtn['data'] = (new ModelPrivilege())->group2Opet();

        return json($rtn);
    }

    public function getBlackApiList()
    {
        $rtn['result'] = true;
        $rtn['data'] = (new ModelPrivilege())->blackApiList();

        return json($rtn);
    }

    public function saveRoleOpt()
    {
        $opt = input();
        $rtn['result'] = (new ModelPrivilege())->handleRoleOpt($opt);

        return json($rtn);
    }

    public function saveGroupOpt()
    {
        $opt = input();
        $rtn['result'] = (new ModelPrivilege())->handleGroupOpt($opt);

        return json($rtn);
    }

    public function saveOperationOpt()
    {
        $opt = input();
        $rtn['result'] = (new ModelPrivilege())->handleOperationOpt($opt);

        return json($rtn);
    }

    public function saveOpetMenuMapOpt()
    {
        $opt = input();
        $rtn['result'] = (new ModelPrivilege())->handleOpet2MenuOpt($opt);

        return json($rtn);
    }

    public function saveRoleOpetMapOpt()
    {
        $opt = input();
        $rtn['result'] = (new ModelPrivilege())->handleRole2OpetOpt($opt);

        return json($rtn);
    }

    public function saveGroupOpetMapOpt()
    {
        $opt = input();
        $rtn['result'] = (new ModelPrivilege())->handleGroup2OpetOpt($opt);

        return json($rtn);
    }

    public function saveBlackApiOpt()
    {
        $opt = input();
        $rtn['result'] = (new ModelPrivilege())->handleBlackApiOpt($opt);

        return json($rtn);
    }
}

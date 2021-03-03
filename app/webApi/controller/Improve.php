<?php
/*
 * @Author: yanbuw1911
 * @Date: 2020-11-05 13:28:20
 * @LastEditTime: 2021-03-03 09:19:52
 * @LastEditors: yanbuw1911
 * @Description: 
 * @FilePath: /sverp/app/webApi/controller/Improve.php
 */

namespace app\webApi\controller;

use app\webApi\model\Improve as ModelImprove;
use app\webApi\model\System;
use app\webApi\model\User;

class Improve
{
    public function getSoftRequireInitData()
    {
        $depts = (new System())->conDepts();

        $rtn['result'] = true;
        $rtn['data'] = [
            'depts' => $depts,
        ];
        return json($rtn);
    }

    public function getSoftRequire()
    {
        $data = (new ModelImprove())->softwareRequire();

        $rtn['result'] = true;
        $rtn['data'] = $data;

        return json($rtn);
    }

    public function saveSoftRequireOpt()
    {
        $opt = input();

        $rtn['result'] = (new ModelImprove())->handleSoftwareRequireOpt($opt);

        return json($rtn);
    }

    public function getUserFavoritePages()
    {
        $id = input('post.id');

        $rtn['result'] = true;
        $rtn['data'] = (new ModelImprove())->userFavoritePages($id);

        return json($rtn);
    }

    public function setUserFavoritePage()
    {
        $param = input();

        $rtn['result'] = (new ModelImprove())->setUserFavoritePage($param['menuid'], $param['usrid']);

        return json($rtn);
    }

    public function rmUserFavoritePage()
    {
        $param = input();

        $rtn['result'] = (new ModelImprove())->rmUserFavoritePage($param['menuid'], $param['usrid']);

        return json($rtn);
    }

    public function setSoftwareRequireDayCheck()
    {
        $param = input();

        $rtn['result'] = (new ModelImprove())->setSoftwareRequireDayCheck($param['softid'], $param['checker']);

        return json($rtn);
    }

    public function getDailyCheckList()
    {
        $softid = input('post.softid');

        $rtn['result'] = true;
        $rtn['data'] = (new ModelImprove())->dailyCheckList($softid);

        return json($rtn);
    }

    public function auditRequire()
    {
        $softid = input('post.softid');
        $usrid = input('post.usrid');

        $rtn['result'] = (new ModelImprove())->auditRequire($softid, $usrid);

        return json($rtn);
    }

    public function getSoftwareRequireDetail()
    {
        $softid = input('post.softid');

        $rtn['result'] = true;
        $rtn['data'] = (new ModelImprove())->softwareRequireDetail($softid);

        return json($rtn);
    }

    public function saveSoftwareRequireDetail()
    {
        $softid = input('post.softid');
        $detail = input('post.detail');

        $rtn['result'] = (new ModelImprove())->saveSoftwareRequireDetail($softid, $detail);

        return json($rtn);
    }

    public function getSoftwareRequireDevLog()
    {
        $softid = input('post.softid');

        $rtn['result'] = true;
        $rtn['data'] = (new ModelImprove())->softwareRequireDevLog($softid);

        return json($rtn);
    }

    public function saveSoftwareRequireDevLog()
    {
        $softid = input('post.softid');
        $devLog = input('post.devLog');

        $rtn['result'] = (new ModelImprove())->saveSoftwareRequireDevLog($softid, $devLog);

        return json($rtn);
    }
}

<?php
/*
 * @Author: yanbuw1911
 * @Date: 2020-11-05 13:28:20
 * @LastEditTime: 2021-01-14 16:03:40
 * @LastEditors: yanbuw1911
 * @Description: 
 * @FilePath: \backend\app\webApi\controller\Improve.php
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
        $rtn['data'] = (new ModelImprove())->userFavirotePages($id);

        return json($rtn);
    }

    public function setUserFavirotePage()
    {
        $param = input();

        $rtn['result'] = (new ModelImprove())->setUserFavirotePage($param['menuid'], $param['usrid']);

        return json($rtn);
    }

    public function rmUserFavirotePage()
    {
        $param = input();

        $rtn['result'] = (new ModelImprove())->rmUserFavirotePage($param['menuid'], $param['usrid']);

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
}

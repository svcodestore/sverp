<?php
/*
 * @Author: yanbuw1911
 * @Date: 2020-11-05 13:28:20
 * @LastEditTime: 2020-12-30 10:25:52
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
}

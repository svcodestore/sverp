<?php
/*
 * @Author: yanbuw1911
 * @Date: 2020-11-05 13:32:40
 * @LastEditTime: 2020-12-30 10:26:15
 * @LastEditors: yanbuw1911
 * @Description: 
 * @FilePath: \backend\app\webApi\api\Improve.php
 */

namespace app\webApi\api;

use app\BaseController;
use app\webApi\controller\Improve as ControllerImprove;

class Improve extends BaseController
{
    public function getSoftRequireInitData()
    {
        return (new ControllerImprove())->getSoftRequireInitData();
    }

    public function getSoftRequire()
    {
        return (new ControllerImprove())->getSoftRequire();
    }

    public function getUserFavoritePages()
    {
        return (new ControllerImprove())->getUserFavoritePages();
    }

    public function setUserFavirotePage()
    {
        return (new ControllerImprove())->setUserFavirotePage();
    }

    public function rmUserFavirotePage()
    {
        return (new ControllerImprove())->rmUserFavirotePage();
    }
}

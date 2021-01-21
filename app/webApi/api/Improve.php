<?php
/*
 * @Author: yanbuw1911
 * @Date: 2020-11-05 13:32:40
 * @LastEditTime: 2021-01-15 13:42:41
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

    public function saveSoftRequireOpt()
    {
        return (new ControllerImprove())->saveSoftRequireOpt();
    }

    public function setSoftwareRequireDayCheck()
    {
        return (new ControllerImprove())->setSoftwareRequireDayCheck();
    }

    public function getDailyCheckList()
    {
        return (new ControllerImprove())->getDailyCheckList();
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

    public function auditRequire()
    {
        return (new ControllerImprove())->auditRequire();
    }

    public function getSoftwareRequireDetail()
    {
        return (new ControllerImprove())->getSoftwareRequireDetail();
    }

    public function saveSoftwareRequireDetail()
    {
        return (new ControllerImprove())->saveSoftwareRequireDetail();
    }

    public function getSoftwareRequireDevLog()
    {
        return (new ControllerImprove())->getSoftwareRequireDevLog();
    }

    public function saveSoftwareRequireDevLog()
    {
        return (new ControllerImprove())->saveSoftwareRequireDevLog();
    }
}
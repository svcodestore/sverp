<?php
/*
 * @Author: yanbuw1911
 * @Date: 2020-11-05 13:32:40
 * @LastEditTime: 2021-03-03 09:19:59
 * @LastEditors: yanbuw1911
 * @Description: 
 * @FilePath: /sverp/app/webApi/api/Improve.php
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

    public function setUserFavoritePage()
    {
        return (new ControllerImprove())->setUserFavoritePage();
    }

    public function rmUserFavoritePage()
    {
        return (new ControllerImprove())->rmUserFavoritePage();
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

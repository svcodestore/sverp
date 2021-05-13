<?php
/*
 * @Date: 2021-05-06 14:11:21
 * @LastEditors: Mok.CH
 * @LastEditTime: 2021-05-12 11:22:47
 * @FilePath: \sverp\app\webApi\api\Dcs.php
 */

namespace app\webApi\api;

use app\BaseController;
use app\webApi\controller\Dcs as ControllerDcs;

class Dcs extends BaseController
{
  public function getPlan()
  {
    return (new ControllerDcs)->getPlan();
  }

  public function countPlan()
  {
    return (new ControllerDcs)->getPlanCount();
  }

  public function getUserPlan()
  {
    return (new ControllerDcs)->getUserPlan();
  }

  public function getDir()
  {
    return (new ControllerDcs)->getDir();
  }

  public function getFinishedPlan()
  {
    return (new ControllerDcs)->getFinishedPlan();
  }

  public function verify()
  {
    return (new ControllerDcs)->verify();
  }

  public function addPlan()
  {
    return (new ControllerDcs)->addPlan();
  }

  public function passCheck()
  {
    return (new ControllerDcs)->passCheck();
  }

  public function delPlan()
  {
    return (new ControllerDcs)->delPlan();
  }

  public function updatePlan()
  {
    return (new ControllerDcs)->updatePlan();
  }

  public function updatePlanAuth()
  {
    return (new ControllerDcs)->updatePlanAuth();
  }

  public function updatePlanCheck()
  {
    return (new ControllerDcs)->updatePlanCheck();
  }

  public function addPlanCheck()
  {
    return (new ControllerDcs)->addPlanCheck();
  }

  public function addPlanAuth()
  {
    return (new ControllerDcs)->addPlanAuth();
  }
  
  public function getFiles()
  {
    return (new ControllerDcs)->getFiles();
  }

  public function uploadFile()
  {
    return (new ControllerDcs)->uploadFile();
  }

  public function downloadFile()
  {
    return (new ControllerDcs)->downloadFile();
  }

  public function addRecord()
  {
    return (new ControllerDcs)->addRecord();
  }

  public function updateVersion()
  {
    return (new ControllerDcs)->updateVersion();
  }
}
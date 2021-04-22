<?php
/*
 * @Author: your name
 * @Date: 2020-12-07 16:46:26
 * @LastEditTime: 2021-04-22 09:00:42
 * @LastEditors: Mok.CH
 * @Description: In User Settings Edit
 * @FilePath: \sverp\app\webApi\api\Record.php
 */

namespace app\webApi\api;
use app\BaseController;
use app\webApi\controller\Record as recordController;
class Record extends BaseController
{
    public function apiRepair()
    {
       return (new recordController)->repair();
    }
    public function apiSaveRepair()
    {
       return (new recordController)->saveRepair();
    }
    public function apiRepairDetail()
    {
      return (new recordController)->getRepairDetail();
    }
    public function apiMecheInfo()
    {
       return (new recordController)->getMecheInfo();
    }
    public function apiSaveMecheInfo()
    {
       return (new recordController)->saveMecheInfo();
    }

    public function apiSendMsg()
    {
       return (new recordController)->sendMsg();
    }
    public function apiNotify()
    {
       return (new recordController)->getNotice();
    }
    public function apiSaveNotify()
    {
       return (new recordController)->saveNotice();
    }
    public function apiCheckCode()
    {
       return (new recordController)->checkCode();
    }
	 public function apiFitting()
    {
       return (new recordController)->getFitting();
    }
    public function apiSaveFitting()
    {
       return (new recordController)->saveFitting();
    }
	 public function apiFittingMsg()
    {
       return (new recordController)->fittingMsg();
    }
	 public function apiRepairComp()
    {
       return (new recordController)->repairComplete();
    }
    public function apiGetmecheNames()
    {
       
       return (new recordController)->getMecheNames();
    }
    public function apiGetRepairLogs()
    {
       return (new recordController)->getrepairLogs();
    }
   
}

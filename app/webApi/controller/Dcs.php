<?php
/*
 * @Date: 2021-05-06 13:28:22
 * @LastEditors: Mok.CH
 * @LastEditTime: 2021-05-08 14:13:06
 * @FilePath: \sverp\app\webApi\controller\Dcs.php
 */
namespace app\webApi\controller;

use think\facade\Log;
use app\webApi\model\Dcs as DcsModel;
use app\webApi\model\User as UserModel;

class Dcs
{
  public function getPlan()
  {
    $dirId = request()->param('dirId', null);
    
    $model = new DcsModel();
    $userModel = new UserModel();
    if (empty($dirId)) {
      $directorList = $model->getAllDir();
    } else {
      $directorList = [$model->getDirById($dirId)];
    }
    
    $resList = [];
    foreach ($directorList as $dir) {
      $plans = $model->getPlanByDirId($dir['id']);
      foreach ($plans as $plan) {
        $plan['directory'] = $dir;
        // $plan['users'] = $model->
        // $plan['planGather'] = $model->getPlanGatherById($plan['id']);

        // $plan['planAuth'] = $model->getPlanAuthById($plan['id']);
        // if ($plan['planAuth'] != null) {
        //   $_tmp_userIds =  explode(',', $plan['planAuth']);
        //   foreach($_tmp_userIds as $_uid) {
        //     $plan['planAuth']['users'][] = $model->
        //   }
        // }
        
        // $plan['planCheck'] = $model->getPlanCheckById($plan['id']);
        $plan['gatherUsername'] = '123';
        $plan['authUsers'] = [['username'=>321], ['username'=>'333']];
        $plan['checkUsers'] = [['username'=>321], ['username'=>'333']];
        $resList [] = $plan;
      }
    }
    return json(['data'=>$resList, 'count'=>count($resList)]);
  }

  /**
   * 按分类计划数据的数量
   */
  public function getPlanCount()
  {
    $dirId = request()->param('dirId', null);
    if ($dirId === null) return json(['msg'=>'dirId error', 'code'=>0]);
    
    $model = new DcsModel();
    $count = $model->countPlanByDirId($dirId);
    return json(['num' => $count]);
  }

  /**
   * 用户计划
   */
  public function getUserPlan()
  {
    $userId = request()->param('userId', null);
    $model = new DcsModel();
    $plans = $model->getPlanByUser($userId);
    return json(['data'=>$plans]);
  }

  public function getDir()
  {
    $model = new DcsModel();
    $dirs = $model->getAllDir();
    return json(['data'=>$dirs]);
  }

  public function getFinishedPlan()
  {
    $model = new DcsModel();
    $data = $model->getFinishedPlan();
    $resList = [];
    
    foreach ($data as $plan) {
      // 处理 userid
    }
    return json(['data'=>$data]);
  }

  /**
   * 确认完成操作，记录真实完成时间
   * 
   */
  public function verify()
  {
    $planId = request()->param('planId', null);
    $userId = request()->param('userId', null);
    $index = request()->param('index', null);

    if (empty($planId) || empty($userId) || empty($index)) {
      return json(['code'=>1, 'msg' => 'param (planId, userId, index) required']);
    }
    $res = false;
    $model = new DcsModel();
    switch($index) {
      case 1:
        $data = ['actualTime'=> date('Y-m-d H:i:s',time())];
        $res = $model->updatePlanGather($planId, $data);
        break;
      case 2:
        $data = ['authActualTime'=> time()];
        $data['userId'] = $userId;
        $res = $model->updatePlanAuth($planId, $data);
        break;
    }

    if ($res) {
      return json(['code'=>0, 'msg'=>'success']);
    }
    return json(['code'=>1, 'msg'=>'faild']);
  }

  /**
   * 添加认证计划
   */
  public function addPlan()
  {
    $model = new DcsModel();
    $data = request()->param();
    $planTime = $data['time'];
    unset($data['time']);
    $same = $model->getSamePlan($data['content']);
    
    if ($same) {
      return json(['code'=>1, 'msg'=>'已存在相同的计划']);
    }
    $newPlanId = $model->addPlan($data);
    
    if ($newPlanId) {
      // 初始化下一步 gather 的记录
      $gatherData = [
        'userId' => $data['userId'],
        'planId' => $newPlanId,
        'planTime' => $planTime,
      ];

      if ($model->addPlanGather($gatherData) ) {
        return json(['code'=>0, 'msg'=>'success']);
      }
    }
    
    return json(['code'=>1, 'msg'=>'faild']);
  }

  /**
   * 删除计划
   */
  public function delPlan()
  {
    $id = request()->param('id', null);
    if (empty($id)) return json(['code'=>1, 'msg'=>'param id required!']);
    
    $model = new DcsModel();
    if ($model->delPlan($id)) {
      return json(['code'=>'0', 'msg'=>'success']);
    } 
    return json(['code'=>1, 'msg'=>'faild']);
  }

  /**
   * 稽查判定
   * 
   */
  public function passCheck()
  {
    $planId = request()->param('planId', null);
    $userId = request()->param('userId', null);
    
    if (empty($planId) || empty($userId)) {
      return json(['code'=>1, 'msg'=> 'param required!']);
    }

    $model = new DcsModel();
    $data = [
      'userId' => $userId,
      'checkActualTime' => date('Y-m-d H:i:s', time())
    ];
    $res = $model->updatePlanCheck($planId, $data);

    if ($res) {
      return json(['code'=>0, 'msg'=>'success']);
    } else {
      return json(['code'=>1, 'msg'=>'faild']);
    }
  }

  /**
   * 更新计划
   */
  public function updatePlan()
  {
    $data = [
      'content' => request()->param('content'),
      'dirId' => request()->param('dirId'),
      'depPrincipal' => request()->param('depPrincipal'),
      'planTime' => request()->param('planTime')
    ];
    $planId = request()->param('planId', null);
    if (empty($planId)) return json(['code'=>1, 'msg'=>'param id required']);
    $model = new DcsModel();
    if ($model->updatePlanById($planId, $data)) {
      return json(['code'=>0, 'msg'=>'success']);
    }

    return json(['code'=>1, 'msg'=>'faild']);
  }

  /**
   * 更新认证计划
   */
  public function updatePlanAuth()
  {
    $planId = request()->param('planId', null);
    if (empty($planId)) return json(['code'=>1, 'msg'=>'param planId is required']);
    $data = [
      'authPlanTime' => request()->param('time'),
      'userId' => implode(',', request()->param('checkUsers', []))
    ];
    $model = new DcsModel();
    if ($model->updatePlanAuth($planId, $data)) {
      return json(['code'=>0, 'msg'=>'success']);
    }
    return json(['code'=>1, 'msg'=>'faild']);
  }

  /**
   * 更新稽核计划
   */
  public function updatePlanCheck()
  {
    $planId = request()->param('planId', null);
    if (empty($planId)) return json(['code'=>1, 'msg'=>'param planId is required']);
    $data = [
      'checkPlanTime' => request()->param('time', ''),
      'userId' => implode(',', request()->param('checkUsers', []))
    ];
    $model = new DcsModel();
    if ($model->updatePlanCheck($planId, $data)) {
      return json(['code'=>0, 'msg'=>'success']);
    }
    return json(['code'=>1, 'msg'=>'faild']);
  }

  /**
   * 添加稽核计划
   */
  public function addPlanCheck() 
  {
    $planId = request()->param('planId', null);
    if (empty($planId)) return json(['code'=>1, 'msg'=>'param']);
    $data = [
      'planId' => $planId,
      'checkPlanTime' => request()->param('time', null),
      'userId' => implode(',', request()->param('checkUsers', []))
    ];

    $model = new DcsModel();
    if ($model->addPlanCheck($data)) {
      return json(['code'=>0 , 'msg'=>'success']);
    }

    return json(['code'=>1, 'msg'=>'faild']);
  }

  public function addPlanAuth()
  {
    $planId = request()->param('planId', null);
    if (empty($planId)) return json(['code'=>1, 'msg'=>'param planId is required']);
    
    $gatherData = [
      // 'userId' => implode(',', request()->param('authUsers', [])),
      'gatherPlanTime' => request()->param('time', null),
      'planId' => request()->param('planId')
    ];
    $model = new DcsModel();
    if ($model->updatePlanGather($planId, $gatherData)) {
      $authData = [
        'userId' => implode(',', request()->param('authUsers', [])),
        'authPlanTime' => request()->param('time', null)
      ];

      if ($model->addPlanAuth($authData)) {
        return json(['code'=>0 , 'msg'=>'success']);
      }
    }
    return json(['code'=>1, 'msg'=>'faild']);
  }

}

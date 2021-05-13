<?php
/*
 * @Date: 2021-05-06 13:28:22
 * @LastEditors: Mok.CH
 * @LastEditTime: 2021-05-12 16:36:53
 * @FilePath: \sverp\app\webApi\controller\Dcs.php
 */
namespace app\webApi\controller;

use think\facade\Log;
use app\webApi\model\Dcs as DcsModel;
use app\webApi\model\User as UserModel;
use think\facade\Filesystem;

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
        $plan['gatherUsername'] = $userModel->userInfo($plan['userId']);
        if($plan['gatherUsername'] != null) {
          $plan['gatherUsername'] = $plan['gatherUsername'][0]['con_name'];
        }

        if (!empty($plan['aUserId'])){
          $aUserIds = $plan['aUserId'][-1]!==','?: substr($plan['aUserId'], 0 , -1);
          $plan['authUsers'] = $userModel->getUsersByIds($aUserIds);
        } else {
          $plan['authUsers'] = [];
        }

        if (!empty($plan['cUserId'])) {
          $cUserIds = $plan['cUserId'][-1]!==','?:substr($plan['cUserId'], 0, -1);
          $plan['checkUsers'] = $userModel->getUsersByIds($cUserIds);
        } else {
          $plan['checkUsers'] = [];
        }
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
        $data = ['authActualTime'=> date('Y-m-d H:i:s',time())];
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
      return json(['code'=>0, 'msg'=>'success']);
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
    $planId = request()->param('id', null);
    if (empty($planId)) return json(['code'=>1, 'msg'=>'param id required']);
    $model = new DcsModel();
    $data = [
      'content' => request()->param('content'),
      'dirId' => request()->param('dirId'),
      'depPrincipal' => request()->param('depPrincipal'),
    ];
    $gatherData = [
      'planTime' => request()->param('planTime')
    ];
    $r1 = $model->updatePlanById($planId, $data);
    $r2 = $model->updatePlanGather($planId, $gatherData);
    if ($r1 || $r2) {
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
      'gatherPlanTime' => request()->param('time', null),
    ];
    $model = new DcsModel();
    $res = $model->updatePlanGather($planId, $gatherData);

    if ($res) {
      $authData = [
        'userId' => implode(',', request()->param('authUsers', [])),
        'authPlanTime' => request()->param('time', null),
        'planId' => $planId
      ];

      if ($model->addPlanAuth($authData)) {
        return json(['code'=>0 , 'msg'=>'success']);
      }
    }
    return json(['code'=>1, 'msg'=>'faild']);
  }

  /** 
   * 获取文档
   * dirId 文档分类ID
   * depId 部门id
   */
  public function getFiles()
  {
    $dirId = request()->param('dirId', null);
    $depId = request()->param('depId', null);
    $model = new DcsModel();
    $userModel = new UserModel();
    $datas = $model->getAllFilesByDepIdAndDir($depId, $dirId);
    $retData = [];
    foreach($datas as $k=>$f) {
      // 如果有更新文档， 则采用更新的文档
      if ($f['version'] != 0 && $f['versionNo'] == 1)
        continue;
      
      $_dep = $userModel->getDepartments(['id'=>$f['departmentId']]);
      if ($_dep) {
        $f['department'] = $_dep[0];
      } else {
        $f['department'] = ['sgd_alias'=> ''];
      }
      
      $retData[] = $f;
    }
    return json(['data'=>$retData]);
  }

  /**
   * 文档上传
   */
  public function uploadFile()
  {
    $file = request()->file('files');
    $userName = request()->param('userName', null);
    $departmentId = request()->param('departmentId', null);
    $dirId = request()->param('dirId', null);
    $userId = request()->param('userId', null);
    
    if ($file === null) return json(['code'=>1, 'msg'=>'file required!']);
    
    $model = new DcsModel();
    $file_path = $model->getFilePath($dirId, $departmentId);
    $savename = Filesystem::putFile('dcs', $file, $file_path.$file->getOriginalName().'.'.$file->getOriginalExtension());

    if ($savename) {
      $data['cdate'] = date('Y-m-d H:i:s');
      $data['filesName'] = $file->getOriginalName().'.'.$file->getOriginalExtension();
      $data['filesPath'] = $file_path;
      $data['departmentId'] = $departmentId;
      $data['dirId'] = $dirId;
      $data['userId'] = $userId;
      $data['versionNo'] = 1;
      $data['cuser'] = $userName;
      $data['originalFileId'] = null;
      $data['isoNo'] = $file->getOriginalName();
      $model->addFile($data);
      return json(['code'=>0, 'msg'=>'success']);
    }

    return json(['code'=>1, 'msg'=>'file upload faild']);
  }

  /**
   * 下载文件
   */
  public function downloadFile()
  {
    $fileId = request()->param('fileId', null);
    $userId = request()->param('userId', null);
    
    if (empty($fileId)) return json(['code'=>1, 'msg'=>'param fileId required!']);
    
    $model = new DcsModel();
    $file = $model->getFileByFileId($fileId);
    if ($file) {
      return download($file['filesPath'], $file['filesName'], false, 3600);
    }
    return json(['code'=>1, 'msg'=>'file not exist']);
  }

  

  /**
   * 提交更新权限申请
   */
  public function addRecord()
  {
    $model = new DcsModel();
    $recordData = [
      'applyName' => request()->param('name', ''),
      'applyDate' => date('Y-m-d H:i:s'),
      'applyContent' => request()->param('content'),
      'applyStatus' => 1,
      'applyPassDate' => null,
      'inForce' => null,
      'operator' => null,
      'userId' => request()->param('userId'),
      'fileId' => request()->param('fileId'),
    ];
    if ($recordData['applyContent'] == '更新') {
      $res = $model->findRecordOnlyApply($recordData);
      if (!empty($res)) {
        return json(['code'=>1, 'msg'=>'该文件已有用户提交更新申请']);
      }

      $res2 = $model->findRecordOnlyPass($recordData);
      if (!empty($res2)) {
        return json(['code'=>1, 'msg'=>'无法申请，存在已通过审核但并未更新文件记录']);
      }
    } else {
      $res = $model->findRecordOnly($recordData);
      if (!empty($res)) {
        return json(['code'=>1, 'msg'=>'请勿重复申请']);
      }
      $res2 = $model->selectPass($recordData);
      if (!empty($res2)) {
        return json(['code'=>1, 'msg'=>'已提交，并已通过']); 
      }
    }

    if ($model->addRecord($recordData)) {
      return json(['code'=>0 , 'msg'=>'申请已提交']);
    }
    
    return json(['code'=>1, 'msg'=>'提交失败']);
  }

  /**
   * 更新文件版本
   * 
   */
  public function updateVersion()
  {
    $file = request()->file('file');
    $fileId = request()->param('fileId', null);
    $userName = request()->param('userName', null);
    $departmentId = request()->param('departmentId', null);
    $dirId = request()->param('dirId', null);
    $userId = request()->param('userId', null);
    $cuser = $userName;

    $model = new DcsModel();
    $file_path = $model->getFilePath($dirId, $departmentId);
    
    $recordData = [
      'applyContent' => '更新',
      'fileId' => $fileId,
    ];

    $record = $model->findRecordOnlyApply($recordData);
    if(!empty($record)) {
      return json(['code'=>1, 'msg'=>'更新失败，该文件有更新申请，请先审核']);
    }

    $r2 = $model->findRecordOnlyPass($recordData);
    if (!empty($r2)){
      $cuser = $r2['applyName'];
    }

    $oldFile = $model->getFileByFileId($fileId);

    if (Filesystem::putFile('dcs', $file, $file_path)) {
      $newFile = [
        'filesPath' => $file_path,
        'filesName' => $file->getOriginalName().'.'.$file->getOriginalExtension(),
        'cuser' => $cuser,
        'departmentId' => $departmentId,
        'isoNo' => $file->getOriginalName(),
        'version' => 0,
        'versionNo' => $oldFile['versionNo'] + 1,
        'dirId' => $dirId,
        'userId' => $userId,
        'originalFileId' => empty($oldFile['originalFileId']) ? $fileId : $oldFile['originalFileId']
      ];

      $newFileId = $model->addFile($newFile);
      if ($newFileId) {
        $model->updateFilesVersionId($newFile['originalFileId'], $newFileId);
        if (!empty($record)) {
          $model->updateRecordOut($record['id']);
        }
        $model->recordLog(5, $newFile['filesName'], $newFile['userId']);
        return json(['code'=>0, 'msg'=>'更新成功']);
      }
    }
    
    return json(['code'=>1, 'msg'=>'更新失败']);
  }

}

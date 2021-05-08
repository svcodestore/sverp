<?php
/*
 * @Date: 2021-05-06 07:56:29
 * @LastEditors: Mok.CH
 * @LastEditTime: 2021-05-07 16:36:43
 * @FilePath: \sverp\app\webApi\model\Dcs.php
 */
namespace app\webApi\model;

use think\facade\Db;
use think\facade\Log;

class Dcs 
{
  protected $database = 'starvc_dcs';

  /**
   * 上传文件表名
   * @return string
   */
  protected function fileTable():string
  {
    return $this->database . '.files';
  }

  /**
   * 计划总表名称
   * @return string
   */
  protected function planTable(): string
  {
    return $this->database . '.plan';
  }

  /**
   * 计划认证阶段状态表名
   */
  protected function planAuthTable(): string
  {
    return $this->database . '.plan_auth';
  }

  /**
   * 计划稽查阶段状态表名
   */
  protected function planCheckTable(): string
  {
    return $this->database . '.plan_check';
  }

  /**
   * 计划收集阶段状态表名
   */
  protected function planGatherTable(): string
  {
    return $this->database . '.plan_gather';
  }

  
  /**
   * 查询对应目录下对应部门的所有文件
   */
  public function getAllFilesByDepIdAndDir($departmentId, $dirId)
  {
    return Db::table($this->fileTable())
            ->where(['departmenId'=>$departmentId, 'dirId'=>$dirId])
            ->select()->toArray();
  }

  /**
   * 查询所有文件
   */
  public function getAllFiles()
  {
    return Db::table($this->fileTable())->select()->toArray();
  }

  /**
   * 根据id 查找文件
   * @param fileId
   */
  public function getFileByFileId($fileId)
  {
    return Db::table($this->fileTable())
              ->where(['filesId'=>$fileId])
              ->select()->toArray();
  }

  /**
   * 添加一条文件上传记录
   * @param file Array
   * @return
   */
  public function addFile(array $file)
  {
    return Db::table($this->fileTable())->insert($file);
  }

  /**
   * 根据文件名判断是否有同名文件
   * @param fileName
   * @return
   */
  public function fileIsExist($fileName)
  {
    return Db::table($this->fileTable())
              ->where(['filesName'=>$fileName])
              ->select()->toArray();
  }

  /**
   * 修改最新版本号
   * @param fileRow Array
   * @return
   */
  public function updateFilesVersionId($fileId, $version)
  {
    return Db::table($this->fileTable())
            ->where('filesId', $fileId)
            ->update(['version'=>$version]);
  }

  /**
   * 查询最新文件ID
   * @return
   */
  public function getFilesTop()
  {
    return Db::table($this->fileTable())
            ->order('filesId desc')
            ->limit(1)
            ->find();
  }

  /**
   * 根据目录id查询所有文件
   * @param dirId
   * @return
   */
  public function getAllFilesByDir($dirId)
  {
    return Db::table($this->fileTable())
              ->where('dirId', $dirId)
              ->select()->toArray();
  }

  /**
   * 查询历史文件版本
   * @param originalFileId
   * @return
   */
  public function getFilesVersion($originalFileId)
  {
    return Db::table($this->fileTable())
              ->where('originalFileId', $originalFileId)
              ->select()->toArray();
  }

  /**
   * 添加认证计划
   * @param plan
   * @return
   */
  public function addPlan($plan)
  {
    $plan['isFinish'] = 1;
    return Db::table($this->planTable())
              ->insertGetId($plan);
  }

  /**
   * 添加认证计划的认证时间等等
   * @param planAuth
   * @return
   */
  public function addPlanAuth($planAuth)
  {
    return Db::table($this->planAuthTable())
              ->insert($planAuth);
  }

  /**
   * 添加认证计划的收集资料时间等等
   * @param planGather
   * @return
   */
  public function addPlanGather($planGather)
  {
    return Db::table($this->planGatherTable())
              ->insert($planGather);
  }

  /**
   * 添加认证计划的稽核时间等等
   * @param planCheck
   * @return
   */
  public function addPlanCheck($planCheck)
  {
    return Db::table($this->planCheckTable())
              ->insert($planCheck);
  }

  /**
   * 修改 收集资料计划
   * @param planId
   * @param planGather
   * @return
   */
  public function updatePlanGather($planId, $planGather)
  {
    return Db::table($this->planGatherTable())
              ->where('planId', $planId)
              ->update($planGather);
  }

  /**
   * 修改 审核认证计划
   * @param planId
   * @param planAuth
   * @return
   */
  public function updatePlanAuth($planId, $planAuth)
  {
    return Db::table($this->planAuthTable())
              ->where('planId', $planId)
              ->update($planAuth);
  }

  /**
   * 修改 稽核计划
   * @param planCheck
   * @return
   */
  public function updatePlanCheck($planId, $planCheck)
  {
    return Db::table($this->planCheckTable())
              ->where('planId', $planId)
              ->update($planCheck);
  }

  /**
   * 修改计划 的稽核通过状态
   * @param planId
   * @return
   */
  public function updatePlanPass($planId)
  {
    return Db::table($this->planTable())
              ->where('id', $planId)
              ->update(['isFinish'=>2]);
  }

  /**
   * 联表查询plan 
   * basic sql for reused
   * 表名 alias :
   *   plan as p
   *   plan_gather as g
   *   plan_auth as a
   *   plan_check as c
   */
  protected function getPlansBasicSQL()
  {
    return Db::table($this->planTable())->alias('p')
        ->field('
          p.*, 
          g.gatherPlanTime, g.userId as gUserId, g.planTime, g.actualTime,
          a.userId as aUserId, a.authActualTime, a.authUserId, a.authPlanTime, 
          c.checkPlanTime, c.checkActualTime, c.userId as cUserId, c.checkUserId
        ',)
        ->leftJoin($this->planAuthTable().' a', 'p.id = a.planId')
        ->leftJoin($this->planCheckTable(). ' c', 'p.id = c.planId')
        ->leftJoin($this->planGatherTable(). ' g', 'p.id = g.planId');
  }

  /**
   * 根据认证项目查询认证计划
   * @param dirId 如果为空 则 查询所有
   * @return
   */
  public function getPlanByDirId($dirId)
  {
    return $this->getPlansBasicSQL()
            ->where('isFinish', 1)
            ->where('dirId', $dirId)
            ->select()->toArray();
  }

  /**
   * 计算计划数量 by dirId (table: plan)
   * @param dirId
   * @return integer
   */
  public function countPlanByDirId($dirId)
  {
    return Db::table($this->planTable())
              ->where('isFinish', 1)
              ->where('dirId', $dirId)
              ->count();
  }

  /**
   * 根据计划id查询认证信息
   * @param planId
   * @return
   */
  public function getPlanAuthById($planId)
  {
    return Db::table($this->planAuthTable())
              ->where('planId', $planId)
              ->find();
  }

  /**
   * 根据计划id查询收集资料信息
   * @param planId
   * @return
   */
  public function getPlanGatherById($planId)
  {
    return Db::table($this->planGatherTable())
              ->where('planId', $planId)
              ->find();
  }

  /**
   * 根据计划id查询稽核信息
   * @param planId
   * @return
   */
  public function getPlanCheckById($planId)
  {
    return Db::table($this->planCheckTable())
              ->where('planId', $planId)
              ->find();
  }

  /**
   * 最新的planId
   * @return
   */
  public function getPlanTop()
  {
    return Db::table($this->planTable())
              ->order('id desc')
              ->limit(1)
              ->find();
  }

  /**
   * 查询未完成计划中的计划认证内容是否有相同
   * @param content
   * @return
   */
  public function getSamePlan(string $content)
  {
    return Db::table($this->planTable())
              ->where('content', $content)
              ->where('isFinish', 1)
              ->find();
  }

  /**
   * 清空值
   * @param planId
   * @return
   */
  public function updatePlanAuthEmpty(array $planId)
  {
    return $this->updatePlanAuth($planId, [
                  'authPlanTime'=>null,
                  'userId' => null,
                  'authActualTime' => null,
                  'authUserId' => null
                ]);
  }

  /**
   * 清空值
   * @param planId
   * @return
   */
  public function updatePlanCheckEmpty(array $planId)
  {
    return $this->updatePlanCheck($planId, [
                'checkPlanTime'=>null,
                'userId' => null,
                'checkActualTime' => null,
                'checkUserId' => null
              ]);
  }

  /**
   * 更新认证计划表
   * @param planId
   * @param planAuth
   * @return
   */
  public function updatePlanAuthWithUser($planId, array $planAuth)
  {
    return Db::table($this->planAuthTable())
              ->where('planId', $planId)
              ->update($planAuth);
  }

  /**
   * 更新稽核计划表
   * @param planId
   * @param planCheck
   * @return
   */
  public function updatePlanCheckWithUser($planId, array $planCheck)
  {
    return Db::table($this->planCheckTable())
              ->where('planId', $planId)
              ->update($planCheck);
  }

  /**
   * 查询已完成的计划
   * @return
   */
  public function getFinishedPlan($dirId=null)
  {
    
    $bd = $this->getPlansBasicSQL()
              ->where('isFinish', 2);
    if ($dirId != null) {
      $bd = $bd->where('dirId', $dirId);
    }
              
    return $bd->select()->toArray();
  }

  /**
   * 获取计划信息根据主键id
   * @param id
   * @return
   */
  public function getPlanById($id)
  {
    return $this->getPlansBasicSQL()
              ->where('id', $id)
              ->find();
  }

  /**
   * 获取跟用户相关的计划
   * @param userId
   * @return array
   */
  public function getPlanByUser($userId)
  {

    $data = $this->getPlansBasicSQL()
              ->whereOr([
                ['p.userId', '=' ,$userId],
                ['g.userId', '=', $userId],
                ['a.authUserId', '=', $userId],
                ['a.userId', 'like', '%'.$userId.',%'],
                ['c.checkUserId', '=', $userId],
                ['c.userId', 'like', '%'.$userId.',%'],
              ])->select()->toArray();
    return $data;
  }

  /**
   * 删除计划根据主键id
   * @param id
   * @return
   */
  public function delPlan($id)
  {
    return Db::table($this->planTable())
            ->where('id', $id)
            ->delete();
  }

  /**
   * 删除资料收集计划根据主键id
   * @param id
   * @return
   */
  public function delPlanGather($id)
  {
    return Db::table($this->planGatherTable())
              ->where('id', $id)
              ->delete();
  }

  /**
   * 根据id修改计划
   * @param planId
   * @param plan
   * @return
   */
  public function updatePlanById($planId, array $plan)
  {
    return Db::table($this->planTable())
            ->where('id', $planId)
            ->update($plan);
  }

  /**
   * 根据plan id修改资料收集计划时间
   * @param id
   * @param planTime string
   * @return
   */
  public function updatePlanGatherById($id, $planTime)
  {
    return Db::table($this->planGatherTable())
              ->where('planId', $id)
              ->update([
                'planTime' => $planTime
              ]);
  }
  

  /**
   * 日志表名
   */
  protected function logTable()
  {
    return $this->database . '.log';
  }


  /**
   * 添加日志记录
   * @param log
   * @return
   */
  public function addLog($log)
  {
    return Db::table($this->logTable())
              ->insert($log);
  }

  /**
   * 根据用户查询操作日志
   * @param userId
   * @return
   */
  public function getLogListByUser($userId)
  {
    return Db::table($this->logTable())
              ->where('userId', $userId)
              ->find();
  }

  /**
   * 申请单表名
   * @return string
   */
  protected function recordTable()
  {
    return $this->database . '.record';
  }

  /**
   * 添加一条数据
   * @param record
   * @return
   */
  public function addRecord($record)
  {
    return Db::table($this->recordTable())
              ->insert($record);
  }
  
  /**
   * 获取所有待审核的申请单
   * @return
   */
  public function getRecordStatusOne()
  {
    return Db::table($this->recordTable())
              ->where('applyStatus', 1)
              ->select()->toArray();
  }

  /**
   * 根据申请状态显示
   * @param stauts 
   * @param userId
   * @return
   */
  public function getRecordByStatus($status, $userId)
  {
    return Db::table($this->recordTable())
              ->where('userId', $userId)
              ->where('applyStatus', $status)
              ->select()->toArray();
  }

  /**
   * 判断不能重复申请
   * @param where 判断条件 文件id，申请用户id，申请内容，申请状态为1
   * @return
   */
  public function findRecordOnly($where)
  {
    return Db::table($this->recordTable())
              ->where('userId', $where['userId'])
              ->where('applyStatus', 1)
              ->where('fileId', $where['fileId'])
              ->where('applyContent', $where['applyContent'])
              ->select()->toArray();
  }

  /**
   * 判断申请更新所有用户只能有个一用户申请
   * @param record 判断条件文件id，申请内容，申请状态为1
   * @return
   */
  public function findRecordOnlyApply($where)
  {
    return Db::table($this->recordTable())
              ->where($where)
              ->where('applyStatus', 1)
              ->select()->toArray();
  }

  /**
   * 更新审核通过但还未更新的，无法进行审核
   * @param record 判断条件文件id，申请内容，申请状态为2
   * @return
   */
  public function findRecordOnlyPass($where)
  {
    return Db::table($this->recordTable())
              ->where('applyStatus', 2)
              ->where($where)
              ->select()->toArray();
  }

  /**
   * 删除一条申请数据
   * @param id
   * @return
   */
  public function delRecord($id)
  {
    return Db::table($this->recordTable())
              ->where('id', $id)
              ->delete();
  }

  /**
   * 审核通过或不通过修改状态
   * @param record 记录id 操作人员 修改时间
   * @return
   */
  public function updateRecord($id, $record)
  {
    return Db::table($this->recordTable())
            ->where('id', $id)
            ->update($record);
  }

  /**
   * 将文件已更新的更新申请记录的修改为失效
   * @param record 主键id
   * @return
   */
  public function updateRecordOut($id)
  {
    return Db::table($this->recordTable())
              ->where('id', $id)
              ->update(['applyStatus'=>4]);
  }

  /**
   * 申请已通过，不能重复申请
   * 根据 fileId, userId, applyContent 三项查找
   * @param where 
   * @return
   */
  public function selectPass($where)
  {
    return Db::table($this->recordTable())
              ->where($where)
              ->where('applyStatus', 2)
              ->select()->toArray();
  }

  protected function directoryTable()
  {
    return $this->database . '.directory';
  }

  public function getAllDir()
  {
    return Db::table($this->directoryTable())
              ->select()->toArray();
  }

  public function getDirById($id)
  {
    return Db::table($this->directoryTable())
              ->where('id', $id)
              ->find();
  }
}
<?php
/*
 * @Author: yu chen
 * @Date: 2020-12-07 16:16:43
 * @LastEditTime: 2021-04-21 14:24:47
 * @LastEditors: Mok.CH
 * @Description: In User Settings Edit
 * @FilePath: \sverp\app\webApi\model\Record.php
 */

namespace app\webApi\model;

use think\facade\Db;

class Record
{
  protected $repair_record = starvc_homedb . '.prodlib_repair_record';
  protected $meche_info = starvc_homedb . '.prodlib_meche_info';
  protected $repair_log = starvc_homedb . '.prodlib_repair_log';
  protected $repair_notify_staff = starvc_homedb . '.prodlib_repair_notify_staff';
  protected $tmplib_fitting = starvc_homedb. '.tmplib_fitting';
  public function repair_record($field, $where, $page, $limit)
  {
    $data = Db::table($this->repair_record)
      ->alias('r')
      ->field($field)
      // ->join($this->meche_info . ' m', 'r.mechenum = m.mache_num')
      ->leftjoin($this->meche_info. ' m', 'r.mechenum = m.mache_num')
      ->where($where)
      ->order('id desc')
      ->limit($page, $limit)
      ->select()
      ->toArray();
    return $data;
  }
  public function repairRecord($field, $where, $page, $limit)
  {
    $data = Db::table($this->repair_record)
      ->field($field)
      ->where($where)
      ->limit($page, $limit)
      ->select()
      ->toArray();
    return $data;
  }
  public function add_record(array $rows): bool
  {
    $res = Db::name($this->repair_record)->insertAll($rows);
    return $res !== false;
  }
  public function get_record_detail(int $id): array{
    return Db::table($this->repair_record)->alias('rr')
              ->field('*, rr.id as id, m.id as mache_id')
              ->leftjoin($this->meche_info.' m', 'rr.mechenum = m.mache_num')
              ->where('rr.id', $id)->find();
  }
  public function update_record(string $where, array $row): bool
  {
    $res = Db::name($this->repair_record)
      ->where('id', $where)
      ->update($row);
    return $res !== false;
  }
  public function del_record(array $ids): bool
  {
    $res = Db::name($this->repair_record)->delete($ids);
    return $res !== false;
  }
  public function saveRepair_record(array $opt): bool
  {
    Db::startTrans();
    $flag = true;
    foreach ($opt as $k => $v) {
      if ($k == 'A') {
        foreach ($v as $ks => $vs) {
          unset($v[$ks]['mache_name']);
          unset($v[$ks]['expendtime']);
          unset($v[$ks]['id']);
          
          $v[$ks]['alarmtime'] = isset($vs['alarmtime']) ? strtotime($vs['alarmtime']) : time();
          $v[$ks]['reachtime'] = isset($vs['reachtime']) ? strtotime($vs['reachtime']):0;
          $v[$ks]['repairtime'] = isset($vs['repairtime'])? strtotime($vs['repairtime']):0;
          $v[$ks]['repairAttr'] = isset($vs['repairAttr'])? $vs['repairAttr']:'维修';
          $v[$ks]['repairstatus'] = isset($vs['repairstatus']) ? $vs['repairstatus']: 'false';
          
        }
        $flag = $flag && false !== $this->add_record($v);
      }
      if ($k == 'U') {
        foreach ($v as $ks => $vs) {
          $pkVal = array_keys($vs)[0];
          $row   = array_values($vs)[0];
          unset($row['expendtime']);
          if (array_key_exists('alarmtime', $row)) {
            $row['alarmtime'] = strtotime($row['alarmtime']);
          }
          if (array_key_exists('reachtime', $row)) {
            $row['reachtime'] = strtotime($row['reachtime']);
          }
          if (array_key_exists('repairtime', $row)) {
            $row['repairtime'] = strtotime($row['repairtime']);
          }
          $flag  = $flag && false !== $this->update_record($pkVal, $row);
        }
      }
      if ($k == 'D') {
        $flag  = $flag && false !== $this->del_record($v['id']);
      }
    }
    if ($flag) {
      Db::commit();
    } else {
      Db::rollback();
    }
    return $flag;
  }
  public function getMecheInfo($field, $where, $page, $limit)
  {
    return Db::table($this->meche_info)->field($field)->where($where)->limit($page, $limit)->select()->toArray();
  }
  public function addMeche(array $rows): bool
  {
    $res = Db::name($this->meche_info)->insertAll($rows);
    return $res !== false;
  }
  public function updateMeche(string $where, array $rows): bool
  {
    $res = Db::name($this->meche_info)->where('id', $where)->update($rows);
    return $res !== false;
  }
  public function delMeche(array $ids): bool
  {
    $res = Db::name($this->meche_info)->delete($ids);
    return $res !== false;
  }
  public function saveMecheInfo(array $opt): bool
  {
    Db::startTrans();
    $flag = true;
    foreach ($opt as $k => $v) {
      if ($k == 'A') {
        foreach ($v as $ks => $vs) {
          unset($v[$ks]['id']);
          if(empty($v[$ks]['status'])){
            $v[$ks]['status'] = 1;
          }
          $v[$ks]['create_time'] = date('Y-m-d H:i:s',time());
        }
        $flag = $flag && false !== $this->addMeche($v);
      }
      if ($k == 'U') {

        foreach ($v as $ks => $vs) {
          $pkVal = array_keys($vs)[0];
          $row   = array_values($vs)[0];
          $flag  = $flag && false !== $this->updateMeche($pkVal, $row);
        }
      }
      if ($k == 'D') {
        $flag  = $flag && false !== $this->delMeche($v['id']);
      }
    }
    if ($flag) {
      Db::commit();
    } else {
      Db::rollback();
    }
    return $flag;
  }
  public function getMecheNames()
  {
    return Db::table($this->meche_info)->field('mache_name')->group('mache_name')->select();
  }
  public function repairLogAdd($save)
  {
    return Db::table($this->repair_log)->insert($save);
  }
  public function getNotify($field, $where, $page, $limit)
  {
    return Db::table($this->repair_notify_staff)->field($field)->where($where)->limit($page, $limit)->select()->toArray();
  }
  public function saveNotifyStaff(array $opt): bool
  {
    Db::startTrans();
    $flag = true;
    foreach ($opt as $k => $v) {
      if ($k == 'A') {
        foreach ($v as $ks => $vs) {

          if (!$vs['notify_create_time']) {

            $v[$ks]['notify_create_time'] = date('Y-m-d H:i:s', time());
          }
        }
        $flag = $flag && false !== $this->addNotify($v);
      }
      if ($k == 'U') {
        foreach ($v as $ks => $vs) {
          $pkVal = array_keys($vs)[0];
          $row   = array_values($vs)[0];
          $flag  = $flag && false !== $this->updateNotify($pkVal, $row);
        }
      }
      if ($k == 'D') {
        $flag  = $flag && false !== $this->delNotifye($v['id']);
      }
    }
    if ($flag) {
      Db::commit();
    } else {
      Db::rollback();
    }
    return $flag;
  }
  public function addNotify(array $rows): bool
  {
    $res = Db::name($this->repair_notify_staff)->insertAll($rows);
    return $res !== false;
  }
  public function updateNotify(string $where, array $rows): bool
  {
    $res = Db::name($this->repair_notify_staff)->where('id', $where)->update($rows);
    return $res !== false;
  }
  public function delNotifye(array $ids): bool
  {
    $res = Db::name($this->repair_notify_staff)->delete($ids);
    return $res !== false;
  }
  public function addRecord(array $rows): int
  {
    $res = Db::name($this->repair_record)->insertGetId($rows);
    
    return $res;
  }
  public function updateRecord(string $where, array $rows): bool
  {
    $res = Db::name($this->repair_record)->where('id', $where)->update($rows);
    return $res !== false;
  }
  public function delFitting(array $ids): bool
  {
    $res = Db::name($this->tmplib_fitting)->delete($ids);
    return $res !== false;
  }
  protected function judgeFittingStatus($row)
  {
    if ($row['fitting_num'] < $row['fitting_msg_number']) {
      $row['fitting_msg_status'] = -1;
    } else {
      $row['fitting_msg_status'] = 1;
    }
    return $row;
  }
  public function addFitting(array $rows): bool
  {
    foreach($rows as $k=>$row) {
      $rows[$k] = $this->judgeFittingStatus($row);
    }
    $res = Db::name($this->tmplib_fitting)->insertAll($rows);
	
    return $res;
  }
  public function updateFitting(string $where, array $row): bool
  {
    // 处理更新配件时，判断配件数量是否达到警示值，更新警示状态
    $oldData = Db::name($this->tmplib_fitting)->where('id', $where)->find();
    $row = array_merge($oldData, $row);
    $row = $this->judgeFittingStatus($row);
    $res = Db::name($this->tmplib_fitting)->where('id', $where)->update($row);
    return $res !== false;
  }
  public function saveFitting(array $opt): bool
  {
    Db::startTrans();
    $flag = true;
    foreach ($opt as $k => $v) {
      if ($k == 'A') {
        $flag = $flag && false !== $this->addFitting($v);
      }
      if ($k == 'U') {
        foreach ($v as $ks => $vs) {
          $pkVal = array_keys($vs)[0];
          $row   = array_values($vs)[0];
          $flag  = $flag && false !== $this->updateFitting($pkVal, $row);
        }
      }
      if ($k == 'D') {
        $flag  = $flag && false !== $this->delFitting($v['id']);
      }
    }
	
    if ($flag) {
      Db::commit();
    } else {
      Db::rollback();
    }
    return $flag;
  }
  public function getFitting($field, $where, $page, $limit)
  {
    return Db::table($this->tmplib_fitting)->field($field)->where($where)->limit($page, $limit)->select()->toArray();
  }
}

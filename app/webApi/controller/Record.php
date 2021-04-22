<?php
/*
 * @Author: yu chen
 * @Date: 2020-12-07 16:23:05
 * @LastEditTime: 2021-04-22 09:34:49
 * @LastEditors: Mok.CH
 * @Description: In User Settings Edit
 * @FilePath: \sverp\app\webApi\controller\Record.php
 */

namespace app\webApi\controller;



use app\webApi\model\Record as recordModel;
use app\webApi\validate\Record as recordValidate;
use think\exception\ValidateException;

use think\facade\Log;

require_once '../vendor/phpqrcode/phpqrcode.php';

class Record
{
  private $ac = 'send';
  private $uid = 'starvincci';
  private $template = '427538';
  private $password = 'e14be1b108a8ca237108f33b072f9ab6';
  public function __construct()
  {
  }
  /**查询维修记录
   * @param pro_time_end 结束时间
   * @param pro_time_star 开始时间
   * @param repaircontents 内容
   * @param repairAttr 分类
   * @param mechenum 机甲号
   * @return json
   */
  public function repair()
  {
    $data['msg'] = '数据返回错误';
    $data['code'] = 1;
    if (!request()->isPost()) {
      return json($data);
    }
    try {
      validate(recordValidate::class)->check([
        'pro_time_end'  => request()->param('pro_time_end'),
        'pro_time_star'   => request()->param('pro_time_star'),
        'mechenum' => request()->param('mechenum'),
        'repairAttr' => request()->param('repairAttr'),
      ]);
    } catch (ValidateException $e) {
      // 验证失败 输出错误信息
      dump($e->getError());
    }
    $where = array();
    $where[] = ['dell_repair', '=', 0];
    // $where[] = ['repairstatus', '=', 'true'];
    $data['repairAttr'] = request()->param('repairAttr');
    $data['pro_time_star'] = request()->param('leftTime');
    $data['pro_time_end'] = request()->param('rightTime');
    $data['repaircontents'] = request()->param('repaircontents');
    $data['mechenum'] = request()->param('mechenum');
    if (request()->param('repairAttr')) {
      $where[] = ['repairAttr', '=', request()->param('repairAttr')];
    }
    if (request()->param('leftTime') || request()->param('rightTime')) {
      $pro_time_star = strtotime(request()->param('leftTime'));
      $pro_time_end = strtotime(request()->param('rightTime'));
      if ($pro_time_star && $pro_time_end) {
        $where[] = ['repairtime', '>', "$pro_time_star"];
        $where[] = ['repairtime', '<', "$pro_time_end"];
      } elseif ($pro_time_star && !$pro_time_end) {
        $where[] = ['repairtime', '>', "$pro_time_star"];
      } elseif ($pro_time_end && !$pro_time_star) {
        $where[] = ['repairtime', '<', "$pro_time_end"];
      }
    }
    if (request()->param('repaircontents')) {
      $where[] = ['repaircontents', 'LIKE', '%' . request()->param('repaircontents') . '%'];
    }
    if (request()->param('mechenum')) {
      $where[] = ['mechenum', '=', request()->param('mechenum')];
    }
    // 新增：报修人查询
    if (request()->param('reporterConId')) {
      $where[] = ['reporter_con_id', '=', request()->param('reporterConId')];
    }
    $cnd = $where;
    $result = array();
    if (request()->param('error')) {
      $result = $this->countError($cnd);
      $data['data'] = $result['data'];
      $data['count'] = $result['count'];
    } else {
      $record = new recordModel;
      $field = 'r.id,r.mechenum,r.alarmtime,r.reachtime,r.repaircontents,r.repairmethod,r.repairman,r.repairtime,r.repairAttr,r.repairstatus,m.mache_name';
      $data['data'] = $record->repair_record($field, $cnd, $page = 0, $limit = 10000);
      foreach ($data['data'] as $key => $value) {
        if (!empty(intval($value['repairtime']))) {
          $data['data'][$key]['expendtime'] = (intval($value['repairtime']) - intval($value['alarmtime'])) / 60;
        } else {
          $data['data'][$key]['expendtime'] = 0;
        }
        $data['data'][$key]['alarmtime'] = date('Y-m-d H:i:s', $value['alarmtime']);
        $data['data'][$key]['reachtime'] = date('Y-m-d H:i:s', intval($value['reachtime']));
        $data['data'][$key]['repairtime'] = date('Y-m-d H:i:s', $value['repairtime']);
      }
    }
    $data['code'] = 0;
    $data['msg'] = 'success';
    return json($data);
  }

  public function getRepairDetail () {
    $data['code'] = 1;
    $data['msg'] = 'Object Not Found';
    $id = request()->param('recordId');
    $record_info = (new recordModel)->get_record_detail($id);
    if ($record_info) {
      $record_info['alarmtime'] = date('Y-m-d H:i:s', $record_info['alarmtime']);
      $record_info['reachtime'] = date('Y-m-d H:i:s', intval($record_info['reachtime']));
      $record_info['repairtime'] = date('Y-m-d H:i:s', $record_info['repairtime']);
      $record_info['create_time'] = date('Y-m-d H:i:s', $record_info['create_time']);
    }
    $data['data'] = $record_info;
    
    if ($record_info) {
      $data['code'] = 0;
      $data['msg'] = 'success';
    }
    
    return json($data);
  }

  /**
   * 故障统计
   * @param mechenum
   * @param startime
   * @param endtime
   * @param repairAttr 
   * @param repaircontents
   * @return json
   * 
   */
  public function countError($whereArr)
  {
    $recordModel = new recordModel;
    $count = 0;
    $field = 'id,mache_num,create_time,line_num,produc_num,mache_name';
    $where['status'] = '1';
    $mache_num = $recordModel->getMecheInfo($field, $where, $page = 0, $limit = 100000);
    $res = array();
    foreach ($mache_num as $key => $value) {
      $whereArr[] = ['mechenum', '=', $value['mache_num']];
      $res[$key] = $recordModel->repairRecord('*', $whereArr, $page = 0, $limit = 100000);
      array_pop($whereArr);
      $mache_num[$key]['errCount'] = count($res[$key]);
      $count += count($res[$key]);
    }
    $data['data'] = $mache_num;
    $data['count'] = $count;
    return $data;
  }
  /**
   * 维修增删查改接口
   * @param 
   * @return json
   */
  public function saveRepair()
  {
    $opt = request()->param('saveRepair');
    if ($opt) {
      $res = (new recordModel())->saveRepair_record($opt);
      $rtn['result'] = $res;
      return json($rtn);
    }
  }
  /**
   *机甲信息
   * @return json
   * 
   */
  public function getMecheInfo()
  {
    // if (!request()->isPost()) {
    //   $data['code'] = 1;
    //   $data['msg'] = 'error';
    // } else {
    $record = new recordModel;
    $field = 'id,line_num,produc_num,mache_num,mache_name,keeper,status,create_time';
    $where[] = ['status', '>', '0'];
    if (request()->param('line_num')) {
      $where[] = ['line_num', '=', request()->param('line_num')];
    }
    if (request()->param('status')) {
      $where[] = ['status', '=', request()->param('status')];
    }
    if (request()->param('limit')) {
      $page = request()->param('page');
      $limit = request()->param('limit');
    }
    $data['result'] = $record->getMecheInfo($field, $where, $page = 0, $limit = 10000);
    $data['code'] = 0;
    $data['msg'] = 'success';
    return json($data);
    // }
  }
  /**
   * 机器增删查改接口
   * @param 
   * @return json
   */
  public function saveMecheInfo()
  {
    $opt = request()->param('mecheParam');
    if ($opt) {
      $res = (new recordModel())->saveMecheInfo($opt);
      $rtn['result'] = $res;
      return json($rtn);
    }
  }

  /**
   * 短信通知
   * @param id 
   * @
   */
  // public function sendMsg()
  // {
  //   $param = request()->param('param');
  //   $ac = $this->ac;
  //   $uid = $this->uid;
  //   $pwd = $this->password;
  //   $template = $this->template;
  //   $content = json_encode(["code" => '异常：制' . $param['row']['line_num'] . '线,第' . $param['row']['produc_num'] . '工程,设备' . $param['row']['mache_num'] . '(' . $param['row']['mache_name'] . '),' . $param['cause']]);
  //   $mobile = implode(',', $param['arr']);
  //   $res = array();
  //   $url = 'http://api.sms.cn/sms/?ac=' . $ac . '&uid=' . $uid . '&pwd=' . $pwd . '&template=' . $template . '&mobile=' . $mobile . '&content=' . $content;
  //   $result = $this->_httpGet($url);
  //   $result = json_decode($result);
  //   if ($result->stat === '100') {
  //     $num = array();
  //     for ($i = 0; $i < count($param['arr']); $i++) {
  //       $data = [
  //         'repair_phone' => $param['arr'][$i],
  //         'repair_content' => $content,
  //         'repair_name' => 'test',
  //         'repair_create_time' => time()
  //       ];
  //       $num[$i] = (new recordModel)->repairLogAdd($data);
  //     }
  //     $res['code'] = 0;
  //     $res['msg'] = '短信发送成功';
  //   } else {
  //     $res['code'] = 1;
  //     $res['msg'] = '短信发送失败';
  //   }
  //   return json($res);
  // }

  // protected function _httpGet($url = "")
  // {
  //   $curl = curl_init();
  //   curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  //   curl_setopt($curl, CURLOPT_TIMEOUT, 500);
  //   // 为保证第三方服务器与微信服务器之间数据传输的安全性，所有微信接口采用https方式调用，必须使用下面2行代码打开ssl安全校验。
  //   // 如果在部署过程中代码在此处验证失败，请到 http://curl.haxx.se/ca/cacert.pem 下载新的证书判别文件。
  //   // curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
  //   // curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
  //   curl_setopt($curl, CURLOPT_URL, $url);
  //   $res = curl_exec($curl);
  //   curl_close($curl);
  //   return $res;
  // }
  protected function qrcode($data, $size = 4)
  {
    header("Content-type:image/png");
    $qRcode = new \QRcode();
    //data网址或者是文本内容
    // 纠错级别：L、M、Q、H
    $level = 'L';
    // size点的大小：1到10,用于手机端4就可以了
    // 生成的文件名
    $data = 'test1';
    $qRcode->png($data, false, $level, $size);
    $imagestring = base64_encode(ob_get_contents());
    ob_end_clean();
    return "<img src='data:image/png;base64,{$imagestring}'/>";
  }
  public function getNotice()
  {
    $record = new recordModel;
    $field = '*';
    $where = '';
    if (request()->param('limit')) {
      $page = request()->param('page');
      $limit = request()->param('limit');
    }
    $data['result'] = $record->getNotify($field, $where, $page = 0, $limit = 10000);
    $data['code'] = 0;
    $data['msg'] = 'success';
    return json($data);
  }
  public function saveNotice()
  {
    $opt = request()->param('saveNoticStaff');
    if ($opt) {
      $res = (new recordModel())->saveNotifyStaff($opt);
      $rtn['result'] = $res;
      return json($rtn);
    }
  }
  /**
   * 短信通知
   * @param id 
   * @
   */
  public function sendMsg()
  {
    $param = request()->param('param');
    if (!empty($param['row']['line_num']) && !empty($param['row']['produc_num'] && !empty($param['row']['mache_num']) && !empty($param['row']['mache_name']))) {
      $content['part'] = 'TPM';
      $content['number'] = $param['row']['line_num'];
      $content['line_num'] = $param['row']['produc_num'];
      $content['meche_num'] = $param['row']['mache_num'];
      $content['meche_name'] = $param['row']['mache_name'] . $param['cause']; //机器名和初步原因
      $phone = implode(',', $param['arr']);
      if ($phone && $content['number'] && $content['line_num'] && $content['meche_num'] && $content['meche_name']) {
        $res = smsSend($phone, '文迪软件', 'SMS_207970725', $content);
        if ($res['Code'] === 'OK') {
          $data = [
            'mechenum' => $param['row']['mache_num'],
            'alarmtime' => time(),
            'repairAttr' => $param['cate'] ? $param['cate'] : '',
            'repairstatus' => 'false',
            'dell_repair' => 0,
            'reporter_con_id' => isset($param['reporterConId'])?$param['reporterConId']:'',
            'reporter_name' => isset($param['reporterName'])?$param['reporterName']:''
          ];
          $record = new recordModel;
          $id = $record->addRecord($data);
          $list['id'] = $id;
        }
      }
    } elseif (!empty($param['cause']) && !empty($param['mecheName']) && !empty($param['noticeName'][0])) {
      $param['phone'] = implode(',', $param['noticeName']);
      $img = cookie('url');
      $data = [
        'repair_phone' => $param['phone'],
        'repair_create_time' => time(),
        'repair_content' => $param['cause'],
        'repair_name' => $param['mecheName'],
        'repair_department' => $param['noticeDepartment'],
        'repair_img' => $img ? $img : '',
      ];
      $content = [
        'department' => $param['noticeDepartment'].' '.(isset($param['address'])?$param['address']:''), 
        'meche' => $param['mecheName'], 
        'cause' => $param['cause'], 
        'time' => date('Y-m-d H:i:s', time())
      ];
      $res = smsSend($param['phone'], '文迪软件', 'SMS_210075241', $content); //发送短信
      $res['Code'] = 'OK';
      if ($res['Code'] === 'OK') {
        $record = new recordModel;
        $id = $record->repairLogAdd($data);
      }
    }
    $list['code'] = 1;
    $list['msg'] = '发送失败';
    if (!empty($id)) {
      $list['code'] = 0;
      $list['msg'] = '发送成功';
    }
    return json($list);
  }
  /**
   * 到场人验证
   * @param 
   * @return json
   */
  public function checkCode()
  {
    $id = request()->param('id');
    $phone = request()->param('phone');
    $phoneFour = request()->param('phoneFour');
    $record = new recordModel;
    $where['notify_phone'] = $phone;
    $list = $record->getNotify('id,notify_phone,notify_name', $where, 0, 10000);
    if ($list[0]['notify_name'] && $phone && $phoneFour && $id) {
      $data['repairman'] = $list[0]['notify_name'];
      $data['reachtime'] = time();
      $res = $record->updateRecord($id, $data);
      if ($res) {
        $msg['code'] = 0;
        $msg['msg'] = 'success';
      } else {
        $msg['code'] = 1;
        $msg['msg'] = 'error';
      }
      return json($msg);
    }
  }
  /**
   * 文件上传
   * @param file
   */
  public function upload()
  {
    $files = request()->file('avatar');
    if (!empty($files)) {
      try {
        validate(['image' => 'filesize:10240|fileExt:jpg|image:200,200,jpg'])
          ->check(['file' => $files]);
        $savename = \think\facade\Filesystem::putFile('images', $files);
        // $savename = [];
        // foreach($files as $file) {
        //     $savename[] = \think\facade\Filesystem::putFile( 'topic', $file);
        // }
        $data['name'] = '';
        $data['status'] = '';
        $data['thumbUrl'] = '';
        $data['url'] = 'http://' . $_SERVER['HTTP_HOST'] . '/static/' . $savename;
        cookie('imgUrl', 'http://' . $_SERVER['HTTP_HOST'] . '/static/' . $savename, 3600);
        $imgurl=$data['url'];
        return json($data);
      } catch (\think\exception\ValidateException $e) {
        echo $e->getMessage();
        die;
      }
    }
  }
  /**
   *获取配件信息
   *
   *
   */
  public function getFitting()
  {
    $record = new recordModel;
    $field = '*';
    $where = '';
    if (request()->param('limit')) {
      $page = request()->param('page');
      $limit = request()->param('limit');
    }
    $data['result'] = $record->getFitting($field, $where, $page = 0, $limit = 10000);
    $data['code'] = 0;
    $data['msg'] = 'success';
    return json($data);
  }
  /**
   *配件信息数据操作
   *
   *
   */
  public function saveFitting()
  {
    $opt = request()->param('saveFittingList');
    //print_r($opt);die;
    if ($opt) {
      $res = (new recordModel())->saveFitting($opt);
      $rtn['result'] = $res;
      return json($rtn);
    }
  }
  /**
   *配件不足
   *
   */
  public function fittingMsg($phone, $content)
  {
    $record = new recordModel;
    $field = '*';
    $where = '';
    if (request()->param('limit')) {
      $page = request()->param('page');
      $limit = request()->param('limit');
    }
    $result = $record->getFitting($field, $where, $page = 0, $limit = 10000);
    foreach ($result as $v) {
      if ($v['fitting_num'] < $v['fitting_msg_number'] && $v['fitting_msg_status'] === 1) {
        $data['fitting_msg_status'] = -1;
        $record->updateFitting($v['id'], $data);
        $res = smsSend($phone, '文迪软件', 'SMS_210070263', $content); //发送短信
        if ($res['Code'] === 'OK') {
          //修改状态
          $msg['code'] = 0;
          $msg['msg'] = '短信发送成功';
        }
      }
    }

    return $msg;
  }
  /**
   *维修完成
   *
   */
  public function repairComplete()
  {
    $params = request()->param('params');
    if ($params) {
      $notice = [];
      $fitting_name = '';
      $fitting_number = '';
      $record = new recordModel;
      $id = $params['id'];
      $rows['repairtime'] = time();
      $rows['repairstatus'] = 'true';
      $rows['repaircontents'] = $params['content'];
      $rows['repairmethod'] = $params['action'];
      $res = $record->updateRecord($id, $rows);

      if (!empty($res) && !empty($params['number'])) {
        $list = array_filter($params['number']);

        foreach ($list as $k => $v) {
          $where['id'] = $k;
          $field = '*';
          if (!empty($v)) {
            $result = $record->getFitting($field, $where, $page = 0, $limit = 10000);
          }
          if (!empty($result) && !empty($result[0]['fitting_num']) && ($result[0]['fitting_num'] - $v) >= 0) {
            // $data['fitting_num'] = $result[0]['fitting_num'] - $v;
            $data['fitting_consume_num'] = $result[0]['fitting_consume_num'] + $v;
            $data['fitting_msg_status'] = intval($result[0]['fitting_msg_status']); //由于下一次循环没有定义该值所以需要默认数据库的值
            if ($data['fitting_num'] < $result[0]['fitting_msg_number'] && $result[0]['fitting_msg_status'] === 1) {
              $data['fitting_msg_status'] = -1;
              $fitting_name .= $result[0]['fitting_name'] . '、';
              $fitting_number .= $data['fitting_num'] . '、';
            }
            $record->updateFitting($k, $data);
          } else {
            $msg['msg'] = '配件不足';
          }
        }
        $fitting_name = mb_substr($fitting_name, 0, -1, "UTF-8");
        $fitting_number = mb_substr($fitting_number, 0, -1, "UTF-8");
        if (!empty($fitting_name) && !empty($fitting_number)) {
          $notice['fitting'] = $fitting_name;
          $notice['number'] = $fitting_number;
          $notice['time'] = date('Y-m-d H:i:s', time());
          $phone = '';
          $whereArr['notify_people'] = 2;
          $noticeArr = $record->getNotify('notify_people,notify_phone', $whereArr, 0, 1000);
          foreach ($noticeArr as $key => $value) {
            $phone .= $value['notify_phone'] . ',';
          }
          $phone = substr($phone, 0, -1);
          if (!empty($phone)) {
            $respon = [];
            $respon = smsSend($phone, '文迪软件', 'SMS_210070263', $notice); //发送短信
            if ($respon['Code'] === 'OK') {
              //修改状态
              $msg['msg'] = '短信发送成功';
            }
          }
        }
      }
      $msg['code'] = 0;
      return json($msg);
    }
  }

  
  /**
   * 获取设备名称列表 以便快速输入设备名称
   * 
   */
  public function getMecheNames() 
  {
    return json((new recordModel)->getMecheNames());
  }
  
  /**
   * 获取其它部门的报修记录 log 表
   */
  public function getrepairLogs()
  {
    $param = request()->param();
    $page = request()->param('page', 0);
    $limit = request()->param('limit', 1000);
    $where = [];
    if (isset($param['reporterConId'])) {
      $where['reporter_con_id'] = $param['reporterConId'];
    }
    $model = new recordModel();
    $data = $model->getRepairLogs($where, $page, $limit);
    foreach ($data as $k =>$v) {
      $data[$k]['repair_create_time'] = date('Y-m-d H:i:s', $v['repair_create_time']);
      $data[$k]['repair_done_time'] = date('Y-m-d H:i:s', $v['repair_done_time']);
    }
    return json($data);
  }

}

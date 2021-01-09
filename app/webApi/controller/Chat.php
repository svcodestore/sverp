<?php
/*
 * @Date: 2020-12-30 16:47:57
 * @LastEditors: yu chen
 * @LastEditTime: 2021-01-08 10:27:38
 * @FilePath: \sverp\app\webApi\controller\Chat.php
 */

namespace app\webApi\controller;


use GatewayClient\Gateway;
use think\facade\Cache;
use app\webApi\model\Chat as chatModel;
use  think\facade\View;

require_once '../vendor/autoload.php';
class Chat
{
    public function __construct()
    {
        // Gateway::$registerAddress = '127.0.0.1:1236';
    }
    public function index()
    {
        return View::fetch('chat/index');
    }
    public function bind($uid, $client_id, $group_id = '') //绑定用户id
    {
        // client_id与uid绑定
        Gateway::bindUid($client_id, $uid);
        // 加入某个群组（可调用多次加入多个群组）
        Gateway::joinGroup($client_id, $group_id);
    }
    public function send_msg($to_uid, $message)
    {
        // 向任意uid的网站页面发送数据
        Gateway::sendToUid($to_uid, $message);
    }
    public function send_msg_group($uid, $group, $message) //发送消息
    {
        // 设置GatewayWorker服务的Register服务ip和端口，请根据实际情况改成实际值(ip不能是0.0.0.0)
        // Gateway::$registerAddress = '127.0.0.1:1236';
        // 向任意uid的网站页面发送数据
        Gateway::sendToUid($uid, $message);
        // 向任意群组的网站页面发送数据
        Gateway::sendToGroup($group, $message);
    }
    //   public function test_redis()
    //   {
    //     Cache::store('redis')->set('name', 'value', 3600);
    //     return Cache::store('redis')->get('name');
    //   }
    /**
     * 测试
     * @param uid 当前用户的id
     * @param content 发送到客户端的消息
     * @param time
     * @param client_name
     * @param client_id
     * @param uidAll
     * @param uidCount
     * @param to_uid 发送对象的用户id
     * @param group_id 当前组织id
     * @param to_group_id 要发生的组织
     */
    public function test()
    {
        $param = input();
        // print_r($param);die;
        switch ($param['type']) {
            case 'init':
                // $this->bind($param['id'], $param['client_id'], $param['group_id']);
                // $msg['type'] = 'say';
                // $msg['content'] = '绑定成功';
                // Gateway::sendToUid($param['id'], json_encode($msg));
                $this->bind($param['client_name'], $param['client_id'], $param['group_id']);
                //绑定成功后发送成功消息
                $msg['type'] = 'login';
                $msg['client_name'] = $param['client_name'];
                $msg['content'] = $param['client_name'] . '加入聊天室';
                $msg['time'] = date('Y-m-d H:i:s', time());
                $msg['uidCount'] = Gateway::getAllUidCount();
                $msg['uidAll'] =  Gateway::getAllUidList();//获取所有在线绑定的uid
                Gateway::sendToGroup($param['group_id'],json_encode($msg));
                // Gateway::sendToAll( json_encode($msg));
                break;
            case 'say':
                $msg['type']='say';
                $msg['time'] = date('Y-m-d H:i:s', time());
                if($param['to_uid']){
                    $msg['content'] = $param['content'];
                    $to_uid = $param['to_uid'];
                    Gateway::sendToUid($to_uid, json_encode($msg));
                }elseif($param['to_group_id']){
                    $msg['content'] = $param['content'];
                    $to_group_id = $param['to_group_id'];
                    Gateway::sendToGroup($to_group_id, json_encode($msg));
                }
                break;
            case 'all':
                // print_r($param['content']);die;
                $msg['type'] = 'all';
                $msg['time'] = date('Y-m-d H:i:s', time());
                $msg['content'] = $param['content'];
                Gateway::sendToAll(json_encode($msg));
                break;
            default:
                break;
        }
        return;
    }
}

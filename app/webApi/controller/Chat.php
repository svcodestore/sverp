<?php
/*
 * @Date: 2020-12-30 16:47:57
 * @LastEditors: yu chen
 * @LastEditTime: 2021-02-01 08:30:14
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
	private $redis;
	public $checkUserReadable = false;
    public function __construct()
    {
        	$this->redis = new \RedisConnect();
			$this->redis = $this->redis->connectRedis();
    }
    /**
     * 通讯主接口
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
    public function chat()
    {
		$param = input();
		if(!empty($param['type'])){

			// print_r($param);die;
			switch ($param['type']) {
				case 'init':
					Gateway::bindUid( $param['client_id'],$param['client_name']);
					//绑定成功后发送成功消息
					$msg['type'] = 'login';
					$msg['client_name'] = $param['client_name'];
					$msg['content'] = $param['client_name'] . '上线';
					$msg['time'] = date('Y-m-d H:i:s', time());
					$msg['uidCount'] = Gateway::getAllUidCount();
					$msg['uidAll'] =  [];//获取所有在线绑定的uid
					$list = [];
					$list = Gateway::getAllUidList();
					$list = implode(',',$list);
					$msg['uidAll'] = explode(',',$list);
					Gateway::sendToAll(json_encode($msg));
					$this->redis->hset('userinfo',$param['client_name'],$param['client_id']);
					break;
				case 'say':
					if(!empty($this->redis->hget('userinfo',$param['uid']))){
						$msg['type']='say';
						$msg['time'] = date('Y-m-d H:i:s', time());
						$msg['uid'] = $param['uid'];
						if(!empty($param['to_uid'])){
							$msg['content'] = $param['content'];
							$msg['to_uid'] = $param['to_uid'];
							if($this->setChatRecord($param['uid'],$param['to_uid'],$param['content'])!==false){
								Gateway::sendToUid($param['to_uid'],json_encode($msg));
							}
						}elseif(!empty($param['to_group_id'])){
							$msg['content'] = $param['content'];
							$to_group_id = $param['to_group_id'];
							//if($this->setChatRecord($param['uid'],$param['to_group_id'],$param['content'])!==false){
								Gateway::sendToGroup($to_group_id, json_encode($msg));
							//}
						}
						//print_r($this->redis->lrange('chat',0,-1));
					}
					break;
				case 'all':
					if(!empty($this->redis->hget('userinfo',$param['uid']))){
						$msg['uid'] = $param['uid'];
						$msg['type'] = 'all';
						$msg['time'] = date('Y-m-d H:i:s', time());
						$msg['content'] = $param['content'];
						//if($this->setChatRecord($param['uid'],$param['content'])!==false){
							Gateway::sendToAll(json_encode($msg));
						//}
					}
					break;
				case 'loginout':
					$num = $this->redis->hdel('userinfo',$param['clientName']);
					if($param['clientId']&&$param['clientName']&&!empty($num)){
						$msg['content']=$param['clientName'].'退出登录';
						$msg['type']='logout';
						Gateway::closeClient($param['clientId']);
						$msg['uidCount'] = Gateway::getAllUidCount();
						$msg['uidAll'] =  [];//获取所有在线绑定的uid
						$list = [];
						$list = Gateway::getAllUidList();
						$list = implode(',',$list);
						$msg['uidAll'] = explode(',',$list);
						Gateway::sendToAll(json_encode($msg));
					}
					break;
				default:
					break;
			}
			return json(['code'=>0]);
		}
    }
	/**
	*聊天请求数据
	*@from
	*@to 
	*@return json
	*/
	public function getChatList()
	{
		$params=input();
		$rtn['result'] = $this->getChatRecord($params['uid'],$params['toUid'],15);
		$rtn['code'] = 0;
		return	json($rtn);
	}
	/**
	*设置键名
	*
	*/
	protected function getRecKeyName($from,$to){
		return strnatcmp($from,$to)>0 ? $from.'_'.$to : $to.'_'.$from;
	}
	/*
     * 将消息设为已读
     * 当一个用户打开另一个用户的聊天框时，将所有未读消息设为已读
     * 清楚未读消息中的缓存
     * @from 消息发送者id
     * @to 消息接受者id
     *
     * 返回值，成功将未读消息设为已读则返回true,没有未读消息则返回false
     */
    protected function setUnreadToRead($from, $to) {
        $res = $this -> redis -> hDel('unread_' . $to, $from);
        return (bool)$res;
    }
	   /*
     * 当用户不在线时，或者当前没有立刻接收消息时，缓存未读消息,将未读消息的数目和发送者信息存到一个与接受者关联的hash数据中
     *
     * @from 发送消息的用户id
     * @to 接收消息的用户id
     *
     * 返回值，当前两个用户聊天中的未读消息
     *
     */
    private function cacheUnreadMsg($from, $to) {
        return $this -> redis -> hIncrBy('unread_' . $to, $from, 1);
    }
	 /*
     * 获取未读消息的内容
     * 通过未读消息数目，在列表中取得最新的相应消息即为未读
     * @from 消息发送者id
     * @to 消息接受者id
     *
     * 返回值，包括所有未读消息内容的数组
     *
     *
     */
    protected function getUnreadMsg($from, $to) {
        $countArr = $this -> getUnreadMsgCount($to);
        $count = $countArr;
        $keyName = 'rec:' . $this -> getRecKeyName($from, $to);
        return $this -> redis -> lRange($keyName, 0, (int)($count));
    }
	   /*
     * 当用户上线时，或点开聊天框时，获取未读消息的数目
     * @user 用户id
     *
     * 返回值，一个所有当前用户未读的消息的发送者和数组
     * 数组格式为‘消息发送者id’＝>‘未读消息数目’
     *
     */
    protected function getUnreadMsgCount($user) {
        return $this -> redis -> hGetAll('unread_' . $user);
    }
	 /*
     *发送消息时保存聊天记录
     * 这里用的redis存储是list数据类型
     * 两个人的聊天用一个list保存
     *
     * @from 消息发送者id
     * @to 消息接受者id
     * @meassage 消息内容
     *
     * 返回值，当前聊天的总聊天记录数
     */
    protected function setChatRecord($from, $to, $message) {
        $data = array('uid' => $from, 'to_uid' => $to, 'content' => $message, 'sent' => time(), 'recd' => 0);
        $value = json_encode($data);
        //生成json字符串
        $keyName = 'rec:' . $this -> getRecKeyName($from, $to);
        //echo $keyName;
        $res = $this -> redis -> rPush($keyName, $value);
        if (false !== $this -> checkUserReadable) {//消息接受者无法立刻查看时，将消息设置为未读
            $this -> cacheUnreadMsg($from, $to);
        }
        return $res;
    }
    /*
     * 获取聊天记录
     * @from 消息发送者id
     * @to 消息接受者id
     * @num 获取的数量
     * 返回值，指定长度的包含聊天记录的数组
     */
    protected function getChatRecord($from, $to, $num) {
        $keyName = 'rec:' . $this -> getRecKeyName($from, $to);
		//$this -> redis ->LTRIM ($keyName,0,0);
        //echo $keyName;
        $recList = $this -> redis -> lRange($keyName, -$num,-1);
        return $recList;
    }
}

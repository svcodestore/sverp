<?php
/*
 * @Date: 2021-06-30 11:16:28
 * @LastEditors: Mok.CH
 * @LastEditTime: 2021-07-01 15:46:49
 * @FilePath: \sverp\app\service\WeChat.php
 */

namespace app\service;

use app\webApi\model\UserWechat;
use Wxpusher;
use think\Service;

class WeChat extends Service
{
    protected $pusher;
    protected $_token;
    public function register()
    {
        $this->_token = 'AT_Dl6oNbsjKmjOSv2KNeOSODfxSV7tWedY';
        $this->pusher = new Wxpusher($this->_token);
        $this->app->bind('WeChat', $this);
    }

    /**
     * 通过微信公众号发送文本信息
     * @param string $con_id 用户系统账号名
     * @param string $text 内容
     * @return bool
     */
    public function sendTextMsg($con_id, $text)
    {
        $model = new UserWechat();
        $wx_uid = [];
        is_string($con_id) and $con_id = [$con_id];

        foreach ($con_id as $cid) {
            $user = $model->getUser($cid);
            if ($user) {
                $wx_uid[] = $user['wx_uid'];
            }
        }

        if (count($wx_uid)) {
            return $this->pusher->send($text, 1, true, $wx_uid);
        }
        return false;
    }

    /**
     * 生成用于扫描关注的二维码
     * @param string $extra 额外信息(用于区别扫码人)
     * @return bool
     */
    public function Qrcreate($extra = '')
    {
        return $this->pusher->Qrcreate($extra);
    }
}

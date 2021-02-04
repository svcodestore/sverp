<?php
/*
 * @Date: 2021-01-07 15:33:22
 * @LastEditors: yu chen
 * @LastEditTime: 2021-02-04 14:58:31
 * @FilePath: \sverp\app\webApi\api\Chat.php
 */

namespace app\webApi\api;

use app\BaseController;
use app\webApi\controller\Chat as ControllerChat;

class Chat extends BaseController
{
    public function apiChat()
    {

        return (new ControllerChat())->chat();
    }
    public function apiChatRecord()
    {

        return (new ControllerChat())->getChatList();
    }
    public function apiUnreadCount()
    {
        return (new ControllerChat())->getUnreadCount();
    }
}

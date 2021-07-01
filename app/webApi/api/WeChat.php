<?php

namespace app\webApi\api;

use app\BaseController;
use app\webApi\controller\WeChat as ControllerWeChat;

class WeChat extends BaseController
{
  public function subscribeQRCode()
  {
    return (new ControllerWeChat())->genQrcode();
  }

  public function checkScan()
  {
    return (new ControllerWeChat())->checkScan();
  }

  public function getUserInfo()
  {
    return (new ControllerWeChat())->getUserInfo();
  }
}

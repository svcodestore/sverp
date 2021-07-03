<?php
/*
 * @Date: 2021-06-30 10:59:20
 * @LastEditors: Mok.CH
 * @LastEditTime: 2021-07-03 09:31:48
 * @FilePath: \sverp\app\webApi\controller\WeChat.php
 */

namespace app\webApi\controller;

use app\webApi\model\UserWechat;

class WeChat
{

    public function genQrcode()
    {
        $con_id = input('conId');
        $model = new UserWechat();
        // 已绑定， 不生成
        $info = $model->getUser($con_id);
        if ($info != null && !empty($info['wx_uid'])) {
            return json(false);
        }

        $qrcode_info = app('WeChat')->Qrcreate($con_id);

        $data = [
            'con_id' => $con_id,
            'wx_uid' => '',
            'head_img' => '',
            'extra' => '',
            'wx_username' => '',
        ];
        $model->saveInfo($con_id, $data);
        return json($qrcode_info);
    }

    public function checkScan()
    {
        $con_id = input('conId');

        $ch = curl_init("https://wxpusher.api.mokch.info/" . $con_id);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code != 200)
            return json(false);

        $data = json_decode($res, true);
        $data['data'] = json_decode($data['data'], true);
        $model = new UserWechat();
        $userdata = [
            'con_id' => $con_id,
            'wx_uid' => $data['uid'],
            'head_img' => $data['data']['userHeadImg'],
            'extra' => $data['extra'],
            'wx_username' => $data['data']['userName'],
        ];
        $model->saveInfo($con_id, $userdata);

        return json($userdata);
    }

    public function getUserInfo()
    {
        $con_id = input('conId');
        $model = new UserWechat();
        $data = $model->getUser($con_id);
        if ($data && !empty($data['wx_uid'])) {
            return json($data);
        }
        return json(null);
    }
}

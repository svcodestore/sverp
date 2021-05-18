<?php
/*
 * @Date: 2021-04-29 08:43:24
 * @LastEditors: Mok.CH
 * @LastEditTime: 2021-05-07 14:01:59
 * @FilePath: \sverp\app\webApi\controller\Dms.php
 */

namespace app\webApi\controller;
use app\webApi\model\Dms as DmsModel;

class Dms 
{

    /**
     * 待接手项目
     */
    public function newItemRecord()
    {
        $model = new DmsModel();
        $data = $model->getItemRecord(['itemLoaderId', '10086']);
        
        foreach ($data as $key => $value) {
			$user = $model->getUserInfo($value['yeWuUser']);
			$data[$key]['userName'] = empty($user['user_name'])?'':$user['user_name'];
            $fileDressStr = substr($value['simpleFileDress'], 4, -4);
			$data[$key]['fileDress'] = explode('koko', $fileDressStr);
			$fileNameStr = substr($value['simpleFileName'], 4, -4);
			$data[$key]['fileName'] = explode('koko', $fileNameStr);
		}
        return json($data);
    }

    /**
     * 工单展示
     */
    public function showItem()
    {
        $model = new DmsModel();
        $orderInfo = $model->getOrderInfo(['finishStatus', 'doing']);

        foreach ($orderInfo as $key => $value) {
			$orderInfo[$key]['producNum'] = explode('~~', $value['producNum']);
			if ($tmpRes = $model->getUserInfo($value['itemLoaderId'])) {
				$orderInfo[$key]['userName'] = $tmpRes['user_name'];
			} else {
				$orderInfo[$key]['userName'] = '';
			}
		}
        
        return json($orderInfo);
    }

    /**
     * 我的项目任务
     */
    public function myTask()
    {
        $user_name = input('userName');
        $where['userName'] = $user_name;
		$where['status'] = 'doing';
        $data = (new DmsModel)->getOrderNode($where);
        return json($data);
    }

    public function itemSchedule()
    {
        
    }

    public function saveNewOrder()
    {
        $data = request()->param();
        $files = request()->file('fileDress');
        if ($files) {
			$count = count($files);
			for ($h = 0; $h < $count; $h++) {
				$resMoveInfo = $files[$h]->validate(['ext' => 'xls,xlsx,txt,dwg,dwl,dxf,pdf,rar,html,zip,rm,avi,mp3,mp4,jpeg,jpg,png,ai,emb,dst,bag'])->move(ROOT_PATH . 'public' . DIRECTORY_SEPARATOR . 'uploads');
				if ($resMoveInfo) {
					$filePreNameInfo = $resMoveInfo->getinfo();
					$fileBehName = $resMoveInfo->getfileName();
					$data['simpleFileDress'] .= 'http://192.168.123.51/public/uploads/' . date('Ymd', time()) . '/' . $fileBehName . 'koko';
					$data['simpleFileName'] .= $filePreNameInfo['name'] . 'koko';
				} else {
					$retMsg['message'] = 'Some Question Happened , Try To Connect The Administrator';
				}
			}
		}
    }

    public function upload()
    {
        $file = request()->file('file');
        // $resMoveInfo = $fileInfo[$h]->validate(['ext' => 'xls,xlsx,txt,dwg,dwl,dxf,pdf,rar,html,zip,rm,avi,mp3,mp4,jpeg,jpg,png,ai,emb,dst,bag'])->move(ROOT_PATH . 'public' . DS . 'uploads');
        if (!empty($file)) {
            try {
                validate(['image' => 'filesize:10240|fileExt:xls,xlsx,txt,dwg,dwl,dxf,pdf,rar,html,zip,rm,avi,mp3,mp4,jpeg,jpg,png,ai,emb,dst,bag'])
                ->check(['file' => $file]);
                $savename = \think\facade\Filesystem::putFile('files', $file);
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
}
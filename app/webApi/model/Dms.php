<?php
/*
 * @Date: 2021-04-29 08:44:58
 * @LastEditors: Mok.CH
 * @LastEditTime: 2021-05-04 13:49:40
 * @FilePath: \sverp\app\webApi\model\Dms.php
 */
namespace app\webApi\model;
use think\facade\Db;

class Dms
{
    protected $database = 'starvs_file';
    protected $order_info = 'order_info';
    protected $node_info = 'node_info';
    protected $order_node = 'order_node';
    protected $user_admin = 'user_admin';

    protected function table($table_name)
    {
        return $this->database . $table_name;
    }

    public function getItemRecord($where = [])
    {
        $data = Db::table($this->table('order_info'))
                ->field('*')
                ->where($where)
                ->order('orderNum DESC')
                ->select();
        foreach ($data as $key => $value) {
            $whereUserInfo['id'] = ['eq', $value['yeWuUser']];
            if ($tmpUserInfo = Db::table($this->table('user_admin'))->field('user_name')->where($whereUserInfo)->select()) {
                $data[$key]['yeWuUserName'] = $tmpUserInfo[0]['user_name'];
            } else {
                $data[$key]['yeWuUserName'] = '';
            }
            $fileDressStr = substr($value['simpleFileDress'], 4, -4);
            $data[$key]['fileDress'] = explode('koko', $fileDressStr);
            $fileNameStr = substr($value['simpleFileName'], 4, -4);
            $data[$key]['fileName'] = explode('koko', $fileNameStr);

            $data[$key]['producNum'] = explode('~~', $value['producNum']);
			$whereUserName['id'] = $value['itemLoaderId'];
			if ($tmpRes = Db::table($this->table('user_admin'))->field('user_name')->where($whereUserName)->select()) {
				$data[$key]['userName'] = $tmpRes[0]['user_name'];
			} else {
				$data[$key]['userName'] = '';
			}
        }
        return $data;
    }

    /**
     * order_node表
     * 
     */
    public function getOrderNode($where)
    {
        $resTask = Db::table($this->table('order_node'))
                    ->field('*, node_info.nodeName as nodeName')
                    ->leftjoin('node_info', 'order_node.nodeNum = node_info.id')
                    ->leftjoin('order_info', 'order_info.orderNum = node_info.orderNum')
                    ->where($where)
                    ->order('orderNum,nodeNum,needFinishDate')
                    ->select();
        // foreach ($resTask as $key => $value) {
        //     // $whereNodeName['id'] = $value['nodeNum'];
        //     // $nodeName = db('node_info')->field('nodeName')->where($whereNodeName)->select();
        //     // $resTask[$key]['nodeName'] = $nodeName[0]['nodeName'];
        //     // $whereOrderInfo['orderNum'] = $value['orderNum'];
        //     // $orderInfoRes = db('order_info')->field('customNum,producNumWork')->where($whereOrderInfo)->select();
        //     //查询该工单所有未完成的工序，判断如果第一个工序=$value['nodeNum'];,则此节点工序背景为红色;
        //     $whereSelFirst['orderNum'] = $value['orderNum'];
        //     $whereSelFirst['status'] = 'doing';
        //     $firstNodeInfo = db('order_node')->field('nodeNum')->where($whereSelFirst)->order('nodeNum')->select();
        //     if ($firstNodeInfo[0]['nodeNum'] == $value['nodeNum']) {
        //         $resTask[$key]['bgcolor'] = '#EE0000';
        //     } else {
        //         $resTask[$key]['bgcolor'] = '#FFFFFF';
        //     }
        //     $resTask[$key]['customNum'] = $orderInfoRes[0]['customNum'];
        //     $resTask[$key]['producNumWork'] = $orderInfoRes[0]['producNumWork'];
        // }

        return $resTask;
    }

    public function getOrderInfo($where, $fields='*'): array
    {
        $data = Db::table($this->table('order_info'))
                ->field($fields)
                ->where($where)
                ->order('orderNum DESC')
                ->select();
                
        return $data;
    }

    public function saveOrderInfo($data)
    {
        Db::table($this->table('order_info'))->insert($data);
    }

    /**
     * 此处是获取旧系统 user_admin表的数据
     */
    public function getUserInfo($id, $fields='*')
    {
        $data = Db::table($this->table('user_admin'))->field($fields)
            ->where('id', $id)->find();
        return $data;
    }

    /**
     * 工序信息 node_info
     */
    public function getNodeInfo($fields = '*', $where = [['nodeStatus', 'show']])
    {
        return Db::table($this->table('node_info'))->field($fields)
                    ->where($where)
                    ->select();
    }

}
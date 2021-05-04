<?php
/*
 * @Date: 2021-04-28 09:45:48
 * @LastEditors: Mok.CH
 * @LastEditTime: 2021-04-28 14:22:50
 * @FilePath: \sverp\app\command\TPMCycleJob.php
 */
declare (strict_types = 1);

namespace app\command;

use app\webApi\model\Record;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

/**
 * TPM 周期性任务 command 类
 * 将使用系统crontab 或其它定时任务 触发
 */
class TPMCycleJob extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('tpmcyclejob')
            ->setDescription('the tpmcyclejob command');
    }

    protected function execute(Input $input, Output $output)
    {
        // 指令输出
        $output->writeln('TPM-Cycle-Job Start!');

        // 周期任务： 保养提醒
        $this->checkMachineCycle();
        
    }

    /**
     * 机器保养提醒
     * 获取正常状态期间的机器保养周期时间， 对比上一次维修记录完成时间（如有未完成的维修，则跳过）
     * 超过时间的则短信提醒
     */
    private function checkMachineCycle()
    {
        $recordModel = new Record();

        $whereArr['notify_people'] = 2;
        $noticeArr = $recordModel->getNotify('notify_people,notify_phone', $whereArr, 0, 1000);
        $phones = [];
        foreach ($noticeArr as $key => $value) {
            $phones[] = $value['notify_phone'];
        }
        // 没有设定通知人员， 不发信息
        if (empty($phones)) return;

        $machines = $recordModel->getMecheInfo('*', ['status', '1'], 0, 100000);
        $warning_machines = [];
        foreach ($machines as $m) {
            // 没有定期保养时间 不处理
            if (empty($m->inspection_cycle)) break;

            $newestRecord = $recordModel->repair_record(['repairtime'], ['mechenum', $m->mache_num], 0, 1);
            
            // 如果上一次维修记录未完成，跳过！ 新机器没有维修记录(暂定)跳过!
            if (!$newestRecord || empty($newestRecord[0]['repairtime'])) break;
            
            $last_check_time = intval($newestRecord[0]['repairtime']);
            $now_time = time();
            if (($last_check_time + $m->inspection_cycle) > $now_time) {
                // 添加到队列
                $warning_machines[] = $m;
            }
        }
        // 没有超过周期的设备, 终止
        if (empty($warning_machines)) return;

        $machine_nums = [];
        // 整理要发送的设备信息
        foreach ($warning_machines as $wm) {
            $machine_nums[] = $wm->mache_name.'('.$wm->mache_num.')';
        }

        $content = [
            'department' => 'TPM 设备保养 ',
            'meche' => implode(', ', $machine_nums),
            'cause' => '以上设备，根据以往维修、保养记录，已经过了设定的保养周期，请抽空检查。检查完成，请到系统添加记录。',
            'time' => date('Y-m-d H:i:s', time())
        ];
        
        // 发送信息
        $phone = implode(',', $phones);
        smsSend($phone, '文迪软件', 'SMS_210075241', $content);
    }
}

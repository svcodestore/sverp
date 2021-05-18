<?php
/*
 * @Date: 2021-05-18 15:42:38
 * @LastEditors: Mok.CH
 * @LastEditTime: 2021-05-18 16:11:01
 * @FilePath: \sverp\app\command\TPMCopyAliyunData.php
 */
declare (strict_types = 1);

namespace app\command;

use PDO;
use Exception;
use think\console\Input;
use think\console\Output;
use think\console\Command;
use think\console\input\Option;
use think\console\input\Argument;
use app\webApi\model\Record;

class TPMCopyAliyunData extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('tpm-copy-aliyun-data')
            ->setDescription('定时获取aliyun上外网保存的报修记录到内网服务器');
    }

    
    protected function execute(Input $input, Output $output)
    {
        // 指令输出
        // $output->writeln('tpmcopyaliyundata');
        try{
            $db = new PDO('mysql:host=47.112.192.40;dbname=starvc_homedb', 'root', 'root');
        } catch (Exception $e) {
            echo $e->getMessage();
            return;
        }
        
        
        $runtime_path = app()->getRuntimePath();
        $last_id_record_file = join('\\', [$runtime_path, 'aliyun_tpm_last_id.txt']);
        if (!is_file($last_id_record_file)) {
            touch($last_id_record_file);
        }
        $last_id = intval(file_get_contents($last_id_record_file));
        
        $SQL = "SELECT * FROM prodlib_repair_record WHERE id > {$last_id}";

        $data = $db->query($SQL)->fetchAll(PDO::FETCH_ASSOC);

        $record_model = new Record();

        if (count($data) > 0) {
            foreach ($data as $row) {
                $last_id = $row['id'];
                unset($row['id']);
                $record_model->addRecord($row);
            }
        }
        
        file_put_contents($last_id_record_file, $last_id);
    }
}

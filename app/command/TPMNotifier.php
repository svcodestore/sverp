<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use Workerman\Worker;

class TPMNotifier extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('tpm-notice')
            ->setDescription('the tpm-notice command');
    }

    protected function execute(Input $input, Output $output)
    {
        $wsWorker = new Worker("websocket://0.0.0.0:2345");
        $wsWorker->onWorkerStart = function ($worker) use ($output) {
            $output->writeln("Worker starting...\n");
        };
        $wsWorker->onMessage = function ($connection, $data) use ($output) {
            $output->writeln(" ============== session begin ======================== ");

            $redis = new \Redis();
            $redis->connect('127.0.0.1', 6379);
            echo "Connection to server successfully\n";
            // 报修通知列表
            $tpmNotirierList = $redis->lrange('tpm-notifier', 0, -1);
            $output->writeln("you just received: $data\n");

            // websock 发送过来的数据
            $originData = json_decode($data, true);

            // 如果是登录请求就向 Redis 集合【loginedUserList】中添加用户 ID
            if (isset($originData['loginRequest']) && $originData['loginRequest'] === 1) {
                $this->loginToRedis($redis, $originData['id']);
                $output->writeln(" >>> user: " . $originData['id'] . " is logined.");
            } else {
                // 处理报修通知  [draft]
                if (isset($originData['msg'])) {
                    // 已登录则将 Redis 列表【tpm-notifier】中的记录和接受到的通知发送给通知人
                    if ($redis->sismember('loginedUserList', $originData['id'])) {
                        $output->writeln(" >>> userid[" . $originData['id'] . "]: iterate msg list begin <<<");
                        foreach ($tpmNotirierList as $key => $value) {
                            $valueArr = json_decode($value, true);
                            $output->writeln("[list item " . $key . ": sender ->" . $valueArr['id']);
                            if ((string)$valueArr['sendto'] === (string)$originData['id']) {
                                $output->writeln(" sended item " . $key . ": receiver->" . $valueArr['sendto']);
                                $connection->send($value);
                                $redis->lrem('tpm-notifier', $value, 0);
                            }
                        }
                        $output->writeln(" >>> iterate list queue end <<<");
                        $connection->send($data);

                        // 未登录，存入 Redis 列表中，等待上线再通知
                    } else {
                        $redis->lpush('tpm-notifier', $data);
                        $msg = "added msg, msg count " . count($redis->lrange('tpm-notifier', 0, -1));
                        $output->writeln($msg);
                    }
                }
            }
            $output->writeln(" ============== session end ======================== ");
        };

        Worker::runAll();
    }

    protected function loginToRedis($redisInstance, $id)
    {
        $redisInstance->sAdd('loginedUserList', $id);
    }
}

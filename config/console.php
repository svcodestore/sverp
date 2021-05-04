<?php
/*
 * @Date: 2021-04-19 16:19:10
 * @LastEditors: Mok.CH
 * @LastEditTime: 2021-04-28 09:46:42
 * @FilePath: \sverp\config\console.php
 */
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        'hello' => 'app\command\Hello',
        'tpm-notice' => 'app\command\TPMNotifier',
        'tpm-cycle' => 'app\command\TPMCycleJob',
    ]
];

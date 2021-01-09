<?php
// +----------------------------------------------------------------------

// [ 应用入口文件 ]
namespace think;

require __DIR__ . '/../vendor/autoload.php';
//数据库名常量
define('starvc_cfo', 'starvc_cfo');
define('starvc_file', 'starvc_file');
define('starvc_homedb', 'starvc_homedb');
define('starvc_hr', 'starvc_hr');
define('starvc_imprvlib', 'starvc_imprvlib');
define('starvc_kpi', 'starvc_kpi');
define('starvc_mis', 'starvc_mis');
define('starvc_pannel', 'starvc_pannel');
define('starvc_syslib', 'starvc_syslib');
define('starvc_vote', 'starvc_vote');
define('starvc_qa', 'starvc_qa');
// 执行HTTP应用并响应
$http = (new App())->http;

$response = $http->run();

$response->send();

$http->end($response);

<?php
/*
 * @Date: 2020-12-28 14:21:43
 * @LastEditors: Mok.CH
 * @LastEditTime: 2021-05-18 10:00:34
 * @FilePath: \sverp\app\common.php
 */
// 应用公共文件
#require_once '../vendor/autoload.php';

use Firebase\JWT\JWT;
use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;

function authorizedToken($data)
{
	$currTime = time();

	$points = [
		strtotime('today 12:00:00'),
		strtotime('today 23:59:59')
	];
	define('EXP', $points);

	if ($currTime < EXP[0]) {
		$exp = EXP[0];
	} else {
		$exp = EXP[1];
	}

	$token = [
		'iss' => 'http://www.sverp.com', // 签发者
		'aud' => 'http://www.sverp.com', // jwt所面向的用户
		'iat' => $currTime, // 签发时间
		'exp' => $exp, // 过期时间
		'data' => $data
	];

	$jwt = JWT::encode($token, 'U2FsdGVkX1/JWgZqBvRZmEuyNLdUy3L8xMGS');

	return $jwt;
}
function smsSend($phone, $sign, $template, $content = array())
{
	// debug: 
	if (env('APP_DEBUG_SEND_SMS') === false) {
		return ['Code' => 'OK'];
	}
	AlibabaCloud::accessKeyClient('LTAI4GKxRNfpcn52LPF7BXHc', '89DyCBjPV8kf1OeSU5O9kjFUfMxd7J')
		->regionId('cn-hangzhou')
		->asDefaultClient();
	try {
		$result = AlibabaCloud::rpc()
			->product('Dysmsapi')
			// ->scheme('https') // https | http
			->version('2017-05-25')
			->action('SendSms')
			->method('POST')
			->host('dysmsapi.aliyuncs.com')
			->options([
				'query' => [
					'RegionId' => "cn-hangzhou",
					'PhoneNumbers' => $phone,
					'SignName' => $sign,
					'TemplateCode' => $template,
					'TemplateParam' => json_encode($content),
				],
			])
			->request();
		return $result->toArray();
	} catch (ClientException $e) {
		echo $e->getErrorMessage() . PHP_EOL;
	} catch (ServerException $e) {
		echo $e->getErrorMessage() . PHP_EOL;
	}
}

/**
 * 图片base64编码
 * @param string $img
 * @param bool $imgHtmlCode
 * author 
 * @return string
 */
function imgBase64Encode($img = '', $imgHtmlCode = true)
{
	//如果是本地文件
	if (strpos($img, 'http') === false && !file_exists($img)) {
		return $img;
	}
	//获取文件内容
	$file_content = file_get_contents($img);
	if ($file_content === false) {
		return $img;
	}
	$imageInfo = getimagesize($img);
	$prefiex = '';
	if ($imgHtmlCode) {
		$prefiex = 'data:' . $imageInfo['mime'] . ';base64,';
	}
	$base64 = $prefiex . chunk_split(base64_encode($file_content));
	return $base64;
}
/**
 * 片base64解码
 * @param string $base64_image_content 图片文件流
 * @param bool $save_img    是否保存图片
 * @param string $path  文件保存路径
 * author 
 * @return bool|string
 */
function imgBase64Decode($base64_image_content = '', $save_img = false, $path = '')
{
	if (empty($base64_image_content)) {
		return false;
	}
	//匹配出图片的信息
	$match = preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result);
	if (!$match) {
		return false;
	}
	//解码图片内容(方法一)
	/*$base64_image = preg_split("/(,|;)/",$base64_image_content);
  $file_content = base64_decode($base64_image[2]);
  $file_type = substr(strrchr($base64_image[0],'/'),1);*/
	//解码图片内容(方法二)
	$base64_image = str_replace($result[1], '', $base64_image_content);
	$file_content = base64_decode($base64_image);
	$file_type = $result[2];
	//如果不保存文件,直接返回图片内容
	if (!$save_img) {
		return $file_content;
	}
	//如果没指定目录,则保存在当前目录下
	if (empty($path)) {
		$path = __DIR__ . '/upload';
	}
	$file_path = $path . "/" . date('Ymd', time()) . "/";
	if (!is_dir($file_path)) {
		//检查是否有该文件夹，如果没有就创建
		mkdir($file_path, 0777, true);
	}
	$file_name = time() . ".{$file_type}";
	$new_file = $file_path . $file_name;
	if (file_exists($new_file)) {
		//有同名文件删除
		@unlink($new_file);
	}
	if (file_put_contents($new_file, $file_content)) {
		return $new_file;
	}
	return false;
}

function phpredis(): \Redis
{
	$redis = new \Redis();
	$redis->connect(env('cache.redis_host'));
	$redis->auth(env('cache.redis_password'));
	return $redis;
}

function pdosqlsrv(array $options = null): \PDO
{
	$dbinfo = $options ?? [
		// 数据库类型
		'type'                      => 'Sqlsrv', //必须输入
		// 用户名
		'username'                  => 'sa',
		// 密码
		'password'                  => 'Sql_2008',
		// 连接dsn,驱动、服务器地址和端口、数据库名称
		'dsn'                    => env('mssql.dsn', 'odbc:Driver={SQL Server};Server=192.168.123.245,1433;Database=databasesdwx'),
		// 'dsn'                    => 'odbc:Driver={SQL Server};Server=192.168.123.245,1433;Database=databasesdwx',
		// 'dsn'                       => 'sqlsrv:server=192.168.123.245,1433;Database=databasesdwx;',
	];

	$dbh = new PDO($dbinfo['dsn'], $dbinfo['username'], $dbinfo['password']);

	return $dbh;
}

<?php
/*
 * @Author: yanbuw1911
 * @Date: 2020-11-04 08:50:09
 * @LastEditTime: 2021-01-16 16:03:47
 * @LastEditors: yanbuw1911
 * @Description: 
 * @FilePath: \backend\app\webApi\api\Test.php
 */

namespace app\webApi\api;

use Dompdf\Dompdf;
use Dompdf\Options;
use mysqli;
use ReflectionClass;
use think\facade\Db;

// header('Access-Control-Allow-Origin:*');
// header('Access-Control-Allow-Methods:*');
// header('Access-Control-Allow-Headers:x-requested-with,content-type,access-token,Access-Token');
// header('Access-Control-Max-Age: 86400');

class Test
{
    public function foo()
    {
        return 'foo';
    }

    public function getIp()
    {
        dump($_SERVER);
    }

    public function delRedis()
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379);
        echo "Connection to server successfully";
        // //存储数据到列表中
        // $redis->lpush("tutorial-list", "Redis");
        // $redis->lpush("tutorial-list", "Mongodb");
        // $redis->lpush("tutorial-list", "Mysql");
        // // 获取存储的数据并输出
        // $arList = $redis->lrange("tutorial-list", 0, -1);
        $listLen = $redis->lLen('tutorial-list');
        print_r($listLen);
        print_r(PHP_EOL);
        $redis->del('tpm-notifier');
        print_r(PHP_EOL);
        print_r($listLen);

        // print_r($arList);
    }

    public function setRedis()
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379);
        echo "Connection to server successfully";
        // //存储数据到列表中
        // $redis->lpush("tutorial-list", "Redis");
        // $redis->lpush("tutorial-list", "Mongodb");
        // $redis->lpush("tutorial-list", "Mysql");
        // 获取存储的数据并输出
        $arList = $redis->lrange("tpm-notifier", 0, -1);

        print_r($arList);
    }

    public function setAutoSchedulePdo()
    {
        $prodObj = new \app\webApi\model\Prod();
        $res = $prodObj->syncProdSchdParam('V', 2021, 01);
        dd($res);
    }

    public function test()
    {
        // $data = Db::table('star_cfo.prdmoedl')->where(['facno' => 'B0415F'])->select()->toArray();

        // foreach ($data as $key => $value) {
        //     var_dump($value);
        // }

        $conn = mysqli_connect('192.168.123.51', 'root', 'root', 'star_cfo');
        $sql = "select a.stock,a.create_time,a.type,a.author,b.goods_name from cfo_goods_stock as a,cfo_goods as b where a.goods_id = b.id";
        $res = mysqli_fetch_all(mysqli_query($conn, $sql), MYSQLI_ASSOC);

        $dbh = new \PDO("mysql:host=127.0.0.1;dbname=starvc_homedb;port=3306", 'root', 'root');
        $sql = "SELECT * FROM `hrdlib_material_used`";
        $res2 = $dbh->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $values = [];
        foreach ($res as $v) {
            foreach ($res2 as $v2) {
                if ($v['goods_name'] == $v2['hmu_material_name']) {
                    $t = '';
                    switch ($v['type']) {
                        case '0':
                            $t = 'set';
                            break;
                        case '1':
                            $t = 'put';
                            break;
                        case '2':
                            $t = 'out';
                            break;
                    }
                    $values[] = [
                        'hml_material_id' => $v2['id'],
                        'hml_operate_qty' => $v['stock'],
                        'hml_operate_type' => $t,
                        'hml_creator' => $v['author'],
                        'hml_join_date' => $v['create_time'] ? date('Y-m-d H:i:s', $v['create_time']) : $v['create_time'],
                    ];

                    continue;
                }
            }
        }
        dd(Db::table('hrdlib_material_log')->insertAll($values));
        // $cnt = 0;
        // foreach ($res as $v) {
        //     $value = Db::table('hrdlib_material_used')->where('hmu_material_name', $v['hom_material_id'])->field(['id', 'hmu_material_name'])->find();
        //     // dd($value);
        //     $c = Db::table('hrdlib_outbound_material')->where('hom_material_id', $value['hmu_material_name'])->update(['hom_material_id' => $value['id']]);
        //     $cnt += $c;
        // }

        // dd($cnt);
        // $dbh2 = new \PDO("mysql:host=127.0.0.1;dbname=starvc_homedb", 'root', 'root');
        // $res2 = $dbh->query("SHOW TABLES")->fetchAll(\PDO::FETCH_ASSOC);
        // dd($res2);
        // $res2 = Db::table('starvc_homedb.prodlibmap_prdschd_initpdo2phs')->select();
        // $data = array_map(function ($e) {
        //     return [
        //         'map_ppi_prd_item' => $e['facno'],
        //         'map_ppi_seq' => $e['item'],
        //         'map_ppi_phsid' => $e['jdno'],
        //         'map_ppi_phs' => $e['jdname'],
        //         'map_ppi_cost_time' => (int) $e['price'],
        //         'map_ppi_phs_desc' => $e['descn'],
        //         'map_ppi_deadtime' => $e['worktimesh'],
        //         'map_ppi_ismaster' => $e['iszf'] != '2' ? 1 : 0,
        //     ];
        // }, $res);
        // dd(Db::name('starvc_homedb.prodlibmap_prdschd_initpdo2phs')->insertAll($data));
        // dd($data);

        // dd(__CLASS__);
        // dd(call_user_func_array([__CLASS__, 'arr'], [1, 2]));

        $area = array(
            array('id' => 1, 'name' => '安徽', 'parent' => 0),
            array('id' => 2, 'name' => '北京', 'parent' => 0),
            array('id' => 3, 'name' => '海淀', 'parent' => 2),
            array('id' => 4, 'name' => '中关村', 'parent' => 3),
            array('id' => 5, 'name' => '合肥', 'parent' => 1),
            array('id' => 6, 'name' => '上地', 'parent' => 3),
            array('id' => 7, 'name' => '河北', 'parent' => 0),
            array('id' => 8, 'name' => '石家庄', 'parent' => 7)
        );

        return json($area);
    }

    public function dompdf()
    {
        $html = '<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
</head>
<body >
<div style="font-family:simkai;">
测 试
English / 正體中文 123 Chinese 测试 测试测 
</div>
</body>
</html>';
        //echo $html;exit;
        $options = new Options();
        $options->set('enable_remote', TRUE);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();
        $dompdf->stream("sample.pdf", array("Attachment" => 0));
        exit;
    }

    public function arr($a, $b)
    {
        return $a + $b;
    }

    /**
     * @description: 获取api路径
     */
    public function reflect()
    {
        $apiDir = app()->getAppPath() . 'api';
        $apiFiles = array_diff(scandir($apiDir), array('.', '..'));
        $nsPrefix = 'app\webApi\api\\';

        $apiList = [];
        foreach ($apiFiles as $apiFile) {
            $classname = substr($apiFile, 0, -4);
            $nsClass = $nsPrefix  . $classname;
            $reflect = new ReflectionClass($nsClass);
            $reflectMethods = $reflect->getMethods();
            foreach ($reflectMethods as $reflectMethod) {
                $modifier = \Reflection::getModifierNames($reflectMethod->getModifiers());
                $funcname = $reflectMethod->{'name'};
                if ($modifier[0] === 'public' && $funcname !== '__construct') {
                    $api = '/' . strtolower($classname) . "/$funcname";
                    $apiList[] = ['path' => $api, 'doc' => $reflectMethod->getDocComment()];
                }
            }
        }

        return json($apiList);
    }
}

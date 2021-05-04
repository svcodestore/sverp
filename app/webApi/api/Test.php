<?php
/*
 * @Author: yanbuw1911
 * @Date: 2020-11-04 08:50:09
 * @LastEditTime: 2021-04-30 10:02:27
 * @LastEditors: yanbuw1911
 * @Description: 
 * @FilePath: /sverp/app/webApi/api/Test.php
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
        $input = input();
        $prodObj = new \app\webApi\model\Prod();
        $res = $prodObj->syncProdSchdParam($input['prodLine'], $input['year'], $input['month']);
        dd($res);
    }

    public function sync()
    {

        $dbh = new \PDO("mysql:host=127.0.0.1;dbname=starvc_homedb", 'root', 'root');

        $sql = "SELECT  PPI_IS_DIRTY,
                        PPI_PO_SORT,
                        PPI_PO_YEAR,
                        PPI_PO_MONTH,
                        PPI_EXPECTED_QTY,
                        PPI_PRD_ITEM,
                        PPI_CUSTOMER_PONO,
                        PPI_CUSTOMER_NO,
                        PPI_WORKSHOP_NAME,
                        PPI_WORKSHOP 
                    FROM PRODLIB_PRDSCHD_INITPO 
                WHERE PPI_PO_YEAR = 2021 
                    AND PPI_PO_MONTH = 03";
        $res2 = $dbh->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        dd(Db::table('prodlib_prdschd_initpo')->insertAll($res2));
    }

    public function test()
    {
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

    public function transferMaterialStock()
    {
        (function () {
            $conn = mysqli_connect('192.168.123.51', 'root', 'root', 'star_cfo');
            $sql = "select * from cfo_goods";
            $res = mysqli_fetch_all(mysqli_query($conn, $sql), MYSQLI_ASSOC);

            foreach ($res as $v) {
                foreach (Db::table('hrdlib_material_used')->select()->toArray() as $value) {
                    if ($v['goods_name'] === $value['hmu_material_name']) {
                        Db::table('hrdlib_material_used')->where('id', $value['id'])->update(['hmu_material_stock' => $v['stock']]);
                    }
                }
            }
        })();
        // --------------  从老系统中获取用料库存操作数据 ------------------ //
        (function () {
            $conn = mysqli_connect('192.168.123.51', 'root', 'root', 'star_cfo');
            $sql = "select a.stock,a.create_time,a.type,a.author,b.goods_name from cfo_goods_stock as a,cfo_goods as b where a.goods_id = b.id";
            $res = mysqli_fetch_all(mysqli_query($conn, $sql), MYSQLI_ASSOC);

            $res2 = Db::table('hrdlib_material_used')->select()->toArray();
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
            Db::table('hrdlib_material_log')->insertAll($values);
            // dd(Db::table('hrdlib_material_log')->insertAll($values));
        })();
        // --------------  从老系统中获取用料库存操作数据 ------------------ //

        // -------------- 从老系统中获取用料出料订单数据 ------------------ //
        (function () {
            $conn = mysqli_connect('192.168.123.51', 'root', 'root', 'star_cfo');
            $sql = "select * from cfo_goods_order";
            $res = mysqli_fetch_all(mysqli_query($conn, $sql), MYSQLI_ASSOC);

            $rows = array_map(function ($e) {

                switch ($e['supplier']) {
                    case '制一线':
                        $supplier = 'WL1';
                        break;
                    case '制二线':
                        $supplier = 'WL2';
                        break;
                    case '制三线':
                        $supplier = 'WL3';
                        break;
                    case '制四线':
                        $supplier = 'WL4';
                        break;
                    case '制五线':
                        $supplier = 'WL5';
                        break;
                    case '制六线':
                        $supplier = 'WL6';
                        break;
                    case '制七线':
                        $supplier = 'WL7';
                        break;
                    case '制八线':
                        $supplier = 'WL8';
                        break;
                    case '制九线':
                        $supplier = 'WL9';
                        break;
                    case '制十线':
                        $supplier = 'WL10';
                        break;
                    case '物管课':
                        $supplier = 'PMD';
                        break;
                    case '人资管理部':
                        $supplier = 'HRD';
                        break;
                    case '业务科':
                        $supplier = 'BS';
                        break;
                    case '研发科':
                        $supplier = 'R&D';
                        break;
                    case '生管课':
                        $supplier = 'PROD';
                        break;
                    case '生产部':
                        $supplier = 'PROD';
                        break;
                    case '生產部':
                        $supplier = 'PROD';
                        break;
                    case 'TPM':
                        $supplier = 'TPM';
                        break;
                    case '品保中心':
                        $supplier = 'QAC';
                        break;
                    case '采购科':
                        $supplier = 'PD';
                        break;
                    default:
                        $supplier = null;
                        break;
                }
                return [
                    'hoo_order_id' => $e['order_id'],
                    'hoo_applicant' => $supplier,
                    'hoo_is_approved' => $e['is_audit'],
                    'hoo_join_date' => $e['daytime'] !== '0' ? date('Y-m-d H:i:s', $e['daytime']) : null,
                    'hoo_creator' => $e['user']
                ];
            }, $res);

            Db::table('hrdlib_outbound_order')->insertAll($rows);
        })();
        // -------------- 从老系统中获取用料出料订单数据 ------------------ //

        // -------------- 从老系统中获取用料出料详细数据 ------------------ //
        (function () {
            $conn = mysqli_connect('192.168.123.51', 'root', 'root', 'star_cfo');
            $sql = "select a.*, b.order_id,c.goods_name from star_cfo.cfo_goods_order_sub as a, cfo_goods_order as b, cfo_goods as c where a.goods_id = c.id and a.order_id = b.id";
            $res = mysqli_fetch_all(mysqli_query($conn, $sql), MYSQLI_ASSOC);

            $res2 = Db::table('hrdlib_material_used')->select()->toArray();

            $rows = array_map(function ($e) {
                return [
                    'hom_outbound_id' => $e['order_id'],
                    'hom_material_id' => $e['goods_name'],
                    'hom_apply_qty' => $e['count'],
                    'hom_out_qty' => $e['true_count'],
                    'hom_remark' => $e['desc']
                ];
            }, $res);
            foreach ($rows as $k => $v) {
                foreach ($res2 as $value) {
                    if ($value['hmu_material_name'] === $v['hom_material_id']) {
                        $rows[$k]['hom_material_id'] = $value['id'];
                        continue;
                    }
                }
            }
            $res3 = Db::table('hrdlib_outbound_order')->select()->toArray();
            foreach ($rows as $k => $v) {
                foreach ($res3 as $value) {
                    if ($value['hoo_order_id'] === $v['hom_outbound_id']) {
                        $rows[$k]['hom_outbound_id'] = $value['id'];
                        continue;
                    }
                }
            }

            Db::table('hrdlib_outbound_material')->insertAll($rows);
            // dd(Db::table('hrdlib_outbound_material')->insertAll($rows));
        })();
        // -------------- 从老系统中获取用料出料详细数据 ------------------ //
    }

    public function mongo()
    {
        $manager = new \MongoDB\Driver\Manager("mongodb://192.168.132.83:27017");
        $bulk = new \MongoDB\Driver\BulkWrite;
        $bulk->insert(['x' => 1, 'name' => '菜鸟教程', 'url' => 'http://www.runoob.com']);
        $bulk->insert(['x' => 2, 'name' => 'Google', 'url' => 'http://www.google.com']);
        $bulk->insert(['x' => 3, 'name' => 'taobao', 'url' => 'http://www.taobao.com']);
        $manager->executeBulkWrite('test.sites', $bulk);

        $filter = ['x' => ['$gt' => 1]];
        $options = [
            'projection' => ['_id' => 0],
            'sort' => ['x' => -1],
        ];
        $query = new \MongoDB\Driver\Query($filter, $options);
        $cursor = $manager->executeQuery('test.sites', $query);

        foreach ($cursor as $document) {
            print_r($document);
        }
    }
}

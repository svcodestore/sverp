<?php
/*
 * @Author: yanbuw1911
 * @Date: 2020-11-04 08:50:09
 * @LastEditTime: 2021-06-25 11:17:16
 * @LastEditors: yanbuw1911
 * @Description: 
 * @FilePath: /sverp/app/webApi/api/Test.php
 */

namespace app\webApi\api;

use Dompdf\Dompdf;
use Dompdf\Options;
use mysqli;
use PDO;
use Prodigy_DBF;
use ReflectionClass;
use think\facade\Db;

// header('Access-Control-Allow-Origin:*');
// header('Access-Control-Allow-Methods:*');
// header('Access-Control-Allow-Headers:x-requested-with,content-type,access-token,Access-Token');
// header('Access-Control-Max-Age: 86400');

class Test
{
    public function phpi()
    {
        phpinfo();
    }

    public function foo()
    {
        return 'foo';
    }

    public function getIp()
    {
        dump($_SERVER);
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
        var_dump(class_exists(\Redis::class));
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

    public function syncPdoPhs()
    {
        $prodObj = new \app\webApi\model\Prod();
        $res = $prodObj->syncPdoPhs();
        dd($res);
    }

    public function syncPosition()
    {
        $conn = mysqli_connect('192.168.123.51', 'root', 'root', 'star_kpi');
        $sql = "select distinct  show_stations from worker_info";
        $res = mysqli_fetch_all(mysqli_query($conn, $sql), MYSQLI_ASSOC);
        $rows = array_map(
            function ($e) {
                return ['cph_position' => $e['show_stations']];
            },
            $res
        );
        dd(Db::table('commonlib_position_home')->insertAll($rows));
    }

    public function syncTitle()
    {
        $conn = mysqli_connect('192.168.123.51', 'root', 'root', 'star_kpi');
        $sql = "select distinct  worker_stations from worker_info";
        $res = mysqli_fetch_all(mysqli_query($conn, $sql), MYSQLI_ASSOC);
        $rows = array_map(
            function ($e) {
                return ['cpt_title' => $e['worker_stations']];
            },
            $res
        );
        dd(Db::table('commonlib_position_title')->insertAll($rows));
    }

    public function syncRank()
    {
        $conn = mysqli_connect('192.168.123.51', 'root', 'root', 'star_kpi');
        $sql = "select distinct  stations_rank from worker_info where stations_rank != ''";
        $res = mysqli_fetch_all(mysqli_query($conn, $sql), MYSQLI_ASSOC);
        $rows = array_map(
            function ($e) use ($conn) {
                $sql = "select distinct rank_name from worker_info where stations_rank = '{$e['stations_rank']}'";
                $res = mysqli_fetch_all(mysqli_query($conn, $sql), MYSQLI_ASSOC);
                return ['cpr_rank_code' => $e['stations_rank'], 'cpr_rank_name' => $res[0]['rank_name']];
            },
            $res
        );

        dd(Db::table('commonlib_position_rank')->insertAll($rows));
    }

    public function dbfreader()
    {
        $loc = input('post.loc');

        $fmt = function ($str) {
            $s = str_replace(' ', '', @iconv('GBK', 'UTF-8', $str));
            $lastByte = substr($s, -1);
            if (ord($lastByte) < 10 || ord($lastByte) === 92) {
                return substr($s, 0, -1);
            }

            return  $s;
        };

        $path = '//192.168.123.252/data$/database/';
        $Test = new Prodigy_DBF($path . 'employee.DBF', $path . 'employee.FPT');
        $info = [];
        while (($Record = $Test->GetNextRecord(true))) {

            $row['positionRankCode'] = $Record['NBJB'];
            $row['name'] = $fmt($Record['NAME']) === false ? '' : $fmt($Record['NAME']);
            $row['dept'] =  $fmt($Record['DEPTS']) === false ? '' : $fmt($Record['DEPTS']);
            $row['grp'] = $fmt($Record['GRP']) === false ? '' : $fmt($Record['GRP']);
            $row['subGrp'] = $fmt($Record['GRPS']) === false ? '' : $fmt($Record['GRPS']);
            $row['position'] = $fmt($Record['POSITIONS']) === false ? '' : $fmt($Record['POSITIONS']);
            $row['isLeaveJob'] = $fmt($Record['RETIRETYPE']) === false ? '' : $fmt($Record['RETIRETYPE']);
            $row['joinDate'] = $fmt($Record['ENTERDATE']) === false ? '' : $fmt($Record['ENTERDATE']);
            $row['staffNo'] = $fmt($Record['WORKNO']) === false ? '' : $fmt($Record['WORKNO']);
            $row['salaryComputeType'] = $fmt($Record['GZTYPE1']) === false ? '' : $fmt($Record['GZTYPE1']);
            // $row['born'] = $fmt($Record['BORN']);
            // $row['education'] = $fmt($Record['DEGREE']);

            // foreach (array_keys($Record) as $key => $value) {
            //     print_r($value);
            //     print_r(' ===> ');
            //     print_r( $fmt($Record[$value]));
            //     print_r('<br>');
            // }
            // print_r('<br>');

            if ($row['isLeaveJob'] === '在职') {
                switch ($loc) {
                    case 'SV':
                        if ($row['staffNo'] > 99) {
                            $info[] = $row;
                        }
                        break;
                    case 'JS':
                        if (!is_numeric($row['staffNo']) && substr($row['staffNo'], 0, 2) === 'JS') {
                            $info[] = $row;
                        }
                        break;
                }
            }
        }

        return json($info);
    }
}

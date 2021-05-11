<?php
/*
 * @Author: yanbuw1911
 * @Date: 2020-11-04 08:50:09
 * @LastEditTime: 2021-05-11 15:58:34
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

        $data = (json_decode('[["CT\/\u5de5\u65f6*\u7cfb\u6570",0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],["\u5e8f\u53f7","\u90e8\u95e8","\u5de5\u53f7","\u59d3\u540d","\u52a0\u603b - CT","\u51fa\u52e4\u5de5\u65f6","\u79d2","\uff08\u5de5\u65f6\u79d2\uff09","\u7b2c\u4e00\u6b21\u8ba1\u7b97","100%","201806\u7cfb\u6570","201806\n\u6807\u51c6\u7ee9\u6548","\u4ea7\u7ebf\u7ee9\u6548","\u4e0d\u826f\u6263\u5206","7S\u52a0\u51cf\u5206","\u603b\u7ee9\u6548",0,0],[1,"\u7269\u7ba1\u8bfe",9284,"\u5f20\u5bb6\u658c",211975,59,3540,212400,"100%","100%","120%","120%","120.00 ",0,0,"120.00 ",0,0],[2,"\u7269\u7ba1\u8bfe",9239,"\u674e\u660e\u4eae",1233225,337.5,20250,1215000,"102%","102%","120%","122%","122.00 ",0,0,"122.00 ",0,0],[3,"\u7269\u7ba1\u8bfe",9275,"\u4f26\u5fd7\u950b",524799,139.5,8370,502200,"105%","105%","120%","125%","125.00 ",0,0,"125.00 ",0,0],[4,"\u7269\u7ba1\u8bfe",9225,"\u5218\u4e9a\u6ce2",1292544,340,20400,1224000,"106%","106%","120%","127%","127.00 ",0,0,"127.00 ",0,0],[5,"\u7269\u7ba1\u8bfe",7706,"\u9648\u6dd1\u5a25",1300860,330,19800,1188000,"110%","110%","130%","142%","142.00 ",0,0,"142.00 ",0,0],[6,"\u7269\u7ba1\u8bfe",8553,"\u4e18\u4f1f\u6d2a",1189642,294,17640,1058400,"112%","112%","130%","146%","146.00 ",0,20,"166.00 ",0,0],[7,"\u7269\u7ba1\u8bfe",8292,"\u5468\u5173\u5409",1380272,339,20340,1220400,"113%","113%","130%","147%","147.00 ",0,0,"147.00 ",0,0],[8,"\u7269\u7ba1\u8bfe",4918,"\u80e1\u5175\u7965",1385568,340,20400,1224000,"113%","113%","130%","147%","147.00 ",0,0,"147.00 ",0,0],[9,"\u7269\u7ba1\u8bfe",8615,"\u5468\u5fc5\u6e05",1245132,305,18300,1098000,"113%","113%","130%","147%","147.00 ",0,0,"147.00 ",0,0],[10,"\u7269\u7ba1\u8bfe",9238,"\u6797\u6c5f\u8f89",1295820,313,18780,1126800,"115%","115%","130%","150%","150.00 ",0,0,"150.00 ",0,0],[11,"\u7269\u7ba1\u8bfe",121,"\u9ece\u4ed5\u6ee1",1180800,328,19680,1180800,"100%","100%","120%","120%","150.00 ",0,0,"150.00 ",0,0],[12,"\u7269\u7ba1\u8bfe",8590,"\u5468\u5b57",1235164,281,16860,1011600,"122%","122%","140%","171%","171.00 ",0,0,"171.00 ",0,0],[13,"\u7269\u7ba1\u8bfe",3434,"\u9c81\u56fd\u5168",556214,124,7440,446400,"125%","125%","140%","174%","174.00 ",0,0,"174.00 ",0,0],[14,"\u7269\u7ba1\u8bfe",9227,"\u53f6\u6b22\u826f",1530000,340,20400,1224000,"125%","125%","140%","175%","175.00 ",0,0,"175.00 ",0,0],[15,"\u7269\u7ba1\u8bfe","L0727","\u9648\u6da6\u8f89",1037232,280,16800,1008000,"103%","103%","120%","123%","103.00 ",0,0,"103.00 ",0,0],[16,"\u5236\u4e00\u7ebf",2047,"\u5510\u4ef2\u6587",503562,205,12300,738000,"68%","68%","0%","0%","0.00 ",-22,0,"0.00 ",0,0],[17,"\u5236\u4e00\u7ebf",5380,"\u674e\u6811\u829d",1220875,351,21060,1263600,"97%","97%","110%","106%","106.00 ",-22,0,"84.00 ",0,0],[18,"\u5236\u4e00\u7ebf",9177,"\u7b26\u7f8e\u73cd",1070802,294.5,17670,1060200,"101%","101%","120%","121%","121.00 ",-22,0,"99.00 ",0,0],[19,"\u5236\u4e00\u7ebf",8676,"\u6768\u6625\u5e78",1239264,331,19860,1191600,"104%","104%","120%","125%","125.00 ",-22,0,"103.00 ",0,0],[20,"\u5236\u4e00\u7ebf",8844,"\u4f26\u9521\u4eae",1276964,338,20280,1216800,"105%","105%","120%","126%","126.00 ",-22,0,"104.00 ",0,0],[21,"\u5236\u4e00\u7ebf",9243,"\u6c88\u8d24\u6e05",1301087,343.5,20610,1236600,"105%","105%","120%","126%","126.00 ",-22,0,"104.00 ",0,0],[22,"\u5236\u4e00\u7ebf",9278,"\u8042\u7f8e\u679d",584888,153.5,9210,552600,"106%","106%","120%","127%","127.00 ",-22,0,"105.00 ",0,0],[23,"\u5236\u4e00\u7ebf",9184,"\u5510\u73cd\u559c",1314290,344,20640,1238400,"106%","106%","120%","127%","127.00 ",-22,0,"105.00 ",0,0],[24,"\u5236\u4e00\u7ebf",8662,"\u6768\u79cb\u9999",1327752,341.5,20490,1229400,"108%","108%","120%","130%","130.00 ",-22,0,"108.00 ",0,0],[25,"\u5236\u4e00\u7ebf",9196,"\u80e1\u5b88\u6881",1114416,284,17040,1022400,"109%","109%","120%","131%","131.00 ",-22,0,"109.00 ",0,0],[26,"\u5236\u4e00\u7ebf",2591,"\u95eb\u5bcc\u51e4",1423253,341,20460,1227600,"116%","116%","130%","151%","151.00 ",-22,0,"129.00 ",0,0],[27,"\u5236\u4e00\u7ebf",2851,"\u9093\u8fde\u5a23",1464920,335,20100,1206000,"121%","121%","140%","170%","170.00 ",-22,0,"148.00 ",0,0],[28,"\u5236\u4e00\u7ebf",9033,"\u674e\u7fe0\u83ca",1509405,340,20400,1224000,"123%","123%","140%","173%","173.00 ",-22,0,"151.00 ",0,0],[29,"\u5236\u4e00\u7ebf",4978,"\u5434\u591a\u4e49",1533672,331,19860,1191600,"129%","129%","140%","180%","180.00 ",-22,0,"158.00 ",0,0],[30,"\u5236\u4e00\u7ebf",6722,"\u9a6c\u4e49\u98ce",1629936,343,20580,1234800,"132%","132%","150%","198%","198.00 ",-22,0,"176.00 ",0,0],[31,"\u5236\u4e00\u7ebf",6651,"\u8983\u73cd\u6362",1623132,339,20340,1220400,"133%","133%","150%","200%","200.00 ",-22,0,"178.00 ",0,0],[32,"\u5236\u4e00\u7ebf",8557,"\u66fe\u5f3a",1649376,332,19920,1195200,"138%","138%","150%","207%","207.00 ",-22,0,"185.00 ",0,0],[33,"\u5236\u4e00\u7ebf",2436,"\u5f90\u5584\u8fde",1883778,356.5,21390,1283400,"147%","147%","150%","220%","220.00 ",-22,0,"198.00 ",0,0],[34,"\u5236\u4e00\u7ebf",2025,"\u674e\u7ecd\u5168",1852928,343.5,20610,1236600,"150%","150%","150%","225%","225.00 ",-22,0,"203.00 ",0,0],[35,"\u5236\u4e00\u7ebf","L0771","\u5f90\u7075\u79c0",783000,290,17400,1044000,"75%","75%","90%","68%","75.00 ",0,0,"75.00 ",0,0],[36,"\u5236\u4e8c\u7ebf",8602,"\u6768\u79c0\u73cd",1420764,341,20460,1227600,"116%","116%","130%","150%","117.00 ",-1,0,"116.00 ",0,0],[37,"\u5236\u4e8c\u7ebf",5731,"\u8d75\u71d5\u534e",1092776,262,15720,943200,"116%","116%","130%","151%","117.00 ",-1,0,"116.00 ",0,0],[38,"\u5236\u4e8c\u7ebf",8103,"\u5468\u7231\u534e",921060,215,12900,774000,"119%","119%","130%","155%","121.00 ",-1,0,"120.00 ",0,0],[39,"\u5236\u4e8c\u7ebf",1609,"\u674e\u6c34\u51e4",1547283,341.5,20490,1229400,"126%","126%","140%","176%","137.00 ",-1,0,"136.00 ",0,0],[40,"\u5236\u4e8c\u7ebf",5754,"\u5434\u5fd7\u82ac",1514537,317,19020,1141200,"133%","133%","150%","199%","155.00 ",-1,0,"154.00 ",0,0],[41,"\u5236\u4e8c\u7ebf",3493,"\u6768\u671d\u51e4",1312200,270,16200,972000,"135%","135%","150%","203%","158.00 ",-1,0,"157.00 ",0,0],[42,"\u5236\u4e8c\u7ebf",100,"\u6842\u4f1a\u743c",1755468,341,20460,1227600,"143%","143%","150%","215%","167.00 ",-1,0,"166.00 ",0,0],[43,"\u5236\u4e8c\u7ebf",8635,"\u738b\u9732",1755468,341,20460,1227600,"143%","143%","150%","215%","167.00 ",-1,0,"166.00 ",0,0],[44,"\u5236\u4e8c\u7ebf",2692,"\u660e\u963f\u6885",1755900,341,20460,1227600,"143%","143%","150%","215%","167.00 ",-1,0,"166.00 ",0,0],[45,"\u5236\u4e8c\u7ebf",8907,"\u5c48\u4f73\u5e73",1767765,335,20100,1206000,"147%","147%","150%","220%","171.00 ",-1,0,"170.00 ",0,0],[46,"\u5236\u4e8c\u7ebf",6924,"\u6613\u5efa\u9999",1477100,266,15960,957600,"154%","154%","150%","231%","180.00 ",-1,0,"179.00 ",0,0],[47,"\u5236\u4e8c\u7ebf",3175,"\u5229\u8fdc\u7984",1897200,340,20400,1224000,"155%","155%","150%","233%","181.00 ",-1,0,"180.00 ",0,0],[48,"\u5236\u4e8c\u7ebf",5212,"\u6797\u4e95\u82f1",1903480,341,20460,1227600,"155%","155%","150%","233%","181.00 ",-1,0,"180.00 ",0,0],[49,"\u5236\u4e8c\u7ebf",5348,"\u9648\u4fdd\u82b9",1884060,337,20220,1213200,"155%","155%","150%","233%","182.00 ",-1,0,"181.00 ",0,0],[50,"\u5236\u4e8c\u7ebf",8470,"\u8096\u9759\u82b3",1945253.2,341,20460,1227600,"158%","158%","150%","238%","185.00 ",-1,0,"184.00 ",0,0],[51,"\u5236\u4e8c\u7ebf",3918,"\u848b\u6709\u5e73",1925820,336,20160,1209600,"159%","159%","150%","239%","186.00 ",-1,0,"185.00 ",0,0],[52,"\u5236\u4e8c\u7ebf",8229,"\u5218\u7ecd\u6674",1930140,336,20160,1209600,"160%","160%","150%","239%","187.00 ",-1,0,"186.00 ",0,0],[53,"\u5236\u4e8c\u7ebf",8788,"\u5f20\u5f69\u534e",1976436,341,20460,1227600,"161%","161%","150%","242%","188.00 ",-1,0,"187.00 ",0,0],[54,"\u5236\u4e8c\u7ebf",3799,"\u4f59\u5b97\u7434",1070486.8,183,10980,658800,"162%","162%","150%","244%","190.00 ",-1,0,"189.00 ",0,0],[55,"\u5236\u4e8c\u7ebf",4736,"\u65bd\u6d77\u82ac",1894820,322,19320,1159200,"163%","163%","150%","245%","191.00 ",-1,0,"190.00 ",0,0],[56,"\u5236\u4e8c\u7ebf",8257,"\u9ad8\u6728\u5174",906900,151,9060,543600,"167%","167%","150%","250%","195.00 ",-1,0,"194.00 ",0,0],[57,"\u5236\u4e8c\u7ebf",7412,"\u738b\u4e91\u971e",2095750,341,20460,1227600,"171%","171%","150%","256%","200.00 ",-1,0,"199.00 ",0,0],[58,"\u5236\u4e8c\u7ebf",5861,"\u9ec4\u6653\u52e4",1979600,317,19020,1141200,"173%","173%","150%","260%","203.00 ",-1,0,"202.00 ",0,0],[59,"\u5236\u4e8c\u7ebf",5604,"\u6f58\u743c",2137476,341,20460,1227600,"174%","174%","150%","261%","204.00 ",-1,0,"203.00 ",0,0],[60,"\u5236\u4e8c\u7ebf",8369,"\u8c22\u5d07\u5409",2155041.2,335,20100,1206000,"179%","179%","150%","268%","209.00 ",-1,0,"208.00 ",0,0],[61,"\u5236\u4e8c\u7ebf",6007,"\u4f55\u53cc\u679d",2212920,341.5,20490,1229400,"180%","180%","150%","270%","211.00 ",-1,0,"210.00 ",0,0],[62,"\u5236\u4e8c\u7ebf",2691,"\u4ee3\u5b66\u5bcc",2289730,341,20460,1227600,"187%","187%","150%","280%","218.00 ",-1,50,"267.00 ",0,0],[63,"\u5236\u4e8c\u7ebf",6773,"\u8463\u6842\u534e",2312486,341,20460,1227600,"188%","188%","150%","283%","220.00 ",-1,0,"219.00 ",0,0],[64,"\u5236\u4e8c\u7ebf",7769,"\u674e\u6842\u53d1",2201450,323,19380,1162800,"189%","189%","150%","284%","222.00 ",-1,50,"271.00 ",0,0],[65,"\u5236\u4e8c\u7ebf",8119,"\u6797\u6625\u6885",2147760,314,18840,1130400,"190%","190%","150%","285%","222.00 ",-1,0,"221.00 ",0,0],[66,"\u5236\u4e8c\u7ebf",7040,"\u9ec4\u79cb\u71d5",2228024,324.5,19470,1168200,"191%","191%","150%","286%","223.00 ",-1,0,"222.00 ",0,0],[67,"\u5236\u4e8c\u7ebf",9095,"\u9093\u91d1\u5170",2245320,297,17820,1069200,"210%","210%","150%","315%","246.00 ",-1,0,"245.00 ",0,0],[68,"\u5236\u4e8c\u7ebf",9288,"\u6c99\u963f\u5477",180576,33,1980,118800,"152%","152%","150%","228%","178.00 ",-1,0,"177.00 ",0,0],[69,"\u5236\u4e8c\u7ebf",9290,"\u6797\u70b3\u79c0",53568,12,720,43200,"124%","124%","140%","174%","135.00 ",-1,0,"134.00 ",0,0],[70,"\u5236\u4e8c\u7ebf","L0697","\u6881\u5bb6\u660e",1061208,289,17340,1040400,"102%","102%","120%","122%","102.00 ",0,0,"102.00 ",0,0],[71,"\u5236\u4e8c\u7ebf","L0698","\u52b3\u8096\u73cd",2061482,340,20400,1224000,"168%","168%","150%","253%","168.00 ",0,0,"168.00 ",0,0],[72,"\u5236\u4e09\u7ebf",9222,"\u5468\u539a\u4ed9",980616,336,20160,1209600,"81%","81%","100%","81%","98.00 ",-4,0,"94.00 ",0,0],[73,"\u5236\u4e09\u7ebf",8611,"\u8983\u4ed9\u743c",1100704,332,19920,1195200,"92%","92%","110%","101%","123.00 ",-4,0,"119.00 ",0,0],[74,"\u5236\u4e09\u7ebf",6013,"\u6e29\u6811\u59e3",846270,229,13740,824400,"103%","103%","120%","123%","149.00 ",-4,0,"145.00 ",0,0],[75,"\u5236\u4e09\u7ebf",8250,"\u674e\u7389\u6885",1261738,336,20160,1209600,"104%","104%","120%","125%","151.00 ",-4,0,"147.00 ",0,0],[76,"\u5236\u4e09\u7ebf",7790,"\u5434\u91d1\u7f8e",1305200,330,19800,1188000,"110%","110%","130%","143%","173.00 ",-4,0,"169.00 ",0,0],[77,"\u5236\u4e09\u7ebf",8790,"\u67e5\u5fb7\u4fa8",1122660,283.5,17010,1020600,"110%","110%","130%","143%","173.00 ",-4,0,"169.00 ",0,0],[78,"\u5236\u4e09\u7ebf",8540,"\u674e\u94f6\u7b11",1366230,333,19980,1198800,"114%","114%","130%","148%","179.00 ",-4,0,"175.00 ",0,0],[79,"\u5236\u4e09\u7ebf",8320,"\u4f55\u6653\u7f8e",1472040,338,20280,1216800,"121%","121%","140%","169%","205.00 ",-4,0,"201.00 ",0,0],[80,"\u5236\u4e09\u7ebf",8314,"\u5510\u798f\u946b",1423602,321.5,19290,1157400,"123%","123%","140%","172%","208.00 ",-4,20,"224.00 ",0,0],[81,"\u5236\u4e09\u7ebf",7276,"\u6222\u6cbb\u751f",1521530,338,20280,1216800,"125%","125%","140%","175%","212.00 ",-4,20,"228.00 ",0,0],[82,"\u5236\u4e09\u7ebf",8750,"\u738b\u8d77",1487723,329,19740,1184400,"126%","126%","140%","176%","213.00 ",-4,0,"209.00 ",0,0],[83,"\u5236\u4e09\u7ebf",8347,"\u6881\u6ce2",1457095,311,18660,1119600,"130%","130%","150%","195%","236.00 ",-4,0,"232.00 ",0,0],[84,"\u5236\u4e09\u7ebf",9030,"\u5218\u5f3a",760770,150,9000,540000,"141%","141%","150%","211%","256.00 ",-4,0,"252.00 ",0,0],[85,"\u5236\u4e09\u7ebf","L0866","\u90ed\u5e7f\u4f1a",1037879,336,20160,1209600,"86%","86%","100%","86%","86.00 ",0,0,"86.00 ",0,0],[86,"\u5236\u56db\u7ebf",9250,"\u674e\u6cfd\u541b",1086220,289,17340,1040400,"104%","104%","120%","125%","100.00 ",0,0,"100.00 ",0,0],[87,"\u5236\u56db\u7ebf",6891,"\u90b9\u9ad8",1472263,337,20220,1213200,"121%","121%","140%","170%","136.00 ",0,0,"136.00 ",0,0],[88,"\u5236\u56db\u7ebf",7354,"\u6768\u548c\u9999",1387012,273,16380,982800,"141%","141%","150%","212%","169.00 ",0,0,"169.00 ",0,0],[89,"\u5236\u56db\u7ebf",8749,"\u738b\u8d63\u6e05",1717174,337,20220,1213200,"142%","142%","150%","212%","170.00 ",0,0,"170.00 ",0,0],[90,"\u5236\u56db\u7ebf",8753,"\u6768\u83ca\u73cd",1737998,337,20220,1213200,"143%","143%","150%","215%","172.00 ",0,0,"172.00 ",0,0],[91,"\u5236\u56db\u7ebf",8638,"\u9ec4\u5bb6\u82b3",1738534,337,20220,1213200,"143%","143%","150%","215%","172.00 ",0,0,"172.00 ",0,0],[92,"\u5236\u56db\u7ebf",8839,"\u5f20\u5584\u4f1f",1560964,296,17760,1065600,"146%","146%","150%","220%","176.00 ",0,0,"176.00 ",0,0],[93,"\u5236\u56db\u7ebf",9038,"\u5218\u7965\u4f1a",1835488,337,20220,1213200,"151%","151%","150%","227%","182.00 ",0,0,"182.00 ",0,0],[94,"\u5236\u56db\u7ebf",7719,"\u5ed6\u6842\u5170",1490070,337,20220,1213200,"123%","123%","140%","172%","190.00 ",0,0,"190.00 ",0,0],[95,"\u5236\u56db\u7ebf",8950,"\u718a\u8363\u52e4",1340864,234,14040,842400,"159%","159%","150%","239%","191.00 ",0,0,"191.00 ",0,0],[96,"\u5236\u56db\u7ebf",4158,"\u6c88\u5b5f\u82f1",1944124,337,20220,1213200,"160%","160%","150%","240%","192.00 ",0,0,"192.00 ",0,0],[97,"\u5236\u56db\u7ebf",5972,"\u738b\u5bb6\u672c",1866775,321,19260,1155600,"162%","162%","150%","242%","194.00 ",0,0,"194.00 ",0,0],[98,"\u5236\u56db\u7ebf",8216,"\u6b27\u5fb7\u660e",1943298,328,19680,1180800,"165%","165%","150%","247%","197.00 ",0,0,"197.00 ",0,0],[99,"\u5236\u56db\u7ebf",8184,"\u674e\u5e94\u4eae",1993010,334,20040,1202400,"166%","166%","150%","249%","199.00 ",0,0,"199.00 ",0,0],[100,"\u5236\u56db\u7ebf",4758,"\u5218\u575a",987760,151,9060,543600,"182%","182%","150%","273%","218.00 ",0,0,"218.00 ",0,0],[101,"\u5236\u56db\u7ebf",7389,"\u8d75\u4ee3\u68cb",2240010,337,20220,1213200,"185%","185%","150%","277%","222.00 ",0,0,"222.00 ",0,0],[102,"\u5236\u56db\u7ebf",6128,"\u90b9\u9648\u79c0",2226678,324,19440,1166400,"191%","191%","150%","286%","229.00 ",0,0,"229.00 ",0,0],[103,"\u5236\u56db\u7ebf",8185,"\u9ec4\u71d5\u9e4f",2357748,329,19740,1184400,"199%","199%","150%","299%","239.00 ",0,0,"239.00 ",0,0],[104,"\u5236\u56db\u7ebf",7747,"\u8096\u4e91\u5170",2444146,337,20220,1213200,"201%","201%","150%","302%","242.00 ",0,0,"242.00 ",0,0],[105,"\u5236\u56db\u7ebf",4173,"\u5218\u8d24\u707f",2403414,315.5,18930,1135800,"212%","212%","150%","317%","254.00 ",0,0,"254.00 ",0,0],[106,"\u5236\u4e94\u7ebf",9281,"\u6768\u5b89\u6167",276750,102.5,6150,369000,"75%","75%","90%","68%","57.00 ",-6,0,"51.00 ",0,0],[107,"\u5236\u4e94\u7ebf",9244,"\u51cc\u6c34\u79c0",953280,331,19860,1191600,"80%","80%","100%","80%","68.00 ",-6,0,"62.00 ",0,0],[108,"\u5236\u4e94\u7ebf",4977,"\u8c2d\u6708\u6c60",1079917,331,19860,1191600,"91%","91%","110%","100%","85.00 ",-6,0,"79.00 ",0,0],[109,"\u5236\u4e94\u7ebf",8536,"\u4f0d\u5f3a",1058400,294,17640,1058400,"100%","100%","120%","120%","102.00 ",-6,0,"96.00 ",0,0],[110,"\u5236\u4e94\u7ebf",9253,"\u5019\u7389\u5a25",1222560,283,16980,1018800,"120%","120%","140%","168%","143.00 ",-6,0,"137.00 ",0,0],[111,"\u5236\u4e94\u7ebf",9246,"\u674e\u51e4\u79c0",1378080,319,19140,1148400,"120%","120%","140%","168%","143.00 ",-6,0,"137.00 ",0,0],[112,"\u5236\u4e94\u7ebf",8581,"\u5189\u9f99\u78a7",1653355,343,20580,1234800,"134%","134%","150%","201%","171.00 ",-6,0,"165.00 ",0,0],[113,"\u5236\u4e94\u7ebf",6910,"\u9a6c\u4e45\u6587",1659740,340,20400,1224000,"136%","136%","150%","203%","173.00 ",-6,0,"167.00 ",0,0],[114,"\u5236\u4e94\u7ebf",9234,"\u5218\u6625\u82b1",1796297,343,20580,1234800,"145%","145%","150%","218%","185.00 ",-6,0,"179.00 ",0,0],[115,"\u5236\u4e94\u7ebf",9230,"\u675c\u4e3d\u5ae6",1743189,327,19620,1177200,"148%","148%","150%","222%","189.00 ",-6,0,"183.00 ",0,0],[116,"\u5236\u4e94\u7ebf",5932,"\u4f55\u5168\u751f",1852200,343,20580,1234800,"150%","150%","150%","225%","191.00 ",-6,0,"185.00 ",0,0],[117,"\u5236\u4e94\u7ebf",8319,"\u660e\u51e4\u82f1",1344600,249,14940,896400,"150%","150%","150%","225%","191.00 ",-6,0,"185.00 ",0,0],[118,"\u5236\u4e94\u7ebf",8181,"\u674e\u8fde\u5a23",1107000,205,12300,738000,"150%","150%","150%","225%","191.00 ",-6,0,"185.00 ",0,0],[119,"\u5236\u4e94\u7ebf",2259,"\u95eb\u7956\u5143",1479600,274,16440,986400,"150%","150%","150%","225%","191.00 ",-6,0,"185.00 ",0,0],[120,"\u5236\u4e94\u7ebf",4311,"\u4efb\u6000\u82ac",1887170,343,20580,1234800,"153%","153%","150%","229%","195.00 ",-6,0,"189.00 ",0,0],[121,"\u5236\u4e94\u7ebf",8625,"\u5f20\u660e\u5eb7",2017148,338,20280,1216800,"166%","166%","150%","249%","211.00 ",-6,0,"205.00 ",0,0],[122,"\u5236\u4e94\u7ebf",8987,"\u5218\u665a\u82b1",2132408.4,343,20580,1234800,"173%","173%","150%","259%","220.00 ",-6,0,"214.00 ",0,0],[123,"\u5236\u4e94\u7ebf",9073,"\u66f9\u5c0f\u8bba",2124527.5,328,19680,1180800,"180%","180%","150%","270%","229.00 ",-6,0,"223.00 ",0,0],[124,"\u5236\u4e94\u7ebf",2776,"\u9ec4\u83ca",2225880,343.5,20610,1236600,"180%","180%","150%","270%","230.00 ",-6,0,"224.00 ",0,0],[125,"\u5236\u4e94\u7ebf",5377,"\u51af\u5148\u82b3",1840528,283,16980,1018800,"181%","181%","150%","271%","230.00 ",-6,0,"224.00 ",0,0],[126,"\u5236\u4e94\u7ebf",1559,"\u59da\u8302\u94a2",2309706,343,20580,1234800,"187%","187%","150%","281%","238.00 ",-6,0,"232.00 ",0,0],[127,"\u5236\u4e94\u7ebf",8387,"\u5218\u534e\u6dfb",2392360,343,20580,1234800,"194%","194%","150%","291%","247.00 ",-6,0,"241.00 ",0,0],[128,"\u5236\u516d\u7ebf",9258,"\u8303\u4e49\u6885",572552,257,15420,925200,"62%","62%","0%","0%","0.00 ",0,0,"0.00 ",0,0],[129,"\u5236\u516d\u7ebf",9148,"\u949f\u4e3d\u82b3",1235811,341,20460,1227600,"101%","101%","120%","121%","193.00 ",0,0,"193.00 ",0,0],[130,"\u5236\u516d\u7ebf",7323,"\u6b27\u9999\u5e73",1367296,341,20460,1227600,"111%","111%","130%","145%","232.00 ",0,0,"232.00 ",0,0],[131,"\u5236\u516d\u7ebf",8085,"\u66f9\u6e05\u6e05",1462108,341,20460,1227600,"119%","119%","130%","155%","248.00 ",0,0,"248.00 ",0,0],[132,"\u5236\u4e03\u7ebf",9224,"\u5218\u8fde\u6ce2",1167480,352.5,21150,1269000,"92%","92%","110%","101%","132.00 ",-2,0,"130.00 ",0,0],[133,"\u5236\u4e03\u7ebf",7735,"\u6768\u6d3b\u9999",1206576,342,20520,1231200,"98%","98%","110%","108%","140.00 ",-2,0,"138.00 ",0,0],[134,"\u5236\u4e03\u7ebf",6909,"\u674e\u4f1a\u82b9",1255824,342,20520,1231200,"102%","102%","120%","122%","159.00 ",-2,0,"157.00 ",0,0],[135,"\u5236\u4e03\u7ebf",8854,"\u674e\u8273\u5e73",245520,62,3720,223200,"110%","110%","130%","143%","186.00 ",-2,0,"184.00 ",0,0],[136,"\u5236\u4e03\u7ebf",8420,"\u9ad8\u5b66\u82b9",1459350,352.5,21150,1269000,"115%","115%","130%","150%","194.00 ",-2,0,"192.00 ",0,0],[137,"\u5236\u4e03\u7ebf",9081,"\u8983\u970d\u5d14",1093680,245,14700,882000,"124%","124%","140%","174%","226.00 ",-2,0,"224.00 ",0,0],[138,"\u5236\u4e03\u7ebf","L0712","\u5218\u4f1f\u535a",1234084,350.5,21030,1261800,"98%","98%","110%","108%","98.00 ",0,0,"98.00 ",0,0],[139,"\u5236\u4e5d\u7ebf",9260,"\u4f0d\u98de",974556,253,15180,910800,"107%","107%","120%","128%","128.00 ",-2,0,"126.00 ",0,0],[140,"\u5236\u4e5d\u7ebf",9259,"\u9ec4\u5bcc\u7f8e",971460,249.5,14970,898200,"108%","108%","120%","130%","130.00 ",-2,0,"128.00 ",0,0],[141,"\u5236\u4e5d\u7ebf",9151,"\u738b\u7389\u4e91",1389056,335,20100,1206000,"115%","115%","130%","150%","150.00 ",-2,0,"148.00 ",0,0],[142,"\u5236\u4e5d\u7ebf",8224,"\u65bd\u5a07\u8fde",1545426,334,20040,1202400,"129%","129%","140%","180%","180.00 ",-2,0,"178.00 ",0,0],[143,"\u5236\u4e5d\u7ebf",8338,"\u8c2d\u78a7\u534e",1722099,336,20160,1209600,"142%","142%","150%","214%","214.00 ",-2,0,"212.00 ",0,0],[144,"\u5236\u4e5d\u7ebf",9176,"\u4efb\u9633\u654f",1727732,336,20160,1209600,"143%","143%","150%","214%","214.00 ",-2,0,"212.00 ",0,0],[145,"\u5236\u4e5d\u7ebf",7754,"\u519c\u660c\u6dfb",1729086,336,20160,1209600,"143%","143%","150%","214%","214.00 ",-2,0,"212.00 ",0,0],[146,"\u5236\u4e5d\u7ebf",8777,"\u6768\u5fb7\u79d1",1569658,304,18240,1094400,"143%","143%","150%","215%","215.00 ",-2,0,"213.00 ",0,0],[147,"\u5236\u4e5d\u7ebf",8856,"\u65bd\u6885\u82f1",1831892,335,20100,1206000,"152%","152%","150%","228%","228.00 ",-2,0,"226.00 ",0,0],[148,"\u5236\u4e5d\u7ebf",9158,"\u9648\u771f\u548c",1896690,335,20100,1206000,"157%","157%","150%","236%","236.00 ",-2,0,"234.00 ",0,0],[149,"\u5236\u4e5d\u7ebf",6589,"\u83ab\u7434\u5a07",1894312,332,19920,1195200,"158%","158%","150%","238%","238.00 ",-2,0,"236.00 ",0,0],[150,"\u5236\u4e5d\u7ebf",8680,"\u5f20\u5efa\u82f1",1974652,339,20340,1220400,"162%","162%","150%","243%","243.00 ",-2,0,"241.00 ",0,0],[151,"\u5236\u5341\u7ebf",9251,"\u5468\u5b66\u6210",723348,283,16980,1018800,"71%","71%","90%","64%","80.00 ",-17,0,"63.00 ",0,0],[152,"\u5236\u5341\u7ebf",9266,"\u6768\u658c",527814,206.5,12390,743400,"71%","71%","90%","64%","80.00 ",-17,0,"63.00 ",0,0],[153,"\u5236\u5341\u7ebf",9252,"\u674e\u660e\u5e86",732600,275,16500,990000,"74%","74%","90%","67%","83.00 ",-17,0,"66.00 ",0,0],[154,"\u5236\u5341\u7ebf",9267,"\u9a6c\u4e49",562104,211,12660,759600,"74%","74%","90%","67%","83.00 ",-17,0,"66.00 ",0,0],[155,"\u5236\u5341\u7ebf",9242,"\u674e\u6cfd\u5bbd",888300,329,19740,1184400,"75%","75%","90%","68%","84.00 ",-17,0,"67.00 ",0,0],[156,"\u5236\u5341\u7ebf",9285,"\u9ec4\u5b8f\u4e3d",184945,67,4020,241200,"77%","77%","90%","69%","86.00 ",-17,0,"69.00 ",0,0],[157,"\u5236\u5341\u7ebf",9241,"\u9a6c\u6587",951054,334.5,20070,1204200,"79%","79%","90%","71%","89.00 ",-17,0,"72.00 ",0,0],[158,"\u5236\u5341\u7ebf",8579,"\u8983\u64cd",1064934,266.5,15990,959400,"111%","111%","130%","144%","180.00 ",-17,0,"163.00 ",0,0],[159,"\u5236\u5341\u7ebf",8155,"\u674e\u71d5\u534e",1276673,347,20820,1249200,"102%","102%","120%","123%","153.00 ",-17,0,"136.00 ",0,0],[160,"\u5236\u5341\u7ebf",7904,"\u5f20\u7fe0\u6167",1408978,339,20340,1220400,"115%","115%","130%","150%","188.00 ",-17,0,"171.00 ",0,0],[161,"\u5236\u5341\u7ebf",8677,"\u6768\u4fdd\u751f",1431778,343.5,20610,1236600,"116%","116%","130%","151%","188.00 ",-17,0,"171.00 ",0,0],[162,"\u5236\u5341\u7ebf",1778,"\u80e1\u5c0f\u5a1f",1459746,342.5,20550,1233000,"118%","118%","130%","154%","192.00 ",-17,0,"175.00 ",0,0],[163,"\u5236\u5341\u7ebf",9273,"\u6768\u5c0f\u7fa4",774676,178,10680,640800,"121%","121%","140%","169%","212.00 ",-17,0,"195.00 ",0,0],[164,"\u5236\u5341\u7ebf",9058,"\u674e\u70b3\u6850",1188790,270,16200,972000,"122%","122%","140%","171%","214.00 ",-17,0,"197.00 ",0,0],[165,"\u5236\u5341\u7ebf",3427,"\u83ab\u5de7\u73b2",1438402,305.5,18330,1099800,"131%","131%","150%","196%","245.00 ",-17,0,"228.00 ",0,0],[166,"\u5236\u5341\u7ebf",9024,"\u674e\u660e\u6797",603167.5,128,7680,460800,"131%","131%","150%","196%","245.00 ",-17,0,"228.00 ",0,0],[167,"\u5236\u5341\u7ebf",9027,"\u5434\u6d77\u82b3",1637290,338.5,20310,1218600,"134%","134%","150%","202%","252.00 ",-17,0,"235.00 ",0,0],[168,"\u5236\u5341\u7ebf",1911,"\u97e6\u5175\u7389",1663962,344,20640,1238400,"134%","134%","150%","202%","252.00 ",-17,0,"235.00 ",0,0],[169,"\u5236\u5341\u7ebf",4483,"\u82cf\u8273\u5170",1678462,343,20580,1234800,"136%","136%","150%","204%","255.00 ",-17,0,"238.00 ",0,0],[170,"\u5236\u5341\u7ebf",8710,"\u6c6a\u5229\u8273",1058107,190,11400,684000,"155%","155%","150%","232%","290.00 ",-17,0,"273.00 ",0,0],[171,"\u5236\u5341\u7ebf",7676,"\u848b\u6797\u6069",1880591,334,20040,1202400,"156%","156%","150%","235%","293.00 ",-17,0,"276.00 ",0,0],[172,"\u5236\u5341\u7ebf","L0737","\u5ed6\u6625\u71d5",1065446,340,20400,1224000,"87%","87%","100%","87%","100.00 ",0,0,"100.00 ",0,0],[173,"\u5236\u516b\u7ebf",6177,"\u738b\u9f99",0,0,0,0,0,0,0,0,"156.44 ",0,0,"156.44 ",0,0],[174,"\u5236\u516b\u7ebf",2799,"\u6768\u6da6\u5e73",0,0,0,0,0,0,0,0,"203.92 ",0,0,"203.92 ",0,0],[175,"\u5236\u516b\u7ebf",9257,"\u848b\u6dd1\u82ac",0,0,0,0,0,0,0,0,"174.12 ",0,0,"174.12 ",0,0],[176,"\u5236\u516b\u7ebf",8952,"\u5170\u6d77\u71d5",0,0,0,0,0,0,0,0,"172.12 ",0,0,"172.12 ",0,0],[177,"\u5236\u516b\u7ebf",3294,"\u5ed6\u5f69\u71d5",0,0,0,0,0,0,0,0,"189.48 ",0,0,"189.48 ",0,0],[178,"\u5236\u516b\u7ebf",9085,"\u4f0d\u57f9\u677e",0,0,0,0,0,0,0,0,"182.36 ",0,0,"182.36 ",0,0],[179,"\u5236\u516b\u7ebf",9236,"\u6a0a\u96ea\u51e4",0,0,0,0,0,0,0,0,"174.68 ",0,0,"174.68 ",0,0],[180,"\u5236\u516b\u7ebf",9010,"\u738b\u7f8e\u8273",0,0,0,0,0,0,0,0,"185.48 ",0,0,"185.48 ",0,0],[181,"\u5236\u516b\u7ebf",8138,"\u83ab\u7ecd\u9f99",0,0,0,0,0,0,0,0,"167.66 ",0,0,"167.66 ",0,0]]'));
        $redis = phpredis();
        dd($redis->lpush('KpiDirectWorkers', serialize(['2021-04' => $data])));
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

    public function syncPdoPhs()
    {
        $prodObj = new \app\webApi\model\Prod();
        $res = $prodObj->syncPdoPhs();
        dd($res);
    }
}

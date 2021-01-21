<?php
/*
 * @Author: yanbuw1911
 * @Date: 2020-11-18 14:56:05
 * @LastEditTime: 2020-12-25 16:35:46
 * @LastEditors: yanbuw1911
 * @Description: 生管部模型
 * @FilePath: \backend\app\webApi\model\Prod.php
 */

namespace app\webApi\model;

use PDO;
use think\facade\Db;

class Prod
{
    /**
     * @description: 生产单列表
     * @param string $prodLine 生产线
     * @param string $year 年
     * @param string $month 月
     * @return array
     */
    public function prodSchdList(string $prodLine, string $year, string $month): array
    {
        $t = "prodlib_prdschd_initpo";

        $cond = [
            'ppi_workshop'              => $prodLine,
            'ppi_po_year'               => $year,
            'ppi_po_month'              => $month
        ];
        $fields = [
            'id',
            'ppi_workshop_name',
            'ppi_customer_no',
            'ppi_customer_pono',
            'ppi_prd_item',
            'ppi_po_qty',
            'ppi_expected_qty',
            'ppi_actual_qty',
            'ppi_expected_date',
            'ppi_actual_date',
            'ppi_po_sort',
            'ppi_is_dirty'
        ];
        $res = Db::table($t)->field($fields)->where($cond)->select()->toArray();

        return $res;
    }

    /**
     * @description: 获取月行事历设定
     * @param string $year 年
     * @param string $month 月
     * @param int $isRest 是否是休息
     * @return array
     */
    public function calendar(string $year, string $month, int $isRest = null, int $isCustom = null): array
    {
        $t = 'prodlib_prdschd_initcald';

        $cond = "ppi_cald_year=$year AND ppi_cald_month=$month";
        if ($isRest !== null && $isCustom !== null) {
            $cond .= " AND ( ppi_cald_is_rest=$isRest OR ppi_cald_profile is not null )";
        } else if ($isRest !== null) {
            $cond .= " AND ppi_cald_is_rest=$isRest";
        } elseif ($isCustom !== null) {
            $cond .= "AND ppi_cald_profile is not null ";
        }
        $res = Db::table($t)->whereRaw($cond)->select()->toArray();

        return $res;
    }

    /**
     * @description: 同步生产单
     * @param string $prodLine 生产线
     * @param string $year 年
     * @param string $month 月
     * @return bool
     */
    public function syncProdSchdParam(string $prodLine, string $year, string $month): bool
    {
        $t = "prodlib_prdschd_initpo";

        $dbinfo = [
            // 数据库类型
            'type'                      => 'Sqlsrv', //必须输入
            // 用户名
            'username'                  => 'sa',
            // 密码
            'password'                  => 'Sql_2008',
            // 连接dsn,驱动、服务器地址和端口、数据库名称
            // 'dsn'                    => 'odbc:Driver={SQL Server};Server=192.168.123.245,1433;Database=databasesdwx',
            'dsn'                       => 'sqlsrv:server=192.168.123.245,1433;Database=databasesdwx;',
        ];
        $dbh = new PDO($dbinfo['dsn'], $dbinfo['username'], $dbinfo['password']);

        $sql = "SELECT * FROM gbplan WHERE year=$year and month=$month AND partno='$prodLine'";
        $toBeSyncData = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        Db::startTrans();
        $delCond = [
            'ppi_po_year'               => $year,
            'ppi_po_month'              => $month,
            'ppi_workshop'              => $prodLine
        ];

        $res = Db::table($t)->where($delCond)->delete();
        if (false === $res) {
            Db::rollback();
            return false;
        }

        $insertArr = array_map(function ($e) {
            return [
                'ppi_workshop'              => $e['partno'],
                'ppi_workshop_name'         => $e['part'],
                'ppi_customer_no'           => $e['kh_no'],
                'ppi_customer_pono'         => $e['khpono'],
                'ppi_prd_item'              => $e['sp_no'],
                'ppi_po_qty'                => $e['qty'],
                'ppi_expected_qty'          => $e['jhqty'],
                'ppi_expected_date'         => $e['crt'],
                'ppi_actual_date'           => $e['planindate'],
                'ppi_po_year'               => $e['year'],
                'ppi_po_month'              => strlen($e['month']) == 1 ? 0 . $e['month'] : $e['month'],
                'ppi_po_sort'               => $e['sort'],
                'ppi_is_dirty'              => 1,
            ];
        }, $toBeSyncData);
        $res = Db::table($t)->insertAll($insertArr);

        if (false === $res) {
            Db::rollback();
            return false;
        }

        Db::commit();
        return true;
    }

    /**
     * 生产单列表
     * @param  string $prodLine 生产线
     * @param  string $year 生产单年份
     * @param  string $month 生产单月份
     * @return array $prodOrdersList 生产单列表，包含一款款号多个工序的糅余记录
     * @access public
     */
    public function prodOrders(string $prodLine, string $year, string $month): array
    {
        $prodOrderTbl   = 'prodlib_prdschd_initpo';
        $prodPhasesTbl  = 'prodlibmap_prdschd_initpdo2phs';

        $prodOrdersSql  = "SELECT
                            potbl.id,
                            potbl.ppi_workshop_name,
                            potbl.ppi_customer_no,
                            potbl.ppi_customer_pono,
                            potbl.ppi_prd_item,
                            potbl.ppi_po_qty,
                            potbl.ppi_expected_qty,
                            potbl.ppi_actual_qty,
                            potbl.ppi_expected_date,
                            potbl.ppi_actual_date,
                            potbl.ppi_po_sort,
                            potbl.ppi_is_dirty,
                            phstbl.map_ppi_phsid,
                            phstbl.map_ppi_phs,
                            sum( phstbl.map_ppi_cost_time ) map_ppi_cost_time,
                            phstbl.map_ppi_seq,
                            phstbl.map_ppi_phs_desc,
                            phstbl.map_ppi_aheadtime,
                            phstbl.map_ppi_deadtime,
                            phstbl.map_ppi_outime,
                            phstbl.map_ppi_ismaster,
                            phstbl.map_ppi_isdirty 
                        FROM
                            $prodPhasesTbl AS phstbl,
                            $prodOrderTbl AS potbl 
                        WHERE
                            phstbl.map_ppi_cost_time > 0 
                            AND potbl.ppi_workshop = ? 
                            AND potbl.ppi_po_year = ? 
                            AND potbl.ppi_po_month = ? 
                            -- AND potbl.ppi_prd_item = 'B60530' 
                            -- AND potbl.ppi_customer_pono = '85101' 
                            -- AND potbl.ppi_customer_no = 'JSTW' 
                            AND phstbl.map_ppi_prd_item = potbl.ppi_prd_item 
                        GROUP BY
                            phstbl.map_ppi_phsid,
                            phstbl.map_ppi_phs,
                            potbl.id 
                        ORDER BY
                            potbl.ppi_po_sort,
                            potbl.id,
                            phstbl.map_ppi_phsid";
        // 生产单列表，包含一款款号多个工序的糅余记录
        $prodOrdersList = Db::query($prodOrdersSql, [$prodLine, $year, $month]);

        return $prodOrdersList;
    }

    /**
     * 生产单列表
     * @param  string $prodLine 生产线
     * @param  string $year 生产单年份
     * @param  string $month 生产单月份
     * @param  string $prodList 生产单主键
     * @return array $prodOrdersList 生产单列表，包含一款款号多个工序的糅余记录，调试方法
     * @access public
     */
    public function prodOrdersInspection(string $prodLine, string $year, string $month, string $prodList, string $phsid): array
    {
        $prodOrderTbl   = 'prodlib_prdschd_initpo';
        $prodPhasesTbl  = 'prodlibmap_prdschd_initpdo2phs';

        $cond = "";
        if ($prodList) {
            $cond .= "AND potbl.id IN ( $prodList )";
        }
        if ($phsid) {
            $cond .= "AND phstbl.map_ppi_phsid = '$phsid'";
        }

        $prodOrdersSql  = "SELECT
                            potbl.id,
                            potbl.ppi_workshop_name,
                            potbl.ppi_customer_no,
                            potbl.ppi_customer_pono,
                            potbl.ppi_prd_item,
                            potbl.ppi_po_qty,
                            potbl.ppi_expected_qty,
                            potbl.ppi_actual_qty,
                            potbl.ppi_expected_date,
                            potbl.ppi_actual_date,
                            potbl.ppi_po_sort,
                            potbl.ppi_is_dirty,
                            phstbl.map_ppi_phsid,
                            phstbl.map_ppi_phs,
                            sum( phstbl.map_ppi_cost_time ) map_ppi_cost_time,
                            phstbl.map_ppi_seq,
                            phstbl.map_ppi_phs_desc,
                            phstbl.map_ppi_aheadtime,
                            phstbl.map_ppi_deadtime,
                            phstbl.map_ppi_outime,
                            phstbl.map_ppi_ismaster,
                            phstbl.map_ppi_isdirty 
                        FROM
                            $prodPhasesTbl AS phstbl,
                            $prodOrderTbl AS potbl 
                        WHERE
                            phstbl.map_ppi_cost_time > 0 
                            AND potbl.ppi_workshop = ? 
                            AND potbl.ppi_po_year = ? 
                            AND potbl.ppi_po_month = ? 
                            $cond 
                            AND phstbl.map_ppi_prd_item = potbl.ppi_prd_item 
                        GROUP BY
                            phstbl.map_ppi_phsid,
                            phstbl.map_ppi_phs,
                            potbl.id 
                        ORDER BY
                            potbl.ppi_po_sort,
                            potbl.id,
                            phstbl.map_ppi_phsid";
        // 生产单列表，包含一款款号多个工序的糅余记录
        $prodOrdersList = Db::query($prodOrdersSql, [$prodLine, $year, $month]);

        return $prodOrdersList;
    }

    /**
     * @description: 生产参数，如上下班时间，工序每批次生产的数量
     * @return array
     */
    public function prdSchdParam(): array
    {
        $t   = "prodlib_prdschd_initext";
        $res = Db::table($t)->select()->toArray();

        return $res;
    }

    /**
     * @description: 设置行事历，可设置哪天是休息时间，哪天加班，哪天上下班时间是怎样的
     * @param array $opt 行事历编辑数据
     * @return bool
     */
    public function calendarOpt(array $opt): bool
    {
        $t  = "prodlib_prdschd_initcald";

        return Common::handleOpt($t, $opt);
    }

    /**
     * @description: 设置上下班时间
     * @param string $timestr 时间字符串[YYYY-MM-DD]
     * @return bool
     */
    public function setWorktime(string $timestr): bool
    {
        $t = "prodlib_prdschd_initext";

        $res = Db::table($t)
            ->where('ppi_extra_key', 'ppi_workday_time_range')
            ->update(['ppi_extra_value' => $timestr]);

        return false !== $res;
    }
}
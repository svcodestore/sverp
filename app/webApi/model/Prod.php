<?php
/*
 * @Author: yanbuw1911
 * @Date: 2020-11-18 14:56:05
 * @LastEditTime: 2021-05-11 16:04:10
 * @LastEditors: yanbuw1911
 * @Description: 生管部模型
 * @FilePath: /sverp/app/webApi/model/Prod.php
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
     * @description: 同步计划交期
     * @return bool
     */
    public function syncPlanindate(): bool
    {
        $sql = "SELECT *, MAX(ppa_phs_complete) AS planindate
                    FROM (SELECT a.ppa_prdo_id,
                                a.ppa_phs_start,
                                a.ppa_phs_complete,
                                b.ppi_customer_no,
                                b.ppi_customer_pono,
                                b.ppi_prd_item,
                                b.ppi_po_year,
                                b.ppi_po_month
                        FROM prodlib_prdschd_auto as a
                                LEFT JOIN prodlib_prdschd_initpo AS b ON a.ppa_prdo_id = b.id) AS a
                    GROUP BY ppa_prdo_id";

        $dates = Db::query($sql);

        $dbh = pdosqlsrv();

        $flag = true;
        // 这里应开启事务
        foreach ($dates as $date) {
            $sql = "UPDATE gbplan SET planindate = '{$date['planindate']}' WHERE 
                kh_no = '{$date['ppi_customer_no']}' AND 
                khpono = '{$date['ppi_customer_pono']}' AND 
                sp_no = '{$date['ppi_prd_item']}'";
            $flag = $flag !== false && $dbh->query(
                $sql
            );
        }
        return $flag;
    }


    /**
     * @description: 同步工站
     * @return bool
     */
    public function syncPdoPhs(): bool
    {
        $t = "prodlibmap_prdschd_initpdo2phs";

        $dbh = pdosqlsrv();
        $sql = "SELECT * FROM prdmodel";
        $toBeSyncData = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        Db::startTrans();
        $sql = "TRUNCATE TABLE {$t}";
        Db::table($t)->exec($sql);
        $data = array_map(function ($e) {
            return [
                'map_ppi_prd_item' => $e['facno'],
                'map_ppi_phsid' => $e['jdno'],
                'map_ppi_phs' => $e['jdname'],
                'map_ppi_phs_desc' => $e['descn'],
                'map_ppi_ismaster' => $e['iszf'] != '2' ? 1 : 0,
                'map_ppi_seq' => $e['item'],
                'map_ppi_cost_time' => (int) $e['price'],
                'map_ppi_deadtime' => $e['worktimesh'],
                'map_ppi_aheadtime' => $e['pricez'],
                'map_ppi_outime' => $e['pricef'],
            ];
        }, $toBeSyncData);
        $result = Db::name('starvc_homedb.prodlibmap_prdschd_initpdo2phs')->insertAll($data);
        if (false !== $result) {
            Db::commit();
        }

        return false !== $result;
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

        $dbh = pdosqlsrv();

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


    public function prodItemSubphases(string $prdItem, string $phsid): array
    {
        $table = 'prodlibmap_prdschd_initpdo2phs';
        $cond  = [
            'map_ppi_phsid'    => $phsid,
            'map_ppi_prd_item' => $prdItem
        ];

        return Db::table($table)->where($cond)->select()->toArray();
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
                            SUM( phstbl.map_ppi_cost_time ) AS map_ppi_cost_time,
                            phstbl.map_ppi_seq,
                            phstbl.map_ppi_phs_desc,
                            SUM( phstbl.map_ppi_aheadtime ) AS map_ppi_aheadtime,
                            SUM( phstbl.map_ppi_deadtime ) AS map_ppi_deadtime,
                            SUM( phstbl.map_ppi_outime ) AS map_ppi_outime,
                            SUM( IF( phstbl.map_ppi_ismaster = 1, 0, 1 ) ) AS map_ppi_isvice,
                            phstbl.map_ppi_isdirty 
                        FROM
                            $prodPhasesTbl AS phstbl,
                            $prodOrderTbl AS potbl 
                        WHERE potbl.ppi_workshop = ? 
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

    public function schdRecords(string $year, string $month, string $prodLine): array
    {
        $sql = "SELECT *
                    FROM prodlib_prdschd_auto AS A,
                        (SELECT *
                        FROM prodlib_prdschd_initpo
                        WHERE ppi_po_year = ? AND ppi_po_month = ? AND ppi_workshop = ?) AS B
                    WHERE A.ppa_prdo_id = B.id";

        return Db::query($sql, [$year, $month, $prodLine]);
    }

    public function insertSchdRecords(array $records): bool
    {
        return false !== Db::table('prodlib_prdschd_auto')->insertAll($records);
    }
}

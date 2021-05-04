<?php
/*
 * @Author: yanbuw1911
 * @Date: 2020-11-18 15:00:44
 * @LastEditTime: 2021-05-04 09:17:37
 * @LastEditors: yanbuw1911
 * @Description: 
 * @FilePath: /sverp/app/webApi/controller/Prod.php
 */

namespace app\webApi\controller;

use app\webApi\model\Prod as ModelProd;
use Dompdf\Dompdf;
use Dompdf\Options;
use think\facade\Cache;

class Prod
{
    // 中午休息时间
    private  $morningWorkRest          = 0;
    // 下午休息时间
    private  $afternoonWorkRest        = 0;
    private const SECONDS_OF_DAY       = 86400;
    private const WORK_DATETIME_FORMAT = 'Y-m-d H:i:s';
    private const WORK_DATE_FORMAT     = 'Y-m-d';

    /**
     * @description: 获取每月的生产行事历安排
     */
    public function getCalenderData()
    {
        $date          = input('post.date');
        $rtn['result'] = true;
        $rtn['data']   = [];
        if (empty($date)) {
            return json($rtn);
        }

        list($year, $month) = explode('/', $date);
        $rtn['data']        = (new ModelProd())->calendar($year, $month);

        return json($rtn);
    }

    /**
     * @description: 获取生产单
     */
    public function getProdSchdData()
    {
        $date               = input('post.date');
        $prodLine           = input('post.prodLine');
        list($year, $month) = explode('-', $date);

        $res                = (new ModelProd())->prodSchdList($prodLine, $year, $month);
        $rtn['result']      = true;
        $rtn['data']        = $res;

        return json($rtn);
    }

    /**
     * @description: 同步生产单
     */
    public function syncProdSchdParam()
    {
        $date               = input('post.date');
        $prodLine           = input('post.prodLine');
        list($year, $month) = explode('-', $date);

        $res                = (new ModelProd())->syncProdSchdParam($prodLine, $year, $month);
        $rtn['result']      = $res;

        return json($rtn);
    }

    public function getProdItemSubphases()
    {
        $prdItem = input('post.prdItem');
        $phsid = input('post.phsid');

        $rtn['result'] = true;
        $rtn['data'] = (new ModelProd())->prodItemSubphases($prdItem, $phsid);

        return json($rtn);
    }

    /**
     * 处理工序开始时间。加入工作日上下班休息时间
     * @param  int $computeStartAt 工序开始时间，时间戳。未算入算入工作日上下班休息时间。
     * @param  array $worktimeArr 工作日上下班时间，H:i:s。
     * @param  bool $isFirstPhase 是否是第一工序
     * @param  array $arrangeDays 行事历设定
     * @return int $phaseActualStartAt 工序完成时间，时间戳。已算入工作日上下班休息时间。
     * @description 开始时间超过下班时间点就算对应的休息时间，相邻的工序生产开始时间这里假设不超过一天
     * @access private
     */
    private function handlePhaseStartTime(int $computeStartAt, array $worktimeArr, bool $isFirstPhase, array $arrangeDays): int
    {
        list(
            $morningWorktimeStart,
            $morningWorktimeStop,
            $afternoonWorktimeStart,
            $afternoonWorktimeStop,
            $eveningWorktimeStart,
            $eveningWorktimeStop
        ) = $worktimeArr;

        // 算入行事历设定
        $currArrange = current($arrangeDays);
        if ($currArrange && $currArrange['ppi_cald_is_rest'] === 0) {
            if (date('d', $computeStartAt) === substr($currArrange['ppi_cald_date'], -2)) {
                if (isset($currArrange['morning'])) {
                    list($morningWorktimeStart, $morningWorktimeStop) = explode(' - ', $currArrange['morning']);
                }
                if (isset($currArrange['afternoon'])) {
                    list($afternoonWorktimeStart, $afternoonWorktimeStop) = explode(' - ', $currArrange['afternoon']);
                }
                if (isset($currArrange['evening'])) {
                    list($eveningWorktimeStart, $eveningWorktimeStop) = explode(' - ', $currArrange['evening']);
                }
            }
        }
        // 计算中午休息时间
        if ($afternoonWorktimeStart && $morningWorktimeStop) {
            $this->morningWorkRest = strtotime($afternoonWorktimeStart)  - strtotime($morningWorktimeStop);
        }
        // 计算下午休息时间
        if ($eveningWorktimeStart && $afternoonWorktimeStop) {
            $this->afternoonWorkRest = strtotime($eveningWorktimeStart)  - strtotime($afternoonWorktimeStop);
        }

        $computeStartDate          = date(self::WORK_DATE_FORMAT, $computeStartAt);
        $morningWorkDatetimeStart  = strtotime("$computeStartDate $morningWorktimeStart");
        $morningWorkDatetimeStop   = strtotime("$computeStartDate $morningWorktimeStop");
        $afternoonWorkDatetimeStop = strtotime("$computeStartDate $afternoonWorktimeStop");
        $eveningWorkDatetimeStop   = strtotime("$computeStartDate $eveningWorktimeStop");

        $phaseActualStartAt = $computeStartAt;
        if (
            $phaseActualStartAt > $morningWorkDatetimeStop &&
            $phaseActualStartAt - $morningWorkDatetimeStop < $this->morningWorkRest
        ) {

            // 不是第一道工序时才加入休息时间
            if (!$isFirstPhase) {
                $phaseActualStartAt += $this->morningWorkRest;
            }
        }
        if (
            $phaseActualStartAt > $afternoonWorkDatetimeStop &&
            $phaseActualStartAt - $afternoonWorkDatetimeStop < $this->afternoonWorkRest
        ) {
            if (!$isFirstPhase) {
                $phaseActualStartAt += $this->afternoonWorkRest;
            }
        }
        if ($phaseActualStartAt > $eveningWorkDatetimeStop) {
            if (!$isFirstPhase) {
                // 工序开始时间间隔不会超过一天
                $phaseActualStartAt = $morningWorkDatetimeStart + self::SECONDS_OF_DAY;
            }
        }

        return $phaseActualStartAt;
    }

    /**
     * 处理工序完成时间。加入工作日上下班休息时间
     * @param  int $computeStartAt 工序开始时间，时间戳。已算入工作日上下班休息时间。
     * @param  int &$phaseCompleteAt 工序完成时间，时间戳。未算入工作日上下班休息时间。
     * @param  array $worktimeArr 工作日上下班时间，H:i:s。
     * @param  int $arrangeDays 设置的日期数组，设定的日期为休息或其它。
     * @return int $phaseActualCompleteAt 工序完成时间，时间戳。已算入工作日上下班休息时间。
     * @description 函数内自身递归调用自身。通过工序开始、完成时间是否在同一天进行计算
     * @access private
     */
    private function handlePhaseCompleteTime(int $computeStartAt, int &$phaseCompleteAt, array $worktimeArr, array $arrangeDays): int
    {
        list(
            $morningWorktimeStart,
            $morningWorktimeStop,
            $afternoonWorktimeStart,
            $afternoonWorktimeStop,
            $eveningWorktimeStart,
            $eveningWorktimeStop
        ) = $worktimeArr;

        $startDay                   = date('d', $computeStartAt);
        $completeDay                = date('d', $phaseCompleteAt);

        // 算入行事历设定
        $currArrange = current($arrangeDays);
        if ($currArrange && $currArrange['ppi_cald_is_rest'] === 0) {
            if (date('d', $computeStartAt) === substr($currArrange['ppi_cald_date'], -2)) {
                if (isset($currArrange['morning'])) {
                    list($morningWorktimeStart, $morningWorktimeStop) = explode(' - ', $currArrange['morning']);
                }
                if (isset($currArrange['afternoon'])) {
                    list($afternoonWorktimeStart, $afternoonWorktimeStop) = explode(' - ', $currArrange['afternoon']);
                }
                if (isset($currArrange['evening'])) {
                    list($eveningWorktimeStart, $eveningWorktimeStop) = explode(' - ', $currArrange['evening']);
                }
            } else {
                next($arrangeDays);
            }
        }
        // 计算中午休息时间
        if ($afternoonWorktimeStart && $morningWorktimeStop) {
            $this->morningWorkRest = strtotime($afternoonWorktimeStart)  - strtotime($morningWorktimeStop);
        }
        // 计算下午休息时间
        if ($eveningWorktimeStart && $afternoonWorktimeStop) {
            $this->afternoonWorkRest = strtotime($eveningWorktimeStart)  - strtotime($afternoonWorktimeStop);
        }

        $computeStartDate           = date(self::WORK_DATE_FORMAT, $computeStartAt);
        $morningWorkDatetimeStart   = strtotime("$computeStartDate $morningWorktimeStart");
        $morningWorkDatetimeStop    = strtotime("$computeStartDate $morningWorktimeStop");
        $afternoonWorkDatetimeStart = strtotime("$computeStartDate $afternoonWorktimeStart");
        $afternoonWorkDatetimeStop  = strtotime("$computeStartDate $afternoonWorktimeStop");
        $eveningWorkDatetimeStart   = strtotime("$computeStartDate $eveningWorktimeStart");
        $eveningWorkDatetimeStop    = strtotime("$computeStartDate $eveningWorktimeStop");

        $timePoints = [
            $morningWorkDatetimeStart,
            $morningWorkDatetimeStop,
            $afternoonWorkDatetimeStart,
            $afternoonWorkDatetimeStop,
            $eveningWorkDatetimeStart,
            $eveningWorkDatetimeStop,
        ];

        $phaseActualCompleteAt     = $phaseCompleteAt;
        // 如果工序在当天内完成
        // 比较完成时间是否超过下班时间点，超过则算入对应的休息时间
        // 开始完成在同一天
        if ($startDay === $completeDay) {
            if (
                $phaseActualCompleteAt > $morningWorkDatetimeStop
            ) {
                $phaseActualCompleteAt += $this->morningWorkRest;
            }
            if (
                $phaseActualCompleteAt > $afternoonWorkDatetimeStop
            ) {
                $phaseActualCompleteAt += $this->afternoonWorkRest;
            }

            // 当完成时间在当天下班时间之后，则开始时间和完成时间各增加一天，递归计算
            if ($phaseActualCompleteAt > $eveningWorkDatetimeStop) {
                // 递归计算无效，暂时假设超出部分不会超过一天的工时 ==
                $timeOfNextDay         = $phaseActualCompleteAt - $eveningWorkDatetimeStop;

                $phaseActualCompleteAt = strtotime('1 day', $morningWorkDatetimeStart) + $timeOfNextDay;
                $actualCompleteDay     = date('d', $phaseActualCompleteAt);
                $nextComputeStartDay  = date('d', strtotime('1 day', $morningWorkDatetimeStart));

                if ($nextComputeStartDay === $actualCompleteDay) {
                    $computeStartAt = ($morningWorkDatetimeStart + self::SECONDS_OF_DAY);
                    return $this->handlePhaseCompleteTime(
                        $computeStartAt,
                        $phaseActualCompleteAt,
                        $worktimeArr,
                        $arrangeDays
                    );
                }
            }
            return $phaseActualCompleteAt;

            // 不在同一天，就减去当天开始到当天下班的时间，得到剩余时间进行递归计算
        } else {
            $timeOfNextDay         = $phaseActualCompleteAt - $eveningWorkDatetimeStop;

            $phaseActualCompleteAt = strtotime('1 day', $morningWorkDatetimeStart) + $timeOfNextDay;
            $computeStartAt = ($morningWorkDatetimeStart + self::SECONDS_OF_DAY);
            return $this->handlePhaseCompleteTime(
                $computeStartAt,
                $phaseActualCompleteAt,
                $worktimeArr,
                $arrangeDays
            );
        }
    }

    /**
     * @description: 自动排程算法，通过生产单，行事历，生产工序信息（耗时）三个表中的数据来计算
     */
    public function autoSchedule()
    {
        $params         = $this->getAutoSchdParam();
        $prodOrdersList = $params['prodOrdersList'];
        if (count($prodOrdersList) === 0) {
            return json([
                'result' => true,
                'data'   => []
            ]);
        }
        $year           = $params['year'];
        $month          = $params['month'];
        $prodLine       = $params['prodLine'];
        $arrangeDays    = $params['arrangeDays'];
        $schdParams     = $params['schdParams'];
        $schdMode       = $params['schdMode'];

        $cacheKey = "autoschd:$year:$month:$prodLine";
        $autoschdCache = unserialize(Cache::store('redis')->get($cacheKey));
        if ($autoschdCache) {
            $rtn['result'] = true;
            $rtn['data']   = $autoschdCache;
            return json($rtn);
        }

        foreach ($schdParams as $schdParam) {
            if ($schdParam['ppi_extra_key'] === 'ppi_bisection_cnt') {
                // 工序生产量等分数
                $splitCount = $schdParam['ppi_extra_value'];
            }

            if ($schdParam['ppi_extra_key'] === 'ppi_workday_time_range') {
                // 每天的上下班时间，字符串
                $worktimeStr = $schdParam['ppi_extra_value'];
            }
        }
        preg_match_all('/[\d:]{8}/', $worktimeStr, $worktimeArr);
        list(
            $morningWorktimeStart,
            $morningWorktimeStop,
            $afternoonWorktimeStart,
            $afternoonWorktimeStop,
            $eveningWorktimeStart,
            $eveningWorktimeStop
        ) = $worktimeArr[0];
        // 计算中午休息时间
        if ($afternoonWorktimeStart && $morningWorktimeStop) {
            $this->morningWorkRest = strtotime($afternoonWorktimeStart)  - strtotime($morningWorktimeStop);
        }
        // 计算下午休息时间
        if ($eveningWorktimeStart && $afternoonWorktimeStop) {
            $this->afternoonWorkRest = strtotime($eveningWorktimeStart)  - strtotime($afternoonWorktimeStop);
        }

        $firstWorkdayOfMonth = "$year-$month-01";
        // 月初是休息日则下一非休息天开始排程
        foreach ($arrangeDays as $k => $v) {
            $currArrange = current($arrangeDays);
            if ($firstWorkdayOfMonth === $currArrange['ppi_cald_date']) {
                // 如果是休息日
                if ($currArrange['ppi_cald_is_rest']) {
                    $firstWorkdayOfMonth = date(
                        self::WORK_DATE_FORMAT,
                        strtotime(
                            '1 day',
                            strtotime($firstWorkdayOfMonth)
                        )
                    );
                    array_shift($arrangeDays);
                    // 不是休息日，按设定的上下班时间排程
                } else {
                    if (isset($currArrange['morning'])) {
                        $morningWorktimeStart = explode(' - ', $currArrange['morning'])[0];
                    }
                }
            } else {
                $startSchDate = $firstWorkdayOfMonth;
                break;
            }
        }
        $startSchDate    = $firstWorkdayOfMonth;
        // 排程开始日期
        // $schStartAt     = strtotime("2020-11-04 15:32:12");
        $schStartAt     = strtotime("$startSchDate $morningWorktimeStart");

        // =========== 设置生产单中工序耗时最长的工序为其它工序的耗时 ====================
        $phsCost = [];
        foreach ($prodOrdersList as $orderItem) {
            $isExisted = false;
            foreach ($phsCost as $orderInfo) {
                if ($orderInfo['id'] == $orderItem['id']) {
                    $isExisted = true;
                    break;
                }
            }

            if (!$isExisted) {
                $phsCost[] = [
                    'id' => $orderItem['id'],
                ];
            }

            foreach ($phsCost as $k => $v) { # 首先是连续不休息地排程
                if ($v['id'] == $orderItem['id']) {
                    $phsCost[$k]['cost'][] = $orderItem['map_ppi_cost_time'];
                }
            }
        }
        $maxPhsCost = array_map(fn ($e) => max($e['cost']), $phsCost);
        // ===========================================================================
        // 工序排程开始时间
        $phaseStartAt   = $schStartAt;
        // 重新组织生产单信息，将每一款款号的工序存于生产单的 phases 键中
        $prodOrdersInfo = [];
        $tmpProdOrdersInfo = [];
        foreach ($prodOrdersList as $orderItem) {
            $isExisted = false;
            foreach ($tmpProdOrdersInfo as $orderInfo) {
                if ($orderInfo['prdoid'] == $orderItem['id']) {
                    $isExisted = true;
                    break;
                }
            }

            // 不存在则插入数据
            if (!$isExisted) {
                $prodOrder = [
                    'prdoid'            => $orderItem['id'],
                    'ppi_workshop_name' => $orderItem['ppi_workshop_name'],
                    'ppi_customer_no'   => $orderItem['ppi_customer_no'],
                    'ppi_customer_pono' => $orderItem['ppi_customer_pono'],
                    'ppi_prd_item'      => $orderItem['ppi_prd_item'],
                    'ppi_po_qty'        => $orderItem['ppi_po_qty'] ?: $orderItem['ppi_expected_qty'],
                    'ppi_expected_qty'  => $orderItem['ppi_expected_qty'],
                    'ppi_actual_qty'    => $orderItem['ppi_actual_qty'],
                    'ppi_expected_date' => $orderItem['ppi_expected_date'],
                    'ppi_actual_date'   => $orderItem['ppi_actual_date'],
                    'ppi_po_sort'       => $orderItem['ppi_po_sort'],
                    'ppi_is_dirty'      => $orderItem['ppi_is_dirty']
                ];

                // 由于只要上一个工站的完成时间，控制遍历的数组为2个元素
                if (count($tmpProdOrdersInfo) > 1) {
                    $prodOrdersInfo[] = array_shift($tmpProdOrdersInfo);
                }
                if (count($prodOrdersInfo) + count($tmpProdOrdersInfo) === count($phsCost) - 1) {
                    $prodOrdersInfo[] = end($tmpProdOrdersInfo);
                }
                array_push($tmpProdOrdersInfo, $prodOrder);
            }

            // 根据已插入的生产单号匹配其工序，开始计算排程，
            // 排程逻辑为：生产单的每一单的各个工序根据工序耗时和生产量来计算，
            // 默认排程开始时间为每月的第一个工作日，
            // 生产单的第一工序开始时间为上一生产单第一工序完成时间。
            // 一个生产单中的每个工序生产量会按数量等分，
            // 第一个工序的第一等分生产量完成时间，则是后面工序的开始时间，依次类推
            // 排到休息日、停滞时间、前置时间（提前生产时间 map_ppi_aheadtime）、外发时间（外包出去工序的完成时间），则累加进去。
            // 工序生产分为主、辅流程生产。辅流程工序开始时间为上一生产单第一工序的第一等分生产量完成时间。
            foreach ($tmpProdOrdersInfo as $k => $v) {
                if ($v['prdoid'] == $orderItem['id']) {
                    #由于数据有问题，取 ppi_po_qty，ppi_expected_qty 中的其中一个值，以 ppi_po_qty 优先
                    $prdTotal        = $tmpProdOrdersInfo[$k]['ppi_po_qty'] ?: $tmpProdOrdersInfo[$k]['ppi_expected_qty'];
                    $costTime        = 0;

                    switch ($schdMode) {
                        case 'SELF_COST':
                            // 采用自身耗时
                            $costTime        = $orderItem['map_ppi_cost_time'] > 0 ? $orderItem['map_ppi_cost_time'] : 0;
                            $singlePhaseNeed = $splitCount * $costTime;
                            $allPhaseNeed    = $prdTotal * $costTime;
                            break;
                        case 'MAX_COST':
                            // 工序耗时为整张生产单中最大工序耗时
                            $costTime        = $orderItem['map_ppi_cost_time'] > 0 ? $maxPhsCost[$k] : 0;
                            $singlePhaseNeed = $splitCount * $costTime;
                            $allPhaseNeed    = $prdTotal * $costTime;
                            break;
                        default:
                            // 工序耗时为整张生产单中最大工序耗时
                            $costTime        = $orderItem['map_ppi_cost_time'] > 0 ? $maxPhsCost[$k] : 0;
                            $singlePhaseNeed = $splitCount * $costTime;
                            $allPhaseNeed    = $prdTotal * $costTime;
                    }

                    #是否为第一工序
                    $isFirstPhase    = false;

                    // 生产单的第一道工序
                    if (!isset($tmpProdOrdersInfo[$k]['phases'])) {
                        // 不是生产单列表第一单时，使开始时间为上一生产单第一主工序（有耗时）等分生产量的完成时间。辅流程逻辑相同
                        if ($k !== 0) {
                            $prevPrdOrderPhs = $tmpProdOrdersInfo[$k - 1]['phases'];
                            foreach ($prevPrdOrderPhs as $key => $value) {
                                if ($value['map_ppi_cost_time'] > 0 && $value['map_ppi_isvice'] === '0') {
                                    $phaseStartAt    = strtotime($value['ppi_phs_complete']);
                                    break;
                                }
                            }
                            $isFirstPhase    = true;
                        }

                        // 生产单的第二道工序，假设不是副流程
                    } else if (count($tmpProdOrdersInfo[$k]['phases']) === 1) {
                        // 主辅流程处理
                        $phaseStartAt = strtotime(current($tmpProdOrdersInfo[$k]['phases'])['ppi_phs_start']) + $singlePhaseNeed;

                        // 生产单的之后的工序
                    } else if (next($tmpProdOrdersInfo[$k]['phases'])) {
                        // 主辅流程处理
                        if ($orderItem['map_ppi_isvice'] === '0') {
                            $phs = current($tmpProdOrdersInfo[$k]['phases']);
                            $phaseStartAt = strtotime($phs['ppi_phs_start']) + $singlePhaseNeed;

                            // 辅流程，使开始时间为上一生产单第一工序等分生产量的完成时间
                        } else {
                            if ($k > 0) {
                                // 车间上线工站索引
                                $i = -1;
                                foreach ($v['phases'] as $idx => $item) {
                                    if ($item['map_ppi_phsid'] === '005') {
                                        $i = $idx;
                                        break;
                                    }
                                }
                                // 如果前面工站存在车间上线，则开始时间以车间上线前一个工站计算
                                if ($i > 0) {
                                    $beforeWorkshopOnline = $v['phases'][$i - 1];
                                    if ($beforeWorkshopOnline['map_ppi_cost_time'] == 0) {
                                        $phaseStartAt    = strtotime($beforeWorkshopOnline['ppi_phs_complete']);
                                    } else {
                                        $phaseStartAt    = strtotime($beforeWorkshopOnline['ppi_phs_start']) + $singlePhaseNeed;
                                    }
                                } else {
                                    $phaseStartAt    = strtotime($tmpProdOrdersInfo[$k - 1]['phases'][0]['ppi_phs_complete']);
                                }
                            } else {
                                $phaseStartAt    = $schStartAt;
                            }
                        }
                    }

                    // 如果有停滞时间则算入排程时间
                    if ($orderItem['map_ppi_deadtime'] > 0) {
                        $phaseStartAt += $orderItem['map_ppi_deadtime'];
                    }

                    // 处理工序开始时间。加入工作日上下班休息时间
                    $phaseActualStartAt
                        = $this->handlePhaseStartTime(
                            $phaseStartAt,
                            $worktimeArr[0],
                            $isFirstPhase,
                            $arrangeDays
                        );

                    // 加入休息日，排到休息日则向后算入一天
                    foreach ($arrangeDays as $v) {
                        if (
                            current($arrangeDays) &&
                            current($arrangeDays)['ppi_cald_date']
                            === date(self::WORK_DATE_FORMAT, $phaseActualStartAt)
                        ) {
                            if (current($arrangeDays)['ppi_cald_is_rest'] === 1) {
                                $phaseActualStartAt += self::SECONDS_OF_DAY;
                                next($arrangeDays);
                            }
                        } else {
                            break;
                        }
                    }

                    // 工序等分生产量完成时间
                    if ($orderItem['map_ppi_outime'] > 0) { // 如果有外发时间则以外发时间计算
                        $phaseCompleteAt = $phaseActualStartAt + $orderItem['map_ppi_outime'];
                    } else { //没有外发时间，则以耗时计算
                        $phaseCompleteAt = $phaseActualStartAt + $allPhaseNeed;
                    }

                    // 处理工序完成时间。加入工作日上下班休息时间
                    $phaseActualCompleteAt =
                        $this->handlePhaseCompleteTime(
                            $phaseActualStartAt,
                            $phaseCompleteAt,
                            $worktimeArr[0],
                            $arrangeDays
                        );

                    foreach ($arrangeDays as $arrangeDay) {
                        if (
                            strtotime($arrangeDay['ppi_cald_date']) <
                            $phaseActualCompleteAt &&
                            $arrangeDay['ppi_cald_is_rest'] === 1
                        ) {
                            $phaseActualCompleteAt
                                += self::SECONDS_OF_DAY;
                        }
                    }

                    $append = [
                        'map_ppi_phsid'     => $orderItem['map_ppi_phsid'],
                        'map_ppi_phs'       => $orderItem['map_ppi_phs'] ?: $orderItem['map_ppi_phs_desc'],
                        'map_ppi_seq'       => $orderItem['map_ppi_seq'],
                        'map_ppi_phs_desc'  => $orderItem['map_ppi_phs_desc'],
                        'map_ppi_cost_time' => $orderItem['map_ppi_cost_time'],
                        'map_ppi_aheadtime' => $orderItem['map_ppi_aheadtime'],
                        'map_ppi_deadtime'  => $orderItem['map_ppi_deadtime'],
                        'map_ppi_outime'    => $orderItem['map_ppi_outime'],
                        'map_ppi_isvice'    => $orderItem['map_ppi_isvice'],
                        'map_ppi_isdirty'   => $orderItem['map_ppi_isdirty'],
                        'ppi_phs_start'     => date(self::WORK_DATETIME_FORMAT, $phaseActualStartAt),
                        'ppi_phs_complete'  => date(self::WORK_DATETIME_FORMAT, $phaseActualCompleteAt)
                    ];

                    if ($orderItem['map_ppi_isvice'] === '0') {
                        $tmpProdOrdersInfo[$k]['phases'][] = $append;
                    } else {
                        // 车间上线工站索引，副流程都排在车间上线之前
                        $workshopOnline = false;
                        $tmp = [];
                        // 获取副流程移动的步数
                        $steps = 0;
                        foreach ($tmpProdOrdersInfo[$k]['phases'] as $idx => $value) {
                            if ($value['map_ppi_phsid'] === current($tmpProdOrdersInfo[$k]['phases'])['map_ppi_phsid']) {
                                $steps = $idx;
                            }
                            if ($value['map_ppi_phsid'] === '005') {
                                $steps = abs($steps - $idx) - 2;
                                $workshopOnline = true;
                                $tmp[] = $append;
                            }
                            $tmp[] = $value;
                        }
                        if ($workshopOnline) {
                            // 这里变更了内部的 prev, next 指针
                            $tmpProdOrdersInfo[$k]['phases'] = $tmp;
                            // 还原指针位置
                            while ($steps > 0) {
                                $steps--;
                                next($tmpProdOrdersInfo[$k]['phases']);
                            }
                            unset($tmp);
                        } else {
                            array_unshift($tmpProdOrdersInfo[$k]['phases'], $append);
                        }
                    }
                }
            }
        }

        $prodOrdersInfo[] = end($tmpProdOrdersInfo);
        $m = new ModelProd;
        $isExistedInDb = $m->schdRecords($year, $month, $prodLine);
        $result = true;
        if (count($isExistedInDb) < 1) {
            $schdRecords = [];
            foreach ($prodOrdersInfo as $v) {
                foreach ($v['phases'] as $phsInfo) {
                    $schdRecords[] = [
                        'ppa_prdo_id'           => $v['prdoid'],
                        'ppa_phs_id'            => $phsInfo['map_ppi_phsid'],
                        'ppa_phs'               => $phsInfo['map_ppi_phs'],
                        'ppa_phs_start'         => $phsInfo['ppi_phs_start'],
                        'ppa_phs_complete'      => $phsInfo['ppi_phs_complete'],
                        'ppa_prdo_customerno'   => $v['ppi_customer_no'],
                        'ppa_prdo_customerpono' => $v['ppi_customer_pono'],
                        'ppa_prdo_item'         => $v['ppi_prd_item'],
                    ];
                }
            }
            $result = $m->insertSchdRecords($schdRecords);
        }
        $result = $result && $m->syncPlanindate();
        $rtn['result'] = $result;
        if ($result) {
            // Cache::store('redis')->set($cacheKey, serialize($prodOrdersInfo));
            $rtn['data']   = $prodOrdersInfo;
        }

        return json($rtn);
    }

    /**
     * @description: 获取自动排程所需要的数据：生产单，行事历，生产工序信息（耗时）
     */
    public function getAutoSchdParam(): array
    {
        $date               = input('post.date');
        $prodLine           = input('post.prodLine');
        $schdMode           = input('post.schdMode');
        list($year, $month) = explode('-', $date);

        // 生产订单
        $prodOrdersList     = (new ModelProd())->prodOrders($prodLine, $year, $month);
        if (count($prodOrdersList) === 0) {
            return [
                'prodOrdersList' => $prodOrdersList
            ];
        }

        // 行事历
        $arrangeDays        = (new ModelProd())->calendar($year, $month, 1, 1);
        foreach ($arrangeDays as $k => $v) {
            if ($v['ppi_cald_profile']) {
                $profile = json_decode($v['ppi_cald_profile'], true);
                if (isset($profile['ppi_workday_time_range'])) {
                    $workdayTimeRange = explode(' | ', $profile['ppi_workday_time_range']);

                    try {
                        list($morning, $afternoon, $evening) = $workdayTimeRange;
                    } catch (\Throwable $th) {
                        $offset = substr($th->getMessage(), -1);
                        if ($offset == '1') {
                            $afternoon = null;
                            $evening   = null;
                        } else if ($offset == '2') {
                            $evening = null;
                        }
                    }
                    $arrangeDays[$k]['morning'] = $morning;
                    $arrangeDays[$k]['afternoon'] = $afternoon;
                    $arrangeDays[$k]['evening'] = $evening;
                }
            }
        }

        // 获取生产排程参数
        $schdParams = (new ModelProd())->prdSchdParam();

        return [
            'year'           => $year,
            'month'          => $month,
            'prodLine'       => $prodLine,
            'prodOrdersList' => $prodOrdersList,
            'arrangeDays'    => $arrangeDays,
            'schdParams'     => $schdParams,
            'schdMode'       => $schdMode
        ];
    }

    /**
     * @description: 自动排程生产报表
     */
    public function getPrdSchdReport()
    {
        $date = input('post.date');
        $data = input('post.data');

        $html  = '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body {
                    font-family: simkai;
                }

                .phs {
                    text-align: center;
                    border: 1px solid black;
                }
            </style>
        </head>
        <body>
        <center><h1>' . $date . '生产排程报表</h1></center>';

        foreach ($data as $v) {
            $html .= '<table cellspacing="0" cellpadding="0" width="50%">
            <tr>
                <td>客户代号：' . $v['ppi_customer_no'] . '</td>
                <td>客户订单号：' . $v['ppi_customer_pono'] . '</td>
                <td>工厂品号：' . $v['ppi_prd_item'] . '</td>
                <td>订单数量：' . ($v['ppi_po_qty'] ?: $v['ppi_expected_qty']) . '</td>
            </tr>
        </table>';
            $html .= '<table cellspacing="0" cellpadding="0" width="100%" style="margin-bottom: 6px;">
                        <tr>';
            $row1 = '';
            $row2 = '';
            $row3 = '';
            foreach ($v['phases'] as $phs) {
                $row1 .= '<td class="phs">' . $phs['map_ppi_phs'] . ($phs['map_ppi_isvice'] === '1' ? '·副' : '') . '</td>';
                $row2 .= '<td class="phs">' . $phs['ppi_phs_start'] . '</td>';
                $row3 .= '<td class="phs">' . $phs['ppi_phs_complete'] . '</td>';
            }
            $html .= $row1 . '</tr><tr>' . $row2 . '</tr><tr>' . $row3;
            $html .= '
                        </tr>
                    </table>';
        }

        $html .= '</body></html>';

        $options = new Options();
        $options->set('enable_remote', true);
        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A3', 'landscape');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();
        $dompdf->stream("sample.pdf", ["Attachment" => 0]);
    }

    /**
     * @description: 获取生产排程参数，如上下班时间、工序生产每批批次次数量
     */
    public function getPrdSchdParam()
    {
        $res = (new ModelProd())->prdSchdParam();

        $rtn['result'] = true;
        $rtn['data'] = $res;

        return json($rtn);
    }

    public function saveCalenderOpt()
    {
        $opt = input('post.opt');

        $res = (new ModelProd())->calendarOpt($opt);
        $rtn['result'] = $res;

        return json($rtn);
    }

    /**
     * @description: 设置上下班时间
     */
    public function setWorktime()
    {
        $timestr = input('post.timestr');

        $res = (new ModelProd())->setWorktime($timestr);
        $rtn['result'] = $res;

        return json($rtn);
    }
}

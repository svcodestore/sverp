<?php
/*
* @Author: yanbuw1911
* @Date: 2021-05-20 09:49:01
 * @LastEditors: yanbuw1911
 * @LastEditTime: 2021-05-24 17:02:34
* @Description: Do not edit
 * @FilePath: /sverp/app/algorithm/ProdAutoSchd.php
*/

declare(strict_types=1);

namespace app\algorithm;

class ProdAutoSchd
{
    private const DAY_SECONDS      = 86400;
    private const DATETIME_FORMAT  = 'Y-m-d H:i:s';
    private const DATE_FORMAT      = 'Y-m-d';
    private $arrangeDaysPointSteps = 0;
    public $prodList               = [];

    // 中午休息时间
    private  $morningWorkRest      = 0,
        // 下午休息时间
        $afternoonWorkRest             = 0,
        $prodOrdersList                = [],
        $maxPhsCost,
        $year,
        $month,
        $prodLine,
        $arrangeDays,
        $schdMode,
        $splitCount,
        $worktimeStr,
        $firstWorkdayOfMonth,
        $schStartAt,
        $morningWorktimeStart,
        $morningWorktimeStop,
        $afternoonWorktimeStart,
        $afternoonWorktimeStop,
        $eveningWorktimeStart,
        $eveningWorktimeStop,
        $morningWorktime,
        $afternoonWorktime,
        $eveningWorktime;


    public function __construct(array $params)
    {
        $this->prodOrdersList = $params['prodOrdersList'];

        $this->year           = $params['year'];
        $this->month          = $params['month'];
        $this->prodLine       = $params['prodLine'];
        $this->arrangeDays    = $params['arrangeDays'];
        $this->schdMode       = $params['schdMode'];

        $schdParams     = $params['schdParams'];
        foreach ($schdParams as $schdParam) {
            if ($schdParam['ppi_extra_key'] === 'ppi_bisection_cnt') {
                // 工序生产量等分数
                $this->splitCount = $schdParam['ppi_extra_value'];
            }

            if ($schdParam['ppi_extra_key'] === 'ppi_workday_time_range') {
                // 每天的上下班时间，字符串
                $this->worktimeStr = $schdParam['ppi_extra_value'];
            }
        }

        $this->setTimeParam();

        $this->setMaxPhaseCost();

        $this->setProdList();

        $this->adjustPhasePosition(true);
    }

    private function setTimeParam()
    {
        preg_match_all('/[\d:]{8}/', $this->worktimeStr, $worktimeArr);
        $this->worktime = $worktimeArr[0];
        list(
            $this->morningWorktimeStart,
            $this->morningWorktimeStop,
            $this->afternoonWorktimeStart,
            $this->afternoonWorktimeStop,
            $this->eveningWorktimeStart,
            $this->eveningWorktimeStop
        ) = $this->worktime;

        // 上午上班时长
        if ($this->morningWorktimeStart && $this->morningWorktimeStop) {
            $this->morningWorktime = strtotime($this->morningWorktimeStop) - strtotime($this->morningWorktimeStart);
        }
        // 下午上班时长
        if ($this->afternoonWorktimeStart && $this->afternoonWorktimeStop) {
            $this->afternoonWorktime = strtotime($this->afternoonWorktimeStop) - strtotime($this->afternoonWorktimeStart);
        }
        // 晚上上班时长
        if ($this->eveningWorktimeStart && $this->eveningWorktimeStop) {
            $this->eveningWorktime = strtotime($this->eveningWorktimeStop) - strtotime($this->eveningWorktimeStart);
        }
        // 计算中午休息时间
        if ($this->afternoonWorktimeStart && $this->morningWorktimeStop) {
            $this->morningWorkRest = strtotime($this->afternoonWorktimeStart)  - strtotime($this->morningWorktimeStop);
        }
        // 计算下午休息时间
        if ($this->eveningWorktimeStart && $this->afternoonWorktimeStop) {
            $this->afternoonWorkRest = strtotime($this->eveningWorktimeStart)  - strtotime($this->afternoonWorktimeStop);
        }

        $this->firstWorkdayOfMonth = "{$this->year}-{$this->month}-01";
        // 算入行事历
        foreach ($this->arrangeDays as $v) {
            $currArrange = current($this->arrangeDays);
            if ($this->firstWorkdayOfMonth === $currArrange['ppi_cald_date']) {
                // 月初是休息日则下一非休息天开始排程
                if ($currArrange['ppi_cald_is_rest'] === 1) {
                    $this->firstWorkdayOfMonth = date(
                        self::DATE_FORMAT,
                        strtotime(
                            '1 day',
                            strtotime($this->firstWorkdayOfMonth)
                        )
                    );
                    array_shift($this->arrangeDays);
                    // 不是休息日，按设定的上下班时间排程
                } else {
                    if (isset($currArrange['morning'])) {
                        $this->morningWorktimeStart = explode(' - ', $currArrange['morning'])[0];
                    }
                }
            } else {
                break;
            }
        }

        // 排程开始日期
        $this->schStartAt     = strtotime("$this->firstWorkdayOfMonth $this->morningWorktimeStart");
    }

    private function setMaxPhaseCost()
    {
        $phsCost = [];
        foreach ($this->prodOrdersList as $orderItem) {
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

            foreach ($phsCost as $k => $v) {
                if ($v['id'] == $orderItem['id']) {
                    $phsCost[$k]['cost'][] = $orderItem['map_ppi_cost_time'];
                }
            }
        }
        $this->maxPhsCost = array_map(fn ($e) => max($e['cost']), $phsCost);

        return $this->maxPhsCost;
    }

    /**
     * 重新组织生产单信息，将每一款款号的工序存于生产单的 phases 键中
     */
    private function setProdList()
    {
        $list = [];
        $tmpList = [];
        foreach ($this->prodOrdersList as $orderItem) {
            $isExisted = false;
            foreach ($tmpList as $orderInfo) {
                if ($orderInfo['prdoid'] == $orderItem['id']) {
                    $isExisted = true;
                    break;
                }
            }

            // 不存在则插入数据
            if (!$isExisted) {
                $arr = [
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

                // 由于只要上一个工站的完成时间，控制遍历的数组为2个元素，可以理解为滑动窗口
                if (count($tmpList) > 1) {
                    $list[] = array_shift($tmpList);
                }
                // 这里的 count($this->maxPhsCost) 代表全部生产单数量的意思
                if (count($list) + count($tmpList) === count($this->maxPhsCost) - 1) {
                    $list[] = end($tmpList);
                }
                array_push($tmpList, $arr);
            }

            foreach ($tmpList as $k => $orderInfo) {
                if ($orderInfo['prdoid'] == $orderItem['id']) {
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
                        'map_ppi_isdirty'   => $orderItem['map_ppi_isdirty']
                    ];

                    $tmpList[$k]['phases'][] = $append;
                }
            }
        }
        $list[] = end($tmpList);

        $this->prodList = $list;
    }

    /**
     *  调整工站位置，副流程工站在车间上线和皮企入库之间
     */
    private function adjustPhasePosition(bool $isSchedule = false)
    {
        foreach ($this->prodList as $key => $orderItem) {
            $beforeOnlineWorkshop = [];
            $afterOnlineWorkshop = [];
            foreach ($orderItem['phases'] as $p) {
                if ($p['map_ppi_phsid'] === '005') {
                    array_push(
                        $beforeOnlineWorkshop,
                        ...array_filter(
                            $orderItem['phases'],
                            fn ($e) => $e['map_ppi_isvice'] === '1'
                        )
                    );

                    $beforeOnlineWorkshop[] = $p;
                } else if ($p['map_ppi_isvice'] === '0') {
                    if ($p['map_ppi_phsid'] < 5) {
                        $beforeOnlineWorkshop[] = $p;
                    } else {
                        $afterOnlineWorkshop[] = $p;
                    }
                }
            }

            // 调整工站顺序一并计算工序开始时间和完成时间
            if ($isSchedule) {
                $this->schedule($key, $beforeOnlineWorkshop, $afterOnlineWorkshop);
            }

            $this->prodList[$key]['phases'] = array_merge($beforeOnlineWorkshop, $afterOnlineWorkshop);
            unset($phases);
        }
    }

    /**
     * @description: 自动排程算法，通过生产单，行事历，生产工序信息（耗时）三个表中的数据来计算
     */
    public function schedule(int $phsSeq, array &$beforeOnlineWorkshop, array &$afterOnlineWorkshop)
    {
        $needtime = function (int $total, int $single) use ($phsSeq): array {
            switch ($this->schdMode) {
                case 'SELF_COST':
                    // 采用自身耗时
                    $costTime        = $single > 0 ? $single : 0;
                    $singlePhaseNeed = $this->splitCount * $costTime;
                    $allPhaseNeed    = $total * $costTime;
                    break;
                case 'MAX_COST':
                    // 工序耗时为整张生产单中最大工序耗时
                    $costTime        = $single > 0 ? $this->maxPhsCost[$phsSeq] : 0;
                    $singlePhaseNeed = $this->splitCount * $costTime;
                    $allPhaseNeed    = $total * $costTime;
                    break;
                default:
                    // 工序耗时为整张生产单中最大工序耗时
                    $costTime        = $single > 0 ? $this->maxPhsCost[$phsSeq] : 0;
                    $singlePhaseNeed = $this->splitCount * $costTime;
                    $allPhaseNeed    = $total * $costTime;
            }

            return [(int)$allPhaseNeed, (int)$singlePhaseNeed];
        };
        $workshopOnline = function (array $phases): array {
            foreach ($phases as $p) {
                if ($p['map_ppi_phsid'] === '005') {
                    return $p;
                }
            }

            return [];
        };

        #由于数据有问题，取 ppi_po_qty，ppi_expected_qty 中的其中一个值，以 ppi_po_qty 优先
        $prdTotal        =
            (int)($this->prodList[$phsSeq]['ppi_po_qty']
                ?: $this->prodList[$phsSeq]['ppi_expected_qty']);

        if ($phsSeq === 0) {
            foreach ($beforeOnlineWorkshop as $k => $p) {
                list(
                    $allPhaseNeed,
                    $singlePhaseNeed
                ) = $needtime(
                    $prdTotal,
                    (int)$p['map_ppi_cost_time']
                );
                if ($k === 0) {
                    $start = $this->schStartAt;
                } else {
                    $start = strtotime(
                        $beforeOnlineWorkshop[$k - 1]['ppi_phs_complete']
                    );
                }
                $actualStart = $this->handlePhaseStartTime(
                    $start
                );
                $beforeOnlineWorkshop[$k]['ppi_phs_start'] =
                    date(
                        self::DATETIME_FORMAT,
                        $actualStart
                    );
                if ((int)$p['map_ppi_outime'] > 0) {
                    $complete =
                        $start
                        + (int)$p['map_ppi_outime'];
                } else {
                    $complete =
                        $start
                        + $allPhaseNeed
                        + (int)$p['map_ppi_deadtime']
                        + (int)$p['map_ppi_aheadtime'];
                }
                $actualComplete =
                    $this->handlePhaseCompleteTime(
                        $start,
                        $complete
                    );
                $beforeOnlineWorkshop[$k]['ppi_phs_complete']
                    =  date(
                        self::DATETIME_FORMAT,
                        $actualComplete
                    );
            }

            foreach ($afterOnlineWorkshop as $k => $p) {
                list($allPhaseNeed, $singlePhaseNeed)
                    = $needtime(
                        $prdTotal,
                        (int)$p['map_ppi_cost_time']
                    );
                if ($k === 0) {
                    $e = count($beforeOnlineWorkshop) - 1;
                    $start = strtotime($beforeOnlineWorkshop[$e]['ppi_phs_complete']);
                } else {
                    $start =
                        strtotime($afterOnlineWorkshop[$k - 1]['ppi_phs_start']) +
                        $singlePhaseNeed;
                }
                $actualStart = $this->handlePhaseStartTime($start);
                $afterOnlineWorkshop[$k]['ppi_phs_start'] = date(
                    self::DATETIME_FORMAT,
                    $actualStart
                );
                if ((int)$p['map_ppi_outime'] > 0) {
                    $complete
                        = $start
                        + (int)$p['map_ppi_outime'];
                } else {
                    $complete
                        = $start
                        + $allPhaseNeed
                        + (int)$p['map_ppi_deadtime']
                        + (int)$p['map_ppi_aheadtime'];
                }
                $actualComplete =
                    $this->handlePhaseCompleteTime(
                        $start,
                        $complete
                    );
                $afterOnlineWorkshop[$k]['ppi_phs_complete'] = date(
                    self::DATETIME_FORMAT,
                    $actualComplete
                );
            }
        } else {

            $index =
                count($beforeOnlineWorkshop) - 1;
            while (isset($beforeOnlineWorkshop[$index])) {
                $p = $beforeOnlineWorkshop[$index];
                list($allPhaseNeed, $singlePhaseNeed)
                    = $needtime(
                        $prdTotal,
                        (int)$p['map_ppi_cost_time']
                    );
                if ($p['map_ppi_phsid'] === '005') {
                    $start =
                        strtotime(
                            $workshopOnline(
                                $this->prodList[$phsSeq - 1]['phases']
                            )['ppi_phs_complete']
                        );
                } else {
                    if ((int)$p['map_ppi_outime'] > 0) {
                        $start =
                            strtotime(
                                $beforeOnlineWorkshop[$index + 1]['ppi_phs_start']
                            )
                            - (int)$p['map_ppi_outime'];
                    } else {
                        $start = strtotime(
                            $beforeOnlineWorkshop[$index
                                + 1]['ppi_phs_start']
                        )
                            - $allPhaseNeed
                            - (int)$p['map_ppi_deadtime']
                            - (int)$p['map_ppi_aheadtime'];
                    }
                }
                $actualStart = $this->handlePhaseStartTimeReverse(
                    $start
                );
                $beforeOnlineWorkshop[$index]['ppi_phs_start'] =
                    date(
                        self::DATETIME_FORMAT,
                        $actualStart
                    );
                if ((int)$p['map_ppi_outime'] > 0) {
                    $complete = $start
                        + (int)$p['map_ppi_outime'];
                } else {
                    $complete = $start
                        + $allPhaseNeed
                        + (int)$p['map_ppi_deadtime']
                        + (int)$p['map_ppi_aheadtime'];
                }
                $actualComplete
                    = $this->handlePhaseCompleteTime(
                        $start,
                        $complete
                    );
                $beforeOnlineWorkshop[$index]['ppi_phs_complete']
                    = date(
                        self::DATETIME_FORMAT,
                        $actualComplete
                    );
                $index--;
            }

            foreach ($afterOnlineWorkshop as $k => $p) {
                list(
                    $allPhaseNeed,
                    $singlePhaseNeed
                ) = $needtime(
                    $prdTotal,
                    (int)$p['map_ppi_cost_time']
                );
                if ($k === 0) {
                    $start = strtotime(
                        $workshopOnline(
                            $this->prodList[$phsSeq - 1]['phases']
                        )['ppi_phs_complete']
                    );
                } else {
                    $start =
                        strtotime(
                            $afterOnlineWorkshop[$k - 1]['ppi_phs_start']
                        )
                        + $singlePhaseNeed;
                }
                $actualStart = $this->handlePhaseStartTime($start);
                $afterOnlineWorkshop[$k]['ppi_phs_start'] =
                    date(
                        self::DATETIME_FORMAT,
                        $actualStart
                    );
                if ((int)$p['map_ppi_outime'] > 0) {
                    $complete =
                        $start
                        + (int)$p['map_ppi_outime'];
                } else {
                    $complete =
                        $start
                        + $allPhaseNeed
                        + (int)$p['map_ppi_deadtime']
                        + (int)$p['map_ppi_aheadtime'];
                }
                $actualComplete =
                    $this->handlePhaseCompleteTime(
                        $start,
                        $complete
                    );
                $afterOnlineWorkshop[$k]['ppi_phs_complete'] =
                    date(
                        self::DATETIME_FORMAT,
                        $actualComplete
                    );
            }
        }
    }

    private function arrangeDaysCompute($date)
    {
        // 算入行事历设定
        $currArrange = current($this->arrangeDays);
        if ($currArrange && $currArrange['ppi_cald_is_rest'] === 0) {
            if ($date === $currArrange['ppi_cald_date']) {
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
            $morningWorkRest = strtotime($afternoonWorktimeStart)  - strtotime($morningWorktimeStop);
        }
        // 计算下午休息时间
        if ($eveningWorktimeStart && $afternoonWorktimeStop) {
            $afternoonWorkRest = strtotime($eveningWorktimeStart)  - strtotime($afternoonWorktimeStop);
        }
    }

    /**
     * 处理工序开始时间。加入工作日上下班休息时间往后推
     * @param  int $computeStartAt 工序开始时间，时间戳。未算入算入工作日上下班休息时间。
     * @return int $phaseActualStartAt 工序完成时间，时间戳。已算入工作日上下班休息时间。
     * @description 开始时间超过下班时间点就算对应的休息时间，相邻的工序生产开始时间这里假设不超过一天
     * @access private
     */
    private function handlePhaseStartTime(int &$computeStartAt): int
    {
        $computeStartDate          = date(self::DATE_FORMAT, $computeStartAt);
        $morningWorkDatetimeStart  = strtotime("$computeStartDate $this->morningWorktimeStart");
        $morningWorkDatetimeStop   = strtotime("$computeStartDate $this->morningWorktimeStop");
        $afternoonWorkDatetimeStop = strtotime("$computeStartDate $this->afternoonWorktimeStop");
        $eveningWorkDatetimeStop   = strtotime("$computeStartDate $this->eveningWorktimeStop");

        $phaseActualStartAt = $computeStartAt;
        if (
            $phaseActualStartAt > $morningWorkDatetimeStop &&
            $phaseActualStartAt - $morningWorkDatetimeStop < $this->morningWorkRest
        ) {
            $phaseActualStartAt += $this->morningWorkRest;
        }
        if (
            $phaseActualStartAt > $afternoonWorkDatetimeStop &&
            $phaseActualStartAt - $afternoonWorkDatetimeStop < $this->afternoonWorkRest
        ) {
            $phaseActualStartAt += $this->afternoonWorkRest;
        }

        if ($phaseActualStartAt > $eveningWorkDatetimeStop) {
            $next = $morningWorkDatetimeStart + ($phaseActualStartAt - $eveningWorkDatetimeStop) + self::DAY_SECONDS;
            // 排到第二天，小于 4 小时
            if ($phaseActualStartAt - $eveningWorkDatetimeStop < $morningWorkDatetimeStop - $morningWorkDatetimeStart) {
                $phaseActualStartAt = $next;
            } else {
                $phaseActualStartAt = $this->handlePhaseStartTime($next);
            }
        }

        return $phaseActualStartAt;
    }



    /**
     * 处理工序开始时间。加入工作日上下班休息时间往前推
     * @param  int $computeStartAt 工序开始时间，时间戳。未算入算入工作日上下班休息时间。
     * @return int $phaseActualStartAt 工序完成时间，时间戳。已算入工作日上下班休息时间。
     * @description 开始时间超过下班时间点就算对应的休息时间，相邻的工序生产开始时间这里假设不超过一天
     * @access private
     */
    private function handlePhaseStartTimeReverse(int &$computeStartAt): int
    {
        $computeStartDate          = date(self::DATE_FORMAT, $computeStartAt);
        $morningWorkDatetimeStart  = strtotime("$computeStartDate $this->morningWorktimeStart");
        $morningWorkDatetimeStop   = strtotime("$computeStartDate $this->morningWorktimeStop");
        $afternoonWorkDatetimeStart = strtotime("$computeStartDate $this->afternoonWorktimeStart");
        $afternoonWorkDatetimeStop = strtotime("$computeStartDate $this->afternoonWorktimeStop");
        $eveningWorkDatetimeStart   = strtotime("$computeStartDate $this->eveningWorktimeStart");
        $eveningWorkDatetimeStop   = strtotime("$computeStartDate $this->eveningWorktimeStop");

        $phaseActualStartAt = $computeStartAt;
        if ($phaseActualStartAt < $eveningWorkDatetimeStart && $phaseActualStartAt > $afternoonWorkDatetimeStop) {
            $phaseActualStartAt -= $this->afternoonWorkRest;
        }
        if ($phaseActualStartAt < $afternoonWorkDatetimeStart && $phaseActualStartAt > $morningWorkDatetimeStop) {
            $phaseActualStartAt -= $this->morningWorkRest;
        }
        if ($phaseActualStartAt < $morningWorkDatetimeStart && $phaseActualStartAt > $eveningWorkDatetimeStop - self::DAY_SECONDS) {
            $phaseActualStartAt = $eveningWorkDatetimeStop - self::DAY_SECONDS - ($morningWorkDatetimeStart - $phaseActualStartAt);
            $phaseActualStartAt = $this->handlePhaseStartTimeReverse($phaseActualStartAt);
        }

        return $phaseActualStartAt;
    }


    /**
     * 处理工序完成时间。加入工作日上下班休息时间
     * @param  int $computeStartAt 工序开始时间，时间戳。已算入工作日上下班休息时间。
     * @param  int &$phaseCompleteAt 工序完成时间，时间戳。未算入工作日上下班休息时间。
     * @return int $phaseActualCompleteAt 工序完成时间，时间戳。已算入工作日上下班休息时间。
     * @description 函数内自身递归调用自身。通过工序开始、完成时间是否在同一天进行计算
     * @access private
     */
    public function handlePhaseCompleteTime(int $computeStartAt, int &$phaseCompleteAt): int
    {

        $startDate                  = date(self::DATE_FORMAT, $computeStartAt);
        $completeDate               = date(self::DATE_FORMAT, $phaseCompleteAt);
        $morningWorkDatetimeStart   = strtotime("$startDate $this->morningWorktimeStart");
        $morningWorkDatetimeStop    = strtotime("$startDate $this->morningWorktimeStop");
        $afternoonWorkDatetimeStart = strtotime("$startDate $this->afternoonWorktimeStart");
        $afternoonWorkDatetimeStop  = strtotime("$startDate $this->afternoonWorktimeStop");
        $eveningWorkDatetimeStart   = strtotime("$startDate $this->eveningWorktimeStart");
        $eveningWorkDatetimeStop    = strtotime("$startDate $this->eveningWorktimeStop");

        $phaseActualCompleteAt      = $phaseCompleteAt;
        // 如果工序在当天内完成
        // 比较完成时间是否超过下班时间点，超过则算入对应的休息时间
        // 开始完成在同一天
        if ($startDate === $completeDate) {
            if (
                $phaseActualCompleteAt > $morningWorkDatetimeStop
                && $phaseActualCompleteAt < $afternoonWorkDatetimeStart
            ) {
                $phaseActualCompleteAt += $this->morningWorkRest;
            }

            if (
                $phaseActualCompleteAt > $afternoonWorkDatetimeStop
                && $phaseActualCompleteAt < $eveningWorkDatetimeStart
            ) {
                $phaseActualCompleteAt += $this->afternoonWorkRest;
            }
            // 当完成时间在当天下班时间之后，则开始时间和完成时间各增加一天，递归计算
            if ($phaseActualCompleteAt > $eveningWorkDatetimeStop) {
                // 递归计算无效，暂时假设超出部分不会超过一天的工时 ==
                $remainTime         = $phaseActualCompleteAt - $eveningWorkDatetimeStop;

                $completeAt = strtotime('1 day', $morningWorkDatetimeStart) + $remainTime;
                $compDate     = date(self::DATE_FORMAT, $completeAt);
                $nextComputeStartDate  = date(self::DATE_FORMAT, strtotime('1 day', $morningWorkDatetimeStart));

                if ($nextComputeStartDate === $compDate) {
                    $computeStartAt = ($morningWorkDatetimeStart + self::DAY_SECONDS);
                    $phaseActualCompleteAt = $completeAt;
                } else {
                    $divisor = $this->morningWorktime + $this->afternoonWorktime + $this->eveningWorktime;
                    $remaindDays = (int)floor($remainTime / $divisor);
                    $computeStartAt = $morningWorkDatetimeStart + self::DAY_SECONDS * $remaindDays;
                    $phaseActualCompleteAt = $computeStartAt + $remainTime % $divisor;
                }
                return $this->handlePhaseCompleteTime(
                    $computeStartAt,
                    $phaseActualCompleteAt
                );
            }

            return $phaseActualCompleteAt;

            // 不在同一天，就减去当天开始到当天下班的时间，得到剩余时间进行递归计算
        } else if ($phaseCompleteAt > $computeStartAt) {
            $timeOfNextDay         = $phaseActualCompleteAt - $eveningWorkDatetimeStop;

            $phaseActualCompleteAt = strtotime('1 day', $morningWorkDatetimeStart) + $timeOfNextDay;
            $computeStartAt = ($morningWorkDatetimeStart + self::DAY_SECONDS);
            return $this->handlePhaseCompleteTime(
                $computeStartAt,
                $phaseActualCompleteAt
            );
        } else if ($phaseCompleteAt < $computeStartAt) {
            $diff = abs($phaseCompleteAt - $computeStartAt);
            $divisor = $this->morningWorktime + $this->afternoonWorktime + $this->eveningWorktime;
            if ($diff < $divisor) {
                return $this->handlePhaseCompleteTime($computeStartAt - self::DAY_SECONDS, $phaseCompleteAt);
            } else {
                return $this->handlePhaseCompleteTime($computeStartAt - self::DAY_SECONDS * (int)ceil($diff / $divisor), $phaseCompleteAt);
            }
        }
    }
}

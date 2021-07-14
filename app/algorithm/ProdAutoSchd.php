<?php
/*
* @Author: yanbuw1911
* @Date: 2021-05-20 09:49:01
 * @LastEditors: yanbuw1911
 * @LastEditTime: 2021-07-14 10:00:30
* @Description: Do not edit
 * @FilePath: /sverp/app/algorithm/ProdAutoSchd.php
*/

declare(strict_types=1);

namespace app\algorithm;

class ProdAutoSchd
{
    private const DAY_SECONDS     = 86400;
    private const DATETIME_FORMAT = 'Y-m-d H:i:s';
    private const DATE_FORMAT     = 'Y-m-d';

    // 中午休息时间
    private  $morningWorkRest     = 0,
        // 下午休息时间
        $afternoonWorkRest            = 0,
        $prodList                     = [],
        $prodOrdersList               = [],
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

        $schdParams           = $params['schdParams'];
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

    public function scheduleList(): array
    {
        return $this->prodList;
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

        $morningWorktimeStart = null;
        // 算入行事历设置
        foreach ($this->arrangeDays as $arrangeDay) {
            $currArrange = current($this->arrangeDays);
            if ($this->firstWorkdayOfMonth === $arrangeDay['ppi_cald_date']) {
                // 月初是休息日则下一非休息天开始排程
                if ($arrangeDay['ppi_cald_is_rest'] === 1) {
                    $this->firstWorkdayOfMonth = date(
                        self::DATE_FORMAT,
                        strtotime(
                            '1 day',
                            strtotime($this->firstWorkdayOfMonth)
                        )
                    );
                    // 不是休息日，按设定的上下班时间排程
                } else {
                    if (isset($currArrange['morning'])) {
                        list($morningWorktimeStart) = explode(' - ', $currArrange['morning']);
                    }
                }
            } else {
                break;
            }
        }

        $start = $morningWorktimeStart ?: $this->morningWorktimeStart;
        // 排程开始日期
        $this->schStartAt     = strtotime("$this->firstWorkdayOfMonth $start");
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
                try {
                    $this->schedule($key, $beforeOnlineWorkshop, $afterOnlineWorkshop);
                } catch (\Throwable $th) {
                    $msg = "生产单工站设定错误，未找到车间上线工站。\n";
                    $msg .= "{$this->prodList[$key]['ppi_workshop_name']} {$this->prodList[$key]['ppi_customer_no']} {$this->prodList[$key]['ppi_customer_pono']} {$this->prodList[$key]['ppi_prd_item']} 生产单工站：\n";
                    $msg .= implode("，", array_map(function ($e) {
                        return $e['map_ppi_phs'];
                    }, $this->prodList[$key]['phases']));
                    throw new \Exception($msg);
                }
            }

            $this->prodList[$key]['phases'] = array_merge($beforeOnlineWorkshop, $afterOnlineWorkshop);
            unset($phases);
        }
    }

    /**
     * @description: 自动排程算法，通过生产单，行事历，生产工序信息（耗时）三个表中的数据来计算
     */
    private function schedule(int $phsSeq, array &$beforeOnlineWorkshop, array &$afterOnlineWorkshop)
    {
        // 计算工站单等分所需时间和全部生产所需时间
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
        // 查找车间上线工站后的第一个主流程
        $firstMasterPhase = function (array $phases): array {
            foreach ($phases as $k => $p) {
                if ($p['map_ppi_phsid'] === '005') {
                    if (isset($phases[$k + 1])) {
                        return $phases[$k + 1];
                    }
                }
            }

            return [];
        };
        // 计算工站开始时间，加入休息日
        $startTimeAddArrange = function (int &$timestamp, $isReverse = false): void {
            foreach ($this->arrangeDays as $arrangeDay) {
                if (
                    date(
                        self::DATE_FORMAT,
                        $timestamp
                    )
                    === $arrangeDay['ppi_cald_date']
                    && $arrangeDay['ppi_cald_is_rest'] === 1
                ) {
                    if ($isReverse) {
                        $timestamp -= self::DAY_SECONDS;
                    } else {
                        $timestamp += self::DAY_SECONDS;
                    }
                    // 超出当月范围，要去获取其他月行事历再进行计算
                    if (date('m', $timestamp) !== date('m', strtotime($arrangeDay['ppi_cald_date']))) {
                    }
                }
            }
        };
        // 计算工站完成时间，加入休息日
        $compTimeArrange = function (int &$timestamp, int $startTime): void {
            foreach ($this->arrangeDays as $arrangeDay) {
                if (
                    strtotime(
                        $arrangeDay['ppi_cald_date']
                    ) > $startTime &&
                    strtotime(
                        $arrangeDay['ppi_cald_date']
                    ) <
                    $timestamp &&
                    $arrangeDay['ppi_cald_is_rest'] === 1
                ) {
                    $timestamp
                        += self::DAY_SECONDS;
                    // 超出当月范围，要去获取其他月行事历再进行计算
                    if (date('m', $timestamp) !== date('m', strtotime($arrangeDay['ppi_cald_date']))) {
                    }
                }
            }
        };
        // 是否在行事历时间范围内
        $isInCalendar = function (int $time): bool {
            $currDate = date(self::DATE_FORMAT, $time);

            $flag = ($time > strtotime("$currDate {$this->morningWorktimeStart}")
                && $time <  strtotime("$currDate {$this->morningWorktimeStop}"))
                || ($time > strtotime("$currDate {$this->afternoonWorktimeStart}")
                    && $time <  strtotime("$currDate {$this->afternoonWorktimeStop}"))
                || ($time > strtotime("$currDate {$this->eveningWorktimeStart}")
                    && $time <  strtotime("$currDate {$this->eveningWorktimeStop}"));

            return $flag;
        };

        #由于数据有问题，取 ppi_po_qty，ppi_expected_qty 中的其中一个值，以 ppi_po_qty 优先
        $prdTotal        =
            (int)($this->prodList[$phsSeq]['ppi_po_qty']
                ?: $this->prodList[$phsSeq]['ppi_expected_qty']);

        /**
         *  将生产单的每一单以车间上线工站为划分点，分成车间上线之前的工站，和车间上线之后的工站；
         *  车间上线之前的工站的开始时间要是上一工站的全部生产所需时间，车间上线之后的工站的开始时间是上一工站的等分生产所需时间
         *  生产单的第一单，每一个工站一个接着一个顺序生产。之后的每一单，车间上线之前的工站向前计算开始时间
         */

        // 当月生产单第一单
        if ($phsSeq === 0) {
            // 处理车间上线之前工站
            foreach ($beforeOnlineWorkshop as $k => $p) {
                list(
                    $allPhaseNeed,
                    $singlePhaseNeed
                ) = $needtime(
                    $prdTotal,
                    (int)$p['map_ppi_cost_time']
                );
                if ($k === 0) {
                    // 第一工站为当月生产开始时间
                    $start = $this->schStartAt;
                } else {
                    // 第一工站之后的工站开始时间是上一工站的完成时间
                    $start = strtotime(
                        $beforeOnlineWorkshop[$k - 1]['ppi_phs_complete']
                    );
                }
                // 有前置时间，则减去前置时间
                if ((int)$p['map_ppi_aheadtime'] > 0) {
                    $start -= (int)$p['map_ppi_aheadtime'];
                    // 减去前置时间有可能在工作时间之外或者行事历设置之外
                    $start = $this->handlePhaseStartTimeReverse($start);
                }
                // 当开始时间加入工站等分生产耗时，有可能在工作时间之外或者行事历设置之外
                $actualStart = $this->handlePhaseStartTime(
                    $start
                );
                // 算入休息日
                $startTimeAddArrange($actualStart);
                $beforeOnlineWorkshop[$k]['ppi_phs_start'] =
                    date(
                        self::DATETIME_FORMAT,
                        $actualStart
                    );
                // 如果有外发时间，完成时间则只计算外发时间
                if ((int)$p['map_ppi_outime'] > 0) {
                    $complete =
                        $start
                        + (int)$p['map_ppi_outime'];
                } else {
                    $complete =
                        $start
                        + $allPhaseNeed
                        + (int)$p['map_ppi_deadtime'];
                }
                // 有可能在工作时间之外或者行事历设置之外
                $actualComplete =
                    $this->handlePhaseCompleteTime(
                        $start,
                        $complete
                    );
                // 算入休息日
                $compTimeArrange($actualComplete, $actualStart);
                $beforeOnlineWorkshop[$k]['ppi_phs_complete']
                    =  date(
                        self::DATETIME_FORMAT,
                        $actualComplete
                    );
            }

            // 处理车间上线之后工站
            foreach ($afterOnlineWorkshop as $k => $p) {
                list($allPhaseNeed, $singlePhaseNeed)
                    = $needtime(
                        $prdTotal,
                        (int)$p['map_ppi_cost_time']
                    );
                if ($k === 0) {
                    $e = count($beforeOnlineWorkshop) - 1;
                    $start = strtotime(
                        $beforeOnlineWorkshop[$e]['ppi_phs_complete']
                    );
                } else {
                    $start =
                        strtotime(
                            $afterOnlineWorkshop[$k - 1]['ppi_phs_start']
                        )
                        + $singlePhaseNeed;
                }
                // 有前置时间，则减去前置时间
                if ((int)$p['map_ppi_aheadtime'] > 0) {
                    $start -= (int)$p['map_ppi_aheadtime'];
                    // 减去前置时间有可能在工作时间之外或者行事历设置之外
                    $start = $this->handlePhaseStartTimeReverse($start);
                }
                // 当开始时间加入工站等分生产耗时，有可能在工作时间之外或者行事历设置之外
                $actualStart = $this->handlePhaseStartTime($start);
                $startTimeAddArrange($actualStart);
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
                        + (int)$p['map_ppi_deadtime'];
                }
                $actualComplete =
                    $this->handlePhaseCompleteTime(
                        $start,
                        $complete
                    );
                $compTimeArrange($actualComplete, $actualStart);
                $afterOnlineWorkshop[$k]['ppi_phs_complete'] = date(
                    self::DATETIME_FORMAT,
                    $actualComplete
                );
            }
        } else {
            // 处理车间上线之前工站
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
                            $firstMasterPhase(
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
                $startTimeAddArrange($actualStart, true);
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
                $compTimeArrange($actualComplete, $actualStart);
                $beforeOnlineWorkshop[$index]['ppi_phs_complete']
                    = date(
                        self::DATETIME_FORMAT,
                        $actualComplete
                    );
                $index--;
            }

            // 处理车间上线之后工站
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
                        $beforeOnlineWorkshop[count($beforeOnlineWorkshop) - 1]['ppi_phs_complete']
                    );
                } else {
                    $start =
                        strtotime(
                            $afterOnlineWorkshop[$k - 1]['ppi_phs_start']
                        )
                        + $singlePhaseNeed;
                }

                // 有前置时间，则减去前置时间
                if ((int)$p['map_ppi_aheadtime'] > 0) {
                    $start -= (int)$p['map_ppi_aheadtime'];
                    // 减去前置时间有可能在工作时间之外或者行事历设置之外
                    if (
                        $isInCalendar($start)
                    ) {
                        $start = $this->handlePhaseStartTimeReverse($start);
                    }
                }
                $actualStart = $this->handlePhaseStartTime($start);
                $startTimeAddArrange($actualStart);
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
                        + (int)$p['map_ppi_deadtime'];
                }
                $actualComplete =
                    $this->handlePhaseCompleteTime(
                        $start,
                        $complete
                    );
                $compTimeArrange($actualComplete, $actualStart);
                $afterOnlineWorkshop[$k]['ppi_phs_complete'] =
                    date(
                        self::DATETIME_FORMAT,
                        $actualComplete
                    );
            }
        }
    }

    private function arrangeDaysCompute(int $timestamp): array
    {
        $isAnotherMonth         = false;

        $morningWorkRest        = null;
        $afternoonWorkRest      = null;
        $morningWorktimeStart   = null;
        $morningWorktimeStop    = null;
        $morningWorktime        = null;
        $afternoonWorktimeStart = null;
        $afternoonWorktimeStop  = null;
        $afternoonWorktime      = null;
        $eveningWorktimeStart   = null;
        $eveningWorktimeStop    = null;
        $eveningWorktime        = null;

        // 算入行事历设定
        foreach ($this->arrangeDays as $arrangeDay) {
            if (date('m', $timestamp) !== date('m', strtotime($arrangeDay['ppi_cald_date']))) {
                $isAnotherMonth = true;
                break;
            } else if ($arrangeDay['ppi_cald_is_rest'] === 0 && date(self::DATE_FORMAT, $timestamp) === $arrangeDay['ppi_cald_date']) {
                if (isset($arrangeDay['morning'])) {
                    list($morningWorktimeStart, $morningWorktimeStop) = explode(' - ', $arrangeDay['morning']);
                }
                if (isset($arrangeDay['afternoon'])) {
                    list($afternoonWorktimeStart, $afternoonWorktimeStop) = explode(' - ', $arrangeDay['afternoon']);
                }
                if (isset($arrangeDay['evening'])) {
                    list($eveningWorktimeStart, $eveningWorktimeStop) = explode(' - ', $arrangeDay['evening']);
                }
            }
        }
        // 其他月计算
        if ($isAnotherMonth) {
        }

        // 计算中午休息时间
        if ($afternoonWorktimeStart && $morningWorktimeStop) {
            $morningWorkRest = strtotime($afternoonWorktimeStart)  - strtotime($morningWorktimeStop);
        }
        // 计算下午休息时间
        if ($eveningWorktimeStart && $afternoonWorktimeStop) {
            $afternoonWorkRest = strtotime($eveningWorktimeStart)  - strtotime($afternoonWorktimeStop);
        }

        // 上午上班时长
        if ($morningWorktimeStart && $morningWorktimeStop) {
            $morningWorktime = strtotime($morningWorktimeStop) - strtotime($morningWorktimeStart);
        }
        // 下午上班时长
        if ($afternoonWorktimeStart && $afternoonWorktimeStop) {
            $afternoonWorktime = strtotime($afternoonWorktimeStop) - strtotime($afternoonWorktimeStart);
        }
        // 晚上上班时长
        if ($eveningWorktimeStart && $eveningWorktimeStop) {
            $eveningWorktime = strtotime($eveningWorktimeStop) - strtotime($eveningWorktimeStart);
        }

        return [
            $morningWorkRest,
            $afternoonWorkRest,
            $morningWorktimeStart,
            $morningWorktimeStop,
            $morningWorktime,
            $afternoonWorktimeStart,
            $afternoonWorktimeStop,
            $afternoonWorktime,
            $eveningWorktimeStart,
            $eveningWorktimeStop,
            $eveningWorktime
        ];
    }

    /**
     * 处理工序开始时间。加入工作日上下班休息时间往后推
     * @param  int $computeStartAt 工序开始时间，时间戳。未算入算入工作日上下班休息时间。
     * @return int $phaseActualStartAt 工序完成时间，时间戳。已算入工作日上下班休息时间。
     * @description 开始时间超过下班时间点就算对应的休息时间
     * @access private
     */
    private function handlePhaseStartTime(int &$computeStartAt): int
    {
        $computeStartDate          = date(self::DATE_FORMAT, $computeStartAt);

        list(
            $morningWorkRest,
            $afternoonWorkRest,
            $morningWorktimeStart,
            $morningWorktimeStop,
            $morningWorktime,
            $afternoonWorktimeStart,
            $afternoonWorktimeStop,
            $afternoonWorktime,
            $eveningWorktimeStart,
            $eveningWorktimeStop,
            $eveningWorktime
        )                          =
            $this->arrangeDaysCompute($computeStartAt);

        $morRest                   = $morningWorkRest ?: $this->morningWorkRest;
        $aftRest                   = $afternoonWorkRest ?: $this->afternoonWorkRest;

        $morStart                  = $morningWorktimeStart ?: $this->morningWorktimeStart;
        $morStop                   = $morningWorktimeStop ?: $this->morningWorktimeStop;

        $aftStop                   = $afternoonWorktimeStop ?: $this->afternoonWorktimeStop;
        $eveStop                   = $eveningWorktimeStop ?: $this->eveningWorktimeStop;

        $morningWorkDatetimeStart  = strtotime("$computeStartDate $morStart");
        $morningWorkDatetimeStop   = strtotime("$computeStartDate $morStop");

        $afternoonWorkDatetimeStop = strtotime("$computeStartDate $aftStop");
        $eveningWorkDatetimeStop   = strtotime("$computeStartDate $eveStop");

        $phaseActualStartAt        = $computeStartAt;
        if (
            $phaseActualStartAt > $morningWorkDatetimeStop &&
            $phaseActualStartAt - $morningWorkDatetimeStop < $morRest
        ) {
            $phaseActualStartAt += $morRest;
        }
        if (
            $phaseActualStartAt > $afternoonWorkDatetimeStop &&
            $phaseActualStartAt - $afternoonWorkDatetimeStop < $aftRest
        ) {
            $phaseActualStartAt += $aftRest;
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
     * @description 开始时间超过下班时间点就算对应的休息时间
     * @access private
     */
    private function handlePhaseStartTimeReverse(int &$computeStartAt): int
    {
        $computeStartDate           = date(self::DATE_FORMAT, $computeStartAt);

        list(
            $morningWorkRest,
            $afternoonWorkRest,
            $morningWorktimeStart,
            $morningWorktimeStop,
            $morningWorktime,
            $afternoonWorktimeStart,
            $afternoonWorktimeStop,
            $afternoonWorktime,
            $eveningWorktimeStart,
            $eveningWorktimeStop,
            $eveningWorktime
        )                           =
            $this->arrangeDaysCompute($computeStartAt);

        $morRest                    = $morningWorkRest ?: $this->morningWorkRest;
        $aftRest                    = $afternoonWorkRest ?: $this->afternoonWorkRest;

        $morStart                   = $morningWorktimeStart ?: $this->morningWorktimeStart;
        $morStop                    = $morningWorktimeStop ?: $this->morningWorktimeStop;
        $aftStart                   = $afternoonWorktimeStart ?: $this->afternoonWorktimeStart;
        $aftStop                    = $afternoonWorktimeStop ?: $this->afternoonWorktimeStop;
        $eveStart                   = $eveningWorktimeStart ?: $this->eveningWorktimeStart;
        $eveStop                    = $eveningWorktimeStop ?: $this->eveningWorktimeStop;

        $morningWorkDatetimeStart   = strtotime("$computeStartDate $morStart");
        $morningWorkDatetimeStop    = strtotime("$computeStartDate $morStop");
        $afternoonWorkDatetimeStart = strtotime("$computeStartDate $aftStart");
        $afternoonWorkDatetimeStop  = strtotime("$computeStartDate $aftStop");
        $eveningWorkDatetimeStart   = strtotime("$computeStartDate $eveStart");
        $eveningWorkDatetimeStop    = strtotime("$computeStartDate $eveStop");

        $phaseActualStartAt         = $computeStartAt;
        // 当在下午休息时间中，减去休息时间
        if ($phaseActualStartAt < $eveningWorkDatetimeStart && $phaseActualStartAt > $afternoonWorkDatetimeStop) {
            $phaseActualStartAt -= $aftRest;
        }
        // 当在中午休息时间中，减去休息时间
        if ($phaseActualStartAt < $afternoonWorkDatetimeStart && $phaseActualStartAt > $morningWorkDatetimeStop) {
            $phaseActualStartAt -= $morRest;
        }
        // 当在早上上班时间之前
        if ($phaseActualStartAt < $morningWorkDatetimeStart && $phaseActualStartAt > $eveningWorkDatetimeStop - self::DAY_SECONDS) {
            $phaseActualStartAt = $eveningWorkDatetimeStop - self::DAY_SECONDS - ($morningWorkDatetimeStart - $phaseActualStartAt);
            $phaseActualStartAt = $this->handlePhaseStartTimeReverse($phaseActualStartAt);
        }
        // 当在晚上下班时间之后
        if ($phaseActualStartAt > $eveningWorkDatetimeStop && $phaseActualStartAt < $morningWorkDatetimeStart + self::DAY_SECONDS) {
            $phaseActualStartAt = $eveningWorkDatetimeStop - ($phaseActualStartAt - $eveningWorkDatetimeStop);
            $phaseActualStartAt = $this->handlePhaseStartTimeReverse($phaseActualStartAt);
        }

        return $phaseActualStartAt;
    }


    /**
     * 处理工序完成时间。加入工作日上下班休息时间
     * @param  int $computeStartAt 工序开始时间，时间戳。未算入工作日上下班休息时间。
     * @param  int &$phaseCompleteAt 工序完成时间，时间戳。未算入工作日上下班休息时间。
     * @return int $phaseActualCompleteAt 工序完成时间，时间戳。已算入工作日上下班休息时间。
     * @description 函数内自身递归调用自身。通过工序开始、完成时间是否在同一天进行计算
     * @access private
     */
    public function handlePhaseCompleteTime(int $computeStartAt, int &$phaseCompleteAt): int
    {
        $startDate                  = date(self::DATE_FORMAT, $computeStartAt);
        $completeDate               = date(self::DATE_FORMAT, $phaseCompleteAt);

        list(
            $morningWorkRest,
            $afternoonWorkRest,
            $morningWorktimeStart,
            $morningWorktimeStop,
            $morningWorktime,
            $afternoonWorktimeStart,
            $afternoonWorktimeStop,
            $afternoonWorktime,
            $eveningWorktimeStart,
            $eveningWorktimeStop,
            $eveningWorktime
        )                           =
            $this->arrangeDaysCompute($phaseCompleteAt);

        $morRest                    = $morningWorkRest ?: $this->morningWorkRest;
        $aftRest                    = $afternoonWorkRest ?: $this->afternoonWorkRest;

        $morTime                    = $morningWorktime ?: $this->morningWorktime;
        $aftTime                    = $afternoonWorktime ?: $this->afternoonWorktime;
        $eveTime                    = $eveningWorktime ?: $this->eveningWorktime;

        $morStart                   = $morningWorktimeStart ?: $this->morningWorktimeStart;
        $morStop                    = $morningWorktimeStop ?: $this->morningWorktimeStop;
        $aftStart                   = $afternoonWorktimeStart ?: $this->afternoonWorktimeStart;
        $aftStop                    = $afternoonWorktimeStop ?: $this->afternoonWorktimeStop;
        $eveStart                   = $eveningWorktimeStart ?: $this->eveningWorktimeStart;
        $eveStop                    = $eveningWorktimeStop ?: $this->eveningWorktimeStop;

        $morningWorkDatetimeStart   = strtotime("$startDate $morStart");
        $morningWorkDatetimeStop    = strtotime("$startDate $morStop");
        $afternoonWorkDatetimeStart = strtotime("$startDate $aftStart");
        $afternoonWorkDatetimeStop  = strtotime("$startDate $aftStop");
        $eveningWorkDatetimeStart   = strtotime("$startDate $eveStart");
        $eveningWorkDatetimeStop    = strtotime("$startDate $eveStop");

        $phaseActualCompleteAt      = $phaseCompleteAt;
        // 如果工序在当天内完成
        // 比较完成时间是否超过下班时间点，超过则算入对应的休息时间
        // 开始完成在同一天
        if ($startDate === $completeDate) {
            if (
                $phaseActualCompleteAt > $morningWorkDatetimeStop
                && $phaseActualCompleteAt < $afternoonWorkDatetimeStart
            ) {
                $phaseActualCompleteAt += $morRest;
            }

            if (
                $phaseActualCompleteAt > $afternoonWorkDatetimeStop
                && $phaseActualCompleteAt < $eveningWorkDatetimeStart
            ) {
                $phaseActualCompleteAt += $aftRest;
            }
            // 当完成时间在当天下班时间之后，则开始时间和完成时间各增加一天，递归计算
            if ($phaseActualCompleteAt > $eveningWorkDatetimeStop) {
                // 递归计算无效，暂时假设超出部分不会超过一天的工时 ==
                $remainTime           = $phaseActualCompleteAt - $eveningWorkDatetimeStop;

                $completeAt           = strtotime('1 day', $morningWorkDatetimeStart) + $remainTime;
                $compDate             = date(self::DATE_FORMAT, $completeAt);
                $nextComputeStartDate = date(self::DATE_FORMAT, strtotime('1 day', $morningWorkDatetimeStart));

                if ($nextComputeStartDate === $compDate) {
                    $computeStartAt = ($morningWorkDatetimeStart + self::DAY_SECONDS);
                    $phaseActualCompleteAt = $completeAt;
                } else {
                    $divisor               = $morTime + $aftTime + $eveTime;
                    $remaindDays           = (int)floor($remainTime / $divisor);
                    $computeStartAt        = $morningWorkDatetimeStart + self::DAY_SECONDS * $remaindDays;
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
            $computeStartAt        = ($morningWorkDatetimeStart + self::DAY_SECONDS);
            return $this->handlePhaseCompleteTime(
                $computeStartAt,
                $phaseActualCompleteAt
            );
        } else if ($phaseCompleteAt < $computeStartAt) {
            $diff    = abs($phaseCompleteAt - $computeStartAt);
            $divisor = $morTime + $aftTime + $eveTime;
            if ($diff < $divisor) {
                return $this->handlePhaseCompleteTime($computeStartAt - self::DAY_SECONDS, $phaseCompleteAt);
            } else {
                return $this->handlePhaseCompleteTime($computeStartAt - self::DAY_SECONDS * (int)ceil($diff / $divisor), $phaseCompleteAt);
            }
        }
    }
}

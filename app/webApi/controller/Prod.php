<?php
/*
 * @Author: yanbuw1911
 * @Date: 2020-11-18 15:00:44
 * @LastEditTime: 2021-06-07 13:28:03
 * @LastEditors: yanbuw1911
 * @Description: 
 * @FilePath: \sverp\app\webApi\controller\Prod.php
 */

namespace app\webApi\controller;

use app\algorithm\ProdAutoSchd;
use app\webApi\model\Prod as ModelProd;
use Dompdf\Dompdf;
use Dompdf\Options;

class Prod
{
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

        foreach ($data as $k => $v) {
            $html .= '<table cellspacing="0" cellpadding="0" width="50%">
            <tr>
                <td>#' . ($k + 1) . '. &nbsp;' . '</td>
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
        $arrangeDays        = (new ModelProd())->calendar($year, $month, 1);

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
     * @description: 自动排程算法，通过生产单，行事历，生产工序信息（耗时）三个表中的数据来计算
     */
    public function autoSchedule()
    {
        $params    = $this->getAutoSchdParam();
        if (empty($params['prodOrdersList'])) {
            return json([
                'result' => true,
                'data' => []
            ]);
        }

        $algorithm = new ProdAutoSchd($params);

        return json([
            'result' => true,
            'data' => $algorithm->scheduleList()
        ]);
    }
}

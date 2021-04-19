<?php
namespace app\webApi\validate;

use think\Validate;
/*
 * @Author: yu chen
 * @Date: 2020-12-08 10:19:50
 * @LastEditTime: 2020-12-08 15:49:01
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: \sverp\app\webApi\validate\Recored.php
 */
class Record extends Validate
{
  protected $rule =  [
    'pro_time_end'  => 'date',
    'pro_time_star'   => 'date',
    'mechenum' => 'alphaNum',
    'repairAttr' => 'chs',

  ];
  protected $message  =  [
    'pro_time_end.date' => '结束必须是日期格式',
    'pro_time_star.date' => '开始时间必须是日期格式',
    'mechenum.alphaNum' => '机器编号必须是字母或数字',
    'repairAttr.chs' => '分类必须是中文',
  ];
}

<?php
namespace app\api\model;

use think\Model;

class SaleTime extends Model
{
    public function getSaleTime(){
        $time = time();
        $todayDate = date('Y-m-d',$time);
        $tomorrowDate = date('Y-m-d',$time+3600*24);

        $saleTime = $this->order('time asc')->select();
        if(!$saleTime){
            return array('status'=>400,'mess'=>'找不到秒杀时间段信息');
        }

        $seckillTime = array();

        foreach ($saleTime as $k2 => $v2){
            if($v2['time'] < 10){
                $startTime = strtotime($todayDate.' 0'.$v2['time'].':00:00');
            }else{
                $startTime = strtotime($todayDate.' '.$v2['time'].':00:00');
            }

            if(!empty($saleTime[$k2+1])){
                if($saleTime[$k2+1]['time'] < 10){
                    $endTime = strtotime($todayDate.' 0'.$saleTime[$k2+1]['time'].':00:00');
                }else{
                    $endTime = strtotime($todayDate.' '.$saleTime[$k2+1]['time'].':00:00');
                }
            }else{
                if($saleTime[0]['time'] < 10){
                    $endTime = strtotime($tomorrowDate.' 0'.$saleTime[0]['time'].':00:00');
                }else{
                    $endTime = strtotime($tomorrowDate.' '.$saleTime[0]['time'].':00:00');
                }
            }

            if($time >= $startTime){
                $cuxiao = 1;
            }else{
                $cuxiao = 0;
            }
            $seckillTime[] = array('time'=>$startTime,'end_time'=>$endTime,'cuxiao'=>$cuxiao,'show'=>0);
        }

        if(!$seckillTime){
            return array('status'=>400,'mess'=>'找不到秒杀时间段信息');
        }

        foreach ($seckillTime as $key => $val){
            if($time >= $val['time'] && $time < $val['end_time']){
                $seckillTime[$key]['show'] = 1;
                break;
            }
        }

        return array('status'=>200,'mess'=>'秒杀时间段','data'=>$seckillTime);
        
    }
}
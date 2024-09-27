<?php
namespace app\admin\controller;
use app\admin\controller\Basic;
use think\Db;

class Planorder extends Basic{
    
    //过期自动关闭
    public function closeorder(){
        // 启动事务
        Db::startTrans();
        try{
            $orderids = Db::name('order')->lock(true)->where('time_out','elt',time())->where('state',0)->where('fh_status',0)->where('order_status',0)->where('is_show',1)->field('id,coupon_id,user_id,shop_id')->select();
            if($orderids){
                foreach ($orderids as $v){
                    Db::name('order')->where('id',$v['id'])->update(array('order_status'=>2,'can_time'=>time()));
                
                    if($v['coupon_id']){
                        Db::name('member_coupon')->where('user_id',$v['user_id'])->where('coupon_id',$v['coupon_id'])->where('is_sy',1)->where('shop_id',$v['shop_id'])->update(array('is_sy'=>0));
                    }
                
                    $goodinfos = Db::name('order_goods')->where('order_id',$v['id'])->field('id,goods_id,goods_attr_id,goods_num,hd_type,hd_id')->select();
                    if($goodinfos){
                        foreach ($goodinfos as $val3){
                            if(in_array($val3['hd_type'],array(0,2,3))){
                                $prokc = Db::name('product')->where('goods_id',$val3['goods_id'])->where('goods_attr',$val3['goods_attr_id'])->find();
                                if($prokc){
                                    Db::name('product')->where('goods_id',$val3['goods_id'])->where('goods_attr',$val3['goods_attr_id'])->setInc('goods_number', $val3['goods_num']);
                                }
                            }elseif($val3['hd_type'] == 1){
                                $hdactivitys = Db::name('seckill')->where('id',$val3['hd_id'])->find();
                                if($hdactivitys){
                                    Db::name('seckill')->where('id',$val3['hd_id'])->setInc('stock',$val3['goods_num']);
                                    Db::name('seckill')->where('id',$val3['hd_id'])->setDec('sold',$val3['goods_num']);
                                }
                            }
                        }
                    }
                }
            }
            
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
    }
    
    //过期自动收货
    public function zdqrsh(){
        // 启动事务
        Db::startTrans();
        try{
            $orderids = Db::name('order')
                          ->lock(true)
                          ->where('zdsh_time','elt',time())
                          ->where('state',1)
                          ->where('fh_status',1)
                          ->where('order_status',0)
                          ->where('shouhou',0)
                          ->where('is_show',1)
                          ->field('id,total_price,user_id,shop_id')
                          ->select();
            if($orderids){
                foreach ($orderids as $v){
                    Db::name('order')->where('id',$v['id'])->update(array('order_status'=>1,'coll_time'=>time()));

                    $goodinfos = Db::name('order_goods')->where('order_id',$v['id'])->field('id,goods_id,goods_attr_id,goods_num,th_status,shop_id')->select();
                    if($goodinfos){
                        foreach ($goodinfos as $val2){
                            if(in_array($val2['th_status'], array(0,8))){
                                $gdinfos = Db::name('goods')->where('id',$val2['goods_id'])->field('id,sale_num,deal_num')->find();
                                if($gdinfos){
                                    $deal_num = $gdinfos['deal_num']+$val2['goods_num'];
                                    $deal_lv = sprintf("%.2f",$deal_num/$gdinfos['sale_num'])*100;
                                    Db::name('goods')->update(array('id'=>$val2['goods_id'],'deal_num'=>$deal_num,'deal_lv'=>$deal_lv));
                                }
        
                                $spinfos = Db::name('shops')->where('id',$val2['shop_id'])->field('id,sale_num,deal_num')->find();
                                if($spinfos){
                                    $shop_deal_num = $spinfos['deal_num']+$val2['goods_num'];
                                    $shop_deal_lv = sprintf("%.2f",$shop_deal_num/$spinfos['sale_num'])*100;
                                    Db::name('shops')->update(array('id'=>$val2['shop_id'],'deal_num'=>$shop_deal_num,'deal_lv'=>$shop_deal_lv));
                                }
        
                            }
                        }
                    }
                }
            }
        
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
    }
    
    
    
    //已完成订单自动给商家打款
    public function dkshop(){
        $dkorderes = Db::name('order')
                       ->where('shop_id','neq',1)
                       ->where('state',1)
                       ->where('fh_status',1)
                       ->where('order_status',1)
                       ->where('dakuan_status',0)
                       ->where('shouhou',0)
                       ->where('is_show',1)
                       ->field('id,total_price,user_id,shop_id')
                       ->select();
        if($dkorderes){
            $distributions = Db::name('distribution')->where('id',1)->find();
            if($distributions){
                foreach ($dkorderes as $v){
                    $shops = Db::name('shops')->where('id',$v['shop_id'])->field('id,indus_id,fenxiao')->find();
                    if($shops){
                        // 启动事务
                        Db::startTrans();
                        try{
                            Db::name('order')->where('id',$v['id'])->update(array('dakuan_status'=>1,'dakuan_time'=>time()));
                
                            $tui_price = 0;
                
                            $applys = Db::name('th_apply')->where('order_id',$v['id'])->where('thfw_id','in','1,2')->where('apply_status',3)->field('id,tui_price')->find();
                            if($applys){
                                $tui_price = Db::name('th_apply')->where('order_id',$v['id'])->where('thfw_id','in','1,2')->where('apply_status',3)->sum('tui_price');
                            }
                
                            $total_price = $v['total_price']-$tui_price;
							
                            if($distributions['is_open'] == 1 && $shops['fenxiao'] == 1){
                                // 分销佣金计算
                            }
                
                            $remind = Db::name('industry')->where('id',$shops['indus_id'])->value('remind');
                            if($remind){
                                $remind_lv = $remind/1000;
                                $remind_price = sprintf("%.2f",$total_price*$remind_lv);
                                $total_price = sprintf("%.2f",$total_price-$remind_price);
                            }
                            $shop_wallets = Db::name('shop_wallet')->where('shop_id',$shops['id'])->find();
                            if($shop_wallets){
                                Db::name('shop_detail')->insert(array('de_type'=>1,'sr_type'=>1,'price'=>$total_price,'order_type'=>1,'order_id'=>$v['id'],'shop_id'=>$shops['id'],'wat_id'=>$shop_wallets['id'],'time'=>time()));
                                Db::name('shop_wallet')->where('id',$shop_wallets['id'])->setInc('price',$total_price);
                            }
                
                            // 提交事务
                            Db::commit();
                        } catch (\Exception $e) {
                            // 回滚事务
                            Db::rollback();
                        }
                    }
                }
            }
        }
    }

    //秒杀拼团活动时间到期自动结束
    public function zdfinish(){
        // 启动事务
        Db::startTrans();
        try{
            $sckillIds = Db::name('seckill')->lock(true)->where('end_time','elt',time())->where('checked',1)->where('finish_status',0)->where('is_show',0)->field('id')->select();
            if($sckillIds){
                foreach ($sckillIds as $k=>$v){
                    $data = [];
                    $data['id']=$v['id'];
                    $data['finish_status'] = 1;
                    $data['finish_time'] = time();
                    Db::name('seckill')->update($data);
                }
            }

            $assembleIds = Db::name('assemble')->lock(true)->where('end_time','elt',time())->where('checked',1)->where('finish_status',0)->where('is_show',0)->field('id')->select();
            if($assembleIds){
                foreach ($assembleIds as $k=>$v){
                    $data = [];
                    $data['id']=$v['id'];
                    $data['finish_status'] = 1;
                    $data['finish_time'] = time();
                    Db::name('seckill')->update($data);
                }
            }

            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
    }
    
}
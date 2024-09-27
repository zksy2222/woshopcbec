<?php
namespace app\api\controller;
use app\api\controller\Common;
use think\Db;

class PlanTask extends Common
{
    //秒杀、拼团活动结束和开始后自动更新参与商品展示价格
    public function modifyGoodsShowPrice(){
        $nowtime = time();

        //过期秒杀信息
        $end_rushres = Db::name('seckill')->where('checked',1)->where('is_show',1)->where('end_time','elt',$nowtime)->where('finish_status',0)->field('id,goods_id,end_time')->select();
        if($end_rushres){
            foreach ($end_rushres as $vr){
                $rumin_price = Db::name('goods')->where('id',$vr['goods_id'])->value('min_price');
                // 启动事务
                Db::startTrans();
                try{
                    Db::name('seckill')->update(array('hd_bs'=>2,'id'=>$vr['id'],'finish_status'=>1,'finish_time'=>$vr['end_time']));
                    Db::name('goods')->update(array('id'=>$vr['goods_id'],'zs_price'=>$rumin_price,'is_activity'=>0));
                    // 提交事务
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                }
            }
        }

//        //过期团购信息
//        $end_groupres = Db::name('group_buy')->where('checked',1)->where('is_show',1)->where('end_time','elt',$nowtime)->where('finish_status',0)->field('id,goods_id')->select();
//        if($end_groupres){
//            foreach ($end_groupres as $vp){
//                $acmin_price = Db::name('goods')->where('id',$vp['goods_id'])->value('min_price');
//                // 启动事务
//                Db::startTrans();
//                try{
//                    Db::name('group_buy')->update(array('hd_bs'=>2,'id'=>$vp['id']));
//                    Db::name('goods')->update(array('id'=>$vp['goods_id'],'zs_price'=>$acmin_price,'is_activity'=>0));
//                    // 提交事务
//                    Db::commit();
//                } catch (\Exception $e) {
//                    // 回滚事务
//                    Db::rollback();
//                }
//            }
//        }

        //过期拼团信息
        $end_pinres = Db::name('assemble')->where('checked',1)->where('hd_bs',1)->where('is_show',1)->where('end_time','elt',$nowtime)->field('id,goods_id,end_time')->select();
        if($end_pinres){
            foreach ($end_pinres as $va){
                $asmin_price = Db::name('goods')->where('id',$va['goods_id'])->value('min_price');
                // 启动事务
                Db::startTrans();
                try{
                    Db::name('assemble')->update(array('hd_bs'=>2,'id'=>$va['id'],'finish_status'=>1,'finish_time'=>$va['end_time']));
                    Db::name('goods')->update(array('id'=>$va['goods_id'],'zs_price'=>$asmin_price,'is_activity'=>0));
                    // 提交事务
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                }
            }
        }


        //秒杀中信息
        $rushres = Db::name('seckill')->where('checked',1)->where('hd_bs',0)->where('is_show',1)->where('start_time','elt',$nowtime)->where('end_time','gt',$nowtime)->field('id,goods_id,goods_attr,price')->select();
        if($rushres){
            foreach($rushres as $v){
                if($v['goods_attr']){
                    // 启动事务
                    Db::startTrans();
                    try{
                        Db::name('seckill')->update(array('hd_bs'=>1,'id'=>$v['id']));
                        Db::name('goods')->update(array('id'=>$v['goods_id'],'zs_price'=>$v['price'],'is_activity'=>1));
                        // 提交事务
                        Db::commit();
                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                    }
                }else{
                    // 启动事务
                    Db::startTrans();
                    try{
                        Db::name('seckill')->update(array('hd_bs'=>1,'id'=>$v['id']));
                        Db::name('goods')->update(array('id'=>$v['goods_id'],'zs_price'=>$v['price'],'is_activity'=>1));
                        // 提交事务
                        Db::commit();
                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                    }
                }
            }
        }

//        //团购中信息
//        $groupres = Db::name('group_buy')->where('checked',1)->where('hd_bs',0)->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->field('id,goods_id,price')->select();
//        if($groupres){
//            foreach ($groupres as $val){
//                // 启动事务
//                Db::startTrans();
//                try{
//                    Db::name('group_buy')->update(array('hd_bs'=>1,'id'=>$val['id']));
//                    Db::name('goods')->update(array('id'=>$val['goods_id'],'zs_price'=>$val['price'],'is_activity'=>2));
//                    // 提交事务
//                    Db::commit();
//                } catch (\Exception $e) {
//                    // 回滚事务
//                    Db::rollback();
//                }
//            }
//        }
//
        //拼团中信息
        $pinres = Db::name('assemble')->where('checked',1)->where('hd_bs',0)->where('is_show',1)->where('start_time','elt',time())->where('end_time','gt',time())->field('id,goods_id,price')->select();
        if($pinres){
            foreach ($pinres as $val2){
                // 启动事务
                Db::startTrans();
                try{
                    Db::name('assemble')->update(array('hd_bs'=>1,'id'=>$val2['id']));
                    Db::name('goods')->update(array('id'=>$val2['goods_id'],'zs_price'=>$val2['price'],'is_activity'=>3));
                    // 提交事务
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                }
            }
        }
    }

    //商家访客人数每6小时执行一次
    public function modifyShopVisitor(){
        $shopsVisitorRandom = $this->webconfig['shop_visitor_random'];
        $shopsVisitorRandom = explode(',', $shopsVisitorRandom);;
        $shopsDb = db('shops');
        $shopss = $shopsDb->select();
        foreach ($shopss as $k => $v){
            // 启动事务
            Db::startTrans();
            try{
                $shopVisitor = $v['shop_visitor'] + rand($shopsVisitorRandom[0],$shopsVisitorRandom[1]);
                $shopsDb->update(['id'=>$v['id'],'shop_visitor'=>$shopVisitor]);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
            }
        }
    }

    //过期自动拼团失败
    public function assembleFail(){
        // 启动事务
        Db::startTrans();
        try{
            $pintuanres = Db::name('pintuan')->lock(true)->where('timeout','elt',time())->where('state',1)->where('pin_status',0)->field('id,pin_num,tuan_num,pin_status,timeout')->select();
            if($pintuanres){
                foreach ($pintuanres as $v){
                    Db::name('pintuan')->where('id',$v['id'])->update(array('pin_status'=>2));

                    $order_assembleres = Db::name('order_assemble')->where('pin_id',$v['id'])->where('state',1)->where('tui_status',0)->select();
                    if($order_assembleres){
                        foreach ($order_assembleres as $vrc){
                            $pinorders = Db::name('order')->where('id',$vrc['order_id'])->where('state',1)->where('fh_status',0)->where('order_status',0)->where('order_type',2)->where('is_show',1)->field('id,total_price,user_id')->find();
                            if($pinorders){
                                Db::name('order_assemble')->where('id',$vrc['id'])->update(array('tui_status'=>1));
                                Db::name('order')->where('id',$pinorders['id'])->update(array('order_status'=>2,'can_time'=>time()));

                                $orgoods = Db::name('order_goods')->where('order_id',$pinorders['id'])->field('goods_id,goods_attr_id,goods_num,hd_type,hd_id')->find();
                                if($orgoods){
                                    Db::name('product')->where('goods_id',$orgoods['goods_id'])->where('goods_attr',$orgoods['goods_attr_id'])->setInc('goods_number', $orgoods['goods_num']);
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

    //开启退款状态的拼团单给用户钱包打款
    public function payAssembleRefund(){
        $pintuanres = Db::name('pintuan')->where('state',1)->where('pin_status',2)->where('tk_status',0)->field('id,pin_num,tuan_num,pin_status,timeout')->select();
        if($pintuanres){
            foreach ($pintuanres as $v){
                // 启动事务
                Db::startTrans();
                try{
                    Db::name('pintuan')->where('id',$v['id'])->update(array('tk_status'=>1,'tk_time'=>time()));

                    $order_assembleres = Db::name('order_assemble')->where('pin_id',$v['id'])->where('state',1)->where('tui_status',1)->where('dakuan_status',0)->select();
                    if($order_assembleres){
                        foreach ($order_assembleres as $vrc){
                            $pinorders = Db::name('order')->where('id',$vrc['order_id'])->where('state',1)->where('fh_status',0)->where('order_status',2)->where('order_type',2)->where('is_show',1)->field('id,total_price,user_id')->find();
                            if($pinorders){
                                Db::name('order_assemble')->where('id',$vrc['id'])->update(array('dakuan_status'=>1,'dakuan_time'=>time()));

                                $wallets = Db::name('wallet')->where('user_id',$pinorders['user_id'])->find();
                                if($wallets){
                                    Db::name('wallet')->where('id',$wallets['id'])->setInc('price', $pinorders['total_price']);
                                    Db::name('detail')->insert(array('de_type'=>1,'sr_type'=>2,'price'=>$pinorders['total_price'],'order_type'=>4,'order_id'=>$pinorders['id'],'user_id'=>$pinorders['user_id'],'wat_id'=>$wallets['id'],'time'=>time()));
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
        }
    }

    //订单过期自动关闭
    public function closeOverdueOrder(){
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

    //过期自动确认收货
    public function confirmOverdueOrderReceipt(){
        // 启动事务
        Db::startTrans();
        try{
            $orderids = Db::name('order')->lock(true)->where('zdsh_time','elt',time())->where('state',1)->where('fh_status',1)->where('order_status',0)->where('shouhou',0)->where('is_show',1)->field('id,total_price,user_id,shop_id')->select();
            if($orderids){
                foreach ($orderids as $v){
                    Db::name('order')->where('id',$v['id'])->update(array('order_status'=>1,'coll_time'=>time()));

                    //分销：成为下级条件-首次下单，自动确认收货，绑定上下级关系
                    $distrib = new DistributionCommon();
                    $distrib->bindDistribUser($v['user_id'], 2);

                    //订单完成，过期自动收货开始佣金结算
                    $distrib->commissionSettlement($v['id']);


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

                    //8购物消费（%）送积分(会员积分)
                    $num0 = $this->getIntegralValue(8);//获取积分
                    $num1 = sprintf("%.2f",$v['total_price']*($num0/100));
                    $this->addIntegral($v['user_id'],$num1,8,$v['id']);

                    //分销
                    $distributions = Db::name('distribution')->where('id',1)->find();
                    $shops = Db::name('shops')->where('id',$v['shop_id'])->field('id,indus_id,fenxiao')->find();
                    $total_price = $v['total_price'];

                    if($distributions['is_open'] == 1 && $shops['fenxiao'] == 1){
                        // 佣金计算
                        $levelinfos = Db::name('member')->where('id',$v['user_id'])->field('id,one_level,two_level')->find();
                        if($levelinfos['one_level']){
                            $one_wallets = Db::name('wallet')->where('user_id',$levelinfos['one_level'])->find();
                            if($one_wallets){
                                $onefen_price = sprintf("%.2f",$total_price*($distributions['one_profit']/100));
                                Db::name('wallet')->where('id',$one_wallets['id'])->setInc('price', $onefen_price);
                                Db::name('detail')->insert(array('de_type'=>1,'sr_type'=>1,'price'=>$onefen_price,'order_type'=>1,'order_id'=>$v['id'],'user_id'=>$levelinfos['one_level'],'wat_id'=>$one_wallets['id'],'time'=>time()));
                                Db::name('order')->where('id',$v['id'])->update(array('onefen_id'=>$levelinfos['one_level'],'onefen_price'=>$onefen_price));
                                $total_price = $total_price-$onefen_price;
                            }
                        }
//
                        if($levelinfos['two_level']){
                            $two_wallets = Db::name('wallet')->where('user_id',$levelinfos['two_level'])->find();
                            if($two_wallets){
                                $twofen_price = sprintf("%.2f",$total_price*($distributions['two_profit']/100));
                                Db::name('wallet')->where('id',$two_wallets['id'])->setInc('price', $twofen_price);
                                Db::name('detail')->insert(array('de_type'=>1,'sr_type'=>1,'price'=>$twofen_price,'order_type'=>1,'order_id'=>$v['id'],'user_id'=>$levelinfos['two_level'],'wat_id'=>$two_wallets['id'],'time'=>time()));
                                Db::name('order')->where('id',$v['id'])->update(array('twofen_id'=>$levelinfos['two_level'],'twofen_price'=>$twofen_price));
                                $total_price = $total_price-$twofen_price;
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
    public function payMerchant(){
        $dkorderes = Db::name('order')->where('shop_id','neq',1)->where('state',1)->where('fh_status',1)->where('order_status',1)->where('dakuan_status',0)->where('shouhou',0)->where('is_show',1)->field('id,total_price,user_id,shop_id')->select();
        if($dkorderes){
            $distributions = Db::name('distribution')->where('id',1)->find();
            if($distributions){
                foreach ($dkorderes as $v){
                    $shops = Db::name('shops')->where('id',$v['shop_id'])->field('id,indus_id,fenxiao,service_rate')->find();
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
                            //计算服务费
                            $remind_lv = $shops['service_rate']/1000;
                            $remind_price = sprintf("%.2f",$total_price*$remind_lv);
                            $total_price = sprintf("%.2f",$total_price-$remind_price);


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
    public function endExpiredActivity(){
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

    //退换货申请商家审核超时自动同意处理
    public function confirmReturnApply(){
        // 启动事务
        Db::startTrans();
        try{
            $applyres = Db::name('th_apply')->lock(true)->where('check_timeout','elt',time())->where('apply_status',0)->field('id,thfw_id,orgoods_id,order_id')->select();

            if($applyres){
                $ordouts = Db::name('order_timeout')->where('id',1)->find();

                foreach ($applyres as $v){
                    if($v['thfw_id'] == 1){
                        $shoptui_timeout = time()+$ordouts['shoptui_timeout']*24*3600;
                        Db::name('th_apply')->update(array('apply_status'=>1,'agree_time'=>time(),'shoptui_timeout'=>$shoptui_timeout,'id'=>$v['id']));
                    }elseif(in_array($v['thfw_id'], array(2,3))){
                        $yhfh_timeout = time()+$ordouts['yhfh_timeout']*24*3600;
                        Db::name('th_apply')->update(array('apply_status'=>1,'agree_time'=>time(),'yhfh_timeout'=>$yhfh_timeout,'id'=>$v['id']));
                    }

                    if(in_array($v['thfw_id'], array(1,2))){
                        $th_status = 2;
                    }elseif($v['thfw_id'] == 3){
                        $th_status = 6;
                    }

                    if(!empty($th_status)){
                        Db::name('order_goods')->update(array('th_status'=>$th_status,'id'=>$v['orgoods_id']));
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

    //退款申请商家确定退款超时处理
    public function confirmRefundApply(){
        // 启动事务
        Db::startTrans();
        try{
            $applyres = Db::name('th_apply')->lock(true)->where(function ($query){
                $query->where('thfw_id',1)->where('shoptui_timeout','elt',time())->where('apply_status',1);
            })->whereOr(function ($query){
                $query->where('thfw_id',2)->where('shoptui_timeout','elt',time())->where('apply_status',1)->where('dcfh_status',1)->where('sh_status',1);
            })->select();

            if($applyres){
                $ordouts = Db::name('order_timeout')->where('id',1)->find();

                foreach ($applyres as $v){
                    $orgoods = Db::name('order_goods')->where('id',$v['orgoods_id'])->field('goods_id,goods_attr_id,hd_type,hd_id')->find();
                    if($orgoods){
                        Db::name('th_apply')->update(array('apply_status'=>3,'com_time'=>time(),'id'=>$v['id']));
                        Db::name('order_goods')->update(array('th_status'=>4,'id'=>$v['orgoods_id']));
                        $ordergoods = Db::name('order_goods')->where('id','neq',$v['orgoods_id'])->where('order_id',$v['order_id'])->where('th_status','in','0,1,2,3,5,6,7,8')->field('id')->find();
                        if(!$ordergoods){
                            $orders = Db::name('order')->where('id',$v['order_id'])->find();
                            if($orders){
                                Db::name('order')->where('id',$v['order_id'])->update(array('order_status'=>2,'shouhou'=>0,'can_time'=>time()));
                                if($orders['coupon_id']){
                                    Db::name('member_coupon')->where('user_id',$orders['user_id'])->where('coupon_id',$orders['coupon_id'])->where('is_sy',1)->where('shop_id',$orders['shop_id'])->update(array('is_sy'=>0));
                                }
                            }
                        }else{
                            $ordergoodres = Db::name('order_goods')->where('id','neq',$v['orgoods_id'])->where('order_id',$v['order_id'])->where('th_status','in','1,2,3,5,6,7')->field('id')->find();

                            if($ordergoodres){
                                $shouhou = 1;
                            }else{
                                $shouhou = 0;
                            }

                            if($shouhou == 0){
                                $orders = Db::name('order')->where('id',$v['order_id'])->find();
                                if($orders){
                                    $zdsh_time = time()+$ordouts['zdqr_sh_time']*24*3600;
                                    Db::name('order')->where('id',$v['order_id'])->update(array('shouhou'=>0,'zdsh_time'=>$zdsh_time));
                                }
                            }
                        }

                        if(in_array($orgoods['hd_type'],array(0,2,3))){
                            $prokc = Db::name('product')->where('goods_id',$orgoods['goods_id'])->where('goods_attr',$orgoods['goods_attr_id'])->find();
                            if($prokc){
                                Db::name('product')->where('goods_id',$orgoods['goods_id'])->where('goods_attr',$orgoods['goods_attr_id'])->setInc('goods_number', $v['tui_num']);
                            }
                        }elseif($orgoods['hd_type'] == 1){
                            $hdactivitys = Db::name('seckill')->where('id',$orgoods['hd_id'])->find();
                            if($hdactivitys){
                                Db::name('seckill')->where('id',$orgoods['hd_id'])->setInc('stock',$v['tui_num']);
                                Db::name('seckill')->where('id',$orgoods['hd_id'])->setDec('sold',$v['tui_num']);
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

    //退款申请商家确定退款自动退款
    public function payMemberRefund(){
        $applyres = Db::name('th_apply')->where(function ($query){
            $query->where('thfw_id',1)->where('apply_status',3)->where('dakuan_status',0);
        })->whereOr(function ($query){
            $query->where('thfw_id',2)->where('apply_status',3)->where('dcfh_status',1)->where('sh_status',1)->where('dakuan_status',0);
        })->select();

        if($applyres){
            foreach ($applyres as $v){
                // 启动事务
                Db::startTrans();
                try{
                    Db::name('th_apply')->where('id',$v['id'])->update(array('dakuan_status'=>1,'dakuan_time'=>time()));
                    $wallets = Db::name('wallet')->where('user_id',$v['user_id'])->find();
                    if($wallets){
                        Db::name('wallet')->where('id',$wallets['id'])->setInc('price', $v['tui_price']);
                        Db::name('detail')->insert(array('de_type'=>1,'sr_type'=>2,'price'=>$v['tui_price'],'order_type'=>4,'order_id'=>$v['id'],'user_id'=>$v['user_id'],'wat_id'=>$wallets['id'],'time'=>time()));
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

    //退货退款或换货订单用户发货超时自动撤销
    public function closeMemberRefundApply(){
        // 启动事务
        Db::startTrans();
        try{
            $applyres = Db::name('th_apply')->lock(true)->where('yhfh_timeout','elt',time())->where('thfw_id','in','2,3')->where('apply_status',1)->where('dcfh_status',0)->field('id,thfw_id,orgoods_id,order_id,apply_status,dcfh_status')->select();
            if($applyres){
                $ordouts = Db::name('order_timeout')->where('id',1)->find();

                foreach ($applyres as $v){
                    $orders = Db::name('order')->where('id',$v['order_id'])->where('state',1)->where('fh_status',1)->field('id')->find();
                    if($orders){
                        Db::name('th_apply')->update(array('apply_status'=>4,'che_time'=>time(),'id'=>$v['id']));
                        Db::name('order_goods')->update(array('th_status'=>0,'id'=>$v['orgoods_id']));

                        $ordergoods = Db::name('order_goods')->where('id','neq',$v['orgoods_id'])->where('order_id',$v['order_id'])->where('th_status','in','1,2,3,5,6,7')->field('id')->find();

                        if($ordergoods){
                            $shouhou = 1;
                        }else{
                            $shouhou = 0;
                        }

                        if($shouhou == 0){
                            $orders = Db::name('order')->where('id',$v['order_id'])->find();
                            if($orders){
                                $zdsh_time = time()+$ordouts['zdqr_sh_time']*24*3600;
                                Db::name('order')->where('id',$v['order_id'])->update(array('shouhou'=>0,'zdsh_time'=>$zdsh_time));
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

    //换货用户确认收货超时自动收货
    public function confirmReturnOrderReceipt(){
        $applyres = Db::name('th_apply')->lock(true)->where('yhshou_timeout','elt',time())->where('thfw_id',3)->where('apply_status',1)->where('dcfh_status',1)->where('sh_status',1)->where('fh_status',1)->where('shou_status',0)->field('id,thfw_id,orgoods_id,order_id')->select();
        if($applyres){
            $ordouts = Db::name('order_timeout')->where('id',1)->find();

            foreach($applyres as $v){
                // 启动事务
                Db::startTrans();
                try{
                    Db::name('th_apply')->update(array('shou_status'=>1,'apply_status'=>3,'shou_time'=>time(),'com_time'=>time(),'id'=>$v['id']));
                    Db::name('order_goods')->update(array('th_status'=>8,'id'=>$v['orgoods_id']));

                    $ordergoods = Db::name('order_goods')->where('id','neq',$v['orgoods_id'])->where('order_id',$v['order_id'])->where('th_status','in','1,2,3,5,6,7')->field('id,th_status')->find();

                    if($ordergoods){
                        $shouhou = 1;
                    }else{
                        $shouhou = 0;
                    }

                    if($shouhou == 0){
                        $orders = Db::name('order')->where('id',$v['order_id'])->find();
                        if($orders){
                            $zdsh_time = time()+$ordouts['zdqr_sh_time']*24*3600;
                            Db::name('order')->where('id',$v['order_id'])->update(array('shouhou'=>0,'zdsh_time'=>$zdsh_time));
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
    }

    //已确认收货并不存在售后中，超时自动评价
    public function automaticComment(){
        //获取未评价的订单
        $where = [
            'a.ping'          =>  0,
            'a.state'         =>  1,
            'a.fh_status'     =>  1,
            'a.order_status'  =>  1,
            'a.is_show'       =>  1,
            'a.shouhou'       =>  0,
        ];
        $commentRes =   db('order')
            ->alias('a')
            ->field('a.id,a.user_id,a.shop_id,a.coll_time,a.shouhou,b.id orgoods_id,b.goods_id')
            ->where($where)
            ->join('order_goods b','a.id = b.order_id','INNER')
            ->select();
        //获取自动评价时间
        $timeOut = db('order_timeout')->field('comment_timeout,comment_content')->where(['id'=>1])->find();
        if($commentRes){
            foreach ($commentRes as $k => $v){
                $commentTimeOut = $v['coll_time'] + $timeOut['comment_timeout']*24*60*60;
                if($commentTimeOut <= time()){
                    // 启动事务
                    Db::startTrans();
                    try{
                        $data = [
                            'goods_star'        =>  5,
                            'service_star'      =>  5,
                            'logistics_star'    =>  5,
                            'content'           =>  $timeOut['comment_content'],
                            'time'              =>  time(),
                            'goods_id'          =>  $v['goods_id'],
                            'orgoods_id'        =>  $v['orgoods_id'],
                            'order_id'          =>  $v['id'],
                            'user_id'           =>  $v['user_id'],
                            'shop_id'           =>  $v['shop_id'],
                            'anonymous'         =>  0,
                            'checked'           =>  1,
                        ];
                        //新增评价信息
                        $id =  db('comment')->insertGetId($data);
                        if($id){
                            //修改订单表评价状态为已评价
                            db('order')->where(['id'=>$v['id']])->update(['ping'=>1]);
                            //修改订单商品表评价状态为已评价
                            db('order_goods')->where(['order_id'=>$v['id']])->update(['ping'=>1]);
                        }

                        //修改店铺好评率
                        $where = [
                            'shop_id'=>$v['shop_id']
                        ];
                        $orderRes = db('order')->where($where)->count();
                        $commentRes = db('comment')->where($where)->where('goods_star','>',3)->count();
                        //计算好评率
                        $praise_lv = $commentRes/$orderRes;
                        if($praise_lv<1){
                            $praise_lv = 1;
                        }
                        $praise_lv = floor($praise_lv);
                        //修改店铺好评率
                        db('shops')->where('id',$v['shop_id'])->update(['praise_lv'=>$praise_lv]);

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

    //自动清理商家客服和聊天异常数据
    public function autoService(){
        $shops = db('shops')->field('id')->select();
        // 启动事务
        Db::startTrans();
        try{
            //处理商家绑定客服
            $memberDb = db('member');
            foreach ($shops as $k => $v){
                $memberIds = $memberDb->where('id','<',200)->where('shop_id',0)->field('id')->select();
                $members = $memberDb->where('shop_id',$v['id'])->select();

                $count = count($members);
                if($count < 1){
                    $memberId = array_rand($memberIds);
                    $memberDb->where('id',$memberId)->update(['shop_id'=>$v['id']]);
                }
                if($count > 1){
                    foreach ($members as $k1 => $v1){
                        if($k1 == 0){
                            continue;
                        }
                        $memberDb->update(['id'=>$v1['id'],'shop_id'=>0]);
                    }
                }
            }
            //处理聊天记录
            $chatMessageDb = db('chat_message');
            $messages = $chatMessageDb->select();
            foreach ($messages as $k=>$v){
                if(empty($v['fromid']) || empty($v['toid'])){
                    $chatMessageDb->where('id',$v['id'])->delete();
                }
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
    }

    //首页商品定时恢复,演示账号定时恢复
    public function timingRecovery(){
        // 启动事务
        Db::startTrans();
        try{
            //恢复商品信息
            $where = [77,78,79,80,81,82,83,84,85,86,87,88,449,8313,8314,8315,8316,8317,8318,8319,8320];
            $goodss = db('goods')->where('id','in',$where)->select();
            foreach ($goodss as $k => $v){
                db('goods')->where('id',$v['id'])->delete();
                db('goods_lang')->where('goods_id',$v['id'])->delete();
                db('goods_option')->where('goods_id',$v['id'])->delete();
                db('goods_pic')->where('goods_id',$v['id'])->delete();
                $specIds = db('goods_spec')->where('goods_id',$v['id'])->column('id');
                db('goods_spec')->where('goods_id',$v['id'])->delete();
                db('goods_spec_item')->where('spec_id','in',$specIds)->delete();
            }
            //删除产品库ID大于1100的商品
            db('goods')->where('id','>',1100)->where('shop_id',1)->delete();

            //恢复账号信息
            $where1 = ['1242','1244'];
            $member = db('member')->where('id','in',$where1)->select();
            foreach ($member as $k => $v){
                db('member')->where('id',$v['id'])->delete();
            }

            $agentOne = db('agent')->where('id',1)->find();
            if($agentOne){
                db('agent')->where('id',1)->delete();
            }
            $agent = db('agent')->where('user_id','in',$where1)->select();
            foreach ($agent as $k => $v){
                db('agent')->where('id',$v['id'])->delete();
            }
            $shop = db('shops')->where('id',40)->find();
            if($shop){
                db('shops')->where('id',40)->delete();
            }

            db('shop_admin')->where('shop_id',39)->update(['phone'=>'17712345678','password'=>'e10adc3949ba59abbe56e057f20f883e']);
            db('shop_admin')->where('shop_id',40)->update(['phone'=>'18812345678','password'=>'e10adc3949ba59abbe56e057f20f883e']);

            db('agent')->insert(['id'=>1,'user_id'=>1242,'divide'=>1,'addtime'=>'1668343910','checked'=>1,'login_ip'=>'127.0.0.1']);
            //重新写入数据
            $sql = ROOT_PATH.'/data/recovery.sql';
            $sql=file_get_contents($sql);
            $tableArr = explode('--page--',$sql);
            foreach ($tableArr as $k=>$v){
                Db::execute($v);
            }
            db('goods')->where('id','in',$where)->update(['onsale'=>1]);
            db('goods')->where('id','not in',$where)->update(['onsale'=>0]);
            $categoryIds = db('category')->column('id');
            db('category')->where('id','in',$categoryIds)->update(['is_show'=>1]);
            $where2 = [32,33,34,35,36,37,38,41,42,43,44,45];
            db('goods')->where('id','in',$where2)->update(['onsale'=>1]);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            dump($e->getMessage());
        }
    }
}
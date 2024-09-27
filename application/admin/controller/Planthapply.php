<?php
namespace app\admin\controller;
use app\admin\controller\Basic;
use think\Db;

class Planthapply extends Basic{
    
    //退换货申请商家审核超时自动同意处理
    public function checkedapply(){
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
    public function shoptui(){
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
    public function dakuanuser(){
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
    public function chexiao(){
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
    public function shouhuo(){
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
    
}
<?php
namespace app\api\controller;
use app\api\controller\Common;
use app\api\model\Common as CommonModel;
use app\api\model\Goods as GoodsModel;
use app\api\model\OrderGoods as OrderGoodsModel;
use app\api\model\DistributionCommissonDetail as DistributionCommissonDetailModel;
use app\api\model\DistributionUser as DistributionUserModel;
use think\Db;

class MemberOrder extends Common{
    
    //订单列表信息接口
    public function index(){
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{
		    $userId = $tokenRes['user_id'];
	    }
        if(!input('post.page') && !preg_match("/^\\+?[1-9][0-9]*$/", input('post.page'))){
	        datamsg(400,'缺少页数参数',array('status'=>400));
        }

        $ordouts = Db::name('order_timeout')->where('id',1)->find();
        $webconfig = $this->webconfig;
        $perpage = 20;
        $offset = (input('post.page')-1)*$perpage;

        $filter = input('post.filter');
        if(!$filter || !in_array($filter, array(1,2,3,4,5,6,7))){
            $filter = 6;
        }

        switch($filter){
            //待付款
            case 1:
                $where = array('a.user_id'=>$userId,'a.state'=>0,'a.fh_status'=>0,'a.order_status'=>0,'a.is_show'=>1);
                $sort = array('a.addtime'=>'desc','a.id'=>'desc');
                break;
            //待发货
            case 2:
                $where = array('a.user_id'=>$userId,'a.state'=>1,'a.fh_status'=>0,'a.order_status'=>0,'a.is_show'=>1);
                $sort = array('a.pay_time'=>'desc','a.id'=>'desc');
                break;
            //待收货
            case 3:
                $where = array('a.user_id'=>$userId,'a.state'=>1,'a.fh_status'=>1,'a.order_status'=>0,'a.is_show'=>1);
                $sort = array('a.fh_time'=>'desc','a.id'=>'desc');
                break;
            //待评价
            case 4:
                $where = array('a.user_id'=>$userId,'a.state'=>1,'a.fh_status'=>1,'a.order_status'=>1,'a.ping'=>0,'a.is_show'=>1,'a.shouhou'=>0);
                $sort = array('a.coll_time'=>'desc','a.id'=>'desc');
                break;
            //已取消
            case 7:
                $where = array('a.user_id'=>$userId,'a.order_status'=>3,'a.is_show'=>1,'a.shouhou'=>0);
                $sort = array('a.coll_time'=>'desc','a.id'=>'desc');
                break;
            //全部
            case 6:
                $where = array('a.user_id'=>$userId,'a.is_show'=>1);
                $sort = array('a.addtime'=>'desc','a.id'=>'desc');
                break;
        }

        if(in_array($filter,array(1,2,3,4,6,7))){
            $orderes = Db::name('order')
                         ->alias('a')
                         ->field('a.id,a.ordernumber,a.coupon_id,a.total_price,a.integral,a.state,a.fh_status,a.order_status,a.shouhou,a.ping,a.is_show,a.ping,a.order_type,a.pin_type,a.pin_id,a.shop_id,a.zdsh_time,a.time_out,a.coll_time,a.user_id,a.user_dakuan_status,b.shop_name')
                         ->join('sp_shops b','a.shop_id = b.id','INNER')
                         ->where($where)
                         ->order($sort)
                         ->limit($offset,$perpage)
                         ->select();
            if($orderes){
                foreach ($orderes as $k => $v){
                    if($v['state'] == 0 && $v['fh_status'] == 0 && $v['order_status'] == 0 && $v['is_show'] == 1){
                        $orderes[$k]['order_zt'] = "待付款";
                        $orderes[$k]['filter'] = 1;
                    }elseif($v['state'] == 1 && $v['fh_status'] == 0 && $v['order_status'] == 0 && $v['is_show'] == 1){
                        $orderes[$k]['order_zt'] = "待发货";
                        $orderes[$k]['filter'] = 2;
                    }elseif($v['state'] == 1 && $v['fh_status'] == 1 && $v['order_status'] == 0 && $v['is_show'] == 1){
                        $orderes[$k]['order_zt'] = "待收货";
                        $orderes[$k]['filter'] = 3;
                    }elseif($v['state'] == 1 && $v['fh_status'] == 1 && $v['order_status'] == 1 && $v['is_show'] == 1){
                        $orderes[$k]['order_zt'] = "已完成";
                        $orderes[$k]['filter'] = 4;
                    }elseif($v['order_status'] == 2 && $v[state] ==1 && $v['is_show'] == 1){
                        $orderes[$k]['order_zt'] = "已关闭";
                        $orderes[$k]['filter'] = 5;
                    }elseif($v['order_status'] == 3 && $v['is_show'] == 1){
                        $orderes[$k]['order_zt'] = "已取消";
                        $orderes[$k]['filter'] = 6;
                    }

                    $orderes[$k]['goodsinfo'] = Db::name('order_goods')->where('order_id',$v['id'])->field('id,goods_id,goods_name,thumb_url,goods_attr_str,price,integral,hd_type,goods_num,th_status,order_id')->select();

                    $domain = $webconfig['weburl'];

                    foreach ($orderes[$k]['goodsinfo'] as $key => $val){
                        $orderes[$k]['goodsinfo'][$key]['thumb_url'] = url_format($val['thumb_url'],$webconfig['weburl']);
                        $hasComment = Db::name('comment')->where('goods_id',$val['goods_id'])->where('order_id',$val['order_id'])->find();
                        if($hasComment){
                            $orderes[$k]['goodsinfo'][$key]['hasComment'] = 1;
                        }else{
                            $orderes[$k]['goodsinfo'][$key]['hasComment'] = 0;
                        }
                        unset($hasComment);
                    }
                    $orderes[$k]['spnum'] = Db::name('order_goods')->where('order_id',$v['id'])->sum('goods_num');
                }
            }
        }else{

            $orderes = Db::name('th_apply')
                         ->alias('a')
                         ->field('a.id,a.th_number,a.thfw_id,a.apply_status,a.tui_price,a.tui_num,a.orgoods_id,a.order_id,a.dcfh_status,a.sh_status,a.fh_status,a.shou_status,a.check_timeout,a.shoptui_timeout,a.yhfh_timeout,a.yhshou_timeout,a.shop_id,b.shop_name')
                         ->join('sp_shops b','a.shop_id = b.id','INNER')
                         ->where('a.user_id',$userId)
                         ->order('a.apply_time desc')
                         ->limit($offset,$perpage)
                         ->select();
            if($orderes){
                foreach ($orderes as $k => $v){
                    switch($v['thfw_id']){
                        case 1:
                            if($v['apply_status'] == 0){
                                $orderes[$k]['order_zt'] = '待商家处理';
                            }elseif($v['apply_status'] == 1){
                                $orderes[$k]['order_zt'] = '待商家退款';
                            }elseif($v['apply_status'] == 2){
                                $orderes[$k]['order_zt'] = '平台拒绝申请';
                            }elseif($v['apply_status'] == 3){
                                $orderes[$k]['order_zt'] = '退款已完成';
                            }elseif($v['apply_status'] == 4){
                                $orderes[$k]['order_zt'] = '已撤销';
                            }
                            break;
                        case 2:
                            if($v['apply_status'] == 0){
                                $orderes[$k]['order_zt'] = '待商家处理';
                            }elseif($v['apply_status'] == 1){
                                if($v['dcfh_status'] == 0){
                                    $orderes[$k]['order_zt'] = '待用户发货';
                                }elseif($v['dcfh_status'] == 1 && $v['sh_status'] == 0){
                                    $orderes[$k]['order_zt'] = '待商家收货';
                                }elseif($v['dcfh_status'] == 1 && $v['sh_status'] == 1){
                                    $orderes[$k]['order_zt'] = '待商家退款';
                                }
                            }elseif($v['apply_status'] == 2){
                                $orderes[$k]['order_zt'] = '平台拒绝申请';
                            }elseif($v['apply_status'] == 3){
                                $orderes[$k]['order_zt'] = '退款已完成';
                            }elseif($v['apply_status'] == 4){
                                $orderes[$k]['order_zt'] = '已撤销';
                            }
                            break;
                        case 3:
                            if($v['apply_status'] == 0){
                                $orderes[$k]['order_zt'] = '待商家处理';
                            }elseif($v['apply_status'] == 1){
                                if($v['dcfh_status'] == 0){
                                    $orderes[$k]['order_zt'] = '待用户发货';
                                }elseif($v['dcfh_status'] == 1 && $v['sh_status'] == 0){
                                    $orderes[$k]['order_zt'] = '待商家收货';
                                }elseif($v['sh_status'] == 1 && $v['fh_status'] == 0){
                                    $orderes[$k]['order_zt'] = '待商家发货';
                                }elseif($v['fh_status'] == 1 && $v['shou_status'] == 0){
                                    $orderes[$k]['order_zt'] = '待用户收货';
                                }
                            }elseif($v['apply_status'] == 2){
                                $orderes[$k]['order_zt'] = '平台拒绝申请';
                            }elseif($v['apply_status'] == 3){
                                $orderes[$k]['order_zt'] = '换货已完成';
                            }elseif($v['apply_status'] == 4){
                                $orderes[$k]['order_zt'] = '已撤销';
                            }
                            break;
                    }
                    $orderes[$k]['orgoods'] = Db::name('order_goods')->where('id',$v['orgoods_id'])->where('order_id',$v['order_id'])->field('id,goods_id,goods_name,thumb_url,goods_attr_str,goods_num,th_status,order_id')->find();


                    $orderes[$k]['orgoods']['thumb_url'] = url_format($orderes[$k]['orgoods']['thumb_url'],$webconfig['weburl']);
                    if($v['apply_status'] == 0 && $v['check_timeout'] <= time()){
                        // 启动事务
                        Db::startTrans();
                        try{
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

                            // 提交事务
                            Db::commit();
                        } catch (\Exception $e) {
                            // 回滚事务
                            Db::rollback();
                        }
                    }elseif($v['thfw_id'] == 1 && $v['apply_status'] == 1 && $v['shoptui_timeout'] <= time()){
                        $orgoods = Db::name('order_goods')->where('id',$v['orgoods_id'])->field('goods_id,goods_attr_id,hd_type,hd_id')->find();
                        if($orgoods){
                            // 启动事务
                            Db::startTrans();
                            try{
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

                                // 提交事务
                                Db::commit();
                            } catch (\Exception $e) {
                                // 回滚事务
                                Db::rollback();
                            }
                        }
                    }elseif($v['thfw_id'] == 2 && $v['apply_status'] == 1 && $v['dcfh_status'] == 1 && $v['sh_status'] == 1 && $v['shoptui_timeout'] <= time()){
                        $orgoods = Db::name('order_goods')->where('id',$v['orgoods_id'])->field('goods_id,goods_attr_id,hd_type,hd_id')->find();
                        if($orgoods){
                            // 启动事务
                            Db::startTrans();
                            try{
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

                                // 提交事务
                                Db::commit();
                            } catch (\Exception $e) {
                                // 回滚事务
                                Db::rollback();
                            }
                        }
                    }elseif(in_array($v['thfw_id'], array(2,3)) && $v['apply_status'] == 1 && $v['dcfh_status'] == 0 && $v['yhfh_timeout'] <= time()){
                        $orders = Db::name('order')->where('id',$v['order_id'])->where('state',1)->where('fh_status',1)->field('id')->find();
                        if($orders){
                            // 启动事务
                            Db::startTrans();
                            try{
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

                                // 提交事务
                                Db::commit();
                            } catch (\Exception $e) {
                                // 回滚事务
                                Db::rollback();
                            }
                        }
                    }elseif($v['thfw_id'] == 3 && $v['apply_status'] == 1 && $v['dcfh_status'] == 1 && $v['sh_status'] == 1 && $v['fh_status'] == 1 && $v['shou_status'] == 0 && $v['yhshou_timeout'] <= time()){
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
        }
	    datamsg(200,'获取订单信息成功',set_lang($orderes));
    }
    
    //取消订单
    public function quxiao(){
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{
		    $userId = $tokenRes['user_id'];
	    }

        if(!input('post.order_num')){
	        datamsg(400,'缺少订单号',array('status'=>400));
        }

        $order_num = input('post.order_num');
        $orders = Db::name('order')->where('ordernumber',$order_num)->where('user_id',$userId)->where('fh_status',0)->where('order_status',0)->where('is_show',1)->find();
        if(!$orders){
	        datamsg(400,'找不到相关类型订单',array('status'=>400));
        }
        $orgoodres = Db::name('order_goods')->where('order_id',$orders['id'])->field('id,goods_id,order_id,goods_attr_id,goods_num,hd_type,hd_id,shop_id')->select();
	    if(!$orgoodres){
		    datamsg(400,'找不到相关类型订单商品',array('status'=>400));
	    }

        // 启动事务
        Db::startTrans();
        try{
            Db::name('order')->update(array('order_status'=>3,'can_time'=>time(),'id'=>$orders['id']));

            if($orders['coupon_id']){
                Db::name('member_coupon')->where('user_id',$userId)->where('coupon_id',$orders['coupon_id'])->where('is_sy',1)->where('shop_id',$orders['shop_id'])->update(array('is_sy'=>0));
            }

            foreach($orgoodres as $v){
                Db::name('order_goods')->update(array('th_status'=>9,'id'=>$v['id']));
                if(in_array($v['hd_type'],array(0,3))){
                    $prokc = Db::name('product')->where('goods_id',$v['goods_id'])->where('goods_attr',$v['goods_attr_id'])->find();
                    if($prokc){
                        Db::name('product')->where('goods_id',$v['goods_id'])->where('goods_attr',$v['goods_attr_id'])->setInc('goods_number', $v['goods_num']);
                    }
                }elseif($v['hd_type'] == 1){
                    $hdactivitys = Db::name('seckill')->where('id',$v['hd_id'])->find();
                    if($hdactivitys){
                        Db::name('seckill')->where('id',$v['hd_id'])->setInc('stock',$v['goods_num']);
                        Db::name('seckill')->where('id',$v['hd_id'])->setDec('sold',$v['goods_num']);
                    }
                }elseif($v['hd_type'] == 2){
                    $hdactivitys = Db::name('integral_shop')->where('id',$v['hd_id'])->find();
                    if($hdactivitys){
                        Db::name('integral_shop')->where('id',$v['hd_id'])->setInc('stock',$v['goods_num']);
                        Db::name('integral_shop')->where('id',$v['hd_id'])->setDec('sold',$v['goods_num']);

                        Db::name('member')->where('id',$userId)->setInc('integral',$v['integral']);
                        $data = [];
                        $data['user_id'] = $orders['user_id'];
                        $data['integral'] = $v['integral'];
                        $data['type'] = 15;
                        $data['order_id'] = $orders['id'];
                        $data['class'] = 0;
                        $data['addtime'] = time();
                        Db::name('member_integral')->insert($data);
                    }
                }
            }


            // 提交事务
            Db::commit();
	        datamsg(200,'取消订单成功',array('status'=>200));
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
	        datamsg(400,'取消订单失败',array('status'=>400));
        }
    }
    
    //删除订单
    public function delorder(){
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{
		    $userId = $tokenRes['user_id'];
	    }
	    if(!input('post.order_num')){
		    datamsg(400,'缺少订单号',array('status'=>400));
	    }
        $order_num = input('post.order_num');
        $orders = Db::name('order')->where('ordernumber',$order_num)->where('user_id',$userId)->where('state',0)->where('fh_status',0)->where('order_status',2)->where('is_show',1)->find();
	    if(!$orders){
		    datamsg(400,'找不到相关类型订单');
	    }
        $count = Db::name('order')->update(array('is_show'=>0,'del_time'=>time(),'id'=>$orders['id']));

        if($count > 0){
	        datamsg(200,'删除订单成功',array('status'=>200));
        }else{
	        datamsg(400,'删除订单失败',array('status'=>400));
        }
    }
    
    //订单详情
    public function getOrderInfo(){
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{
		    $userId = $tokenRes['user_id'];
	    }
	    if(!input('post.order_num')){
		    datamsg(400,'缺少订单号',array('status'=>400));
	    }
        $order_num = input('post.order_num');
        $orders = Db::name('order')
                    ->alias('a')
                    ->field('a.id,a.ordernumber,a.integral,a.contacts,a.telephone,a.province,a.city,a.zf_type,a.area,a.address,a.goods_price,a.freight,a.youhui_price,a.coupon_id,a.coupon_price,a.coupon_str,a.total_price,a.beizhu,a.state,a.pay_time,a.fh_status,a.fh_time,a.order_status,a.shouhou,a.is_show,a.coll_time,a.can_time,a.ping,a.order_type,a.pin_type,a.pin_id,a.zong_id,a.shop_id,a.addtime,a.zdsh_time,a.time_out,b.order_number,c.shop_name')
                    ->join('sp_order_zong b','a.zong_id = b.id','LEFT')
                    ->join('sp_shops c','a.shop_id = c.id','LEFT')
                    ->where('a.ordernumber',$order_num)
                    ->where('a.user_id',$userId)
                    ->where('a.is_show',1)
                    ->find();
	    if(!$orders){
		    datamsg(400,'找不到相关订单',array('status'=>400));
	    }

        if($orders['pay_time']){
            $orders['pay_time'] = date('Y-m-d H:i:s',$orders['pay_time']);
        }

        if($orders['fh_time']){
            $orders['fh_time'] = date('Y-m-d H:i:s',$orders['fh_time']);
        }

        if($orders['coll_time']){
            $orders['coll_time'] = date('Y-m-d H:i:s',$orders['coll_time']);
        }

        if($orders['can_time']){
            $orders['can_time'] = date('Y-m-d H:i:s',$orders['can_time']);
        }

        if($orders['addtime']){
            $orders['addtime'] = date('Y-m-d H:i:s',$orders['addtime']);
        }

        if($orders['state'] == 0 && $orders['fh_status'] == 0 && $orders['order_status'] == 0 && $orders['is_show'] == 1){
            $orders['order_zt'] = "待付款";
            $orders['filter'] = 1;
            if($orders['time_out'] > time()){
                $orders['sytime'] = time2string($orders['time_out']-time());
            }else{
                $orders['sytime'] = '';
            }
        }elseif($orders['state'] == 1 && $orders['fh_status'] == 0 && $orders['order_status'] == 0 && $orders['is_show'] == 1){
            $orders['order_zt'] = "待发货";
            $orders['filter'] = 2;
        }elseif($orders['state'] == 1 && $orders['fh_status'] == 1 && $orders['order_status'] == 0 && $orders['is_show'] == 1){
            $orders['order_zt'] = "待收货";
            $orders['filter'] = 3;
            if($orders['zdsh_time'] > time()){
                $orders['sysh_time'] = time2string($orders['zdsh_time']-time());
            }else{
                $orders['sysh_time'] = '';
            }
        }elseif($orders['state'] == 1 && $orders['fh_status'] == 1 && $orders['order_status'] == 1 && $orders['is_show'] == 1){
            $orders['order_zt'] = "已完成";
            $orders['filter'] = 4;
        }elseif($orders['order_status'] == 2 && $orders['state'] == 1 && $orders['is_show'] == 1){
            $orders['order_zt'] = "已关闭";
            $orders['filter'] = 5;
        }elseif($orders['order_status'] == 2 && $orders['state'] == 0 && $orders['is_show'] == 1){
            $orders['order_zt'] = "已取消";
            $orders['filter'] = 6;
        }

        $orders['pinzhuangtai'] = 0;

        if($orders['state'] == 1 && $orders['fh_status'] == 0 && $orders['order_status'] == 0 && $orders['order_type'] == 2 && $orders['is_show'] == 1){
            $pinzts = Db::name('pintuan')->where('id',$orders['pin_id'])->where('state',1)->field('id,pin_num,tuan_num,pin_status,timeout')->find();
            if($pinzts){
                if($pinzts['pin_status'] == 0){
                    $order_assembleres = Db::name('order_assemble')->where('pin_id',$pinzts['id'])->where('order_id',$orders['id'])->where('user_id',$userId)->where('state',1)->where('tui_status',0)->find();
                    if($order_assembleres){
                        $orders['pinzhuangtai'] = 1;
                    }else{
                        $orders['pinzhuangtai'] = 2;
                    }
                }elseif($pinzts['pin_status'] == 2){
                    $orders['pinzhuangtai'] = 2;
                }
            }else{
                $orders['pinzhuangtai'] = 2;
            }
        }

        $orders['goodsinfo'] = Db::name('order_goods')->where('order_id',$orders['id'])->field('id,goods_id,goods_name,thumb_url,goods_attr_str,real_price,goods_num,th_status,order_id')->select();

        foreach ($orders['goodsinfo'] as $key => $val){
            $orders['goodsinfo'][$key]['thumb_url'] =url_format($val['thumb_url'],$this->webconfig['weburl']);
            $orders['goodsinfo'][$key]['th_order_id'] = Db::name('th_apply')->where('order_id',$orders['id'])->where('orgoods_id',$val['id'])->order('id DESC')->value('id');
        }

		$orders['logistics'] = "";

        if($orders['fh_status'] == 1){
            $order_wulius = Db::name('order_wuliu')->alias('a')->field('a.id,a.psnum,b.log_name,b.telephone,b.kdniao_code')->join('sp_logistics b','a.ps_id = b.id','LEFT')->where('a.order_id',$orders['id'])->find();
            $orders['wulius'] = $order_wulius;
			$orders['logistics'] = $order_wulius['log_name'];
			$orders['logistics_no'] = $order_wulius['psnum'];
        }else{
            $orders['wulius'] = '-';
            $orders['logistics'] = '-';
            $orders['logistics_no'] = '-';
        }

		//支付方式
		$orders['paytype'] = get_pay_type($orders['zf_type']);

	    datamsg(200,'获取订单详情成功',set_lang($orders));
    }
    
    
    //获取退换货订单详情接口
    public function thorderinfo(){
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess'],$tokenRes['data']);
        }else{
            $userId = $tokenRes['user_id'];
        }

        $thOrderId = input('post.th_order_id');
        if(!$thOrderId){
            datamsg(400,'缺少退换订单编号');
        }

        //如果用户是商家
        $member_data = Db::name('member')->find($userId);
        if(!$member_data['shop_id'] ){
            datamsg(400,'商家账号信息有误');
        }
        //这个退换货对应的用户信息
        $th_user = Db::name('th_apply')->where('id',$thOrderId)->field('user_id')->find();
        if(empty($th_user)){
            datamsg(400,'找不到相关退换货信息');
        }else{
            $userId = $th_user['user_id'];
        }

        $applys = Db::name('th_apply')
                    ->where('id',$thOrderId)
                    ->where('user_id',$userId)
                    ->field('id,th_number,thfw_id,sh_type,th_reason,th_content,tui_price,tui_num,contacts,telephone,shengshiqu,address,orgoods_id,order_id,apply_status,apply_time,agree_time,refuse_time,refuse_reason,dcfh_status,dcfh_time,sh_status,sh_time,fh_status,fh_time,shou_status,shou_time,che_time,com_time,check_timeout,shoptui_timeout,yhfh_timeout,yhshou_timeout,shop_id')
                    ->find();
        if(!$applys){
            datamsg(400,'找不到相关退换货信息');
        }
        $ordouts = Db::name('order_timeout')->where('id',1)->find();

        $webconfig = $this->webconfig;
        if($applys['apply_status'] == 0 && $applys['check_timeout'] <= time()){
            // 启动事务
            Db::startTrans();
            try{
                if($applys['thfw_id'] == 1){
                    $shoptui_timeout = time()+$ordouts['shoptui_timeout']*24*3600;
                    Db::name('th_apply')->update(array('apply_status'=>1,'agree_time'=>time(),'shoptui_timeout'=>$shoptui_timeout,'id'=>$applys['id']));
                }elseif(in_array($applys['thfw_id'], array(2,3))){
                    $yhfh_timeout = time()+$ordouts['yhfh_timeout']*24*3600;
                    Db::name('th_apply')->update(array('apply_status'=>1,'agree_time'=>time(),'yhfh_timeout'=>$yhfh_timeout,'id'=>$applys['id']));
                }

                if(in_array($applys['thfw_id'], array(1,2))){
                    $th_status = 2;
                }elseif($applys['thfw_id'] == 3){
                    $th_status = 6;
                }

                if(!empty($th_status)){
                    Db::name('order_goods')->update(array('th_status'=>$th_status,'id'=>$applys['orgoods_id']));
                }

                // 提交事务
                Db::commit();
                $applys = Db::name('th_apply')
                            ->where('th_number',$th_number)
                            ->where('user_id',$userId)
                            ->field('id,th_number,thfw_id,sh_type,th_reason,th_content,tui_price,tui_num,contacts,telephone,shengshiqu,address,orgoods_id,order_id,apply_status,apply_time,agree_time,refuse_time,refuse_reason,dcfh_status,dcfh_time,sh_status,sh_time,fh_status,fh_time,shou_status,shou_time,che_time,com_time,check_timeout,shoptui_timeout,yhfh_timeout,yhshou_timeout,shop_id')
                            ->find();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                datamsg(400,'系统错误，请重试');
            }
        }elseif($applys['thfw_id'] == 1 && $applys['apply_status'] == 1 && $applys['shoptui_timeout'] <= time()){
            $orgoods = Db::name('order_goods')->where('id',$applys['orgoods_id'])->field('goods_id,goods_attr_id,hd_type,hd_id')->find();
            if($orgoods){
                // 启动事务
                Db::startTrans();
                try{
                    Db::name('th_apply')->update(array('apply_status'=>3,'com_time'=>time(),'id'=>$applys['id']));
                    Db::name('order_goods')->update(array('th_status'=>4,'id'=>$applys['orgoods_id']));
                    $ordergoods = Db::name('order_goods')
                                    ->where('id','neq',$applys['orgoods_id'])
                                    ->where('order_id',$applys['order_id'])
                                    ->where('th_status','in','0,1,2,3,5,6,7,8')
                                    ->field('id')
                                    ->find();
                    if(!$ordergoods){
                        $orders = Db::name('order')->where('id',$applys['order_id'])->find();
                        if($orders){
                            Db::name('order')->where('id',$applys['order_id'])->update(array('order_status'=>2,'shouhou'=>0,'can_time'=>time()));
                            if($orders['coupon_id']){
                                Db::name('member_coupon')
                                  ->where('user_id',$orders['user_id'])
                                  ->where('coupon_id',$orders['coupon_id'])
                                  ->where('is_sy',1)
                                  ->where('shop_id',$orders['shop_id'])
                                  ->update(array('is_sy'=>0));
                            }
                        }
                    }else{
                        $ordergoodres = Db::name('order_goods')
                                          ->where('id','neq',$applys['orgoods_id'])
                                          ->where('order_id',$applys['order_id'])
                                          ->where('th_status','in','1,2,3,5,6,7')
                                          ->field('id')
                                          ->find();

                        if($ordergoodres){
                            $shouhou = 1;
                        }else{
                            $shouhou = 0;
                        }

                        if($shouhou == 0){
                            $orders = Db::name('order')->where('id',$applys['order_id'])->find();
                            if($orders){
                                $zdsh_time = time()+$ordouts['zdqr_sh_time']*24*3600;
                                Db::name('order')->where('id',$applys['order_id'])->update(array('shouhou'=>0,'zdsh_time'=>$zdsh_time));
                            }
                        }
                    }

                    if(in_array($orgoods['hd_type'],array(0,2,3))){
                        $prokc = Db::name('product')->where('goods_id',$orgoods['goods_id'])->where('goods_attr',$orgoods['goods_attr_id'])->find();
                        if($prokc){
                            Db::name('product')->where('goods_id',$orgoods['goods_id'])->where('goods_attr',$orgoods['goods_attr_id'])->setInc('goods_number', $applys['tui_num']);
                        }
                    }elseif($orgoods['hd_type'] == 1){
                        $hdactivitys = Db::name('seckill')->where('id',$orgoods['hd_id'])->find();
                        if($hdactivitys){
                            Db::name('seckill')->where('id',$orgoods['hd_id'])->setInc('stock',$applys['tui_num']);
                            Db::name('seckill')->where('id',$orgoods['hd_id'])->setDec('sold',$applys['tui_num']);
                        }
                    }

                    // 提交事务
                    Db::commit();
                    $applys = Db::name('th_apply')
                                ->where('th_number',$th_number)
                                ->where('user_id',$userId)
                                ->field('id,th_number,thfw_id,sh_type,th_reason,th_content,tui_price,tui_num,contacts,telephone,shengshiqu,address,orgoods_id,order_id,apply_status,apply_time,agree_time,refuse_time,refuse_reason,dcfh_status,dcfh_time,sh_status,sh_time,fh_status,fh_time,shou_status,shou_time,che_time,com_time,check_timeout,shoptui_timeout,yhfh_timeout,yhshou_timeout,shop_id')
                                ->find();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    datamsg(400,'系统错误，请重试');
                }
            }
        }elseif($applys['thfw_id'] == 2 && $applys['apply_status'] == 1 && $applys['dcfh_status'] == 1 && $applys['sh_status'] == 1 && $applys['shoptui_timeout'] <= time()){
            $orgoods = Db::name('order_goods')->where('id',$applys['orgoods_id'])->field('goods_id,goods_attr_id,hd_type,hd_id')->find();
            if($orgoods){
                // 启动事务
                Db::startTrans();
                try{
                    Db::name('th_apply')->update(array('apply_status'=>3,'com_time'=>time(),'id'=>$applys['id']));
                    Db::name('order_goods')->update(array('th_status'=>4,'id'=>$applys['orgoods_id']));
                    $ordergoods = Db::name('order_goods')
                                    ->where('id','neq',$applys['orgoods_id'])
                                    ->where('order_id',$applys['order_id'])
                                    ->where('th_status','in','0,1,2,3,5,6,7,8')
                                    ->field('id')
                                    ->find();
                    if(!$ordergoods){
                        $orders = Db::name('order')->where('id',$applys['order_id'])->find();
                        if($orders){
                            Db::name('order')->where('id',$applys['order_id'])->update(array('order_status'=>2,'shouhou'=>0,'can_time'=>time()));
                            if($orders['coupon_id']){
                                Db::name('member_coupon')
                                  ->where('user_id',$orders['user_id'])
                                  ->where('coupon_id',$orders['coupon_id'])
                                  ->where('is_sy',1)
                                  ->where('shop_id',$orders['shop_id'])
                                  ->update(array('is_sy'=>0));
                            }
                        }
                    }else{
                        $ordergoodres = Db::name('order_goods')->where('id','neq',$applys['orgoods_id'])->where('order_id',$applys['order_id'])->where('th_status','in','1,2,3,5,6,7')->field('id')->find();

                        if($ordergoodres){
                            $shouhou = 1;
                        }else{
                            $shouhou = 0;
                        }

                        if($shouhou == 0){
                            $orders = Db::name('order')->where('id',$applys['order_id'])->find();
                            if($orders){
                                $zdsh_time = time()+$ordouts['zdqr_sh_time']*24*3600;
                                Db::name('order')->where('id',$applys['order_id'])->update(array('shouhou'=>0,'zdsh_time'=>$zdsh_time));
                            }
                        }
                    }

                    if(in_array($orgoods['hd_type'],array(0,2,3))){
                        $prokc = Db::name('product')->where('goods_id',$orgoods['goods_id'])->where('goods_attr',$orgoods['goods_attr_id'])->find();
                        if($prokc){
                            Db::name('product')->where('goods_id',$orgoods['goods_id'])->where('goods_attr',$orgoods['goods_attr_id'])->setInc('goods_number', $applys['tui_num']);
                        }
                    }elseif($orgoods['hd_type'] == 1){
                        $hdactivitys = Db::name('seckill')->where('id',$orgoods['hd_id'])->find();
                        if($hdactivitys){
                            Db::name('seckill')->where('id',$orgoods['hd_id'])->setInc('stock',$applys['tui_num']);
                            Db::name('seckill')->where('id',$orgoods['hd_id'])->setDec('sold',$applys['tui_num']);
                        }
                    }

                    // 提交事务
                    Db::commit();
                    $applys = Db::name('th_apply')
                                ->where('th_number',$th_number)
                                ->where('user_id',$userId)
                                ->field('id,th_number,thfw_id,sh_type,th_reason,th_content,tui_price,tui_num,contacts,telephone,shengshiqu,address,orgoods_id,order_id,apply_status,apply_time,agree_time,refuse_time,refuse_reason,dcfh_status,dcfh_time,sh_status,sh_time,fh_status,fh_time,shou_status,shou_time,che_time,com_time,check_timeout,shoptui_timeout,yhfh_timeout,yhshou_timeout,shop_id')
                                ->find();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    datamsg(400,'系统错误，请重试');
                }
            }
        }elseif(in_array($applys['thfw_id'], array(2,3)) && $applys['apply_status'] == 1 && $applys['dcfh_status'] == 0 && $applys['yhfh_timeout'] <= time()){
            $orders = Db::name('order')
                        ->where('id',$applys['order_id'])
                        ->where('state',1)
                        ->where('fh_status',1)
                        ->field('id')
                        ->find();
            if($orders){
                // 启动事务
                Db::startTrans();
                try{
                    Db::name('th_apply')->update(array('apply_status'=>4,'che_time'=>time(),'id'=>$applys['id']));
                    Db::name('order_goods')->update(array('th_status'=>0,'id'=>$applys['orgoods_id']));

                    $ordergoods = Db::name('order_goods')
                                    ->where('id','neq',$applys['orgoods_id'])
                                    ->where('order_id',$applys['order_id'])
                                    ->where('th_status','in','1,2,3,5,6,7')
                                    ->field('id')
                                    ->find();

                    if($ordergoods){
                        $shouhou = 1;
                    }else{
                        $shouhou = 0;
                    }

                    if($shouhou == 0){
                        $orders = Db::name('order')->where('id',$applys['order_id'])->find();
                        if($orders){
                            $zdsh_time = time()+$ordouts['zdqr_sh_time']*24*3600;
                            Db::name('order')->where('id',$applys['order_id'])->update(array('shouhou'=>0,'zdsh_time'=>$zdsh_time));
                        }
                    }

                    // 提交事务
                    Db::commit();
                    $applys = Db::name('th_apply')
                                ->where('th_number',$th_number)
                                ->where('user_id',$userId)
                                ->field('id,th_number,thfw_id,sh_type,th_reason,th_content,tui_price,tui_num,contacts,telephone,shengshiqu,address,orgoods_id,order_id,apply_status,apply_time,agree_time,refuse_time,refuse_reason,dcfh_status,dcfh_time,sh_status,sh_time,fh_status,fh_time,shou_status,shou_time,che_time,com_time,check_timeout,shoptui_timeout,yhfh_timeout,yhshou_timeout,shop_id')
                                ->find();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    datamsg(400,'系统错误，请重试');
                }
            }
        }elseif($applys['thfw_id'] == 3 && $applys['apply_status'] == 1 && $applys['dcfh_status'] == 1 && $applys['sh_status'] == 1 && $applys['fh_status'] == 1 && $applys['shou_status'] == 0 && $applys['yhshou_timeout'] <= time()){
            // 启动事务
            Db::startTrans();
            try{
                Db::name('th_apply')->update(array('shou_status'=>1,'apply_status'=>3,'shou_time'=>time(),'com_time'=>time(),'id'=>$applys['id']));
                Db::name('order_goods')->update(array('th_status'=>8,'id'=>$applys['orgoods_id']));

                $ordergoods = Db::name('order_goods')->where('id','neq',$applys['orgoods_id'])->where('order_id',$applys['order_id'])->where('th_status','in','1,2,3,5,6,7')->field('id,th_status')->find();

                if($ordergoods){
                    $shouhou = 1;
                }else{
                    $shouhou = 0;
                }

                if($shouhou == 0){
                    $orders = Db::name('order')->where('id',$applys['order_id'])->find();
                    if($orders){
                        $zdsh_time = time()+$ordouts['zdqr_sh_time']*24*3600;
                        Db::name('order')->where('id',$applys['order_id'])->update(array('shouhou'=>0,'zdsh_time'=>$zdsh_time));
                    }
                }

                // 提交事务
                Db::commit();
                $applys = Db::name('th_apply')
                            ->where('th_number',$th_number)
                            ->where('user_id',$userId)
                            ->field('id,th_number,thfw_id,sh_type,th_reason,th_content,tui_price,tui_num,contacts,telephone,shengshiqu,address,orgoods_id,order_id,apply_status,apply_time,agree_time,refuse_time,refuse_reason,dcfh_status,dcfh_time,sh_status,sh_time,fh_status,fh_time,shou_status,shou_time,che_time,com_time,check_timeout,shoptui_timeout,yhfh_timeout,yhshou_timeout,shop_id')
                            ->find();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                datamsg(400,'系统错误，请重试');
            }
        }


        $orders = Db::name('order')
                    ->where('id',$applys['order_id'])
                    ->where('state',1)
                    ->where('user_id',$userId)
                    ->field('id,ordernumber,fh_status')
                    ->find();
        if(!$orders){
            datamsg(400,'订单信息错误');
        }
        $orgoods = Db::name('order_goods')
                     ->where('id',$applys['orgoods_id'])
                     ->where('order_id',$orders['id'])
                     ->where('th_status','neq',0)
                     ->field('id,goods_id,goods_name,thumb_url,goods_attr_str,price,goods_num,order_id')
                     ->find();
        if(!$orgoods){
            datamsg(400,'订单商品信息错误');
        }
        $orgoods['ordernumber'] = $orders['ordernumber'];
        $orgoods['fh_status'] = $orders['fh_status'];

        $orgoods['thumb_url'] = url_format($orgoods['thumb_url'],$webconfig['weburl']);
        $applys['apply_time'] = date('Y-m-d H:i:s',$applys['apply_time']);
        if(!empty($applys['agree_time'])){
            $applys['agree_time'] = date('Y-m-d H:i:s',$applys['agree_time']);
        }

        if(!empty($applys['refuse_time'])){
            $applys['refuse_time'] = date('Y-m-d H:i:s',$applys['refuse_time']);
        }

        if(!empty($applys['dcfh_time'])){
            $applys['dcfh_time'] = date('Y-m-d H:i:s',$applys['dcfh_time']);
        }

        if(!empty($applys['sh_time'])){
            $applys['sh_time'] = date('Y-m-d H:i:s',$applys['sh_time']);
        }

        if(!empty($applys['fh_time'])){
            $applys['fh_time'] = date('Y-m-d H:i:s',$applys['fh_time']);
        }

        if(!empty($applys['shou_time'])){
            $applys['shou_time'] = date('Y-m-d H:i:s',$applys['shou_time']);
        }

        if(!empty($applys['che_time'])){
            $applys['che_time'] = date('Y-m-d H:i:s',$applys['che_time']);
        }

        if(!empty($applys['com_time'])){
            $applys['com_time'] = date('Y-m-d H:i:s',$applys['com_time']);
        }

        $applys['thfw'] = Db::name('thcate')->where('id',$applys['thfw_id'])->value('cate_name');

        if($applys['apply_status'] == 0){
            $applys['zhuangtai'] = '待商家同意';
            $applys['filter'] = 1;
            $applys['sycheck_timeout'] = time2string($applys['check_timeout']-time());
        }elseif(in_array($applys['apply_status'], array(1,3))){
            switch ($applys['thfw_id']){
                case 1:
                    if($applys['apply_status'] == 1){
                        $applys['zhuangtai'] = '商家已同意（退款中）';
                        $applys['filter'] = 2;
                        $applys['syshoptui_timeout'] = time2string($applys['shoptui_timeout']-time());
                    }elseif($applys['apply_status'] == 3){
                        $applys['zhuangtai'] = '退款已完成';
                        $applys['filter'] = 3;
                    }
                    break;
                case 2:
                    if($applys['apply_status'] == 1){
                        if($applys['dcfh_status'] == 0){
                            $applys['zhuangtai'] = '商家已同意（填写退货物流信息）';
                            $applys['filter'] = 4;
                            $applys['syyhfh_timeout'] = time2string($applys['yhfh_timeout']-time());
                        }elseif($applys['dcfh_status'] == 1 && $applys['sh_status'] == 0){
                            $applys['zhuangtai'] = '等待商家确认收货（退货退款中）';
                            $applys['filter'] = 5;
                        }elseif($applys['dcfh_status'] == 1 && $applys['sh_status'] == 1){
                            $applys['zhuangtai'] = '商家已收货（退货退款中）';
                            $applys['filter'] = 6;
                            $applys['syshoptui_timeout'] = time2string($applys['shoptui_timeout']-time());
                        }
                    }elseif($applys['apply_status'] == 3){
                        $applys['zhuangtai'] = '退货退款已完成';
                        $applys['filter'] = 7;
                    }
                    break;
                case 3:
                    if($applys['apply_status'] == 1){
                        if($applys['dcfh_status'] == 0){
                            $applys['zhuangtai'] = '商家已同意（填写退货物流信息）';
                            $applys['filter'] = 8;
                            $applys['syyhfh_timeout'] = time2string($applys['yhfh_timeout']-time());
                        }elseif($applys['dcfh_status'] == 1 && $applys['sh_status'] == 0){
                            $applys['zhuangtai'] = '等待商家确认收货（换货中）';
                            $applys['filter'] = 9;
                        }elseif($applys['dcfh_status'] == 1 && $applys['sh_status'] == 1 && $applys['fh_status'] == 0){
                            $applys['zhuangtai'] = '商家已收货（换货中）';
                            $applys['filter'] = 10;
                        }elseif($applys['dcfh_status'] == 1 && $applys['sh_status'] == 1 && $applys['fh_status'] == 1){
                            $applys['zhuangtai'] = '商家已发货（换货中）';
                            $applys['filter'] = 11;
                            $applys['syyhshou_timeout'] = time2string($applys['yhshou_timeout']-time());
                        }
                    }elseif($applys['apply_status'] == 3){
                        $applys['zhuangtai'] = '换货已完成';
                        $applys['filter'] = 12;
                    }
                    break;
            }
        }elseif($applys['apply_status'] == 2){
            $applys['zhuangtai'] = '商家已拒绝';
            $applys['filter'] = 13;
        }elseif($applys['apply_status'] == 4){
            $applys['zhuangtai'] = '已撤销';
            $applys['filter'] = 14;
        }

        $thpicres = Db::name('thapply_pic')->where('th_id',$applys['id'])->select();

        if(in_array($applys['thfw_id'],array(2,3)) && $applys['apply_status'] == 1){
            $shopdzs = Db::name('shop_shdz')->where('shop_id',$applys['shop_id'])->find();
        }else{
            $shopdzs = array();
        }

        $tuiwulius = array();
        if(in_array($applys['thfw_id'], array(2,3)) && $applys['apply_status'] == 1 && $applys['dcfh_status'] == 1){
            $tuiwulius = Db::name('tui_wuliu')->where('th_id',$applys['id'])->find();
        }

        $wulius = array();
        if($applys['thfw_id'] == 3 && $applys['apply_status'] == 1 && $applys['dcfh_status'] == 1 && $applys['sh_status'] == 1 && $applys['fh_status'] == 1){
            $wulius = Db::name('huan_wuliu')->alias('a')->field('a.*,b.log_name,b.telephone')->join('sp_logistics b','a.ps_id = b.id','LEFT')->where('a.th_id',$applys['id'])->find();
        }

        $thapplyinfo = array('orgoods'=>$orgoods,'applys'=>$applys,'thpicres'=>$thpicres,'shopdzs'=>$shopdzs,'tuiwulius'=>$tuiwulius,'wulius'=>$wulius);
        datamsg(200,'获取退换货申请信息成功',set_lang($thapplyinfo));
    }

    
    //支付获取订单信息
    public function zhifuorder(){
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{
		    $userId = $tokenRes['user_id'];
	    }

        if(!input('post.order_nums/a') && !is_array(input('post.order_nums/a'))){
	        datamsg(400,'缺少订单号',array('status'=>400));
        }

        if(!input('post.zf_type') && !in_array(input('post.zf_type'), array(1,2,3,5))){
	        datamsg(400,'支付方式参数错误',array('status'=>400));
        }

        $zf_type = input('post.zf_type');
        $order_nums = input('post.order_nums/a');
        $order_nums = array_unique($order_nums);

        $total_price = 0;
        $orderids = array();
        $outarr = array();

        $orderGoodsModel = new OrderGoodsModel();

        foreach ($order_nums as $v){
            $orders = Db::name('order')->where('ordernumber',$v)->where('user_id',$userId)->where('state',0)->where('fh_status',0)->where('order_status',0)->where('is_show',1)->field('id,total_price,shop_id,time_out')->find();
            if(!$orders){
	            datamsg(400,'订单信息错误，操作失败',array('status'=>400));
            }

            if($orders['time_out'] > time()){
                $total_price+=$orders['total_price'];
                $orderids[] = $orders['id'];
                $outarr[] = $orders['time_out'];
                $orderGoodsList = $orderGoodsModel->getOrderGoods($orders['id']);
                foreach ($orderGoodsList as $ok => $ov){
                    $ruinfo = array('id'=>$ov['goods_id'],'shop_id'=>$ov['shop_id']);
                    $commonModel = new CommonModel();
                    $activity = $commonModel->getActivityInfo($ruinfo);
                    $goodsModel = new GoodsModel();
                    if($activity){
                        if($activity['ac_type'] == 1){
                            // 秒杀商品限购判断
                            $hasBuy = $orderGoodsModel->getUserOrderGoodsCount($userId,$ov['goods_id'],'seckill');
                            $hasBuyStr = $hasBuy > 0 ? '，'.lang('您已经购买过').$hasBuy.lang('件') : '';
                            if($ov['goods_num']+$hasBuy > $activity['xznum']){
                                datamsg(400,$ov['goods_name'].'-'.lang('限购').$activity['xznum'].lang('件').$hasBuyStr);
                            }

                            $stock = $goodsModel->getGoodsOptionStock($ov['goods_id'],$ov['goods_attr_id'],'seckill');
                        }
                        if($activity['ac_type'] == 2){
                            // 积分换购商品限购判断
                            $hasBuy = $orderGoodsModel->getUserOrderGoodsCount($userId,$ov['goods_id'],'integral');
                            $hasBuyStr = $hasBuy > 0 ? '，'.lang('您已经购买过').$hasBuy.lang('件') : '';
                            if($ov['goods_num']+$hasBuy > $activity['xznum']){
                                datamsg(400,$ov['goods_name'].'-'.lang('限购').$activity['xznum'].lang('件').$hasBuyStr);
                            }

                            $stock = $goodsModel->getGoodsOptionStock($ov['goods_id'],$ov['goods_attr_id'],'integral');
                        }
                        if($activity['ac_type'] == 3){
                            datamsg(400,'存在拼团商品，确认订单失败');
                        }
                    }else{
                        $stock = $goodsModel->getGoodsOptionStock($ov['goods_id'],$ov['goods_attr_id']);
                    }

                    if($ov['goods_num'] <= 0 || $ov['goods_num'] > $stock){
                        datamsg(400,$ov['goods_name'].lang('库存不足'));
                    }
                    unset($stock);
                    unset($hasBuy);
                }
            }else{
	            datamsg(400,'订单已过期，操作失败',array('status'=>400));
            }

        }

        $order_number = 'Z'.date('YmdHis').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
        $dingdan = Db::name('order_zong')->where('order_number',$order_number)->find();
        if(!$dingdan){
            $datainfo = array();
            $datainfo['order_number'] = $order_number;
            $datainfo['total_price'] = $total_price;
            $datainfo['state'] = 0;
            $datainfo['zf_type'] = 0;
            $datainfo['user_id'] = $userId;
            $datainfo['addtime'] = time();
            $datainfo['time_out'] = min($outarr);

            // 启动事务
            Db::startTrans();
            try{
                $zong_id = Db::name('order_zong')->insertGetId($datainfo);
                if($zong_id){
                    foreach ($orderids as $v2){
                        Db::name('order')->update(array('zong_id'=>$zong_id,'id'=>$v2));
                    }
                }
                // 提交事务
                Db::commit();
                $orderinfos = array('order_number'=>$order_number,'zf_type'=>$zf_type);
	            datamsg(200,'获取订单信息成功',set_lang($orderinfos));
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
	            datamsg(400,'获取订单信息失败',array('status'=>400));
            }
        }else{
	        datamsg(400,'系统错误，请重试',array('status'=>400));
        }
    }

    
    
    //确认收货
    public function qrshouhuo(){
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{
		    $userId = $tokenRes['user_id'];
	    }

        if(input('post.order_num')){
            $order_num = input('post.order_num');

            $orders = Db::name('order')->where('ordernumber',$order_num)->where('user_id',$userId)->where('state',1)->where('fh_status',1)->where('order_status',0)->where('is_show',1)->field('id,total_price,shop_id,shouhou,user_id')->find();
            if($orders){
                //if($orders['shouhou'] == 0){
                    // 启动事务
                    Db::startTrans();
                    try{
                        Db::name('order')->where('id',$orders['id'])->update(array('order_status'=>1,'coll_time'=>time()));

                        //分销：成为下级条件-首次下单，自动确认收货，绑定上下级关系
                        $distrib = new DistributionCommon();
                        $distrib->bindDistribUser($userId, 2);

                        //订单完成，确认收货了就开始计算佣金
                        $distrib->commissionSettlement($orders['id']);

                        $goodinfos = Db::name('order_goods')->where('order_id',$orders['id'])->field('id,goods_id,goods_attr_id,goods_num,th_status,shop_id')->select();
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
						$num1 = sprintf("%.2f",$orders['total_price']*($num0/100));
						$this->addIntegral($userId,$num1,8,$orders['id']);


						//分销
						$distributions = Db::name('distribution')->where('id',1)->find();
						$shops = Db::name('shops')->where('id',$orders['shop_id'])->field('id,indus_id,fenxiao')->find();
						Db::name('order')->where('id',$orders['id'])->update(array('dakuan_status'=>1,'dakuan_time'=>time()));
						$total_price = $orders['total_price'];

						//if($distributions['is_open'] == 1){
						if($distributions['is_open'] == 1 && $shops['fenxiao'] == 1){
						    // 佣金计算
						    //if($total_price >= 10){
						        $levelinfos = Db::name('member')->where('id',$orders['user_id'])->field('id,one_level,two_level')->find();
						        if($levelinfos['one_level']){
                                    $one_wallets = Db::name('wallet')->where('user_id',$levelinfos['one_level'])->find();
						            if($one_wallets){
						                $onefen_price = sprintf("%.2f",$total_price*($distributions['one_profit']/100));
						                Db::name('wallet')->where('id',$one_wallets['id'])->setInc('price', $onefen_price);
						                Db::name('detail')->insert(array('de_type'=>1,'sr_type'=>1,'price'=>$onefen_price,'order_type'=>1,'order_id'=>$orders['id'],'user_id'=>$levelinfos['one_level'],'wat_id'=>$one_wallets['id'],'time'=>time()));
						                Db::name('order')->where('id',$orders['id'])->update(array('onefen_id'=>$levelinfos['one_level'],'onefen_price'=>$onefen_price));
						                $total_price = $total_price-$onefen_price;
						            }
						        }
//
						        if($levelinfos['two_level']){
						            $two_wallets = Db::name('wallet')->where('user_id',$levelinfos['two_level'])->find();
						            if($two_wallets){
						                $twofen_price = sprintf("%.2f",$total_price*($distributions['two_profit']/100));
						                Db::name('wallet')->where('id',$two_wallets['id'])->setInc('price', $twofen_price);
						                Db::name('detail')->insert(array('de_type'=>1,'sr_type'=>1,'price'=>$twofen_price,'order_type'=>1,'order_id'=>$orders['id'],'user_id'=>$levelinfos['two_level'],'wat_id'=>$two_wallets['id'],'time'=>time()));
						                Db::name('order')->where('id',$orders['id'])->update(array('twofen_id'=>$levelinfos['two_level'],'twofen_price'=>$twofen_price));
						                $total_price = $total_price-$twofen_price;
						            }
						        }


                                //判断平台是否安装了商家等级插件
                                $is_plugin = Db::name("plugin")->where(["name"=>"shoplevel","status"=>1,"isclose"=>1])->find();
                                if($is_plugin){
                                    //查找用户升级商家等级的最高级别
                                    $plugin_shoplevel_user = Db::name("plugin_shoplevel_user")->where(['shop_id'=>$orders['shop_id'],"admin_check"=>3,"status"=>1])->order("shoplevel_id DESC")->find();
                                    if($plugin_shoplevel_user){
                                        $bond_total_price = $total_price * ($plugin_shoplevel_user['rate'] / 100);
                                        Db::name('shop_wallet')->where('shop_id',$orders['shop_id'])->setInc('price',$bond_total_price);
                                        $watBondId = Db::name('shop_wallet')->where('shop_id',$orders['shop_id'])->value('id');
                                        Db::name('shop_detail')->insert(array('de_type'=>1,'sr_type'=>1,'price'=>$bond_total_price,'order_type'=>1,'order_id'=>$orders['id'],'shop_id'=>$orders['shop_id'],'wat_id'=>$watBondId,'time'=>time()));
                                    }
                                }

						        Db::name('shop_wallet')->where('shop_id',$orders['shop_id'])->setInc('price',$total_price);
                                $watId = Db::name('shop_wallet')->where('shop_id',$orders['shop_id'])->value('id');
                                Db::name('shop_detail')->insert(array('de_type'=>1,'sr_type'=>1,'price'=>$total_price,'order_type'=>6,'order_id'=>$orders['id'],'shop_id'=>$orders['shop_id'],'wat_id'=>$watId,'time'=>time()));
						}

                        // 提交事务
                        Db::commit();
                        datamsg(200,'确认收货成功',array('status'=>200));
                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                        datamsg(400,'确认收货失败',array('status'=>400));
                    }
            }else{
                datamsg(400,'找不到相关类型订单信息',array('status'=>400));
            }
        }else{
            datamsg(400,'缺少订单号',array('status'=>400));
        }
    }
    
}
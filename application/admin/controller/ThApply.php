<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class ThApply extends Common{

    public function lst(){
        $shopId = session('shop_id');

        $filter = input('filter');
        if(!$filter || !in_array($filter, array(1,2,3,4,5,10))){
            $filter = 10;
        }

        switch ($filter){
            //待平台审核
            case 1:
                $where = array('a.shop_id'=>$shopId,'a.apply_status'=>0);
                break;
            //平台已同意
            case 2:
                $where = array('a.shop_id'=>$shopId,'a.apply_status'=>1);
                break;
            //平台已拒绝
            case 3:
                $where = array('a.shop_id'=>$shopId,'a.apply_status'=>2);
                break;
            //已完成
            case 5:
                $where = array('a.shop_id'=>$shopId,'a.apply_status'=>3);
                break;
            //用户已撤销
            case 4:
                $where = array('a.shop_id'=>$shopId,'a.apply_status'=>4);
                break;
            //全部
            case 10:
                $where = array('a.shop_id'=>$shopId);
                break;
        }

        $list = Db::name('th_apply')->alias('a')->field('a.id,a.th_number,a.tui_price,apply_status,apply_time,b.user_name,b.phone,c.cate_name')->join('sp_member b','a.user_id = b.id','LEFT')->join('sp_thcate c','a.thfw_id = c.id','LEFT')->where($where)->order('a.apply_time desc')->paginate(25);
        $page = $list->render();

        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }

        $this->assign('filter',$filter);
        $this->assign('pnum',$pnum);
        $this->assign('page',$page);// 赋值分页输出
        $this->assign('list',$list);// 赋值数据集
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }

    //退换申请详情
    public function info(){
        if(input('th_id')){
            $shopId = session('shop_id');
            $th_id = input('th_id');
            //$applys = Db::name('th_apply')->alias('a')->field('a.*,b.user_name,b.phone,c.cate_name')->join('sp_member b','a.user_id = b.id','LEFT')->join('sp_thcate c','a.thfw_id = c.id','LEFT')->where('a.id',$th_id)->where('a.shop_id',$shopId)->find();
            $applys = Db::name('th_apply')->alias('a')->field('a.*,b.user_name,b.phone,c.cate_name')->join('sp_member b','a.user_id = b.id','LEFT')->join('sp_thcate c','a.thfw_id = c.id','LEFT')->where('a.id',$th_id)->find();
            if($applys){
                $tuiwulius = array();
                if(in_array($applys['thfw_id'], array(2,3)) && $applys['dcfh_status'] == 1){
                    $tuiwulius = Db::name('tui_wuliu')->where('th_id',$applys['id'])->find();
                }
                $wulius = array();
                if($applys['thfw_id'] == 3 && $applys['dcfh_status'] == 1 && $applys['sh_status'] == 1){
                    $wulius = Db::name('huan_wuliu')->alias('a')->field('a.*,b.log_name,b.telephone')->join('sp_logistics b','a.ps_id = b.id','LEFT')->where('a.th_id',$th_id)->find();
                }
                $order_goods = Db::name('order_goods')->where('id',$applys['orgoods_id'])->find();
                $thpicres = Db::name('thapply_pic')->where('th_id',$th_id)->select();
                $psres = Db::name('logistics')->where('is_show',1)->field('id,log_name')->order('sort asc')->select();

                //$orders = Db::name('order')->alias('a')->field('a.*,b.user_name,b.phone,c.pro_name,d.city_name,p.area_name')->join('sp_member b','a.user_id = b.id','LEFT')->join('sp_province c','a.pro_id = c.id','LEFT')->join('sp_city d','a.city_id = d.id','LEFT')->join('sp_area p','a.area_id = p.id','LEFT')->where('a.id',$applys['order_id'])->where('a.shop_id',$shopId)->find();
                $orders = Db::name('order')->alias('a')->field('a.*,b.user_name,b.phone,c.pro_name,d.city_name,p.area_name')->join('sp_member b','a.user_id = b.id','LEFT')->join('sp_province c','a.pro_id = c.id','LEFT')->join('sp_city d','a.city_id = d.id','LEFT')->join('sp_area p','a.area_id = p.id','LEFT')->where('a.id',$applys['order_id'])->find();
                if($orders['state'] == 1 && $orders['fh_status'] == 0 && $orders['order_status'] == 0){
                    $orders['zhuangtai'] = 1;
                }elseif($orders['state'] == 1 && $orders['fh_status'] == 1 && $orders['order_status'] == 0){
                    $orders['zhuangtai'] = 2;
                }elseif($orders['state'] == 1 && $orders['fh_status'] == 1 && $orders['order_status'] == 1){
                    $orders['zhuangtai'] = 3;
                }elseif($orders['state'] == 0 && $orders['fh_status'] == 0 && $orders['order_status'] == 0){
                    $orders['zhuangtai'] = 4;
                }elseif($orders['order_status'] == 2){
                    $orders['zhuangtai'] = 5;
                }
                //print_r($orders);exit();
                $this->assign('applys',$applys);
                $this->assign('tuiwulius',$tuiwulius);
                $this->assign('order_goods',$order_goods);
                $this->assign('thpicres',$thpicres);
                $this->assign('wulius',$wulius);
                $this->assign('psres',$psres);
                $this->assign('orders',$orders);
                return $this->fetch();
            }else{
                $this->error('订单信息错误');
            }
        }else{
            $this->error('缺少订单信息');
        }
    }

    //平台审核退换申请
    public function checked(){
        if(request()->isPost()){
            if(input('post.th_id')){
                if(input('post.apply_status') && in_array(input('post.apply_status'), array(1,2))){
                    $shopId = session('shop_id');
                    $th_id = input('post.th_id');
                    $apply_status = input('post.apply_status');
                    $applys = Db::name('th_apply')->where('id',$th_id)->where('shop_id',$shopId)->where('apply_status',0)->where('check_timeout','gt',time())->find();
                    if($applys){
                        $ordouts = Db::name('order_timeout')->where('id',1)->find();

                        if($applys['thfw_id'] == 1){
                            $orders = Db::name('order')->where('id',$applys['order_id'])->where('state',1)->field('id,fh_status')->find();
                            if($orders['fh_status'] == 0 && $apply_status == 2){
                                $value = array('status'=>0,'mess'=>'未发货订单不允许拒绝仅退款申请');
                                return json($value);
                            }
                        }

                        switch ($apply_status){
                            case 1:
                                // 启动事务
                                Db::startTrans();
                                try{
                                    if($applys['thfw_id'] == 1){
                                        $shoptui_timeout = time()+$ordouts['shoptui_timeout']*24*3600;
                                        Db::name('th_apply')->update(array('apply_status'=>$apply_status,'agree_time'=>time(),'shoptui_timeout'=>$shoptui_timeout,'id'=>$th_id));
                                    }elseif(in_array($applys['thfw_id'], array(2,3))){
                                        $yhfh_timeout = time()+$ordouts['yhfh_timeout']*24*3600;
                                        Db::name('th_apply')->update(array('apply_status'=>$apply_status,'agree_time'=>time(),'yhfh_timeout'=>$yhfh_timeout,'id'=>$th_id));
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
                                    ys_admin_logs('审核退换订单申请','th_apply',$th_id);
                                    $value = array('status'=>1,'mess'=>'操作成功');
                                } catch (\Exception $e) {
                                    // 回滚事务
                                    Db::rollback();
                                    $value = array('status'=>0,'mess'=>'操作失败');
                                }
                                break;
                            case 2:
                                if(input('post.refuse_reason')){
                                    $refude_reason = input('post.refuse_reason');
                                    // 启动事务
                                    Db::startTrans();
                                    try{
                                        $count = Db::name('th_apply')->update(array('apply_status'=>$apply_status,'refuse_reason'=>$refude_reason,'refuse_time'=>time(),'id'=>$th_id));
                                        if(in_array($applys['thfw_id'], array(1,2))){
                                            $th_status = 3;
                                        }elseif($applys['thfw_id'] == 3){
                                            $th_status = 7;
                                        }
                                        if(!empty($th_status)){
                                            Db::name('order_goods')->update(array('th_status'=>$th_status,'id'=>$applys['orgoods_id']));
                                        }
                                        // 提交事务
                                        Db::commit();
                                        ys_admin_logs('审核退换订单申请','th_apply',$th_id);
                                        $value = array('status'=>1,'mess'=>'操作成功');
                                    } catch (\Exception $e) {
                                        // 回滚事务
                                        Db::rollback();
                                        $value = array('status'=>0,'mess'=>'操作失败');
                                    }
                                }else{
                                    $value = array('status'=>0,'mess'=>'请填写拒绝原因');
                                    return json($value);
                                }
                                break;
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'退换申请信息错误，操作失败');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'请选择同意或拒绝');
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少退换申请信息，操作失败');
            }
            return json($value);
        }
    }

    //平台确认收货
    public function qrshouhuo(){
        if(request()->isPost()){
            if(input('post.th_id')){
                //$shopId = session('shop_id');
                $th_id = input('post.th_id');
                //$applys = Db::name('th_apply')->where('id',$th_id)->where('shop_id',$shopId)->where('thfw_id','in','2,3')->where('apply_status',1)->where('dcfh_status',1)->where('sh_status',0)->find();
                $applys = Db::name('th_apply')->where('id',$th_id)->where('thfw_id','in','2,3')->where('apply_status',1)->where('dcfh_status',1)->where('sh_status',0)->find();
                if($applys){
                    $ordouts = Db::name('order_timeout')->where('id',1)->find();

                    if($applys['thfw_id'] == 2){
                        $shoptui_timeout = time()+$ordouts['shoptui_timeout']*24*3600;
                        $count = Db::name('th_apply')->update(array('sh_status'=>1,'sh_time'=>time(),'shoptui_timeout'=>$shoptui_timeout,'id'=>$th_id));
                    }elseif($applys['thfw_id'] == 3){
                        $count = Db::name('th_apply')->update(array('sh_status'=>1,'sh_time'=>time(),'id'=>$th_id));
                    }
                    if($count > 0){
                        ys_admin_logs('退换订单平台确认收货','th_apply',$th_id);
                        $value = array('status'=>1,'mess'=>'收货成功');
                    }else{
                        $value = array('status'=>0,'mess'=>'收货失败');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'退换申请信息错误，操作失败');
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少退换申请信息，操作失败');
            }
            return json($value);
        }
    }

    //平台完成退换申请
    public function complete(){
        if(request()->isPost()){
            if(input('post.th_id')){
                $shopId = session('shop_id');
                $th_id = input('post.th_id');

                $applys = Db::name('th_apply')->where(function ($query) use ($th_id,$shopId){
                    $query->where('id',$th_id)->where('shop_id',$shopId)->where('thfw_id',1)->where('apply_status',1)->where('shoptui_timeout','gt',time());
                })->whereOr(function ($query) use ($th_id,$shopId){
                    $query->where('id',$th_id)->where('shop_id',$shopId)->where('thfw_id',2)->where('apply_status',1)->where('dcfh_status',1)->where('sh_status',1)->where('shoptui_timeout','gt',time());
                })->find();

                if($applys){
                    $ordouts = Db::name('order_timeout')->where('id',1)->find();

                    $orgoods = Db::name('order_goods')->where('id',$applys['orgoods_id'])->field('goods_id,goods_attr_id,hd_type,hd_id')->find();
                    if($orgoods){
                        // 启动事务
                        Db::startTrans();
                        try{
                            Db::name('th_apply')->update(array('apply_status'=>3,'com_time'=>time(),'id'=>$th_id));
                            Db::name('order_goods')->update(array('th_status'=>4,'id'=>$applys['orgoods_id']));
                            $ordergoods = Db::name('order_goods')->where('id','neq',$applys['orgoods_id'])->where('order_id',$applys['order_id'])->where('th_status','in','0,1,2,3,5,6,7,8')->field('id,th_status,integral,hd_type')->find();
                            if(!$ordergoods){
                                $orders = Db::name('order')->where('id',$applys['order_id'])->find();
                                if($orders){
                                    Db::name('order')->where('id',$applys['order_id'])->update(array('order_status'=>2,'shouhou'=>0,'can_time'=>time()));
                                    if($orders['coupon_id']){
                                        Db::name('member_coupon')->where('user_id',$orders['user_id'])->where('coupon_id',$orders['coupon_id'])->where('is_sy',1)->where('shop_id',$orders['shop_id'])->update(array('is_sy'=>0));
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

                            if(in_array($orgoods['hd_type'],array(0,3))){
                                Db::name('product')->where('goods_id',$orgoods['goods_id'])->where('goods_attr',$orgoods['goods_attr_id'])->setInc('goods_number', $applys['tui_num']);
                            }elseif($orgoods['hd_type'] == 1){
                                Db::name('seckill')->where('id',$orgoods['hd_id'])->setInc('stock',$applys['tui_num']);
                                Db::name('seckill')->where('id',$orgoods['hd_id'])->setDec('sold',$applys['tui_num']);
                            }elseif($orgoods['hd_type'] == 2){
                                Db::name('integral_shop')->where('id',$orgoods['hd_id'])->setInc('stock',$applys['tui_num']);
                                Db::name('integral_shop')->where('id',$orgoods['hd_id'])->setDec('sold',$applys['tui_num']);
                            }
                            //处理用户钱包及明细
                            $watId = Db::name('wallet')->where('user_id',$applys['user_id'])->value('id');
                            $detailData = [];
                            $detailData['de_type']     =    1;
                            $detailData['sr_type']     =    2;
                            $detailData['price']       =    $applys['tui_price'];
                            $detailData['order_id']    =    $applys['order_id'];
                            $detailData['order_type']  =    4;
                            $detailData['user_id']     =    $applys['user_id'];
                            $detailData['wat_id']      =    $watId;
                            $detailData['time']        =    time();

                            Db::name('detail')->insert($detailData);
                            Db::name('wallet')->where('user_id',$applys['user_id'])->setInc('price', $detailData['price']);
                            //处理商户钱包及明细
                            $shopWatId = Db::name('shop_wallet')->where('shop_id',$applys['shop_id'])->value('id');
                            $shopDetailData = [];
                            $shopDetailData['de_type']     =    2;
                            $shopDetailData['zc_type']     =    2;
                            $shopDetailData['price']       =    $applys['tui_price'];
                            $shopDetailData['order_type']  =    4;
                            $shopDetailData['order_id']    =    $applys['order_id'];
                            $shopDetailData['shop_id']     =    $applys['shop_id'];
                            $shopDetailData['wat_id']      =    $shopWatId;
                            $shopDetailData['time']        =    time();

                            Db::name('shop_detail')->insert($shopDetailData);
                            Db::name('shop_wallet')->where('shop_id',$applys['shop_id'])->setInc('price', $detailData['price']);
                            //处理积分商品退还积分
                            if($ordergoods['hd_type'] == 2){
                                Db::name('member')->where('id',$applys['user_id'])->setInc('integral',$ordergoods['integral']);
                            }


                            // 提交事务
                            Db::commit();
                            ys_admin_logs('退换订单平台确认完成','th_apply',$th_id);
                            $value = array('status'=>1,'mess'=>'确认完成成功');
                        } catch (\Exception $e) {
                            // 回滚事务
                            Db::rollback();
                            echo $e->getMessage();
                            $value = array('status'=>0,'mess'=>'操作失败');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'找不到相关退换商品');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'退换申请信息错误，操作失败');
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少退换申请信息，操作失败');
            }
            return json($value);
        }
    }

    //保存物流信息
    public function savewuliu(){
        if(request()->isPost()){
            if(input('post.ps_id') && input('post.psnum') && input('post.th_id')){
                $shopId = session('shop_id');
                $ps_id = input('post.ps_id');
                $psnum = input('post.psnum');
                $th_id = input('post.th_id');
                $wuliu_infos = Db::name('huan_wuliu')->where('psnum',$psnum)->find();
                if(!$wuliu_infos){
                    $logs = Db::name('logistics')->where('id',$ps_id)->find();
                    $applys = Db::name('th_apply')->where('id',$th_id)->where('shop_id',$shopId)->where('thfw_id',3)->where('apply_status',1)->where('dcfh_status',1)->where('sh_status',1)->field('id')->find();
                    if($logs){
                        if($applys){
                            $wulius = Db::name('huan_wuliu')->where('th_id',$th_id)->find();
                            if($wulius){
                                $count = Db::name('huan_wuliu')->update(array('ps_id'=>$ps_id,'psnum'=>$psnum,'id'=>$wulius['id']));
                                if($count !== false){
                                    ys_admin_logs('换货订单平台保存物流信息','huan_wuliu',$wulius['id']);
                                    $value = array('status'=>1,'mess'=>'保存成功');
                                }else{
                                    $value = array('status'=>0,'mess'=>'保存失败');
                                }
                            }else{
                                $lastId = Db::name('huan_wuliu')->insertGetId(array('ps_id'=>$ps_id,'psnum'=>$psnum,'th_id'=>$th_id));
                                if($lastId){
                                    ys_admin_logs('换货订单平台保存物流信息','huan_wuliu',$lastId);
                                    $value = array('status'=>1,'mess'=>'保存成功');
                                }else{
                                    $value = array('status'=>0,'mess'=>'保存失败');
                                }
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'退换申请信息错误，保存失败');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'物流信息错误，保存失败');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'运单号已存在');
                }
            }else{
                $value = array('status'=>0,'mess'=>'请完善物流信息，保存失败');
            }
            return json($value);
        }
    }

    public function fachu(){
        if(request()->isPost()){
            if(input('post.th_id')){
                $shopId = session('shop_id');
                $th_id = input('post.th_id');
                $applys = Db::name('th_apply')->where('id',$th_id)->where('shop_id',$shopId)->where('thfw_id',3)->where('apply_status',1)->where('dcfh_status',1)->where('sh_status',1)->where('fh_status',0)->field('id')->find();
                if($applys){
                    $ordouts = Db::name('order_timeout')->where('id',1)->find();

                    $wulius = Db::name('huan_wuliu')->where('th_id',$th_id)->find();
                    if($wulius){
                        $yhshou_timeout  = time()+$ordouts['yhshou_timeout']*24*3600;
                        $count = Db::name('th_apply')->update(array('fh_status'=>1,'fh_time'=>time(),'yhshou_timeout'=>$yhshou_timeout,'id'=>$th_id));
                        if($count > 0){
                            ys_admin_logs('换货订单平台发货','th_apply',$th_id);
                            $value = array('status'=>1,'mess'=>'发货成功');
                        }else{
                            $value = array('status'=>0,'mess'=>'发货失败');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'请先保存物流信息，发货失败');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'找不到相关退换申请，发货失败');
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少订单信息，发货失败');
            }
            return json($value);
        }
    }

    public function search(){
        $shopId = session('shop_id');
        if(input('post.keyword') != ''){
            cookie('th_keyword',input('post.keyword'),7200);
        }else{
            cookie('th_keyword',null);
        }

        if(input('post.thfw_id') != ''){
            cookie("thfw_id", input('post.thfw_id'), 7200);
        }

        if(input('post.th_status') != ''){
            cookie("th_status", input('post.th_status'), 7200);
        }

        if(input('post.starttime') != ''){
            $thstarttime = strtotime(input('post.starttime'));
            cookie('thstarttime',$thstarttime,7200);
        }

        if(input('post.endtime') != ''){
            $thendtime = strtotime(input('post.endtime'));
            cookie('thendtime',$thendtime,7200);
        }

        $where = array();
        $where['a.shop_id'] = $shopId;

        if(cookie('thfw_id') != ''){
            $thfw_id = (int)cookie('thfw_id');
            if($thfw_id != 0){
                switch($thfw_id){
                    //仅退款
                    case 1:
                        $where['a.thfw_id'] = 1;
                        break;
                    //退货退款
                    case 2:
                        $where['a.thfw_id'] = 2;
                        break;
                    //换货
                    case 3:
                        $where['a.thfw_id'] = 3;
                        break;
                }
            }
        }

        if(cookie('th_status') != ''){
            $th_status = cookie('th_status');
            if($th_status != 0){
                switch($th_status){
                    //待平台审核
                    case 1:
                        $where['a.apply_status'] = 0;
                        break;
                    //平台已同意
                    case 2:
                        $where['a.apply_status'] = 1;
                        break;
                    //平台已拒绝
                    case 3:
                        $where['a.apply_status'] = 2;
                        break;
                    //已完成
                    case 5:
                        $where['a.apply_status'] = 3;
                        break;
                    //用户已撤销
                    case 4:
                        $where['a.apply_status'] = 4;
                        break;
                }
            }
        }

        if(cookie('thendtime') && cookie('thstarttime')){
            $where['a.apply_time'] = array(array('egt',cookie('thstarttime')), array('lt',cookie('thendtime')));
        }

        if(cookie('thstarttime') && !cookie('thendtime')){
            $where['a.apply_time'] = array('egt',cookie('thstarttime'));
        }

        if(cookie('thendtime') && !cookie('thstarttime')){
            $where['a.apply_time'] = array('lt',cookie('thendtime'));
        }

        if(cookie('th_keyword')){
            $keyword = cookie('th_keyword');
            $where['a.th_number'] = ['like', "%{$keyword}%"];
        }

        $list = Db::name('th_apply')->alias('a')->field('a.id,a.th_number,a.tui_price,apply_status,apply_time,b.user_name,b.phone,c.cate_name')->join('sp_member b','a.user_id = b.id','LEFT')->join('sp_thcate c','a.thfw_id = c.id','LEFT')->where($where)->order('a.apply_time desc')->paginate(25);
        $page = $list->render();

        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }

        $search = 1;

        if(cookie('thstarttime')){
            $this->assign('starttime',cookie('thstarttime'));
        }

        if(cookie('thendtime')){
            $this->assign('endtime',cookie('thendtime'));
        }

        if(cookie('th_keyword')){
            $this->assign('keyword',cookie('th_keyword'));
        }

        if(cookie('th_status') != ''){
            $this->assign('th_status',cookie('th_status'));
        }

        if(cookie('thfw_id') != ''){
            $this->assign('thfw_id',cookie('thfw_id'));
        }

        $this->assign('search',$search);
        $this->assign('pnum', $pnum);
        $this->assign('list', $list);// 赋值数据集
        $this->assign('page', $page);// 赋值分页输出
        $this->assign('filter',10);
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }


}
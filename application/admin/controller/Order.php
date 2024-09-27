<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class Order extends Common{

    public function lst(){
        $shop_id = session('shop_id');
        
        $filter = input('filter');
        if(!$filter || !in_array($filter, array(1,2,3,4,5,6,10))){
            $filter = 10;
        }
    
        switch ($filter){
            //待发货
            case 1:
                $where = array('a.shop_id'=>$shop_id,'a.state'=>1,'a.fh_status'=>0,'a.order_status'=>0);
                break;
            //已发货
            case 2:
                $where = array('a.shop_id'=>$shop_id,'a.state'=>1,'a.fh_status'=>1,'a.order_status'=>0);
                break;
            //已完成
            case 3:
                $where = array('a.shop_id'=>$shop_id,'a.state'=>1,'a.fh_status'=>1,'a.order_status'=>1);
                break;
            //待支付
            case 4:
                $where = array('a.shop_id'=>$shop_id,'a.state'=>0,'a.fh_status'=>0,'a.order_status'=>0);
                break;
            //已关闭
            case 5:
                $where = array('a.shop_id'=>$shop_id,'a.order_status'=>2);
                break;
            //已关闭
            case 6:
                $where = array('a.shop_id'=>$shop_id,'a.order_status'=>3);
                break;
            //全部
            case 10:
                $where = array('a.shop_id'=>$shop_id);
                break;
        }
    
    
        $list = Db::name('order')->alias('a')->field('a.*,b.user_name,b.phone,c.pro_name,d.city_name,u.area_name')->join('sp_member b','a.user_id = b.id','LEFT')->join('sp_province c','a.pro_id = c.id','LEFT')->join('sp_city d','a.city_id = d.id','LEFT')->join('sp_area u','a.area_id = u.id','LEFT')->where($where)->order('a.addtime desc')->paginate(25);
        $page = $list->render();
    
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
    
        $prores = Db::name('province')->field('id,pro_name,zm')->order('sort asc')->select();
        $this->assign('filter',$filter);
        $this->assign('prores',$prores);
        $this->assign('pnum',$pnum);
        $this->assign('page',$page);// 赋值分页输出
        $this->assign('list',$list);// 赋值数据集
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }
    
    public function getcitylist(){
        if(request()->isPost()){
            $pro_id = input('post.pro_id');
            if($pro_id){
                $cityres = Db::name('city')->where('pro_id',$pro_id)->field('id,city_name,zm')->order('sort asc')->select();
                if(empty($cityres)){
                    $cityres = 0;
                }
                return $cityres;
            }
        }
    }
    
    public function getarealist(){
        if(request()->isPost()){
            $city_id = input('post.city_id');
            if($city_id){
                $areares = Db::name('area')->where('city_id',$city_id)->field('id,area_name,zm')->order('sort asc')->select();
                if(empty($areares)){
                    $areares = 0;
                }
                return $areares;
            }
        }
    }
     
    //订单详情
    public function info(){
        if(input('order_id')){
            $shop_id = session('shop_id');
            $order_id = input('order_id');
            $orders = Db::name('order')->alias('a')->field('a.*,b.user_name,b.phone,c.pro_name,d.city_name,p.area_name')->join('sp_member b','a.user_id = b.id','LEFT')->join('sp_province c','a.pro_id = c.id','LEFT')->join('sp_city d','a.city_id = d.id','LEFT')->join('sp_area p','a.area_id = p.id','LEFT')->where('a.id',$order_id)->where('a.shop_id',$shop_id)->find();
            if($orders){
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
                }elseif($orders['order_status'] == 3){
                    $orders['zhuangtai'] = 6;
                }
                
                if($orders['order_type'] == 2){
                    $pintuans = Db::name('pintuan')->where('id',$orders['pin_id'])->field('id,pin_num,tuan_num,state,pin_status,timeout')->find();
                    $assembles = Db::name('order_assemble')->where('pin_id',$pintuans['id'])->where('order_id',$orders['id'])->find();
                }else{
                    $pintuans = array();
                    $assembles = array();
                }
                
                $order_goodres = Db::name('order_goods')->where('order_id',$orders['id'])->select();
                foreach ($order_goodres as $k => $v){
                    $order_goodres[$k]['dan_price'] = sprintf("%.2f", $v['real_price']*$v['goods_num']);
                }
                
                $wulius = Db::name('order_wuliu')->where('order_id',$order_id)->find();

                $psres = Db::name('logistics')->where('is_show',1)->field('id,log_name')->order('sort asc')->select();

                if($wulius){
                    $log_name = Db::name('logistics')->where('id',$wulius['ps_id'])->value('log_name');
                    $this->assign('log_name',$log_name);
                }else{
                    $log_name = '';
                }
                $this->assign('orders',$orders);
                $this->assign('pintuans',$pintuans);
                $this->assign('assembles',$assembles);
                $this->assign('order_goodres',$order_goodres);
                $this->assign('wulius',$wulius);
                $this->assign('psres',$psres);
                $this->assign('log_name',$log_name);
                return $this->fetch();
            }else{
                $this->error('订单信息错误');
            }
        }else{
            $this->error('缺少订单信息');
        }
    }
    
    //保存物流信息
    public function savewuliu(){
        if(request()->isPost()){
            if(input('post.ps_id') && input('post.psnum') && input('post.order_id')){
                $shop_id = session('shop_id');
                $ps_id = input('post.ps_id');
                $psnum = input('post.psnum');
                $order_id = input('post.order_id');
                $wuliu_infos = Db::name('order_wuliu')->where('psnum',$psnum)->find();
                if(!$wuliu_infos){
                    $logs = Db::name('logistics')->where('id',$ps_id)->find();
                    $orders = Db::name('order')->where('id',$order_id)->where('shop_id',$shop_id)->where('state',1)->where('order_status',0)->field('id')->find();
                    if($logs){
                        if($orders){
                            if($orders['order_type'] == 2){
                                $pintuans = Db::name('pintuan')->where('id',$orders['pin_id'])->where('state',1)->where('pin_status',1)->field('id')->find();
                                if(!$pintuans){
                                    $value = array('status'=>0,'mess'=>'拼团未完成，保存失败');
                                    return json($value);
                                }
                            }
                            
                            $wulius = Db::name('order_wuliu')->where('order_id',$order_id)->find();
                            if($wulius){
                                $count = Db::name('order_wuliu')->update(array('ps_id'=>$ps_id,'psnum'=>$psnum,'id'=>$wulius['id']));
                                if($count !== false){
                                    ys_admin_logs('保存订单物流信息','order_wuliu',$wulius['id']);
                                    $value = array('status'=>1,'mess'=>'保存成功');
                                }else{
                                    $value = array('status'=>0,'mess'=>'保存失败');
                                }
                            }else{
                                $lastId = Db::name('order_wuliu')->insertGetId(array('ps_id'=>$ps_id,'psnum'=>$psnum,'order_id'=>$order_id));
                                if($lastId){
                                    ys_admin_logs('保存订单物流信息','order_wuliu',$lastId);
                                    $value = array('status'=>1,'mess'=>'保存成功');
                                }else{
                                    $value = array('status'=>0,'mess'=>'保存失败');
                                }
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'订单信息错误，保存失败');
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
            if(input('post.order_id')){
                $shop_id = session('shop_id');
                $order_id = input('post.order_id');
                $orders = Db::name('order')->where('id',$order_id)->where('shop_id',$shop_id)->where('state',1)->where('fh_status',0)->where('order_status',0)->field('id,shouhou')->find();
                if($orders){
                    $ordouts = Db::name('order_timeout')->where('id',1)->find();
                    
                    if($orders['order_type'] == 2){
                        $pintuans = Db::name('pintuan')->where('id',$orders['pin_id'])->where('state',1)->where('pin_status',1)->field('id')->find();
                        if(!$pintuans){
                            $value = array('status'=>0,'mess'=>'拼团未完成，发货失败');
                            return json($value);
                        }
                    }
                    
                    if($orders['shouhou'] == 0){
                        $order_goodres = Db::name('order_goods')->where('order_id',$orders['id'])->field('th_status')->select();
                        if($order_goodres){
                            foreach ($order_goodres as $v){
                                if(in_array($v['th_status'], array(1,2,3))){
                                    $value = array('status'=>0,'mess'=>'订单存在商品在申请退款中，请处理后发货');
                                    return json($value);
                                }
                            }
                            $wulius = Db::name('order_wuliu')->where('order_id',$order_id)->find();
                            if($wulius){
                                $zdsh_time = time()+$ordouts['zdqr_sh_time']*24*3600;
                                $count = Db::name('order')->update(array('fh_status'=>1,'fh_time'=>time(),'zdsh_time'=>$zdsh_time,'id'=>$order_id));
                                if($count > 0){
                                    ys_admin_logs('订单发货','order',$order_id);
                                    $value = array('status'=>1,'mess'=>'发货成功');
                                }else{
                                    $value = array('status'=>0,'mess'=>'发货失败');
                                }
                            }else{
                                $value = array('status'=>0,'mess'=>'请先保存物流信息，发货失败');
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'订单异常，发货失败');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'订单存在商品在申请退款中，请处理后发货');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'找不到相关待发货订单，发货失败');
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少订单信息，发货失败');
            }
            return json($value);
        }  
    }

    public function payment(){
        if(request()->isPost()){
            if(input('post.order_id')){
                $shop_id = session('shop_id');
                $order_id = input('post.order_id');
                $orders = Db::name('order')->where('id',$order_id)->where('shop_id',$shop_id)->where('state',1)->where('user_dakuan_status',0)->where('order_status',3)->find();
                $orderGoodss = Db::name('order_goods')->where('order_id',$orders['id'])->find();
                if($orders){
                    // 启动事务
                    Db::startTrans();
                    try{
                        Db::name('order')->update(array('user_dakuan_status'=>1,'user_dakuan_time'=>time(),'id'=>$order_id));
                        //余额支付
                        if($orders['zf_type'] == 3){
                            $detailData = [];
                            $shopDetailData = [];
                            //未发货退款包含运费，已发货不包含运费
                            if($orders['state'] == 1 && $orders['fh_status'] == 0){
                                $price      =    $orders['goods_price'] + $orders['freight'];
                            }
                            //未发货直接取消的订单将用户钱包明细的状态改为5，取消订单
                            if($orders['order_status'] == 3 && $orders['fh_status'] == 0){
                                $detailData['sr_type']     =    5;
                            }else{
                                $detailData['sr_type']     =    2;
                            }
                            //处理用户钱包及明细
                            $watId = Db::name('wallet')->where('user_id',$orders['user_id'])->value('id');

                            $detailData['de_type']     =    1;
                            $detailData['order_id']    =    $orders['id'];
                            $detailData['price']       =    $price;
                            $detailData['order_type']  =    4;
                            $detailData['user_id']     =    $orders['user_id'];
                            $detailData['wat_id']      =    $watId;
                            $detailData['time']        =    time();

                            Db::name('detail')->insert($detailData);
                            Db::name('wallet')->where('user_id',$orders['user_id'])->setInc('price', $price);
                            //处理商户钱包及明细
                            $shopWatId = Db::name('shop_wallet')->where('shop_id',$orders['shop_id'])->value('id');

                            $shopDetailData['de_type']     =    2;
                            $shopDetailData['zc_type']     =    2;
                            $shopDetailData['price']       =    $price;
                            $shopDetailData['order_type']  =    4;
                            $shopDetailData['order_id']    =    $orders['id'];
                            $shopDetailData['shop_id']     =    $orders['shop_id'];
                            $shopDetailData['wat_id']      =    $shopWatId;
                            $shopDetailData['time']        =    time();

                            Db::name('shop_detail')->insert($shopDetailData);
                            Db::name('shop_wallet')->where('shop_id',$orders['shop_id'])->setInc('price', $price);
                        }

                        //处理积分商品退还积分
                        if($orderGoodss['hd_type'] == 2){
                            Db::name('member')->where('id',$orders['user_id'])->setInc('integral',$orderGoodss['integral']);
                        }
                        // 提交事务
                        Db::commit();
                        $value = array('status'=>1,'mess'=>'打款成功');
                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                        echo $e->getMessage();
                        $value = array('status'=>0,'mess'=>'打款失败');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'找不到相关待发货订单，打款失败');
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少订单信息，打款失败');
            }
            return json($value);
        }
    }
    public function delete(){
        if(input('id') && !is_array(input('id'))){
            $shop_id = session('shop_id');
            $id = input('id');
            $orders = Db::name('order')->where('id',$id)->where('shop_id',$shop_id)->where('state',0)->where('order_status',2)->field('id')->find();
            if($orders){
                // 启动事务
                Db::startTrans();
                try{
                    Db::name('order')->where('id',$id)->delete();
                    // 提交事务
                    Db::commit();
                    ys_admin_logs('删除订单','order',$id);
                    $value = array('status'=>1,'mess'=>'删除成功');
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    $value = array('status'=>0,'mess'=>'删除失败');
                }
            }else{
                $value = array('status'=>0,'mess'=>'未关闭订单不可删除');
            }
        }else{
            $value = array('status'=>0,'mess'=>'删除失败');
        }
        return json($value);
    }
    
    public function search(){
        $shop_id = session('shop_id');
        
        if(input('post.keyword') != ''){
            cookie('or_keyword',input('post.keyword'),7200);
        }else{
            cookie('or_keyword',null);
        }
    
        if(input('post.pro_id') != ''){
            cookie("or_pro_id", input('post.pro_id'), 7200);
        }
    
        if(input('post.city_id') != ''){
            cookie("or_city_id", input('post.city_id'), 7200);
        }
    
        if(input('post.area_id') != ''){
            cookie("or_area_id", input('post.area_id'), 7200);
        }
        
        if(input('post.order_type') != ''){
            cookie("or_order_type", input('post.order_type'), 7200);
        }
    
        if(input('post.order_zt') != ''){
            cookie("or_order_zt", input('post.order_zt'), 7200);
        }
    
        if(input('post.zf_type') != ''){
            cookie("or_zf_type", input('post.zf_type'), 7200);
        }
    
        if(input('post.starttime') != ''){
            $orstarttime = strtotime(input('post.starttime'));
            cookie('orstarttime',$orstarttime,7200);
        }
    
        if(input('post.endtime') != ''){
            $orendtime = strtotime(input('post.endtime'));
            cookie('orendtime',$orendtime,7200);
        }
    
        $where = array();
        $where['a.shop_id'] = $shop_id;
        
        if(cookie('or_keyword')){
            $where['a.ordernumber'] = cookie('or_keyword');
        }
        
        
        if(cookie('or_pro_id') != ''){
            $proid = (int)cookie('or_pro_id');
            if($proid != 0){
                $where['a.pro_id'] = $proid;
            }
        }
    
        if(cookie('or_city_id') != ''){
            $cityid = (int)cookie('or_city_id');
            if($cityid != 0){
                $where['a.city_id'] = $cityid;
            }
        }
    
        if(cookie('or_area_id') != ''){
            $areaid = (int)cookie('or_area_id');
            if($areaid != 0){
                $where['a.area_id'] = $areaid;
            }
        }
    
        $nowtime = time();
        
        if(cookie('or_order_type') != ''){
            $order_type = (int)cookie('or_order_type');
            if($order_type != 0){
                switch($order_type){
                    //普通订单
                    case 1:
                        $where['a.order_type'] = 1;
                        break;
                        //拼团订单
                    case 2:
                        $where['a.order_type'] = 2;
                        break;
                }
            }
        }
    
        if(cookie('or_order_zt') != ''){
            $order_zt = (int)cookie('or_order_zt');
            if($order_zt != 0){
                switch($order_zt){
                    //待发货
                    case 1:
                        $where['a.state'] = 1;
                        $where['a.fh_status'] = 0;
                        $where['a.order_status'] = 0;
                        break;
                    //已发货
                    case 2:
                        $where['a.state'] = 1;
                        $where['a.fh_status'] = 1;
                        $where['a.order_status'] = 0;
                        break;
                    //已完成
                    case 3:
                        $where['a.state'] = 1;
                        $where['a.fh_status'] = 1;
                        $where['a.order_status'] = 1;
                        break;
                    //待支付
                    case 4:
                        $where['a.state'] = 0;
                        $where['a.fh_status'] = 0;
                        $where['a.order_status'] = 0;
                        break;
                    //已关闭
                    case 5:
                        $where['a.order_status'] = 2;
                        break;
                    //已取消
                    case 6:
                        $where['a.order_status'] = 3;
                        break;
                }
            }
        }
    
        if(cookie('or_zf_type') != ''){
            $zf_type = (int)cookie('or_zf_type');
            if($zf_type != 0){
                switch($zf_type){
                    //支付宝支付
                    case 1:
                        $where['a.zf_type'] = 1;
                        break;
                    //微信支付
                    case 2:
                        $where['a.zf_type'] = 2;
                        break;
                    //余额支付
                    case 3:
                        $where['a.zf_type'] = 3;
                        break;
	                //银行卡支付
	                case 5:
		                $where['a.zf_type'] = 5;
		                break;
                }
            }
        }
    
        if(cookie('orendtime') && cookie('orstarttime')){
            $where['a.addtime'] = array(array('egt',cookie('orstarttime')), array('lt',cookie('orendtime')));
        }
    
        if(cookie('orstarttime') && !cookie('orendtime')){
            $where['a.addtime'] = array('egt',cookie('orstarttime'));
        }
    
        if(cookie('orendtime') && !cookie('orstarttime')){
            $where['a.addtime'] = array('lt',cookie('orendtime'));
        }
        $list = Db::name('order')->alias('a')->field('a.*,b.user_name,b.phone,c.pro_name,d.city_name,u.area_name')->join('sp_member b','a.user_id = b.id','LEFT')->join('sp_province c','a.pro_id = c.id','LEFT')->join('sp_city d','a.city_id = d.id','LEFT')->join('sp_area u','a.area_id = u.id','LEFT')->where($where)->order('a.addtime desc')->paginate(25);
    
        $page = $list->render();
    
        $prores = Db::name('province')->field('id,pro_name,zm')->order('sort asc')->select();
    
        if(cookie('or_pro_id')){
            $cityres = Db::name('city')->where('pro_id',cookie('or_pro_id'))->field('id,city_name,zm')->order('sort asc')->select();
        }
    
        if(cookie('or_pro_id') && cookie('or_city_id')){
            $areares = Db::name('area')->where('city_id',cookie('or_city_id'))->field('id,area_name,zm')->select();
        }
    
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
    
        $search = 1;
    
        if(cookie('or_pro_id') != ''){
            $this->assign('pro_id',cookie('or_pro_id'));
        }
        if(cookie('or_city_id') != ''){
            $this->assign('city_id',cookie('or_city_id'));
        }
        if(cookie('or_area_id') != ''){
            $this->assign('area_id',cookie('or_area_id'));
        }
    
        if(cookie('orstarttime')){
            $this->assign('starttime',cookie('orstarttime'));
        }
    
        if(cookie('orendtime')){
            $this->assign('endtime',cookie('orendtime'));
        }
    
        if(!empty($cityres)){
            $this->assign('cityres',$cityres);
        }
    
        if(!empty($areares)){
            $this->assign('areares',$areares);
        }
    
        if(cookie('or_keyword')){
            $this->assign('keyword',cookie('or_keyword'));
        }
        
        if(cookie('or_order_type') != ''){
            $this->assign('order_type',cookie('or_order_type'));
        }
    
        if(cookie('or_order_zt') != ''){
            $this->assign('order_zt',cookie('or_order_zt'));
        }
    
        if(cookie('or_zf_type') != ''){
            $this->assign('zf_type',cookie('or_zf_type'));
        }
    
        $this->assign('search',$search);
        $this->assign('pnum', $pnum);
        $this->assign('list', $list);// 赋值数据集
        $this->assign('page', $page);// 赋值分页输出
        $this->assign('prores',$prores);
        $this->assign('filter',10);
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }    
 
    
}

<?php
namespace app\api\controller;
use app\api\controller\Common;
use think\db;
use app\api\model\Common as CommonModel;
use app\api\model\Member;
use app\common\model\SmsCode;

class Merchant extends Common
{
    public function bindSubAccount(){
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess'],$tokenRes['data']);
        }else{
            $userId = $tokenRes['user_id'];
        }
        $userModel = new Member();
        $user = $userModel->getUserInfoById($userId);
        if(empty($user['shop_id'])){
            datamsg(400,'您没有添加子账号权限！');
        }
        $data['phone'] = input('param.phone');
        $data['sms_code'] = input('param.sms_code');
        $validate = validate('AddSubAccount');
        if(!$validate->check($data)){
            datamsg(400,$validate->getError());
        }
        $subUser = $userModel->getUserInfoByPhone($data['phone']);
        if(!$subUser){
            datamsg(400,'该手机号未注册，请先注册账号再进行绑定！');
        }
        if($subUser['id'] == $userId){
            datamsg(400,'子账号不能添加自己！');
        }
        $smsCodeModel = new SmsCode();
        $checkSmsCode = $smsCodeModel->checkSmsCode($data['sms_code'],$data['phone'],4);
        if($checkSmsCode['status'] == 400){
            datamsg(400,$checkSmsCode['mess']);
        }

        $bindParent = Db::name('member')->where(['id'=>$subUser['id']])->update(['pid'=>$userId]);
        if($bindParent){
            datamsg(200,'绑定成功');
        }else{
            datamsg(400,'绑定失败');
        }
    }

    // 客服列表
    public function customerServiceList(){
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess'],$tokenRes['data']);
        }else{
            $userId = $tokenRes['user_id'];
        }

        $customerServiceList = db('member')->where(['pid'=>$userId])->select();
        foreach($customerServiceList as $k=>$v){
            $customerServiceToken = db('member_token')->where(['user_id'=>$v['id']])->value('token');
            if($customerServiceToken){
                $v['toid'] = $customerServiceToken;
                $v['headimgurl'] = url_format($v['headimgurl'],$this->webconfig['weburl']);
                $customerServiceListRes[]=$v;
            }
        }
        datamsg(200,'获取成功',$customerServiceListRes);

    }


    // 停用子账号
    public function deleteCustomerService(){
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){ // 400返回错误描述
            datamsg(400,$tokenRes['mess'],$tokenRes['data']);
        }
        $id = input('post.id');
        if(empty($id)){
            datamsg(400,'缺少子账号ID参数');
        }

        $res = Db::name('member')->where(['id'=>$id])->update(['pid'=>0]);
        if($res){
            datamsg(200,'解绑成功');
        }else{
            datamsg(400,'解绑失败');
        }
    }

    // 订单列表
    //订单列表信息接口
    public function orderList(){
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess'],$tokenRes['data']);
        }else{
            $userId = $tokenRes['user_id'];
        }

        $members = db('member')->where(['id'=>$userId])->find();
        $shop_id = $members['shop_id'];

        if(empty($shop_id) || $shop_id == 0){
            //查看是否是客服登录进来， 如果是客服，商家id 等于客服的父级的商家id
            if($members['pid'] > 0){
                $shop_id = db('member')->where(['id'=>$members['pid']])->value('shop_id');
            }
        }

        if(empty($shop_id) || $shop_id == 0){
            datamsg(400,'缺少商家id参数');
        }
        if(!input('post.page') || !preg_match("/^\\+?[1-9][0-9]*$/", input('post.page'))){
            datamsg(400,'缺少页数参数');
        }
        $ordouts = Db::name('order_timeout')->where('id',1)->find();
        $webconfig = $this->webconfig;
        $perpage = 20;
        $offset = (input('post.page')-1)*$perpage;

        $filter = input('post.filter');
        if(!$filter || !in_array($filter, array(1,2,3,4,5,6,7,8,9))){
            $filter = 6;
        }
        switch($filter){
            //待付款
            case 1:
                $where = array('a.shop_id'=>$shop_id,'a.state'=>0,'a.fh_status'=>0,'a.order_status'=>0,'a.is_show'=>1);
                $sort = array('a.addtime'=>'desc','a.id'=>'desc');
                break;
            //待发货
            case 2:
                $where = array('a.shop_id'=>$shop_id,'a.state'=>1,'a.fh_status'=>0,'a.order_status'=>0,'a.is_show'=>1);
                $sort = array('a.pay_time'=>'desc','a.id'=>'desc');
                break;
            //待收货
            case 3:
                $where = array('a.shop_id'=>$shop_id,'a.state'=>1,'a.fh_status'=>1,'a.order_status'=>0,'a.is_show'=>1);
                $sort = array('a.fh_time'=>'desc','a.id'=>'desc');
                break;
            //待评价
            case 4:
                $where = array('a.shop_id'=>$shop_id,'a.state'=>1,'a.fh_status'=>1,'a.order_status'=>1,'a.ping'=>0,'a.is_show'=>1);
                $sort = array('a.addtime'=>'desc','a.id'=>'desc');
                break;
            //待付款
            case 5:
                $where = array('a.shop_id'=>$shop_id,'a.state'=>0,'a.fh_status'=>0,'a.order_status'=>0,'a.is_show'=>1);
                $sort = array('a.addtime'=>'desc','a.id'=>'desc');
                break;
            //失败
            case 7:
                $where = array('a.shop_id'=>$shop_id,'a.state'=>0,'a.order_status'=>2);
                $sort = array('a.coll_time'=>'desc','a.id'=>'desc');
                break;
            //全部
            //已评价
            case 8:
                $where = array('a.shop_id'=>$shop_id,'a.state'=>1,'a.fh_status'=>1,'a.order_status'=>1,'a.ping'=>1,'a.is_show'=>1);
                $sort = array('a.addtime'=>'desc','a.id'=>'desc');
                break;
            case 9:
                $where = array('a.shop_id'=>$shop_id,'a.state'=>1,'a.fh_status'=>1,'a.order_status'=>1,'a.is_show'=>1);
                $sort = array('a.addtime'=>'desc','a.id'=>'desc');
                break;
            //全部
            case 6:
                $where = array('a.shop_id'=>$shop_id,'a.is_show'=>1);
                $sort = array('a.addtime'=>'desc','a.id'=>'desc');
                break;
        }

            if(in_array($filter,array(1,2,3,4,5,6,7,8,9))){
                $orderes = Db::name('order')
                             ->alias('a')
                             ->field('a.id,a.ordernumber,a.coupon_id,a.total_price,a.state,a.fh_status,a.order_status,a.shouhou,a.ping,a.is_show,a.ping,a.order_type,a.pin_type,a.pin_id,a.shop_id,a.zdsh_time,a.time_out,b.shop_name')
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
                        }elseif($v['order_status'] == 2 && $v['is_show'] == 1){
                            $orderes[$k]['order_zt'] = "已关闭";
                            $orderes[$k]['filter'] = 5;
                        }

                        $orderes[$k]['goodsinfo'] = Db::name('order_goods')
                                                      ->where('order_id',$v['id'])
                                                      ->field('id,goods_id,goods_name,thumb_url,goods_attr_str,price,goods_num,th_status,order_id')
                                                      ->find();
                        //print_r($orderes[$k]['goodsinfo']);

                        $orderes[$k]['goodsinfo']['thumb_url'] = url_format($orderes[$k]['goodsinfo']['thumb_url'],$webconfig['weburl'],'?imageMogr2/thumbnail/200x200');



                        $orderes[$k]['spnum'] = Db::name('order_goods')->where('order_id',$v['id'])->sum('goods_num');
                    }
                }
            }else{

                $orderes = Db::name('th_apply')
                             ->alias('a')
                             ->field('a.id,a.th_number,a.thfw_id,a.apply_status,a.tui_price,a.tui_num,a.orgoods_id,a.order_id,a.dcfh_status,a.sh_status,a.fh_status,a.shou_status,a.check_timeout,a.shoptui_timeout,a.yhfh_timeout,a.yhshou_timeout,a.shop_id,b.shop_name')
                             ->join('sp_shops b','a.shop_id = b.id','INNER')
                             ->where('a.shop_id',$shop_id)
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
                                    $orderes[$k]['order_zt'] = '商家拒绝申请';
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
                                    $orderes[$k]['order_zt'] = '商家拒绝申请';
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
                                    $orderes[$k]['order_zt'] = '商家拒绝申请';
                                }elseif($v['apply_status'] == 3){
                                    $orderes[$k]['order_zt'] = '换货已完成';
                                }elseif($v['apply_status'] == 4){
                                    $orderes[$k]['order_zt'] = '已撤销';
                                }
                                break;
                        }
                        $orderes[$k]['orgoods'] = Db::name('order_goods')
                                                    ->where('id',$v['orgoods_id'])
                                                    ->where('order_id',$v['order_id'])
                                                    ->field('id,goods_id,goods_name,thumb_url,goods_attr_str,goods_num,th_status,order_id')
                                                    ->find();
                        $orderes[$k]['orgoods']['thumb_url'] = url_format($orderes[$k]['orgoods']['thumb_url'],$webconfig['weburl'],'?imageMogr2/thumbnail/350x350');

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
                            $orgoods = Db::name('order_goods')
                                         ->where('id',$v['orgoods_id'])
                                         ->field('goods_id,goods_attr_id,hd_type,hd_id')
                                         ->find();
                            if($orgoods){
                                // 启动事务
                                Db::startTrans();
                                try{
                                    Db::name('th_apply')->update(array('apply_status'=>3,'com_time'=>time(),'id'=>$v['id']));
                                    Db::name('order_goods')->update(array('th_status'=>4,'id'=>$v['orgoods_id']));
                                    $ordergoods = Db::name('order_goods')
                                                    ->where('id','neq',$v['orgoods_id'])
                                                    ->where('order_id',$v['order_id'])
                                                    ->where('th_status','in','0,1,2,3,5,6,7,8')
                                                    ->field('id')
                                                    ->find();
                                    if(!$ordergoods){
                                        $orders = Db::name('order')->where('id',$v['order_id'])->find();
                                        if($orders){
                                            Db::name('order')->where('id',$v['order_id'])->update(array('order_status'=>2,'shouhou'=>0,'can_time'=>time()));
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
                                                          ->where('id','neq',$v['orgoods_id'])
                                                          ->where('order_id',$v['order_id'])
                                                          ->where('th_status','in','1,2,3,5,6,7')
                                                          ->field('id')
                                                          ->find();

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
                            $orgoods = Db::name('order_goods')
                                         ->where('id',$v['orgoods_id'])
                                         ->field('goods_id,goods_attr_id,hd_type,hd_id')
                                         ->find();
                            if($orgoods){
                                // 启动事务
                                Db::startTrans();
                                try{
                                    Db::name('th_apply')->update(array('apply_status'=>3,'com_time'=>time(),'id'=>$v['id']));
                                    Db::name('order_goods')->update(array('th_status'=>4,'id'=>$v['orgoods_id']));
                                    $ordergoods = Db::name('order_goods')
                                                    ->where('id','neq',$v['orgoods_id'])
                                                    ->where('order_id',$v['order_id'])
                                                    ->where('th_status','in','0,1,2,3,5,6,7,8')
                                                    ->field('id')
                                                    ->find();
                                    if(!$ordergoods){
                                        $orders = Db::name('order')->where('id',$v['order_id'])->find();
                                        if($orders){
                                            Db::name('order')->where('id',$v['order_id'])->update(array('order_status'=>2,'shouhou'=>0,'can_time'=>time()));
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
                                                          ->where('id','neq',$v['orgoods_id'])
                                                          ->where('order_id',$v['order_id'])
                                                          ->where('th_status','in','1,2,3,5,6,7')
                                                          ->field('id')
                                                          ->find();

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
                                        $prokc = Db::name('product')
                                                   ->where('goods_id',$orgoods['goods_id'])
                                                   ->where('goods_attr',$orgoods['goods_attr_id'])
                                                   ->find();
                                        if($prokc){
                                            Db::name('product')
                                              ->where('goods_id',$orgoods['goods_id'])
                                              ->where('goods_attr',$orgoods['goods_attr_id'])
                                              ->setInc('goods_number', $v['tui_num']);
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
                            $orders = Db::name('order')
                                        ->where('id',$v['order_id'])
                                        ->where('state',1)
                                        ->where('fh_status',1)
                                        ->field('id')
                                        ->find();
                            if($orders){
                                // 启动事务
                                Db::startTrans();
                                try{
                                    Db::name('th_apply')->update(array('apply_status'=>4,'che_time'=>time(),'id'=>$v['id']));
                                    Db::name('order_goods')->update(array('th_status'=>0,'id'=>$v['orgoods_id']));

                                    $ordergoods = Db::name('order_goods')
                                                    ->where('id','neq',$v['orgoods_id'])
                                                    ->where('order_id',$v['order_id'])
                                                    ->where('th_status','in','1,2,3,5,6,7')
                                                    ->field('id')
                                                    ->find();

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

                                $ordergoods = Db::name('order_goods')
                                                ->where('id','neq',$v['orgoods_id'])
                                                ->where('order_id',$v['order_id'])
                                                ->where('th_status','in','1,2,3,5,6,7')
                                                ->field('id,th_status')
                                                ->find();

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

    //订单列表信息接口
    public function orderCount(){
        $tokenRes = $this->checkToken();
        if ($tokenRes['status'] == 400) {
            datamsg(400, $tokenRes['mess'], $tokenRes['data']);
        } else {
            $userId = $tokenRes['user_id'];
        }

        $members = db('member')->where(['id'=>$userId])->find();
        $shop_id = $members['shop_id'];

        if(empty($shop_id) || $shop_id == 0){
            //查看是否是客服登录进来， 如果是客服，商家id 等于客服的父级的商家id
            if($members['pid'] > 0){
                $shop_id = db('member')->where(['id'=>$members['pid']])->value('shop_id');
            }
        }
        if(empty($shop_id) || $shop_id == 0){
            datamsg(400,'缺少商家id参数');
        }

        $ordouts = Db::name('order_timeout')->where('id',1)->find();
        $webconfig = $this->webconfig;
        $perpage = 20;
        $offset = (input('post.page')-1)*$perpage;

        $filter = input('post.filter');
        if(!$filter || !in_array($filter, array(1,2,3,4,5,6,7,8,9))){
            $filter = 6;
        }

        switch($filter){
            //失败订单
            case 1:
                $where = array('a.shop_id'=>$shop_id,'a.state'=>0,'a.fh_status'=>0,'a.order_status'=>2,'a.is_show'=>1);
                $sort = array('a.addtime'=>'desc','a.id'=>'desc');
                break;
            //待发货
            case 2:
                $where = array('a.shop_id'=>$shop_id,'a.state'=>1,'a.fh_status'=>0,'a.order_status'=>0,'a.is_show'=>1);
                $sort = array('a.pay_time'=>'desc','a.id'=>'desc');
                break;
            //待收货
            case 3:
                $where = array('a.shop_id'=>$shop_id,'a.state'=>1,'a.fh_status'=>1,'a.order_status'=>0,'a.is_show'=>1);
                $sort = array('a.fh_time'=>'desc','a.id'=>'desc');
                break;
            //已完成订单(待评价)
            case 4:
                $where = array('a.shop_id'=>$shop_id,'a.state'=>1,'a.fh_status'=>1,'a.order_status'=>1,'a.ping'=>0,'a.is_show'=>1);
                $sort = array('a.coll_time'=>'desc','a.id'=>'desc');
                break;
            //待付款
            case 5:
                $where = array('a.shop_id'=>$shop_id,'a.state'=>0,'a.fh_status'=>0,'a.order_status'=>0,'a.is_show'=>1);
                $sort = array('a.addtime'=>'desc','a.id'=>'desc');
                break;

            //失败订单
            case 7:
                $where = array('a.shop_id'=>$shop_id,'a.state'=>0,'a.order_status'=>2);
                $sort = array('a.addtime'=>'desc','a.id'=>'desc');
                break;
            //已评价
            case 8:
                $where = array('a.shop_id'=>$shop_id,'a.state'=>1,'a.fh_status'=>1,'a.order_status'=>1,'a.ping'=>1,'a.is_show'=>1);
                $sort = array('a.addtime'=>'desc','a.id'=>'desc');
                break;
            //已完成（已收货）
            case 9:
                $where = array('a.shop_id'=>$shop_id,'a.state'=>1,'a.fh_status'=>1,'a.order_status'=>1,'a.is_show'=>1);
                $sort = array('a.addtime'=>'desc','a.id'=>'desc');
                break;
            //全部
            case 6:
                $where = array('a.shop_id'=>$shop_id,'a.is_show'=>1);
                $sort = array('a.addtime'=>'desc','a.id'=>'desc');
                break;
        }

        if(in_array($filter,array(1,2,3,4,5,6,7,8,9))){
            $orderes = Db::name('order')
                         ->alias('a')
                         ->field('a.id,a.ordernumber,a.coupon_id,a.total_price,a.state,a.fh_status,a.order_status,a.shouhou,a.ping,a.is_show,a.ping,a.order_type,a.pin_type,a.pin_id,a.shop_id,a.zdsh_time,a.time_out,b.shop_name')
                         ->join('sp_shops b','a.shop_id = b.id','INNER')
                         ->where($where)
                         ->order($sort)
                         ->count();

        }else{
            $orderes = Db::name('th_apply')
                         ->alias('a')
                         ->field('a.id,a.th_number,a.thfw_id,a.apply_status,a.tui_price,a.tui_num,a.orgoods_id,a.order_id,a.dcfh_status,a.sh_status,a.fh_status,a.shou_status,a.check_timeout,a.shoptui_timeout,a.yhfh_timeout,a.yhshou_timeout,a.shop_id,b.shop_name')
                         ->join('sp_shops b','a.shop_id = b.id','INNER')
                         ->where('a.user_id',$userId)
                         ->order('a.apply_time desc')
                         ->count();

        }

        datamsg(200,'获取订单信息成功',array('count'=>$orderes));
    }

    //商家订单详情
    public function orderInfo(){
        $tokenRes = $this->checkToken();
        if ($tokenRes['status'] == 400) {
            datamsg(400, $tokenRes['mess'], $tokenRes['data']);
        } else {
            $userId = $tokenRes['user_id'];
        }

        $members = db('member')->where(['id'=>$userId])->find();
        $shop_id = $members['shop_id'];

        if(empty($shop_id) || $shop_id == 0){
            //查看是否是客服登录进来， 如果是客服，商家id 等于客服的父级的商家id
            if($members['pid'] > 0){
                $shop_id = db('member')->where(['id'=>$members['pid']])->value('shop_id');
            }
        }
        if(empty($shop_id)){
            datamsg(400,'缺少商家id参数');
        }
        if(input('post.order_num')){
            $order_num = input('post.order_num');
            $orders = Db::name('order')
                        ->alias('a')
                        ->field('a.id,a.ordernumber,a.contacts,a.telephone,a.province,a.city,a.zf_type,a.area,a.address,a.goods_price,a.freight,a.youhui_price,a.coupon_id,a.coupon_price,a.coupon_str,a.total_price,a.beizhu,a.state,a.pay_time,a.fh_status,a.fh_time,a.order_status,a.shouhou,a.is_show,a.coll_time,a.can_time,a.ping,a.order_type,a.pin_type,a.pin_id,a.zong_id,a.shop_id,a.addtime,a.zdsh_time,a.time_out,b.order_number,c.shop_name,w.id as wid,w.psnum,w.ps_id')
                        ->join('sp_order_zong b','a.zong_id = b.id','LEFT')
                        ->join('order_wuliu w','a.id = w.order_id','LEFT')
                        ->join('sp_shops c','a.shop_id = c.id','LEFT')
                        ->where('a.ordernumber',$order_num)
                        ->where('a.shop_id',$shop_id)
                        ->where('a.is_show',1)
                        ->find();
            if(!$orders){
                datamsg(400,'找不到相关订单');
            }

            $orders['paytype'] = get_pay_type($orders['zf_type']);
            //物流
            $orders['logistics'] = "";
            $wltype = Db::name('logistics')->field("log_name")->where('id',$orders['ps_id'])->find();
            if($wltype){
                $orders['logistics'] = $wltype['log_name'];
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
                if($orders['sysh_time'] > time()){
                    $orders['sysh_time'] = time2string($orders['zdsh_time']-time());
                }else{
                    $orders['sysh_time'] = '';
                }
            }elseif($orders['state'] == 1 && $orders['fh_status'] == 1 && $orders['order_status'] == 1 && $orders['is_show'] == 1){
                $orders['order_zt'] = "已完成";
                $orders['filter'] = 4;
            }elseif($orders['order_status'] == 2 && $orders['is_show'] == 1){
                $orders['order_zt'] = "已关闭";
                $orders['filter'] = 5;
            }

            $orders['pinzhuangtai'] = 0;

            if($orders['state'] == 1 && $orders['fh_status'] == 0 && $orders['order_status'] == 0 && $orders['order_type'] == 2 && $orders['is_show'] == 1){
                $pinzts = Db::name('pintuan')
                            ->where('id',$orders['pin_id'])
                            ->where('state',1)
                            ->field('id,pin_num,tuan_num,pin_status,timeout')
                            ->find();
                if($pinzts){
                    if($pinzts['pin_status'] == 0){
                        $order_assembleres = Db::name('order_assemble')
                                               ->where('pin_id',$pinzts['id'])
                                               ->where('order_id',$orders['id'])
                                               ->where('user_id',$userId)
                                               ->where('state',1)
                                               ->where('tui_status',0)
                                               ->find();
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

            $orders['goodsinfo'] = Db::name('order_goods')
                                     ->where('order_id',$orders['id'])
                                     ->field('id,goods_id,goods_name,thumb_url,goods_attr_str,real_price,goods_num,th_status,order_id')
                                     ->select();

            $webconfig = $this->webconfig;

            foreach ($orders['goodsinfo'] as $key => $val){
                $orders['goodsinfo'][$key]['thumb_url'] = url_format($val['thumb_url'],$webconfig['weburl'],'?imageMogr2/thumbnail/200x200');
            }
            $orders['wulius'] = "";
            if($orders['fh_status'] == 1){
                $order_wulius = Db::name('order_wuliu')
                                  ->alias('a')
                                  ->field('a.id,a.psnum,b.log_name,b.telephone')
                                  ->join('sp_logistics b','a.ps_id = b.id','LEFT')
                                  ->where('a.order_id',$orders['id'])
                                  ->find();
                if($order_wulius){
                    $orders['wulius'] = $order_wulius;
                }
            }
            datamsg(200,'获取订单详情成功',set_lang($orders));
        }else{
            datamsg(400,'缺少订单号');
        }
    }

}
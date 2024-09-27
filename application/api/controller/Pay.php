<?php
namespace app\api\controller;
use think\Controller;
use think\Db;
use app\common\model\SmsCode as SmsCodeModel;

class Pay extends Common {
    /****
     * 商品订单状态处理
     */
    public function doGooodsOrder($order_sn,$payType=0){

        $orderzongs = Db::name('order_zong')->where('order_number',$order_sn)->where('state',0)->find();
        if(!$orderzongs){
            return false;
        }

        $orderes = Db::name('order')->where('zong_id',$orderzongs['id'])->where('state',0)->where('fh_status',0)->where('order_status',0)->select();
        if(!$orderes){
            return false;
        }

        $telephone = db('shops')->where('id',$orderes[0]['shop_id'])->value('telephone');

        $member_data = DB::name('member')->where(['id'=>$orderes[0]['user_id']])->find();
        $clientId = $member_data['appinfo_code'];
        $leixing = 1;
        // 启动事务
        Db::startTrans();
        try{
            Db::name('order_zong')->update(array('id'=>$orderzongs['id'],'state'=>1,'zf_type'=>$payType,'pay_time'=>time()));
            $pt_wallets = Db::name('pt_wallet')->where('id',1)->find();
            if($pt_wallets){
                Db::name('pt_wallet')->where('id',1)->setInc('price', $orderzongs['total_price']);
                Db::name('pt_detail')->insert(array('de_type'=>1,'sr_type'=>1,'price'=>$orderzongs['total_price'],'order_type'=>1,'order_id'=>$orderzongs['id'],'wat_id'=>$pt_wallets['id'],'time'=>time()));
            }

            foreach ($orderes as $vr){
                Db::name('order')->update(array('id'=>$vr['id'],'state'=>1,'zf_type'=>$payType,'pay_time'=>time()));
                $goodinfos = Db::name('order_goods')->where('order_id',$vr['id'])->field('id,goods_id,goods_num,hd_type,hd_id,shop_id')->select();

                foreach ($goodinfos as $kd => $vd){
                    $goods = Db::name('goods')->where('id',$vd['goods_id'])->field('id')->find();
                    if($goods){
                        Db::name('goods')->where('id',$vd['goods_id'])->setInc('sale_num',$vd['goods_num']);
                    }
                    $shops = Db::name('shops')->where('id',$vd['shop_id'])->field('id')->find();
                    if($shops){
                        Db::name('shops')->where('id',$vd['shop_id'])->setInc('sale_num',$vd['goods_num']);
                    }
                }

                // 处理积分商品订单
                if($vr['integral'] > 0){
                    $this->decIntegral($vr['user_id'],$vr['integral'],13,$vr['id']);
                }
            }




            if(count($orderes) == 1){
                if($orderes[0]['order_type'] == 2){
                    $pinorder_id = $orderes[0]['id'];
                    $pin_type = $orderes[0]['pin_type'];
                    $pin_id = $orderes[0]['pin_id'];
                    $userId = $orderes[0]['user_id'];

                    if($pin_type == 1){
                        $pintuans = Db::name('pintuan')
                                      ->where('id',$pin_id)
                                      ->where('tz_id',$userId)
                                      ->find();
                        $order_assembles = Db::name('order_assemble')
                                             ->where('pin_id',$pintuans['id'])
                                             ->where('order_id',$pinorder_id)
                                             ->where('pin_type',1)
                                             ->where('user_id',$userId)
                                             ->where('state',0)
                                             ->where('tui_status',0)
                                             ->find();
                        Db::name('pintuan')->where('id',$pintuans['id'])->update(array('state'=>1,'tuan_num'=>1));
                        Db::name('order_assemble')->where('id',$order_assembles['id'])->update(array('state'=>1));
                    }elseif($pin_type == 2){

                        $pintuans = Db::name('pintuan')
                                      ->where('id',$pin_id)
                                      ->where('tz_id','neq',$userId)
                                      ->find();
                        $order_assembles = Db::name('order_assemble')
                                             ->where('pin_id',$pintuans['id'])
                                             ->where('order_id',$pinorder_id)
                                             ->where('pin_type',2)
                                             ->where('user_id',$userId)
                                             ->where('state',0)
                                             ->where('tui_status',0)
                                             ->find();
                        Db::name('order_assemble')->where('id',$order_assembles['id'])->update(array('state'=>1));
                        Db::name('pintuan')->where('id',$pintuans['id'])->setInc('tuan_num',1);

                        $tuannums = Db::name('pintuan')->lock(true)->where('id',$pintuans['id'])->field('pin_num,tuan_num')->find();
                        if($tuannums['pin_num'] <= $tuannums['tuan_num']){
                            Db::name('pintuan')->where('id',$pintuans['id'])->update(array('pin_status'=>1,'com_time'=>time()));
                        }
                    }
                }
            }
            // 提交事务
            Db::commit();

            //首次付款，绑定上下级关系
            $distrib = new DistributionCommon();
            $distrib->bindDistribUser($userId);
            //发送短信
            $smsCodeModel = new SmsCodeModel();
            $smsCodeModel->send($telephone,9,'',$member_data['phone'].",".$order_sn.",".$orderzongs['total_price']);
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }


    }

    /***
     * 充值订单状态处理
     */
    public function doRechargeOrder($order_number,$payType=0){
        $clientId =  "";
        $charge_order = Db::name('recharge_order')->where('order_number',$order_number)->where('pay_status',0)->find();
        if($charge_order){
            $member_data = DB::name('member')->where(['id'=>$charge_order['uid']])->find();
            $clientId = $member_data['appinfo_code'];
            $price = $charge_order['order_price'];
            // 启动事务
            Db::startTrans();
            try{
                Db::name('recharge_order')->update(array('id'=>$charge_order['id'],'pay_status'=>1,'pay_way'=>$payType));
                $wallet = Db::name('wallet')->where('user_id',$charge_order['uid'])->find();
                //return $wallet;
                if($wallet){   //增加金额
                    $before_price = $wallet['price'];
                    $now_price = $price + $before_price;
                    Db::name('wallet')->update(array('id'=>$wallet['id'],'price'=>$now_price));
                    //增加明细
                    $ddata = [
                        'de_type'  => 1 ,  'sr_type'  => 4 , 'zc_type' => 0 , 'price' => $price,'time' =>time(),
                        'order_type' => 5 , 'order_id' => 0, 'tx_id' => 0, 'wat_id' => $wallet['id'],'user_id'=>$charge_order['uid']
                    ];
                    db('detail')->insert($ddata);
                    // 提交事务
                    Db::commit();
                }else{   //增加用户钱包值
                    $wdata = [
                        'price'  =>  $price,  'user_id'  => $charge_order['uid']
                    ];
                    $wid = db('wallet')->insertGetId($wdata);
                    //增加明细
                    $ddata = [
                        'de_type'  => 1 ,  'sr_type'  => 4 , 'zc_type' => 0 , 'price' => $price,'time' =>time(),
                        'order_type' => 5 , 'order_id' => 0, 'tx_id' => 0, 'wat_id' => $wid,'user_id'=>$charge_order['uid']
                    ];
                    db('detail')->insert($ddata);
                    Db::commit();

                }
            }catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $value = array('status'=>400,'mess'=>'创建充值明细信息失败','data'=>array('status'=>400));
            }
        }
    }

    /***
     * 商家入驻保证金订单状态处理
     */
    public function doRzOrder($order_sn,$payType=0){
        $rzorders = Db::name('rz_order')->where('ordernumber',$order_sn)->where('state',0)->find();
        if($rzorders){
            // 启动事务
            Db::startTrans();
            try{
                Db::name('rz_order')->update(array('id'=>$rzorders['id'],'state'=>1,'zf_type'=>$payType,'pay_time'=>time()));
                Db::name('apply_info')->update(array('state'=>1,'pay_time'=>time(),'id'=>$rzorders['apply_id']));

                $pt_wallets = Db::name('pt_wallet')->where('id',1)->find();
                if($pt_wallets){
                    Db::name('pt_wallet')->where('id',1)->setInc('price', $rzorders['total_price']);
                    Db::name('pt_detail')->insert(array('de_type'=>1,'sr_type'=>1,'price'=>$rzorders['total_price'],'order_type'=>3,'order_id'=>$rzorders['id'],'wat_id'=>$pt_wallets['id'],'time'=>time()));
                }
                Db::commit();
                //发送短信
                $smsCodeModel = new SmsCodeModel();
                $smsCodeModel->send(get_config_value('web_telephone'),8,'',$rzorders['telephone'].",".$order_sn.",".$rzorders['total_price']);
            } catch (\Exception $e) {
                Db::rollback();
            }
        }
    }

    /***
     * 获取支付方式的开启状态
     */
    public function getPayOpenStatus(){
        //获取用户信息
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess'],$tokenRes['data']);
        }

        $payList = Db::name('pay_type')->order('id asc')->select();
        datamsg(200,'获取成功',array('pay_list'=>$payList));
    }

}
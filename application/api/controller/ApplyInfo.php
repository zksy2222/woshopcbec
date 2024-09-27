<?php
namespace app\api\controller;
use app\api\controller\Common;
use app\api\model\Common as CommonModel;
use think\Db;
use EasyWeChat\Factory;
use app\common\model\SmsCode as SmsCodeModel;

class ApplyInfo extends Common{

    public function panduan(){
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess']);
        }else{
            $userId = $tokenRes['user_id'];
        }

        $members = Db::name('member')->where('id',$userId)->field('phone,email,password')->find();
        if($members['phone'] || $members['email']){
            $applyinfos = Db::name('apply_info')
                            ->where('user_id',$userId)
                            ->field('id,checked,qht,state,complete')
                            ->order('apply_time desc')
                            ->find();
            $zhuangtai = !$applyinfos ? 1 : 2;
        }else{
            $zhuangtai = 4;
        }

        datamsg(200,'获取判断信息成功',array('zhuangtai'=>$zhuangtai));
    }

    //申请入驻获取相关信息
    public function ruzhuinfo(){
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess']);
        }else{
            $userId = $tokenRes['user_id'];
        }

        $members = Db::name('member')->where('id',$userId)->field('phone,email,password')->find();
        if(!$members['phone'] && !$members['email']){
            $cominfos = array('industryres'=>array(),'prores'=>array(),'zhuangtai'=>4);
            datamsg(200,'获取信息成功',$cominfos);
        }

        $applyinfos = Db::name('apply_info')->where('user_id',$userId)->field('id,checked,qht,state,complete')->order('apply_time desc')->find();
        if(!$applyinfos){
            $prores = Db::name('province')->where('checked',1)->where('pro_zs',1)->field('id,pro_name,zm')->order('sort asc')->select();
            $industryres = Db::name('industry')->where('is_show',1)->field('id,industry_name,remind')->order('sort asc')->select();
            $cominfos = array('industryres'=>$industryres,'prores'=>$prores,'zhuangtai'=>1);
        }else{
            if($applyinfos['checked'] == 2 && $applyinfos['qht'] == 0 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                $prores = Db::name('province')->where('checked',1)->where('pro_zs',1)->field('id,pro_name,zm')->order('sort asc')->select();
                $industryres = Db::name('industry')->where('is_show',1)->field('id,industry_name,remind')->order('sort asc')->select();
                $cominfos = array('industryres'=>$industryres,'prores'=>$prores,'zhuangtai'=>2);
            }else{
                $cominfos = array('industryres'=>array(),'prores'=>array(),'zhuangtai'=>3);
            }
        }

        datamsg(200,'获取信息成功',$cominfos);
    }

    //通过行业获取类目
    public function getcates(){
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess']);
        }else{
            $userId = $tokenRes['user_id'];
        }

        $where = array();
        if(!input('post.indus_id')){
            datamsg(400,'请选择主营行业');
        }
        $indus_id = input('post.indus_id');
        $industrys = Db::name('industry')
                       ->where('id',$indus_id)
                       ->where('is_show',1)
                       ->field('id,cate_id_list')
                       ->find();
        if(!$industrys){
            datamsg(400,'找不到相关行业');
        }
        $goodsids = explode(',', $industrys['cate_id_list']);

        $where['id'] = array('in',$goodsids);
        $where['pid'] = 0;
        $where['is_show'] = 1;

        $list = Db::name('category')->where($where)->field('id,cate_name')->order('sort asc')->select();
        datamsg(200,'获取经营类目信息成功',$list);
    }




    //店铺申请
    public function applyShop(){
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){ // 400返回错误描述
            return json($tokenRes);
        }else{ // 成功则返回$userId
            $userId = $tokenRes['user_id'];
        }

        $members = Db::name('member')->where('id',$userId)->field('phone,email,password')->find();

        if($members['phone'] || $members['email']){
            $data = input('post.');
            $applyinfos = Db::name('apply_info')->where('user_id',$userId)->field('id,checked,qht,state,complete')->order('apply_time desc')->find();

            $zhuangtai = 0;

            if($applyinfos && $applyinfos['checked'] == 2 && $applyinfos['qht'] == 0 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                $zhuangtai = 1;
            }

            if(!$applyinfos || $zhuangtai == 1){
                $yzresult = $this->validate($data,'ComapplyInfo');
                if(true !== $yzresult){
                    datamsg(400,$yzresult);
                }else{
                    if(!empty($data['apply_type'])){
                        if(!empty($data['indus_id'])){
                            $industrys = Db::name('industry')->where('id',$data['indus_id'])->where('is_show',1)->field('id,cate_id_list')->find();
                            if(!$industrys){
                                datamsg(400,'请选择行业');
                            }
                        }else{
                            datamsg(400,'请选择行业');
                        }

                        if(!empty($data['cate_ids'])){
                            $cate_ids = $data['cate_ids'];
                            $cate_ids = trim($cate_ids);
                            $cate_ids = str_replace('，', ',', $cate_ids);
                            $cate_ids = rtrim($cate_ids,',');

                            if($cate_ids){
                                $cateids = explode(',', $cate_ids);
                                if($cateids && is_array($cateids)){
                                    $cateids = array_unique($cateids);

                                    foreach ($cateids as $v){
                                        if(!empty($v)){
                                            if(strpos(','.$industrys['cate_id_list'].',',','.$v.',') !== false){
                                                $cates = Db::name('category')->where('id',$v)->where('pid',0)->where('is_show',1)->field('id')->find();
                                                if(!$cates){
                                                    datamsg(400,'经营类目信息有误，申请失败');
                                                }
                                            }else{
                                                datamsg(400,'经营类目信息有误，申请失败');
                                            }
                                        }else{
                                            datamsg(400,'经营类目信息有误，申请失败');
                                        }
                                    }
                                }else{
                                    datamsg(400,'经营类目信息有误，申请失败');
                                }
                            }else{
                                datamsg(400,'经营类目信息有误，申请失败');
                            }
                        }else{
                            datamsg(400,'请选择经营类目');
                        }

//                        $pro_id = $data['pro_id'];
//                        $city_id = $data['city_id'];
//                        $area_id = $data['area_id'];
//
//                        $pros = Db::name('province')->where('id',$pro_id)->where('checked',1)->where('pro_zs',1)->field('id,pro_name')->find();

                        $zlpicres = array();
                        if(empty($data['logo'])){
                            datamsg(400,'请上传店铺logo图片');
                        }

                        if(empty($data['sfzz_pic'])){
                            datamsg(400,'请上传经营者身份证正面照片');
                        }

                        if(empty($data['sfzb_pic'])){
                            datamsg(400,'请上传经营者身份证背面照片');
                        }

                        if(empty($data['frsfz_pic'])){
                            datamsg(400,'请上传经营者手持身份证正面照片');
                        }

                        if($data['apply_type'] == 2){ // 企业店铺必填营业执照
                            if(empty($data['zhizhao'])){
                                datamsg(400,'请上传营业执照');
                            }
                        }else{
                            $data['zhizhao'] = '';
                        }

                        if(empty($data['agent_id'])){
                            datamsg(400,'代理商邀请码必填');
                        }else{
                            $agent_user = Db::name("agent")->where("invite_code",$data['agent_id'])->find();
                            if(!$agent_user){
                                datamsg(-1,'代理商邀请码错误，没有找到代理商');
                            }
                            $data['agent_id'] = $agent_user['id'];
                        }

                            // 启动事务
                            Db::startTrans();
                            try{
                                $apply_id = Db::name('apply_info')->insertGetId(array(
                                    'indus_id'=>$data['indus_id'],
                                    'shop_name'=>$data['shop_name'],
                                    'shop_desc'=>$data['shop_desc'],
                                    'logo'=>$data['logo'],
                                    'contacts'=>$data['contacts'],
                                    'telephone'=>$data['telephone'],
                                    'email'=>$data['email'],
                                    'pro_id'=>0,
                                    'city_id'=>0,
                                    'area_id'=>0,
                                    'province'=>$data['province'],
                                    'city'=>$data['city'],
                                    'area'=>$data['area'],
                                    'shengshiqu'=>"{$data['province']} {$data['city']} {$data['area']}",//$pros['pro_name'].$citys['city_name'].$areas['area_name'],
                                    'address'=>$data['address'],
                                    'sfz_num'=>$data['sfz_num'],
                                    'sfzz_pic'=>$data['sfzz_pic'],
                                    'sfzb_pic'=>$data['sfzb_pic'],
                                    'frsfz_pic'=>$data['frsfz_pic'],
                                    'zhizhao'=>$data['zhizhao'],
                                    'apply_type'=>$data['apply_type'],
                                    'service_rate'=>$data['service_rate'],
                                    'apply_time'=>time(),
                                    'user_id'=>$userId,
                                    'agent_id'=>$data['agent_id'],
                                ));

                                if($apply_id){
                                    foreach ($cateids as $val){
                                        Db::name('manage_apply')->insert(array('cate_id'=>$val,'apply_id'=>$apply_id,'apply_time'=>time()));
                                    }
                                    if(!empty($zlpicres)){
                                        foreach ($zlpicres as $v){
                                            Db::name('apply_ziliaopic')->insert(array('img_url'=>$v,'apply_id'=>$apply_id));
                                        }
                                    }
                                }
                                // 提交事务
                                Db::commit();
                                //发送短信
                                $smsCodeModel = new SmsCodeModel();
                                $smsCodeModel->send(get_config_value('web_telephone'),7,'',$data['telephone'].",".$apply_id);
                                datamsg(200,'提交资料成功，请待审核');
                            } catch (\Exception $e) {
                                // 回滚事务
                                Db::rollback();
                                datamsg(400,'提交资料失败',array('status'=>$e->getMessage()));
                            }

                    }else{
                        datamsg(400,'缺少入驻类型参数');
                    }
                }
            }else{
                datamsg(400,'信息有误，提交失败');
            }
        }else{
            datamsg(400,'请先完成账号安全设置，提交失败');
        }
    }
    //获取入驻审核状态信息
    public function applystatus(){
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess'],$tokenRes['data']);
        }else{
            $userId = $tokenRes['user_id'];
        }

        $applyinfos = Db::name('apply_info')->where('user_id',$userId)->field('id,indus_id,checked,qht,state,complete,remarks')->order('apply_time desc')->find();
        if($applyinfos){
            $xinxi = '';
            $remarks = '';
            $industrys = array();

            if($applyinfos['checked'] == 2 && $applyinfos['qht'] == 0 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                $zhuangtai = 6;
                $xinxi = '您提交的商家申请资料被拒绝';
                $remarks = $applyinfos['remarks'];
            }elseif($applyinfos['checked'] == 0 && $applyinfos['qht'] == 0 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                $zhuangtai = 1;
                $xinxi = '您提交的入驻申请正在审核中，请耐心等待';
            }elseif($applyinfos['checked'] == 1 && $applyinfos['qht'] == 0 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                $zhuangtai = 2;
                $xinxi = '您提交的入驻申请已审核通过，请等待签署入驻合同协议';
            }elseif($applyinfos['checked'] == 1 && $applyinfos['qht'] == 1 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
                $zhuangtai = 3;
                $xinxi = '您的入驻合同协议已签署，请缴纳保证金完成入驻';
                $rzorders = Db::name('rz_order')->where('user_id',$userId)->where('apply_id',$applyinfos['id'])->field('id,state')->find();
                if(!$rzorders || $rzorders['state'] == 0){
                    $industrys = Db::name('industry')->where('id',$applyinfos['indus_id'])->where('is_show',1)->field('id,industry_name,ser_price,remind')->find();
                    if(!$industrys){
                        datamsg(400,'信息错误');
                    }
                }else{
                    datamsg(400,'信息错误');
                }
            }elseif($applyinfos['checked'] == 1 && $applyinfos['qht'] == 1 && $applyinfos['state'] == 2 && $applyinfos['complete'] == 0){       //修改无需缴纳保证金
                $zhuangtai = 3;
                $xinxi = '您的入驻合同协议已签署，等待管理员开通';
                $rzorders = Db::name('rz_order')->where('user_id',$userId)->where('apply_id',$applyinfos['id'])->field('id,state')->find();
                if(!$rzorders || $rzorders['state'] == 0){
                    $industrys = Db::name('industry')->where('id',$applyinfos['indus_id'])->where('is_show',1)->field('id,industry_name,ser_price,remind')->find();
                    if(!$industrys){
                        datamsg(400,'信息错误');
                    }
                }else{
                    datamsg(400,'信息错误');
                }
            }elseif($applyinfos['checked'] == 1 && $applyinfos['qht'] == 1 && $applyinfos['state'] == 1 && $applyinfos['complete'] == 0){
                $zhuangtai = 4;
                $xinxi = '您的入驻流程已完成，平台将及时为您开通商家后台，请耐心等待';
            }elseif($applyinfos['checked'] == 1 && $applyinfos['qht'] == 1 && $applyinfos['state'] == 1 && $applyinfos['complete'] == 1){
                $zhuangtai = 5;
                $xinxi = '您的账号已开通商家，目前暂且支持一个账号申请入驻一家商家';
            }else{
                datamsg(400,'信息错误');
            }

            $ruzhuinfos = array('zhuangtai'=>$zhuangtai,'xinxi'=>$xinxi,'remarks'=>$remarks,'industrys'=>$industrys,'shop_is_earnest'=>$this->webconfig['shop_is_earnest']);
            datamsg(200,'获取入驻申请状态信息成功',set_lang($ruzhuinfos));
        }else{
            datamsg(400,'找不到相关入驻申请，请先提交入驻申请');
        }
    }

    public function orderzhifu(){

        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess'],$tokenRes['data']);
        }else{
            $userId = $tokenRes['user_id'];
        }

        if(!in_array(input('post.zf_type','0'), array(1,2))){
            datamsg(400,'支付方式错误');
        }
        $zf_type = input('post.zf_type');

        $applyinfos = Db::name('apply_info')->where('user_id',$userId)->order('apply_time desc')->find();
        if(!$applyinfos){
            datamsg(400,'请先提交申请资料');
        }
        if($applyinfos['checked'] == 1 && $applyinfos['qht'] == 1 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
            $industrys = Db::name('industry')->where('id',$applyinfos['indus_id'])->where('is_show',1)->field('id,ser_price')->find();
            if(!$industrys){
                datamsg(400,'行业信息错误，支付失败');
            }
            $rzorders = Db::name('rz_order')->where('user_id',$userId)->where('apply_id',$applyinfos['id'])->field('id,ordernumber,state,total_price')->find();
            if($rzorders){
                if($rzorders['state'] != 0){
                    datamsg(400,'信息错误，支付失败');
                }
                if($rzorders['total_price'] != $industrys['ser_price']){
                    $ordernumber = 'R'.date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
                    $dingdan = Db::name('rz_order')->where('ordernumber',$ordernumber)->find();
                    if($dingdan){
                        datamsg(400,'系统错误，支付失败');
                    }
                    $count = Db::name('rz_order')->update(array('id'=>$rzorders['id'],'ordernumber'=>$ordernumber,'total_price'=>$industrys['ser_price']));
                    if($count > 0){
                        $rzorders = Db::name('rz_order')->where('id',$rzorders['id'])->where('user_id',$userId)->field('id,ordernumber,state,total_price')->find();
                    }else{
                        datamsg(400,'系统错误，支付失败');
                    }

                }
                $zhuangtai = 'zhuangtai';

            }else{
                $ordernumber = 'R'.date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
                $dingdan = Db::name('rz_order')->where('ordernumber',$ordernumber)->find();
                if($dingdan){
                    datamsg(400,'提交订单失败');
                }
                $order_id = Db::name('rz_order')->insertGetId(array(
                    'ordernumber'=>$ordernumber,
                    'contacts'=>$applyinfos['contacts'],
                    'telephone'=>$applyinfos['telephone'],
                    'shop_name'=>$applyinfos['shop_name'],
                    'total_price'=>$industrys['ser_price'],
                    'pro_id'=>$applyinfos['pro_id'],
                    'city_id'=>$applyinfos['city_id'],
                    'area_id'=>$applyinfos['area_id'],
                    'state'=>0,
                    'user_id'=>$userId,
                    'apply_id'=>$applyinfos['id'],
                    'indus_id'=>$industrys['id'],
                    'addtime'=>time()
                ));
                if($order_id){
                    $rzorders = Db::name('rz_order')->where('id',$order_id)->where('user_id',$userId)->field('id,ordernumber,state,total_price')->find();
                }else{
                    datamsg(400,'提交订单失败');
                }

            }

            if(!$rzorders){
                datamsg(400,'信息错误，支付失败');
            }
            $webconfig = $this->webconfig;
            $body = '商家入驻保证金';//支付说明
            switch($zf_type){
                case 1:
                    $reoderSn = $rzorders['ordernumber'];
                    $money = $rzorders['total_price'];
                    $notify_url = $webconfig['weburl']."/api/AliPay/aliNotify";
                    $AliPayHelper = new AliPay();
                    if(input('post.h5') == 1){
                        $return_url = $webconfig['weburl'].'/h5/#/pagesC/applyshop/applyStatus';
                        $data = $AliPayHelper->getWapPayInfo($reoderSn,$body,$money,$notify_url,$return_url);
                        datamsg(200,'创建订单成功',array('order_number'=>$reoderSn,'infos'=>$data));
                    }else{
                        $data = $AliPayHelper->getPrePayOrder($body,$money,$reoderSn,$notify_url);
                        datamsg(200,'创建订单成功',array('order_number'=>$reoderSn,'infos'=>$data));
                    }

                    break;
                case 2:
                    //获取订单号
                    $reoderSn = $rzorders['ordernumber'];
                    //获取支付金额
                    $money = $rzorders['total_price'];
                    $wx = new WxPay();
                    $out_trade_no = $reoderSn;//订单号
                    $total_fee = $money * 100;//支付金额(乘以100)
                    $time_start = time();
                    $time_expire = time()+3600;
                    $notify_url = $webconfig['weburl'].'/api/WxPay/wxNotify';//回调地址

                    if(input('post.wechat_miniprogram') == 1){ // 小程序微信支付
                        $openId = Db::name('member')->where('id',$userId)->value('openid');
                        if (!$openId) {
                            return json(array('status'=>400,'mess'=>'没有openid，支付失败','data'=>array('status'=>400)));
                        }
                        $wechatePay = Factory::payment($this->wechatPayConfig);
                        $jssdk = $wechatePay->jssdk;

                        $order = $wechatePay->order->unify([
                            'body' => $body,
                            'out_trade_no' => $out_trade_no,
                            'total_fee' => $total_fee,
                            'notify_url' => $notify_url, // 支付结果通知网址，如果不设置则会使用配置里的默认地址
                            'trade_type' => 'JSAPI', // 请对应换成你的支付方式对应的值类型
                            'openid' => $openId
                        ]);
                        $wechatePayRes = $jssdk->bridgeConfig($order['prepay_id'], false); // 返回数组
                        datamsg(200,'创建订单成功',array('order_number'=>$rzorders['ordernumber'],'infos'=>$wechatePayRes,'order'=>$order));
                    }else{
                        $order = $wx->getPrePayOrder($body, $out_trade_no, $total_fee, $time_start, $time_expire, $notify_url);//调用微信支付的方法
                        if($order['prepay_id']){
                            //判断返回参数中是否有prepay_id
                            $order1 = $wx->getOrder($order['prepay_id']);//执行二次签名返回参数
                            datamsg(200,'成功',array('ordernumber'=>$rzorders['ordernumber'],'infos'=>$order1));
                        }else{
                            datamsg(400,$order['err_code_des']);
                        }
                    }
                    break;
            }

        }else{
            datamsg(400,'资料审核尚未通过');
        }
    }

    public function addApplyPayOrder(){
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess'],$tokenRes['data']);
        }else{
            $userId = $tokenRes['user_id'];
        }

        $applyinfos = Db::name('apply_info')->where('user_id',$userId)->order('apply_time desc')->find();
        if(!$applyinfos){
            datamsg(400,'请先提交申请资料');
        }
        if($applyinfos['checked'] == 1 && $applyinfos['qht'] == 1 && $applyinfos['state'] == 0 && $applyinfos['complete'] == 0){
            $industrys = Db::name('industry')->where('id',$applyinfos['indus_id'])->where('is_show',1)->field('id,ser_price')->find();
            if(!$industrys){
                datamsg(400,'行业信息错误，支付失败');
            }
            $rzorders = Db::name('rz_order')->where('user_id',$userId)->where('apply_id',$applyinfos['id'])->field('id,ordernumber,state,total_price')->find();
            if($rzorders){
                if($rzorders['state'] != 0){
                    datamsg(400,'信息错误，支付失败');
                }
                if($rzorders['total_price'] != $industrys['ser_price']){
                    $ordernumber = 'R'.date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
                    $dingdan = Db::name('rz_order')->where('ordernumber',$ordernumber)->find();
                    if($dingdan){
                        datamsg(400,'系统错误，支付失败');
                    }
                    $count = Db::name('rz_order')->update(array('id'=>$rzorders['id'],'ordernumber'=>$ordernumber,'total_price'=>$industrys['ser_price']));
                    if($count > 0){
                        $rzorders = Db::name('rz_order')->where('id',$rzorders['id'])->where('user_id',$userId)->field('id,ordernumber,state,total_price')->find();
                    }else{
                        datamsg(400,'系统错误，支付失败');
                    }

                }
                $zhuangtai = 'zhuangtai';

            }else{
                $ordernumber = 'R'.date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
                $dingdan = Db::name('rz_order')->where('ordernumber',$ordernumber)->find();
                if($dingdan){
                    datamsg(400,'提交订单失败');
                }
                $order_id = Db::name('rz_order')->insertGetId(array(
                    'ordernumber'=>$ordernumber,
                    'contacts'=>$applyinfos['contacts'],
                    'telephone'=>$applyinfos['telephone'],
                    'shop_name'=>$applyinfos['shop_name'],
                    'total_price'=>$industrys['ser_price'],
                    'pro_id'=>$applyinfos['pro_id'],
                    'city_id'=>$applyinfos['city_id'],
                    'area_id'=>$applyinfos['area_id'],
                    'state'=>0,
                    'user_id'=>$userId,
                    'apply_id'=>$applyinfos['id'],
                    'indus_id'=>$industrys['id'],
                    'addtime'=>time()
                ));
                if($order_id){
                    $rzorders = Db::name('rz_order')->where('id',$order_id)->where('user_id',$userId)->field('id,ordernumber,state,total_price')->find();
                }else{
                    datamsg(400,'提交订单失败');
                }

            }

            datamsg(200,'获取入驻订单信息', $rzorders);

        }else{
            datamsg(400,'资料审核尚未通过');
        }
    }

}
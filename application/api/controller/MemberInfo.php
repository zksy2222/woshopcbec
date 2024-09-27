<?php
namespace app\api\controller;
use app\api\controller\Common;
use app\common\model\Config;
use app\api\model\Common as CommonModel;
use think\Cache;
use think\Db;
use app\api\model\DistributionUser;
use app\common\Lookup;
use EasyWeChat\Factory;
use app\api\model\Member as MemberModel;
use app\common\model\SmsCode as SmsCodeModel;

class MemberInfo extends Common{

    //读取用户资料
    public function readprofile(){
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){ // 400返回错误描述
            datamsg(400,$tokenRes['mess']);
        }else{ // 成功则返回$userId
            $userId = $tokenRes['user_id'];
        }

        $members = Db::name('member')->where('id',$userId)->field('id,user_name,phone,email,password,headimgurl,integral,sex,birth,email,oauth,shop_id')->find();
        if(!$members){
	        datamsg(400,'信息有误,获取失败',array('status'=>400));
        }
        $wallets = Db::name('wallet')->where('user_id',$userId)->field('price')->find();
        $members['wallet_price'] = $wallets['price'];
        $coupon_num = Db::name('member_coupon')->alias('a')->join('sp_coupon b','a.coupon_id = b.id','INNER')->join('sp_shops c','a.shop_id = c.id','INNER')->where('a.user_id',$userId)->where('a.is_sy',0)->where('b.onsale',1)->where('c.open_status',1)->count();
        $members['coupon_num'] = $coupon_num;

        $collgoods_count = Db::name('coll_goods')->where('user_id',$userId)->count();
        $collshops_count = Db::name('coll_shops')->where('user_id',$userId)->count();
        $members['coll_num'] = $collgoods_count+$collshops_count;

        $webconfig = $this->webconfig;

        $members['headimgurl'] = url_format($members['headimgurl'],$webconfig['weburl']);
        $members['birth'] = $members['birth'] ? date('Y-m-d',$members['birth']) : '';


        $memberLevelInfo = $this->getMemberLevelInfo($members['integral']);
        $members['rank'] = $memberLevelInfo['sort'];
        $members['rank_name'] = $memberLevelInfo['level_name'];
        $nextMemberLevelInfo = Db::name('member_level')->where('sort','gt',$memberLevelInfo['sort'])->order('sort ASC')->find();
        $members['rank_percent'] = round($members['integral']/$nextMemberLevelInfo['points_min'],2)*100;
        $members['next_rank_integral'] = $nextMemberLevelInfo['points_min'];
        $members['privilege'] = array(
            'returns' => 1,
            'customerService' =>1,
            'freeEvaluation' =>1,
            'identification' => 1,
            'holidayGift' => 1,
            'preemptive' =>1,
            'offlineActivity' =>1
        );
        $members['rz_zt'] = 0;
        if(($members['phone'] || $members['email']) && $members['shop_id'] > 0){
            $where = array('shop_id' => $members['shop_id'], 'checked' => 1, 'qht' => 1, 'state' => 1, 'complete' => 1);
            $applyinfos = Db::name('apply_info')->where($where)->field('id,checked,qht,state,complete')->order('apply_time desc')->find();
            if($applyinfos){
                $members['rz_zt'] = 1;
            }else{
                $where = array('shop_id' => $members['shop_id'], 'checked' => 1, 'qht' => 1, 'state' => 2, 'complete' => 1);
                $applyinfos_no = Db::name('apply_info')->where($where)->field('id,checked,qht,state,complete')->order('apply_time desc')->find();
                if($applyinfos_no){
                    $members['rz_zt'] = 1;
                }
            }
        }

        //平台消息
        $pt_msg = Db::name('notification')->where('status',1)->count();
        //客服消息
        $kf_msg = Db::name('chat_message')->where('fromid',input('post.token'))->whereOr('toid',input('post.token'))->count();

        $members['msg_num'] = $pt_msg+$kf_msg;



        //订单数 1:待支付 2:待发货 3:待收货 4:待评价 5:退款/售后 6:全部

        //待付款
        $where1 = array('a.user_id'=>$userId,'a.state'=>0,'a.fh_status'=>0,'a.order_status'=>0,'a.is_show'=>1);
        $sort1 = array('a.addtime'=>'desc','a.id'=>'desc');
        $num1 = Db::name('order')->alias('a')->field('a.id,a.ordernumber,a.coupon_id,a.total_price,a.state,a.fh_status,a.order_status,a.shouhou,a.ping,a.is_show,a.ping,a.order_type,a.pin_type,a.pin_id,a.shop_id,a.zdsh_time,a.time_out,b.shop_name')->join('sp_shops b','a.shop_id = b.id','INNER')->where($where1)->order($sort1)->count();
        $members['pay_num'] = $num1;
        //待发货
        $where2 = array('a.user_id'=>$userId,'a.state'=>1,'a.fh_status'=>0,'a.order_status'=>0,'a.is_show'=>1);
        $sort2 = array('a.pay_time'=>'desc','a.id'=>'desc');
        $num2 = Db::name('order')->alias('a')->field('a.id,a.ordernumber,a.coupon_id,a.total_price,a.state,a.fh_status,a.order_status,a.shouhou,a.ping,a.is_show,a.ping,a.order_type,a.pin_type,a.pin_id,a.shop_id,a.zdsh_time,a.time_out,b.shop_name')->join('sp_shops b','a.shop_id = b.id','INNER')->where($where2)->order($sort2)->count();
        $members['send_num'] = $num2;
        //待收货
        $where3 = array('a.user_id'=>$userId,'a.state'=>1,'a.fh_status'=>1,'a.order_status'=>0,'a.is_show'=>1);
        $sort3 = array('a.fh_time'=>'desc','a.id'=>'desc');
        $num3 = Db::name('order')->alias('a')->field('a.id,a.ordernumber,a.coupon_id,a.total_price,a.state,a.fh_status,a.order_status,a.shouhou,a.ping,a.is_show,a.ping,a.order_type,a.pin_type,a.pin_id,a.shop_id,a.zdsh_time,a.time_out,b.shop_name')->join('sp_shops b','a.shop_id = b.id','INNER')->where($where3)->order($sort3)->count();
        $members['shou_num'] = $num3;
        //待评价
        $where4 = array('a.user_id'=>$userId,'a.state'=>1,'a.fh_status'=>1,'a.order_status'=>1,'a.ping'=>0,'a.is_show'=>1);
        $sort4 = array('a.coll_time'=>'desc','a.id'=>'desc');
        $num4 = Db::name('order')->alias('a')->field('a.id,a.ordernumber,a.coupon_id,a.total_price,a.state,a.fh_status,a.order_status,a.shouhou,a.ping,a.is_show,a.ping,a.order_type,a.pin_type,a.pin_id,a.shop_id,a.zdsh_time,a.time_out,b.shop_name')->join('sp_shops b','a.shop_id = b.id','INNER')->where($where4)->order($sort4)->count();
        $members['ping_num'] = $num4;

        //退换货
        $where5 = array('user_id'=>$userId,'apply_status'=>1);
        //$where5 = array('user_id'=>$userId);
        $sort5 = array('apply_time'=>'desc');
        $num5 = Db::name('th_apply')->where($where5)->order($sort5)->count();
        $num6 = Db::name('th_apply')->where(array('user_id'=>$userId,'apply_status'=>0))->order($sort5)->count();


        //评价
        /*
        $where0 = array('a.user_id'=>$userId,'a.state'=>1,'a.fh_status'=>1,'a.order_status'=>1,'a.ping'=>1,'a.is_show'=>1);
        $sort0 = array('a.coll_time'=>'desc','a.id'=>'desc');
        $num0 = Db::name('order')->alias('a')->field('a.id,a.ordernumber,a.coupon_id,a.total_price,a.state,a.fh_status,a.order_status,a.shouhou,a.ping,a.is_show,a.ping,a.order_type,a.pin_type,a.pin_id,a.shop_id,a.zdsh_time,a.time_out,b.shop_name')->join('sp_shops b','a.shop_id = b.id','INNER')->where($where0)->order($sort0)->count();
        $members['myping_num'] = $num0+$num4;
        */
        //$members['huan_num'] = $num5;
        $members['huan_num'] = $num5+$num6;

        //购物车数量
        $shopcar_num = Db::name('cart')->alias('a')->field('a.id,a.goods_id,a.goods_attr,a.num,a.shop_id,b.goods_name,b.shop_price,b.thumb_url,c.shop_name')->join('sp_goods b','a.goods_id = b.id','INNER')->join('sp_shops c','a.shop_id = c.id','INNER')->where('a.user_id',$userId)->where('b.onsale',1)->where('c.open_status',1)->order('a.add_time desc')->count();

        $members['shopcar_num']  = $shopcar_num;

        unset($members['password']);

        //是否为分销商
        $distribModel = new DistributionUser();
        $distrib = $distribModel->isDistributionUser($userId);
        $members['is_distribution'] = Lookup::isNotDistrib;
        if ($distrib) {
            $members['is_distribution'] = Lookup::isDistrib;
        }

        $members['role'] = get_user_role($userId);


        //获取商家端用户登录token
        $members['shopapi_url'] = "";
        if($members['shop_id']){
            $shopapi_url = Db("config")->where("ename","shopapi_url")->find();
            $shopapi_url = $shopapi_url ? $shopapi_url['value'] : '';
            $shop_admin = Db::name("shop_admin")->where("shop_id",$members['shop_id'])->value("id");
            if($shop_admin){
                $shop_token = Db::name("member_token")->where("shop_admin_id",$shop_admin)->value("token");
                if($shop_token){
                    $members['shopapi_url'] = "{$shopapi_url}?token={$shop_token}";
                }else{
                    $shop_user_admin = Db::name("member_token")->where("user_id",$userId)->find();
                    if(empty($shop_user_admin['shop_admin_id'])){
                        Db::name("member_token")->where("user_id",$userId)->update(['shop_admin_id'=>$shop_admin]);
                    }
                    $members['shopapi_url'] = "{$shopapi_url}?token={$shop_user_admin['token']}";
                }
            }
        }

        //平台是否安装了shopapi模块
        $members['plugin_shopapi'] = 0;
        $plugin_shopapi = Db("plugin")->where(['name'=>"shopapi","status"=>1,"isclose"=>1])->find();
        if($plugin_shopapi){
            $members['plugin_shopapi'] = 1;
        }


        datamsg(200,'获取用户资料成功',$members);
    }

    //设置个人基本资料
    public function editprofile(){
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){ // 400返回错误描述
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{ // 成功则返回$userId
		    $userId = $tokenRes['user_id'];
	    }
        $data = input('post.');
        $yzresult = $this->validate($data,'Member.edit');
        if(true !== $yzresult){
            datamsg(400,$yzresult);
        }else{
            $repic = Db::name('member')->where('id',$userId)->value('headimgurl');

            $datainfo = array();
            if(!empty($data['user_name'])){
                $datainfo['user_name'] = $data['user_name'];
            }

            if(!empty($data['sex'])){
                $datainfo['sex'] = $data['sex'];
            }

            if(!empty($data['birth'])){
                $datainfo['birth'] = strtotime($data['birth']);
            }

            if(!empty($data['email'])){
                $datainfo['email'] = $data['email'];
            }

            if(!empty($data['headimgurl'])){
                $datainfo['headimgurl'] = $data['headimgurl'];
            }

            $datainfo['id'] = $userId;
            // 启动事务
            Db::startTrans();
            try{
                Db::name('member')->update($datainfo);
                if(!empty($data['headimgurl'])){
                    if(!empty($repic) && file_exists('./'.$repic)){
                        @unlink('./'.$repic);
                    }
                    //4完善信息（上传头像）
                    $num = $this->getIntegralValue(4);//获取积分
                    $this->addIntegral($userId,$num,4);
                }
                // 提交事务
                Db::commit();
                $value = array('data'=>array('status'=>200),'info'=>$datainfo);
                datamsg(200,'设置成功',$value);
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                datamsg(400,'设置失败');
            }
        }
    }

    //找回密码
    public function findBackPwd(){
	    $tokenRes = $this->checkToken(0);
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess']);
	    }

        $data['phone'] = input('post.phone');
        $data['phonecode'] = input('post.phonecode');
        $data['password'] = input('post.password');

        $validate = $this->validate($data,'Member.find_back_pwd');
        if($validate !== true){
            datamsg(400,$validate);
        }

	    $smsCodeModel = new SmsCodeModel();
	    $checkSmsCode = $smsCodeModel->checkSmsCode($data['phonecode'],$data['phone'],3);
	    if($checkSmsCode['status'] == 400){
	        datamsg(400,$checkSmsCode['mess']);
        }

	    $userModel = new MemberModel();
        $updatePwd = $userModel->save(['password'=>pwdEncrypt($data['password'])],['phone'=>$data['phone']]);
        if($updatePwd){
            datamsg(200,'密码重置成功');
        }else{
            datamsg(400,'密码重置失败');
        }
    }

    //修改登录密码
    public function editpwd(){
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){ // 400返回错误描述
            datamsg(400,$tokenRes['mess']);
        }else{ // 成功则返回$userId
            $userId = $tokenRes['user_id'];
        }
        $data['phone'] = input('post.phone');
        $data['phonecode'] = input('post.code');
        $data['password'] = input('post.new_pwd');



        $validate = $this->validate($data,'Member.edit_pwd');
        if($validate !== true){
            datamsg(400,$validate);
        }

        $userModel = new MemberModel();
        $userInfo = $userModel->where('id',$userId)->field('phone,password,paypwd')->find();
        // 判断手机号是不是属于该用户
        if($data['phone'] != $userInfo['phone']){
            datamsg(400,'手机号错误');
        }

        // 判断验证码
        $smsCodeModel = new SmsCodeModel();
        $checkSmsCode = $smsCodeModel->checkSmsCode($data['phonecode'],$data['phone'],3);
        if($checkSmsCode['status'] == 400){
            datamsg(400,$checkSmsCode['mess']);
        }

        $data['password'] = pwdEncrypt($data['password']);
        if($userInfo['paypwd'] == $data['password']){
            datamsg(400,'新密码不能与支付密码相同');
        }
        if($userInfo['password'] == $data['password']){
            datamsg(400,'新密码不能与旧密码相同');
        }
        $updatePwd = $userModel->save(['password'=>$data['password']],['phone'=>$data['phone']]);
        if($updatePwd){
            datamsg(200,'密码重置成功');
        }else{
            datamsg(400,'密码重置失败');
        }
    }

    //修改支付密码
    public function editpaypwd(){
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){ // 400返回错误描述
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{ // 成功则返回$userId
		    $userId = $tokenRes['user_id'];
	    }

        if(!input('post.old_pwd')){
	        datamsg(400,'旧支付密码不能为空',array('status'=>400));
        }

        if(!input('post.paypwd')){
            datamsg(400,'新支付密码不能为空',array('status'=>400));
        }

        if(!input('post.confirm_pwd')){
            datamsg(400,'确认密码不能为空',array('status'=>400));
        }

        $old_pwd = input('post.old_pwd');
        $paypwd = input('post.paypwd');
        $confirm_pwd = input('post.confirm_pwd');

        $member_paypwd = Db::name('member')->where('id',$userId)->value('paypwd');
        if(!$member_paypwd){
	        datamsg(400,'请先设置支付密码，修改失败',array('status'=>400));
        }else{
            if($member_paypwd != pwdEncrypt($old_pwd)){
	            datamsg(400,'旧支付密码错误',array('status'=>400));
            }
        }

        if(!preg_match("/^\\d{6}$/", $paypwd)){
	        datamsg(400,'支付密码只能为6位数字组成',array('status'=>400));
        }

        if($paypwd == $old_pwd){
	        datamsg(400,'新支付密码不能和旧支付密码相同',array('status'=>400));
        }

        if($confirm_pwd != $paypwd){
	        datamsg(400,'确认密码不正确',array('status'=>400));
        }

        $count = Db::name('member')->update(array('paypwd'=>pwdEncrypt($paypwd),'id'=>$userId));
        if($count > 0){
	        datamsg(200,'修改支付密码成功',array('status'=>200));
        }else{
	        datamsg(400,'修改支付密码失败',array('status'=>400));
        }
    }

    //获取用户手机号
    public function getUserPhone(){
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){ // 400返回错误描述
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{ // 成功则返回$userId
		    $userId = $tokenRes['user_id'];
	    }
	    $userModel = new MemberModel();
	    $phone = $userModel->where('id',$userId)->value('phone');
	    if($phone){
		    datamsg(200,'获取用户手机号成功',array('phone'=>$phone));
	    }else{
		    datamsg(400,'获取用户手机号失败');
	    }
    }

    //判断用户支付密码设置与否
    public function pdpaypwd(){
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){ // 400返回错误描述
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{ // 成功则返回$userId
		    $userId = $tokenRes['user_id'];
	    }
        $paypwd = Db::name('member')->where('id',$userId)->value('paypwd');
        if($paypwd){
            $zhifupwd = 1;
        }else{
            $zhifupwd = 0;
        }
	    datamsg(200,'获取用户手机号成功',array('zhifupwd'=>$zhifupwd));
    }

    //设置支付密码
    public function setPayPwd(){
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){ // 400返回错误描述
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{ // 成功则返回$userId
		    $userId = $tokenRes['user_id'];
	    }

        $data['phone'] = input('post.phone');
        $data['phonecode'] = input('post.code');
        $data['paypwd'] = input('post.pay_pwd');

        $validate = $this->validate($data,'Member.set_pay_pwd');
        if($validate !== true){
            datamsg(400,$validate);
        }

        $userModel = new MemberModel();
        $userInfo = $userModel->where('id',$userId)->field('phone,password,paypwd')->find();
        // 判断手机号是不是属于该用户
        if($data['phone'] != $userInfo['phone']){
            datamsg(400,'手机号错误');
        }

        $smsCodeModel = new SmsCodeModel();
        $checkSmsCode = $smsCodeModel->checkSmsCode($data['phonecode'],$data['phone'],5);
        if($checkSmsCode['status'] == 400){
            datamsg(400,$checkSmsCode['mess']);
        }

        if($userInfo['paypwd'] == pwdEncrypt($data['paypwd'])){
            datamsg(400,'新密码不能与旧密码相同');
        }
        if($userInfo['password'] == pwdEncrypt($data['paypwd'])){
            datamsg(400,'支付密码不能与登录密码相同');
        }
        $updatePwd = $userModel->save(['paypwd'=>pwdEncrypt($data['paypwd'])],['phone'=>$data['phone']]);
        if($updatePwd){
            datamsg(200,'支付密码设置成功');
        }else{
            datamsg(400,'支付密码设置失败');
        }

    }

    //设置支付密码
    public function editPhone(){
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){ // 400返回错误描述
            datamsg(400,$tokenRes['mess'],$tokenRes['data']);
        }else{ // 成功则返回$userId
            $userId = $tokenRes['user_id'];
        }

        $data['phone'] = input('post.phone');
        $data['phonecode'] = input('post.code');

        $validate = $this->validate($data,'Member.edit_phone');
        if($validate !== true){
            datamsg(400,$validate);
        }

        $smsCodeModel = new SmsCodeModel();
        $checkSmsCode = $smsCodeModel->checkSmsCode($data['phonecode'],$data['phone'],6);
        if($checkSmsCode['status'] == 400){
            datamsg(400,$checkSmsCode['mess']);
        }

        $userModel = new MemberModel();
        $userInfo = $userModel::get($userId);
        if($userInfo['phone'] == $data['phone']){
            datamsg(400,'新手机号不能与旧手机号相同');
        }
        $updatePhone = $userModel->save(['phone'=>$data['phone']],['id'=>$userId]);
        if($updatePhone){
            datamsg(200,'手机号修改成功');
        }else{
            datamsg(400,'手机号修改失败');
        }

    }

    /**
     * 我的积分明细
     * @param
     * @return object
     * @author:Damow
     */
    public function getIntegralList(){
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){ // 400返回错误描述
            datamsg(400,$tokenRes['mess'],$tokenRes['data']);
       }else{ // 成功则返回$userId
            $userId = $tokenRes['user_id'];
        }
        $page=input('post.page');
        if($page && preg_match("/^\\+?[1-9][0-9]*$/", $page)){
            $perpage = 20;
            $offset = ($page-1)*$perpage;
            $list   = db('member_integral')->where(['user_id'=>$userId])->limit($offset,$perpage)->select();
            count($list)<1 && datamsg(200,'暂无更多数据','arr');

            foreach ($list as $k=>$v){
                $list[$k]['addtime']   = date('Y-m-d H:i:s',$v['addtime']);
                $list[$k]['log'] = $this->getIntegralTitle($v['type']);
                $list[$k]['class'] = $v['class'] == 0 ? lang('奖励+') : lang('消费-');
            }
        }else{
            datamsg(400,'缺少页面参数',array('status'=>400));
        }

        datamsg(200,'成功',$list);
    }

    //获取积分数量
    public function getUserIntegral(){
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){ // 400返回错误描述
            datamsg(400,$tokenRes['mess']);
        }else{ // 成功则返回$userId
            $userId = $tokenRes['user_id'];
        }
        $memberModel = new MemberModel();
        $integralNum = $memberModel->getUserIntegral($userId);
        datamsg(200,'获取成功',array('integral'=>$integralNum));
    }

    public function getWechatMiniProgramQrcode(){
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){ // 400返回错误描述
            return json($tokenRes);
        }else{ // 成功则返回$userId
            $userId = $tokenRes['user_id'];
        }


        $weChatApp = Factory::miniProgram($this->wechatConfig);

        $response = $weChatApp->app_code->getUnlimit('scene1', [
            'page'  => 'pages/tabBar/my',
            'width' => 600,
        ]);
// $response 成功时为 EasyWeChat\Kernel\Http\StreamResponse 实例，失败为数组或你指定的 API 返回类型

// 保存小程序码到文件
        if ($response instanceof \EasyWeChat\Kernel\Http\StreamResponse) {
            $filename = $response->saveAs('/public/qr/', $userId.'.png');
        }

        datamsg(200, '获取成功', 'https://uqu.ltd/'.$filename);

//        $res = $weChatApp->auth->session($code);
//        if(!empty($res['openid'])){
//            datamsg(200, '获取成功', $res);
//        }else{
//            datamsg(400,'获取失败');
//        }


    }

    public function getUserInfoByPhone(){
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess'],$tokenRes['data']);
        }
        $phone = input('post.phone');
        if(empty($phone)){
            datamsg(400,'缺少手机号参数');
        }
        $userModel = new MemberModel();
        $userInfo = $userModel->getUserInfoByPhone($phone);
        if($userInfo){
            $userInfo['headimgurl'] = url_format($userInfo['headimgurl'],$this->webconfig['weburl']);
            datamsg(200, '获取用户信息成功', $userInfo);
        }else{
            datamsg(400,'未查到该用户信息');
        }
    }

    public function hasPayPwd(){
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){
            return json($tokenRes);
        }else{ // 成功则返回$userId
            $userId = $tokenRes['user_id'];
        }
        $userModel = new MemberModel();
        $result = $userModel->hasPayPwd($userId);
        datamsg($result['status'],$result['mess'],$result['data']);
    }

    //注销账号
    public function cancelAccount(){
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){ // 400返回错误描述
            datamsg(400,$tokenRes['mess']);
        }else{ // 成功则返回$userId
            $userId = $tokenRes['user_id'];
        }
        $memberModel = new MemberModel();
        $member = $memberModel->where('id',$userId)->find();
        if(empty($member)){
            datamsg('400','参数错误');
        }

        // 启动事务
        Db::startTrans();
        try{
            $memberModel->where('id',$userId)->update(['is_cancel'=>0]);
            // 提交事务
            Db::commit();
            datamsg('200','注销成功');
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            datamsg('400','注销失败');
        }
    }

}


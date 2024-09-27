<?php
namespace app\api\controller;
use app\api\model\Common as CommonModel;
use EasyWeChat\Factory;
use think\Cache;
use think\Db;
use app\common\Lookup;
use app\common\model\DistributionConfig;
use app\common\model\DistributionUser;
use app\api\model\Member as MemberModel;
use app\common\model\SmsCode as SmsCodeModel;

class Login extends Common{
    //用户账号密码登录
    public function login(){
        $tokenRes = $this->checkToken(0);
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess']);
        }
        $data = input('post.');
        $newData['phone'] = $data['phone'];
        $newData['email'] =  $data['email'];
        $newData['password'] =  $data['password'];
        $newData['devicetoken'] =  $data['devicetoken'];

        if($data['type']== 0){
            $scene = 'Member.pwd_login';
        }else{
            $scene = 'Member.email_pwd_login';
        }
        $userModel = new MemberModel();
        $result = $userModel->doLogin($data,$scene);

        if($result['status'] == 200){
            datamsg(200,$result['mess'],$result['data']);
        }else{
            datamsg(400,$result['mess']);
        }
    }

    //短信验证码登录
    public function smsLogin(){
        $res = $this->checkToken(0);
        if($res['status'] == 400){
            datamsg(400,$res['mess']);
        }

        $needSmsCode = input('post.need_sms_code',1);
        $oauth = input('post.oauth');
        $smsCode = input('post.sms_code');
        $phone = input('post.phone');
        $openid = input('post.openid');
        $userName = input('post.nick_name');
        $headImgUrl = input('post.iconurl');
        $sex = input('post.uniongender');
        $unionid = input('post.unionid');
        $devicetoken = input('post.devicetoken','');
        $registerClient = input('post.register_client');
        if($needSmsCode == 1){
            $smsCodeModel = new SmsCodeModel();
            $checkSmsCode = $smsCodeModel->checkSmsCode($smsCode,$phone,2);
            if($checkSmsCode['status'] == 400){
                datamsg(400,$checkSmsCode['mess']);
            }
        }
        $userModel = new MemberModel();
        $userInfo = $userModel->getUserInfoByPhone($phone);
        if(empty($userInfo)){
            // 不存在该手机号，创新账号
            if(isset($oauth) && $oauth == 'weixin_app'){
                $insertData = array(
                    'phone' => $phone,
                    'app_openid' => $openid,
                    'user_name' => $userName,
                    'headimgurl' => $headImgUrl,
                    'sex' => $sex,
                    'unionid' => $unionid,
                    'devicetoken' => $devicetoken,
                    'register_type'=>3,
                    'register_client' => $registerClient
                );
                $scene = 'Member.weixin_app_register';
            }elseif(isset($oauth) && $oauth == 'weixin_mp'){
                $insertData = array(
                    'phone' => $phone,
                    'openid' => $openid,
                    'user_name' => $userName,
                    'headimgurl' => $headImgUrl,
                    'sex' => intval($sex),
                    'unionid' => $unionid,
                    'register_type'=>3,
                    'register_client' => 'mp_wechat'
                );
                $scene = 'Member.weixin_mp_register';
            }else{
                $insertData = array(
                    'phone' => $phone,
                    'user_name' =>get_random_string(10),
                    'devicetoken' => $devicetoken,
                    'register_client' => $registerClient,
                    'register_type'=>2,
                );
                $scene = 'Member.sms_login';
            }

            $register = $userModel->doRegister($insertData,$scene);
            if($register['status'] == 400){
                datamsg(400,$register['mess']);
            }
        }else{
            // 手机号存在，绑定手机号
            if(isset($oauth) && $oauth == 'weixin_app'){
                $bindPhone = $userModel->save(['app_openid'=>$openid],['phone'=>$phone]);
                if(!$bindPhone){
                    datamsg(400,'绑定手机号失败');
                }
            }elseif(isset($oauth) && $oauth == 'weixin_mp'){
                $bindPhone = $userModel->save(['openid'=>$openid],['phone'=>$phone]);
                if(!$bindPhone){
                    datamsg(400,'绑定手机号失败');
                }
            }
        }

        // 登录
        if(isset($oauth) && $oauth == 'weixin_app'){
            $data['app_openid'] = $openid;
            $scene = 'Member.weixin_app_login';
        }elseif(isset($oauth) && $oauth == 'weixin_mp'){
            $data['openid'] = $openid;
            $scene = 'Member.weixin_mp_login';
        }else{
            $data['phone'] = $phone;
            $scene = 'Member.sms_login';
        }
        $data['devicetoken'] = $devicetoken;
        $result = $userModel->doLogin($data,$scene);

        if($result['status'] == 200){
            datamsg(200,$result['mess'],$result['data']);
        }else{
            datamsg(400,$result['mess']);
        }



    }

    //是否开启第三方登陆
    public function openThirdLogin(){
        $tokenRes = $this->checkToken(0);
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess']);
        }

        $open = get_config_value('thirdlogin');
        $isOpen = $open == 1 ? true : false;
        datamsg(200,'获取第三方登录信息',array('open'=>$isOpen));
    }

    //处理第三方登录
    public function thirdLogin(){
        // 验证api_token
        $res = $this->checkToken(0);
        if($res['status'] == 400){
            datamsg(400,$res['mess']);
        }

        $data['oauth'] = input('post.oauth');
        $data['openid'] = $data['app_openid'] = input('post.openid');
        $data['user_name'] = input('post.nick_name');
        $data['unionid'] = input('post.unionid');
        $data['sex'] = input('post.uniongender');
        $data['headimgurl'] = input('post.iconurl');
        $data['inviter'] = input('post.inviter',0);
        $data['devicetoken'] = input('post.devicetoken');

        if(empty($data['oauth'])){
            datamsg(400,'缺少oauth类型参数');
        }
        switch ($data['oauth']){
            case 'weixin_mp':
                $scene = 'Member.weixin_mp_login';
                break;
            case 'weixin_app':
                $scene = 'Member.weixin_app_login';
                break;
            default:
                $scene = 'Member.weixin_mp_login';
        }
        $userModel = new MemberModel();
        $login = $userModel->doLogin($data,$scene);
        datamsg($login['status'],$login['mess'],$login['data']);

    }


//    public function getAccessToken(){
//        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wx3a697e9630da7dbd&secret=eb446b0ae21c893dc2cc0e474447fbcf";
//        $token = https_request($url);
//        return $token['access_token'];
//    }

//    public function getWxUserInfo(){
//        $tokenUrl = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wx3a697e9630da7dbd&secret=eb446b0ae21c893dc2cc0e474447fbcf";
//        // $tokenUrl = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wxdc0a2e3f8fa61d0f&secret=623311f72bb5f062400409ac56a12118";
//        $token = https_request($tokenUrl);
//        $token = json_decode($token,true);
//        // dump($token);die;
//        $userInfoUrl = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$token['access_token']."&openid=oQeeE52lAwlHniHJ437sOoTf3V-o&lang=zh_CN";
//        $userInfo = https_request($userInfoUrl);
//        return $userInfo;
//    }

    public function getWechatMiniProgramOpenid() {
        $res = $this->checkToken(0);
        if ($res['status'] == 400) {
            return json($res);
        }
        $code = input('post.code');
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid=" . $this->wechatConfig['app_id'] . "&secret=" . $this->wechatConfig['secret'] . "&js_code=" . $code . "&grant_type=authorization_code";
        $result_json = https_request($url);
        $result = json_decode($result_json, true);
        if (empty($result['openid']) || empty($result['session_key'])) {
            datamsg(400,'微信授权失败', $result);
        }
        $data = array('openid' => $result['openid'], 'session_key' => $result['session_key'], 'unionid' => $result['unionid']);
        datamsg(200, '微信授权成功', $data);
    }


    /***
     * 增加注册用户手机信息
     */
    public function addMemberMobile(){
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess'],$tokenRes['data']);
        }else{
            $userId = $tokenRes['user_id'];
        }
        $data = [];
        $data['user_id'] = $userId;
        $testdata = Db::name('member_extends')->where('user_id',$data['user_id'])->find();
        if(empty($testdata)){
            $data['brand'] = input('post.brand');
            $data['model'] = input('post.model');
            $data['version'] = input('post.version');
            $data['system'] = input('post.system');
            $data['platform'] = input('post.platform');
            $data['created'] = date('Y-m-d H:i:s',time());
            Db::name('member_extends')->insert($data);
        }
        $value = array('status'=>200,'mess'=>'操作成功','data'=>[]);

        return json($value);
    }

    //获取微信登录绑定的手机号码
    public function getUserPhone(){
        $res = $this->checkToken(0);
        if ($res['status'] == 400) {
            return json($res);
        }
        $weChatApp = Factory::miniProgram($this->wechatConfig);
        $sessionKey = input('post.sessionKey');
        $encryptedData = input('post.encryptedData');
        $iv = input('post.iv');
        $data = $weChatApp->encryptor->decryptData($sessionKey, $iv, $encryptedData);
        if (empty($data['phoneNumber'])) {
            datamsg(400,'获取手机号失败');
        }else{
            datamsg(200,'获取手机号成功',array('phone'=>$data['phoneNumber']));
        }
    }

}
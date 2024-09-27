<?php
namespace app\api\controller;

use app\api\controller\Common;
use app\api\model\Common as CommonModel;
use think\Cache;
use think\Db;
use app\common\model\DistributionUser as DistributionUserModel;
use app\common\Lookup;
use app\common\model\DistributionConfig as DistributionConfigModel;
use app\common\model\SmsCode as SmsCodeModel;
use app\common\model\EmailCode as EmailCodeModel;
use app\api\model\Member as MemberModel;

class Register extends Common {

    //用户注册
    public function register() {
	    $tokenRes = $this->checkToken(0);
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }
        $webconfig = $this->webconfig;
        $data = input('post.');
        if($data['type'] == 0){
            $registerType = 1;
            if($webconfig['is_phone'] == 1){
                $smsCodeModel = new SmsCodeModel();
                $checkSmsCode = $smsCodeModel->checkSmsCode($data['phonecode'],$data['phone'],1);
                if ($checkSmsCode['status'] == 400) {
                    datamsg(400,$checkSmsCode['mess']);
                }
            }

        }else{
            $registerType = 5;
            if($webconfig['is_email'] == 1){
                if(empty($data['phonecode'])){
                    datamsg(400,"验证码不能为空");
                }
                $emailCodeModel = new EmailCodeModel();
                $checkSmsCode = $emailCodeModel->checkSmsCode($data['phonecode'],$data['email'],10);
                if ($checkSmsCode['status'] == 400) {
                    datamsg(400,$checkSmsCode['mess']);
                }
            }

        }

        $insertData = array(
            'phone' => $data['phone'],
            'user_name' => get_random_string(10),
            'password' => $data['password'],
            'qrcodeurl' => '',
            'register_type'=>$registerType,
            'register_client'=>$data['register_client'],
            'regtime' => time(),
            'email'  => $data['email'],
            'type'  => $data['type']
        );

        $userModel = new MemberModel();
        $result = $userModel->doRegister($insertData);
        datamsg($result['status'],$result['mess']);

    }

}

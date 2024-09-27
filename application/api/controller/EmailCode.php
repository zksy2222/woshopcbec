<?php

namespace app\api\controller;

use think\Controller;
use think\Request;
use app\api\controller\Common;
use app\common\model\EmailCode as EmailCodeModel;
use app\api\model\Member as MemberModel;

class EmailCode extends Common
{
    /**
     * 发送验证码
     *
     * @return \think\Response
     */
    public function send()
    {
        $tokenRes = $this->checkToken(0);
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess']);
        }
        $data['phone'] = input('param.phone');
        $data['type'] = input('param.type');
        $data['email'] = input('param.email');

        $validate = $this->validate($data,'EmailCode.send');
        if($validate !== true){
            datamsg(400,$validate);
        }

        $emailCodeModel = new EmailCodeModel();
        $sendRes = $emailCodeModel->send($data['email'],$data['type']);

        datamsg($sendRes['status'],$sendRes['mess']);

    }

    public function checkSmsCode(){
        $tokenRes = $this->checkToken(0);
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess']);
        }
        $smsCode = input('post.sms_code');
        $phone = input('post.phone');
        $smsCodeModel = new EmailCodeModel();
        $result = $smsCodeModel->checkSmsCode($smsCode,$phone,2);
        datamsg($result['status'],$result['mess']);
    }



}

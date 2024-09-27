<?php
namespace app\common\model;
use think\Model;
use app\common\model\Config;
use app\common\validate\EmailCode as EmailCodeValidate;
use app\api\model\Member as MemberModel;

class EmailCode extends Model
{
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;
    protected static $instance = null;

    public static function instance($options = []){
        if (is_null(self::$instance)) {
            self::$instance = new static($options);
        }
        return self::$instance;
    }

    protected function getCreateTimeAttr(){
        return $this->getData('create_time');
    }

    /**
     * @description 根据邮箱获取最新验证码
     * @param $email
     * @param $type 验证码类型：1:注册,2:短信快捷登录,3:找回密码,修改密码,4:绑定子账号,5:设置、修改支付密码,6:修改手机号,7:商家入驻申请,8:商家入驻订单,9:商品下单
     * @return object
     */
    public function getCodeByEmail($email,$type){
        $info = $this->where('email',$email)->where('type',$type)->order('id DESC')->find();
        return $info;
    }

    public function getTodayCountByEmail($email){
        $count = $this->where('email',$email)->whereTime('create_time','d')->count();
        return $count;
    }

    public function checkSmsCode($userSmsCode,$email,$type){
        $data['sms_code']= $userSmsCode;
        $data['email'] = $email;
        $data['type'] = $type;
        $validate = new EmailCodeValidate;
        if (!$validate->scene('check')->check($data)) {
            return array('status'=>400,'mess'=>$validate->getError());
        }
        $systemSmsCode = $this->where('email',$email)->where('type',$type)->order('id DESC')->find();
        if(!$systemSmsCode || $userSmsCode != $systemSmsCode['sms_code']){
            return array('status'=>400,'mess'=>'验证码错误');
        }
        $configModel = new Config();
        $validTime = $configModel->getConfigByName('mess_valid_time');
        if(time()-$systemSmsCode['create_time'] > $validTime*60){
            return array('status'=>400,'mess'=>'验证码已过期');
        }else{
            return array('status'=>200,'mess'=>'验证码有效');
        }
    }

    /**
     * @description 发送验证码
     * @param $email
     * @param $type 验证码类型：10:注册
     * @param $param 变量参数，多个参数使用英文逗号隔开（如：param=“a,b,c”）
     * @return object
     */
    public function send($email,$type,$param=''){

        // 发送频率验证
        // step1 验证今日发送最大次数
        $todayCount = $this->getTodayCountByEmail($email);
        $dayMaxCount = get_config_value('maxcodenum');
        if($todayCount >= $dayMaxCount){
            return array('status'=>400,'mess'=>'发送失败，今日发送次数已达到最大限制');
        }

        // step2 验证发送间隔时间
        $lastSmsCode = $this->getCodeByEmail($email,$type);
        $interval = get_config_value('messtime');
        if(isset($lastSmsCode) && (time() - $lastSmsCode['create_time']) < $interval){
            return array('status'=>400,'mess'=>lang('发送失败，').$interval.lang('s内只能发送一次'));
        }

        $validMinutes = intval(get_config_value('mess_valid_time'));
        $data['sms_code'] = create_sms_code();
        $data['ip'] = request()->ip();
        $data['email'] = $email;
        $data['type'] = $type;

        switch ($type){
            case 10: //注册
                $userModel = new MemberModel();
                $getCodeByEmail = $userModel::getByEmail($email);
                if($getCodeByEmail){
                    return array('status'=>400,'mess'=>'该邮箱已注册');
                }
                break;
        }


        $sendRes = send_email($email, $data['sms_code']);
        if($sendRes){
            $res = self::create($data);
            if($res){
                return array('status'=>200,'mess'=>'验证码发送成功');
            }else{
                return array('status'=>400,'mess'=>'验证码发送失败');
            }
        }else{
            return array('status'=>400,'mess'=>'验证码发送失败');
        }


    }
}

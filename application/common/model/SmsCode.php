<?php
namespace app\common\model;
use think\Model;
use app\common\model\Config;
use app\common\validate\SmsCode as SmsCodeValidate;
use app\api\model\Member as MemberModel;

class SmsCode extends Model
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
     * @description 根据手机号获取最新验证码
     * @param $phone
     * @param $type 验证码类型：1:注册,2:短信快捷登录,3:找回密码,修改密码,4:绑定子账号,5:设置、修改支付密码,6:修改手机号,7:商家入驻申请,8:商家入驻订单,9:商品下单
     * @return object
     */
    public function getSmsCodeByPhone($phone,$type){
        $info = $this->where('phone',$phone)->where('type',$type)->order('id DESC')->find();
        return $info;
    }

    public function getTodayCountByPhone($phone){
        $count = $this->where('phone',$phone)->whereTime('create_time','d')->count();
        return $count;
    }

    public function checkSmsCode($userSmsCode,$phone,$type){
        $data['sms_code']= $userSmsCode;
        $data['phone'] = $phone;
        $data['type'] = $type;
        $validate = new SmsCodeValidate;
        if (!$validate->scene('check')->check($data)) {
            return array('status'=>400,'mess'=>$validate->getError());
        }
        $systemSmsCode = $this->where('phone',$phone)->where('type',$type)->order('id DESC')->find();
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
     * @param $phone
     * @param $type 验证码类型：1:注册,2:短信快捷登录,3:找回密码,修改密码,4:绑定子账号,5:设置、修改支付密码,6:修改手机号,7:商家入驻申请,8:商家入驻订单,9:商品下单
     * @param $param 变量参数，多个参数使用英文逗号隔开（如：param=“a,b,c”）
     * @return object
     */
    public function send($phone,$type,$countryCode='',$param=''){


        //7: 商家入驻申请，8：商家入驻订单，9：商品下单 不须验证发送次数和频率
        if(!in_array($type,[7,8,9])){

            // 发送频率验证
            // step1 验证今日发送最大次数
            $todayCount = $this->getTodayCountByPhone($phone);
            $dayMaxCount = get_config_value('maxcodenum');
            if($todayCount >= $dayMaxCount){
                return array('status'=>400,'mess'=>'发送失败，今日发送次数已达到最大限制');
            }

            // step2 验证发送间隔时间
            $lastSmsCode = $this->getSmsCodeByPhone($phone,$type);
            $interval = get_config_value('messtime');
            if(isset($lastSmsCode) && (time() - $lastSmsCode['create_time']) < $interval){
                return array('status'=>400,'mess'=>lang('发送失败，').$interval.lang('s内只能发送一次'));
            }
        }

        $validMinutes = intval(get_config_value('mess_valid_time'));
        $data['sms_code'] = create_sms_code();
        $data['ip'] = request()->ip();
        $data['phone'] = $phone;
        $data['type'] = $type;

        if(empty($countryCode)){
            $countryCode = '86';
        }

        switch ($type){
            case 1: //注册
                $userModel = new MemberModel();
                $getUserByPhone = $userModel::getByPhone($phone);
                if($getUserByPhone){
                    return array('status'=>400,'mess'=>'该手机号已注册');
                }
                $templateId = get_config_value('message_templateid');
                $param = $data['sms_code'].",".$validMinutes;
                break;
            case 2: // 短信快捷登录
                $templateId = get_config_value('message_templateid');
                $param = $data['sms_code'].",".$validMinutes;
                break;
            case 3: // 找回密码,修改密码
            case 5: // 设置、修改支付密码
                $userModel = new MemberModel();
                $getUserByPhone = $userModel::getByPhone($phone);
                if(!$getUserByPhone){
                    return array('status'=>400,'mess'=>'该手机号未注册');
                }
                $templateId = get_config_value('message_templateid');
                $param = $data['sms_code'].",".$validMinutes;
                break;
            case 4: // 绑定子账号
                $userModel = new MemberModel();
                $getUserByPhone = $userModel->getUserInfoByPhone($phone);
                if(!$getUserByPhone){
                    return array('status'=>400,'mess'=>'该手机号未注册，请先注册账号再进行绑定');
                }
                if($getUserByPhone['pid'] > 0){
                    return array('status'=>400,'mess'=>'绑定失败，该手机号对应的账号已被绑定');
                }
                $templateId = get_config_value('message_templateid');
                $param = $data['sms_code'].",".$validMinutes;
                break;
            case 6: // 修改手机号
                $userModel = new MemberModel();
                $getUserByPhone = $userModel::getByPhone($phone);
                if($getUserByPhone){
                    return array('status'=>400,'mess'=>'该手机号已存在');
                }
                $templateId = get_config_value('message_templateid');
                $param = $data['sms_code'].",".$validMinutes;
                break;
            case 7:
                $templateId = get_config_value('message_apply_shop');
                break;
            case 8:
                $templateId = get_config_value('message_rz_order');
                break;
            case 9:
                $templateId = get_config_value('message_goods_order');
                break;
        }



        $sendPhone = '00'.$countryCode.$phone;

        $sendRes = send_sms($sendPhone, $param , $templateId);

        if($sendRes->msg == "OK"){
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

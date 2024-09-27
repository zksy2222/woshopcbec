<?php
namespace app\api\model;
use app\common\Lookup;
use app\common\model\DistributionConfig;
use app\common\model\DistributionUser;
use think\Cache;
use think\Db;
use think\Model;
use app\common\model\Config;
use app\api\controller\Common as CommonController;

class Member extends Model
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'regtime';
    protected $updateTime = false;

    /**
     * 用户详情
     * @param $key 键值
     * @param $val 值
     * @return object
     * @author:Damow
     */
    public function getUser($key,$val){
        return Member::where([$key=>$val])->field('user_name,phone,headimgurl,integral,pid')->find();
    }

    /**
     * 积分新增，并添加日志
     * @param $num=1 连续签到
     * @return object
     * @author:Damow
     */
    public function addLog($integral,$content,$num=0,$userId){
        $tomouth    = date('Y-m', time());
        $data   = [
            'time'      => time(),
            'user_id'   => $userId,
            'credit'    => $integral,
            'log'       => $content,
        ];
        $where   =[
            'signdate'  => $tomouth,
            'user_id'   => $data['user_id'],
        ];
        $info   = db('sign_user')->where($where)->find();
        if($num==1){
            $data['type']   = 1;
            db('sign_records')->insert($data);
            empty($info)?db('sign_user')->insert(['user_id'=>$userId,'signdate'=>$tomouth,'sum'=>1]):db('sign_user')->where($where)->setInc('sum');
            db('member')->where(['id'=>$data['user_id']])->setInc('integral',$integral);
        }else{
            //新增日志
            db('sign_records')->insert($data);
            //增加签到天数
            empty($info)?db('sign_user')->insert(['user_id'=>$userId,'signdate'=>$tomouth,'orderday'=>1]):db('sign_user')->where($where)->setInc('orderday');
            //增加积分
            db('member')->where(['id'=>$data['user_id']])->setInc('integral',$integral);
        }
    }

    public function getRealName($userId) {
        return $this->where('id', $userId)->value('user_name');
    }

    public function getPayPwd($userId) {
        return $this->where('id', $userId)->value('paypwd');
    }

    public function getUserInfoById($id) {
        return self::get($id);
    }

    public function getUserInfoByPhone($phone) {
        return $this->field('id,user_name,headimgurl,phone,pid')->where('phone', $phone)->find();
    }

    public function getUserInfoByUnionId($unionid){
        return $this->field('id,unionid,openid,user_name,headimgurl,phone,appinfo_code,shop_id')->where('unionid', $unionid)->order('id DESC')->find();
    }

    public function doRegister($insertData,$scene='Member.register'){
        if($insertData['type'] == 1){
            $scene='Member.emailRegister';
        }
        unset($insertData['type']);
        $userValidate = $this->validateData($insertData, $scene);
        if ($userValidate !== true) {
            return array('status'=>400,'mess'=>$this->getError());
        }

        $token = settoken();
        $rxs = Db::name('member_token')->where('token', $token)->find();

        $recode = settoken();
        $recodeInfo = Db::name('member')->where('recode', $recode)->field('id')->find();

        $appinfoCode = isset($insertData['devicetoken']) ? $insertData['devicetoken'] : "";
        unset($insertData['devicetoken']);

        if (!$rxs && !$recodeInfo) {
            // 启动事务
            Db::startTrans();
            try {
                if($scene == 'Member.register' || $scene == 'Member.emailRegister'){
                    $insertData['password'] = pwdEncrypt($insertData['password']);
                }
                $appendData = array(
                    'recode' => $recode,
                    'appinfo_code' => $appinfoCode,
                    'regtime' => time()
                );
                $insertData = array_merge($insertData,$appendData);
                $userId = $this->insertGetId($insertData);

                if ($userId) {
                    Db::name('member_token')->insert(array('token' => $token, 'user_id' => $userId));
                    Db::name('wallet')->insert(array('price' => 0, 'user_id' => $userId));
                    Vendor('phpqrcode.phpqrcode');
                    //生成二维码图片
                    $object = new \QRcode();
                    $imgrq = date('Ymd', time());
                    if (!is_dir("./uploads/memberqrcode/" . $imgrq)) {
                        mkdir("./uploads/memberqrcode/" . $imgrq);
                    }
                    $weburl = Db::name('config')->where('ca_id', 5)->where('ename', 'weburl')->field('value')->find();
                    $url = $weburl['value'] . "/index/mobile/index.html?member_recode=" . $recode;
                    $imgfilepath = "./uploads/memberqrcode/" . $imgrq . "/qrcode_" . $userId . ".jpg";
                    $object->png($url, $imgfilepath, 'L', 10, 2);
                    $imgurlfile = "uploads/memberqrcode/" . $imgrq . "/qrcode_" . $userId . ".jpg";
                    Db::name('member')->update(array('qrcodeurl' => $imgurlfile,'headimgurl'=>url_format(''), 'id' => $userId));

                    //3完善信息（绑定手机）送积分
                    $commonController = new CommonController();
                    $num = $commonController->getIntegralValue(3); //获取积分
                    $commonController->addIntegral($userId, $num, 3);

                    //分销商逻辑
                    //若存在邀请人
                    $inviterId = $insertData['inviter'];
                    if (!empty($inviterId)) {
                        //邀请注册送积分
                        $inviteIntegral = $commonController->getIntegralValue(2); //获取积分
                        $commonController->addIntegral($inviterId, $inviteIntegral, 2);

                        //注册送金额
                        $money = get_config_value(invitation_money);
                        $inviterWallet = Db::name('wallet')->where('user_id', $inviterId)->find();
                        if ($inviterWallet) {
                            Db::name('wallet')->where('user_id', $inviterId)->setInc('price', $money);
                            Db::name('detail')->insert(array('user_id'=>$inviterId,'de_type' => 1, 'sr_type' => 3, 'price' => $money, 'order_type' => 1, 'wat_id' => $inviterWallet['id'], 'time' => time()));
                        }

                        //分销商逻辑
                        $configModel = new DistributionConfig();
                        $config = $configModel->getDistributionConfig();
                        if ($config['is_open'] == Lookup::isOpen) {
                            $distribModel = new DistributionUser();
                            //分销商绑定上下级关系 分享链接注册
                            if ($config['become_child'] == Lookup::becomeChildOne) {
                                $distribModel->bindDistribUser($userId, $inviterId);
                            } else {
                                //首次下单、首次付款，存入上下级关系临时表
                                $distribModel->bindDistribTempUser($userId, $inviterId);
                            }
                        }
                    }
                    // 提交事务
                    Db::commit();
                    return array('status'=>200,'mess'=>'注册成功');
                }else{
                    // 提交事务
                    Db::commit();
                    return array('status'=>400,'mess'=>'注册失败');
                }
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $error = $e->getMessage().$e->getLine();
                return array('status'=>400,'mess'=>'注册失败'.$error);
            }
        } else {
            return array('status'=>400,'mess'=>'系统故障，请重试');
        }
    }

    /*
     * @param $scene string  Member.pwd_login:密码登录，Member.sms_login:短信验证码登录，Member.weixin_app_login:app微信登录，Member.weixin_mp_login:微信小程序登录
     * */
    public function doLogin($data,$scene='Member.pwd_login'){

        $userValidate = $this->validateData($data, $scene);
        if ($userValidate !== true) {
            return array('status'=>400,'mess'=>$this->getError());
        }

        switch ($scene){
            case 'Member.pwd_login':
                $data['password'] = pwdEncrypt($data['password']);
                $where = ['phone'=>$data['phone'],'password'=>$data['password']];
                $getData = self::getByPhone($data['phone']);
                $unregisterDesc = '账号不存在';
                break;
            case 'Member.email_pwd_login':
                $data['password'] = pwdEncrypt($data['password']);
                $where = ['email'=>$data['email'],'password'=>$data['password']];
                $getData = self::getByEmail($data['email']);
                $unregisterDesc = '账号不存在';
                break;
            case 'Member.sms_login':
                $where = ['phone'=>$data['phone']];
                $getData = self::getByPhone($data['phone']);
                $unregisterDesc = '账号不存在';
                break;
            case 'Member.weixin_app_login':
                $where = ['app_openid'=>$data['app_openid']];
                $getData = self::getByAppOpenid($data['app_openid']);
                $unregisterDesc = '首次微信登录，请绑定手机号';
                break;
            case 'Member.weixin_mp_login':
                $where = ['openid'=>$data['openid']];
                $getData = self::getByOpenid($data['openid']);
                $unregisterDesc = '首次微信登录，请绑定手机号';
                break;

        }

        if(!$getData){
            datamsg(400,$unregisterDesc,set_lang(array('info'=>'unregister')));
        }
        $userInfo = $this->where($where)->field('id,appinfo_code,checked,shop_id,pid,is_cancel')->find();

        if($userInfo && $userInfo['checked'] == 1 && $userInfo['is_cancel'] == 1){
            $rxs = Db::name('member_token')->where('user_id',$userInfo['id'])->field('token')->find();
            $userInfo['token'] = $rxs['token'];
            $userInfo['role'] = get_user_role($userInfo['id']);

            if($userInfo['pid'] >0 ){
                $shop =  Db::name('member')->where('id',$userInfo['pid'])->find();
                $userInfo['serviceShopId'] = $shop['shop_id'];
            }
            //登录成功，更改用户的设备token
            if($data['devicetoken'] && $data['devicetoken'] != $userInfo['appinfo_code']){   //如果有新的设备token进来，记录此token值
                $this->update(array('id'=>$userInfo['id'],'appinfo_code'=>$data['devicetoken']));
            }
            $this->update(array('id'=>$userInfo['id'],'last_login_ip'=>getIP()));
            //登录送积分
//            $num = $this->getIntegralValue(1);//获取登录积分
//            $this->addIntegral($userInfo['id'],$num,1);

            unset($userInfo['id']);
            return array('status'=>200,'mess'=>'登录成功','data'=>$userInfo);
        }elseif($userInfo && $userInfo['checked'] != 1) {
            return array('status' => 400, 'mess' => '账号已禁用');
        }elseif($userInfo && $userInfo['is_cancel'] != 1) {
            return array('status' => 400, 'mess' => '账号已注销');
        }else{
            return array('status'=>400,'mess'=>'账号密码错误');
        }
    }

    // 判断用户是否设置了支付密码
    public function hasPayPwd($userId){
        $payPwd = $this->where('id',$userId)->value('paypwd');
        if(empty($payPwd)){
            return array('status'=>400,'mess'=>'请先设置支付密码','data'=>array('tip_show'=>'close'));
        }else{
            return array('status'=>200,'mess'=>'已设置支付密码','data'=>array());
        }
    }

    // 判断用户支付密码是否正确
    public function checkPayPwd($userId,$payPwd){
        $userPayPwd = $this->where('id',$userId)->value('paypwd');
        if(empty($userPayPwd)){
            return array('status'=>400,'mess'=>'请先设置支付密码');
        }
        if($userPayPwd == pwdEncrypt($payPwd)){
            return array('status'=>200,'mess'=>'支付密码正确');
        }else{
            return array('status'=>400,'mess'=>'支付密码错误');
        }
    }

    //获取用户积分数量
    public function getUserIntegral($userId){
       return $integralNum = $this->where('id',$userId)->value('integral');
    }
}
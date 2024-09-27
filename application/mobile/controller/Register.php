<?php

namespace app\mobile\controller;

use think\Db;
use app\common\Lookup;
use app\common\model\DistributionConfig;
use app\api\model\Member;
use app\common\model\SmsCode as SmsCodeModel;
use app\common\model\DistributionUser;

class Register extends Common {

    //用户注册
    public function index() {
        if (request()->isPost()) {
            if (session('user_id')) {
                $value = array('status' => 0, 'mess' => '您已登录，请退出登录后重试');
            }
            $data = input('post.');
            $result = $this->validate($data, 'Member');
            if (true !== $result) {
                datamsg(0,$result);
            }
            if ($data['xieyi'] != 1) {
                datamsg(0,'请同意注册协议，注册失败');
            }

            $smsCodeModel = new SmsCodeModel();
            $checkSmsCode = $smsCodeModel->checkSmsCode($data['phonecode'],$data['phone'],1);
            if($checkSmsCode['status'] == 400){
                datamsg(0,$checkSmsCode['mess']);
            }
            $data['password'] = md5($data['password']);
            $token = settoken();
            $rxs = Db::name('member_token')->where('token', $token)->find();

            $recode = settoken();
            $recodeInfo = Db::name('member')->where('recode', $recode)->field('id')->find();

            $appinfoCode = settoken();
            $members = Db::name('member')->where('appinfo_code', $appinfoCode)->field('id')->find();

            if (!$rxs && !$recodeInfo && !$members) {
                // 启动事务
                Db::startTrans();
                try {
                    $userId = Db::name('member')->insertGetId(array(
                        'phone' => $data['phone'],
                        'user_name' => get_random_string(10),
                        'recode' => $recode,
                        'password' => $data['password'],
                        'appinfo_code' => $appinfoCode,
                        'xieyi' => $data['xieyi'],
                        'inviter' => $data['inviter'],
                        'regtime' => time()
                    ));

                    if ($userId) {
                        Db::name('member_token')->insert(array('token' => $token, 'user_id' => $userId));
                        Db::name('wallet')->insert(array('price' => 0, 'user_id' => $userId));
                    }


                    //邀请注册送积分
                    //若存在邀请人
                    if (!empty($data['inviter'])) {
                        $uid = $data['inviter'];
                        $num = $this->getIntegralValue(2); //获取积分
                        $this->addIntegral($uid, $num, 2);

                        //注册送金额
                        $money = get_config_value(invitation_money);
                        $pt_wallets = Db::name('wallet')->where('user_id', $uid)->find();
                        if ($pt_wallets) {
                            Db::name('wallet')->where('user_id', $uid)->setInc('price', $money);
                            Db::name('detail')->insert(array('user_id'=>$uid,'de_type' => 1, 'sr_type' => 3, 'price' => $money, 'order_type' => 1, 'wat_id' => $pt_wallets['id'], 'time' => time()));
                        }

                        //分销商逻辑
                        $configModel = new DistributionConfig();
                        $config = $configModel->getDistributionConfig();
                        if ($config['is_open'] == Lookup::isOpen) {
                            $distribModel = new DistributionUser();
                            //分销商绑定上下级关系 分享链接注册
                            if ($config['become_child'] == Lookup::becomeChildOne) {
                                $distribModel->bindDistribUser($userId, $uid);
                            } else {
                                //首次下单、首次付款，存入上下级关系临时表
                                $distribModel->bindDistribTempUser($userId, $uid);
                            }
                        }
                    }
                    // 提交事务
                    Db::commit();
//                                    $value = array('status' => 1, 'mess' => '注册成功');
                    datamsg(1,'注册成功');
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
//                                    $value = array('status' => 0, 'mess' => '注册失败');
                    datamsg(0,'注册失败'.$e->getMessage().$e->getTraceAsString());
                }
            } else {
//                                $value = array('status' => 0, 'mess' => '注册失败，请重试');
                datamsg(0,'注册失败,请重试');
            }
        } else {
            if (!session('user_id')) {
                $peizhi = Db::name('config')->where('ename', 'messtime')->field('value')->find();
                $inviter = input('inviter');
                
                //点击了分享邀请，将邀请人存入session，在h5个人中心页面注册了也同样绑定上下级关系
                session('user_pid', $inviter);
                $agreement = Db::name('news')->where('tag', 'agreement')->order('sort DESC')->find();
                $this->assign('agreement',$agreement);
                $this->assign('messtime', $peizhi['value']);
                $this->assign('inviter', $inviter);
                return $this->fetch('register');
            } else {
                $this->redirect('index/index');
            }
        }
    }

    //验证用户手机号唯一性
    public function checkPhone() {
        if (request()->isAjax()) {
            if (input('post.phone')) {
                $username = Db::name('member')->where(array('phone' => input('post.phone')))->find();
                if ($username) {
                    echo 'false';
                } else {
                    echo 'true';
                }
            } else {
                echo 'false';
            }
        }
    }

    public function sendcode() {
        if (!request()->isPost()) {
            datamsg(0,'请求方式错误');
        }
        if (session('user_id')) {
            datamsg(0,'您已登录，请退出登录后重试');
        }
        $phone = input('post.phone');
        if (!$phone || !preg_match("/^1[3456789]{1}\\d{9}$/", $phone)) {
            datamsg(0,'请填写正确的手机号码');
        }
        $userModel = new Member();
        $userInfo = $userModel->getUserInfoByPhone($phone);
        if ($userInfo) {
            datamsg(0,'手机号已存在');
        }


    }

//    获取隐私协议
	public function getArticleByTitle(){
		if(request()->isPost()){
			$title=input('post.title');
			$article=db('news')->where('ar_title',$title)->find();
			if(!empty($article)){
				$value=array('status'=>1,'data'=>$article);
			}else{
				$value=array('status'=>0,'mess'=>'文章不存在！');
			}
			return json($value);
		}
	}

}

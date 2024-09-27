<?php
namespace app\mobile\controller;
use think\Controller;
use think\Db;

class Common extends Controller{
    public $webconfig;
    public $userId = null;
    public $gourl = "index /index";
    
    public function _initialize(){
//        $this->user_id = session('user_id');
        $this->user_id = 22;
        $this->checkUser();
        $this->_getconfig();//获取配置项

    }
    
    public function _getconfig(){
        $_configres = Db::name('config')->where('ca_id','in','1,2,5,8,11,15')->field('ename,value')->select(); //1,2,5,8,11,15 为配置参数的分类
        $configres = array();
        foreach ($_configres as $v){
            $configres[$v['ename']] = $v['value'];
        }
        
        $this->webconfig=$configres;
        // dump($configres);die;
        $this->assign('configres',$configres);
    }
    
    public function checkUser($flag = false) {
        if (!$this->user_id) {
            if (!$flag) {
                return false;
            }
            $this->error('用户信息错误', $this->gourl);
        }
        return true;
    }
    
    public function checkLogin() {
        if (!$this->user_id) {
            return $this->fetch('login/index');
        }
    }
    
    /**
	* @description: 获取单个配置
	* @Author: lxb
	* @param : $id:配置ID
	* @return: json
	*/
	function getConfigInfo($id){
		$res = Db::name('config')->where('id',$id)->field('ename,value,values')->find();
		return $res;
	}

    /**
     * @description: 会员积分规则
     * @Author: lxb
     * @param : $type: 1每日登录,2邀请注册,3完善信息（绑定手机）,4完善信息（上传头像）,5观看直播,6直播发言（次）,7直播分享（次）,8购物消费（%）,9订单评价（次）,10商品分享,11连续签到奖励,12普通签到奖励,13积分兑换,14后台积分操作
     * @return: array
     */
    function getIntegralValue($type){
        $integral = Db::name('integral_task')->where('id',$type)->value('integral');
        return intval($integral);
    }
	
	/**
	* @description: 会员积分
	* @Author: lxb
	* @param : $userId:用户id;$num:积分;$type:1每日登录,2邀请注册,3完善信息（绑定手机）,4完善信息（上传头像）,5观看直播,6直播发言（次）,7直播分享（次）,8购物消费（%）,9订单评价（次）,10商品分享,11连续签到奖励,12普通签到奖励,13积分兑换,14后台积分操作
	* @return: json
	*/
	function addIntegral($userId,$num,$type,$order_id=0){
		
		$data['user_id'] = $userId;
		$data['integral'] = $num;
		$data['type'] = $type;
		$data['order_id'] = $order_id;
		$data['addtime'] = time();
		Db::name('member_integral')->insert($data);
		Db::name('member')->where(['id'=>$userId])->setInc('integral',$num);
		
		return true;
	}

    
    
}
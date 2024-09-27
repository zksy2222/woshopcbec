<?php

namespace app\api\controller;
use app\api\controller\Common;
use app\api\model\DistributionUser as DistributionUserModel;
use app\api\model\DistributionCommissonDetail as DistributionCommissonDetailModel ;
use app\common\model\DistributionConfig;
use app\api\model\Member;
use app\common\Lookup;
use app\api\model\BankCard;
use app\api\model\DistributionWithdraw;
use app\api\model\Order;
use app\api\model\DistributionGrade;
use think\Db;

class DistributionUser extends Common {
    
    public $distribModel = null;
    public $gradeModel = null;
    
    //进入申请分销商界面
    public function getDistributionStatus() {
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{
		    $userId = $tokenRes['user_id'];
	    }
        $distribModel = new DistributionUserModel();
        $distrib = $distribModel->getDistributionUserInfo($userId);
        if (!$distrib) {
            datamsg(400, '您还未申请分销商，暂无状态');
        }
        $data = array('check_status' => $distrib['status']);
        datamsg(200, 'success', $data);
    }
    
    //申请分销商,提交信息
    public function submitDistribution() {
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{
		    $userId = $tokenRes['user_id'];
	    }
        $isOpen = db('distribution_config')->where('id',1)->value('is_open');
        if($isOpen != 1){
            datamsg(400, '分销商申请已关闭，请联系管理员');
        }
        $data = input('post.');
        $result = $this->validate($data, 'DistributionUser');
        if(true !== $result){
            datamsg(400, $result);
        }
        $distribModel = new DistributionUserModel();
        $configModel = new DistributionConfig();
        $config = $configModel->getDistributionConfig();
        $getDistributionUserInfo = $distribModel->getDistributionUserInfo($userId);
        $isDistributionUser = $distribModel->isDistributionUser($userId);
	    if($isDistributionUser){
		    datamsg(400, '您已申请分销商，无需再申请了',array('status'=>400));
	    }
        $applyUser['real_name'] = $data['real_name'];
        $applyUser['phone'] = $data['phone'];
        $applyUser['wxnum'] = $data['wxnum'];
        $applyUser['user_id'] = $userId;
        if ($config['become_distrib'] == Lookup::becomeDistribZero) {
            $applyUser['status'] = 2;
            $applyUser['is_distribution'] = 1;
        }elseif($config['become_distrib'] == Lookup::becomeDistribOne){
            $applyUser['status'] = 1;
            $applyUser['is_distribution'] = 0;
        }
        if($getDistributionUserInfo){
            $res=$distribModel->update($applyUser,['id'=>$getDistributionUserInfo['id']]);
        }else{
            $res=$distribModel->save($applyUser);
        }
        if($res){
            datamsg(200, '申请成功', array('status'=>200));
        }else{
            datamsg(400, '申请失败',array('status'=>400));
        }
    }
    
    //分销中心
    public function distribCenter() {
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{
		    $userId = $tokenRes['user_id'];
	    }
        
        //分销商等级升级，满足条件自动升级
        $common = new DistributionCommon();
        $common->upGradeDistribution($userId);
        
        $distribbModel = new DistributionUserModel();
        $user = $distribbModel->getDistribCenterData($userId);
        if (!$user) {
            datamsg(400, '您还不是分销商');
        }
        if ($user['user_pid']) {
            $memberModel = new Member();
            $user['user_parent'] = $memberModel->getRealName($user['user_pid']);
        } else {
            $user['user_parent'] = lang('无');
        }
        //可提现佣金 withdrawal
        //成功提现佣金 withdrawal_success
        $this->distribModel = new DistributionUserModel();
        $this->gradeModel = new DistributionGrade();
        $configModel = new DistributionConfig();
        $config = $configModel->getDistributionConfig();

        $userid_arr[] = $userId;
        $userid_arr = $this->distribModel->getTotalUserId($userid_arr, $config['level']);

        $webconfig = $this->webconfig;
        $pageSize = $webconfig['app_goodlst_num'];
        $offset = (1 - 1) * $pageSize;

        $orderModel = new Order();
        $orderList = $orderModel->getOrderList($userid_arr, 1, $offset, $pageSize);
        $user['commisson_num'] = count($orderList);                                     //佣金明细 笔数

        $withdraw = new DistributionWithdraw();
        $user['withdrawal_num'] = $withdraw->getWithdrawCount($userId);        //提现明细 笔数
//        $commissonModel = new DistributionCommissonDetail();
//        $user['commisson_num'] = $commissonModel->getCommissonTotalNum($userId); //佣金明细 笔数
        $configModel = new DistributionConfig();
        $config = $configModel->getDistributionConfig();
        $userid_arr[] = $userId;
        $user['total_user'] = $distribbModel->getTotalUser($userid_arr, $config['level']); //下线总人数
        unset($user['user_pid']);
        $user['headimgurl'] = url_format($user['headimgurl'],$this->webconfig['weburl']);
        $data = array('user' => $user);
        datamsg(200, 'success', set_lang($data));
    }
    
    //级数tab栏
    public function getLevelTab() {
    	$res = $this->checkToken();
        if($res['status'] == 400){
            return json($res);
        }
        $userid_arr[] = $res['user_id'];
        $configModel = new DistributionConfig();
        $config = $configModel->getDistributionConfig();
        $distribUserModel = new DistributionUserModel();
        $diffLevel = $distribUserModel->getDiffLevelUser($userid_arr, $config['level']);
        $levelTab = array();
        for($i = 1; $i <= $config['level']; $i++) {
            if ($i == Lookup::levelOne) {
                $levelTab[$i]['level_str'] = lang('一级');
                $levelTab[$i]['total_user'] = $diffLevel['levelOne'];
            } elseif ($i == Lookup::levelTwo) {
                $levelTab[$i]['level_str'] = lang('二级');
                $levelTab[$i]['total_user'] = $diffLevel['levelTwo'];
            } elseif ($i == Lookup::levelThree) {
                $levelTab[$i]['level_str'] = lang('三级');
                $levelTab[$i]['total_user'] = $diffLevel['levelThr'];
            }
        }
        $data = array('level_tab' => array_values($levelTab));
        datamsg(200, 'success', $data);
    }
    
    //tab栏切换不同级数的用户信息
    public function getDiffLevelUserList() {
        $res = $this->checkToken();
        if($res['status'] == 400){
            return json($res);
        }
        $useridArr[] = $res['user_id'];
        $level = input('post.level');
        if (!in_array($level, [1,2,3])) {
            datamsg(400, '参数错误');
        }
        $distribUserModel = new DistributionUserModel();
        $useridArr = $distribUserModel->getLowerTotalUser($useridArr, $level, 0, true); //当前级数的所有用户id
        $userList = $distribUserModel->getLowerUserInfo($useridArr);
        datamsg(200, 'success', $userList);
    }
    
    //分销佣金详情
    public function getCommissionDetails() {
        $res = $this->checkToken();
        if($res['status'] == 400){
            return json($res);
        }
        $userId = $res['user_id'];
        $distribbModel = new DistributionUserModel();
        $field = "id,commission,withdraw_amount,applied_amount,waitpay_amount,success_amount";
        $distribution = $distribbModel->getDistributionUserInfo($userId, $field);
        if (!$distribution) {
            datamsg(400, '分销商信息错误');
        }
//        $distrib = array(
//            'commission' => $distribution['commission'],                    //累计佣金
//            'withdrawal_amount' => $distribution['withdrawal_amount'],      //可提现佣金
//            'applied_amount' => $distribution['applied_amount'],            //已申请佣金
//            'waitpay_amount' => $distribution['waitpay_amount'],            //待打款佣金
//            'invalid_amount' => 0.00,                                       //无效佣金
//            'success_amount' => $distribution['success_amount'],            //成功提现佣金
//            'waitrece_amount' => 0.00,                                      //待收货佣金
//            'unsettled_amount' => 0.00                                      //未结算佣金
//        );
        $distribution['invalid_amount'] = "0.00";
        $distribution['waitrece_amount'] = "0.00";
        $distribution['unsettled_amount'] = "0.00";
        $data = array(
            'distrib_info' => $distribution,
        );
        datamsg(200, 'success', $data);
    }
    
    //我要提现-获取可提现金额、银行卡信息
    public function withdrawInfo() {
        $res = $this->checkToken();
        if($res['status'] == 400){
            return json($res);
        }
        $userId = $res['user_id'];
        $cardModel = new BankCard();
        $cardInfo = $cardModel->getBankCardInfo($userId);
        if (!$cardInfo) {
            datamsg(400, '请先绑定银行卡');
        }
        $distribbModel = new DistributionUserModel();
        $field = "id,withdraw_amount";
        $distribution = $distribbModel->getDistributionUserInfo($userId, $field);
        if (!$distribution) {
            datamsg(400, '分销商信息错误');
        }
        $webconfig = $this->webconfig;
        $min_amount = $webconfig['commission_withdraw_max_money'];
        $withdraw_count = $webconfig['commission_withdraw_max_number'];
        $tip_str = lang("每月最多提现") . $withdraw_count . lang("次，每次最少提现") . $min_amount . lang("元");
        //是否设置支付密码
        $memberModel = new Member();
        $paypwd = $memberModel->getPayPwd($userId);
        if (!$paypwd) {
            $paypwd_status = Lookup::isClose;
        } else {
            $paypwd_status = Lookup::isOpen;
        }
        $distribution['min_amount'] = $min_amount;
        $distribution['withdraw_count'] = $withdraw_count;
        $distribution['tip_str'] = $tip_str;
        $distribution['paypwd_status'] = $paypwd_status;
        $data = array(
            'card_info' => $cardInfo,
            'withdraw_info' => $distribution
        );
        datamsg(200, 'success', $data);
    }
    
    //申请提现接口
    public function withdrawSubmit() {
        $res = $this->checkToken();
        if ($res['status'] == 400) {
            return json($res);
        }
        $userId = $res['user_id'];
        $memberModel = new Member();
        $paypwd = $memberModel->getPayPwd($userId);
        if (!$paypwd) {
            datamsg(400, '请先设置支付密码');
        }
        $pay_password = input('post.pay_password');
        $amount = input('post.amount');
        if (!$pay_password) {
            datamsg(400, '请填写支付密码');
        }
        if (!preg_match("/^\\d{6}$/", $pay_password)) {
            datamsg(400, '支付密码错误');
        }
        if ($paypwd != pwdEncrypt($pay_password)) {
            datamsg(400, '支付密码错误');
        }
        if (!$amount) {
            datamsg(400, '请输入提现金额');
        }
        if (!preg_match("/(^[1-9]([0-9]+)?(\\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\\.[0-9]([0-9])?$)/", $amount)) {
            datamsg(400, '提现金额格式错误');
        }
        $webconfig = $this->webconfig;
        if ($amount < $webconfig['commission_withdraw_min_money']) {
            datamsg(400, lang('每次最少提现').$webconfig['commission_withdraw_min_money'].lang('元'));
        }
        $distribbModel = new DistributionUserModel();
        $field = "id,withdraw_amount,applied_amount";
        $distribution = $distribbModel->getDistributionUserInfo($userId, $field);
        if ($amount > $distribution['withdraw_amount']) {
            datamsg(400, '可提现金额不足，提现失败');
        }
        $withdraw = new DistributionWithdraw();
        $withdrawCount = $withdraw->getWithdrawMonthCount($userId);
        if ($withdrawCount >= $webconfig['commission_withdraw_max_number']) {
            datamsg(400, lang('每月最多提现').$webconfig['commission_withdraw_max_number'].lang('次'));
        }
        $tx_number = 'TX'.date('YmdHis').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
        $withdraw_info = $withdraw->getWithdrawByNumber($tx_number);
        if ($withdraw_info) {
            datamsg(400, '申请提现失败，请重试');
        }
        $cardModel = new BankCard();
        $cardInfo = $cardModel->getBankCardInfo($userId);
        if (!$cardInfo) {
            datamsg(400, '银行卡信息错误');
        }
        
        Db::startTrans();
        try{
            $data = array(
                'user_id' => $userId,
                'bank_id' => $cardInfo['id'],
                'tx_number' => $tx_number,
                'amount' => $amount,
                'create_time' => date('Y-m-d H:i:s')
            );
            $withdraw->save($data);
            $update = array(
                'withdraw_amount' => $distribution['withdraw_amount'] - $amount,
                'applied_amount' => $distribution['applied_amount'] + $amount,
            );
            $distribbModel->update($update, array('id' => $distribution['id']));
            Db::commit();
            datamsg(200, '提现申请成功，我们将尽快处理');
        } catch (\Exception $e) {
            Db::rollback();
            datamsg(400, '申请提现失败;' . $e->getMessage());
        }
    }
    
    //佣金明细
    public function getCommissionList() {
        $res = $this->checkToken();
        if ($res['status'] == 400) {
            return json($res);
        }
        $page = input('post.page', 1);
        $status = input('post.status', 1);
        if (!preg_match("/^\\+?[1-9][0-9]*$/", $page)) {
            datamsg(400, 'page参数错误');
        }
        if (!in_array($status, [1,2,3,4])) {
            datamsg(400, 'status参数错误');
        }
        $userId = $res['user_id'];
        $this->distribModel = new DistributionUserModel();
        $this->gradeModel = new DistributionGrade();
        //累计佣金
        $field = 'commission,grade_id';
        $distrib = $this->distribModel->getDistributionUserInfo($userId, $field);
        if (!$distrib) {
            datamsg(400, '分销商信息错误');
        }
        $webconfig = $this->webconfig;
        $pageSize = $webconfig['app_goodlst_num'];
        $DistributionCommissonDetailModel = new DistributionCommissonDetailModel();
        $orderList = $DistributionCommissonDetailModel->getAmountLst($userId,$status,$page,$pageSize);
        $data = array('commission' => $distrib['commission'],'order_list' => $orderList);
        
        datamsg(200, 'success', $data);
        
    }
    
    //提现明细
    public function getWithdrawList() {
        $res = $this->checkToken();
        if ($res['status'] == 400) {
            return json($res);
        }
        $page = input('post.page', 1);
        $status = input('post.status', 1);
        if (!preg_match("/^\\+?[1-9][0-9]*$/", $page)) {
            datamsg(400, 'page参数错误');
        }
        if (!in_array($status, [1,2,3,4,5])) {
            datamsg(400, 'status参数错误');
        }
        $userId = $res['user_id'];
        
        $distribbModel = new DistributionUserModel();
        $field = 'withdraw_amount';
        $distrib = $distribbModel->getDistributionUserInfo($userId, $field);
        if (!$distrib) {
            datamsg(400, '分销商信息错误');
        }
        
        $webconfig = $this->webconfig;
        $pageSize = $webconfig['app_goodlst_num'];
        $offset = ($page - 1) * $pageSize;
        
        $withdraw = new DistributionWithdraw();
        $withdraw_list = $withdraw->getWithdrawList($userId, $status, $offset, $pageSize);
        
        $data = array('withdraw_amount' => $distrib['withdraw_amount'], 'withdraw_list' => $withdraw_list);
        
        datamsg(200, 'success', $data);
    }
    
    /**
     * 计算每一单的佣金收益
     * @param type $userId             分销商的下级用户id
     * @param type $userPid            当前分销商id
     * @param type $total_price         每一个订单金额
     * @param type $level               后台配置的分销级数
     * @param type $grade_id            分销商等级
     * @return int                      每一笔订单所得佣金
     */
    private function getEveryOrderCommission($userId, $userPid, $total_price, $level, $grade_id) {
        $every_order = array();
        for($i = 1; $i <= $level; $i++) {
            $userpid = $this->getUserPid($userId, $i);
            if ($userPid == $userpid) {
                $grade = $this->gradeModel->getGradeInfoById($grade_id);
                if (!$grade) {
                    break;
                }
                if ($i == Lookup::levelOne) {
                    $level_rate = $grade['one_level_rate'];
                } elseif ($i == Lookup::levelTwo) {
                    $level_rate = $grade['two_level_rate'];
                } elseif ($i == Lookup::levelThree) {
                    $level_rate = $grade['three_level_rate'];
                }
                $every_order['level'] = $i;
                $every_order['commission'] = round(($total_price * $level_rate)/Lookup::percent, Lookup::roundPrecision);
                break;
            }
        }
        return $every_order;
    }
    
    private function getUserPid($userId, $level, $count = 0) {
        $distribUser = $this->distribModel->getDistributionUserInfo($userId);
        if (!$distribUser) {
            return 0;
        } else {
            if (empty($distribUser['user_pid'])) {
                return 0;
            }
            $userPid = $distribUser['user_pid'];
            $count++;
            if ($level != $count) {
                return $this->getUserPid($userPid, $level, $count);
            }
            return $userPid;
        }
    }
    
    private function updateUserInfo($data, $userId) {
        $user_data = array(
            'real_name' => $data['real_name'],
            'phone' => $data['phone'],
            'wxnum' => $data['wxnum']
        );
        $memberModel = new Member();
        $user_result = $memberModel->update($user_data, array('id' => $userId));
        if (!$user_result) {
            return false;
        }
        return true;
    }
}

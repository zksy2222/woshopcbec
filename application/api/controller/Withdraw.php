<?php
namespace app\api\controller;
use app\api\controller\Common;
use app\api\model\Common as CommonModel;
use app\api\model\DistributionUser as DistributionUserModel;
use think\Db;
use app\api\model\Member as MemberModel;
use app\api\model\BankCard as BankCardModel;
use app\api\model\Wallet as WalletModel;
use app\api\model\Withdraw as WithdrawModel;

class Withdraw extends Common{
    //提现获取钱包及银行卡信息
    public function index(){
	    $tokenRes = $this->checkToken();
	    if ($tokenRes['status'] == 400) {
		    datamsg(400, $tokenRes['mess'], $tokenRes['data']);
	    } else {
		    $userId = $tokenRes['user_id'];
	    }

	    $data['type'] = input('post.type');
	    $validate = $this->validate($data,'Withdraw.get_withdraw_info');
	    if($validate !== true){
	        datamsg(400,$validate);
        }

        $bankCardModel = new BankCardModel();
        $withdrawInfo['bank_card'] = $bankCardModel->getBankCardInfo($userId);
        if(!$withdrawInfo['bank_card']) {
            datamsg(400, '请先绑定银行卡');
        }

        $webconfig   = $this->webconfig;
	    switch ($data['type']){
            case 1:
                $withdrawInfo['type_name'] = lang('余额提现');
                $withdrawInfo['min_money']  = intval($webconfig['balance_withdraw_min_money']);
                $withdrawInfo['max_number']= intval($webconfig['balance_withdraw_max_number']);
                $walltModel = new WalletModel();
                $withdrawInfo['max_money'] = $walltModel->getUserMoney($userId);
                if(!$withdrawInfo['max_money']){
                    datamsg(400, '获取信息失败');
                }
                break;
            case 2:
                $withdrawInfo['type_name'] = lang('佣金提现');
                $withdrawInfo['min_money']  = intval($webconfig['commission_withdraw_min_money']);
                $withdrawInfo['max_number']= intval($webconfig['commission_withdraw_max_number']);
                $distribbModel = new DistributionUserModel();
                $field = "id,withdraw_amount";
                $distribution = $distribbModel->getDistributionUserInfo($userId, $field);
                if (!$distribution) {
                    datamsg(400, '分销商信息错误');
                }
                $withdrawInfo['max_money'] = $distribution->withdraw_amount;
                break;
        }


        $memberModel = new MemberModel();
	    $payPwd = $memberModel->where('id',$userId)->value('paypwd');
	    if(empty($payPwd)){
            $withdrawInfo['paypwd'] = 0;
        }else{
            $withdrawInfo['paypwd'] = 1;
        }

	    datamsg(200, '获取信息成功', array('withdraw_info' => $withdrawInfo));
    }



    //余额提现
    public function doWithdraw(){
	    $tokenRes = $this->checkToken();
	    if ($tokenRes['status'] == 400) {
		    datamsg(400, $tokenRes['mess'], $tokenRes['data']);
	    } else {
		    $userId = $tokenRes['user_id'];
	    }
	    $data = input('post.');

	    $bankCardModel = new BankCardModel();
	    $bankCard = $bankCardModel->getBankCardInfo($userId);
	    if(!$bankCard){
	        datamsg(400,'提现银行卡信息错误');
        }

	    $data['card_number'] = $bankCard['card_number'];
        $data['zs_name'] = $bankCard['name'];
        $data['bank_name'] = $bankCard['bank_name'];
        $data['shengshiqu'] = $bankCard['province'].$bankCard['city'].$bankCard['area'];
        $data['branch_name'] = $bankCard['branch_name'];
	    $data['user_id'] = $userId;
	    $validate = $this->validate($data,'Withdraw.do_withdraw');
	    if($validate !== true){
	        datamsg(400,$validate);
        }

        $memberModel = new MemberModel();
	    $paypwd = $memberModel->where('id',$userId)->value('paypwd');
	    if(empty($paypwd)){
            datamsg(400,'请先设置支付密码');
        }else{
	        if(pwdEncrypt($data['paypwd']) != $paypwd){
                datamsg(400, '支付密码错误');
            }
        }
        $webconfig = $this->webconfig;
        $withdrawModel = new WithdrawModel();
	    if($data['type'] == 1){// 余额提现
            $minMoney = $webconfig['balance_withdraw_min_money'];
            $maxNumber = $webconfig['balance_withdraw_max_number'];


            $withdrawCount = $withdrawModel->getMonthWithdrawCount($userId,1);
            if($withdrawCount >= $maxNumber){
                datamsg(400,'已达到每月最大提现次数，提现失败');
            }

            if($data['price'] < $minMoney){
                datamsg(400,lang('最小提现金额为：').$minMoney);
            }

            $walletModel = new WalletModel();
            $walltMoney = $walletModel->getUserMoney($userId);
            if($data['price'] > $walltMoney){
                datamsg(400,lang('余额不足，最大可提现余额为：').$minMoney);
            }

        }

        if($data['type'] == 2){// 佣金提现

        }


        $data['tx_number'] = 'TX'.$userId.date('YmdHis').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
        $checkTxNumber = $withdrawModel->getByTxNumber($data['tx_number']);
        if($checkTxNumber){
	        datamsg(400, '申请提现失败，请重试');
        }

        // 启动事务
        Db::startTrans();
        try{
            $withdrawModel->allowField(true)->save($data);
            $walletModel->where('user_id',$userId)->setDec('price', $data['price']);
            // 提交事务
            Db::commit();
	        datamsg(200, '提现申请成功，我们将尽快处理');
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
	        datamsg(400, lang('申请提现失败').$e->getMessage());
        }
    }
    
    //获取提现列表
    public function getWithdrawList(){
	    $tokenRes = $this->checkToken();
	    if ($tokenRes['status'] == 400) {
		    datamsg(400, $tokenRes['mess'], $tokenRes['data']);
	    } else {
		    $userId = $tokenRes['user_id'];
	    }
	    if(!input('post.page') && !preg_match("/^\\+?[1-9][0-9]*$/", input('post.page'))) {
	        datamsg(400, '缺少页数', array('status'=>400));
	    }
        $perpage = 20;
        $offset = (input('post.page')-1)*$perpage;
        $txmxres = Db::name('withdraw')->where('user_id',$userId)->order('id desc')->field('id,type,price,create_time,checked,complete')->limit($offset,$perpage)->select();
        foreach ($txmxres as $k => $v){
            $txmxres[$k]['type_name'] = lang(get_withdraw_type_name($v['type']));
        }
	    datamsg(200, '获取提现记录成功', $txmxres);
    }

	//获取提现明细
    public function txinfo(){
	    $tokenRes = $this->checkToken();
	    if ($tokenRes['status'] == 400) {
		    datamsg(400, $tokenRes['mess'], $tokenRes['data']);
	    } else {
		    $userId = $tokenRes['user_id'];
	    }
        if(!input('post.tx_id')){
	        datamsg(400, '缺少提现记录参数', array('status'=>400));
        }
	    $tx_id = input('post.tx_id');
	    $txs = Db::name('withdraw')->where('id',$tx_id)->where('user_id',$userId)->field('id,tx_number,price,create_time,checked,complete,card_number,zs_name,bank_name,branch_name,remarks,wtime')->find();
	    if(!$txs){
	        datamsg(400, '找不到相关提现记录', array('status'=>400));
	    }
	    $txs['time'] = date('Y/m/d H:i:s',$txs['time']);
	    datamsg(200, '获取提现记录成功', $txs);
    }

}
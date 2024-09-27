<?php

namespace app\api\controller;

use think\Db;
use app\api\model\Wallet as WalletModel;
use app\api\model\BankCard;
use app\api\model\Member;
use app\api\model\MemberCoupon;

class  Wallet extends Common
{
    // 钱包页面
    public function index(){
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess'],$tokenRes['data']);
        }else{
            $userId = $tokenRes['user_id'];
        }
        $walletModel = new WalletModel();
        $wallet['money'] = $walletModel->getUserMoney($userId);
        $bankcardModel = new Bankcard();
        $wallet['bankcard_count'] = $bankcardModel->getBackCardCount($userId);
        $userModel = new Member();
        $userInfo = $userModel->getUserInfoById($userId);
        $wallet['integral'] = $userInfo['integral'];
        $memberCouponModel = new MemberCoupon();
        $wallet['coupon_count'] = $memberCouponModel->getUnusedCouponsCount($userId);

        if($wallet){
            datamsg(200,'获取成功',$wallet);
        }else{
            datamsg(400,'获取失败',array('status'=>400));
        }
    }

    // 钱包页面
    public function getWalletMoney(){
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess']);
        }else{
            $userId = $tokenRes['user_id'];
        }
        $walletModel = new WalletModel();
        $wallet = $walletModel->getUserMoney($userId);

        if($wallet){
            datamsg(200,'获取成功',$wallet);
        }else{
            datamsg(400,'获取失败',array('status'=>400));
        }
    }

    //退款明细
    public function getRefundList(){
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess']);
        }else{
            $userId = $tokenRes['user_id'];
        }

    }
}
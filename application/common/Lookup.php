<?php

namespace app\common;

class Lookup {
    
    const pageSize = 10;

    const isOpen = 1;
    const isClose = 0;
    
    const levelOne = 1;     //一级分销商
    const levelTwo = 2;     //二级分销商
    const levelThree = 3;   //三级分销商
    
    //成为下线条件
    const becomeChildOne = 1;   //首次点击分享链接 
    const becomeChildTwo = 2;   //首次下单
    const becomeChildThree = 3; //首次付款
    
    //成为分销商条件
    const becomeDistribZero = 0;    //无条件
    const becomeDistribOne = 1;     //申请
    const becomeDistribTwo = 2;     //消费次数
    const becomeDistribThree = 3;   //消费金额
    const becomeDistribFour = 4;    //购买商品
    
    //分销商升级条件
    const upgradeByUserCount = 1;       //邀请人数
    const upgradeByConsumeAmount = 2;   //邀请人消费金额
    const upgradeByGoodsId = 3;         //购买指定产品
    
    //分销商常量
    const isDistrib = 1;
    const isNotDistrib = 0;
    const checkRfuse = 0;
    const checkOn = 1;
    const checkPass = 2;
    const percent = 100;
    const roundPrecision = 2;
    
    //订单相关常量
    const isShow = 1;
    const allStatus = 1;
    const waitPayStatus = 2;
    const payStatus = 3;
    const finishStatus = 4;
    const zeroStatus = 0;
    const oneStatus = 1;
    
    //提现相关常量
    //提现审核状态
    const waitCheckWithdraw = 0;
    const passCheckWithdraw = 1;
    const rfuseCheckWithdraw = 2;
    //打款状态
    const waitPayStatusWithdraw = 0;
    const successPayStatusWithdraw = 1;
    const failPayStatusWithdraw = 2;
    
    const waitCheck = 2;
    const waitPay = 3;
    const finishPay = 4;
    const invalidStatus = 5;
    
    //图片存储路径
    const videoCoverImage = 'images/video_cover';
    const iconImage = 'images/icon';
    const bannerImage = 'images/banner';
    const videoCheckPass = 1;
    
    //自定义导航位
    const mobileHomePageNavId = 1;
    const pcHomePageNavId = 3;

    
    //积分类别
    const integralDeduct = 1;   //积分抵扣
    const integralPrice = 2;    //积分+商品价格
    const integralExchange = 3; //积分换购
    
    //任务状态  1:已完成; 2:未完成
    const integralCompleted = 1;
    const integralNotCompleted = 2;
    
    //配置参数
    const configCaId = '18';
    
    public static function getCateIdArr() {
        return explode(',', self::configCaId);
    }
}

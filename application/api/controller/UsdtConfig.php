<?php
namespace app\api\controller;
use think\Controller;
use think\Db;

class UsdtConfig extends Common{


    //获取USDT支付参数
    public function getUsdtConfig(){

        $tokenRes = $this->checkToken(0);
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess']);
        }

        $type = input('post.type');
        if(empty($type)){
            datamsg(400,'缺少USDT支付类型');
        }
        $usdtConfigRes = db('usdt_config')->where(['id'=>1])->find();
        if($type == 1){
            $usdtConfig = [];
            $usdtConfig['wallet'] = $usdtConfigRes['TRC20_wallet'];
            $usdtConfig['pic'] = $usdtConfigRes['TRC20_pic'];

        }elseif($type == 2){
            $usdtConfig = [];
            $usdtConfig['wallet'] = $usdtConfigRes['ERC20_wallet'];
            $usdtConfig['pic'] = $usdtConfigRes['ERC20_pic'];
        }
        datamsg(200,'获取USDT支付参数成功',$usdtConfig);
    }


}
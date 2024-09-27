<?php
namespace app\api\controller;
use think\Controller;
use think\Db;
use app\api\controller\Common;
use app\admin\services\Upush;
use app\api\model\RechargeOrder as RechargeOrderModel;

class Recharge extends Common{
    /**增加订单
     *  params:  充值金额   支付方式payway 0微信  1支付宝
     *  do:    生成订单信息  拉起支付
     **/
     public function createOrder(){
        //获取用户信息
	     $tokenRes = $this->checkToken();
	     if($tokenRes['status'] == 400){
		     datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	     }else{
		     $userId = $tokenRes['user_id'];
	     }
        $price = (float)input('param.price');
        if($price <=0 ){
            datamsg(400,'请输入充值金额');
        }

        $data['order_number'] = $this->makeOrderNum().$userId;
        $data['order_price'] = $price;
        $data['pay_status'] = 0;
        $data['uid'] = $userId;

        $rechargeOrderModel = new RechargeOrderModel();
        $result = $rechargeOrderModel->save($data);
        if($result){  //订单创建成功，拉取支付
            datamsg(200,"充值订单创建成功",array('order_number'=>$data['order_number']));
        }else{
            datamsg(400,"充值订单创建失败");
        }

    }



    /***
     * 生成唯一订单号
     */
    private function makeOrderNum(){
        $uonid = uniqid();
        $order_number = 'C'.time().$uonid; // 充值订单以大写字母C开头，请勿修改
        return $order_number;
    }


    //
    public function getRechargeList(){
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{
		    $userId = $tokenRes['user_id'];
	    }
        if(!input('post.page') && !preg_match("/^\\+?[1-9][0-9]*$/", input('post.page'))){
	        datamsg(400,'缺少页数',array('status'=>400));
        }

        $perpage = 20;
        $offset = (input('post.page')-1)*$perpage;
        $list = Db::name('recharge_order')->where('uid',$userId)->order('id desc')->limit($offset,$perpage)->select();
        foreach ($list as $k=>$v) {
            $list[$k]['pay_way'] = get_pay_type($v['pay_way']);
        }
	    datamsg(200,'获取成功',$list);
    }


}
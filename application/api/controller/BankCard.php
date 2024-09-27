<?php
namespace app\api\controller;
use app\api\controller\Common;
use app\api\model\Common as CommonModel;
use think\Db;
use app\api\model\BankCard as BackCardModel;

class BankCard extends Common{
    public function index(){
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{
		    $userId = $tokenRes['user_id'];
	    }
        $cards = Db::name('bank_card')->where('user_id',$userId)->field('id,name,telephone,card_number,bank_name,province,city,area,branch_name')->find();
        if($cards){
            $cards['card_number'] = format_bankcard_no($cards['card_number']);
            datamsg(200,'获取信息成功',$cards);
        }else{
        	datamsg(400,'暂未绑定银行卡',['tip_show'=>'close']);
        }
    }
    
    //添加银行卡
    public function add(){
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{
		    $userId = $tokenRes['user_id'];
	    }

	    $bankCardModel = new BackCardModel();
	    $card = $bankCardModel->getBankCardInfo($userId);
	    if($card){
	    	datamsg(400,'您已绑定银行卡，暂支持绑定一张银行卡');
	    }

        $data = input('post.');
        $validate = $this->validate($data,'BankCard');
        if($validate !== true){
	        datamsg(400,$validate);
        }

        $data['user_id'] = $userId;
        $result = $bankCardModel->allowField(true)->save($data);
        if($result){
            datamsg(200,'添加银行卡成功');
        }else{
            datamsg(400,'添加银行卡失败');
        }
    }
    
    //删除银行卡
    public function deletecard()
    {
	    $tokenRes = $this->checkToken();
	    if ($tokenRes['status'] == 400) {
		    datamsg(400, $tokenRes['mess'], $tokenRes['data']);
	    } else {
		    $userId = $tokenRes['user_id'];
	    }
	    if (!input('post.card_id')) {
		    datamsg(400, '缺少银行卡信息，解绑失败', array('status' => 400));
	    }
	    $card_id = input('post.card_id');
	    $cards   = Db::name('bank_card')->where('user_id', $userId)->where('id', $card_id)->find();
	    if (!$cards) {
		    datamsg(400, '缺少银行卡信息，解绑失败', array('status' => 400));
	    }
		    $count = Db::name('bank_card')->where('id', $card_id)->where('user_id', $userId)->delete();
		    if ($count > 0) {
			    datamsg(200, '解绑成功', array('status' => 200));
		    } else {
			    datamsg(400, '解绑失败', array('status' => 400));
		    }

    }
}
<?php

namespace app\api\controller;
use app\api\controller\Common;
use app\api\model\Member;
use app\api\model\MemberIntegral;
use app\api\model\IntegralTask;
use app\api\model\Goods as GoodsModel;
use app\common\Lookup;
use think\Db;

class IntegralShop extends Common{
    
    //获取用户积分相关信息
    public function getUserInfo() {
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{
		    $userId = $tokenRes['user_id'];
	    }
        $userModel = new Member();
        $user = $userModel->getUserInfoById($userId);
        $user['headimgurl'] = url_format($user['headimgurl'],$this->webconfig['weburl'],'?imageMogr2/thumbnail/200x200');
        $data = array('user' => $user);
        datamsg(200, 'success', $data);
    }
    
    //任务列表
    public function getTaskList() {
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{
		    $userId = $tokenRes['user_id'];
	    }
        $page = input('post.page', 1);
        if (!preg_match("/^\\+?[1-9][0-9]*$/", $page)) {
            datamsg(400, 'page参数错误');
        }
        $webconfig = $this->webconfig;
        $pageSize = $webconfig['app_goodlst_num'];
        $offset = ($page - 1) * $pageSize;
        
        $taskModel = new IntegralTask();
        $task_list = $taskModel->getTaskList($offset, $pageSize);
        //查询当前用户 当天 对每一项任务完成的状态 sp_member_integral
        $integralRecordModel = new MemberIntegral();
        //当天已完成任务获得的总积分/任务总积分
        $total_integral = $completed_integral = 0;
        foreach ($task_list as $key => $v) {
            $total_integral += $v['integral'];
            $record = $integralRecordModel->getIntegralRecord($userId, $v['id']);
            if (!$record) {
                $task_list[$key]['status'] = Lookup::integralNotCompleted;
            } else {
                $completed_integral += $v['integral'];
            }
        }
        $integral_info = array('total_integral' => $total_integral, 'completed_integral' => $completed_integral);
        $data = array('task_list' => $task_list, 'integral_info' => $integral_info);
        datamsg(200, 'success', set_lang($data));
    }
    
    //积分换购商品列表
    public function getGoodsList() {
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }
        $page = input('post.page', 1);
        if (!preg_match("/^\\+?[1-9][0-9]*$/", $page)) {
            datamsg(400, 'page参数错误');
        }
        $webconfig = $this->webconfig;
        $pageSize = $webconfig['app_goodlst_num'];
        $offset = ($page - 1) * $pageSize;
        $goodsModel = new GoodsModel();
        $goods_list = $goodsModel->getIntegralGoodsList($offset, $pageSize);
        foreach ($goods_list as $key => $v) {
            $goods_list[$key]['goods_img'] = url_format($v['goods_img'],$webconfig['weburl']);
            if(!empty($v['goods_attr'])){
                $res = $goodsModel->getGoodsShowPrice($v['goods_id'],'integral','list');
                $goods_list[$key]['integral']   =   $res['integral'];
                $goods_list[$key]['shop_price'] =   $res['integral_price'];
            }
            $goods_list[$key]['cate_str'] = lang('积分换购');
            $goods_list[$key]['buy_count'] = 0; //用积分兑换过该商品的总人数
        }
        $data = array('goods_list' => $goods_list);
        datamsg(200, 'success', $data);
    }
    
    //积分记录列表
    public function getIntegralRecordList() {
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{
		    $userId = $tokenRes['user_id'];
	    }
        $page = input('post.page', 1);
        if (!preg_match("/^\\+?[1-9][0-9]*$/", $page)) {
            datamsg(400, 'page参数错误');
        }
        $webconfig = $this->webconfig;
        $pageSize = $webconfig['app_goodlst_num'];
        $offset = ($page - 1) * $pageSize;
        $taskModel = new IntegralTask();
        $integralModel = new MemberIntegral();
        $task_arr = $taskModel->getTaskListAll();
        $record_list = $integralModel->getIntegralRecordByUserId($userId, $offset, $pageSize);
        foreach ($record_list as $key => $v) {
            $record_list[$key]['task_name'] = lang($task_arr[$v['type']]);
        }
        $data = array('record_list' => $record_list);
        datamsg(200, 'success', $data);
    }
    
    //分享获得积分
    public function getIntegralByShare() {
        $res = $this->checkToken();
        if($res['status'] == 400){
            return json($res);
        }
        $type = input('post.type');
        //1每日登录,2邀请注册,3完善信息（绑定手机）,4完善信息（上传头像）,5观看直播,6直播发言（次）,7直播分享（次）,8购物消费（%）,9订单评价（次）,10商品分享,11连续签到奖励,12普通签到奖励,13积分兑换,14后台积分操作
        
    }
}

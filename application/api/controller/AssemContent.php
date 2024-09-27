<?php
namespace app\api\controller;
use app\api\controller\Common;
use app\api\model\Common as CommonModel;
use think\Db;

class AssemContent extends Common{
    
    //获取服务项信息列表信息接口
    public function info(){
	    $tokenRes = $this->checkToken(0);
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }
        $infos = Db::name('assem_content')->where('id',1)->find();
        if($infos){
        	datamsg(200,'获取拼团规则信息成功',$infos);
        }else{
	        datamsg(400,'找不到相关拼团规则信息',array('status'=>400));
        }

    }
    
}
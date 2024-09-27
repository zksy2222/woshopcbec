<?php
namespace app\api\controller;
use app\api\controller\Common;
use app\api\model\Common as CommonModel;
use think\Db;

class Sertion extends Common{
    
    //获取服务项信息列表信息接口
    public function serlst()
    {
	    $tokenRes = $this->checkToken();
	    if ($tokenRes['status'] == 400) {
		    datamsg(400, $tokenRes['mess'], $tokenRes['data']);
	    }
	    if (!input('post.goods_id')) {
		    datamsg(400, '缺少商品信息参数', array('status' => 400));
	    }

	    $goodsId = input('post.goods_id');
	    $goods    = Db::name('goods')->where('id', $goodsId)->where('onsale', 1)->field('id,fuwu')->find();
	    if (!$goods) {
		    datamsg(400, '找不到相关商品信息', array('status' => 400));
	    }
	    if ($goods['fuwu']) {
		    $sertionres = Db::name('sertion')->where('id', 'in', $goods['fuwu'])->where('is_show', 1)
		                    ->field('id,ser_name,ser_remark')->order('sort asc')->select();
	    } else {
		    $sertionres = array();
	    }
	    datamsg(200, '获取服务信息成功', $sertionres);

    }
}
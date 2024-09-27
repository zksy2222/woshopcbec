<?php
/**
 * @Description: 拼团
 * @Author : 梧桐 <2487937004@qq.com>
 * @Copyright : 武汉一一零七科技有限公司 All rights reserved.
 */
namespace app\api\controller;
use app\api\controller\Common;
use app\api\model\Common as CommonModel;
use app\api\model\Goods as GoodsModel;
use think\Db;

class Assemble extends Common{
    
    //获取一级分类
    public function getcate(){
	    $tokenRes = $this->checkToken(0);
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }
        $cateres = Db::name('category')->where('pid',0)->where('is_show',1)->field('id,cate_name')->order('sort asc')->select();
	    datamsg(200, '获取一级分类信息成功', set_lang($cateres));

    }
    
    //根据分类获取拼团商品列表
    public function getGoodsList(){
	    $tokenRes = $this->checkToken(0);
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }
	    if(!input('post.page') && !preg_match("/^\\+?[1-9][0-9]*$/", input('post.page'))){
		    datamsg(400, '缺少页数参数', array('status'=>400));
	    }

        $pagenum = input('post.page');
        $webconfig = $this->webconfig;
        $perpage = $webconfig['app_goodlst_num'];
        $offset = ($pagenum-1)*$perpage;
        $where = array();
        if(!input('post.cate_id')){
             $where['a.recommend'] = 1;
        }
        $where['a.checked'] = 1;
        $where['a.is_show'] = 1;
        $where['a.start_time'] = array('elt',time());
        $where['a.end_time'] = array('gt',time());
        $where['a.finish_status'] = 0;
        $cate_id = input('post.cate_id');
        $cates = Db::name('category')->where('id',$cate_id)->where('pid',0)->where('is_show',1)->field('id,cate_name')->find();

        $categoryres = Db::name('category')->where('is_show',1)->field('id,pid')->order('sort asc')->select();
        $cateIds = array();
        $cateIds = get_all_child($categoryres, $cate_id);
        $cateIds[] = $cate_id;
        $cateIds = implode(',', $cateIds);
        $where['b.cate_id'] = array('in',$cateIds);


        $where['b.onsale'] = 1;
        $where['c.open_status'] = 1;

        $assembleres = Db::name('assemble')
                         ->alias('a')
                         ->field('a.id,a.goods_id,a.goods_attr,a.price,a.pin_num,a.sold,b.goods_name,b.thumb_url,b.shop_price,b.min_price,b.max_price,b.zs_price,b.leixing,b.shop_id')
                         ->join('sp_goods b','a.goods_id = b.id','INNER')
                         ->join('sp_shops c','a.shop_id = c.id','INNER')
                         ->where($where)
                         ->group('a.goods_id')
                         ->order('a.sort esc')
                         ->limit($offset,$perpage)
                         ->select();

        if($assembleres){
            $goodsModel = new GoodsModel();
            foreach ($assembleres as $kc => $vc){
                $assembleres[$kc]['thumb_url'] = url_format($vc['thumb_url'],$webconfig['weburl']);
                $assemblePrice = $goodsModel->getGoodsShowPrice($vc['goods_id'],'assemble','list');
                $assembleres[$kc]['price'] = $assemblePrice['assemble_price'];
                $assembleres[$kc]['shop_price'] = $assemblePrice['shop_price'];

                $assembleres[$kc]['pin_number'] = Db::name('order_assemble')->where('hd_id',$vc['id'])->where('state',1)->count();
            }
        }
	    datamsg(200, '获取拼团商品列表成功', $assembleres);

    }

    // 用户拼单信息
    public function pindanInfo(){
        $tokenRes = $this->checkToken(0);
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess'],$tokenRes['data']);
        }
        $pindanId = input('post.pindan_id');
        if(empty($pindanId)){
            datamsg(400,'缺少拼单ID参数');
        }
        $pindanInfo = Db::name('pintuan')
                        ->where('id',$pindanId)
                        ->where('time','elt',time())
                        ->where('timeout','gt',time())
                        ->field('id,assem_number,pin_num,tuan_num,tz_id,timeout')
                        ->find();

        $pindanInfo['tz'] = Db::name('member')
                              ->where('id',$pindanInfo['tz_id'])
                              ->field('user_name,headimgurl')
                              ->find();

        $pindanInfo['tz']['headimgurl'] = url_format($pindanInfo['tz']['headimgurl'],$this->webconfig['weburl']);

        if($pindanInfo['pin_status'] == 0 && $pindanInfo['tuan_num'] < $pindanInfo['pin_num']){
            datamsg(200,'拼团正在进行中',$pindanInfo);
        }else{
            datamsg(400,'拼团已结束',$pindanInfo);
        }
    }
    
}
<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\common\model\DistributionConfig;
use app\common\Lookup;

class Distribution extends Common{
    
    public function info(){
        if(request()->isPost()){
            $data = input('post.');
            $result = $this->validate($data,'Distribution');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $distributions = Db::name('distribution')->where('id',1)->find();
                if($distributions){
                    $count = Db::name('distribution')->update(array(
                        'is_open' => $data['is_open'],
                        'one_profit'=>$data['one_profit'],
                        'two_profit'=>$data['two_profit'],
                        'id'=>$distributions['id']
                    ));
                    if($count !== false){
                        $value = array('status'=>1,'mess'=>'保存成功');
                        ys_admin_logs('编辑分销信息配置','distribution',$distributions['id']);
                    }else{
                        $value = array('status'=>0,'mess'=>'保存失败');
                    }
                }else{
                    $lastId = Db::name('distribution')->insertGetId(array(
                        'is_open' => $data['is_open'],
                        'one_profit'=>$data['one_profit'],
                        'two_profit'=>$data['two_profit'],
                    ));
                    if($lastId){
                        $value = array('status'=>1,'mess'=>'保存成功');
                        ys_admin_logs('新增分销信息配置','distribution',$lastId);
                    }else{
                        $value = array('status'=>0,'mess'=>'保存失败');
                    }
                }
            }
            return json($value);
        }else{
            $distributions = Db::name('distribution')->where('id',1)->find();
            $this->assign('distributions',$distributions);
            return $this->fetch();
        }
    }
    
    public function configure() {
        $distribModel = new DistributionConfig();
        $distrib = $distribModel->getDistributionConfig();
        $shop_id = session('shop_id');
        $goods_list = Db::name('goods')->alias('a')
                   ->field('a.id,a.goods_name,a.thumb_url,a.shop_price,b.cate_name')
                   ->join('sp_shop_cate b','a.shcate_id = b.id','LEFT')
                   ->where('a.id','in',$distrib['goods_id'])
                   ->where('a.shop_id',$shop_id)
                   ->where('a.onsale',1)
                   ->order('a.addtime desc')
                   ->select();
        $this->assign('distrib', $distrib);
        $this->assign('goods_list', $goods_list);
        return $this->fetch();
    }
    
    public function setConfig() {
        if (!request()->isPost()) {
            return json(array('status' => 0, 'mess' => '请求方式错误'));
        }
        $data = input('post.');
        $result = $this->validate($data, 'DistributionConfig');
        if(true !== $result){
            return json(array('status' => 0,'mess' => $result));
        }
        $regex = '/^\+?[1-9][0-9]*$/';
        if ($data['become_distrib'] == Lookup::becomeDistribTwo) {
            if (!preg_match($regex, $data['consume_count'])) {
                return json(array('status' => 0,'mess' => '消费次数请填写正整数'));
            }
            $data['consume_amount'] = NULL;
            $data['goods_id'] = NULL;
        } elseif ($data['become_distrib'] == Lookup::becomeDistribThree) {
            if (!preg_match($regex, $data['consume_amount'])) {
                return json(array('status' => 0,'mess' => '消费金额请填写正整数'));
            }
            $data['consume_count'] = NULL;
            $data['goods_id'] = NULL;
        } elseif ($data['become_distrib'] == Lookup::becomeDistribFour) {
            if (!$data['goods_id']) {
                return json(array('status' => 0,'mess' => '请选择商品'));
            }
            $data['goods_id'] = implode(',', $data['goods_id']);
            $data['consume_count'] = NULL;
            $data['consume_amount'] = NULL;
        }
        $distribModel = new DistributionConfig();
        $distrib = $distribModel->getDistributionConfig();
        if (!$distrib) {
            $distribModel->save($data);
        } else {
            $distribModel->update($data, array('id' => $distrib['id']));
        }
        return json(array('status' => 1,'mess' => '配置保存成功'));
    }
    
}
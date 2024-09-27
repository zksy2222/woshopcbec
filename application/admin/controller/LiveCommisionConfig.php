<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\Link as LinkMx;

class LiveCommisionConfig extends Common{
    
    public function lst(){
        $list = Db::name('live_commision_config a')->join('shops b','a.shop_id = b.id')->field('a.*,b.shop_name')->select();
        $this->assign('list',$list);// 赋值数据集
        return $this->fetch();
    }

    public function add(){
        if(request()->isPost()){
            $data = input('post.');
            $data['create_time'] = time();
            $result = $this->validate($data,'LiveCommisionConfig');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                try{
                    $liveCommisionConfigId = db('live_commision_config')->insertGetId($data);
                    if($liveCommisionConfigId){
                        ys_admin_logs('新增直播分成配置','live_commision_config',$liveCommisionConfigId);
                        $value = array('status'=>1,'mess'=>'增加成功');
                    }else{
                        $value = array('status'=>0,'mess'=>'增加失败');
                    }
                }catch (\Exception $e) {
                    return $this->error($e->getMessage());
                }

            }
            return $value;
        }else{
            $shopsNameRes = db('shops')->where(['normal'=>1])->whereNotIn('id','1')->field('id,shop_name')->select();
            $this->assign('shopsNameRes',$shopsNameRes);// 赋值数据集
            return $this->fetch();
        }
    }

    public function edit(){
        if(request()->isPost()){
            if(input('post.id')){
                $data = input('post.');
                $result = $this->validate($data,'LiveCommisionConfig');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    $liveCommisionConfigId = Db::name('live_commision_config')->where('id',$data['id'])->field('id')->find();
                    if($liveCommisionConfigId){
                        $count = db('live_commision_config')->update($data);
                        if($count !== false){
                            ys_admin_logs('编辑直播分成配置','LiveCommisionConfig',$data['id']);
                            $value = array('status'=>1,'mess'=>'编辑成功');
                        }else{
                            $value = array('status'=>0,'mess'=>'编辑失败');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'找不到相关信息，编辑失败');
                    }
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少参数，编辑失败');
            }
            return json($value);
        }else{
            if(input('id')){
                $id = input('id');
                $liveCommisionConfigs = Db::name('LiveCommisionConfig')->find($id);
                $shopsNameRes = db('shops')->where(['normal'=>1])->whereNotIn('id','1')->field('id,shop_name')->select();
                $this->assign('shopsNameRes',$shopsNameRes);// 赋值数据集
                if($liveCommisionConfigs){
                    $this->assign('liveCommisionConfig', $liveCommisionConfigs);
                    return $this->fetch();
                }else{
                    $this->error('找不到相关信息1');
                }
            }else{
                $this->error('缺少参数');
            }
        }
    }

    public function delete(){
        if(input('post.id')){
            $id= array_filter(explode(',', input('post.id')));
        }else{
            $id = input('id');
        }
        if(!empty($id)){
            $count = db('LiveCommisionConfig')->delete($id);
            if($count > 0){
                if(is_array($id)){
                    foreach ($id as $v2){
                        ys_admin_logs('删除店铺直播分成配置','LiveCommisionConfig',$v2);
                    }
                }else{
                    ys_admin_logs('删除店铺直播分成配置','LiveCommisionConfig',$id);
                }
                $value = array('status'=>1,'mess'=>'删除成功');
            }else{
                $value = array('status'=>0,'mess'=>'编辑失败');
            }
        }else{
            $value = array('status'=>0,'mess'=>'请选择删除项');
        }
        return $value;
    }

    public function paixu(){
        if(request()->isAjax()){
            if(input('post.ids') && input('post.sort')){
                $ids = input('post.ids');
                $sort = input('post.sort');
                $ids = explode(',', $ids);
                $sort = explode(',', $sort);
                foreach ($ids as $k => $v){
                    Db::name('link')->where('id',$v)->update(array('sort'=>$sort[$k]));
                }
            }
            $value = array('status'=>1,'mess'=>'排序成功');
            return json($value);
        }
    }
}
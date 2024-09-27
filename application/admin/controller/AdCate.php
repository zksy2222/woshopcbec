<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\AdCate as AdCateModel;

class AdCate extends Common{
    //广告位列表
    public function lst(){
        $list = Db::name('ad_cate')->paginate(25);
        $page = $list->render();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $this->assign('pnum',$pnum);   
        $this->assign('page',$page);   
        $this->assign('list',$list);
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch();
        }
    }

    public function checkPosname(){
        if(request()->isAjax()){
            $arr = Db::name('ad_cate')->where('cate_name',input('post.cate_name'))->find();
            if($arr){
                echo 'false';
            }else{
                echo 'true';
            }
        }else{
            $this->error('非法请求');
        }
    }

    public function add(){
        if(request()->isAjax()){
            $data = input('post.');
            $result = $this->validate($data,'AdCate');

            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $adCateModel = new AdCateModel();
                $adCateModel->data($data);
                $lastId = $adCateModel->allowField(true)->save();
                if($lastId){
                    ys_admin_logs('新增广告位','ad_cate',$adCateModel->id);
                    $value = array('status'=>1, 'mess'=>'增加成功');
                }else{
                    $value = array('status'=>0, 'mess'=>'增加失败');
                }
            }
            return json($value);
        }else{
            return $this->fetch();
        }
    }
    
    public function edit(){
        if(request()->isAjax()){
            if(input('post.id')){
                $data = input('post.');
                $result = $this->validate($data,'ad_cate');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    $info = Db::name('ad_cate')->where('id',$data['id'])->find();
                    if($info){
                        $adCateModel = new AdCateModel();
                        $count = $adCateModel->allowField(true)->save($data,array('id'=>$data['id']));
                        if($count !== false){
                            ys_admin_logs('编辑广告位','ad_cate',$data['id']);
                            $value = array('status'=>1, 'mess'=>'编辑成功');
                        }else{
                            $value = array('status'=>0, 'mess'=>'编辑失败');
                        }
                    }else{
                        $value = array('status'=>0, 'mess'=>'找不到相关信息');
                    }
                }
            }else{
                $value = array('status'=>0, 'mess'=>'缺少参数，编辑失败');
            }
            return json($value);
        }else{
            if(input('id')){
                $id = input('id');
                $info = Db::name('ad_cate')->where('id',$id)->find();
                if($info){
                    if(input('s')){
                        $this->assign('search', input('s'));
                    }
                    $this->assign('pnum', input('page'));
                    $this->assign('info',$info);
                    return $this->fetch();
                }else{
                    $this->error('找不到相关信息');
                }
            }else{
                $this->error('缺少参数');
            }
        }
    }
    
    public function delete(){
       $id = input('id');
       if(!empty($id) && !is_array($id)){
           $ad = Db::name('ad')->where('cate_id',$id)->field('id')->limit(1)->find();
           if(!empty($ad)){
               $value = array('status'=>0,'mess'=>'该广告位下存在广告，删除失败');
           }else{
               $count = AdCateModel::destroy($id);
               if($count > 0){
                   ys_admin_logs('删除广告位','ad_cate',$id);
                   $value = array('status'=>1,'mess'=>'删除成功');
               }else{
                   $value = array('status'=>0,'mess'=>'删除失败');
               }
           }
       }else{
           $value = array('status'=>0,'mess'=>'未选中任何数据');
       }
       return json($value);
    }

    public function search(){       
        if(input('post.keyword')){    
            cookie('cate_name',input('post.keyword'),3600);
        }
        $where = array();
        if(cookie('cate_name') != ''){
            $where['cate_name'] = array('like','%'.cookie('cate_name').'%');
        }
        $list = Db::name('ad_cate')->where($where)->paginate(25);
        $page = $list->render();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $search = 1;
        if(cookie('cate_name')){
            $this->assign('cate_name',cookie('cate_name'));
        }
        $this->assign('search',$search);
        $this->assign('pnum', $pnum);
        $this->assign('list', $list);// 赋值数据集
        $this->assign('page', $page);// 赋值分页输出
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }
}
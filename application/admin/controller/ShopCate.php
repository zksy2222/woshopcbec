<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\ShopCate as ShopCateMx;

class ShopCate extends Common{
    //栏目列表
    public function lst(){
        $shop_id = session('shop_id');
        $list = Db::name('shop_cate')->where('shop_id',$shop_id)->field('id,cate_name,pid,sort,is_show')->order('sort asc')->select();
        $this->assign('list', recursive($list));
        return $this->fetch();     
    }
    
    //修改状态
    public function gaibian(){
        $shop_id = session('shop_id');
        $id = input('post.id');
        $cates = Db::name('shop_cate')->where('id',$id)->where('shop_id',$shop_id)->field('id')->find();
        if($cates){
            $name = input('post.name');
            $value = input('post.value');
            $data[$name] = $value;
            $data['id'] = $id;
            $count = Db::name('shop_cate')->where('id',$data['id'])->where('shop_id',$shop_id)->update($data);
            if($count > 0){
                $result = 1;
            }else{
                $result = 0;
            }
        }else{
            $result = 0;
        }
        return $result;
    }
    
    //添加分类
    public function add(){
        if(request()->isAjax()){
            $admin_id = session('admin_id');
            $shop_id = session('shop_id');
            $data = input('post.');
            $data['shop_id'] = $shop_id;
            $result = $this->validate($data,'ShopCate');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                if(!empty($data['pic_id'])){
                    $data['cate_pic'] = $data['pic_id'];
                }
                
                $cate = new ShopCateMx();
                $cate->data($data);
                $lastId = $cate->allowField(true)->save();
                if($lastId){
                    $value = array('status'=>1,'mess'=>'增加成功');
                }else{
                    $value = array('status'=>0,'mess'=>'增加失败');
                }
            }
            return json($value);
        }else{
            $shop_id = session('shop_id');
            $cateres = Db::name('shop_cate')->where('shop_id',$shop_id)->field('id,cate_name,pid')->order('sort asc')->select();
            $this->assign('cateres', recursive($cateres));
            return $this->fetch();
        }
    }       
    
    /*
     * 编辑分类
     */
    public function edit(){
        if(request()->isAjax()){
            if(input('post.id')){
                $admin_id = session('admin_id');
                $shop_id = session('shop_id');
                $data = input('post.');
                $data['shop_id'] = $shop_id;
                
                $result = $this->validate($data,'ShopCate');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    $cateinfos = Db::name('shop_cate')->where('id',$data['id'])->where('shop_id',$shop_id)->find();
                    if($cateinfos){
                        if(!empty($data['pic_id'])){
                            $data['cate_pic'] = $data['pic_id'];
                        }else{
                            $data['cate_pic'] = $cateinfos['cate_pic'];
                        }
                        
                        $cate = new ShopCateMx();
                        $count = $cate->allowField(true)->save($data,array('id'=>$data['id']));
                        if($count !== false){
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
                $shop_id = session('shop_id');
                $id = input('id');
                $cates = Db::name('shop_cate')->where('id',$id)->where('shop_id',$shop_id)->find();
                if($cates){
                
                    $cateres = Db::name('shop_cate')->where('id','neq',$id)->where('shop_id',$shop_id)->field('id,cate_name,pid')->order('sort asc')->select();
                    $this->assign('cateres', recursive($cateres));
                    $this->assign('cates', $cates);
                    return $this->fetch();
                }else{
                    $this->error('找不到相关信息');
                }
            }else{
                $this->error('缺少参数');
            }
        }
    }    
    
    //处理删除分类
    public function delete(){
        $shop_id = session('shop_id');
        $id = input('id');
        if(!empty($id)){
            $cates = Db::name('shop_cate')->where('id',$id)->where('shop_id',$shop_id)->field('id')->find();
            if($cates){
                $child = Db::name('shop_cate')->where('pid',$id)->where('shop_id',$shop_id)->field('id')->find();
                if(!empty($child)){
                    $value = array('status'=>0,'mess'=>'该分类下存在子分类，删除失败');
                }else{
                    $goods = Db::name('goods')->where('shcate_id',$id)->where('shop_id',$shop_id)->where('is_recycle',0)->field('id')->find();
                    if(!empty($goods)){
                        $value = array('status'=>0,'mess'=>'该分类存在商品，删除失败');
                    }else{
                        $cate_pic = Db::name('shop_cate')->where('id',$id)->value('cate_pic');
                        $count = ShopCateMX::destroy($id);
                        if($count > 0){
                            if(!empty($cate_pic) && file_exists('./'.$cate_pic)){
                                @unlink('./'.$cate_pic);
                            }
                            $value = array('status'=>1,'mess'=>'删除成功');
                        }else{
                            $value = array('status'=>0,'mess'=>'删除失败');
                        }
                    }
                }
            }else{
                $value = array('status'=>0,'mess'=>'找不到相关信息');
            }
        }else{
            $value = array('status'=>0,'mess'=>'未选中任何数据');
        }
        return json($value);
    }
    
    //处理排序
    public function order(){
        $shop_id = session('shop_id');
        $cate = new ShopCateMx();
        $data = input('post.');
        if(!empty($data['sort'])){
            foreach ($data['sort'] as $key => $v){
                $data2['sort'] = $v;
                $data2['id'] = $key;
                $cateinfos = Db::name('shop_cate')->where('id',$data2['id'])->where('shop_id',$shop_id)->find();
                if($cateinfos){
                    $cate->save($data2,array('id'=>$data2['id']));
                }
            }
            $value = array('status'=>1,'mess'=>'更新排序成功');
        }else{
            $value = array('status'=>0,'mess'=>'未修改任何排序');
        }
        return json($value);
    }
}
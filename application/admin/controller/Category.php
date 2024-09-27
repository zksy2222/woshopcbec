<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\Category as CategoryMx;

class Category extends Common{
    //栏目列表
    public function lst(){
        $list = Db::name('category')->field('id,cate_name,pid,sort,is_show,show_in_recommend')->order('sort asc')->select();
        $this->assign('list', recursive($list));
        return $this->fetch();     
    }
    
    //修改状态
    public function gaibian(){
        $id = input('post.id');
        $name = input('post.name');
        $value = input('post.value');
        $data[$name] = $value;
        $data['id'] = $id;
        if($name == 'show_in_recommend' && $value == 1){
            $cateinfo = Db::name('category')->where('id',$id)->field('id,pid')->find();
            if($cateinfo){
                $childcate = Db::name('category')->where('pid',$id)->find();
                if($childcate){
                    $result = 0;
                    return $result;
                }
                
                if($cateinfo['pid'] != 0){
                    $categoryres = Db::name('category')->field('id,cate_name,pid')->order('sort asc')->select();
                    $cateidres = get_all_parent($categoryres, $cateinfo['pid']);
                    foreach ($cateidres as $v){
                        $catexinxi = Db::name('category')->where('id',$v)->value('show_in_recommend');
                        if($catexinxi['show_in_recommend'] == 1){
                            Db::name('category')->update(array('show_in_recommend'=>0,'id'=>$v));
                        }
                    }
                }
            }else{
                $result = 0;
                return $result;
            }
        }
        
        $cate = new CategoryMx();
        
        $count = $cate->save($data,array('id'=>$data['id']));
        if($count > 0){
            if($value == 1){
                ys_admin_logs('显示商品分类','category',$id);
            }elseif($value == 0){
                ys_admin_logs('隐藏商品分类','category',$id);
            }
            $result = 1;
        }else{
            $result = 0;
        }
        return $result;
    }
    
    public function checkCatename(){
        if(request()->isPost()){
            $arr = Db::name('category')->where('cate_name',input('post.cate_name'))->find();
            if($arr){
                echo 'false';
            }else{
                echo 'true';
            }
        }
    }
    
    //添加分类
    public function add(){
        if(request()->isAjax()){
            $data = input('post.');
            $admin_id = session('admin_id');
            $result = $this->validate($data,'Category');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                if($data['pid'] != 0){
                    $pcateinfos = Db::name('category')->where('id',$data['pid'])->find();
                    if(!$pcateinfos){
                        $value = array('status'=>0,'mess'=>'参数错误，新增失败');
                        return json($value);
                    }else{
                        $cateres = Db::name('category')->field('id,pid')->order('sort asc')->select();
                        $pidres = get_all_parent($cateres, $data['pid']);
                        if(count($pidres) == 3){
                            $value = array('status'=>0,'mess'=>'最多允许添加三级分类');
                            return json($value);
                        }
                    }
                }
                
                if($data['recommend'] == 1){
                    if($data['pid'] != 0){
                        $value = array('status'=>0,'mess'=>'只有顶级分类才可设为主页推荐');
                        return json($value);
                    }
                }
                
                if(!empty($data['search_keywords'])){
                    $data['search_keywords'] = str_replace('，', ',', $data['search_keywords']);
                }else{
                    $data['search_keywords'] = '';
                }
                
                if(!empty($data['cate_pic'])){
                    $data['cate_pic'] = $data['cate_pic'];
                }
                
                $cate = new CategoryMx();
                $cate->data($data);
                $lastId = $cate->allowField(true)->save();
                if($lastId){
                    if($data['pid'] != 0){
                        Db::name('category')->update(array('id'=>$data['pid'],'tjgd'=>0));
                        if($data['show_in_recommend'] == 1){
                            $categoryres = Db::name('category')->field('id,cate_name,pid')->order('sort asc')->select();
                            $cateidres = get_all_parent($categoryres, $data['pid']);
                            foreach ($cateidres as $v){
                                $catexinxi = Db::name('category')->where('id',$v)->value('show_in_recommend');
                                if($catexinxi['show_in_recommend'] == 1){
                                    Db::name('category')->update(array('show_in_recommend'=>0,'id'=>$v));
                                }
                            }
                        }
                    }

                    ys_admin_logs('新增商品分类','category',$cate->id);
                    $value = array('status'=>1,'mess'=>'增加成功');
                }else{
                    $value = array('status'=>0,'mess'=>'增加失败');
                }
            }
            return json($value);
        }else{
            $cateres = Db::name('category')->field('id,cate_name,pid')->order('sort asc')->select();
            $typeres = Db::name('type')->order('sort asc')->select();
            $this->assign('cateres', recursive($cateres));
            $this->assign('typeres',$typeres);
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
                $data = input('post.');
                $result = $this->validate($data,'Category');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    $cateinfos = Db::name('category')->where('id',$data['id'])->find();
                    if($cateinfos){
                        if($data['pid'] != 0){
                            $pcateinfos = Db::name('category')->where('id',$data['pid'])->find();
                            if(!$pcateinfos){
                                $value = array('status'=>0,'mess'=>'参数错误，新增失败');
                                return json($value);
                            }else{
                                $cateres = Db::name('category')->field('id,pid')->order('sort asc')->select();
                                $pidres = get_all_parent($cateres, $data['pid']);
                                if(count($pidres) == 3){
                                    $value = array('status'=>0,'mess'=>'最多允许添加三级分类');
                                    return json($value);
                                }
                            }
                        }
                        
                        if($data['recommend'] == 1){
                            if($data['pid'] != 0){
                                $value = array('status'=>0,'mess'=>'只有顶级分类才可设为主页推荐');
                                return json($value);
                            }
                        }
                        
                        if($data['show_in_recommend'] == 1){
                            $childcate = Db::name('category')->where('pid',$data['id'])->find();
                            if($childcate){
                                $value = array('status'=>0,'mess'=>'该分类存在下级分类，不允许设为推荐分类');
                                return json($value);
                            }
                        }
                       
                        if($data['pid'] != $cateinfos['pid']){
                            $cateres = Db::name('category')->field('id,cate_name,pid')->order('sort asc')->select();
                            $cateIds = get_all_parent($cateres, $data['id']);
                            $cid = end($cateIds);
                            $manages = Db::name('manage_cate')->where('cate_id',$cid)->field('id')->find();
                            
                            if($data['pid'] == 0){
                                if($manages){
                                    $value = array('status'=>0,'mess'=>'已有商家经营该分类，不可设为顶级分类');
                                    return json($value);
                                }
                            }else{
                                if($manages){
                                    $catesz = get_all_parent($cateres, $data['pid']);
                                    $cidsz = end($catesz);
                                    if($cidsz != $cid){
                                        $value = array('status'=>0,'mess'=>'已有商家经营该分类，编辑失败');
                                        return json($value);
                                    }
                                }
                            }
                        }
                        
                        if(!empty($data['search_keywords'])){
                            $data['search_keywords'] = str_replace('，', ',', $data['search_keywords']);
                        }else{
                            $data['search_keywords'] = '';
                        }

                        if(!empty($data['cate_pic'])){
                            $data['cate_pic'] = $data['cate_pic'];
                        }else{
                            if(!empty($cateinfos['cate_pic'])){
                                $data['cate_pic'] = $cateinfos['cate_pic'];
                            }else{
                                $data['cate_pic'] = '';
                            }
                        }
                        
                        $count = Db::name('category')->update(array(
                            'cate_name'=>$data['cate_name'],
                            'cate_pic'=>$data['cate_pic'],
                            'search_keywords'=>$data['search_keywords'],
                            'keywords'=>$data['keywords'],
                            'cate_desc'=>$data['cate_desc'],
                            'is_show'=>$data['is_show'],
                            'show_in_recommend'=>$data['show_in_recommend'],
                            'pid'=>$data['pid'],
                            'sort'=>$data['sort'],
                            'id'=>$data['id']
                        ));
                        
                        if($count !== false){
                            if($data['pid'] != 0){
                                Db::name('category')->update(array('id'=>$data['pid'],'tjgd'=>0));
                                if($data['show_in_recommend'] == 1){
                                    $categoryres = Db::name('category')->field('id,cate_name,pid')->order('sort asc')->select();
                                    $cateidres = get_all_parent($categoryres, $data['pid']);
                                    foreach ($cateidres as $v){
                                        $catexinxi = Db::name('category')->where('id',$v)->value('show_in_recommend');
                                        if($catexinxi['show_in_recommend'] == 1){
                                            Db::name('category')->update(array('show_in_recommend'=>0,'id'=>$v));
                                        }
                                    }
                                }
                            }

                            ys_admin_logs('编辑商品分类','category',$data['id']);
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
                $cates = Db::name('category')->where('id',$id)->find();
                if($cates){
                    $cateres = Db::name('category')->where('id','neq',$id)->field('id,cate_name,pid,cate_pic')->order('sort asc')->select();
                    $typeres = Db::name('type')->order('sort asc')->select();
                    $this->assign('cateres', recursive($cateres));
                    $this->assign('typeres',$typeres);
                    $this->assign('cates', $cates);
                    return $this->fetch();
                }else{
                    $this->error('找不到先关信息');
                }
            }else{
                $this->error('缺少参数');
            }
        }
    }    
    
    //处理删除分类
    public function delete(){
        $id = input('id');
        if(!empty($id)){
            $child = Db::name('category')->where('pid',$id)->field('id')->limit(1)->find();
            if(!empty($child)){
                $value = array('status'=>0,'mess'=>'该分类下存在子分类，删除失败');
            }else{
                $goods = Db::name('goods')->where(['cate_id'=>$id,'shop_id'=>1])->limit(1)->field('id')->find();
                if(!empty($goods)){
                    $value = array('status'=>0,'mess'=>'该分类存在商品，删除失败');
                }else{
                    $manages = Db::name('manage_cate')->where(['cate_id'=>$id,'shop_id'=>1])->field('id')->find();
                    if(!empty($manages)){
                        $value = array('status'=>0,'mess'=>'存在商家经营该分类，删除失败');
                    }else{
                        $cate_pic = Db::name('category')->where('id',$id)->value('cate_pic');
                        $count = CategoryMX::destroy($id);
                        if($count > 0){
                            if(!empty($cate_pic) && file_exists('./'.$cate_pic)){
                                @unlink('./'.$cate_pic);
                            }
                            ys_admin_logs('删除分类','category',$id);
                            $value = array('status'=>1,'mess'=>'删除成功');
                        }else{
                            $value = array('status'=>0,'mess'=>'删除失败');
                        }
                    }
                }
            }
        }else{
            $value = array('status'=>0,'mess'=>'未选中任何数据');
        }
        return json($value);
    }
    
    //处理排序
    public function order(){
        $cate = new CategoryMx();
        $data = input('post.');
        if(!empty($data['sort'])){
            foreach ($data['sort'] as $key => $v){
                $data2['sort'] = $v;
                $data2['id'] = $key;
                $cate->save($data2,array('id'=>$data2['id']));
            }
            ys_admin_logs('更改商品分类排序','category',1);
            $value = array('status'=>1,'mess'=>'更新排序成功');
        }else{
            $value = array('status'=>0,'mess'=>'未修改任何排序');
        }
        return json($value);
    }
}
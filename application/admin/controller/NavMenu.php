<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\NavMenu as NavMenuMx;
use app\common\model\CosFileUpload;
use app\common\Lookup;

class NavMenu extends Common{
    //导航位菜单列表
    public function lst(){
        $list = Db::name('nav_menu')->alias('a')->field('a.id,a.menu_name,a.menu_url,a.sort,a.url_type,b.nav_name')->join('sp_nav b','a.nav_id = b.id','LEFT')->order('a.sort asc')->paginate(25);
        $page = $list->render();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $this->assign('pnum',$pnum);
        $this->assign('list',$list);
        $this->assign('page',$page);
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }
    
    
    //根据导航位置获取菜单列表
    public function navlist(){
        if(input('nav_id')){
            $id = input('nav_id');
            $nav_name = Db::name('nav')->where('id',$id)->value('nav_name');
            $list = Db::name('nav_menu')->alias('a')->field('a.id,a.menu_name,a.menu_url,a.sort,a.url_type,a.image_path,a.is_show')->join('sp_nav b','a.nav_id = b.id','LEFT')->where('a.nav_id',$id)->order('a.sort DESC')->paginate(25);
            $page = $list->render();
            if(input('page')){
                $pnum = input('page');
            }else{
                $pnum = 1;
            }
            $this->assign('nav_id',$id);
            $this->assign('nav_name',$nav_name);
            $this->assign('pnum',$pnum);
            $this->assign('list',$list);
            $this->assign('page',$page);
            $this->assign('cos_domain', $this->webconfig['cos_domain']);
            if(request()->isAjax()){
                return $this->fetch('ajaxpage');
            }else{
                return $this->fetch('lst');
            }
        }else{
            $this->error('缺少参数');
        }
    }
    
    //修改新窗口打开
    public function gaibian(){
        $id = input('post.id');
        $name = input('post.name');
        $value = input('post.value');
        $data[$name] = $value;
        $data['id'] = $id;
        $count = Db::name('nav_menu')->update($data);
        if($count > 0){
            $result = 1;
        }else{
            $result = 0;
        }
        return $result;
    }
    
    //添加导航菜单
    public function add(){
        if(request()->isAjax()){
            $data = input('post.');
            $result = $this->validate($data,'NavMenu');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $menu = new NavMenuMx();
                $menu->data($data);
                $lastId = $menu->allowField(true)->save();
                if($lastId){
                    ys_admin_logs('新增导航菜单','nav_menu',$menu->id);
                    $value = array('status'=>1,'mess'=>'增加成功');
                }else{
                    $value = array('status'=>0,'mess'=>'增加失败');
                }
            }
            return $value;
        }else{
            $this->assign('nav_id',input('nav_id'));
            return $this->fetch();
        }
    }
    
    //编辑导航菜单
    public function edit(){
        if(request()->isAjax()){
            if(input('post.id')){
                $data = input('post.');
                $result = $this->validate($data,'NavMenu');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    $nav_menus = Db::name('nav_menu')->where('id',$data['id'])->field('id')->find();
                    if($nav_menus){
                        $menus = Db::name('nav_menu')->where('id','neq',$data['id'])->where('nav_id',$data['nav_id'])->where('menu_name',$data['menu_name'])->find();
                        if(!$menus){
                            $menu = new NavMenuMx();
                            $count = $menu->allowField(true)->save($data,array('id'=>$data['id']));
                            if($count !== false){
                                ys_admin_logs('编辑导航菜单','nav_menu',$data['id']);
                                $value = array('status'=>1,'mess'=>'编辑成功');
                            }else{
                                $value = array('status'=>0,'mess'=>'编辑失败');
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'该导航位已存在该菜单，编辑失败');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'找不到相关信息');
                    }
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少参数，编辑失败');
            }
            return $value;
        }else{
            if(input('id')){
                $id = input('id');
                $menus = Db::name('nav_menu')->find($id);
                if($menus){
                    $this->assign('pnum', input('page', 1));
                    if(input('s')){
                        $this->assign('search', input('s'));
                    }
                    $this->assign('nav_id', input('nav_id'));
                    $this->assign('menus',$menus);
                    return $this->fetch();
                }else{
                    $this->error('找不到相关信息');
                }
            }else{
                $this->error('缺少参数');
            }
        }
    }
    
    
    //删除菜单
    public function delete(){
        if(input('post.id')){
            $id = array_filter(explode(',', input('post.id')));
        }else{
            $id = input('id');
        }
        
        if(!empty($id)){
            if(is_array($id)){
                $delId = implode(',', $id);
                $count = NavMenuMx::destroy($delId);
            }else{
                $count = NavMenuMx::destroy($id);
            }
            if($count > 0){
                if(is_array($id)){
                    foreach ($id as $v2){
                        ys_admin_logs('删除导航菜单','nav_menu',$v2);
                    }
                }else{
                    ys_admin_logs('删除导航菜单','nav_menu',$id);
                }
                $value = array('status'=>1,'mess'=>'删除成功');
            }else{
                $value = array('status'=>0,'mess'=>'删除失败');
            }
        }else{
            $value = array('status'=>0,'mess'=>'请选择删除项');
        }
        return $value;
    }
    
    //处理排序
    public function order(){
        $menu = new NavMenuMx();
        $data = input('post.');
        if(!empty($data['sort'])){
            foreach ($data['sort'] as $key => $v){
                $data2['sort'] = $v;
                $data2['id'] = $key;
                $menu->save($data2,array('id'=>$data2['id']));
            }
            $value = array('status'=>1,'mess'=>'更新排序成功');
        }else{
            $value = array('status'=>0,'mess'=>'未修改任何排序');
        }
        return $value;
    }
    
    public function uploadImage() {
        $image = !empty($_FILES['icon_image']) ? $_FILES['icon_image'] : '';
        if (!$image) {
            return json(array('status' => Lookup::isClose, 'mess' => '没有图片文件', 'data' => ''));
        }
        $uploadCos = new CosFileUpload();
        $verify = $uploadCos->imageVerify($image);
        if (!$verify['status']) {
            return json(array('status' => Lookup::isClose, 'mess' => $verify['mess'], 'data' => ''));
        }
        $dir = Lookup::iconImage;
        $key = $uploadCos->getFileKey($verify['data'], $dir);
        $upload = $uploadCos->uploadFileCos($image['tmp_name'], $key);
        if (!$upload['status']) {
            return json(array('status' => Lookup::isClose, 'mess' => $upload['mess'], 'data' => ''));
        }
        $image_path = $upload['data']['path'];
        $image_url = $upload['data']['cos_domain'] . $image_path;
        $data = array('image_path' => $image_path, 'image_url' => $image_url);
        return json(array('status' => Lookup::isOpen, 'mess' => '上传成功', 'data' => $data));
    }
    
    public function uploadImageBanner() {
        $image = !empty($_FILES['banner_image']) ? $_FILES['banner_image'] : '';
        if (!$image) {
            return json(array('status' => Lookup::isClose, 'mess' => '没有图片文件', 'data' => ''));
        }
        $uploadCos = new CosFileUpload();
        $verify = $uploadCos->imageVerify($image);
        if (!$verify['status']) {
            return json(array('status' => Lookup::isClose, 'mess' => $verify['mess'], 'data' => ''));
        }
        $dir = Lookup::bannerImage;
        $key = $uploadCos->getFileKey($verify['data'], $dir);
        $upload = $uploadCos->uploadFileCos($image['tmp_name'], $key);
        if (!$upload['status']) {
            return json(array('status' => Lookup::isClose, 'mess' => $upload['mess'], 'data' => ''));
        }
        $image_path = $upload['data']['path'];
        $image_url = $upload['data']['cos_domain'] . $image_path;
        $data = array('image_path' => $image_path, 'image_url' => $image_url);
        return json(array('status' => Lookup::isOpen, 'mess' => '上传成功', 'data' => $data));
    }
       
}
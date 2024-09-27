<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\News as NewsMx;

class News extends Common{
    //文章列表
    public function lst(){
        $list = Db::name('news')->alias('a')->field('a.id,a.ar_title,a.tag,a.is_rem,a.is_show,a.addtime,a.sort,b.cate_name,c.en_name')->join('sp_cate_new b','a.cate_id = b.id','LEFT')->join('sp_admin c','a.aid = c.id','LEFT')->order('a.sort asc')->paginate(25);
        $page = $list->render();
        $cateres = Db::name('cate_new')->field('id,cate_name,pid')->order('sort asc')->select();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $this->assign(array(
           'pnum'=>$pnum,
           'cateres'=> recursive($cateres),
           'list'=>$list,
           'page'=>$page
        ));
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch();
        }
    }
       
    //根据栏目分类获取文章列表
    public function catelist(){
        $id = input('cate_id');
        $cate_name = Db::name('cate_new')->where('id',$id)->value('cate_name');
        $cateres = Db::name('cate_new')->field('id,cate_name,pid')->order('sort asc')->select();
        $cateId = array();
        $cateId = get_all_child($cateres, $id);
        $cateId[] = $id;
        $cateId = implode(',', $cateId);
        $list = Db::name('news')->alias('a')->field('a.id,a.ar_title,a.is_rem,a.is_show,a.addtime,a.sort,b.cate_name,c.en_name')->join('sp_cate_new b','a.cate_id = b.id','LEFT')->join('sp_admin c','a.aid = c.id','LEFT')->where('a.cate_id','in',$cateId)->order('a.sort asc')->paginate(25);
        $page = $list->render();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $this->assign(array(
            'cate_id'=>$id,
            'cate_name'=>$cate_name,
            'pnum'=>$pnum,
            'cateres'=>recursive($cateres),
            'list'=>$list,
            'page'=>$page
        ));
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }
    
    //修改文章推荐
    public function gaibian(){
        $id = input('post.id');
        $name = input('post.name');
        $value = input('post.value');
        $data[$name] = $value;
        $data['id'] = $id;
        $news = new NewsMx();
        $count = $news->save($data,array('id'=>$data['id']));
        if($count > 0){
            $result = 1;
        }else{
            $result = 0;
        }
        return $result;
    }
    
    //添加文章视图
    public function add(){
        if(request()->isAjax()){
            $data = input('post.');
            $lang = db('lang')->select();
            foreach ($lang as $k => $v){
                if(!empty($data['ar_title_'.$v['lang_code']])){
                    $data['ar_title'] = $data['ar_title_'.$v['lang_code']];
                    break;
                }
            }
            foreach ($lang as $k => $v){
                if(!empty($data['ar_content_'.$v['lang_code']])){
                    $data['ar_content'] = $data['ar_content_'.$v['lang_code']];
                    break;
                }

            }
            $result = $this->validate($data,'News');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $data['ar_pic'] = $data['pic_id'];
                if(!empty($data['addtime'])){
                    $data['addtime'] = strtotime($data['addtime']);
                }else{
                    $data['addtime'] = time();
                }
                $data['aid'] = session('admin_id');

                $news = new NewsMx();
                $news->data($data);
                $lastId = $news->allowField(true)->save();

                $newsLangDb = db('news_lang');
                $langs = db('lang')->select();
                foreach ($langs as $k => $v) {
                    $newsLangData = [];
                    $newsLangData['news_id'] = $news->id;
                    $newsLangData['lang_id']  = $v['id'];
                    $newsLangData['ar_title'] = $data['ar_title_'. $v['lang_code']];
                    $newsLangData['ar_content'] = $data['ar_content_' . $v['lang_code']];
                    $newsLangDb->insertGetId($newsLangData);

                }
                if($lastId){
                    $value = array('status'=>1,'mess'=>'增加成功');
                }else{
                    $value = array('status'=>0,'mess'=>'增加失败');
                }
            }
            return json($value);
        }else{
            $cateres = Db::name('cate_new')->field('id,pid,cate_name')->order('sort asc')->select();
            $langs      = Db::name('lang')->order('id asc')->select();
            $this->assign('langs', $langs);
            $this->assign('cateres',recursive($cateres));
            if(input('cate_id')){
                $this->assign('cate_id',input('cate_id'));
            }
            return $this->fetch();
        }
    }
    
    //编辑文章视图
    public function edit(){
        if(request()->isAjax()){
            $data = input('post.');
            $admin_id = session('admin_id');
            $lang = db('lang')->select();
            foreach ($lang as $k => $v){
                if(!empty($data['ar_title_'.$v['lang_code']])){
                    $data['ar_title'] = $data['ar_title_'.$v['lang_code']];
                    break;
                }
            }
            foreach ($lang as $k => $v){
                if(!empty($data['ar_content_'.$v['lang_code']])){
                    $data['ar_content'] = $data['ar_content_'.$v['lang_code']];
                    break;
                }

            }
            $result = $this->validate($data,'News');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $ars = Db::name('news')->where('id',$data['id'])->find();
                if($ars){
                    if(!empty($data['pic_id'])){
                        $data['ar_pic'] = $data['pic_id'];
                    }else{
                        $data['ar_pic'] = $ars['ar_pic'];
                    }
                   
                    if(!empty($data['addtime'])){
                        $data['addtime'] = strtotime($data['addtime']);
                    }else{
                        $data['addtime'] = time();
                    }

                    $news = new NewsMx();
                    $count = $news->allowField(true)->save($data,array('id'=>$data['id']));

                    $newsLangDb = db('news_lang');
                    $langs = db('lang')->select();
                    foreach ($langs as $k => $v) {
                        $newsLangData = [];
                        $hasNewsLang = $newsLangDb->where(['news_id'=>$data['id'],'lang_id'=>$v['id']])->find();
                        $newsLangData['news_id'] = $news->id;
                        $newsLangData['lang_id']  = $v['id'];
                        $newsLangData['ar_title'] = $data['ar_title_'. $v['lang_code']];
                        $newsLangData['ar_content'] = $data['ar_content_' . $v['lang_code']];
                        // 判断商品多语言设置是否存在，没有就新增，存在就更新
                        if($hasNewsLang){
                            $newsLangDb->where(['news_id'=>$data['id'],'lang_id'=>$v['id']])->update($newsLangData);
                        }else{
                            $newsLangDb->insertGetId($newsLangData);
                        }
                    }
                    if($count !== false){
                        $value = array('status'=>1,'mess'=>'编辑成功');
                    }else{
                        $value = array('status'=>0,'mess'=>'编辑失败');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'找不到相关信息，编辑失败');
                }
            }
            return json($value);
        }else{
            $id = input('id');
            $cateres = Db::name('cate_new')->field('id,pid,cate_name')->order('sort asc')->select();
            $ars = Db::name('news')->find($id);
            $langs      = Db::name('lang')->order('id asc')->select();
            $newsLangs = Db::name('news_lang')->where('news_id',$id)->select();
            $this->assign('newsLangs', $newsLangs);
            $this->assign('langs', $langs);
            $this->assign('pnum', input('page'));
            if(input('s')){
                $this->assign('search', input('s'));
            }
            if(input('cate_id')){
                $this->assign('cate_id', input('cate_id'));
            }
            $this->assign('cateres',recursive($cateres));
            $this->assign('ars',$ars);
            return $this->fetch();
        }
    }

    public function delete(){
        if(input('post.id')){
            $id = array_filter(explode(',', input('post.id')));
        }else{
            $id = input('id');
        }
        if(!empty($id)){
            if(is_array($id)){
                $delId = implode(',', $id);
                $pic = Db::name('news')->where('id','in',$delId)->field('ar_pic')->order('addtime desc')->select();
            }else{
                $pic =  Db::name('news')->where('id',$id)->value('ar_pic');
            }
            $count = Db::name('news')->delete($id);
            if($count > 0){
                if(is_array($id)){
                    if(!empty($pic)){
                        foreach ($pic as $v){
                            if(!empty($v['ar_pic']) && file_exists('./'.$v['ar_pic'])){
                                @unlink('./'.$v['ar_pic']);
                            }
                        }
                    }
                }else{
                    if(!empty($pic) && file_exists('./'.$pic)){
                        @unlink('./'.$pic);
                    }
                }
                $value = array('status'=>1,'mess'=>'删除成功');
            }else{
                $value = array('status'=>0,'mess'=>'删除失败');
            }
        }else{
            $value = array('status'=>0,'mess'=>'请选择删除项');
        }
        return json($value);
    }
    
    //搜索
    public function search(){
        if(input('post.keyword') != ''){
            cookie('ar_title',input('post.keyword'),3600);
        }else{
            cookie('ar_title',null);
        }
        
        if(input('post.cate_id') != ''){
            cookie('ar_cate_id',input('post.cate_id'),3600);
        }
        
        $cateres = Db::name('cate_new')->field('id,cate_name,pid')->order('sort asc')->select();
        $where = array();
        
        if(cookie('ar_title')){
            $where['a.ar_title'] = array('like','%'.cookie('ar_title').'%');
        }

        if(cookie('ar_cate_id') != ''){
            $cate_id = (int)cookie('ar_cate_id');
            if($cate_id != 0){
                $cateId = array();
                $cateId = get_all_child($cateres, $cate_id);
                $cateId[] = $cate_id;
                $cateId = implode(',', $cateId);
                $where['a.cate_id'] = array('in',$cateId);
            }
        }
        $list = Db::name('news')->alias('a')->field('a.id,a.ar_title,a.is_rem,a.is_show,a.addtime,a.sort,b.cate_name,c.en_name')->join('sp_cate_new b','a.cate_id = b.id','LEFT')->join('sp_admin c','a.aid = c.id','LEFT')->where($where)->order('a.sort asc')->paginate(25);
        $page = $list->render();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $search = 1;
        if(cookie('ar_title')){
            $this->assign('ar_title',cookie('ar_title'));
        }
        $this->assign('search',$search);
        $this->assign('pnum', $pnum);
        $this->assign('cate_id', $cate_id);
        $this->assign('cateres',recursive($cateres));
        $this->assign('list', $list);// 赋值数据集
        $this->assign('page', $page);// 赋值分页输出
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }
    
    public function paixu(){
       if(request()->isAjax()){
            if(input('post.ids') && input('post.sort')){
                $ids = input('post.ids');
                $sort = input('post.sort');
                $ids = explode(',', $ids);
                $sort = explode(',', $sort);
                foreach ($ids as $k => $v){
                    Db::name('news')->update(array('id'=>$v,'sort'=>$sort[$k]));
                }
            }
            $value = array('status'=>1,'mess'=>'排序成功');
            return json($value);
        }
    }
 
}
<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class GetMember extends Common{
    //列表
    public function lst(){
        $where = [];
        $type = input('get.type');
        if(!empty($type)){
            if($type == 1){           //type=1查找未绑定商家的会员
                $where['shop_id'] = 0;
            }
        }
        $where['checked']=1;
        $list = Db::name('member')->where($where)->field('id,user_name,phone,headimgurl')->order('regtime desc')->paginate(10);

        $page = $list->render();
        
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        
        $this->assign(array(
            'pnum'=>$pnum,
            'list'=>$list,
            'page'=>$page,
            'type'=>$type
        ));
        
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch();
        }
    }
    
    //搜索
    public function search(){
        $where = [];
        $type = input('post.type');
        if(!empty($type)){
            if($type == 1){           //type=1查找未绑定商家的会员
                $where['shop_id'] = 0;
            }
        }

        if(input('post.keyword') != ''){
            cookie('cpmember_name',input('post.keyword'),3600);
        }else{
            cookie('cpmember_name',null);
        }

        $where['checked']=1;
        if(cookie('cpmember_name')){
            $keyword = cookie('cpmember_name');
            $where['phone|user_name'] = ['like', "%{$keyword}%"];
        }

        $list = Db::name('member')->where($where)->field('id,user_name,phone,headimgurl')->order('regtime desc')->paginate(10);

        $page = $list->render();

        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }

        
        $search = 1;
        
        if(cookie('cpmember_name')){
            $this->assign('keyword',cookie('cpmember_name'));
        }

        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('pnum', $pnum);
        $this->assign('search',$search);
        $this->assign('type',$type);
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }
 
}

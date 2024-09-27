<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class Shops extends Common{
    
    public function lst(){
        $list = Db::name('shops')->alias('a')->field('a.id,a.shop_name,a.contacts,a.telephone,a.open_status,a.addtime,a.agent_id,b.price,c.industry_name,d.pro_name,f.city_name,u.area_name,g.user_id')->join('sp_shop_wallet b','a.id = b.shop_id','LEFT')->join('sp_industry c','a.indus_id = c.id','LEFT')->join('sp_province d','a.pro_id = d.id','LEFT')->join('sp_city f','a.city_id = f.id','LEFT')->join('sp_area u','a.area_id = u.id','LEFT')->join('sp_agent g','a.agent_id = g.id','LEFT')->where('a.id','neq',1)->order('a.addtime desc')->paginate(25)->each(function($item,$k){
            if($item['user_id']){
                $item['user_name'] = db('member')->where('id',$item['user_id'])->value('user_name');
            }
            return $item;
        });
        $count = Db::name('shops')->where('id','neq',1)->count();
        $page = $list->render();
    
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
    
        $prores = Db::name('province')->field('id,pro_name,zm')->order('sort asc')->select();
        $industryres = Db::name('industry')->where('is_show',1)->field('id,industry_name')->order('sort asc')->select();
        $this->assign(array(
            'list'=>$list,
            'count'=>$count,
            'page'=>$page,
            'pnum'=>$pnum,
            'prores'=>$prores,
            'industryres'=>$industryres
        ));
        
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }
    
    //修改状态
    public function gaibian(){
        $id = input('post.id');
        $name = input('post.name');
        $value = input('post.value');
        $data[$name] = $value;
        $data['id'] = $id;
        $count = Db::name('shops')->update($data);
        if($count > 0){
            ys_admin_logs('改变商家开启或关闭状态','shops',$id);
            $result = 1;
        }else{
            $result = 0;
        }
        return $result;
    }
    
    public function getcitylist(){
        if(request()->isPost()){
            $pro_id = input('post.pro_id');
            if($pro_id){
                $cityres = Db::name('city')->where('pro_id',$pro_id)->field('id,city_name,zm')->order('sort asc')->select();
                if(empty($cityres)){
                    $cityres = 0;
                }
                return $cityres;
            }
        }
    }
    
    public function getarealist(){
        if(request()->isPost()){
            $city_id = input('post.city_id');
            if($city_id){
                $areares = Db::name('area')->where('city_id',$city_id)->field('id,area_name,zm')->order('sort asc')->select();
                if(empty($areares)){
                    $areares = 0;
                }
                return $areares;
            }
        }
    }
    
    
    public function info(){
        if(input('shop_id')){
            $id = input('shop_id');
            if($id != 1){
                $shops = Db::name('shops')->alias('a')->field('a.*,b.price,c.industry_name,c.remind,d.pro_name,f.city_name,u.area_name,g.user_id')->join('sp_shop_wallet b','a.id = b.shop_id','LEFT')->join('sp_industry c','a.indus_id = c.id','LEFT')->join('sp_province d','a.pro_id = d.id','LEFT')->join('sp_city f','a.city_id = f.id','LEFT')->join('sp_area u','a.area_id = u.id','LEFT')->join('sp_agent g','a.agent_id = g.id','LEFT')->where('a.id',$id)->find();
                if($shops['user_id']){
                    $shops['user_name'] = db('member')->where('id',$shops['user_id'])->value('user_name');
                }
                $applys = Db::name('apply_info')->alias('a')->field('a.*,b.industry_name,c.pro_name,d.city_name,u.area_name')->join('sp_industry b','a.indus_id = b.id','LEFT')->join('sp_province c','a.pro_id = c.id','LEFT')->join('sp_city d','a.city_id = d.id','LEFT')->join('sp_area u','a.area_id = u.id','LEFT')->where('a.shop_id',$id)->where('a.checked',1)->find();
                $rz_orders = Db::name('rz_order')->alias('a')->field('a.*,b.industry_name,b.ser_price,b.remind')->join('sp_industry b','a.indus_id = b.id','LEFT')->where('a.apply_id',$applys['id'])->where('a.shop_id',$id)->where('a.state',1)->find();
                if($shops && $applys){

                    //判断平台是否安装了商家等级插件
                    $plugin = ['install'=>0,"level_name"=>""];
                    $plugin_shoplevel = Db::name("plugin")->where(["name"=>"shoplevel","status"=>1,"isclose"=>1])->count();
                    if($plugin_shoplevel){
                        $plugin['install'] = 1;
                        $level_name = Db::name("plugin_shoplevel_user")->where(["shop_id"=>$id,"status"=>1])->order("shoplevel_id","DESC")->value("level_name");
                        $plugin['level_name'] = $level_name ? $level_name : "默认等级";
                    }
                    $this->assign("plugin",$plugin);


                    if(input('s')){
                        $this->assign('search',input('s'));
                    }
                    $this->assign('shops',$shops);
                    $this->assign('applys',$applys);
                    $this->assign('rz_orders',$rz_orders);
                    return $this->fetch();
                }else{
                    $this->error('参数错误');
                }
            }else{
                $this->error('缺少参数');
            }
        }else{
            $this->error('缺少参数');
        }
    }

    public function edit(){
        if(request()->isPost()){
            $data = input('post.');
            $admin_id = session('admin_id');
            $result = $this->validate($data,'Shops');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $shops = Db::name('shops')->where('id',$data['id'])->field('id,logo')->find();
                if($shops){
                    $pro_id = $data['pro_id'];
                    $city_id = $data['city_id'];
                    $area_id = $data['area_id'];
                    $pros = Db::name('province')->where('id',$pro_id)->where('checked',1)->where('pro_zs',1)->field('id,pro_name')->find();
                    if($pros){
                        $citys = Db::name('city')->where('id',$city_id)->where('pro_id',$pros['id'])->where('checked',1)->where('city_zs',1)->field('id,city_name')->find();
                        if($citys){
                            $areas = Db::name('area')->where('id',$area_id)->where('city_id',$citys['id'])->where('checked',1)->field('id,area_name')->find();
                            if($areas){
                                $data['latlon'] = str_replace('，', ',', $data['latlon']);
                                if(strpos($data['latlon'],',') !== false){

                                    $latlon = explode(',', $data['latlon']);

                                    if(!empty($data['pic_id'])){
                                        $data['logo'] = $data['pic_id'];
                                    }else{
                                        if(!empty($shops['logo'])){
                                            $data['logo'] = $shops['logo'];
                                        }else{
                                            $data['logo'] = '';
                                        }
                                    }
                                    if(empty($data['wxnum'])){
                                        $data['wxnum'] = '';
                                    }

                                    if(empty($data['qqnum'])){
                                        $data['qqnum'] = '';
                                    }

                                    if(empty($data['sertime'])){
                                        $data['sertime'] = '';
                                    }

                                    $data['search_keywords'] = str_replace('，', ',', $data['search_keywords']);
                                    $count = Db::name('shops')->update(array(
                                        'shop_name'=>$data['shop_name'],
                                        'indus_id'=>$data['indus_id'],
                                        'shop_desc'=>$data['shop_desc'],
                                        'search_keywords'=>$data['search_keywords'],
                                        'contacts'=>$data['contacts'],
                                        'telephone'=>$data['telephone'],
                                        'wxnum'=>$data['wxnum'],
                                        'qqnum'=>$data['qqnum'],
                                        'logo'=>$data['logo'],
                                        'sertime'=>$data['sertime'],
                                        'pro_id'=>$data['pro_id'],
                                        'city_id'=>$data['city_id'],
                                        'area_id'=>$data['area_id'],
                                        'address'=>$data['address'],
                                        'lng'=>$latlon[0],
                                        'lat'=>$latlon[1],
                                        'fenxiao'=>$data['fenxiao'],
                                        'open_status'=>$data['open_status'],
                                        'agent_id'=>$data['agent_id'],
                                        'id'=>$data[id]
                                    ));
                                    if($count !== false){
                                        $value = array('status'=>1,'mess'=>'保存信息成功');
                                    }else{
                                        $value = array('status'=>0,'mess'=>'保存信息失败');
                                    }
                                }else{
                                    $value = array('status'=>0,'mess'=>'地址坐标参数错误');
                                }
                            }else{
                                $value = array('status'=>0,'mess'=>'请选择区域，操作失败');
                            }
                        }else{
                            $value = array('status'=>0,'mess'=>'请选择区域，操作失败');
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'请选择区域，操作失败');
                    }
                }else{
                    $value = array('status'=>0,'mess'=>'找不到相关商店信息');
                }
            }
            return json($value);
        }else{
            if(input('shop_id')){
                $id = input('shop_id');

                if($id != 1){
                    $shops = Db::name('shops')->alias('a')->field('a.*,b.price,c.industry_name,c.remind,d.pro_name,f.city_name,u.area_name,g.user_id')->join('sp_shop_wallet b','a.id = b.shop_id','LEFT')->join('sp_industry c','a.indus_id = c.id','LEFT')->join('sp_province d','a.pro_id = d.id','LEFT')->join('sp_city f','a.city_id = f.id','LEFT')->join('sp_area u','a.area_id = u.id','LEFT')->join('sp_agent g','a.agent_id = g.id','LEFT')->where('a.id',$id)->find();
                    if($shops['user_id']){
                        $shops['user_info'] = db('member')->where('id',$shops['user_id'])->find();
                    }
                    if($shops){
                        if(input('s')){
                            $this->assign('search',input('s'));
                        }
                        $industryres = Db::name('industry')->where('is_show',1)->field('id,industry_name')->order('sort asc')->select();
                        $prores = Db::name('province')->where('checked',1)->where('pro_zs',1)->field('id,pro_name,zm')->order('sort asc')->select();
                        $cityres = Db::name('city')->where('pro_id',$shops['pro_id'])->field('id,city_name,zm')->order('sort asc')->select();
                        $areares = Db::name('area')->where('city_id',$shops['city_id'])->field('id,area_name,zm')->select();
                        $agents = db('agent')->alias('a')->where('a.checked',1)->field('a.*,b.user_name')->join('member b','a.user_id = b.id')->select();
                        $this->assign('pnum', input('page'));
                        $this->assign('prores',$prores);
                        $this->assign('cityres',$cityres);
                        $this->assign('areares',$areares);
                        $this->assign('industryres',$industryres);
                        $this->assign('agents',$agents);
                        $this->assign('shops',$shops);
                        return $this->fetch();
                    }else{
                        $this->error('参数错误');
                    }
                }else{
                    $this->error('缺少参数');
                }
            }else{
                $this->error('缺少参数');
            }
        }

    }


    public function search(){
        if(input('post.keyword') != ''){
            cookie('shops_keyword',input('post.keyword'),7200);
        }else{
            cookie('shops_keyword',null);
        }
    
        if(input('post.search_type') != ''){
            cookie('shops_type',input('post.search_type'),7200);
        }
        
        if(input('post.indus_id') != ''){
            cookie('shops_indus_id',input('post.indus_id'),3600);
        }
        
        if(input('post.starttime') != ''){
            $shopsstarttime = strtotime(input('post.starttime'));
            cookie('shopsstarttime',$shopsstarttime,3600);
        }
    
        if(input('post.endtime') != ''){
            $shopsendtime = strtotime(input('post.endtime'));
            cookie('shopsendtime',$shopsendtime,3600);
        }
    
        if(input('post.pro_id') != ''){
            cookie("shops_pro_id", input('post.pro_id'), 7200);
        }
    
        if(input('post.city_id') != ''){
            cookie("shops_city_id", input('post.city_id'), 7200);
        }
    
        if(input('post.area_id') != ''){
            cookie("shops_area_id", input('post.area_id'), 7200);
        }
    
        $where = array();

        $where['a.id'] = array('neq',1);
        
        if(cookie('shops_pro_id') != ''){
            $proid = (int)cookie('shops_pro_id');
            if($proid != 0){
                $where['a.pro_id'] = $proid;
            }
        }
    
        if(cookie('shops_city_id') != ''){
            $cityid = (int)cookie('shops_city_id');
            if($cityid != 0){
                $where['a.city_id'] = $cityid;
            }
        }
    
        if(cookie('shops_area_id') != ''){
            $areaid = (int)cookie('shops_area_id');
            if($areaid != 0){
                $where['a.area_id'] = $areaid;
            }
        }
        
        if(cookie('shops_indus_id') != ''){
            //(int)将cookie字符串强制转换成整型
            $indus_id = (int)cookie('shops_indus_id');
            if($indus_id != 0){
                $where['a.indus_id'] = $indus_id;
            }
        }
    
        if(cookie('shops_type')){
            if(cookie('shops_type') == 1 && cookie('shops_keyword')){
                $where['a.shop_name'] = array('like', '%' . cookie('shops_keyword') . '%');
            }elseif(cookie('shops_type') == 2 && cookie('shops_keyword')){
                $where['a.telephone'] = array('like', '%' . cookie('shops_keyword') . '%');
            }
        }
    
        if(cookie('shopsendtime') && cookie('shopsstarttime')){
            $where['a.addtime'] = array(array('egt',cookie('shopsstarttime')), array('lt',cookie('shopsendtime')));
        }
    
        if(cookie('shopsstarttime') && !cookie('shopsendtime')){
            $where['a.addtime'] = array('egt',cookie('shopsstarttime'));
        }
    
        if(cookie('shopsendtime') && !cookie('shopsstarttime')){
            $where['a.addtime'] = array('lt',cookie('shopsendtime'));
        }
    
    
        $list = Db::name('shops')->alias('a')->field('a.id,a.shop_name,a.contacts,a.telephone,a.open_status,a.addtime,b.price,c.industry_name,d.pro_name,f.city_name,u.area_name')->join('sp_shop_wallet b','a.id = b.shop_id','LEFT')->join('sp_industry c','a.indus_id = c.id','LEFT')->join('sp_province d','a.pro_id = d.id','LEFT')->join('sp_city f','a.city_id = f.id','LEFT')->join('sp_area u','a.area_id = u.id','LEFT')->where($where)->order('a.addtime desc')->paginate(25);
        $count = Db::name('shops')->alias('a')->field('a.id,a.shop_name,a.contacts,a.telephone,a.open_status,a.addtime,b.price,c.industry_name,d.pro_name,f.city_name,u.area_name')->join('sp_shop_wallet b','a.id = b.shop_id','LEFT')->join('sp_industry c','a.indus_id = c.id','LEFT')->join('sp_province d','a.pro_id = d.id','LEFT')->join('sp_city f','a.city_id = f.id','LEFT')->join('sp_area u','a.area_id = u.id','LEFT')->where($where)->order('a.addtime desc')->count();
        
        $page = $list->render();
    
        $prores = Db::name('province')->field('id,pro_name,zm')->order('sort asc')->select();
        
        $industryres = Db::name('industry')->where('is_show',1)->field('id,industry_name')->order('sort asc')->select();
    
        if(cookie('shops_pro_id')){
            $cityres = Db::name('city')->where('pro_id',cookie('shops_pro_id'))->field('id,city_name,zm')->order('sort asc')->select();
        }
    
        if(cookie('shops_pro_id') && cookie('shops_city_id')){
            $areares = Db::name('area')->where('city_id',cookie('shops_city_id'))->field('id,area_name,zm')->select();
        }
    
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $search = 1;
    
        if(cookie('shops_keyword') != ''){
            $this->assign('keyword',cookie('shops_keyword'));
        }
    
        if(cookie('shops_type') != ''){
            $this->assign('search_type',cookie('shops_type'));
        }

        if(cookie('shopsstarttime') != ''){
            $this->assign('starttime',cookie('shopsstarttime'));
        }
    
        if(cookie('shopsendtime') != ''){
            $this->assign('endtime',cookie('shopsendtime'));
        }
    
        if(cookie('shops_pro_id') != ''){
            $this->assign('pro_id',cookie('shops_pro_id'));
        }
        if(cookie('shops_city_id') != ''){
            $this->assign('city_id',cookie('shops_city_id'));
        }
        if(cookie('shops_area_id') != ''){
            $this->assign('area_id',cookie('shops_area_id'));
        }
    
        if(!empty($cityres)){
            $this->assign('cityres',$cityres);
        }
    
        if(!empty($areares)){
            $this->assign('areares',$areares);
        }
        $this->assign('indus_id', $indus_id);
        $this->assign('search',$search);
        $this->assign('pnum', $pnum);
        $this->assign('prores',$prores);
        $this->assign('industryres',$industryres);
        $this->assign('list', $list);// 赋值数据集
        $this->assign('page', $page);// 赋值分页输出
        $this->assign('count',$count);
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }

}

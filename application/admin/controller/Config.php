<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\common\model\Config as ConfigMx;
use Overtrue\Pinyin\Pinyin;
use think\Cache;

class Config extends Common{
    //系统配置列表
    public function lst(){
        $list = Db::name('config')->alias('a')->field('a.*,b.ca_name')->join('sp_cation b','a.ca_id = b.id','LEFT')->order('id desc')->paginate(20);
        $page = $list->render();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $this->assign(array(
            'pnum'=>$pnum,
            'page'=>$page,
            'list'=>$list
        ));
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }    

    public function config(){
        if(request()->isAjax()){
            $pz = input('post.');
            $ca_id = $pz['ca_id'];
            $_enameres = Db::name('config')->where('ca_id',$ca_id)->field('ename')->select();
            $enameres = array();
            $postename = array();
            foreach ($_enameres as $val){
                $enameres[] = $val['ename'];
            }
            foreach ($pz as $key2 => $val2){
                $postename[] = $key2;
            }
            foreach ($enameres as $val3){
                if(!in_array($val3,$postename)){
                    Db::name('config')->where('ename',$val3)->update(array('value'=>''));
                }
            }
            unset($pz['ca_id']);
            foreach($pz as $k => $v){
                Db::name('config')->where(array('ca_id'=>$ca_id,'ename'=>$k))->update(array('value'=>$v));
            }
            //清除所以缓存
            Cache::clear();
            ys_admin_logs('修改系统参数','config','');
            $value = array('status'=>1, 'mess'=>'保存成功');
            return json($value);
        }else{
            $cationres = Db::name('cation')->order('sort asc')->select();
            foreach ($cationres as $key => $val){
                $cationres[$key]['configres'] = Db::name('config')->where('ca_id',$val['id'])->order('sort ASC,id ASC')->select();
            }
            $this->assign('cationres',$cationres);
            return $this->fetch();
        }
    }

    //检索配置名称是否存在
    public function checkCname(){
        if(request()->isAjax()){
            $arr = Db::name('config')->where('cname',input('post.cname'))->find();
            if($arr){
                echo 'false';
            }else{
                echo 'true';
            }
        }else{
            $this->error('非法请求');
        }
    }

    public function checkEname(){
        if(request()->isAjax()){
            $arr = Db::name('config')->where('ename',input('post.ename'))->find();
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
            $result = $this->validate($data,'Config');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                if(!in_array($data['type'], array(0,1,5))){
                    if(empty($data['values'])){
                        $value = array('status'=>0,'mess'=>'配置可选值不能为空');
                    }else{
                        $data['values'] = str_replace('，', ',', $data['values']);
                        $config = new ConfigMx();
                        $config->data($data);
                        $lastId = $config->allowField(true)->save();
                        if($lastId){
                            $value = array('status'=>1,'mess'=>'增加成功');
                        }else{
                            $value = array('status'=>0,'mess'=>'增加失败');
                        }
                    }
                }else{
                    $data['values'] = '';
                    $config = new ConfigMx();
                    $config->data($data);
                    $lastId = $config->allowField(true)->save();
                    if($lastId){
                        $value = array('status'=>1,'mess'=>'增加成功');
                    }else{
                        $value = array('status'=>0,'mess'=>'增加失败');
                    }
                }
            }
            return json($value);
        }else{
            $cationres = Db::name('cation')->order('sort asc')->select();
            $this->assign('cationres',$cationres);
            return $this->fetch();
        }
    }

    public function edit(){
        if(request()->isAjax()){
            if(input('post.id')){
                $data = input('post.');
                $result = $this->validate($data,'Config');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    $pezhis = Db::name('config')->where('id',$data['id'])->find();
                    if($pezhis){
                        if(!in_array($data['type'], array(0,1,5))){
                            if(empty($data['values'])){
                                $value = array('status'=>0,'mess'=>'配置可选值不能为空');
                            }else{
                                $data['values'] = str_replace('，', ',', $data['values']);
                                $config = new ConfigMx();
                                $count = $config->allowField(true)->save($data,array('id'=>$data['id']));
                                if($count !== false){
                                    $value = array('status'=>1,'mess'=>'编辑成功');
                                }else{
                                    $value = array('status'=>0,'mess'=>'编辑失败');
                                }
                            }
                        }else{
                            $data['values'] = '';
                            $config = new ConfigMx();
                            $count = $config->allowField(true)->save($data,array('id'=>$data['id']));
                            if($count !== false){
                                $value = array('status'=>1,'mess'=>'编辑成功');
                            }else{
                                $value = array('status'=>0,'mess'=>'编辑失败');
                            }
                        }
                    }else{
                        $value = array('status'=>0,'mess'=>'找不到相关信息');
                    }
                }
            }else{
                $value = array('status'=>0,'mess'=>'缺少参数，编辑失败');
            }
            return json($value);
        }else{
            if(input('id')){
                $id = input('id');
                $configs = Db::name('config')->where('id',$id)->find();
                if($configs){
                    $cationres = Db::name('cation')->order('sort asc')->select();
                    if(input('s')){
                        $this->assign('search', input('s'));
                    }
                    $this->assign('pnum', input('page'));
                    $this->assign('configs',$configs);
                    $this->assign('cationres',$cationres);
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
        if(input('post.id')){
            $id = array_filter(explode(',', input('post.id')));
        }else{
            $id = input('id');
        }
        if(!empty($id)){
            $count = ConfigMx::destroy($id);
            if($count > 0){
                $value = array('status'=>1,'mess'=>'删除成功');
            }else{
                $value = array('status'=>0,'mess'=>'编辑失败');
            }
        }else{
            $value = array('status'=>0,'mess'=>'请选择删除项');
        }
        return json($value);
    }

    
    public function search(){
        if(input('post.keyword') != ''){
            cookie('cname',input('post.keyword'),3600);
        }
        $where = array();
        if(cookie('cname')){
            $where['a.cname|a.ename'] = array('like','%'.cookie('cname').'%');
        }  
        $list = $list = Db::name('config')->alias('a')->field('a.*,b.ca_name')->join('sp_cation b','a.ca_id = b.id','LEFT')->where($where)->order('id desc')->paginate(20);
        $page = $list->render();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $search = 1;
        if(cookie('cname')){
            $this->assign('cname',cookie('cname'));
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
    
    public function uploadPostersBg() {
        $file = request()->file('bgimage');
        if (!$file) {
            return json(array('status' => 0, 'mess' => '没有图片文件', 'data' => ''));
        }
        $info = $file->validate(['size'=>3145728,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'share' . DS . 'bgimage');
        if (!$info) {
            return json(array('status' => 0, 'mess' => '上传失败', 'data' => ''));
        }
        $image_name = $info->getSaveName();
        $image_path = 'uploads/share/bgimage/' . $image_name;
        $image_url = $this->webconfig['weburl'] . $image_path;
        $data = array('image_path' => $image_path, 'image_url' => $image_url);
        return json(array('status' => 1, 'mess' => '上传成功', 'data' => $data));
    }

    // 同步省份、城市、区域数据
    public function syncArea(){
        $areas = get_areas();
        $pinyin = new Pinyin();

        foreach ($areas['province'] as $v){
            $provinceDate['pro_name'] = $v['@attributes']['name'];
            $provinceDate['code'] = $v['@attributes']['code'];
            $provinceDate['zm'] = strtoupper(substr($pinyin->abbr($v['@attributes']['name']),0,1));;
            $provinceDate['sort'] = 0;
            $provinceDate['pro_zs'] = 1;
            $provinceDate['checked'] = 1;
            $getProvinceId = Db::name('province')->insertGetId($provinceDate);
            dump('省份---'.$getProvinceId);
            dump( $provinceDate['pro_name'].'-'.$provinceDate['zm']);
            if($getProvinceId){
                foreach ($v['city'] as $c){
                    $cityData['city_name'] = $c['@attributes']['name'];
                    $cityData['code'] = $c['@attributes']['code'];
                    $cityData['zm'] = strtoupper(substr($pinyin->abbr($c['@attributes']['name']),0,1));;
                    $cityData['fee_price'] = 0;
                    $cityData['is_hot'] = 0;
                    $cityData['pro_id'] = $getProvinceId;
                    $cityData['base_id'] = 1;
                    $cityData['sort'] = 0;
                    $cityData['city_zs'] = 1;
                    $cityData['checked'] = 1;
                    $getCityId = Db::name('city')->insertGetId($cityData);
                    dump('城市---'.$getCityId);
                    dump($cityData['city_name'].'-'.$cityData['zm']);
                    if($getCityId){
                        foreach ($c['county'] as $a){
                            $areaDate['area_name'] = $a['@attributes']['name'];
                            $areaDate['code'] = $a['@attributes']['code'];
                            $areaDate['zm'] = strtoupper(substr($pinyin->abbr($a['@attributes']['name']),0,1));;
                            $areaDate['city_id'] = $getCityId;
                            $areaDate['sort'] = 0;
                            $areaDate['checked'] = 1;
                            $getAreaId = Db::name('area')->insertGetId($areaDate);
                            dump('区域---'.$getAreaId);
                            dump($areaDate['area_name'].'-'.$areaDate['zm']);
                        }

                    }
                }
            }


        }

    }


}
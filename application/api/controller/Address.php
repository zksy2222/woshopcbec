<?php
namespace app\api\controller;
use app\api\controller\Common;
use app\api\model\Common as CommonModel;
use think\Db;

class Address extends Common{
    //会员地址列表
    public function index(){
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{
		    $userId = $tokenRes['user_id'];
	    }
        if(input('post.page') && preg_match("/^\\+?[1-9][0-9]*$/", input('post.page'))){
            $webconfig = $this->webconfig;
            $perpage = 20;
            $offset = (input('post.page')-1)*$perpage;
            $address = Db::name('address')
                         ->where('user_id',$userId)
                         ->order('addtime desc')
                         ->limit($offset,$perpage)
                         ->select();
	        datamsg(200, '获取地址信息成功', $address);
        }else{
	        datamsg(400, '缺少页数参数', array('status'=>400));
        }
    }
    
    //获取省份
    public function getpro(){
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess']);
	    }
        $prores = Db::name('province')->field('id,pro_name,zm,code')->where('checked',1)->where('pro_zs',1)->order('sort asc')->select();
        foreach ($prores as $k=>$v){
            $proData[$k+1] = $v;
        }
	    datamsg(200, '获取省份信息成功', $prores);
    }
    
    //获取城市
    public function getcity(){
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess']);
	    }
        if(input('post.pro_id')){
            $pro_id = input('post.pro_id');
            $cityres = Db::name('city')->where('pro_id',$pro_id)->where('checked',1)->where('city_zs',1)->field('id,city_name,zm,code')->order('sort asc, id asc')->select();
	        datamsg(200, '获取城市信息成功', $cityres);
        }else{
	        datamsg(400, '缺少省份参数', array('status'=>400));
        }
    }
    
    //获取区县
    public function getarea(){
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }
        if(input('post.city_id')){
            $city_id = input('post.city_id');
            $areares = Db::name('area')->where('city_id',$city_id)->where('checked',1)->field('id,area_name,zm,code')->order('sort asc, id asc')->select();
            datamsg(200,'获取区县信息成功',$areares);
        }else{
	        datamsg(400, '缺少城市参数', array('status'=>400));
        }
    }
    
    //添加地址
    public function add(){
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{
		    $userId = $tokenRes['user_id'];
	    }
        $data = input('post.');
        if(empty($data['is_default']) || !in_array($data['is_default'], array(0,1))){
            $data['is_default'] = 0;
        }
        $yzresult = $this->validate($data,'Address');
        if(true !== $yzresult){
	        datamsg(400, $yzresult, array('status'=>400));
        }

        // 启动事务
        Db::startTrans();
        try{
            $dz_id = Db::name('address')->insertGetId(array(
                'contacts'=>$data['contacts'],
                'phone'=>$data['phone'],
                'pro_id'=>$data['pro_id'],
                'city_id'=>$data['city_id'],
                'area_id'=>$data['area_id'],
                'province'=>$data['province'],
                'city'=>$data['city'],
                'area'=>$data['area'],
                'datavalue' => $data['datavalue'],
                'address'=>$data['address'],
                'user_id'=>$userId,
                'addtime'=>time(),
                'is_default'=>$data['is_default']
            ));
            if($dz_id && $data['is_default'] == 1){
                $dizhires = Db::name('address')->where('user_id',$userId)->where('is_default',1)->where('id','neq',$dz_id)->update(array('is_default'=>0));
            }
            // 提交事务
            Db::commit();
            datamsg(200,'增加地址成功',array('status'=>200));
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            datamsg(400,'增加地址失败',array('status'=>400));
        }
    }
    
    //获取单个地址信息
    public function getinfo(){
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{
		    $userId = $tokenRes['user_id'];
	    }
        if(input('post.dz_id')){
            $dz_id = input('post.dz_id');
            $address = Db::name('address')->where('id',$dz_id)->where('user_id',$userId)->field('id,contacts,phone,province,city,area,address,is_default')->find();
            if($address){
                $addressinfo = array('address'=>$address);
	            datamsg(200,'获取地址成功',$addressinfo);
            }else{
	            datamsg(400,'找不到相关地址信息',array('status'=>400));
            }
        }else{
	        datamsg(400,'缺少地址信息',array('status'=>400));
        }
    }
    
    //编辑经销商地址
    public function edit(){
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{
		    $userId = $tokenRes['user_id'];
	    }
        if(!input('post.dz_id')){
            datamsg(400, '缺少地址信息' ,array('status'=>400));
        }
        $data = input('post.');
        if(empty($data['is_default']) || !in_array($data['is_default'], array(0,1))){
            $data['is_default'] = 0;
        }
        $yzresult = $this->validate($data,'Address');
        if(true !== $yzresult){
            datamsg(400, $yzresult, array('status'=>400));
        }
        $addressinfo = Db::name('address')->where('id',$data['dz_id'])->where('user_id',$userId)->find();
        if(!$addressinfo){
            datamsg(400, '找不到相关地址信息' ,array('status'=>400));
        }

        // 启动事务
        Db::startTrans();
        try{
            Db::name('address')->update(array(
                'contacts'=>$data['contacts'],
                'phone'=>$data['phone'],
                'pro_id'=>$data['pro_id'],
                'city_id'=>$data['city_id'],
                'area_id'=>$data['area_id'],
                'province'=>$data['province'],
                'city'=>$data['city'],
                'area'=>$data['area'],
                'datavalue' => $data['datavalue'],
                'address'=>$data['address'],
                'is_default'=>$data['is_default'],
                'id'=>$data['dz_id']
            ));
            if($addressinfo['is_default'] == 0 && $data['is_default'] == 1){
                $dizhires = Db::name('address')->where('user_id',$userId)->where('is_default',1)->where('id','neq',$data['dz_id'])->update(array('is_default'=>0));
            }
            // 提交事务
            Db::commit();
            datamsg(200, '编辑地址成功' ,array('status'=>200));
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            datamsg(400, '编辑地址失败' ,array('status'=>400));
        }

    }
    
    //设置默认地址
    public function setmoren(){
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{
		    $userId = $tokenRes['user_id'];
	    }
        if(input('post.dz_id')){
            $dz_id = input('post.dz_id');
            $addressinfo = Db::name('address')->where('id',$dz_id)->where('user_id',$userId)->find();
            if($addressinfo){
                if($addressinfo['is_default'] == 0){
                    // 启动事务
                    Db::startTrans();
                    try{
                        Db::name('address')->where('id',$dz_id)->where('user_id',$userId)->update(array('is_default'=>1));
                        $dizhires = Db::name('address')->where('user_id',$userId)->where('is_default',1)->where('id','neq',$dz_id)->select();
                        if($dizhires){
                            foreach ($dizhires as $v){
                                Db::name('address')->where('id',$v['id'])->where('user_id',$userId)->update(array('is_default'=>0));
                            }
                        }
                        // 提交事务
                        Db::commit();
                        datamsg(200,'设置默认地址成功',array('status'=>200));
                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
	                    datamsg(400,'设置默认地址失败',array('status'=>400));
                    }

                }else{
	                datamsg(400,'该地址已为默认地址，请勿重复设置',array('status'=>400));
                }
            }else{
	            datamsg(400,'找不到相关地址信息',array('status'=>400));
            }
        }else{
	        datamsg(400,'缺少地址信息',array('status'=>400));
        }
    }
    
    //删除地址信息
    public function del(){
	    $tokenRes = $this->checkToken();
	    if($tokenRes['status'] == 400){
		    datamsg(400,$tokenRes['mess'],$tokenRes['data']);
	    }else{
		    $userId = $tokenRes['user_id'];
	    }
        if(input('post.dz_id')){
            $dz_id = input('post.dz_id');
            $addressinfo = Db::name('address')->where('id',$dz_id)->where('user_id',$userId)->find();
            if($addressinfo){
                $count = Db::name('address')->where('id',$dz_id)->where('user_id',$userId)->delete();
                if($count > 0){
                	datamsg(200,'删除地址成功',array('status'=>200));
                }else{
	                datamsg(400,'删除地址失败',array('status'=>400));
                }
            }else{
	            datamsg(400,'找不到相关地址信息',array('status'=>400));
            }
        }else{
	        datamsg(400,'缺少地址信息',array('status'=>400));
        }
    }


}

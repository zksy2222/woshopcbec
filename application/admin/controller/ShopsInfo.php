<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\Member as MemberModel;

class ShopsInfo extends Common
{
    public function info(){
        if(request()->isPost()){
            $admin_id = session('admin_id');
            $shop_id = session('shop_id');
            $data = input('post.');
            $data['id'] = $shop_id;
            $result = $this->validate($data,'Shops');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $shops = Db::name('shops')->where('id',$shop_id)->field('id,logo')->find();
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
                                        'id'=>$data['id']
                                    ));
                                    if($count !== false){
                                        if($data['bind_user_id'] > 0){
                                            $memberModel = new MemberModel();
                                            $bindUser = $memberModel->get($data['bind_user_id']);
                                            if(empty($bindUser)){
                                                datamsg(0,'绑定用户ID不存在');
                                            }
                                            if($bindUser['shop_id'] !=0 && $bindUser['shop_id'] != $shop_id){
                                                datamsg(0,'该用户ID已绑定其他店铺');
                                            }
                                            // 先解绑之前绑定的用户ID
                                            $memberModel->update(['shop_id'=>0],['shop_id'=>$shop_id]);
                                            // 绑定本次传入的用户ID
                                            $memberModel->update(['shop_id'=>$shop_id],['id'=>$data['bind_user_id']]);
                                        }

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
            $admin_id = session('admin_id');
            $shop_id = session('shop_id');
            $shops = Db::name('shops')->alias('a')->field('a.*,b.pro_name,c.city_name,d.area_name')->join('sp_province b','a.pro_id = b.id','LEFT')->join('sp_city c','a.city_id = c.id','LEFT')->join('sp_area d','a.area_id = d.id','LEFT')->where('a.id',$shop_id)->where('a.open_status',1)->find();
            if($shops){
                
                $prores = Db::name('province')->where('checked',1)->where('pro_zs',1)->field('id,pro_name,zm')->order('sort asc')->select();
                $cityres = Db::name('city')->where('pro_id',$shops['pro_id'])->field('id,city_name,zm')->order('sort asc')->select();
                $areares = Db::name('area')->where('city_id',$shops['city_id'])->field('id,area_name,zm')->select();
                $memberModel = new MemberModel();
                $bindUserId = $memberModel->where('shop_id',$shop_id)->value('id');
                if(!$bindUserId){
                    $bindUserId = 0;
                }
                $this->assign('bind_user_id',$bindUserId);
                $this->assign('shops',$shops);
                $this->assign('prores',$prores);
                $this->assign('cityres',$cityres);
                $this->assign('areares',$areares);
                return $this->fetch();
            }else{
                $this->error('找不到相关信息','index/index');
            }
        }
    }
    
    public function getcitylist(){
        if(request()->isPost()){
            $pro_id = input('post.pro_id');
            if($pro_id){
                $cityres = Db::name('city')->where('pro_id',$pro_id)->where('checked',1)->where('city_zs',1)->field('id,city_name,zm')->order('sort asc')->select();
                if(empty($cityres)){
                    $cityres = 0;
                }
                return json($cityres);
            }
        }
    }
    
    public function getarealist(){
        if(request()->isPost()){
            $city_id = input('post.city_id');
            if($city_id){
                $areares = Db::name('area')->where('city_id',$city_id)->where('checked',1)->field('id,area_name,zm')->order('sort asc')->select();
                if(empty($areares)){
                    $areares = 0;
                }
                return json($areares);
            }
        }
    }


}
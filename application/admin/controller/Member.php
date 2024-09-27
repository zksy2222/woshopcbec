<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\Member as MemberMx;

class Member extends Common{
    //会员列表
    public function lst(){
        $list = Db::name('member')->alias('a')->field('a.id,a.user_name,a.headimgurl,a.phone,a.integral,a.oauth,a.regtime,a.checked,a.register_client,a.register_type,a.is_cancel,b.price,a.last_login_ip')->join('sp_wallet b','a.id = b.user_id','LEFT')->order('a.regtime desc')->paginate(50)->each(function($item,$k){
            $item['headimgurl'] = url_format($item['headimgurl'],$this->webconfig['weburl']);
            return $item;
        });
        $count = Db::name('member')->count();
        $page = $list->render();
        
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $this->assign(array(
            'list'=>$list,
            'page'=>$page,
            'pnum'=>$pnum,
            'count'=>$count
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
        $count = Db::name('member')->update($data);
        if($count > 0){
            $result = 1;
        }else{
            $result = 0;
        }
        return $result;
    }
    
    //搜索
    public function search(){
        if(input('post.keyword') != ''){
            cookie('yh_telephone',input('post.keyword'),7200);
        }else{
            cookie('yh_telephone',null);
        }
        $where = array();
        if(cookie('yh_telephone')){
            $where['a.phone'] = cookie('yh_telephone');
            $where['a.email'] = cookie('yh_telephone');
        }
        
        $list = Db::name('member')->alias('a')->field('a.id,a.user_name,a.headimgurl,a.phone,a.integral,a.oauth,a.regtime,a.checked,a.is_cancel,b.price,a.register_type,a.last_login_ip')->join('sp_wallet b','a.id = b.user_id','LEFT')->whereOr($where)->order('a.regtime desc')->paginate(50)->each(function($item,$k){
            $item['headimgurl'] = url_format($item['headimgurl'],$this->webconfig['weburl']);
            return $item;
        });
        $count = Db::name('member')->count();
        
        $page = $list->render();
        if(input('page')){
            $pnum = input('page');
        }else{
            $pnum = 1;
        }
        $search = 1;
        if(cookie('yh_telephone')){
            $this->assign('keyword',cookie('yh_telephone'));
        }
        $this->assign('search',$search);
        $this->assign('pnum', $pnum);
        $this->assign('list', $list);// 赋值数据集
        $this->assign('page', $page);// 赋值分页输出
        $this->assign('count',$count);
        if(request()->isAjax()){
            return $this->fetch('ajaxpage');
        }else{
            return $this->fetch('lst');
        }
    }

    // 添加用户
    public function add(){
        if(request()->isAjax()){
            $data = input('post.');
            $result = $this->validate($data,'Member.useradd');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $user = new MemberMx();
//                $user->data($data);
//                $lastId = $user->allowField(true)->save();

                $token = settoken();
                $rxs = Db::name('member_token')->where('token',$token)->find();

                $recode = settoken();
                $recodeInfo = Db::name('member')->where('recode',$recode)->field('id')->find();
                if(!$rxs && !$recodeInfo){
                    // 启动事务
                    Db::startTrans();
                    try{
                        $userId = Db::name('member')->insertGetId(array(
                            'phone'=>$data['phone'],
                            'user_name'=>$data['user_name'],
                            'recode'=>$recode,
                            'password'=>md5($data['password']),
                            'paypwd'=>md5($data['paypwd']),
                            'headimgurl'=>$data['headimgurl'],
                            'xieyi'=>1,
                            'qrcodeurl'=>'',
                            'regtime'=>time()
                        ));

                        if($userId){
                            Db::name('member_token')->insert(array('token'=>$token,'user_id'=>$userId));
                            Db::name('wallet')->insert(array('price'=>0,'user_id'=>$userId));


                            Vendor('phpqrcode.phpqrcode');
                            //生成二维码图片
                            $object = new \QRcode();
                            $imgrq = date('Ymd',time());
                            if(!is_dir("./uploads/memberqrcode/".$imgrq)){
                                mkdir("./uploads/memberqrcode/".$imgrq);
                            }
                            $weburl = Db::name('config')->where('ca_id',5)->where('ename','weburl')->field('value')->find();
                            $url = $weburl['value']."/index/mobile/index.html?member_recode=".$recode;
                            $imgfilepath = "./uploads/memberqrcode/".$imgrq."/qrcode_".$userId.".jpg";
                            $object->png($url, $imgfilepath, 'L', 10, 2);
                            $imgurlfile = "uploads/memberqrcode/".$imgrq."/qrcode_".$userId.".jpg";
                            Db::name('member')->update(array('qrcodeurl'=>$imgurlfile,'id'=>$userId));


                        }

                        // 提交事务
                        Db::commit();
                        ys_admin_logs('增加用户','member',$userId);
                        $value = array('status'=>1,'mess'=>'增加成功');
                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                        $value = array('status'=>0,'mess'=>$e->getMessage());
                    }
                }else{
                    $value = array('status'=>400,'mess'=>'注册失败，请重试','data'=>array('status'=>400));
                }

            }
            return json($value);
        }else{
            return $this->fetch();
        }
    }

    // 检测用户名
    public function checkUsername(){
        if(request()->isAjax()){
            $arr = Db::name('member')->where('user_name',input('post.user_name'))->find();
            if($arr){
                echo 'false';
            }else{
                echo 'true';
            }
        }
    }

    // 检测用户手机号
    public function checkPhone(){
        if(request()->isAjax()){
            $arr = Db::name('member')->where('phone',input('post.phone'))->find();
            if($arr){
                echo 'false';
            }else{
                echo 'true';
            }
        }
    }

    // 编辑用户
    public function edit(){
        if(request()->isAjax()){
            if(input('post.id')){
                $data = input('post.');
                $result = $this->validate($data,'Member.useredit');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    $user = Db::name('member')->where('id',$data['id'])->find();
                    if($user){
                        if(empty($data['password'])){
                            unset($data['password']);
                        }else{
                            $data['password'] = md5($data['password']);
                        }
                        if(empty($data['paypwd'])){
                            unset($data['paypwd']);
                        }else{
                            $data['paypwd'] = md5($data['paypwd']);
                        }
                        $user = new MemberMx();
                        $count = $user->allowField(true)->save($data,array('id'=>$data['id']));
                        if($count !== false){
                            ys_admin_logs('编辑用户','member',$data['id']);
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
                $user = Db::name('member')->find(input('id'));
                if($user){
                    $this->assign('user', $user);
                    return $this->fetch();
                }else{
                    $this->error('找不到相关信息');
                }
            }else{
                $this->error('缺少参数');
            }
        }
    }

    //设置收货地址
    public function address(){
        if(request()->isAjax()){
            $data = input('post.');
            // 启动事务
            Db::startTrans();
            try{
                if(empty($data['id'])){
                    $data['pro_id'] = 0;
                    $data['city_id'] = 0;
                    $data['area_id'] = 0;
                    $data['addtime'] = time();
                    $data['is_default'] = 0;
                    $data['datavalue'] = 0;
                    db('address')->insertGetId($data);
                }else{
                    db('address')->update($data);
                }
                // 提交事务
                Db::commit();
                $value = array('status'=>1, 'mess'=>'操作成功');
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                dump($e->getMessage());die;
                $value = array('status'=>0, 'mess'=>'操作失败');
            }
            return json($value);

        }else{
            $userId = input('user_id');
            if(empty($userId)){
                $this->error('缺少用户信息');
            }
            $address = db('address')
                ->where('user_id',$userId)
                ->find();
            $this->assign('address',$address);
            $this->assign('user_id',$userId);
            return $this->fetch();
        }
    }
}

?>
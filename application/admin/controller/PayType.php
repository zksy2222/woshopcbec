<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\PayType as PayTypeMx;

class PayType extends Common{
    
    public function lst(){
        $list = Db::name('pay_type')->where('is_show',1)->order('sort asc')->select();
        $this->assign('list',$list);// 赋值数据集
        return $this->fetch();
    }
    
    public function checkPayname(){
        if(request()->isAjax()){
            $arr = Db::name('pay_type')->where('pay_name',input('post.pay_name'))->find();
            if($arr){
                echo 'false';
            }else{
                echo 'true';
            }
        }
    }
    
    public function checkOnlyname(){
        if(request()->isAjax()){
            $arr = Db::name('pay_type')->where('only_name',input('post.only_name'))->find();
            if($arr){
                echo 'false';
            }else{
                echo 'true';
            }
        }
    }
    
    //修改状态
    public function gaibian(){
        $id = input('post.id');
        $name = input('post.name');
        $value = input('post.value');
        $data[$name] = $value;
        $data['id'] = $id;
        $count = Db::name('pay_type')->where('id',$id)->update($data);
        if($count > 0){
            if($value == 1){
                ys_admin_logs('开启支付方式','pay_type',$id);
            }elseif($value == 0){
                ys_admin_logs('关闭支付方式','pay_type',$id);
            }
            $result = 1;
        }else{
            $result = 0;
        }
        return $result;
    }
    
    public function add(){
        if(request()->isAjax()){
            $data = input('post.');
            $admin_id = session('admin_id');
            $result = $this->validate($data,'PayType');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                if(!empty($data['pic_id'])){
                    $data['pay_pic'] = $data['pic_id'];
                }else{
                    $value = array('status'=>0,'mess'=>'请上传缩略图，增加失败');
                    return json($value);
                }

                $paytype = new PayTypeMx();
                $paytype->data($data);
                $lastId = $paytype->allowField(true)->save();
                if($lastId){
                    ys_admin_logs('新增支付方式','pay_type',$paytype->id);
                    $value = array('status'=>1,'mess'=>'增加成功');
                }else{
                    $value = array('status'=>0,'mess'=>'增加失败');
                }
            }
            return json($value);
        }else{
            return $this->fetch();
        }
    }
    
    public function edit(){
        if(request()->isAjax()){
            if(input('post.id')){
                $data = input('post.');
                $admin_id = session('admin_id');
                $result = $this->validate($data,'PayType');
                if(true !== $result){
                    $value = array('status'=>0,'mess'=>$result);
                }else{
                    $types = Db::name('pay_type')->where('id',$data['id'])->find();
                    if($types){
                        if(!empty($data['pic_id'])){
                            $data['pay_pic'] = $data['pic_id'];
                        }else{
                            $data['pay_pic'] = $types['pay_pic'];
                        }
                        
                        $paytype = new PayTypeMx();
                        $count = $paytype->allowField(true)->save($data,array('id'=>$data['id']));
                        if($count !== false){
                            ys_admin_logs('编辑支付方式','pay_type',$data['id']);
                            $value = array('status'=>1,'mess'=>'编辑成功');
                        }else{
                            $value = array('status'=>0,'mess'=>'编辑失败');
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
                $types = Db::name('pay_type')->find(input('id'));
                if($types){
                    $this->assign('types', $types);
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
        if(input('id') && !is_array(input('id'))){
            $id = input('id');
            $pay_pic = Db::name('pay_type')->where('id',$id)->value('pay_pic');
            $count = PayTypeMx::destroy($id);
            if($count > 0){
                if(!empty($pay_pic) && file_exists('./'.$pay_pic)){
                    @unlink('./'.$pay_pic);
                }
                ys_admin_logs('删除支付方式','pay_type',$id);
                $value = array('status'=>1,'mess'=>'删除成功');
            }else{
                $value = array('status'=>0,'mess'=>'编辑失败');
            }
        }else{
            $value = array('status'=>0,'mess'=>'请选择删除项');
        }
        return json($value);
    }
    
    //排序
    public function order(){
        $thcate = new PayTypeMx();
        $data = input('post.');
        if(!empty($data['sort'])){
            foreach ($data['sort'] as $key => $v){
                $data2['sort'] = $v;
                $data2['id'] = $key;
                $thcate->save($data2,array('id'=>$data2['id']));
            }
            $value = array('status'=>1,'mess'=>'更新排序成功');
            ys_admin_logs('更新支付方式排序','thcate',1);
        }else{
            $value = array('status'=>0,'mess'=>'未修改任何排序');
        }
        return json($value);
    }    
    
}

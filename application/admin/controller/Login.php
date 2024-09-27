<?php
/*
 * @Descripttion: 
 * @Copyright: 武汉一一零七科技有限公司©版权所有
 * @Link: www.s1107.com
 * @Contact: QQ:2487937004
 * @LastEditors: cbing
 * @LastEditTime: 2020-04-22 22:02:27
 */
namespace app\admin\controller;
use think\Controller;
use think\Db;
use think\captcha\Captcha;
use app\admin\model\Admin as AdminMx;

class Login extends Controller{
    //管理员登录
    public function index(){
        if(request()->isAjax()){
            if(input('post.username') && input('post.password')){
                $username = input('post.username');
                $password = md5(input('post.password'));
                $captcha = new Captcha();
//                if (!$captcha->check(input("post.captcha"))){
//                    datamsg(400,'验证码错误');
//                }
                $list = Db::name('admin')->alias('a')->field('a.*,b.rolename,b.pri_id_list')->join('sp_role b','a.roleid = b.id','LEFT')->where(array('a.username' => $username,'a.password' => $password))->find();
                if($list && $list['suo'] != 1){
                    $members = Db::name('member')->where('shop_id',1)->field('id')->find();
                    $memberToken = Db::name('member_token')->where('user_id',$members['id'])->find();
                    if($memberToken){
                        session('shopadmin_token', $memberToken['token']);
                    }
                    session('adminname',$list['username']);
                    session('admin_id',$list['id']);
                    session('rolename',$list['rolename']);
                    session('shop_id',1); // 自营店铺ID为1
                    $this->getpri($list['pri_id_list']);
                    $data2 = array();
                    $data2['login_ip'] = request()->ip();
                    $data2['login_time'] = time();
                    $data2['id'] = $list['id'];
                    $admin = new AdminMx();
                    $admin->allowField(true)->save($data2,array('id'=>$data2['id']));
                    $value = array('status'=>1,'mess'=>'登录成功');
                }elseif($list && $list['suo'] == 1){
                    $value = array('status'=>0,'mess'=>'您的账号已锁定');
                }elseif(!$list){
                    $value = array('status'=>0,'mess'=>'账号或密码错误');
                }
            }else{
                $value = array('status'=>0,'mess'=>'账号或密码不能为空');
            }
            return json($value);
        }else{
            $_configres = Db::name('config')->where('ca_id','in','1,2,4,5,10,15')->field('ename,value')->select();
            $configres = array();
            foreach ($_configres as $v){
                $configres[$v['ename']] = $v['value'];
            }
            $this->assign('webconfig',$configres);
            return $this->fetch();
        }
    }
    
    //获取管理员权限
    public function getpri($pri_id_list){
        if($pri_id_list == '*'){
            $menu = Db::name('privilege')->where('pid',0)->where('status',1)->order('sort asc')->select();
            foreach ($menu as $key => $val){
                $menu[$key]['child'] = Db::name('privilege')->where('pid',$val['id'])->where('status',1)->order('sort asc')->select();
                foreach ($menu[$key]['child'] as $k =>$v){
	                $menu[$key]['child'][$k]['child'] = Db::name('privilege')->where('pid',$v['id'])->where('status',1)->order('sort asc')->select();
                }
            }
            session('menu',$menu);
        }else{
            $menu = Db::name('privilege')->field('id,icon,pri_name,pid,mname,cname,aname,fwname')->where('pid',0)->where('status',1)->where('id','in',$pri_id_list)->select();
            
            foreach($menu as $key => $val){
                $menu[$key]['child'] = Db::name('privilege')->where('pid',$val['id'])->where('id','in',$pri_id_list)->where('status',1)->order('sort asc')->select();
	            foreach ($menu[$key]['child'] as $k =>$v){
		            $menu[$key]['child'][$k]['child'] = Db::name('privilege')->where('pid',$v['id'])->where('status',1)->order('sort asc')->select();
	            }
            }
            session('menu',$menu);
        }
    }
    
}
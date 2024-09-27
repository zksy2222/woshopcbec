<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use Qcloud\Cos\Client;

class Common extends Controller{
    public $webconfig;
    Public function _initialize(){
        if(!session('admin_id') || !session('shop_id')){
            $this->redirect('Login/index');
        }
       
        $this->_getconfig();
        
        if(request()->module()=='Admin' && request()->controller()=='Index'){
            return true;
        }
        
        if(request()->module()=='Admin' && request()->controller()=='Admin' && request()->action()=='loginOut'){
            return true;
        }
        
        if(session('privilege') == "*"){
            return true;
        }
        
//        if(session('privilege') != '*' && !in_array(request()->module().'/'.request()->controller().'/'.request()->action(), session('privilege'))){
//            echo '您没有权限访问该方法！';
//            die;
//        }
    }

    public function _getconfig(){
        $_configres = Db::name('config')->where('ca_id','in','1,2,4,5,10,15,17')->field('ename,value')->select();
        $configres = array();
        foreach ($_configres as $v){
            $configres[$v['ename']] = $v['value'];
        }
        $this->webconfig=$configres;
        $this->assign('configres',$configres);
    }

}
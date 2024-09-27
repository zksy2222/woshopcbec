<?php
/*
 * @Descripttion: 
 * @Copyright: 武汉一一零七科技有限公司©版权所有
 * @Link: www.s1107.com
 * @Contact: QQ:2487937004
 * @LastEditors: cbing
 * @LastEditTime: 2020-04-19 11:55:12
 */
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class PaypalConfig extends Common{
    
    public function info(){
        if(request()->isPost()){
            $data = input('post.');
            $result = $this->validate($data,'PaypalConfig');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $paypalconfigs = Db::name('paypal_config')->where('id',1)->find();
                if($paypalconfigs){
                    $count = Db::name('paypal_config')->update(array(
                        'client_id' => $data['client_id'],
                        'secret'=>$data['secret'],
                        'online'=>$data['online'],
                        'web_url'=>$data['web_url'],
                        'id'=>$paypalconfigs['id']
                    ));
                    if($count !== false){
                        $value = array('status'=>1,'mess'=>'保存成功');
                        ys_admin_logs('编辑paypal支付配置','paypal_config',$paypalconfigs['id']);
                    }else{
                        $value = array('status'=>0,'mess'=>'保存失败');
                    }
                }else{
                    $lastId = Db::name('paypal_config')->insertGetId(array(
                        'client_id' => $data['client_id'],
                        'secret'=>$data['secret'],
                        'online'=>$data['online'],
                        'web_url'=>$data['web_url'],
                    ));
                    if($lastId){
                        $value = array('status'=>1,'mess'=>'保存成功');
                        ys_admin_logs('新增paypal支付配置','paypal_config',$lastId);
                    }else{
                        $value = array('status'=>0,'mess'=>'保存失败');
                    }
                }
            }
            return json($value);
        }else{
            $paypalconfigs = Db::name('paypal_config')->order('id DESC')->find();
            $this->assign('paypalconfigs',$paypalconfigs);
            return $this->fetch();
        }
    }
}
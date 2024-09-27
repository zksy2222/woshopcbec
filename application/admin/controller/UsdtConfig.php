<?php
/*
 * @Descripttion: 
 * @Copyright: 武汉一一零七科技有限公司©版权所有
 * @Link: www.s1107.com
 * @Contact: QQ:2487937004
 * @LastEditors: cbing
 * @LastEditTime: 2020-04-19 11:53:04
 */
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class UsdtConfig extends Common{

    public function info(){
        if(request()->isPost()){
            $data = input('post.');
            $result = $this->validate($data,'UsdtConfig');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $usdtconfigs = Db::name('usdt_config')->where('id',1)->find();
                if(!empty($data['pic_id'])){
                    $data['pic_id'] = $data['pic_id'];
                }else{
                    $data['pic_id'] = $usdtconfigs['TRC20_pic'];
                }
                if(!empty($data['pic_id1'])){
                    $data['pic_id1'] = $data['pic_id1'];
                }else{
                    $data['pic_id1'] = $usdtconfigs['ERC20_pic'];
                }
                if($usdtconfigs){
                    $count = Db::name('usdt_config')->update(array(
                        'TRC20_pic' => $data['pic_id'],
                        'TRC20_wallet'=>$data['TRC20_wallet'],
                        'ERC20_pic'=>$data['pic_id1'],
                        'ERC20_wallet'=>$data['ERC20_wallet'],
                        'id'=>$data['id']
                    ));
                    if($count !== false){
                        $value = array('status'=>1,'mess'=>'保存成功');
                        ys_admin_logs('编辑USDT支付配置','usdt_config',$usdtconfigs['id']);
                    }else{
                        $value = array('status'=>0,'mess'=>'保存失败');
                    }
                }else{
                    $lastId = Db::name('usdt_config')->insertGetId(array(
                        'TRC20_pic' => $data['pic_id'],
                        'TRC20_wallet'=>$data['TRC20_wallet'],
                        'ERC20_pic'=>$data['pic_id1'],
                        'ERC20_wallet'=>$data['ERC20_wallet'],
                    ));
                    if($lastId){
                        $value = array('status'=>1,'mess'=>'保存成功');
                        ys_admin_logs('新增USDT支付配置','wxpay_config',$lastId);
                    }else{
                        $value = array('status'=>0,'mess'=>'保存失败');
                    }
                }
            }
            return json($value);
        }else{
            $usdtconfigs = Db::name('usdt_config')->order('id DESC')->find();
            $this->assign('usdtconfigs',$usdtconfigs);
            return $this->fetch();
        }
    }
}
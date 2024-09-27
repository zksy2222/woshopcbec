<?php

namespace app\admin\controller;

use app\admin\controller\Common;
use think\Db;
use app\admin\model\Ad as AdModel;

class SignSet extends Common
{
    //签到规则
    public function info()
    {
        if(request()->isPost()){
            $data = input('post.');
            $rewordOrder = [];
            foreach ($data['today'] as $k => $v){
                    $res['today']=$v;
                    $res['num'] = $data['num'][$k];
                    $rewordOrder[$k+1]=$res;
            }
            $data['reword_order'] = json_encode($rewordOrder);
            unset($data['today']);
            unset($data['num']);
            $result = true;
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $sign_sets = Db::name('sign_set')->where('id',1)->find();
                if($sign_sets){
                    $count = Db::name('sign_set')->update($data);
                    if($count !== false){
                        $value = array('status'=>1,'mess'=>'保存成功');
                        ys_admin_logs('编辑签到规则配置','order_timeout',$data['id']);
                    }else{
                        $value = array('status'=>0,'mess'=>'保存失败');
                    }
                }else{
                    $signSetId = Db::name('sign_set')->insertGetId($data);
                    if($signSetId){
                        $value = array('status'=>1,'mess'=>'保存成功');
                        ys_admin_logs('编辑签到规则配置','order_timeout',$signSetId);
                    }else{
                        $value = array('status'=>0,'mess'=>'保存失败');
                    }
                }
            }
            return json($value);
        }else{
            $signSet= Db::name('sign_set')->where('id',1)->find();
            $rewordOrder = json_decode($signSet['reword_order'],true);
            $this->assign('reword_order',$rewordOrder);
            $this->assign('sign_set',$signSet);
            return $this->fetch();
        }
    }

}
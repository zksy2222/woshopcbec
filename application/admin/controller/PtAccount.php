<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use app\admin\controller\UniPush;
use think\Db;

class PtAccount extends Common{


    public function info(){
        if(request()->isPost()){
            $data = input('post.');
            $result = $this->validate($data,'PtAccount');
            if(true !== $result){
                $value = array('status'=>0,'mess'=>$result);
            }else{
                $data['id'] = 1;
                $res = Db::name('pt_account')->where('id',1)->find();
                // 启动事务
                Db::startTrans();
                try{
                    if($res) {
                        Db::name('pt_account')->update($data);
                    }else {
                        Db::name('pt_account')->insert($data);
                    }
                    // 提交事务
                    Db::commit();
                    ys_admin_logs('修改平台账号信息','pt_account',1);
                    $value = array('status'=>1,'mess'=>'修改平台账号信息成功');
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    $value = array('status'=>0,'mess'=>'修改平台账号信息失败');
                }
            }
            return json($value);
        }else{
            $info = Db::name('pt_account')->where('id',1)->find($id);
            $this->assign('info', $info);
            return $this->fetch();
        }
    }


}
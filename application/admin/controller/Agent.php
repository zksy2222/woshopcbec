<?php

namespace app\admin\controller;

use app\admin\controller\Common;
use Exception;
use think\Db;
use app\admin\model\Agent as AgentModel;

class Agent extends Common
{
    //代理商列表
    public function lst()
    {
        $list = Db::name('agent')->alias('a')
                  ->field('a.*,b.user_name,b.headimgurl,b.phone,a.invite_code')
                  ->join('member b', 'a.user_id = b.id', 'LEFT')
                  ->order('a.id desc')
                  ->paginate(25);
        $page = $list->render();
        $count = Db::name('agent')->count();
        if (input('page')) {
            $pnum = input('page');
        } else {
            $pnum = 1;
        }
        $this->assign('pnum', $pnum);
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('count', $count);
        if (request()->isAjax()) {
            return $this->fetch('ajaxpage');
        } else {
            return $this->fetch();
        }
    }

    //添加代理商
    public function add()
    {
        if (request()->isAjax()) {
            $data = input('post.');
            $result = $this->validate($data, 'Agent');
            if (true !== $result) {
                datamsg(0,$result);
            }
            $data['checked'] = 1;
            $data['addtime'] = time();
            $member = db('agent')->where('user_id',$data['user_id'])->find();
            if($member){
                datamsg(0,'该会员已成为代理商，请勿重复添加');
            }
            // 启动事务
            Db::startTrans();
            try{
                $data['invite_code'] = agentInviteCode();
                $agentModel = new AgentModel();
                $add = $agentModel->allowField(true)->save($data);
                // 提交事务
                Db::commit();
                datamsg(1,'新增成功');
                ys_admin_logs('新增代理商','agent',$agentModel->id);
            } catch (Exception $e) {
                // 回滚事务
                Db::rollback();
                datamsg(0,'新增失败',array('status'=>400));
            }
        } else {
            return $this->fetch();
        }
    }

    //编辑代理商
    public function edit()
    {
        if (request()->isAjax()) {
            if (input('post.id')) {
                $admin_id = session('admin_id');
                $data = input('post.');
                $result = $this->validate($data, 'Agent');
                if (true !== $result) {
                    datamsg(0,$result);
                }
                // 启动事务
                Db::startTrans();
                try{
                    $data['invite_code'] = agentInviteCode();
                    $agentModel = new AgentModel();
                    $edit = $agentModel->where('id',$data['id'])->update($data);
                    // 提交事务
                    Db::commit();
                    datamsg(1,'编辑成功');
                    ys_admin_logs('编辑代理商','agent',$agentModel->id);
                } catch (Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    datamsg(0,'编辑失败',array('status'=>400));
                }
            } else {
                datamsg(0, '缺少参数');
            }

        } else {
            if (input('id')) {
                $id = input('id');
                $agentModel = new AgentModel();
                $agentInfo = $agentModel->alias('a')
                    ->field('a.*,b.user_name,b.headimgurl,phone')
                    ->join('member b', 'a.user_id = b.id', 'LEFT')->find($id);
                if ($agentInfo) {
                    $this->assign('agentInfo', $agentInfo);
                    return $this->fetch();
                } else {
                    $this->error('找不到相关信息');
                }
            } else {
                $this->error('缺少参数');
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
        $count = Db::name('agent')->update($data);
        if($count > 0){
            $result = 1;
        }else{
            $result = 0;
        }
        return $result;
    }


    //删除代理商
    public function delete()
    {
        if (input('post.id')) {
            $id = array_filter(explode(',', input('post.id')));
        } else {
            $id = input('id');
        }

        if (!empty($id)) {
            // 启动事务
            Db::startTrans();
            try{
                $agentModel = new AgentModel();
                $agentModel->where('id',$id)->delete();
                // 提交事务
                Db::commit();
                datamsg(1,'删除成功');
                ys_admin_logs('删除代理商','agent',$agentModel->id);
            } catch (Exception $e) {
                // 回滚事务
                Db::rollback();
                datamsg(0,'删除失败',array('status'=>400));
            }
        } else {
            $value = array('status' => 0, 'mess' => '请选择删除项');
        }
        return json($value);
    }


    //搜索广告
    public function search()
    {
        if (input('post.keyword')) {
            cookie('phone', input('post.keyword'), 3600);
        }

       $where = array();

        if (cookie('phone')) {
            $where['b.phone'] = array('like', '%' . cookie('phone') . '%');
        }

        $list = Db::name('agent')->alias('a')
            ->field('a.*,b.user_name,b.headimgurl,b.phone')
            ->join('member b', 'a.user_id = b.id', 'LEFT')
            ->where($where)
            ->order('a.id desc')
            ->paginate(25);
        $page = $list->render();
        if (input('page')) {
            $pnum = input('page');
        } else {
            $pnum = 1;
        }
        $search = 1;
        if (cookie('phone')) {
            $this->assign('phone', cookie('phone'));
        }
        $this->assign('search', $search);
        $this->assign('pnum', $pnum);
        $this->assign('list', $list);// 赋值数据集
        $this->assign('page', $page);// 赋值分页输出
        if (request()->isAjax()) {
            return $this->fetch('ajaxpage');
        } else {
            return $this->fetch('lst');
        }
    }
}
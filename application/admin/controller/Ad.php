<?php

namespace app\admin\controller;

use app\admin\controller\Common;
use think\Db;
use app\admin\model\Ad as AdModel;

class Ad extends Common
{
    //广告列表
    public function lst()
    {
        $cateId = input('cate_id');
        if($cateId){
            $where['a.cate_id'] = $cateId;
        }
        $list = Db::name('ad')->alias('a')
                  ->field('a.id,a.ad_name,a.ad_pic,a.sort,a.is_on,b.cate_name')
                  ->join('sp_ad_cate b', 'a.cate_id = b.id', 'LEFT')
                  ->where($where)
                  ->order('a.id asc')
                  ->paginate(25);
        $page = $list->render();
        $adCateList = Db::name('ad_cate')->field('id,cate_name,width,height')->order('id asc')->select();
        if (input('page')) {
            $pnum = input('page');
        } else {
            $pnum = 1;
        }
        $this->assign('pnum', $pnum);
        $this->assign('adCateList', $adCateList);
        $this->assign('list', $list);
        $this->assign('page', $page);
        if (request()->isAjax()) {
            return $this->fetch('ajaxpage');
        } else {
            return $this->fetch();
        }
    }

    public function checkAdname()
    {
        if (request()->isAjax()) {
            $ad_name = Db::name('ad')->where('ad_name', input('post.ad_name'))->find();
            if ($ad_name) {
                echo 'false';
            } else {
                echo 'true';
            }
        }
    }

    //根据广告位置获取广告列表
    public function poslist()
    {
        $id = input('cate_id');
        $cate_name = Db::name('ad_cate')->where('id', $id)->value('cate_name');
        $adCateList = Db::name('ad_cate')->field('id,cate_name,width,height')->order('id asc')->select();
        $list = Db::name('ad')->alias('a')->field('a.id,a.ad_name,a.is_on,b.cate_name')->join('sp_ad_cate b', 'a.cate_id = b.id', 'LEFT')->where('a.cate_id', $id)->order('a.id desc')->paginate(25);
        $page = $list->render();
        if (input('page')) {
            $pnum = input('page');
        } else {
            $pnum = 1;
        }

        $this->assign('cate_id', $id);
        $this->assign('cate_name', $cate_name);
        $this->assign('pnum', $pnum);
        $this->assign('adCateList', $adCateList);
        $this->assign('list', $list);
        $this->assign('page', $page);
        if (request()->isAjax()) {
            return $this->fetch('ajaxpage');
        } else {
            return $this->fetch('lst');
        }
    }

    //修改广告状态
    public function gaibian()
    {
        $id = input('post.id');
        $name = input('post.name');
        $value = input('post.value');
        $data[$name] = $value;
        $data['id'] = $id;
        $ads = Db::name('ad')->where('id', $data['id'])->find();
        if ($ads) {
            $count = Db::name('ad')->update($data);
            if ($count > 0) {
                if ($value == 1) {
                    // Db::name('ad')->where('cate_id',$ads['cate_id'])->where('id','neq',$ads['id'])->update(array('is_on'=>0));
                    ys_admin_logs('显示广告', 'ad', $id);
                } elseif ($value == 0) {
                    ys_admin_logs('隐藏广告', 'ad', $id);
                }
                $result = 1;
            } else {
                $result = 0;
            }
        } else {
            $result = 0;
        }
        return $result;
    }

    public function deleteone()
    {
        if (input('post.ypic_id') && input('post.ad_id')) {
            $pics = Db::name('ad_pic')->where('id', input('post.ypic_id'))->where('ad_id', input('post.ad_id'))->field('id,pic')->find();
            if ($pics) {
                $count = Db::name('ad_pic')->delete(input('post.ypic_id'));
                if ($count > 0) {
                    if (!empty($pics['pic']) && file_exists('./' . $pics['pic'])) {
                        @unlink('./' . $pics['pic']);
                    }
                    $value = 1;
                } else {
                    $value = 0;
                }
            } else {
                $value = 0;
            }
        } else {
            $value = 0;
        }
        return json($value);
    }

    //添加广告
    public function add()
    {
        if (request()->isAjax()) {
            $admin_id = session('admin_id');
            $data = input('post.');
            $result = $this->validate($data, 'Ad');
            if (true !== $result) {
                datamsg(0,$result);
            }
            $adModel = new AdModel();
            $add = $adModel->allowField(true)->save($data);
            if($add){
                datamsg(1,'新增成功');
                ys_admin_logs('新增广告','ad',$adModel->id);
            }else{
                datamsg(0,'新增失败');
            }
        } else {

            $adCateList = Db::name('ad_cate')->field('id,cate_name,width,height')->order('id asc')->select();
            $this->assign('adCateList', $adCateList);
            if (input('cate_id')) {
                $this->assign('cate_id', input('cate_id'));
            }
            return $this->fetch();
        }
    }

    //编辑广告
    public function edit()
    {
        if (request()->isAjax()) {
            if (input('post.id')) {
                $admin_id = session('admin_id');
                $data = input('post.');
                $result = $this->validate($data, 'Ad');
                if (true !== $result) {
                    datamsg(0,$result);
                }
                $adModel = new AdModel();
                $edit = $adModel->where('id',$data['id'])->update($data);
                if($edit !== false){
                    datamsg(1, '编辑成功');
                }else{
                    datamsg(0, '编辑失败');
                }
            } else {
                datamsg(0, '缺少参数');
            }

        } else {
            if (input('id')) {
                $id = input('id');
                $ads = Db::name('ad')->find($id);
                if ($ads) {

                    $adCateList = Db::name('ad_cate')->field('id,cate_name,width,height')->order('id asc')->select();
                    $this->assign('pnum', input('page'));
                    if (input('s')) {
                        $this->assign('search', input('s'));
                    }
                    if (input('cate_id')) {
                        $this->assign('cate_id', input('cate_id'));
                    }
                    $this->assign('adCateList', $adCateList);
                    $this->assign('ads', $ads);
                    return $this->fetch();
                } else {
                    $this->error('找不到相关信息');
                }
            } else {
                $this->error('缺少参数');
            }
        }
    }


    //删除广告
    public function delete()
    {
        if (input('post.id')) {
            $id = array_filter(explode(',', input('post.id')));
        } else {
            $id = input('id');
        }

        if (!empty($id)) {
            if (is_array($id)) {
                $delId = implode(',', $id);
                $arr = Db::name('ad')->where('id', 'in', $delId)->field('id,ad_pic')->select();
                $count = AdModel::destroy($delId);
            } else {
                $arr2 = Db::name('ad')->where('id', $id)->field('id,ad_pic')->find();
                $count = AdModel::destroy($id);
            }
            if ($count > 0) {
                $value = array('status' => 1, 'mess' => '删除成功');
            } else {
                $value = array('status' => 0, 'mess' => '删除失败');
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
            cookie('ad_name', input('post.keyword'), 3600);
        }
        if (input('post.cate_id') != '') {
            cookie('cate_id', input('post.cate_id'), 3600);
        }
        $adCateList = Db::name('ad_cate')->field('id,cate_name,width,height')->order('id asc')->select();
        $where = array();

        if (cookie('ad_name')) {
            $where['a.ad_name'] = array('like', '%' . cookie('ad_name') . '%');
        }

        if (cookie('cate_id') != '') {
            $cate_id = (int)cookie('cate_id');
            if ($cate_id != 0) {
                $where['a.cate_id'] = $cate_id;
            }
        }

        $list = Db::name('ad')->alias('a')->field('a.id,a.ad_name,a.ad_type,a.is_on,b.cate_name')->join('sp_ad_cate b', 'a.cate_id = b.id', 'LEFT')->where($where)->order('a.id desc')->paginate(25);
        $page = $list->render();
        if (input('page')) {
            $pnum = input('page');
        } else {
            $pnum = 1;
        }
        $search = 1;
        if (cookie('ad_name')) {
            $this->assign('ad_name', cookie('ad_name'));
        }
        if (cookie('cate_id') != '') {
            $this->assign('cate_id', $cate_id);
        }
        $this->assign('search', $search);
        $this->assign('pnum', $pnum);
        $this->assign('adCateList', $adCateList);
        $this->assign('list', $list);// 赋值数据集
        $this->assign('page', $page);// 赋值分页输出
        if (request()->isAjax()) {
            return $this->fetch('ajaxpage');
        } else {
            return $this->fetch('lst');
        }
    }
}
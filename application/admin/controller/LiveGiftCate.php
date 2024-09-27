<?php
namespace app\admin\controller;

use think\Db;
use app\admin\controller\Common;
use app\admin\model\LiveGiftCate as LiveGiftCateModel;

class LiveGiftCate extends Common{
    public function lst(){
        $limit = input('param.limit/d', 7);
        $keyword = input('param.keyword');
        $where=[];
        if ($keyword) {
            $where['cname'] = ['like', "%{$keyword}%"];
        }
        $list = Db::name('live_gift_cate')
            ->where($where)
            ->order('id DESC')
            ->paginate($limit)
            ->each(function ($item){
                return $item;
            });
        $page = $list->render();
        $this->assign([
            'list'=>$list,
            'page'=>$page
        ]);
        return $this->fetch();
    }

    public function add(){
        if (request()->isAjax()) {
            $admin_id = session('admin_id');
            $data = input('post.');
            $data['create_time'] = time();
            $result = $this->validate($data, 'LiveGiftCate');
            if (true !== $result) {
                datamsg(0,$result);
            }
            $liveGiftCateModel = new LiveGiftCateModel();
            $add = $liveGiftCateModel->allowField(true)->save($data);
            if($add){
                ys_admin_logs('新增礼物分类','live_gift_cate',$liveGiftCateModel->id);
                datamsg(1,'新增成功');
            }else{
                datamsg(0,'新增失败');
            }
        } else {
            return $this->fetch();
        }
    }

    public function edit()
    {
        if (request()->isAjax()) {
            if (input('id')) {
                $admin_id = session('admin_id');
                $data = input('post.');
                $result = $this->validate($data, 'LiveGiftCate');
                if (true !== $result) {
                    datamsg(0,$result);
                }
                $liveGiftCateModel = new LiveGiftCateModel();
                $edit = $liveGiftCateModel->where('id',$data['id'])->update($data);

                if($edit !== false){
                    ys_admin_logs('编辑礼物分类','live_gift_cate',$data['id']);
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

                    $liveGiftCate = LiveGiftCateModel::get($id);

                    if (input('s')) {
                        $this->assign('search', input('s'));
                    }

                    $this->assign('liveGiftCate', $liveGiftCate);
                    return $this->fetch();
                } else {
                    $this->error('找不到相关信息');
                }
            } else {
                $this->error('缺少参数');
            }
        }
    }

    public function delete(){
        $id = input('id');
        if(empty($id)){
            datamsg(0,'缺少id参数');
        }
        $res = LiveGiftCateModel::destroy($id);
        if($res){
            datamsg(1,'删除成功');
        }else{
            datamsg(0,'删除失败');
        }
    }



}
?>
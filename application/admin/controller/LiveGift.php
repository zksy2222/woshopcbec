<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;
use think\Loader;
use app\admin\model\LiveGift as LiveGiftModel;

class LiveGift extends Common{
    public function lst(){

        $limit = input('param.limit/d', 10);
        $keyword = input('param.keyword');
        $pnum = input('page',1);
        $where=[];
        if ($keyword) {
            $where['a.name'] = ['like', "%{$keyword}%"];
        }
        $where['a.is_delete']=0;
        $field = 'a.*,ag.cate_name';
        $list = Db::name('live_gift')
            ->alias('a')
            ->field($field)
            ->join('live_gift_cate ag','a.cid = ag.id','LEFT')
            ->where($where)
            ->order('a.id desc')
            ->paginate($limit);
        $page = $list->render();
        $this->assign([
            'list'=>$list,
            'page'=>$page,
            'pnum'=>$pnum
        ]);
        if (request()->isAjax()) {
            return $this->fetch('ajaxpage');
        } else {
            return $this->fetch('lst');
        }
    }


    public function isshow(){
        $id = input('param.id/d');
        if(empty($id)){
            datamsg(400,'id不能为空');
        }else{
            $find = db('find')->where(['id'=>$id])->find();
            if(empty($find)){
                datamsg(400,'没有找到对应的数据');
            }
            if($find['is_show'] == 1){
                $data['is_show']=0;
            }else{
                $data['is_show']=1;
            }
            $result = db('find')->where(['id'=>$id])->update($data);
            if($result){
                datamsg(200,'更新成功');
            }else{
                datamsg(400,'更新失败');
            }
        }
    }

    /**
     * 添加礼物
     */
    public function add(){
        if(request()->isAjax()){
            $data = input('param.');
            $validate = $this->validate($data, 'LiveGift');
            if (true !== $validate) {
                datamsg(0,$validate);
            }
            $result = db('live_gift')->insertGetId($data);
            if($result){
                datamsg(1,'增加成功');
            }else{
                datamsg(0,'增加失败');
            }
        }else{
            $cateres = db('live_gift_cate')->where(['is_delete'=>0])->field('id,cate_name')->select();
            $this->assign([
               'cateres'=>$cateres,
            ]);
            return $this->fetch();
        }
    }


    /**
     * 修改礼物
     */
    public function edit()
    {
        $id = input('param.id');
        if (request()->isAjax()) {
            $data = input('post.');
            $validate = $this->validate($data, 'LiveGift');
            if (true !== $validate) {
                datamsg(0,$validate);
            }
            $res = db('live_gift')->where(['id'=>$id])->update($data);
            if($res !== false){
                datamsg(1,'修改成功');
            }else{
                datamsg(0,'修改失败');
            }
        } else {
            $cateres = db('live_gift_cate')->where(['is_delete' => 0])->field('id,cate_name')->select();
            $gifts = db('live_gift')->where(['id'=>$id])->find();
            $this->assign([
                'cateres' => $cateres,
                'gifts'=>$gifts
            ]);
            return $this->fetch();
        }
    }

    public function delete(){
        $id = input('id');
        if(empty($id)){
            datamsg(0,'缺少id参数');
        }
        $res = LiveGiftModel::destroy($id);
        if($res){
            datamsg(1,'删除成功');
        }else{
            datamsg(0,'删除失败');
        }
    }


}
?>
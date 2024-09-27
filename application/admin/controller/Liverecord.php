<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class Liverecord extends Common{
    public function lst(){
        $limit = input('param.limit/d', 15);
        $keyword = input('param.keyword');
        $where=[];
        if ($keyword) {
            $where['a.title|m.phone|m.user_name'] = ['like', "%{$keyword}%"];
            $where1['ar.room'] = ['like',$keyword];
        }
        $field = 'm.user_name,m.phone,m.headimgurl,ar.*,s.shop_name,s.logo,a.title';
        $list = Db::name('live_record')
            ->alias('ar')
            ->field($field)
            ->join('member m','m.id = ar.mid','LEFT')
            ->join('live a','a.id = ar.aid','LEFT')
            ->join('shops s','s.id = a.shop_id','LEFT')
            ->where($where)
            ->whereOr($where1)
            ->order('ar.id desc')
            ->paginate($limit)
            ->each(function ($item){

                if(($item['endtime']-$item['starttime'])>0) {
                    $item['timedate'] = timediff($item['endtime'], $item['starttime']);
                }else{
                    $item['timedate']=0;
                }


                return $item;
            });
        $page = $list->render();
        $this->assign([
            'list'=>$list,
            'page'=>$page
        ]);
        return $this->fetch();
    }


    /**
     * @func 获取直播入驻的详细信息
     */
    public function info(){
        $uid = input('param.id');
        $where['m.id']=$uid;
        $field = 'm.user_name,m.phone,m.headimgurl,m.integral,m.summary,m.sex,m.email,m.wxnum,m.qqnum,m.regtime,am.*';
        $info = Db::name('live_member')
            ->alias('am')
            ->field($field)
            ->join('member m','am.uid = m.id','LEFT')
            ->where($where)
            ->find();
        $this->assign([
           'info'=>$info
        ]);
        return $this->fetch();
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
     * 直播监控
     */
    public function livemonitor(){
        $limit = input('param.limit/d', 7);
        $keyword = input('param.keyword');
        $where=[];
        if ($keyword) {
            $where['f.describe|m.phone|m.user_name'] = ['like', "%{$keyword}%"];
        }
        $field = 'm.user_name,m.phone,m.headimgurl,a.*,am.lastlogin_time,am.hot,am.recommend,am.prohibit';
        $list = Db::name('live')
            ->alias('a')
            ->field($field)
            ->join('member m','a.uid = m.id','LEFT')
            ->join('live_member am','am.uid = m.id','LEFT')
            ->where($where)
            ->order('a.id desc')
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

}
?>
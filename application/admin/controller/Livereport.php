<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

class Livereport extends Common{
    public function lst(){
        $limit = input('param.limit/d', 7);
        $keyword = input('param.keyword');
        $where=[];
        if ($keyword) {
            $where['f.describe|m.phone|m.user_name'] = ['like', "%{$keyword}%"];
        }
        
        // echo $domain;
        $list = Db::name('live_report')
            ->where($where)
            ->order('id desc')
            ->paginate($limit)
            ->each(function ($item, $key) {
                     
                    $pics = db('room_report_pic')->where(['fid'=>$item['id']])->column('pathurl');
                    if($pics){
                        foreach($pics as $k=>$v){
                            $item['pic'][] = url_format($v,$this->webconfig['weburl']);
                        }
                    }else{
                        $item['pic'] = '';
                    }
                    $item['shop_name'] = db('shops')->where(['id'=>$item['shop_id']])->value('shop_name');
                    
                    
                    return $item;
                });
//        dump($list);die;
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
    	$shop_id=input('id');
        $uid = input('uid');
        $where['m.id']=$uid;
        $field = 'm.user_name,m.phone,m.headimgurl,m.integral,m.summary,m.sex,m.email,m.wxnum,m.qqnum,m.regtime,am.*';
        $info = Db::name('live_member')
            ->alias('am')
            ->field($field)
            ->join('member m','am.uid = m.id','LEFT')
            ->where($where)
            ->find();
//        dump($info);die;
	    $liveId=db('live')->where('shop_id',$shop_id)->value('id');
//	    dump($liveId);die;
        $this->assign([
           'info'=>$info,
	        'live_id'=>$liveId
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
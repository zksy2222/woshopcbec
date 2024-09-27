<?php

namespace app\api\controller;
use app\api\controller\Common;
use app\api\model\NewPublish as NewPublishModel;
use app\api\model\Member as MemberModel;
use app\api\model\Goods;
use app\api\model\Video;
use app\common\Lookup;
use think\Db;

class NewPublish extends Common{
    
    public function getNewsList() {
        $res = $this->checkToken(0);
        if ($res['status'] == 400) {
            return json($res);
        }
        $page = input('post.page', 1);
        if (!preg_match("/^\\+?[1-9][0-9]*$/", $page)) {
            datamsg(400, 'page参数错误');
        }
        
        $webconfig = $this->webconfig;
        $pageSize = $webconfig['app_goodlst_num'];
        $offset = ($page - 1) * $pageSize;
        
        $newModel = new NewPublishModel();
        $goodsModel = new Goods();
        $videoModel = new Video();
        $list = $newModel->getNewPublishList($offset, $pageSize);
        $news_list = array();
        foreach ($list as $key => $v) {
            $news_list[$key]['title'] = $this->getNewPublishLangTitle($v['id'],$this->langCode);
            $news_list[$key]['content'] = $this->getNewPublishLangContent($v['id'],$this->langCode);



            $news_list[$key]['shop_info']['shop_id'] = $v['shop_id'];
            $news_list[$key]['shop_info']['shop_id'] = $v['shop_id'];
            $news_list[$key]['shop_info']['shop_name'] = $v['shop_name'];
            $news_list[$key]['shop_info']['shop_level'] = 1;
            $news_list[$key]['shop_info']['shop_logo'] = url_format($v['shop_logo'],$webconfig['weburl'],'?imageMogr2/thumbnail/150x150');
            $news_list[$key]['shop_info']['shop_desc'] = $v['shop_desc'];
            
            $news_list[$key]['news_info']['title'] = $this->getNewPublishLangTitle($v['id'],$this->langCode);;
            $news_list[$key]['news_info']['content'] = $this->getNewPublishLangContent($v['id'],$this->langCode);
            $news_list[$key]['news_info']['praise_num'] = $v['praise_num'];
            $news_list[$key]['news_info']['read_num'] = $v['read_num'];
            $news_list[$key]['news_info']['tag'] = lang('新品');//$v['tag'];
            $news_list[$key]['news_info']['time'] = $v['create_time'];
            $news_list[$key]['news_info']['id'] = $v['id'];

            $goods_list = $goodsModel->getGoodsListByIds($v['goods_id']);
            $video_list = $videoModel->getVideoListByIds($v['video_id']);
            $resour_list = array();
            foreach ($goods_list as $k => $item) {
                $goods_list[$k]['goods_img'] = url_format($item['goods_img'],$webconfig['weburl']);
                $goods_list[$k]['video_url'] = '';
                $resour_list[] = $goods_list[$k];
            }
            
            foreach ($video_list as $k => $item) {
                $video_list[$k]['goods_img'] = url_format($item['goods_img'],$webconfig['weburl']);
                $video_list[$k]['video_url'] = $item['video_url'];
                $resour_list[] = $video_list[$k];
            }
            $news_list[$key]['news_info']['resour_list'] = $resour_list;
        }
        $data = array('news_list' => $news_list);
        datamsg(200, 'success', $data);
    }
    
    public function setPraise() {
        $res = $this->checkToken();
        if($res['status'] == 400){
            return json($res);
        }
        $id = input('post.id');
        if (!is_numeric($id)) {
            datamsg(400, '参数错误');
        }
        $newModel = new NewPublishModel();
        $is_praise = $newModel->getNewPublishIsPraise($id);
        if (!$is_praise) {
            $newModel->setIncPraise($id);
            $is_praise = Lookup::isOpen;
            $mess = '点赞成功';
        } else {
            $newModel->setDecPraise($id);
            $is_praise = Lookup::isClose;
            $mess = '取消成功';
        }
        $newModel->update(array('is_praise' => $is_praise), array('id' => $id));
        datamsg(200, $mess, array('is_praise' => $is_praise));
    }

    //发布新品
    public function addNewPublish(){
        $tokenRes = $this->checkToken();
        if ($tokenRes['status'] == 400) {
            datamsg(400, $tokenRes['mess'], $tokenRes['data']);
        } else {
            $userId = $tokenRes['user_id'];
        }
        $data = input('post.');
        $memberModel = new MemberModel();
        $memberInfo = $memberModel->getUserInfoById($userId);
        if($memberInfo['shop_id'] == 0){
            datamsg(400, '非商户不能发布新品', array('status'=>400));
        }

        $result = $this->validate($data,'NewPublish');
        if(true !== $result){
            datamsg(400, $result, array('status'=>400));
        }

        $shopId = db('goods')->where(['id'=>$data['goods_id']])->value('shop_id');
        $newPublishData = [];
        $newPublishData['shop_id'] = $shopId;
        $newPublishData['user_id'] = $userId;
        $newPublishData['title'] = $data['title'];
        $newPublishData['content'] = $data['content'];
        $newPublishData['goods_id'] = $data['goods_id'];
        // 启动事务
        Db::startTrans();
        try{
            $newModel = new NewPublishModel();
            $newModel->save($newPublishData);
            // 提交事务
            Db::commit();
            datamsg(200, '发布新品成功', array('status'=>200));
        }catch (\Exception $e) {
            // 回滚事务
            dump($e->getMessage());
            Db::rollback();
            datamsg(400, '发布新品失败', array('status'=>400));
        }

    }
}

<?php

namespace app\api\controller;
use app\api\controller\Common;
use app\api\model\Video as VideoModel;
use app\api\model\VideoLike as VideoLikeModel;
use app\api\model\VideoComment as VideoCommentModel;
use app\api\model\Member as MemberModel;
use think\Db;

class Video extends Common {
    
    public function getVideoList() {
	    $tokenRes = $this->checkToken(0);
	    if ($tokenRes['status'] == 400) {
		    datamsg(400, $tokenRes['mess'], $tokenRes['data']);
	    }
        $page = input('post.page', 1);
        if (!preg_match("/^\\+?[1-9][0-9]*$/", $page)) {
            datamsg(400, 'page参数错误');
        }
        
        $webconfig = $this->webconfig;
        $pageSize = $webconfig['app_goodlst_num'];
        $offset = ($page - 1) * $pageSize;
        
        $videoModel = new VideoModel();
        $videoList = $videoModel->getVideoList($offset, $pageSize);
        foreach ($videoList as $key => $v) {
            $videoList[$key]['cover_img'] = url_format($v['cover_img'],$webconfig['weburl'],'?imageMogr2/thumbnail/x350');
            $videoList[$key]['video_url'] = url_format($v['video_path'],$webconfig['weburl']);
            $videoList[$key]['goods_img'] = url_format($v['goods_img'],$webconfig['weburl']);
            $videoList[$key]['shop_logo'] = url_format($v['shop_logo'],$webconfig['weburl'],'?imageMogr2/thumbnail/150x150');
            $videoList[$key]['state'] = 'pause';
            unset($v['video_path']);
            unset($v['status']);
        }
        $data = array(
            'video_list' => $videoList
        );
        datamsg(200, 'success', $data);
        
    }

    public function getPlayVideoList() {
        $tokenRes = $this->checkToken(0);
        if ($tokenRes['status'] == 400) {
            datamsg(400, $tokenRes['mess'], $tokenRes['data']);
        }
        $page = input('post.page', 1);
        if (!preg_match("/^\\+?[1-9][0-9]*$/", $page)) {
            datamsg(400, 'page参数错误');
        }
        $videoId = input('post.video_id');
        if($page == 1 && !isset($videoId)){
            datamsg(400, '视频Id参数错误');
        }


        $webconfig = $this->webconfig;
        $pageSize = $webconfig['app_goodlst_num'];
        $offset = ($page - 1) * $pageSize;

        $videoModel = new VideoModel();
        $videoList = $videoModel->getVideoList($offset, $pageSize, $videoId);
        $videoLikeModel = new VideoLikeModel();
        $videoCommentModel = new VideoCommentModel();
        foreach ($videoList as $key => $v) {

            $videoList[$key]['comment'] = $videoCommentModel->getCommentCount($v['id']);
            $videoLikeNum = $videoLikeModel->getVideoLikeNum($v['id']);
            $videoList[$key]['like_num'] = $v['praise_num'] + $videoLikeNum;
            $loginCheck = $this->checkToken();
            if($loginCheck['user_id']){
                $isLike = $videoLikeModel->isLike($v['id'],$loginCheck['user_id']);
                $videoList[$key]['is_like'] = $isLike ? true : false;
            }else{
                $videoList[$key]['is_like'] = false;
            }

            $videoList[$key]['cover_img'] = url_format($v['cover_img'],$webconfig['weburl'],'?imageMogr2/thumbnail/x350');
            $videoList[$key]['video_url'] = url_format($v['video_path'],$webconfig['weburl']);
            $videoList[$key]['goods_img'] = url_format($v['goods_img'],$webconfig['weburl']);
            $videoList[$key]['shop_logo'] = url_format($v['shop_logo'],$webconfig['weburl'],'?imageMogr2/thumbnail/150x150');
            // 以下为几个前端短视频组件需要的字段
            $videoList[$key]['state'] = 'pause';
            $videoList[$key]['muted'] = "true";
            $videoList[$key]['_id'] = md5($v['id']);
            $videoList[$key]['isShowProgressBarTime'] = false;
            $videoList[$key]['isplay'] = true;
            unset($v['video_path']);
            unset($v['create_time']);
            unset($v['status']);
        }
        $data = array(
            'video_list' => $videoList
        );
        datamsg(200, 'success', $data);

    }

    // 短视频点赞
    public function like(){
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess']);
        }

        $data = input('post.');
        $data['user_id'] = $tokenRes['user_id'];
        $validate = $this->validate($data,'Video.like');
        if($validate !== true){
            datamsg(400,$validate);
        }
        $videoLikeModel = new VideoLikeModel();
        $doLike =$videoLikeModel->doLike($data['video_id'],$data['user_id']);
        datamsg($doLike['status'],$doLike['mess']);
    }

    // 获取短视频评论列表
    public function getCommentList(){
        $tokenRes = $this->checkToken(0);
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess']);
        }

        $data = input('post.');
        $validate = $this->validate($data,'VideoComment.get_comment_list');
        if($validate !== true){
            datamsg(400,$validate);
        }

        $videoCommentModel = new VideoCommentModel();
        $commentList = $videoCommentModel->getCommentList($data['video_id'],$data['page']);
        datamsg(200,'短视频评论列表',$commentList);

    }

    // 获取短视频二级评论列表
    public function getCommentChildList(){
        $tokenRes = $this->checkToken(0);
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess']);
        }

        $data = input('post.');
        $validate = $this->validate($data,'VideoComment.get_comment_child_list');
        if($validate !== true){
            datamsg(400,$validate);
        }

        $videoCommentModel = new VideoCommentModel();
        $commentList = $videoCommentModel->getCommentChildList($data['id']);
        datamsg(200,'短视频评论列表',$commentList);

    }

    // 发布评论
    public function addComment(){
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){ datamsg(400,$tokenRes['mess']); }

        $data = input('post.');
        $data['user_id'] = $tokenRes['user_id'];
        $validate = $this->validate($data,'VideoComment.add_comment');
        if($validate !== true){
            datamsg(400,$validate);
        }
        $videoCommentModel = new VideoCommentModel();
        $add = $videoCommentModel->addComment($data['video_id'],$data['user_id'],$data['content'],$data['pid']);
        datamsg($add['status'],$add['mess'],$add['data']);
    }

    // 删除评论
    public function deleteComment(){
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){ datamsg(400,$tokenRes['mess']); }

        $data = input('post.');
        $data['user_id'] = $tokenRes['user_id'];
        $validate = $this->validate($data,'VideoComment.delete_comment');
        if($validate !== true){
            datamsg(400,$validate);
        }

        $videoCommentModel = new VideoCommentModel();
        $comment = $videoCommentModel->where('id',$data['id'])->where('user_id',$data['user_id'])->field('id')->find();
        if(!$comment){
            datamsg(400,'删除失败');
        }
        $del = $videoCommentModel->where('id',$data['id'])->delete();
        if($del){
            datamsg(200,'删除成功');
        }else{
            datamsg(400,'删除失败');
        }
    }

    // 分享成功回调
    public function share(){
        $tokenRes = $this->checkToken(0);
        if($tokenRes['status'] == 400){ datamsg(400,$tokenRes['mess']); }

        $data = input('post.');
        $data['user_id'] = $tokenRes['user_id'];
        $validate = $this->validate($data,'Video.share');
        if($validate !== true){
            datamsg(400,$validate);
        }
        $videoModel = new VideoModel();
        $share = $videoModel->where('id',$data['id'])->setInc('share');
        if($share){
            datamsg(200,'分享增加一次成功');
        }else{
             datamsg(400,'分享增加一次失败',['tip_show'=>false]);
        }

    }

    //发布短视频
    public function addVideo(){
        $tokenRes = $this->checkToken();
        if ($tokenRes['status'] == 400) {
            datamsg(400, $tokenRes['mess'], $tokenRes['data']);
        } else {
            $userId = $tokenRes['user_id'];
        }
        $memberModel = new MemberModel();
        $memberInfo = $memberModel->getUserInfoById($userId);
        if($memberInfo['shop_id'] == 0){
            datamsg(400, '非商户不能发布短视频', array('status'=>400));
        }

        $data = input('post.');

        $result = $this->validate($data,'Video.add');
        if(true !== $result){
            datamsg(400, $result, array('status'=>400));
        }
        $shopId = db('goods')->where(['id'=>$data['goods_id']])->value('shop_id');

        $videoData = [];
        $videoData['goods_id'] = $data['goods_id'];
        $videoData['shop_id'] = $shopId;
        $videoData['title'] = $data['title'];
        $videoData['cover_img'] = $data['cover_img'];
        $videoData['video_path'] = $data['video_path'];
        $videoData['describe'] = $data['describe'];
        $videoData['user_id'] = $userId;
        $videoData['create_time'] = time();
        // 启动事务
        Db::startTrans();
        try{
            $newModel = new VideoModel();
            $newModel->save($videoData);
            // 提交事务
            Db::commit();
            datamsg(200, '发布短视频成功', array('status'=>200));
        }catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            datamsg(400, '发布短视频失败', array('status'=>400));
        }
    }
}

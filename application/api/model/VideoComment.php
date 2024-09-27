<?php
namespace app\api\model;
use think\Model;

class VideoComment extends Model
{
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;

    public function getCreateTimeAttr($time) {
        return $time;
    }

    public function member(){
        return $this->belongsTo('Member','user_id');
    }

    /**
     * @description 获取短视频评论列表
     * @param $videoId integer 短视频id
     * @param $page integer 页码
     * @return \think\Paginator
     */
    public function getCommentList($videoId,$page=1){

        $list = $this->with(['member'=>function($query){
                        $query->field('id,user_name,headimgurl');
                     }])->where('video_id',$videoId)
                     ->where('pid',0)
                     ->order('create_time DESC')
                     ->paginate(20)
                     ->each(function($item,$key){
                         $item['child'] = $this->getCommentChildList($item['id'],1);
                         $webUrl = get_config_value('weburl');
                         $item['member']['headimgurl'] = url_format($item['member']['headimgurl'],$webUrl,'?imageMogr2/thumbnail/300x300');
                     });
        return $list;
    }

    /**
     * @description 获取指定评论的回复列表
     * @param $pid integer 评论id
     * @param $page integer 页码
     */
    public function getCommentChildList($pid,$page=1){
        return $this->with(['member'=>function($query){
                        $query->field('id,user_name,headimgurl');
                    }])->where('pid',$pid)
                    ->order('create_time DESC')
                    ->paginate(20)
                    ->each(function($item,$key){
                        $webUrl = get_config_value('weburl');
                        $item['member']['headimgurl'] = url_format($item['member']['headimgurl'],$webUrl,'?imageMogr2/thumbnail/300x300');
                    });
    }

    public function addComment($videoId,$userId,$content,$commentPid=0){
        $videoModel = new Video();
        $video = $videoModel::get($videoId);
        if(!$video){
            return ['status'=>400,'mess'=>'视频资源不存在','data'=>''];
        }
        $data = [
            'video_id' => $videoId,
            'user_id' => $userId,
            'content' => $content,
            'pid' => $commentPid
        ];
        $add = $this->save($data);

        if($add){
            $commentId = $this->where($data)->order('create_time DESC')->value('id');
            $comment = $this->getCommentDetail($commentId);
            return ['status'=>200,'mess'=>'评论成功','data'=>$comment];
        }else{
            return ['status'=>400,'mess'=>'评论失败','data'=>''];
        }
    }

    public function getCommentDetail($id){
        $detail = $this->with(['member'=>function($query){
                    $query->field('id,user_name,headimgurl');
                }])->find($id);
        $webUrl = get_config_value('weburl');
        $detail['member']['headimgurl'] = url_format($detail['member']['headimgurl'],$webUrl,'?imageMogr2/thumbnail/300x300');
        return $detail;
    }

    public function getCommentCount($id){
        return $this->where('video_id',$id)->count();
    }
}
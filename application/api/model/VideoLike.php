<?php
namespace app\api\model;

use think\Model;

class VideoLike extends Model
{
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;

    public function isLike($videoId,$userId){
        return $this->where('video_id',$videoId)->where('user_id',$userId)->field('id')->find();
    }

    public function doLike($videoId,$userId){
        $isLike = $this->isLike($videoId,$userId);
        $videoModel = new Video();
        $video = $videoModel::get($videoId);
        if(!$video){
            return ['status'=>400,'mess'=>'视频不存在'];
        }
        if($isLike){
            $this->startTrans();
            try {
                $this->where('video_id',$videoId)->where('user_id',$userId)->delete();
//                $videoModel->where('id',$videoId)->setDec('praise_num');
                $this->commit();
                return ['status'=>200,'mess'=>'操作成功'];
            } catch (\Exception $e){
                $this->rollback();
                return ['status'=>400,'mess'=>'操作失败'];
            }
        }else{
            $videoLikeData = [
                'video_id' => $videoId,
                'user_id'  => $userId
            ];
            $this->startTrans();
            try {
                $this->save($videoLikeData);
//                $videoModel->where('id',$videoId)->setInc('praise_num');
                $this->commit();
                return ['status'=>200,'mess'=>'操作成功'];
            } catch (\Exception $e){
                $this->rollback();
                return ['status'=>400,'mess'=>'操作失败'];
            }

        }
    }

    public function getVideoLikeNum($videoId){
        return $this->where('video_id',$videoId)->count();
    }
}
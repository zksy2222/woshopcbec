<?php
namespace app\api\controller;
class Livereturn extends Common {

    /**
     * 推流直播回调
    */
    public function pushreturn(){
        $param = input('param.');
        file_put_contents('pushreturn.log',json_encode($param));
        if(!empty($param)){
            $room = $param['stream_id'];
            $data['starttime']=time();
            $data['status']=1;
            $result = db('live')->where(['room'=>$room])->update($data);
            if($result){
                $live = db('live')->where(['room'=>$room])->find();
                $insert['mid']=$live['shop_id'];
                $insert['aid']=$live['id'];
                $insert['starttime']=time();
                $insert['room']=$room;
                db('live_record')->insert($insert);
            }
        }
    }

    /**
     * 直播录制回调
     */
    public function transcribeReturn(){
        $param = input('param.');
        file_put_contents('transcribe.log',json_encode($param));
        if(!empty($param)){
            $liveRoomShopId = db('live')->where('room',$param['stream_id'])->value('shop_id');
            if($liveRoomShopId > 0){
                $userId = db('member')->where('shop_id',$liveRoomShopId)->value('id');
                $userId = !empty($userId) ? $userId : 0;
            }else{
                $userId = 0;
                $liveRoomShopId = 0;
            }
            $data['user_id'] = $userId;
            $data['shop_id'] = $liveRoomShopId;
            $data['stream_id'] = $param['stream_id'];
            $data['start_time']= $param['start_time'];
            $data['end_time']= $param['end_time'];
            $data['video_url']= $param['video_url'];
            $data['duration']= $param['duration'];
            $result = db('live_transcribe')->insert($data);
        }
    }



    /**
     * 断流直播回调
     */
    public function breakpushreturn(){
        $param = input('param.');
        file_put_contents('breakpushreturn.log',json_encode($param));
        $room = $param['channel_id'];

        if(!empty($param)){
            $rooms = db('live')->where(['room'=>$room])->find();
            if($rooms['status'] != 2){   //如果为管理员关闭了这个直播间
                $data['status']=-1;    
            }
            $data['endtime']=time();
            $result = db('live')->where(['room'=>$room])->update($data);
            if($result){
                $live = db('live')->where(['room'=>$room])->find();
                $update['endtime']=time();
                $record = db('live_record')->where(['mid'=>$live['shop_id'],'room'=>$room])->order('id desc')->find();
                if($record){
                    db('live_record')->where(['id'=>$record['id']])->update($update);
                }
            }
        }
    }

}
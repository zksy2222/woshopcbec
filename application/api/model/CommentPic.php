<?php

namespace app\api\model;
use think\Model;

class CommentPic extends Model{
    
    public function getCommentPicList($id) {
        $where = array('com_id' => $id);
        return CommentPic::where($where)->select();
    }
}

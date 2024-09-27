<?php
namespace app\api\validate;
use think\Validate;

class VideoComment extends Validate
{
    protected $rule = [
        'id' => 'require|integer',
        'video_id' => 'require|integer',
        'user_id' => 'require|integer',
        'page' => 'require|integer',
        'content' => 'require',
    ];

    protected $message = [
        'id.require' => '缺少评论id参数',
        'id.integer' => '评论id参数类型错误',
        'video_id.require' => '缺少视频id参数',
        'video_id.integer' => '视频id参数类型错误',
        'user_id.require' => '缺少用户id参数',
        'user_id.integer' => '用户id参数类型错误',
        'page.require' => '缺少分页参数',
        'page.integer' => '分页参数类型错误',
        'content.require' => '请输入评论内容',
    ];

    protected $scene = [
        'get_comment_list' => ['video_id','page'],
        'add_comment' => ['video_id','user_id','content'],
        'delete_comment' => ['id','user_id'],
        'get_comment_child_list' => ['id','page'],
    ];

}
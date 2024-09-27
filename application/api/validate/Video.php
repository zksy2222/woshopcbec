<?php
namespace app\api\validate;
use think\Validate;

class Video extends Validate
{
    protected $rule = [
        'id'=>'require|integer',
        'video_id' => 'require|integer',
        'user_id' => 'require|integer',
        'goods_id' => 'require',
        'title' => 'require',
        'cover_img' => 'require',
        'video_path' => 'require',
        'describe' => 'require',
    ];

    protected $message = [
        'id.require' => '缺少视频id参数',
        'id.integer' => '视频id参数类型错误',
        'video_id.require' => '缺少视频id参数',
        'video_id.integer' => '视频id参数类型错误',
        'user_id.require' => '缺少用户id参数',
        'user_id.integer' => '用户id参数类型错误',
        'title.require' => '标题不能为空',
        'goods_id.require' => '请选择商品信息',
        'cover_img.require' => '请上传视频封面图',
        'video_path.require' => '请上传视频',
        'describe.require' => '请填写简介',
    ];

    protected $scene = [
        'like' => ['video_id','user_id'],
        'share' => ['id'],
        'add'=>['goods_id','title','cover_img','video_path','describe']
    ];

}
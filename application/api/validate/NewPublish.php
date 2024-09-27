<?php
namespace app\api\validate;
use think\Validate;

class NewPublish extends Validate
{
    protected $rule = [
        'title' => 'require',
        'content' => 'require',
        'goods_id' => 'require',
//        'video_id' => 'require',
//        'praise_num' => ['regex'=>'/^\+?[1-9][0-9]*$/'],
//        'read_num' => ['regex'=>'/^\+?[1-9][0-9]*$/']
    ];

    protected $message = [
        'title.require' => '发布标题不能为空！',
        'content.require' => '发布内容不能为空！',
        'goods_id.require' => '请选择商品信息！',
//        'video_id.require' => '请选择视频信息！',
//        'praise_num.regex' => '点赞数量请填写整数字！',
//        'read_num.regex' => '阅读量请填写整数字！',
    ];
    
}
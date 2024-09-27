<?php
namespace app\admin\validate;
use think\Validate;

class Video extends Validate
{
    protected $rule = [
        'shop_id' => 'require',
        'goods_id' => 'require',
        'title' => 'require',
        'cover_img' => 'require',
        'video_path' => 'require',
        'describe' => 'require',
    ];

    protected $message = [
        'title.require' => '标题不能为空',
        'shop_id.require' => '请选择商家',
        'goods_id.require' => '请选择商品信息',
        'cover_img.require' => '请上传视频封面图',
        'video_path.require' => '请上传视频',
        'describe.require' => '请填写简介',
    ];
    
}
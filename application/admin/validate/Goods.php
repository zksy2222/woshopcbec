<?php
namespace app\admin\validate;
use think\Validate;

class Goods extends Validate
{
    protected $rule = [
        'goods_name' => 'require',
        'cate_id'=>['require','regex'=>'/^\+?[1-9][0-9]*$/'],
//        'shcate_id'=>['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'market_price'=>['require','regex'=>'/(^[1-9]([0-9]+)?(\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\.[0-9]([0-9])?$)/','gt'=>0],
        'shop_price'=>['require','regex'=>'/(^[1-9]([0-9]+)?(\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\.[0-9]([0-9])?$)/','gt'=>0,'lt'=>'market_price'],
        'search_keywords' => 'require|max:100',
        'goods_desc'=>'require',
        'is_send_free'=>'require|in:0,1',
        'is_recycle'=>'require|in:0,1',
//        'type_id' => ['require','regex'=>'/^\+?[1-9][0-9]*$/'],
        'shop_id' => ['require','regex'=>'/^\+?[1-9][0-9]*$/'],
//        'goods_pics' => 'require',
    ];

    protected $message = [
        'goods_name.require' => '商品名称不能为空',
        'cate_id.require' => '请选择商品分类',
        'cate_id.regex' => '商品分类参数错误',
//        'shcate_id.require' => '请选择店铺分类',
//        'shcate_id.regex' => '店铺分类参数错误',
        'market_price.require' => '市场价格不能为空',
        'market_price.regex' => '市场价格格式错误',
        'market_price.gt' => '市场价格需大于0',
        'shop_price.require' => '商品价格不能为空',
        'shop_price.regex' => '商品价格格式错误',
        'shop_price.gt' => '商品价格需大于0',
        'shop_price.lt'=> '商品价格需小于市场价格',
        'search_keywords.require' => '搜索关键词不能为空',
        'search_keywords.max' => '搜索关键词最多100个字符',
        'goods_desc.require' => '商品详情不能为空',
        'is_send_free.require' => '请选择是否设为包邮',
        'is_send_free.in' => '设为包邮参数错误',
        'is_recycle.require' => '请选择是否放入回收站',
        'is_recycle.in' => '是否放入回收站参数错误',
//        'type_id.require' => '缺少商品类型参数',
//        'type_id.regex' => '缺少商品类型参数',
        'shop_id.require' => '缺少商家参数',
        'shop_id.regex' => '缺少商家参数',
//        'goods_pics.require' => '请上传商品图片',
    ];

    protected $scene = [
        'edit'  =>  ['goods_name','cate_id','shcate_id','market_price','shop_price','search_keywords','goods_desc','onsale','is_send_free','is_hot', 'is_recycle','shop_id'],
    ];

}
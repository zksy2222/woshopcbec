<?php

namespace app\api\model;

use think\Model;
use think\Db;

class Common extends Model
{
    /*
     * 接口验证
     * @param $needUserToken 是否验证用户token，默认1：需要验证，0不验证
     */
    public function apivalidate($needUserToken = 1)
    {

        $clientId = input('post.client_id');
        $apiToken = input('post.api_token');
        if (!$clientId || !$apiToken) {
            return array('status' => 400, 'mess' => '接口请求验证失败');
        }

        $module = request()->module();
        $controller = request()->controller();
        $action = request()->action(true);
        $secretstr = $module . '/' . $controller . '/' . $action;

        $clientSecret = Db::name('secret')->where('id', $clientId)->value('client_secret');
        if (!$clientSecret) {
            return array('status' => 400, 'mess' => '接口秘钥错误');
        }
        $apiTokenServer = md5($secretstr . $clientSecret);
        if ($apiToken != $apiTokenServer) {
            return array('status' => 400, 'mess' => '接口请求验证失败');
        }
        //验证用户token
        if ($needUserToken) {
            $token = input('post.token');
            if (empty($token)) {
                return array('status' => 400, 'mess' => '请先登录');
            }
            //设备token
            $deviceToken = input('post.device_token');
            $memberToken = Db::name('member_token')->where('token', $token)->find();
            if (empty($memberToken)) {
                return array('status' => 400, 'mess' => '登录状态失效，请重新登录');
            }

            $userInfo = Db::name('member')->where('id', $memberToken['user_id'])->where('checked', 1)->field('id,appinfo_code')->find();
            if (!$userInfo) {
                return array('status' => 400, 'mess' => '登录状态失效，请重新登录');
            }
            //查看当前用户表中存储的设备clientid值与传递的device_token值是否一致，不一致提示在其他设备登录，请重新登录
            if ($deviceToken && $deviceToken != $userInfo['appinfo_code']) {
                return array('status' => 400, 'mess' => '账号已在其他设备上登录，请重新登录');
            } else {
                return array('status' => 200, 'mess' => '接口请求验证成功', 'user_id' => $userInfo['id']);
            }

        } else {
            return array('status' => 200, 'mess' => '接口请求验证成功');
        }

    }

    /**
     * @description 判断是否是秒杀、积分、拼团活动（同一个商品同一时间段只能设置一种活动）
     * @param array $info 活动信息
     * @param string $goodsAttr 已弃用
     * @return array
     */
    public function getActivityInfo($info, $goodsAttr = '')
    {
        $activity = array();
        //秒杀信息
        $seckill = Db::name('seckill')
            ->where('goods_id', $info['id'])
            ->where('shop_id', $info['shop_id'])
            ->where('checked', 1)
            ->where('is_show', 1)
            ->where('hd_bs','not in',2)
            ->field('id,goods_id,goods_attr,price,xznum,stock,sold,start_time,end_time')
            ->order('id asc')
            ->find();

        if (!$seckill) {
            //拼团信息
            $assembles = Db::name('assemble')
                ->where('goods_id', $info['id'])
                ->where('shop_id', $info['shop_id'])
                ->where('checked', 1)
                ->where('is_show', 1)
                ->where('hd_bs','not in',2)
                ->field('id,goods_id,goods_attr,price,pin_num,start_time,end_time')
                ->order('price asc')
                ->find();
        }

        if (!$seckill && !$assembles) {
            //积分信息
            $integral = Db::name('integral_shop')
                ->where('goods_id', $info['id'])
                ->where('shop_id', $info['shop_id'])
                ->where('checked', 1)
                ->where('is_show', 1)
                ->field('id,goods_id,goods_attr,price,xznum,stock,sold,start_time,end_time,integral')
                ->order('price asc')
                ->find();

        }

        if (!empty($seckill)) {
            $seckill['ac_type'] = 1;
            $activity = $seckill;
        } elseif (!empty($integral)) {
            $integral['ac_type'] = 2;
            $activity = $integral;
        }elseif (!empty($assembles)) {
            $assembles['ac_type'] = 3;
            $activity = $assembles;
        }
        return $activity;
    }

}

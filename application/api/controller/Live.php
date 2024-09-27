<?php

namespace app\api\controller;

use app\api\model\Common as CommonModel;
use think\Db;
use app\api\model\Live as LiveModel;

class Live extends Common
{
    // 获取主播列表
    public function getAnchorList()
    {
        $tokenRes = $this->checkToken(0);
        if ($tokenRes['status'] == 400) {
            datamsg(400, $tokenRes['mess'], $tokenRes['data']);
        }
        $anchorList = Db::name('member')->field('user_name,headimgurl')->where(['shop_id' => ['gt', 0]])->limit(15)->select();
        foreach ($anchorList as $k => $v) {
            $anchorList[$k]['headimgurl'] = url_format($v['headimgurl'], $this->webconfig['weburl']);
        }
        datamsg(200, '主播列表', $anchorList);
    }

    // 获取推荐直播间
    public function getRecommendLiveRoom()
    {
        $tokenRes = $this->checkToken(0);
        if ($tokenRes['status'] == 400) {
            datamsg(400, $tokenRes['mess'], $tokenRes['data']);
        }
        $liveModel = new LiveModel();
        $liveRoom  = $liveModel->getRecommendLiveRoom(15);
        foreach ($liveRoom as $k => $v) {
            $liveRoom[$k]['cover'] = url_format($v['cover'], $this->webconfig['weburl'],'?imageMogr2/thumbnail/350x350');
        }
        datamsg(200, '推荐直播间列表', $liveRoom);
    }

    // 获取关注的直播间
    public function getFollowLive()
    {

        $tokenRes = $this->checkToken();
        if ($tokenRes['status'] == 400) {
            datamsg(400, $tokenRes['mess'], $tokenRes['data']);
        } else {
            $userId = $tokenRes['user_id'];
        }
        if ($userId) {
            $followShop = db('coll_shops')->where(['user_id' => $userId])->column('shop_id');
            $page       = input('param.page') ? input('param.page') : 1;
            $size       = input('param.size') ? input('param.size') : 10;
            $shop_id    = input('param.shop_id') ? input('param.shop_id') : "0";
            $where      = ['shop_id' => ['IN', $followShop]];

            $list = db('live')->where($where)->field("id,shop_id,status,room,title,notice,issincerity,isrecommend,cover,city_name,area_name,video_link,if(shop_id=" . $shop_id . ",1,0) as ol")
                              ->order("isrecommend DESC,ol DESC")
                              ->paginate($size)
                              ->each(function ($item, $key) {
                                  $live                = new Coldlivepush();
                                  $item['addressitem'] = 'https://' . $this->liveconfig['playdomain'] . '/live/' . $item['room'] . '.m3u8';
                                  $shop_logo           = db('shops')->where(['id' => $item['shop_id']])->value('logo');
                                  $item['shop_logo']   = $shop_logo ? $this->webconfig['weburl'] . '/' . $shop_logo : $this->webconfig['weburl'] . '/uploads/default.jpg';
                                  $tuijiangoods        = db('goods')->where(['shop_id' => $item['shop_id']])->field('goods_name,keywords,thumb_url,market_price')->find();
                                  if (!empty($tuijiangoods)) {
                                      $tuijiangoods['thumb_url'] = $this->webconfig['weburl'] . $tuijiangoods['thumb_url'];
                                  } else {
                                      $tuijiangoods              = db('goods')->where(['shop_id' => 1])->orderRaw('rand()')->field('goods_name,keywords,thumb_url,market_price')->find();
                                      $tuijiangoods['thumb_url'] = $this->webconfig['weburl'] . $tuijiangoods['thumb_url'];
                                  }
                                  $item['cover'] = url_format($item['cover'],$this->webconfig['weburl']);
                                  $tuijiangoods['goods_name'] = $item['notice'];
                                  $item['goods']              = $tuijiangoods;

                                  //最新的3条留言
                                  $item['message'] = db('live_message')->where(['room' => $item['room']])->field('message,fromid,room,type,comeintime')->limit(3)->order('id DESC')->select();

                                  //在线人数
                                  $item['online_num'] = db('live_comein')->where(['room' => $item['room']])->count();

                                  //关注人数
                                  $item['follow_num'] = db('coll_shops')->where(['shop_id' => $item['shop_id']])->count();

                                  return $item;

                              });
            datamsg(200, '获取成功', $list);
        } else {
            datamsg(200, '请先登录');
        }
    }


    /**
     * @func 获取直播间列表
     */
    public function getLiveList()
    {
        $tokenRes = $this->checkToken(0);
        if ($tokenRes['status'] == 400) {
            datamsg(400, $tokenRes['mess'], $tokenRes['data']);
        }
        $page        = input('param.page') ? input('param.page') : 1;
        $size        = input('param.size') ? input('param.size') : 10;
        $isnewperson = input('param.isnewperson');
        $type        = input('param.typeid');
        $shop_id     = input('param.shop_id') ? input('param.shop_id') : "0";
        $where       = ['isclose' => 0,'is_recycle'=>0,'status'=>['neq',2]];
        if (!empty($type)) {
            $type != -1 ? $where['type_id'] = $type : '';
        }
        $list = db('live')->where($where)
                          ->field("id,shop_id,status,room,title,notice,livetime,issincerity,cover,isrecommend,city_name,area_name,video_link,if(shop_id=" . $shop_id . ",1,0) as ol")
                          ->order("status DESC,livetime DESC,isrecommend DESC,ol DESC")
                          ->paginate($size)
                          ->each(function ($item, $key) {
                              $live = new Coldlivepush();
                              if ($item['status'] == 1) { // 直播中
                                  $item['transcribe'] = 0;  // 是否回放
                              } else {
                                  $item['addressitem'] = db('live_transcribe')
                                                          ->where('shop_id', $item['shop_id'])
                                                          ->order('id DESC')
                                                          ->value('video_url');
                                  if (!empty($item['addressitem'])) {
                                      $item['transcribe'] = 1;
                                  } else {
                                      $item['transcribe'] = 0;
                                  }

                              }


                              $shop              = db('shops')->field('shop_name,logo')
                                                              ->where(['id' => $item['shop_id']])->find();
                              $item['shop_name'] = $shop['shop_name'];
                              $item['shop_logo'] = url_format($shop['logo'],$this->webconfig['weburl'],'?imageMogr2/thumbnail/100x100');
                              $recommendGoods    = db('goods')->where(['shop_id' => $item['shop_id']])->field('goods_name,thumb_url,shop_price')->limit(3)->select();
                              for ($i = 0; $i < 3; $i++) {
                                  if (empty($recommendGoods[$i])) {
                                      $recommendGoods[$i] = [
                                          'goods_name' => lang('商品名称'),
                                          'thumb_url'  => url_format('/static/images/empty_goods/empty_goods' . $i . '.jpg', $this->webconfig['weburl']),
                                          'shop_price' => rand(30, 1000)
                                      ];
                                  } else {
                                      $recommendGoods[$i]['thumb_url'] =
                                          url_format($recommendGoods[$i]['thumb_url'], $this->webconfig['weburl']);
                                  }
                              }

                              $item['goods'] = $recommendGoods;
                              $item['cover'] = url_format($item['cover'], $this->webconfig['weburl'], '?imageMogr2/thumbnail/300x');
                              //最新的3条留言
                              $item['message'] = db('live_message')->where(['room' => $item['room']])
                                                                   ->field('message,fromid,room,type,comeintime')
                                                                   ->limit(3)->order('id DESC')->select();
                              //在线人数
                              $item['online_num'] = db('live_comein')->where(['room' => $item['room']])->count();

                              //关注人数
                              $item['follow_num'] = db('coll_shops')->where(['shop_id' => $item['shop_id']])->count();

                              //$item['title'] = $item['notice'];


                              return $item;
                          });

        $list = $list->toArray();
        $list['msg_num'] = 10;

        datamsg(200, '获取成功', $list);

    }

    /**
     * 获取直播商品类型
     */
    public function gettype()
    {
        $tokenRes = $this->checkToken(0);
        if ($tokenRes['status'] == 400) {
            datamsg(400, $tokenRes['mess'], $tokenRes['data']);
        }
        $list     = db('industry')->field('id,industry_name')->select();
        $tuian[0] = ['id' => -1, 'industry_name' => lang('推荐')];
        $list     = array_merge($tuian, $list);
        datamsg(200, '获取成功', set_lang($list));

    }

    // 判断是否已开通直播间
    public function hasLiveRoom()
    {
        $tokenRes = $this->checkToken();
        if ($tokenRes['status'] == 400) {
            datamsg(400, $tokenRes['mess'], $tokenRes['data']);
        } else {
            $userId = $tokenRes['user_id'];
        }

        $userInfo = db('member')->where(['id' => $userId])->find();
        if ($userInfo['shop_id'] == 0) {
            datamsg(400, '对不起，您还不是店主，不能直播', array('open' => false));
        } else {

            $liveRoom = db('live')->where(['shop_id' => $userInfo['shop_id'], 'isclose' => 0])->find();
            if ($liveRoom) {
                datamsg(200, '已开通直播间', array('open' => true));
            } else {
                datamsg(400, '对不起，您的直播间未开通或已禁播，请联系平台客服处理', array('open' => false));
            }
        }
    }

    // 直播间发布页面信息
    public function liveInfo()
    {
        $tokenRes = $this->checkToken();
        if ($tokenRes['status'] == 400) {
            datamsg(400, $tokenRes['mess'], $tokenRes['data']);
        } else {
            $userId = $tokenRes['user_id'];
        }
        $userInfo = db('member')->where(['id' => $userId])->find();
        if ($userInfo['shop_id'] == 0) {
            datamsg(400, '对不起，您还不是店主，不能直播');
        } else {
            $liveRoom = db('live')->where(['shop_id' => $userInfo['shop_id'], 'isclose' => 0])->find();
            if ($liveRoom) {
                datamsg(200, '已开通直播间', $liveRoom);
            } else {
                datamsg(400, '对不起，您的直播间已禁播，请联系平台客服处理');
            }
        }
    }


    /**
     * 发起直播提交
     *
     */
    public function launchlive()
    {
        $tokenRes = $this->checkToken();
        if ($tokenRes['status'] == 400) {
            datamsg(400, $tokenRes['mess'], $tokenRes['data']);
        } else {
            $userId = $tokenRes['user_id'];
        }
        $data['cover']  = input('param.cover');
        $data['title']  = input('param.title');
        $data['notice'] = input('param.description');
        if (empty($data['cover'])) {
            datamsg(400, '请上传图片封面');
        }

        $user_arr = db('member')->where(['id' => $userId])->find();
        if (empty($user_arr)) {
            datamsg(400, '没有找到用户信息');
        }
        if ($user_arr['shop_id'] == 0) {
            datamsg(400, '对不起，您还不是店主，不能直播');
        }

        $shopInfo = db('shops')->where(['id' => $user_arr['shop_id']])->find();
        if (empty($shopInfo)) {
            datamsg(400, '对不起，没有找到您的店铺信息');
        } else {
            if ($shopInfo['open_status'] == 0) {
                datamsg(400, '对不起，您的店铺已经关闭');
            }
            if ($shopInfo['normal'] == 0) {
                datamsg(400, '对不起，您的店铺已经注销');
            }
        }

        $typeid = $shopInfo['indus_id'];
        $live_arr = db('live')->where(['shop_id' => $user_arr['shop_id']])->find();
        if (empty($live_arr)) {
            $insert['shop_id']  = $user_arr['shop_id'];
            $insert['livetime'] = time();
            $insert['cover']    = $data['cover'];
            $insert['room']     = getRefereeId();
            $insert['type_id']  = $typeid;
            $shops              = $shopInfo;
            $insert['title']    = $data['title'];
            $insert['notice']   = $data['notice'];
            $insert_id          = db('live')->insertGetId($insert);
            $live_arr           = db('live')->where(['id' => $insert_id])->find();
        }

        $data['livetime'] = time();
        if (empty($live_arr['room'])) {
            $data['room'] = getRefereeId();
            if (!empty($typeid)) {
                $data['type_id'] = $typeid;
            }
            db('live')->where(['shop_id' => $user_arr['shop_id']])->update($data);
        } else {
            if (!empty($typeid)) {
                $data['type_id'] = $typeid;
            }
            db('live')->where(['id' => $live_arr['id']])->update($data);
        }
        $live_arr = db('live')->where(['shop_id' => $user_arr['shop_id']])->find();
        if ($live_arr['isclose'] == 1) {
            datamsg(400, '对不起，该直播间由于违规操作，以被关闭');
        }

        $update_live = 1;
        if ($update_live) {
            $live       = new Coldlivepush();
            $streamlive = $live->getstream($live_arr['room']);
            datamsg(200, '获取成功', $streamlive);
        } else {
            datamsg(400, '上传封面失败');
        }

    }


    /**
     * 观看直播
     */
    public function playlive()
    {
        $tokenRes = $this->checkToken();
        if ($tokenRes['status'] == 400) {
            datamsg(400, $tokenRes['mess'], $tokenRes['data']);
        }
        $room = input('param.room');
        if (empty($room)) {
            datamsg(400, '请传入房间号');
        }
        $palystream = $this->liveconfig['playdomain'];
        $type       = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        if (empty($type)) {
            $type = "https://";
        }
        $data[0] = 'rtmp://' . $palystream . '/live/' . $room;
        $data[1] = $type . $palystream . '/live/' . $room . '.flv';
        $data[2] = $type . $palystream . '/live/' . $room . '.flv';
        datamsg(200, '获取成功', $data);

    }


    /**
     * 获取直播页面商品列表
     */
    public function getLiveGoodsList()
    {
        $tokenRes = $this->checkToken(0);
        if ($tokenRes['status'] == 400) {
            datamsg(400, $tokenRes['mess'], $tokenRes['data']);
        }
        $data    = input('param.');
        $shop_id = $data['shop_id'];
        if (empty($shop_id)) {
            datamsg(400, '店铺id不能为空');
        }
        $page = input('param.page') ? input('param.page') : 1;
        $size = input('param.size') ? input('param.size') : 10;

        $where['shop_id'] = $shop_id;
        $where['onsale']  = 1;
        $where['checked'] = 1;
        $where['is_live'] = 1;
        $list             = db('goods')
            ->where($where)
            ->field('id,goods_name,shop_price,thumb_url')
            ->paginate($size)
            ->each(function ($item) {
                $item['thumb_url'] = url_format($item['thumb_url'],$this->webconfig['weburl']);
                return $item;
            });
        datamsg(200, '获取成功', $list);

    }


    /**
     * 获取直播页面礼物列表
     */
    public function liveGifts()
    {
        $tokenRes = $this->checkToken();
        if ($tokenRes['status'] == 400) {
            datamsg(400, $tokenRes['mess'], $tokenRes['data']);
        } else {
            $userId = $tokenRes['user_id'];
        }
        $user_arr = db('member')->where(['id' => $userId])->find();
        if (empty($user_arr)) {
            datamsg(400, '没有找到用户');
        }

        $list = db('live_gift')->where(['is_delete' => 0])->field('id,name,gift_coin,pic,picgif,description')->select();
        foreach ($list as $key => &$value) {
            $value['pic']    = url_format($value['pic'],$this->webconfig['weburl']);
            $value['picgif'] = url_format($value['picgif'],$this->webconfig['weburl']);
        }
        datamsg(200, '获取成功', $list);

    }


    /**
     * @func获取直播间的基本信息 直播间详情信息
     * @param shop_id店铺id
     */
    public function liveinformation()
    {
        $tokenRes = $this->checkToken();
        if ($tokenRes['status'] == 400) {
            datamsg(400, $tokenRes['mess'], $tokenRes['data']);
        } else {
            $userId = $tokenRes['user_id'];
        }

        $shop_id = input('param.shop_id');
        if (empty($shop_id)) {
            datamsg(400, '请传入店铺id');
        }


        $shop_id           = input('post.shop_id');
        $getUserId         = db('apply_info')->where('shop_id', $shop_id)->order('id DESC')->value('user_id');
        $data              = db('member')->where(['id' => $getUserId])->field('user_name,headimgurl')->find();
        $data['user_name'] = $data['user_name'] ? $data['user_name'] : '匿名';
        $shop              = db('shops')->field('shop_name,logo')->where(['id' => $shop_id])->find();
        $data['shop_name'] = $shop['shop_name'];
        $data['shop_logo'] = url_format($shop['logo'], $this->webconfig['weburl']);

        $live            = db('live')->where(['shop_id' => $shop_id])->find();
        $data['bicount'] = db('live_gift_give')->where(['shop_id' => $shop_id])->sum('gift_coin');
        //$data['ordercount'] = db('order')->where(['user_id'=>$result['user_id'],'shop_id'=>$shop_id,'state'=>1])->count();
        $data['system_notice'] = $this->liveconfig['livenotice'];
        $data['shop_id']       = $shop_id;
        $data['room']          = $live['room'];
        $data['title']         = $live['title'];
        $data['user_profile']  = $live['user_profile'];
        $data['room_notice']   = $live['notice'];
        $data['start_time']    = $live['start_time'];
        $data['end_time']      = $live['end_time'];
        $data['live_desc']     = $live['live_desc'];
        $data['notice_time']   = $live['notice_time'];
        $data['cover']         = url_format($live['cover'], $this->webconfig['weburl']);
        $data['headimgurl']    = url_format($data['headimgurl'], $this->webconfig['weburl']);
        $data['type_name']     = db('type')->where(['id' => $live['type_id']])->value('type_name');

        //在线人数
        $data['online_num'] = db('live_comein')->where(['room' => $live['room']])->count();
        //关注人数
        $data['follow_num'] = db('coll_shops')->where(['shop_id' => $shop_id])->count();

        $data['type_name'] = db('type')->where(['id' => $live['type_id']])->value('type_name');
        $is_follow         = db('coll_shops')->where(['shop_id' => $shop_id, 'user_id' => $userId])->count();
        $data['is_follow'] = $is_follow ? 1 : 0;

        //查询是否生成粉丝数据
        $userId = !empty($userId) ? $userId : 0;
        $room   = $live['room'];
        $follow = db('live_fans')->where(['user_id' => $userId, 'room' => $room])->find();
        //默认未关注直播间
        if (empty($follow) && $userId && $room) {
            $arr['user_id']  = $userId;
            $arr['room']     = $room;
            $arr['integral'] = 0;
            $arr['isfollow'] = 0;
            $arr['addtime']  = time();
            Db::name('live_fans')->insert($arr);
        }

        if ($userId > 0) {
            $data['role'] = get_user_role($userId);
        }

        $data['live_status'] = $live['status'];
        if ($data['live_status'] == 1) {
            $data['addressitem'] = 'https://' . $this->liveconfig['playdomain'] . '/live/' . $live['room'] . '.flv';
            $data['transcribe']  = 0;
        } else {
            $data['addressitem'] = db('live_transcribe')->where('shop_id', $live['shop_id'])->order('id DESC')->value('video_url');
            if (!empty($data['addressitem'])) {
                $data['transcribe'] = 1;
            } else {
                $data['transcribe']  = 0;
                $data['addressitem'] = $this->liveconfig['default_live_video_url'];
            }
        }

        datamsg(200, '获取成功', $data);
    }


    /**
     * @func获取直播间的收到的礼物信息
     * @param shop_id店铺id
     */
    public function giftsranking()
    {
        $tokenRes = $this->checkToken();
        if ($tokenRes['status'] == 400) {
            datamsg(400, $tokenRes['mess'], $tokenRes['data']);
        }
        $shop_id = input('param.shop_id');
        if (empty($shop_id)) {
            datamsg(400, '请传入店铺id');
        }
        $givefigts = db('live_gift_give')->where(['shop_id' => $shop_id])->group('uid')->field('sum(gift_coin) as count_gift_coin,uid')->order('count_gift_coin desc')->limit(10)->select();
        foreach ($givefigts as $key => &$value) {

            $member = db('member')->where(['id' => $value['uid']])->find();

            $value['username'] = $member['user_name'] ? $member['user_name'] : lang('匿名');

            $value['headimgurl'] = $member['headimgurl'] ? $this->webconfig['weburl'] . '/' . $member['headimgurl'] : $this->webconfig['weburl'] . '/uploads/default.jpg';;
        }
        datamsg(200, '获取成功', $givefigts);
    }

    // 直播间举报
    public function report()
    {
        $tokenRes = $this->checkToken();
        if ($tokenRes['status'] == 400) {
            datamsg(400, $tokenRes['mess'], $tokenRes['data']);
        } else {
            $userId = $tokenRes['user_id'];
        }
        $type    = input('post.type');
        $tips    = input('post.tips');
        $shop_id = input('post.shop_id');
        if (empty($type)) {
            datamsg(400, '请选择举报内容');
        }
        if (empty($tips)) {
            datamsg(400, '请输入举报或建议内容');
        }
        if (empty($shop_id)) {
            datamsg(400, '缺少房间参数');
        }
        if ($userId) {
            $data['uid'] = $userId;
            // $data['mid'] = $mid;
            $data['type']       = $type;
            $data['tips']       = $tips;
            $data['createtime'] = time();
            $data['status']     = 2;
            $data['shop_id']    = $shop_id;
            $res                = db('live_report')->insertGetId($data);
            $pic                = input('param.pic');
            $datapic            = explode(',', $pic);
            $picarr             = [];
            foreach ($datapic as $key => $value) {
                $picarr[$key]['pathurl'] = $value;
                $picarr[$key]['fid']     = $res;
            }
            $resultPic = Db::name('room_report_pic')->insertAll($picarr);

            if ($res && $resultPic) {
                Db::commit();
                datamsg(200, '提交成功');
            } else {
                Db::rollback();
                datamsg(400, '提交失败');
            }

        } else {
            datamsg(400, '请先登录');
        }

    }

    // 直播间客服列表
    public function liveRoomServiceList()
    {
        $tokenRes = $this->checkToken();
        if ($tokenRes['status'] == 400) {
            datamsg(400, $tokenRes['mess'], $tokenRes['data']);
        } else {
            $userId = $tokenRes['user_id'];
        }
        $shop_id = input('post.shop_id');
        if (empty($shop_id)) {
            datamsg(400, '缺少商户参数');
        }

        $shopBossId  = db('member')->where(['shop_id' => $shop_id])->value('id');
        $serviceList = db('member')->where(['pid' => $shopBossId])->select();
        if ($serviceList) {
            foreach ($serviceList as $k => $v) {
                $serviceList[$k]['headimgurl'] = !empty($serviceList[$k]['headimgurl']) ? $this->webconfig['weburl'] . '/' . $serviceList[$k]['headimgurl'] : '';
                $serviceList[$k]['toid']       = db('member_token')->where(['user_id' => $v['id']])->value('token');
            }
        }
        datamsg(200, '获取成功', $serviceList);
    }

    /**
     * 直播间日志
     */
    public function liveLog()
    {
        $tokenRes = $this->checkToken();
        if ($tokenRes['status'] == 400) {
            datamsg(400, $tokenRes['mess'], $tokenRes['data']);
        } else {
            $userId = $tokenRes['user_id'];
        }

        $room   = input('post.room');
        //判断是否是自己的直播间
        $live = db('live')->where(['room' => $room])->find();
        $user = db('member')->where(['id' => $userId])->find();
        if ($live['shop_id'] == $user['shop_id']) {
            $fromid          = input('post.token');
            $type            = input('post.type');
            $data['room']    = $room;
            $data['fromid']  = $fromid;
            $data['fromuid'] = $userId;
            $data['type']    = $type;
            $data['text']    = $type == 1 ? lang("主播上线") : lang("主播下线");
            $data['addtime'] = time();
            //print_r($data);die();
            Db::name('live_log')->insert($data);
        }

        datamsg(200,'成功');

    }


    // 获取直播录制回放列表
    public function getTranscribeList()
    {
        $tokenRes = $this->checkToken(0);
        if ($tokenRes['status'] == 400) {
            return json($tokenRes);
        }

        $userId = input('post.user_id');
        $size   = input('param.size') ? input('param.size') : 10;
        $list   = db('live_transcribe')->where('user_id', $userId)
                                     ->order("id DESC")
                                     ->paginate($size);
        datamsg(200, '获取成功', $list);

    }

    public function submitLiveParams()
    {
        $tokenRes = $this->checkToken();
        if ($tokenRes['status'] == 400) {
            return json($tokenRes);
        }
        $shop_id   = input('param.shop_id');
        $liveModel = new LiveModel();
        $live      = $liveModel->getLiveByShopId($shop_id);
        if (!$live) {
            datamsg(400, '直播间不存在');
        }
        $live_desc   = input('param.live_desc');
        $notice_time = input('param.notice_time');
        if (empty($live_desc) && empty($notice_time)) {
            datamsg(400, '参数错误');
        }
        if (!empty($live_desc)) {
            $data = array('live_desc' => $live_desc);
            $liveModel->update($data, array('shop_id' => $shop_id));
        }
        if (!empty($notice_time)) {
            $data = array('notice_time' => $notice_time);
            $liveModel->update($data, array('shop_id' => $shop_id));
        }
        datamsg(200, '提交成功');
    }


}
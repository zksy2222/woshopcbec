<?php
namespace app\api\controller;
use think\Controller;
use think\Db;
use app\api\model\Common as CommonModel;
use EasyWeChat\Factory;
use app\common\model\Upload as UploadModel;

class Common extends Controller{
    public $webconfig;
    public $liveconfig;
    public $langCode;

    public function _initialize(){
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers:Origin, X-Requested-With, Content-Type, Accept");
        $_configres = Db::name('config')->field('ename,value')->select();
        $configres = array();
        foreach ($_configres as $v){
            $configres[$v['ename']] = $v['value'];
        }
        // 微信小程序配置
        $this->wechatConfig = [
            'app_id' => get_config_value('wx_xcx_app_id'),
            'secret' => get_config_value('wx_xcx_secret'),
            // 下面为可选项
            // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
            'log' => [
                'level' => 'debug',
                'file' => __DIR__.'/wechat.log',
            ],
        ];
        // 微信小程序支付配置
	    $wxpayRes=db('wxpay_config')->where('id',1)->find();
        $this->wechatPayConfig = [
            // 前面的appid什么的也得保留哦
            'app_id'             => $wxpayRes['appid'],
            'mch_id'             => $wxpayRes['mch_id'],
            'key'                => $wxpayRes['api_key'],
            'cert_path'          => '/home/wwwroot/wosmart/cert/apiclient_cert.pem', // 绝对路径！！！！
            'key_path'           => '/home/wwwroot/wosmart/cert/apiclient_key.pem',      // 绝对路径！！！！
            'notify_url'         => $configres['weburl'].'/api/Wxpay/wxNotify',     // 你也可以在下单时单独设置来想覆盖它

        ];
        //获取直播间配置信息
        $configs = db('live_config')->find('1');
        $this->liveconfig = $configs;
        $this->webconfig = $configres;
        $this->data = input();
        $this->langCode = input('lang',config('default_lang'));
    }


    /**
     * @description: token验证
     * @param : $needUserToken,1验证用户令牌，0不验证用户令牌
     * @return: array
     */
    public function checkToken($needUserToken = 1){
        if(request()->isPost()){
            $commonModel = new CommonModel();
            $result = $commonModel->apivalidate($needUserToken);
        }else{
            $result = array('status'=>400,'mess'=>'请求方式不正确','data'=>array('status'=>400));
        }
        return $result;
    }


    /**
     *@description:把用户输入的文本转义（主要针对特殊符号和emoji表情）
     * @Author: lxb
     * @param : $str字符串
     * @return: json
     */
    public function userTextEncode($str){
        if(!is_string($str))return $str;
        if(!$str || $str=='undefined')return '';

        $text = json_encode($str); //暴露出unicode
        $text = preg_replace_callback("/(\\\u[ed][0-9a-f]{3})/i",function($str){
            return addslashes($str[0]);
        },$text); //将emoji的unicode留下，其他不动，这里的正则比原答案增加了d，因为我发现我很多emoji实际上是\ud开头的，反而暂时没发现有\ue开头。
        return json_decode($text);
    }

    /**
     * @description: 获取单个配置
     * @Author: lxb
     * @param : $id:配置ID
     * @return: json
     */
    public function getConfigInfo($id){
        $res = Db::name('config')->where('id',$id)->field('ename,value,values')->find();
        return $res;
    }

    /**
     * @description: 会员积分规则
     * @Author: lxb
     * @param : $type: 1每日登录,2邀请注册,3完善信息（绑定手机）,4完善信息（上传头像）,5观看直播,6直播发言（次）,7直播分享（次）,8购物消费（%）,9订单评价（次）,10商品分享,11连续签到奖励,12普通签到奖励,13积分兑换,14后台积分操作
     * @return: array
     */
    public function getIntegralValue($type){
        $integral = Db::name('integral_task')->where('id',$type)->value('integral');
        return intval($integral);
    }

    public function getIntegralTitle($type){
        $title = Db::name('integral_task')->where('id',$type)->value('task_name');
        return $title;
    }

    /**
     * @description: 增加会员积分
     * @Author: lxb
     * @param : $userId:用户id;$num:积分;$type:1每日登录,2邀请注册,3完善信息（绑定手机）,4完善信息（上传头像）,5观看直播,6直播发言（次）,7直播分享（次）,8购物消费（%）,9订单评价（次）,10商品分享,11连续签到奖励,12普通签到奖励,13积分兑换,14后台积分操作
     * @return: json
     */
    public function addIntegral($userId,$num,$type,$order_id=0){
        if($num <= 0){
            return false;
        }
        if($type == 4){//上传头像只送1次
            $res = Db::name('member_integral')->where('user_id',$userId)->where('type',4)->field('integral')->find();
            if(empty($res)){
                $data['user_id'] = $userId;
                $data['integral'] = $num;
                $data['type'] = $type;
                $data['order_id'] = $order_id;
                $data['addtime'] = time();
                Db::name('member_integral')->insert($data);
                Db::name('member')->where('id',$userId)->setInc('integral', $num);
            }
        }else{
            $data['user_id'] = $userId;
            $data['integral'] = $num;
            $data['type'] = $type;
            $data['order_id'] = $order_id;
            $data['addtime'] = time();
            Db::name('member_integral')->insert($data);
            Db::name('member')->where('id',$userId)->setInc('integral', $num);
        }
        return true;
    }
    /**
     * @description: 减少会员积分
     * @Author: lxb
     * @param : $userId:用户id;$num:积分;$type:1每日登录,2邀请注册,3完善信息（绑定手机）,4完善信息（上传头像）,5观看直播,6直播发言（次）,7直播分享（次）,8购物消费（%）,9订单评价（次）,10商品分享,11连续签到奖励,12普通签到奖励,13积分兑换,14后台积分操作
     * @return: json
     */
    public function decIntegral($userId,$num,$type,$order_id=0){
        $data['user_id'] = $userId;
        $data['integral'] = $num;
        $data['type'] = $type;
        $data['order_id'] = $order_id;
        $data['class'] = 1;
        $data['addtime'] = time();
        Db::name('member_integral')->insert($data);
        Db::name('member')->where('id',$userId)->setDec('integral', $num);
    }

    /**
     * @description: 会员等级查询
     * @Author: lxb
     * @param : $integral:传入的积分
     * @return: array
     */
    public function getMemberLevelInfo($integral){
        $levelInfo = Db::name('member_level')->where('points_min','<=',$integral)->where('points_max','>=',$integral)->order('sort asc')->find();
        return $levelInfo;
    }


    /**
     * @description: 直播间粉丝积分规则
     * @Author: lxb
     * @param : $userId:用户id;$num:积分;$type:1累积观看10分钟 2累积观看30分钟 3累积观看60分钟 4发言每10次 5分享直播间（单日上线5次） 6点赞满10次（每日限一次） 7关注主播（仅限一次） 8连续7天观看10分钟以上 9购物一次（签收无退货） 10购物金额分（签收无退货）每100元 11购物评价 12优质购物评价（晒图，30字以上）
     * @return: json
     */
    public function getLiveIntegralRules($type){

        $configids = array("1"=>"169","2"=>"170","3"=>"171","4"=>"172","41"=>"173","5"=>"174","51"=>"175","6"=>"176","61"=>"177","7"=>"178","8"=>"179","9"=>"180","10"=>"181","11"=>"182","12"=>"183");

        $res = Db::name('config')->where('id',$configids[$type])->field('ename,value')->find();
        //print_r($res);die();
        return $res['value'];
    }

    /**
     * @description: 粉丝积分
     * @Author: lxb
     * @param : $userId:用户id;$num:积分;$type:1累积观看10分钟 2累积观看30分钟 3累积观看60分钟 4发言每10次 5分享直播间（单日上线5次） 6点赞满10次（每日限一次） 7关注主播（仅限一次） 8连续7天观看10分钟以上 9购物一次（签收无退货） 10购物金额分（签收无退货）每100元 11购物评价 12优质购物评价（晒图，30字以上）
     * @return: json
     */
    public function addLiveIntegral($userId,$shopid,$room,$num,$type,$order_id=0,$ping_id=0){

        $follow = Db::name('live_fans')->where(['user_id'=>$userId,'room'=>$room])->find();
        //默认未关注直播间
        if(empty($follow) && $userId && $room){
            $arr['user_id'] = $userId;
            $arr['room'] = $room;
            $arr['integral'] = 0;
            $arr['isfollow'] = 0;
            $arr['addtime'] = time();
            //print_r($data);die();
            Db::name('live_fans')->insert($arr);
        }

        if(in_array($type, array('4','5','6'))){//上限

            //获取上限值
            $type_up = $type."1";
            $upnum = $this->getLiveIntegralRules($type_up);

            //当日起始时间
            $str_s=date("Y-m-d",time())." 00:00:00";
            $starttime = strtotime($str_s);
            $str_e=date("Y-m-d",time())." 23:59:59";
            $endtime=strtotime($str_e);

            $res = Db::name('fans_integral')->where('user_id',$userId)->where('type',$type)->where('addtime','>',$starttime)->field('integral')->count();
            if($res < $upnum){
                $data['user_id'] = $userId;
                $data['integral'] = $num;
                $data['type'] = $type;
                $data['shopid'] = $shopid;
                $data['room'] = $room;
                $data['order_id'] = $order_id;
                $data['ping_id'] = $ping_id;
                $data['addtime'] = time();
                //print_r($data);die();
                Db::name('fans_integral')->insert($data);
                Db::name('live_fans')->where(['user_id'=>$userId,'room'=>$room])->setInc('integral', $num);
            }
        }else{
            $data['user_id'] = $userId;
            $data['integral'] = $num;
            $data['type'] = $type;
            $data['shopid'] = $shopid;
            $data['room'] = $room;
            $data['order_id'] = $order_id;
            $data['ping_id'] = $ping_id;
            $data['addtime'] = time();
            //print_r($data);die();
            Db::name('fans_integral')->insert($data);
            Db::name('live_fans')->where(['user_id'=>$userId,'room'=>$room])->setInc('integral', $num);
        }
        return true;
    }

    /**
     * @description: 粉丝等级查询
     * @Author: lxb
     * @param : $num:传入的积分;$type 0返回等级名称，1返回折扣率
     * @return: json
     */
    public function getFansLevel($num,$type=0){

        $level = Db::name('fans_level')->field('rate,level_name,points_min,points_max')->order('sort asc')->select();

        foreach ($level as $value) {
            if (($num >= $value['points_min']) && ($num < $value['points_max'])) {
                return $type==1 ? $value['rate']: $value['level_name'];
            }
        }

    }


    public function uploadPic(){
        $tokenRes = $this->checkToken(0);
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess'],$tokenRes['data']);
        }

        $file = request()->file('file');
        if(empty($file)){
            datamsg(400,'请上传图片');
        }

        $uploadModel = new UploadModel();
        $result = $uploadModel->uploadPic($file);
        datamsg($result['status'],$result['mess'],$result['data']);
    }

    public function uploadVideo(){
        $tokenRes = $this->checkToken(0);
        if($tokenRes['status'] == 400){
            datamsg(400,$tokenRes['mess'],$tokenRes['data']);
        }

        $file = request()->file('file');
        if(empty($file)){
            datamsg(400,'请上传视频');
        }

        $uploadModel = new UploadModel();
        $result = $uploadModel->uploadVideo($file);
        datamsg($result['status'],$result['mess'],$result['data']);
    }

    // 获取对应语言的商品名称
    public function getGoodsLangName($goodsId,$langCode){
        $langId = Db::name('lang')->where('lang_code',$langCode)->value('id');
        if(!$langId){
            datamsg(400,'语种不存在');
        }
        $goodsName = Db::name('goods_lang')->where(['goods_id'=>$goodsId,'lang_id'=>$langId])->value('goods_name');
        if(!$goodsName){
            $goodsName = Db::name('goods')->where('id',$goodsId)->value('goods_name');
        }
        return $goodsName;
    }

    // 获取对应语言的商品详情
    public function getGoodsLangDescription($goodsId,$langCode){
        $langId = Db::name('lang')->where('lang_code',$langCode)->value('id');
        if(!$langId){
            datamsg(400,'语种不存在');
        }
        $goodsDescription = Db::name('goods_lang')->where(['goods_id'=>$goodsId,'lang_id'=>$langId])->value('goods_desc');
        if(!$goodsDescription){
            $goodsDescription = Db::name('goods')->where('id',$goodsId)->value('goods_desc');
        }
        return $goodsDescription;
    }

    // 获取对应语言的新品发售标题
    public function getNewPublishLangTitle($newPublishId,$langCode){
        $langId = Db::name('lang')->where('lang_code',$langCode)->value('id');
        if(!$langId){
            datamsg(400,'语种不存在');
        }
        $newPublishTitle = Db::name('new_publish_lang')->where(['new_publish_id'=>$newPublishId,'lang_id'=>$langId])->value('title');
        if(!$newPublishTitle){
            $newPublishTitle = Db::name('new_publish')->where('id',$newPublishId)->value('title');
        }
        return $newPublishTitle;
    }

    // 获取对应语言的新品发售内容
    public function getNewPublishLangContent($newPublishId,$langCode){
        $langId = Db::name('lang')->where('lang_code',$langCode)->value('id');
        if(!$langId){
            datamsg(400,'语种不存在');
        }
        $newPublishContent = Db::name('new_publish_lang')->where(['new_publish_id'=>$newPublishId,'lang_id'=>$langId])->value('content');
        if(!$newPublishContent){
            $newPublishContent = Db::name('new_publish')->where('id',$newPublishId)->value('content');
        }
        return $newPublishContent;
    }

    // 获取对应语言的文章标题
    public function getArticleLangTitle($articleId,$langCode){
        $langId = Db::name('lang')->where('lang_code',$langCode)->value('id');
        if(!$langId){
            datamsg(400,'语种不存在');
        }
        $articleTitle = Db::name('news_lang')->where(['news_id'=>$articleId,'lang_id'=>$langId])->value('ar_title');
        if(!$articleTitle){
            $articleTitle = Db::name('news')->where('id',$articleId)->value('ar_title');
        }
        return $articleTitle;
    }

    // 获取对应语言的文章详情
    public function getArticleLangContent($articleId,$langCode){
        $langId = Db::name('lang')->where('lang_code',$langCode)->value('id');
        if(!$langId){
            datamsg(400,'语种不存在');
        }
        $articleContent = Db::name('news_lang')->where(['news_id'=>$articleId,'lang_id'=>$langId])->value('ar_content');
        if(!$articleContent){
            $articleContent = Db::name('news')->where('id',$articleId)->value('ar_content');
        }
        return $articleContent;
    }

}
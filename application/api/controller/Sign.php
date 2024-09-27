<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/8 0008
 * Time: 11:09
 */
namespace app\api\controller;
use app\api\model\Common as CommonModel;
use app\api\model\IntegralTask;
use app\api\model\MemberIntegral;
use app\api\model\SignSet as SignSetmodel;
use think\Cache;
use think\console\command\make\Model;

class Sign extends Common
{
    public $model;
    public $userId;
    public function _initialize(){
        parent::_initialize();
        $this->model = new SignSetmodel;
        $result = $this->checktoken();
        if ($result['status'] == 400) {
            return json($result);
        }
        $this->user_id = $result['user_id'];
    }

    /**
     * 我的签到信息
     * @param
     * @return object
     * @author:Damow
     */
    public function signInfo(){
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){ // 400返回错误描述
            datamsg(400,$tokenRes['mess'],$tokenRes['data']);
        }else{ // 成功则返回$userId
            $userId = $tokenRes['user_id'];
        }
        $signinfo = $this->model->getSign($userId);
        $config   = model('sign_set')->get(1)->toArray();
        $signinfo['guize'] =json_decode($config['reword_order'],1);
        datamsg(200,SUCCESS,$signinfo);
    }

    /**
     * 签到记录
     * @param
     * @return object
     * @author:Damow
     */
    public function signLog(){
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){ // 400返回错误描述
            datamsg(400,$tokenRes['mess'],$tokenRes['data']);
        }else{ // 成功则返回$userId
            $userId = $tokenRes['user_id'];
        }
        !isset($this->data['page'])?$page=1:$page=$this->data['page'];
        $list   = db('sign_records')->where(['user_id'=>$userId])->field('time,credit,log')->order('id desc')->page($page,PAGE)->select();
        count($list)<1 && datamsg(200,'暂无更多数据','arr');
        foreach ($list as $k=>$v){
            $list[$k]['time']   = date('Y-m-d H:i:s',$v['time']);
        }
        datamsg(200,'成功',$list);
    }

    /**
     * 点击签到（连续签到）
     * @param date 今天的日期
     * @param type 1连续签到奖励
     * @return object
     * @author:Damow
     */
    public function dosign()
    {
        $tokenRes = $this->checkToken();
        if($tokenRes['status'] == 400){ // 400返回错误描述
            datamsg(400,$tokenRes['mess'],$tokenRes['data']);
        }else{ // 成功则返回$userId
            $userId = $tokenRes['user_id'];
        }
        $signSet = new SignSetmodel;
        $date = input('post.date');
        $type =input('post.type');
        isset($type)?$type:0;
        $today      = date('d', time());
        $tomouth    = date('Y-m', time());
        $signinfo   = $signSet->getSign($userId);

        $config   = model('sign_set')->get(1)->toArray();
        $integral = model('member')->getUser('id',$userId);

        if($type==1){
            $days = $this->data['days'];
            $sign_info = db('sign_user')->where(['user_id'=>$userId,'signdate'=>$tomouth])->find();
            $guize =json_decode($config['reword_order'],1);
            $signinfo['continuous']<$days && datamsg(400,'还未达到领取标准');
            $counts =intval($signinfo['continuous']/$config['reward_default_day']);
            $findContinuous = db('sign_records')->whereTime('time','month')->where('day',$days)->find();
            if($findContinuous){
                datamsg(400,'亲，您已经领取过了！');
            }

            model('member')->addLog($guize[$sign_info['sum']+1]['num'],lang('连续签到奖励+').$guize[$sign_info['sum']+1]['num'],1,$userId);
            $this->addIntegral($userId,$guize[$sign_info['sum']+1]['num'],11);
            datamsg(200,'领取成功',array('integral'=>$guize[$sign_info['sum']+1]['num']+$integral['integral']));
        }else{
            isset($date)&&!empty($date)?$date:datamsg(400,'请选择签到的时间');
            //普通签到
            $today!=$date && datamsg(400,'只能签到今天的日期');
            $signinfo['today'] && datamsg(400,'今天已经签到过，请明天再来');

            model('member')->addLog($config['reward_default_day'],lang('日常签到+').$config['reward_default_day'],0,$userId);

            $spIntegralTaskDb = new IntegralTask();
            $spIntegralTask = $spIntegralTaskDb->where("id",1)->find();
            if($spIntegralTask && $spIntegralTask->integral){
                $memberIntegral = new Common();
                $memberIntegral->addIntegral($userId,$spIntegralTask->integral,12);
            }

            datamsg(200,lang('签到成功，积分+').$config['reward_default_day'],array('integral'=>$config['reward_default_day']+$integral['integral']));
        }
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: zhengpeng
 * Date: 2018/6/12
 * Time: 下午4:22
 */
namespace app\admin\controller;
use think\Controller;
use app\admin\model\AdminLog as AdminLogModel;
use think\Db;


class AdminLog extends Controller
{
    /**
     * 添加后台日志
     * @param $log 操作类型
     * @param $tables 操作数据表
     * @param $opid 操作主键ID
     * @param $adminid 管理员ID
     * @param $ip 管理员ip
     */
    public static function add($log,$tables,$opid,$adminid = 0, $ip = '0.0.0.0')
    {
        $module = request()->module();
        $controller = request()->controller();
        $action = request()->action();

        if($module == 'admin'){
            if(!session('admin_id')){
                return false;
            }
            $adminid = session('admin_id');
        }

        if($module == 'shop'){
            if(!session('shopadmin_id')){
                return false;
            }
            $adminid = session('shopadmin_id');
        }

        $data = [
            'log'        => $log,
            'tables'     => $tables,
            'admin_id'   => $adminid,
            'op_id'      => $opid,
            'ip'         => request()->ip(),
            'addtime'    => time(),
            'module'     => $module,
            'controller' => $controller,
            'action'     => $action,
        ];
        $adminLogModel = new AdminLogModel();
        $adminLogModel->save($data);
    }
}
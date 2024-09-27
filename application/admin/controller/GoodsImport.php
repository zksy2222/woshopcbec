<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use app\admin\model\GoodsImportRecord;
use app\admin\model\Goods;

class GoodsImport extends Common{
    
    public function lst() {
        $list = GoodsImportRecord::getImportRecordList();
        $page = $list->render();
        $data = array('list' => $list, 'page' => $page, 'weburl' => $this->webconfig['weburl']);
        $this->assign($data);
        return request()->isAjax() ? $this->fetch('ajaxpage') : $this->fetch();
    }
    
    public function import() {
        $file = request()->file('filedata');
        if (!$file) {
            return json(array('status' => 0, 'mess' => '文件不存在'));
        }
        $info = $file->validate(['size'=>3145728,'ext'=>'xls,xlsx'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'goods_excel');
        if (!$info) {
            return json(array('status' => 0, 'mess' => '导入失败'));
        }
        $getSaveName = str_replace("\\","/",$info->getSaveName());
        $filepath = 'uploads/goods_excel/'.$getSaveName;
        $path = ROOT_PATH . 'public' . DS . 'uploads' . DS . 'goods_excel/' . $info->getSaveName();
        //加载PHPExcel类
        vendor("phpexcel.PHPExcel");
	    //实例化PHPExcel类（注意：实例化的时候前面需要加'\'）
        if ($info->getExtension() =='xlsx') {
	        $cacheMethod = \PHPExcel_CachedObjectStorageFactory::cache_in_memory_serialized;
	        $cacheSettings = array();
	        \PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
            $objReader = new \PHPExcel_Reader_Excel2007();
        } else if ($info->getExtension() =='xls') {
	        $cacheMethod = \PHPExcel_CachedObjectStorageFactory::cache_in_memory_serialized;
	        $cacheSettings = array();
	        \PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
            $objReader = new \PHPExcel_Reader_Excel5();
        }
        $objPHPExcel = $objReader->load($path,$encode='utf-8'); //获取excel文件
        $sheet = $objPHPExcel->getSheet(0);                     //激活当前的表
        $highestRow = $sheet->getHighestRow();                  //取得总行数
        $highestColumn = $sheet->getHighestColumn();            //取得总列数

        $data = array();
        for ($i = 2; $i <= $highestRow; $i++) {
	        array_push($data,[
            'goods_name' => $objPHPExcel->getActiveSheet()->getCell("A".$i)->getValue(),
            'goods_desc' => $objPHPExcel->getActiveSheet()->getCell("B".$i)->getValue(),
           'goods_brief' => $objPHPExcel->getActiveSheet()->getCell("C".$i)->getValue(),
            'shop_price' => $objPHPExcel->getActiveSheet()->getCell("D".$i)->getValue(),
            'thumb_url' => $objPHPExcel->getActiveSheet()->getCell("E".$i)->getValue(),
            'taobao_url' => $objPHPExcel->getActiveSheet()->getCell("F".$i)->getValue(),
           'market_price' => $objPHPExcel->getActiveSheet()->getCell("D".$i)->getValue(),
            'zs_price' => $objPHPExcel->getActiveSheet()->getCell("D".$i)->getValue(),
            'onsale' => 0,
            'cate_id' => 1,
            'addtime' => time(),
            'leixing' => 1,
            'shop_id' => 1,
            'search_keywords' =>1,
	        ]);
        }

	    $num = 200;//每次导入条数
	    $limit = ceil(count($data)/$num);
	    for($i=1;$i<=$limit;$i++){
		    $offset=($i-1)*$num;
		    $res=array_slice($data,$offset,$num);
		    $goods = new Goods();
		    $result = $goods->insertAll($res);
	    }


        $status = 1;
        if (!$result) {
            $status = 0;
        }
        $recodModel = new GoodsImportRecord();
        $inert_data = array(
            'file_path' => $filepath,
            'status' => $status,
            'create_time' => date('Y-m-d H:i:s')
        );
        $recodModel->save($inert_data);
        if (!$status) {
            return json(array('status' => 0, 'mess' => '导入失败'));
        }
        return json(array('status' => 1, 'mess' => '导入成功'));
    }
    
    public function delete() {
        $id = input('id');
        if (!is_numeric($id)) {
            return json(array('status' => 0, 'mess' => '参数错误'));
        }
        $info = GoodsImportRecord::getImportRecordInfo($id);
        if (!empty($info['file_path']) && file_exists($info['file_path'])) {
            @unlink($info['file_path']);
        }
        $delResult = GoodsImportRecord::destroy($id);
        if (!$delResult) {
            return json(array('status' => 0, 'mess' => '删除失败'));
        }
        return json(array('status' => 1, 'mess' => '删除成功'));
    }
    
}
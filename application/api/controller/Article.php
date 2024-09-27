<?php
namespace app\api\controller;
use app\api\controller\Common;
use think\Db;

class Article extends Common
{

	//通过标题获取文章信息
    public function getArticleByTitle(){
        // 验证token
	    $tokenRes = $this->checkToken(0);
	    if($tokenRes['status'] == 400){
		    datamsg(400, $tokenRes['mess'], $tokenRes['data']);
	    }
        $title = input('post.title');

        if(!empty($title)){
            $where = [];
            $where['ar_title'] = array('like', '%' . $title . '%');
            $articleNew = db('news_lang')->where($where)->find();
            $article = db('news')->where(['id'=>$articleNew['news_id']])->find();

            $article['ar_title'] = $this->getArticleLangTitle($article['id'],$this->langCode);
            $article['ar_content'] = $this->getArticleLangContent($article['id'],$this->langCode);
            if($article){
	            datamsg(200, '获取文章信息成功', $article);
            }else{
	            datamsg(400, '获取文章信息失败', array('status' => 400));
            }
        }
    }
	//通过id标识获取文章信息
    public function getArticleById(){
        // 验证token
	    $tokenRes = $this->checkToken(0);
        if($tokenRes['status'] == 400){
        	datamsg(400, $tokenRes['mess'], $tokenRes['data']);
        }
        $id = input('post.id');
        if(!empty($id)) {
	        $article = db('news')->find($id);
            $article['ar_title'] = $this->getArticleLangTitle($article['id'],$this->langCode);
            $article['ar_content'] = $this->getArticleLangContent($article['id'],$this->langCode);
	        if ($article) {
		        datamsg(200, '获取文章信息成功', $article);
	        } else {
		        datamsg(400, '获取文章信息失败', array('status' => 400));
	        }
        }
    }
	//通过tag标识获取文章信息
	public function getArticleByTag()
	{
		// 验证token
		$tokenRes = $this->checkToken(0);
		if ($tokenRes['status'] == 400) {
			datamsg(400, $tokenRes['mess'], $tokenRes['data']);
		}
		$tag = input('post.tag');
		// dump(input('post.'));
		if (!empty($tag)) {
			$article = db('news')->where('tag', $tag)->find();
            $article['ar_title'] = $this->getArticleLangTitle($article['id'],$this->langCode);
            $article['ar_content'] = $this->getArticleLangContent($article['id'],$this->langCode);
			if ($article) {
				datamsg(200, '获取文章信息成功', $article);
			} else {
				datamsg(400, '获取文章信息失败', array('status' => 400));
			}
		}
	}
}

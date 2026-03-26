<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
if (!defined('APP_NAME')) exit();
/*
 * 自动生成关键词链接，自动删除站外链接
 * 'view_filter'=>array('AutoLink', //关键词自动生成链接)
 */
class AutoLinkBehavior extends Behavior {
	// 行为参数定义（默认值） 可在项目配置中覆盖
	protected $options   =  array(
			'REPLACE_COUNT'        => -1,   // 默认替换关键词的个数
	);
	
    public function run(&$content) {
    	if( empty($content) ) return;
    	//自动删除站外链接============================＝＝＝＝＝
    	$p = &$GLOBALS['Config'];
    	if( $p['DEL_LINK_ENABLE'] == 1 ){
    		//自动加入当前域名作为允许链接
    		if( empty($p['ALLOW_LINK']) ){
    			$links[] = $_SERVER['HTTP_HOST'];
    		}else{
    			$link = trim($p['ALLOW_LINK'], '|');
    			$links = explode('|', $link.'|'.$_SERVER['HTTP_HOST']);
    		}
    		$content = yd_replace_link($content, $links);
    	}
    	//=======================================＝＝＝＝＝
    	
    	if( empty($p['LINK_ENABLE']) ) return;
    	$link = $p['LINK_KEYWORD'];
    	$link = str_replace(array("\r\n","\r"), "\n", $link);
    	$link = explode ("\n", $link);
    	$item = array();
    	foreach ($link as $v){
    		if( strpos($v, '=') ){
    			$item[] = explode ("=", $v);
    		}
    	}
    	unset($link);
    	if( count($item) <= 0 ) return;
    	
    	//wordpress关键字插件$regEx = '\'(?!((<.*?)|(<a.*?)))('. $keyword . ')(?!(([^<>]*?)>)|([^>]*?</a>))\'s' . $case;
		///(?!(<a.*?))长沙网站建设(?!.*<\/a>)/s
		//$content = '<span style="font-size: 14px" title="长沙网站建设">2012年是长沙友点软件创办以来在长沙网站建设、网络营销服务、营销软件销售业绩
		//取得历史性突破的一年。<a href="http://www.csyoudian.com/" target="_blank" title="长沙友点">长沙友点网络营销软件</a>';
		$content = yd_key_links($content, $item, C('REPLACE_COUNT'));
    }
    
	/**
	 * 若存在数据，则返回数组，否则返回false;
	 */
    private function _getLinkKeyword(){
    	$link = $GLOBALS['Config']['LINK_KEYWORD'];
    	$link = str_replace(array("\r\n","\r"), "\n", $link);
    	$link = explode ("\n", $link);
    	$item = array();
    	foreach ($link as $v){
    		if( strpos($v, '=') ){
    			$item[] = explode ("=", $v);
    		}
    	}
    	if( count($item) <= 0 ) return false;
    	unset($link);
    	return $item;
    }
}
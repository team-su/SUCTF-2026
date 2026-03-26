<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class LinkClassModel extends Model{
	protected $_validate = array(
			array('LinkClassName', 'require', '链接分类名称不能为空!'),
			array('LinkClassName', '', '链接分类名称已经存在!', '0', 'lang_unique'),
			array('LinkClassOrder', '/^[-]?\d+$/', '排序必须为数字!', '2','regex'),
	);
	
	protected $_auto = array(
			array('LanguageID', 'get_language_id', 1, 'function'),
	);
	
	function getLinkClass($p=false){
		$where = get_language_where();
		$result = $this->where($where)->order('LinkClassOrder asc, LinkClassID desc')->select();
		if( isset($p['Count']) && $p['Count'] == 1 && !empty($result)){ //统计分组链接数量
			$m = D('Admin/Link');
			$n = count($result);
			for($i = 0; $i < $n; $i++){
				$result[$i]['LinkCount'] = $m->where("LinkClassID={$result[$i]['LinkClassID']}")->count();
			}
		}
		return $result;
	}
	
	function batchDelLinkClass( $id = array() ){
		$id = YdInput::filterCommaNum($id);
		$where = 'LinkClassID in('.implode(',', $id).')';
		$result = $this->where($where)->delete();
		return $result;
	}
	
	//幻灯分组是否包含数据
	function hasData($id){
		$m = D('Admin/Link');
		$id = intval($id);
		$c = $m->where("LinkClassID={$id}")->count();
		if( $c > 0){
			return true;
		}else{
			return false;
		}
	}
	
	//批量排序
	function batchSortLinkClass($LinkClassID=array(), $LinkClassOrder = array() ){
		$n = count($LinkClassID);
		for($i = 0; $i < $n; $i++){
			if( is_numeric($LinkClassOrder[$i]) ){
				$id = intval($LinkClassID[$i]);
				$this->where("LinkClassID={$id}")->setField('LinkClassOrder', $LinkClassOrder[$i]);
			}
		}
	}
	
}

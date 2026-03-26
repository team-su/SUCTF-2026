<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class LinkModel extends Model{
	protected $_validate = array(
			array('LinkName', 'require', '链接名称不能为空!'),
			array('LinkName', '', '链接名称已经存在!', '0', 'lang_unique'),
			array('LinkOrder', '/^[-]?\d+$/', '排序必须为数字!', '2','regex'),
	);
	
	protected $_auto = array(
			array('LanguageID', 'get_language_id', 1, 'function'),	
	);
	
	function getLink($offset = -1, $length = -1, $LinkClassID = -1, $IsEnable = -1){
		$this->field('b.LinkClassName, a.*');
		$this->table($this->tablePrefix.'link a');
		$this->join($this->tablePrefix.'link_class b On a.LinkClassID = b.LinkClassID');
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}
		
		$where = get_language_where('a');
		if( $LinkClassID != -1 ){
			$LinkClassID = intval($LinkClassID);
			$where .= " and b.LinkClassID = $LinkClassID";
		}
		if( $IsEnable != -1 ){
			$IsEnable = intval($IsEnable);
			$where .= " and a.IsEnable = $IsEnable";
		}
        $where .= get_site_where(); //站点条件
		$result = $this->where($where)->order('b.LinkClassOrder asc, b.LinkClassID,a.LinkOrder asc, a.LinkID desc')->select();
		return $result;
	}
	
	function batchDelLink( $id = array() ){
		$id = YdInput::filterCommaNum($id);
		$where['LinkID']  = array('in', implode(',', $id));
		$result = $this->where($where)->delete();
		return $result;
	}
	
	//批量排序
	function batchSortLink($LinkID=array(), $LinkOrder = array() ){
		$n = count($LinkID);
		for($i = 0; $i < $n; $i++){
			if( is_numeric($LinkOrder[$i]) ){
				$id = intval($LinkID[$i]);
				$this->where("LinkID={$id}")->setField('LinkOrder', $LinkOrder[$i]);
			}
		}
	}
	
	function getLinkCount($LinkClassID=-1){
		$LinkClassID = intval($LinkClassID);
		$where = get_language_where();
		//$where .= " and IsEnable=1";
		if($LinkClassID != -1){
			$where .= " and LinkClassID={$LinkClassID}";
		}
		$n = $this->where($where)->count();
		return $n;
	}
	
}

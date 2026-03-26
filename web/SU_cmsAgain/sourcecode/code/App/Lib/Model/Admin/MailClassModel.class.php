<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class MailClassModel extends Model{
	protected $_validate = array(
			array('MailClassName', 'require', '分类名称不能为空!'),
			array('MailClassName', '', '分类名称已经存在!', '0', 'lang_unique'),
			array('MailClassOrder', '/^[-]?\d+$/', '排序必须为数字!', '2','regex'),
	);
	
	protected $_auto = array(
			array('LanguageID', 'get_language_id', 1, 'function'),
	);
	
	function getMailClass($IsEnable=-1){
		$where = get_language_where();
		if( $IsEnable != -1){
			$IsEnable = intval($IsEnable);
			$where .= " and IsEnable=$IsEnable";
		}
		$result = $this->where($where)->order('MailClassOrder asc, MailClassID desc')->select();
		return $result;
	}
	
	function getMailClassName($MailClassID){
		$where = get_language_where();
		$MailClassID = intval($MailClassID);
		$where .= " and MailClassID=$MailClassID";
		$name = $this->where($where)->getField('MailClassName');
		return $name;
	}
	
	//获取第一个分类
	function getFirstClassID(){
		$where = get_language_where();
		$this->where($where)->order('MailClassOrder asc, MailClassID desc');
		$id = $this->limit(1)->getField('MailClassID');
		return $id;
	}

	
	function batchDelMailClass( $id = array() ){
		$id = YdInput::filterCommaNum($id);
		$where = 'MailClassID in('.implode(',', $id).')';
		$result = $this->where($where)->delete();
		return $result;
	}
	
	//幻灯分组是否包含数据
	function hasData($id){
		$m = D('Admin/Mail');
		$id = intval($id);
		$c = $m->where("MailClassID=$id")->count();
		if( $c > 0){
			return true;
		}else{
			return false;
		}
	}
	
	//批量排序
	function batchSortMailClass($MailClassID=array(), $MailClassOrder = array() ){
		$n = count($MailClassID);
		for($i = 0; $i < $n; $i++){
			if( is_numeric($MailClassOrder[$i]) ){
				$id = intval($MailClassID[$i]);
				$this->where("MailClassID={$id}")->setField('MailClassOrder', $MailClassOrder[$i]);
			}
		}
	}
	
}

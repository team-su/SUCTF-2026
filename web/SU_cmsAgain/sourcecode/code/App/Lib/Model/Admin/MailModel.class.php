<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class MailModel extends Model{
	protected $_validate = array(
			array('MailAccount', 'require', '邮箱名称不能为空!'),
			array('MailAccount', 'email', '无效邮箱帐号!'),
			array('MailAccount', '', '名称已经存在!', '0', 'lang_unique'),
	);
	
	protected $_auto = array(
			array('LanguageID', 'get_language_id', 1, 'function'),	
	);
	
	function getMail($offset = -1, $length = -1, $MailClassID = -1, $IsEnable = -1){
		$this->field('b.MailClassName, a.*');
		$this->table($this->tablePrefix.'mail a');
		$this->join($this->tablePrefix.'mail_class b On a.MailClassID = b.MailClassID');
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}

		$where = get_language_where('a');
		if( $MailClassID != -1 ){
			$MailClassID = intval($MailClassID);
			$where .= " and b.MailClassID = $MailClassID";
		}
		if( $IsEnable != -1 ){
			$IsEnable = intval($IsEnable);
			$where .= " and a.IsEnable = $IsEnable";
		}
		
		$result = $this->where($where)->order('b.MailClassOrder asc, b.MailClassID, a.MailID desc')->select();
		return $result;
	}
	
	//获取分类邮箱帐号
	function getMailList($MailClassID = array()){
		$MailClassID = YdInput::filterCommaNum($MailClassID);
		$where = 'MailClassID in('.implode(',', $MailClassID).')';
		$where .= ' and IsEnable=1';
		$data = $this->field('MailAccount')->where($where)->select();
        $t = array();
		foreach ($data as $v){
			$t[] = $v['MailAccount'];
		}
		unset($data);
		return $t;
	}
	
	function hasMail($MailAccount){
		$where['MailAccount'] = $MailAccount;
		$n = $this->where($where)->count();
		if($n>0){
			return true;
		}else{
			return false;
		}
	}
	
	function getMailCount($MailClassID=-1){
		$where = get_language_where();
		$where .= " and IsEnable=1";
		if($MailClassID != -1){
			$MailClassID = intval($MailClassID);
			$where .= " and MailClassID=$MailClassID";
		}
		$n = $this->where($where)->count();
		return $n;
	}
	
	function batchDelMail( $id = array() ){
		$id = YdInput::filterCommaNum($id);
		$where = 'MailID in('.implode(',', $id).')';
		$result = $this->where($where)->delete();
		return $result;
	}
	
}

<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */
class MailSendModel extends Model{
	protected $_validate = array(
			array('MailTitle', 'require', '邮件主题不能为空!'),
			array('MailTitle', '', '邮件主题已经存在!', '0', 'lang_unique'),
			array('MailContent', 'require', '邮件内容不能为空!'),
	);
	
	protected $_auto = array(
			array('LanguageID', 'get_language_id', 1, 'function'),	
	);
	
	function getMailSend($offset = -1, $length = -1){
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}
		$where = get_language_where();		
		$result = $this->where($where)->order('MailSendID desc')->select();
		return $result;
	}
	
	function findMailSend($id){
		$id = intval($id);
		$data = $this->where("MailSendID={$id}")->find();
		return $data;
	}
	
	//更新发送时间
	function UpdateSendLog($MailSendID, $SendLog){
		$MailSendID = intval($MailSendID);
		$data = array('SendTime'=>date('Y-m-d H:i:s'),  'SendLog'=>$SendLog);
		return $this->where("MailSendID=$MailSendID")->setField( $data );
	}
	
	function batchDelSend( $id = array() ){
		$id = YdInput::filterCommaNum($id);
		$where = 'MailSendID in('.implode(',', $id).')';
		$result = $this->where($where)->delete();
		return $result;
	}
}

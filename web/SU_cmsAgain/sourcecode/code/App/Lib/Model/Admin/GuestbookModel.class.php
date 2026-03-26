<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class GuestbookModel extends Model{
	protected $_validate = array();
	
	protected $_auto = array(
			array('LanguageID', 'get_language_id', 1, 'function'),
	);
	
	function getMessage($offset = -1, $length = -1, $IsCheck=-1, $GuestID = -1, $SearchWords=''){
		$this->field('b.MemberName, a.*');
		$this->table($this->tablePrefix.'guestbook a');
		$this->join('Left Join '.$this->tablePrefix.'member b On a.GuestID = b.MemberID');
		if(is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);	
		}
		$where = get_language_where('a');
		if($GuestID != -1){
			$where .= " and a.GuestID=".intval($GuestID);
		}
		if($IsCheck != -1){
			$where .= " and a.IsCheck=".intval($IsCheck);
		}
        if( !empty($SearchWords) ){
            $SearchWords = YdInput::checkKeyword($SearchWords);
            $where .= " AND (
                GuestName='{$SearchWords}'  OR GuestIP = '{$SearchWords}'
                OR MessageTitle like '%{$SearchWords}%' OR MessageContent like '%{$SearchWords}%'  OR Contact like '%{$SearchWords}%' 
              ) ";
        }
		$result = $this->where($where)->order('a.MessageTime desc')->select();
		foreach($result as $k=>$v){
		    if(empty($v['GuestName'])){
		        $result[$k]['GuestName'] = L('Anonymous');
            }
        }
		return $result;
	}

    function getMessageCount($IsCheck=-1, $GuestID = -1, $SearchWords=''){
        $where = get_language_where();
        if($GuestID != -1){
            $where .= " and GuestID=".intval($GuestID);
        }
        if($IsCheck != -1){
            $where .= " and IsCheck=".intval($IsCheck);
        }
        if( !empty($SearchWords) ){
            $SearchWords = YdInput::checkKeyword($SearchWords);
            $where .= " AND (
                GuestName='{$SearchWords}'  OR GuestIP = '{$SearchWords}'
                OR MessageTitle like '%{$SearchWords}%' OR MessageContent like '%{$SearchWords}%'  OR Contact like '%{$SearchWords}%' 
              ) ";
        }
        $n = $this->where($where)->count();
        return (int)$n;
    }
	
	function batchDelMessage($id = array(),  $GuestID = -1){
		$id = YdInput::filterCommaNum($id);
		$where['MessageID']  = array('in', implode(',', $id));
		if( $GuestID != -1){
			$where['GuestID']  = intval($GuestID);
		}
		$result = $this->where($where)->delete();
		return $result;
	}
	
	function batchCheckMessage( $id = array() , $Check = 0){
		$id = YdInput::filterCommaNum($id);
		$where['MessageID']  = array('in', implode(',', $id));
        $Check = intval($Check);
		if( $Check != 0 ) $Check = 1;
		$result = $this->where($where)->setField('IsCheck', $Check);
		if( $result === false ){
			return false;
		}else{
			return true;
		}
	}
	
	function answerMessage( $id , $AnswerContent ){
		$time = date('Y-m-d H:i:s'); 
		$data = array('AnswerContent'=>$AnswerContent, 'AnswerTime'=>$time );
		$result = $this->where("MessageID=".intval($id))->setField($data);
		if( $result === false ){
			return false;
		}else{
			return true;
		}
	}
	
}

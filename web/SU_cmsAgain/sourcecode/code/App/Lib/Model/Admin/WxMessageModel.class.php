<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */
class WxMessageModel extends Model {
	protected $_validate = array();

	/**
	 * 获取消息
	 */
	function getMessage($offset = -1, $length = -1, $MsgType = '', $keywords=''){
		$this->field('a.*,b.MemberName,b.MemberRealName,b.MemberGender,b.MemberMobile');
		$this->table($this->tablePrefix.'wx_message a');
		$this->join('Left Join '.$this->tablePrefix.'member b On a.FromUserName = b.FromUser');
		
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}
		$where = "1=1";
		if( $MsgType != ''){
            $MsgType = addslashes(stripslashes($MsgType));
            $MsgType = YdInput::checkKeyword( $MsgType );
			$where .= " and a.MsgType='{$MsgType}'";
		}
		if( $keywords != ''){
			$keywords = addslashes(stripslashes($keywords));
            $keywords = YdInput::checkKeyword( $keywords );
			$where .= " and (a.p1 like '%{$keywords}%' or b.MemberRealName  like '%{$keywords}%' ";
			$where .= " or a.FromUserName  like '%{$keywords}%' or b.MemberMobile like '%{$keywords}%') ";
		}
		$result = $this->where($where)->order('a.MessageID desc')->select();
		return $result;
	}
	
	//获取消息数量
	function getCount($MsgType = '', $keywords=''){
		$this->table($this->tablePrefix.'wx_message a');
		$this->join('Left Join '.$this->tablePrefix.'member b On a.FromUserName = b.FromUser');
		$where = "1=1";
		if( $MsgType != ''){
            $MsgType = addslashes(stripslashes($MsgType));
            $MsgType = YdInput::checkKeyword( $MsgType );
			$where .= " and a.MsgType='$MsgType'";
		}
		if( $keywords != ''){
			$keywords = addslashes(stripslashes($keywords));
            $keywords = YdInput::checkKeyword( $keywords );
			$where .= " and (a.p1 like '%{$keywords}%' or b.MemberRealName  like '%{$keywords}%' ";
			$where .= " or a.FromUserName  like '%{$keywords}%' or b.MemberMobile like '%{$keywords}%') ";
		}
		$n = $this->where($where)->count();
		return $n;
	}
	
	function batchDelMessage( $id = array()){
		$id = YdInput::filterCommaNum($id);
		$where = 'MessageID in('.implode(',', $id).')';
		$result = $this->where($where)->delete();
		return $result;
	}
	
	function delAllMessage(){
		$this->where("MessageID !='' ")->delete();
	}
	
	function InsertMessage($msgId, $msgType, $fromUserName, $toUserName, $p1, $p2, $p3, $p4, $createTime){
        $msgId = YdInput::checkLetterNumber($msgId);
        $fromUserName = YdInput::checkLetterNumber($fromUserName);
        $toUserName = YdInput::checkLetterNumber($toUserName);
		$data=array(
			'MsgID'=>$msgId,
			'MsgType'=>$msgType, 
			'FromUserName'=>$fromUserName,
			'ToUserName'=>$toUserName, 
			'p1'=>$p1, 
			'p2'=>$p2, 
			'p3'=>$p3, 
			'p4'=>$p4,
			'CreateTime'=>date('Y-m-d H:i:s',$createTime),
		);
		return $this->add($data);
	}
}

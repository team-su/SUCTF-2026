<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */
class WxReplyModel extends Model {
	protected $_validate = array();
	
	function getMenuType(){
		$t[1] = array("MenuTypeID"=>1,"MenuTypeName"=>"微信文本消息");
		$t[2] = array("MenuTypeID"=>2,"MenuTypeName"=>"微信图文消息");
		$t[3] = array("MenuTypeID"=>3,"MenuTypeName"=>"微信音乐消息");
		$t[5] = array("MenuTypeID"=>5,"MenuTypeName"=>"第三方应用");
		return $t;
	}
	
	/**
	 * 获取默认自动回复
	 */
	function findDefaultReply($IsEnable = -1){
		$where = "ReplyID=2";
		if( $IsEnable != -1){
			$IsEnable = intval($IsEnable);
			$where .= " and IsEnable={$IsEnable}";
		}
		$data = $this->where($where)->find();
		return $data;
	}
	
	/**
	 * 获取地理位置自动回复
	 */
	function findLbsReply($IsEnable = -1){
		$where = "ReplyID=3";
		if( $IsEnable != -1){
			$IsEnable = intval($IsEnable);
			$where .= " and IsEnable={$IsEnable}";
		}
		$data = $this->where($where)->find();
		return $data;
	}
	
	/**
	 * 获取关注自动回复
	 */
	function findSubscribeReply($IsEnable = -1){
		$where = "ReplyID=1";
		if( $IsEnable != -1){
			$IsEnable = intval($IsEnable);
			$where .= " and IsEnable={$IsEnable}";
		}
		$data = $this->where($where)->find();
		return $data;
	}
	
	/**
	 * 获取关键词回复
	 */
	function getKeywordReply($offset = -1, $length = -1, $IsEnable = -1){
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}
		$this->table($this->tablePrefix.'wx_reply a');
		$this->field('a.*, b.TypeName,b.IsReply');
		$this->join(' Inner Join '.$this->tablePrefix.'wx_type b On a.TypeID = b.TypeID and b.IsEnable=1 and b.IsReply=1');
		$where = "a.ReplyTypeID=3";
		if( $IsEnable != -1){
			$IsEnable = intval($IsEnable);
			$where .= " and a.IsEnable={$IsEnable}";
		}
		$result = $this->where($where)->order('a.Priority asc,a.ReplyID desc')->select();
		return $result;
	}
	
	function findKeywordReply($ReplyID){
		$ReplyID = intval($ReplyID);
		$data = $this->find($ReplyID);
		return $data;
	}
	
	function getKeywordReplyCount($IsEnable = -1){
		$where = "ReplyTypeID=3";
		if( $IsEnable != -1){
			$IsEnable = intval($IsEnable);
			$where .= " and IsEnable={$IsEnable}";
		}
		$n = $this->where($where)->count();
		return $n;
	}
	
	function batchDelKeywordReply( $id = array() ){
		$id = YdInput::filterCommaNum($id);
		$where = 'ReplyID in('.implode(',', $id).')';
		$result = $this->where($where)->delete();
		return $result;
	}
	
	//批量排序
	function batchSortKeywordReply($ReplyID=array(), $Priority = array() ){
		$n = count($ReplyID);
		for($i = 0; $i < $n; $i++){
			if( is_numeric($Priority[$i]) ){
				$id = intval($ReplyID[$i]);
				$this->where("ReplyID={$id}")->setField('Priority', $Priority[$i]);
			}
		}
	}
	
	//$needCount: 是否需要统计计数
	function findReply($keyword, $needCount = false){
		//$data = $this->where("Keyword=$keyword and IsEnable=1")->find();
		//使用FIND_IN_SET支持多关键词查询
		$keyword = addslashes(stripslashes($keyword));
        $keyword = YdInput::checkKeyword( $keyword );
		$where = "FIND_IN_SET('{$keyword}', Keyword) and IsEnable=1";
		$data = $this->where($where)->find();
		return $data;
	}
	
	//记录关键词实用次数
	function incKeyword($keyword){
		$keyword = addslashes(stripslashes($keyword));
        $keyword = YdInput::checkKeyword( $keyword );
		$where = "FIND_IN_SET('{$keyword}', Keyword) and IsEnable=1";
		return $this->where($where)->setInc('Count'); //增加计数
	}
	
	//次数清零
	function zeroKeywordCount($replyID){
		$replyID = intval($replyID);
		$result = $this->where("ReplyID={$replyID}")->setField('Count', 0);
		return $result;
	}
}

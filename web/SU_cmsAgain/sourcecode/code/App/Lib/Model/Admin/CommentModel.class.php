<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class CommentModel extends Model{
	protected $_auto = array(
			array('LanguageID', 'get_language_id', 1, 'function'),
	);
	
	function getComment($offset = -1, $length = -1, $p=array()){
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);	
		}
		$where = '';
		if( isset($p['LanguageID']) && $p['LanguageID'] != -1){
			$where .= 'a.LanguageID = '.intval($p['LanguageID']);
		}else{
			$where .= get_language_where('a');
		}
		
		if( isset($p['CommentID']) && $p['CommentID'] != -1){
			$where .= " and a.CommentID=".intval($p['CommentID']);
		}
		
		if( isset($p['CommentRank']) && $p['CommentRank'] != -1){
			$where .= " and a.CommentRank=".intval($p['CommentRank']);
		}
		
		if( isset($p['GuestID']) && $p['GuestID'] != -1){
			$where .= " and GuestID=".intval($p['GuestID']);
		}
		
		if( isset($p['InfoID']) && $p['InfoID'] != -1){
			$where .= " and InfoID=".intval($p['InfoID']);
		}
		
		if( isset($p['IsCheck']) && $p['IsCheck'] != -1){
			$where .= " and a.IsCheck=".intval($p['IsCheck']);
		}
		
		if( isset($p['Parent']) && $p['Parent'] != -1){
			$where .= " and a.Parent=".intval($p['Parent']);
		}
		
		if( isset($p['SearchWords']) && !empty( $p['SearchWords']) ){
			$SearchWords = addslashes( stripslashes($p['SearchWords']) );
            $SearchWords = YdInput::checkKeyword($SearchWords);
			$where .= " and (GuestName='$SearchWords' or CommentContent like '%{$SearchWords}%' or GuestIP = '$SearchWords') ";
		}
		
		$this->field('b.MemberAvatar, a.*');
		$this->table($this->tablePrefix.'comment a');
		$this->join($this->tablePrefix.'member b On a.GuestID = b.MemberID');
		$result = $this->where($where)->order('CommentID desc')->select();
		if( !empty($result)){
			$n = is_array($result) ? count($result) : 0;
			for($i=0; $i < $n; $i++){
				$result[$i]['CommentRankText'] = $this->getCommentRankText( $result[$i]['CommentRank'] );
				$result[$i]['MemberAvatar'] = DefaultAvatar($result[$i]['MemberAvatar']);
				if( isset($p['ReplyComments']) && $p['ReplyComments'] != -1){
					$p1['Parent'] = $result[$i]['CommentID'];
					$p1['LanguageID'] = $result[$i]['LanguageID'];
					$p1['IsCheck'] = 1;
					$result[$i]['ReplyComments'] = $this->getComment(-1, -1, $p1);
					$result[$i]['ReplyCount'] = is_array($result[$i]['ReplyComments']) ? count($result[$i]['ReplyComments']) : 0;
				}
			}
		}
		return $result;
	}
	
	function getCommentCount($p=array()){
		$where = '';
		if( isset($p['LanguageID']) && $p['LanguageID'] != -1){
			$where .= 'LanguageID = '.intval($p['LanguageID']);
		}else{
			$where .= get_language_where();
		}
		
		if( isset($p['CommentRank']) && $p['CommentRank'] != -1){
			$where .= " and CommentRank=".intval($p['CommentRank']);
		}
		
		if( isset($p['GuestID']) && $p['GuestID'] != -1){
			$where .= " and GuestID=".intval($p['GuestID']);
		}
		
		if( isset($p['InfoID']) && $p['InfoID'] != -1){
			$where .= " and InfoID=".intval($p['InfoID']);
		}
		
		if( isset($p['IsCheck']) && $p['IsCheck'] != -1){
			$where .= " and IsCheck=".intval($p['IsCheck']);
		}
		
		if( isset($p['Parent']) ){
			$where .= " and Parent=".intval($p['Parent']);
		}
		
		if( isset($p['SearchWords']) && !empty( $p['SearchWords']) ){
			$SearchWords = addslashes( stripslashes($p['SearchWords']) );
            $SearchWords = YdInput::checkKeyword($SearchWords);
			$where .= " and (GuestName='$SearchWords' or CommentContent like '%{$SearchWords}%' or GuestIP = '$SearchWords') ";
		}
		$n = $this->where($where)->count();
		return $n;
	}

	/**
	 * 统计信息评论等级
	 * @param int $InfoID
	 * @param int $CommentRank
	 * @param int $LanguageID
	 */
	function statCommenRank($InfoID, $CommentRank = -1, $LanguageID=-1){
		if($LanguageID != -1){
			$where['LanguageID'] = intval($LanguageID);
		}else{
			$where = get_language_where_array();
		}
		if( $InfoID != -1){
			$where['InfoID'] = intval($InfoID);
		}
		if( $CommentRank != -1){
			$where['CommentRank'] = intval($CommentRank);
		}
		$where['IsCheck'] = 1;
		$where['Parent'] = 0; //只获取1级评论
		$result = $this->where($where)->group('CommentRank')->getField('CommentRank,count(CommentID)');
		return $result;
	}
	
	function getCommentRankText($CommentRank=3){
        $CommentRank = intval($CommentRank);
		switch($CommentRank){
			case 1:
				$str = L('NegativeComment');
				break;
			case 2:
				$str = L('NeutralComment');
				break;
			default:
				$str = L('PositiveComment');
		}
		return $str;
	}
	
	function delComment( $id = array(),  $p = array()){
		if( is_array($id) ){
			$id = YdInput::filterCommaNum($id);
			if( count($id) <= 0 )  return false;
			$idlist = implode(',', $id);
			if( isset($p['GuestID']) && $p['GuestID'] != -1){
				$GuestID = intval($p['GuestID']);
				$where = "(CommentID in ({$idlist})  and GuestID=$GuestID) or Parent in ({$idlist})";
			}else{
				$where = "(CommentID in ({$idlist}) or Parent in ({$idlist}) )";
			}
		}else{
			if( !is_numeric($id) ) return false;
			if( isset($p['GuestID']) && $p['GuestID'] != -1){
				$GuestID = intval($p['GuestID']);
				$where = "(CommentID=$id and GuestID=$GuestID) or Parent=$id";
			}else{
				$where = "(CommentID=$id or Parent=$id)";
			}
		}
		$result = $this->where($where)->delete();
		return $result;
	}
	
	function batchCheckComment( $id = array() , $Check = 0){
		$id = YdInput::filterCommaNum($id);
		$where['CommentID']  = array('in', implode(',', $id));
		if( $Check != 0 ) $Check = 1;
		$result = $this->where($where)->setField('IsCheck', $Check);
		if( $result === false ){
			return false;
		}else{
			return true;
		}
	}
	
}

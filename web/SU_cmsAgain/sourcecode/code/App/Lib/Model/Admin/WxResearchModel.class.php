<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class WxResearchModel extends Model {
	protected $_validate = array();
	//获取某个问题的投票人数
	function getPeopleNumber($appID, $questionID=false){
		$appID = intval($appID);
		$where = "AppID={$appID}";
		if( is_numeric($questionID) ){
			$where .= " and QuestionID={$questionID}";
		}
		$n = $this->where($where)->count('distinct FromUser');
		return $n;
	}
	
	//获得某个问题的总票数
	function getTotalCount($appID, $questionID=false){
		$appID = intval($appID);
		$where = "AppID={$appID}";
		if( is_numeric($questionID) ){
			$where .= " and QuestionID={$questionID}";
		}
		$n = $this->where($where)->count();
		return $n;
	}
	
	//获取某个问题某项的票数
	function GetResearchCount($appID, $questionID, $itemID){
		$appID = intval($appID);
		$questionID = intval($questionID);
		$itemID = intval($itemID);
		$where = "AppID={$appID} and QuestionID={$questionID} and ItemID={$itemID}";
		$n = $this->where($where)->count();
		return $n;
	}
	
	//删除某个问题的所有投票记录
	function delResearch($appID=false, $questionID=false){
		$appID = YdInput::filterCommaNum($appID);
		$where = "1=1";
		if( is_array($appID)){
			$where .= ' and AppID in('.implode(',', $appID).')';
		}else if($appID!==false){
			$where .= " and AppID={$appID}";
		}
		if( is_numeric($questionID) ){
			$where .= " and QuestionID={$questionID}";
		}
		$result = $this->where($where)->delete();
		return $result;
	}
	
	//如果$fromUser为空，则获取当前IP作为$fromUser
	function submitResearch($appid,  $questionID, $itemID, $fromUser) {
        $fromUser = YdInput::checkLetterNumber($fromUser);
		$appid = intval($appid);
		$questionID = intval($questionID);
		$hasVoted = $this->hasVoted($appid, $fromUser, $questionID);
		if( $hasVoted ) return false;  //投过票了，不能重复投票
		if(empty($appid) || empty($fromUser)) return false;
		$time = date('Y-m-d H:i:s');
		$item = (array)$itemID;
        $data = array();
		foreach ($item as $it){
			$data[] = array(
					'AppID'=>$appid,
					'QuestionID'=>$questionID,
					'ItemID'=>intval($it),
					'FromUser'=>$fromUser,
					'ResearchTime'=>$time,
			);
		}
		//批量插入
		$result = $this->addAll($data);
		return $result;
	}
	
	//判断是否投过票
	function hasVoted($appid, $fromUser, $questionID=false){
        $fromUser = YdInput::checkLetterNumber($fromUser);
		$where['AppID'] = intval($appid);
		$where['FromUser'] = $fromUser;
		$where['QuestionID'] = intval($questionID);
		$n = $this->where($where)->count();
		if($n>0){
			return true;
		}else{
			return false;
		}
	}
}
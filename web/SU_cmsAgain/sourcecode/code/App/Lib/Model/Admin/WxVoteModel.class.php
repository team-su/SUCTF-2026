<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class WxVoteModel extends Model {
	protected $_validate = array();
	//获取投票人数
	function getPeopleNumber($appID){
		$appID = intval($appID);
		$where = "AppID={$appID} ";
		$n = $this->where($where)->count('distinct FromUser');
		return $n;
	}
	
	//获得总票数
	function getTotalCount($appID){
		$appID = intval($appID);
		$where = "AppID={$appID} ";
		$n = $this->where($where)->count();
		return $n;
	}
	
	//获取某项的票数
	function GetVoteCount($appID, $itemID){
		$appID = intval($appID);
		$itemID = intval($itemID);
		$where = "AppID={$appID} and ItemID={$itemID}";
		$n = $this->where($where)->count();
		return $n;
	}
	
	function delVote($appID){
		$appID = YdInput::filterCommaNum($appID);
		if( is_array($appID)){
			$where = 'AppID in('.implode(',', $appID).')';
		}else{
			$where = "AppID={$appID}";
		}
		return $this->where($where)->delete();
	}
	
	//如果$fromUser为空，则获取当前IP作为$fromUser
	function submitVote($appid, $itemID, $fromUser) {
        $appid = intval($appid);
        $fromUser = YdInput::checkLetterNumber($fromUser);
		if(empty($appid) || empty($fromUser)) return false;
		$votetime = date('Y-m-d H:i:s');
		$item = (array)$itemID;
        $data = array();
		foreach ($item as $it){
			$data[] = array(
			    'AppID'=>$appid,
                'ItemID'=>(int)$it,
                'FromUser'=>$fromUser,
                'VoteTime'=>$votetime,
			);
		}
		//批量插入
		$result = $this->addAll($data);
		return $result;
	}
	
	//判断是否投过票
	function hasVoted($appid, $fromUser){
        $fromUser = YdInput::checkLetterNumber($fromUser);
		$where['AppID'] = intval($appid);
		$where['FromUser'] = $fromUser;
		$n = $this->where($where)->count();
		if($n>0){
			return true;
		}else{
			return false;
		}
	}
}
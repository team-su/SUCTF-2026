<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class ToupiaoListModel extends Model{
	//判断是否已经投了票
	function HasVoted($fromUser){
        $fromUser = YdInput::checkLetterNumber($fromUser);
		$where['WxID'] = $fromUser;
		$n= $this->where($where)->count();
		if($n>0){
			return true;
		}else{
			return false;
		}
	}
	
	//保存投票
	function SaveVote($fromUser, $voteNumber){
        $fromUser = YdInput::checkLetterNumber($fromUser);
		$data['WxID'] = $fromUser;
		$data['VoteNumber'] = $voteNumber;
		return $this->data($data)->add();
	}
	
	//删除所有投票记录
	function delAll(){
		$this->where("TpID !='' ")->delete();
	}
}

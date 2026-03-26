<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class ToupiaoModel extends Model{
	//参选
	function Join($fromUser){
	    $fromUser = YdInput::checkLetterNumber($fromUser);
		$where['WxID'] = $fromUser;
		$VoteNumber = $this->where($where)->getField('VoteNumber');
		if( !empty($VoteNumber) ) return $VoteNumber;
		//不存在则插入
		$MaxNumber = $this->max('VoteNumber'); //获取最大的投票号
		if( empty($MaxNumber) ) $MaxNumber = 100; //从100号开始
		$MyNumber = $MaxNumber + 1;
		$data['WxID'] = $fromUser;
		$data['VoteCount'] = 0;
		$data['VoteNumber'] = $MyNumber;
		$data['VoteTime'] = date('Y-m-d H:i:s');
		$b = $this->data($data)->add();
		if( $b !== false ){
			return $MyNumber;
		}else{
			return false;
		}
	}
	
	//判断是否是候选人
	function IsVoter($fromUser){
        $fromUser = YdInput::checkLetterNumber($fromUser);
		$where['WxID'] = $fromUser;
		$n= $this->where($where)->count();
		if($n>0){
			return true;
		}else{
			return false;
		}
	}
	
	//获取票数
	function GetVoteCount( $fromUser ){
        $fromUser = YdInput::checkLetterNumber($fromUser);
		$where['WxID'] = $fromUser;
		$n = $this->where($where)->getField('VoteCount');
		return $n;
	}
	
	//获取排名
	function GetPlace( $fromUser ){
        $fromUser = YdInput::checkLetterNumber($fromUser);
		$where['WxID'] = $fromUser;
		$VoteCount = $this->where($where)->getField('VoteCount');
		$place = $this->where("VoteCount>{$VoteCount}")->count();
		return $place+1;
	}
	
	//获取排行榜
	function GetRank($offset, $len){
		$offset = intval($offset);
		$len = intval($len);
		$this->field('WxID,VoteNumber,VoteCount')->order("VoteCount desc");
		$data = $this->limit("{$offset},{$len}")->select();
		return $data;
	}
	
	//投票
	function Vote($fromUser, $voteNumber){
        $fromUser = YdInput::checkLetterNumber($fromUser);
		$where['WxID'] = $fromUser;
		$where['VoteNumber'] = $voteNumber;
		return $this->where($where)->setInc('VoteCount');
	}
	
	function getCount($voteNumber){
		$where = "1=1";
		if( $voteNumber != ''){
			$voteNumber = addslashes(stripslashes($voteNumber));
            $voteNumber = YdInput::checkKeyword($voteNumber);
			$where .= " and VoteNumber like '%{$voteNumber}%'";
		}
		$n = $this->where($where)->count();
		return $n;
	}
	
	function getTouPiao($offset = -1, $length = -1, $voteNumber=''){
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}
		$where = "1=1";
		if( $voteNumber != ''){
			$voteNumber = addslashes(stripslashes($voteNumber));
            $voteNumber = YdInput::checkKeyword($voteNumber);
			$where .= " and VoteNumber like '%{$voteNumber}%'";
		}
		$result = $this->where($where)->order('VoteCount desc')->select();
		return $result;
	}
	
	function batchDelTouPiao( $id = array()){
		$id = YdInput::filterCommaNum($id);
		$where = 'VoteID in('.implode(',', $id).')';
		$result = $this->where($where)->delete();
		return $result;
	}
	
	//批量排序
	function batchSaveCount($VoteID=array(), $VoteCount = array() ){
		$n = count($VoteID);
		for($i = 0; $i < $n; $i++){
			if( is_numeric($VoteID[$i]) ){
				$this->where("VoteID={$VoteID[$i]}")->setField('VoteCount', $VoteCount[$i]);
			}
		}
	}
	
	//删除所有投票
	function delAllTouPiao(){
		$this->where("VoteID !='' ")->delete();
	}
	
}

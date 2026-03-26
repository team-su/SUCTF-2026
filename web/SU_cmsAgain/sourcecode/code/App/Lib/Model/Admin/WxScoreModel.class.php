<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */
class WxScoreModel extends Model {
	protected $_validate = array();
	
	//赠送积分
	function give($memberID, $score, $remark=false, $time=false){
		return $this->_addScore($memberID, $score, 1, $remark, $time);
	}
	
	//消费金额赠送积分
	function expend($memberID, $score, $remark=false, $time=false){
		return $this->_addScore($memberID, $score, 3, $remark, $time);
	}
	
	//增加积分记录 $type 1:赠送 2:签到 3:消费 4:礼品兑换
	private function _addScore($memberID, $score, $type = 1, $remark=false, $time=false){
		if( !is_numeric($score) && $score !== 0 ) return false;
		$data['MemberID']=intval($memberID);
		$data['ScoreType']=intval($type);
		$data['ScoreNumber']=$score;
		$data['ScoreTime']=empty($time) ? date('Y-m-d H:i:s') : $time;
		//$remark不能为false,否则生成sql有问题,见下
		//INSERT INTO `wx_score` (`MemberID`,`ScoreTime`,`Remark`) VALUES ('8','2014-03-19 18:09:18',)
		$data['Remark'] = ($remark===false) ? '' : $remark; 
		$result = $this->add($data);
		return $result;
	}
	
	//获取总积分  ScoreType 1:赠送 2:签到 3:消费 4:礼品兑换
	function getTotal($id){
		$where = "ScoreType!=4";
		if($id != -1 ){
			$id = intval($id);
			$where .= " and MemberID={$id}";
		}
		$n = $this->where($where)->sum('ScoreNumber');
		if(empty($n)) $n = 0;
		return $n;
	}
	
	//获取签到总积分
	function getCheckinScore($id){
		$where = "ScoreType=2";
		$id = intval($id);
		$where .= " and MemberID ={$id}";
		$n = $this->where($where)->sum('ScoreNumber');
		if(empty($n)) $n = 0;
		return $n;
	}
	
	//获取某个时间的签到情况,留空表示当天是否签到
	function getCheckin($memberID, $y, $m, $d=false){
        $y = YdInput::checkNum($y);
        $m = YdInput::checkNum($m);
		if($d===false){
			//返回当月的数据
			$last = yd_month_lastday($m, $y);
			$start = "$y-$m-01 00:00:00";
			$end = "$y-$m-$last 23:59:59";
		}else{
			//返回当天的数据
			$start = "$y-$m-$d 00:00:00";
			$end = "$y-$m-$d 23:59:59";
		}
		$memberID = intval($memberID);
		$where = "ScoreType=2 and MemberID={$memberID} and ScoreTime>='{$start}' and ScoreTime<='{$end}'";
		$result = $this->field('ScoreNumber')->where($where)->find();
		return $result;
	}
	
	//处理客户当日签到
	function checkIn($memberID){
		$memberID = intval($memberID);
		$start = date("Y-m-d 00:00:00");
		$end = date("Y-m-d 23:59:59");
		$where = "ScoreType=2 and MemberID={$memberID} and ScoreTime>='{$start}' and ScoreTime<='{$end}'";
		$n = $this->where($where)->count();
		//仅处理没有签到的情况
		if($n==0){
			$m = D('Admin/WxApp');
			$card = $m->findCardConfig();
			$award = is_numeric($card['SignAward']) ? $card['SignAward'] : 0;
			$data = array(
				'MemberID'=>$memberID,
				'ScoreTime'=>date('Y-m-d H:i:s'),
				'ScoreNumber'=>$award,
				'ScoreType'=>2,
			);
			$this->add($data);
		}
		return true;
	}
	
	//已消费积分   ScoreType 1:赠送 2:签到 3:消费 4:礼品兑换
	function getUsed($id){
		$where = "ScoreType=4";
		if($id != -1 ){
			$id = intval($id);
			$where .= " and MemberID={$id}";
		}
		$n = $this->where($where)->sum('ScoreNumber');
		if(empty($n)) $n = 0;
		return $n;
	}
	
	//获取礼品被领取次数
	function GetGiftUsed($GiftID){
		$GiftID = intval($GiftID);
		$where = "ScoreType=4 and RelationID={$GiftID}";
		$n = $this->where($where)->count();
		return $n;
	}
	
	function getScore($offset = -1, $length = -1, $keywords=''){
		$this->field('a.*,b.MemberRealName,b.CardNumber,b.MemberMobile,b.MemberGender');
		$this->table($this->tablePrefix.'wx_score a');
		$this->join('Inner Join '.$this->tablePrefix.'member b On a.MemberID = b.MemberID');
	
		$where = "b.CardNumber != '' ";
		if( $keywords != ''){
			$keywords = addslashes(stripslashes($keywords));
            $keywords = YdInput::checkKeyword( $keywords );
			$where .= " and (b.CardNumber like '%{$keywords}%' or b.MemberName like '%{$keywords}%' or b.MemberMobile like '%{$keywords}%' or b.MemberRealName like '%{$keywords}%') ";
		}
	
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}
		$result = $this->where($where)->order('a.ScoreID desc')->select();
		return $result;
	}
	
	//获取我的积分记录，主要用于会员卡
	function getMyScore($memberID){
		$memberID = intval($memberID);
		$where = "MemberID={$memberID}";
		$data = $this->where($where)->order('ScoreID desc')->select();
		return $data;
	}
	//获取我的兑换记录，主要用于会员卡
	function getMyExchange($memberID){
		$memberID = intval($memberID);
		$where = "MemberID={$memberID} and ScoreType=4";
		$data = $this->where($where)->order('ScoreID desc')->select();
		return $data;
	}
	
	//按礼品查看会员记录
	function getGiftMember($offset = -1, $length = -1, $GiftID=-1){
		$this->field('a.*,b.MemberRealName,b.CardNumber,b.MemberMobile,b.MemberGender');
		$this->table($this->tablePrefix.'wx_score a');
		$this->join('Inner Join '.$this->tablePrefix.'member b On a.MemberID = b.MemberID');
	
		$where = "a.ScoreType =4 ";
		if( $GiftID != -1){
			$GiftID = intval($GiftID);
			$where .= " and a.RelationID={$GiftID}";
		}
	
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}
		$result = $this->where($where)->order('a.ScoreID desc')->select();
		return $result;
	}
	
	function getGiftMemberCount($GiftID=-1){
		$this->table($this->tablePrefix.'wx_score a');
		$this->join('Inner Join '.$this->tablePrefix.'member b On a.MemberID = b.MemberID');
		$where = "a.ScoreType = 4 ";
		if( $GiftID != -1){
			$GiftID = intval($GiftID);
			$where .= " and a.RelationID={$GiftID}";
		}
		$result = $this->where($where)->count();
		return $result;
	}
	
	//获取模糊查询个数
	function getCount($keywords=''){
		$this->table($this->tablePrefix.'wx_score a');
		$this->join('Inner Join '.$this->tablePrefix.'member b On a.MemberID = b.MemberID');
		$where = "b.CardNumber != '' ";
		if( $keywords != ''){
			$keywords = addslashes(stripslashes($keywords));
            $keywords = YdInput::checkKeyword( $keywords );
			$where .= " and (b.CardNumber like '%{$keywords}%' or b.MemberName like '%{$keywords}%' or b.MemberMobile like '%{$keywords}%' or b.MemberRealName like '%{$keywords}%') ";
		}
		$n = $this->where($where)->count();
		return $n;
	}
}
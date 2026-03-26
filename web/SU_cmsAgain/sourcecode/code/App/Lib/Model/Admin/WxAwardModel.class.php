<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */
class WxAwardModel extends Model {
	protected $_validate = array();
	
	//获取中奖者名单
	function getAward($offset = -1, $length = -1, $appID=0, $mobile='', $awardSN='', $isCheck=-1){
		$this->field('b.MemberRealName,b.MemberMobile,b.MemberGender, a.*');
		$this->table($this->tablePrefix.'wx_award a');
		$this->join(' left join '.$this->tablePrefix.'member b On a.FromUser = b.FromUser');
		
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}
		$appID = intval($appID);
		$where = "a.AwardStatus=1 and a.AwardNumber!=0 and a.AppID=$appID";
		if( $isCheck != -1){
			$isCheck = intval($isCheck);
			$where .= " and a.IsCheck={$isCheck}";
		}
		if( $mobile != ''){
			$mobile = addslashes(stripslashes($mobile));
            $mobile = YdInput::checkKeyword($mobile);
			$where .= " and b.MemberMobile like '%{$mobile}%'";
		}
		if( $awardSN != ''){
			$awardSN = addslashes(stripslashes($awardSN));
            $awardSN = YdInput::checkKeyword($awardSN);
			$where .= " and a.AwardSN like '%{$awardSN}%'";
		}
		$result = $this->where($where)->order('a.AwardID desc')->select();
		return $result;
	}
	
	//获取用户中奖情况
	function getUserAward($userName){
		$userName = addslashes(stripslashes($userName));
        $userName = YdInput::checkKeyword($userName);
		$where = "a.AwardStatus=1 and a.AwardNumber!=0 and a.FromUser='{$userName}'";
		$this->field('b.AppName,b.AppParameter,a.AwardNumber,a.AwardSN,a.AwardTime,a.IsCheck');
		$this->table($this->tablePrefix.'wx_award a');
		$this->join('Inner join '.$this->tablePrefix.'wx_app b On a.AppID = b.AppID');
		$result = $this->where($where)->select();
		return $result;
	}
	
	function getAwardCount($appID, $mobile=''){
		$appID = intval($appID);
		$where = "AwardStatus=1 and AwardNumber!=0 and AppID={$appID}";
		if( $mobile != ''){
			$mobile = addslashes(stripslashes($mobile));
            $mobile = YdInput::checkKeyword($mobile);
			$where .= " and Mobile like '%{$mobile}%'";
		}
		$n = $this->where($where)->count();
		return $n;
	}
	
	function checkAward( $awardID ){
		$awardID = intval($awardID);
		$result = $this->where("AwardID={$awardID}")->setField('IsCheck', 1);
		return $result;
	}
	
	
	//设置为已抽奖状态
	function registerLottery($fromUser, $sn){
        $fromUser = YdInput::checkLetterNumber($fromUser);
		if( empty($fromUser)) return false;
		$where['FromUser'] = $fromUser;
		$where['AwardSN'] = $sn;
		$result = $this->where($where)->setField('AwardStatus',1);
		return $result;
	}
	
	//登记用户手机号码, 商户兑奖，姓名
	function registerMobile($fromUser, $mobile, $sn, $username=false){
        $fromUser = YdInput::checkLetterNumber($fromUser);
		if( empty($fromUser)) return false;
		//$where = "FromUser='$fromUser' and AwardSN='$sn'";
		//$result = $this->where($where)->setField('Mobile',$mobile);
		//中奖后，将用户姓名和手机号码存放到会员表，而不是Award
		$m = D('Admin/Member');
		$data['MemberMobile']=$mobile;
		if(!empty($username)){
			$data['MemberRealName']=$username;
		}
		$where['FromUser'] = $fromUser;
		$result = $m->where($where)->setField($data);
		return $result;
	}
	
	//商家登记抽奖
	function checkLottery($fromUser, $sn){
        $fromUser = YdInput::checkLetterNumber($fromUser);
		if( empty($fromUser)) return false;
		$where['FromUser'] = $fromUser;
		$where['AwardSN'] = $sn;
		$result = $this->where($where)->setField('IsCheck',1);
		return $result;
	}
	
	//获取用户抽奖次数
	function getLotteryNumber($fromUser, $appID, $AwardStatus=1, $today=0){
		$appID = intval($appID);
        $fromUser = YdInput::checkLetterNumber($fromUser);
		$where = "AppID={$appID} and FromUser='{$fromUser}' and AwardStatus=1";
		if($today == 1){ //仅获取今天的抽奖次数
			//DATEDIFF函数用于返回2日期之间的天数
			$where .= " and DATEDIFF(Now(),AwardTime)= 0";
		}
		$n = $this->where($where)->count();
		return $n;
	}
	
	//获取参与活动人数（$AwardStatus 1：有效参与人数，0：浏览人数)
	function getPeopleNumber($appID, $AwardStatus=1){
		$where['AppID'] = intval($appID);
		$where['AwardStatus'] = intval($AwardStatus);
		$n = $this->where($where)->count();
		return $n;
	}
	
	//获取中奖人数
	function getWinPeopleNumber($appID){
		$appID = intval($appID);
		$where = "AppID={$appID} and AwardNumber!=0 and AwardStatus=1";
		$n = $this->where($where)->count();
		return $n;
	}
	
	//获取中奖奖品数, $award取值：1一等奖，2：二等奖，3：三等奖
	function getAwardWinNumber($appID, $awardNumber=1){
		$appID = intval($appID);
		$awardNumber = intval($awardNumber);
		$where = "AppID={$appID} and AwardNumber={$awardNumber} and AwardStatus=1";
		$n = $this->where($where)->count();
		return $n;
	}
	
	//登记抽奖
	function addAward($fromUser, $appID, $awardNumber, $awardSN, $awardStatus=0, $isCheck=0, $time=-1){
        $fromUser = YdInput::checkLetterNumber($fromUser);
		if( empty($fromUser)) return false;
		$data['FromUser'] = $fromUser;
		$data['AppID'] = intval($appID);
		$data['AwardNumber'] = intval($awardNumber);
		
		$data['AwardSN'] = $awardSN;
		$data['AwardStatus'] = intval($awardStatus);
		$data['IsCheck'] = intval($isCheck);
		$data['AwardTime'] = ( $time == -1 ) ? date("Y-m-d H:i:s") : $time;  //抽奖时间
		$result = $this->add($data);
		return $result;
	}

	//删除当前抽奖信息
	function deleteAward($appID){
		$appID = intval($appID);
		return $this->where("AppID={$appID}")->delete();
	}
}
<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */
class WxConsumeModel extends Model {
	protected $_validate = array();

	function getConsume($offset = -1, $length = -1, $keywords=''){
		$this->field('a.*,b.MemberRealName,b.CardNumber,b.MemberMobile,b.MemberGender');
		$this->table($this->tablePrefix.'wx_consume a');
		$this->join('Inner Join '.$this->tablePrefix.'member b On a.MemberID = b.MemberID');
	
		$where = "b.CardNumber != '' ";
		if( $keywords != ''){
			$keywords = addslashes(stripslashes($keywords));
            $keywords = YdInput::checkKeyword( $keywords );
			$where .= " and (b.CardNumber like '%$keywords%' or b.MemberName like '%$keywords%' or b.MemberMobile like '%$keywords%' or b.MemberRealName like '%$keywords%') ";
		}
	
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}
		$result = $this->where($where)->order('a.ConsumeID desc')->select();
		return $result;
	}
	
	//获取我的积分记录，主要用于会员卡
	function getMyConsume($memberID){
		$memberID = intval($memberID);
		$where = "MemberID={$memberID}";
		$data = $this->where($where)->order('ConsumeID desc')->select();
		return $data;
	}
	
	
	//会员充值
	function pay($memberID, $consumeMoney, $remark=false, $time=false){
		if( !is_numeric($consumeMoney) && $consumeMoney !== 0 ) return false;
		$data['MemberID']=intval($memberID);
		$data['ConsumeType']=1;
		$data['ConsumeMoney']= $consumeMoney;
		$data['ConsumeTime']=empty($time) ? date('Y-m-d H:i:s') : $time;
		$data['Remark']=$remark;
		$result = $this->add($data);
		return $result;
	}
	
	//获取充值总金额
	function getTotal($memberID){
		return $this->_getNum($memberID, 1);
	}
	
	//已消费金额
	function getUsed($memberID){
		return $this->_getNum($memberID, 2);
	}
	
	//获取会员余额
	function getUnUsed($memberID){
		$total = $this->_getNum($memberID, 1);
		$used = $this->_getNum($memberID, 2);
		$left = $total - $used;
		return $left;
	}
	
	//$memberID＝－1：表示统计所有的，1:充值  2:额余消费 3:现金消费
	private function _getNum($memberID=-1, $type=1){
		$type = intval($type);
		$where = "ConsumeType={$type}";
		if($memberID != -1 ){
			$memberID = intval($memberID);
			$where .= " and MemberID={$memberID}";
		}
		$n = $this->where($where)->sum('ConsumeMoney');
		if(empty($n)) $n = 0;
		return $n;
	}
	
	//获取模糊查询个数
	function getCount($keywords=''){
		$this->table($this->tablePrefix.'wx_consume a');
		$this->join('Inner Join '.$this->tablePrefix.'member b On a.MemberID = b.MemberID');
		$where = "b.CardNumber != '' ";
		if( $keywords != ''){
			$keywords = addslashes(stripslashes($keywords));
            $keywords = YdInput::checkKeyword( $keywords );
			$where .= " and (b.CardNumber like '%$keywords%' or b.MemberName like '%$keywords%' or b.MemberMobile like '%$keywords%' or b.MemberRealName like '%$keywords%') ";
		}
		$n = $this->where($where)->count();
		return $n;
	}
	
	//按礼品查看会员记录
	function getCouponMember($offset = -1, $length = -1, $CouponID=-1){
		$this->field('a.*,b.MemberRealName,b.CardNumber,b.MemberMobile,b.MemberGender');
		$this->table($this->tablePrefix.'wx_consume a');
		$this->join('Inner Join '.$this->tablePrefix.'member b On a.MemberID = b.MemberID');
	
		$where = "a.ConsumeType in(2,3) ";
		if( $CouponID != -1){
			$CouponID = intval($CouponID);
			$where .= " and a.RelationID={$CouponID}";
		}
	
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}
		$result = $this->where($where)->order('a.ConsumeID desc')->select();
		return $result;
	}
	
	//获取礼品被领取次数
	function GetCouponUsed($CouponID){
		$CouponID = intval($CouponID);
		$where = "ConsumeType in(2,3)  and RelationID={$CouponID}";
		$n = $this->where($where)->count();
		return $n;
	}
	
	function getCouponMemberCount($CouponID=-1){
		$this->table($this->tablePrefix.'wx_consume a');
		$this->join('Inner Join '.$this->tablePrefix.'member b On a.MemberID = b.MemberID');
		$where = "a.ConsumeType in (2,3) ";
		if( $CouponID != -1){
			$CouponID = intval($CouponID);
			$where .= " and a.RelationID={$CouponID}";
		}
		$result = $this->where($where)->count();
		return $result;
	}
}
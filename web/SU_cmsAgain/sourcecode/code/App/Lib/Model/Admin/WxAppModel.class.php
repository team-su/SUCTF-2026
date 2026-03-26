<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */
class WxAppModel extends Model {
	protected $_validate = array();
	function getApp($offset = -1, $length = -1, $appTypeID = -1, $keywords='', $isEnable=-1){
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}
		$where = "1=1";
		if( $appTypeID != -1){
			$appTypeID = intval($appTypeID);
			$where .= " and a.AppTypeID=$appTypeID";
		}
		if( $keywords != ''){
			$keywords = addslashes(stripslashes($keywords));
            $keywords = YdInput::checkKeyword($keywords);
			$where .= " and a.AppName like '%$keywords%'";
		}
		if( $isEnable != -1 ){
			$isEnable = intval($isEnable);
			$where .= " and a.IsEnable=$isEnable";
		}
		$this->field('b.AppTypeName, a.*');
		$this->table($this->tablePrefix.'wx_app a');
		$this->join('Inner join '.$this->tablePrefix.'wx_apptype b On a.AppTypeID = b.AppTypeID');
		$result = $this->where($where)->order('a.AppTypeID,a.AppOrder asc, a.AppID desc')->select();
		return $result;
	}
	
	function findApp($appid, $IsEnable=-1){
		$appid = intval($appid);
		$where = "AppID={$appid}";
		if($IsEnable!=-1){
			$where .= " and IsEnable={$IsEnable}";
		}
		$p = $this->where($where)->find();
		parseAppParameter($p['AppParameter'], $p['AppTypeID'], $p);
		return $p;
	}
	
	//获取有url地址的微应用
	function getUrlApp(){
		//3:微工具，4：地理位置服务
		$where = "a.IsEnable=1 and a.AppTypeID!=3 and a.AppTypeID!=4 ";
		$this->table($this->tablePrefix.'wx_app a');
		$this->join('Inner join '.$this->tablePrefix.'wx_apptype b On a.AppTypeID = b.AppTypeID');
		$this->field('a.AppID,a.AppTypeID,a.AppName,b.AppTypeName');
		$data = $this->order('a.AppTypeID,a.AppOrder asc,a.AppID desc')->where($where)->select();
		return $data;
	}
	
	//抽奖 关键词，返回应用 $typeID: 0:大转盘，1：刮刮卡, $appid:用于指定appid,-1表示不指定
	function getLotteryInfo($type = 0, $appid=-1){
		$appid = intval($appid);
		$where = "AppTypeID=1  and IsEnable=1";
		if( $appid == -1){
			$type = addslashes(stripslashes($type));
            $type = YdInput::checkKeyword($type);
			$where .= " and AppParameter like '{$type}%' ";
		}else{
			$where .= " and AppID=$appid";
		}
		$this->field("AppID,AppParameter,AppName");
		$result = $this->where($where)->select();
		return $result;
	}
	
	function getResearch($appid=-1, $IsEnable=-1){
		$where = "AppTypeID=5";
		if($IsEnable!=-1){
			$IsEnable = intval($IsEnable);
			$where .= " and IsEnable={$IsEnable}";
		}
		if( $appid != -1){
			$appid = intval($appid);
			$where .= " and AppID={$appid}";
		}
		$result = $this->where($where)->select();
		$n = is_array($result) ? count($result) : 0;
		for($i=0; $i<$n; $i++){
			parseAppParameter($result[$i]['AppParameter'], $result[$i]['AppTypeID'], $result[$i]);
		}
		return $result;
	}
	
	function getVote($appid=-1, $IsEnable=-1){
		$where = "AppTypeID=2";
		if($IsEnable!=-1){
			$IsEnable = intval($IsEnable);
			$where .= " and IsEnable={$IsEnable}";
		}
		if( $appid != -1){
			$appid = intval($appid);
			$where .= " and AppID={$appid}";
		}
		$result = $this->where($where)->select();
		$n = is_array($result) ? count($result) : 0;
		for($i=0; $i<$n; $i++){
			parseAppParameter($result[$i]['AppParameter'], $result[$i]['AppTypeID'], $result[$i]);
		}
		return $result;
	}
	
	function getCount($appTypeID=-1, $keywords=''){
		$where = "1=1";
		if( $appTypeID != -1){
			$appTypeID = intval($appTypeID);
			$where .= " and AppTypeID={$appTypeID}";
		}
		if( $keywords != ''){
			$keywords = addslashes(stripslashes($keywords));
            $keywords = YdInput::checkKeyword( $keywords );
			$where .= " and AppName like '%{$keywords}%'";
		}
		$n = $this->where($where)->count();
		return $n;
	}

	
	//批量排序
	function batchSortApp($AppID=array(), $AppOrder = array() ){
		$n = count($AppID);
		for($i = 0; $i < $n; $i++){
			if( is_numeric($AppOrder[$i]) ){
				$id = intval($AppID[$i]);
				$this->where("AppID={$id}")->setField('AppOrder', $AppOrder[$i]);
			}
		}
	}
	
	function batchDelApp( $id = array() ){
		$id = YdInput::filterCommaNum($id);
		$where = 'AppID in('.implode(',', $id).')';
		$result = $this->where($where)->delete();
		return $result;
	}
	//========幸运抽奖 start============================================/
	function findLottery($AppID){
		$AppID = intval($AppID);
		$p = $this->find($AppID);
		if( empty($p) ) return false;
		parseAppParameter($p['AppParameter'], $p['AppTypeID'], $p);
		return $p;
	}
	
	//验证商家密码是否正确
	function IsCorrectPwd($appID, $pwd){
		$appID = intval($appID);
		$p = $this->where("AppID={$appID}")->getField('AppParameter');
		$arr = explode('@@@', $p); //20:密码
		if( $pwd == $arr[20] ){
			return true;
		}else{
			return false;
		}
	}
	//========幸运抽奖 end==============================================/
	
	//微投票toupee start==========================
	function findVote($appid, $IsEnable=-1){
		$appid = intval($appid);
		$where = "AppID=$appid";
		if($IsEnable!=-1){
			$IsEnable = intval($IsEnable);
			$where .= " and IsEnable={$IsEnable}";
		}
		$p = $this->where($where)->find();
		//是否多选@@@开始时间@@@结束时间@@@选项@@@图片@@@结果显示
		$para = explode('@@@', $p['AppParameter']);
		$p['IsMultiple'] = $para[0];
		$p['StartTime'] = $para[1];
		$p['EndTime'] = $para[2];
		$p['VotePicture'] = $para[4];
		$p['ShowResult'] = $para[5];
		$temp = (array)explode('$$$', $para[3]);
		foreach ($temp as $it){
			$tt = (array)explode('###', $it);
			$p['Item'][] = array(
					'ItemID'=>$tt[0],
					'ItemName'=>$tt[1]
			);
		}
		return $p;
	}
	
	//用于标签输出
	function findTagVote($appid){
		$appid = intval($appid);
		$where = "AppID={$appid} and IsEnable=1";
		$p = $this->where($where)->find();
		//是否多选@@@开始时间@@@结束时间@@@选项
		$para = (array)explode('@@@', $p['AppParameter']);
		$StartTime = strtotime($para[1]);
		$EndTime = strtotime($para[2]);
		$nNow = time();
		if( $nNow < $StartTime){
			$VoteStatus = 1;  //投票未开始
		}else if( $nNow > $EndTime ){
			$VoteStatus = 2;  //投票已经结束
		}else{
			$VoteStatus = 3;  //投票进行中
		}
		
		$m = D('Admin/WxVote');
		$total = $m->getTotalCount( $appid ); //总票数
		$totalPeople = $m->getPeopleNumber( $appid ); //总人数
		$data[0] = array(
				'VoteName'=>$p['AppName'],
				'VoteDescription'=>$p['AppDescription'],
				'VoteStatus'=>$VoteStatus, 
				'StartTime'=>$para[1], 
				'EndTime'=>$para[2],
				'IsMultiple'=>$para[0],
				'VotePicture'=>$para[4],
				'ShowResult'=>$para[5],
				'Total'=>$total,
				'TotalPeople'=>$totalPeople,
				'Item'=>array(),
		);
		
		$temp = (array)explode('$$$', $para[3]);
		foreach ($temp as $it){
			$tt = (array)explode('###', $it);
			//投票统计
			$VoteCount = $m->GetVoteCount($appid, $tt[0]);
			$VotePercent = round(($VoteCount/$total)*100, 2);
			$data[0]['Item'][] = array(
					'ItemID'=>$tt[0],
					'ItemName'=>$tt[1],
					'Count'=>$VoteCount,
					'Percent'=>$VotePercent,
			);
		}
		return $data;
	}
	//微投票end==========================
	
	//删除微调查项目
	function delResearch($id){
		if(is_array($id)){
			$id = YdInput::filterCommaNum($id);
			$where = 'AppID in('.implode(',', $id).')';
		}else{
			$id = intval($id);
			$where = "AppID=$id";
		}
		$result = $this->where($where)->delete();
		if($result !== false ){
			//删除投票记录
			$m2 = D('Admin/WxResearch');
			$m2->delResearch($id);
			//删除提交的建议
			$m3 = D('Admin/WxSuggest');
			$m3->delSuggest($id);
			//删除问题
			$m1 = D('Admin/WxQuestion');
			$m1->delQuestion($id);
		}
		return $result;
	}

	//微会员卡start==========================
	function findCardConfig($IsEnable=-1){
		$where = "AppTypeID=6";
		if($IsEnable!=-1){
			$IsEnable = intval($IsEnable);
			$where .= " and IsEnable={$IsEnable}";
		}
		$p = $this->where($where)->find();
		if(!empty($p)){
			parseAppParameter($p['AppParameter'], $p['AppTypeID'], $p);
		}
		return $p;
	}
	
	function updateCardConfig($data){
		$AppID = $this->where("AppTypeID=6")->getField('AppID');
		if(empty($AppID)){
			//插入操作
			$result = $this->add($data);
		}else{
			//更新操作
			$data['AppID'] = $AppID;
			$result = $this->save($data);
		}
		return $result;
	}
	
	//微会员卡end==========================
}
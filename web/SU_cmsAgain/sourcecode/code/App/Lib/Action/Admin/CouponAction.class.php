<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class CouponAction extends AdminBaseAction{
	function index(){
		$p = array();
        $p['Parameter']['CouponType'] = !empty($_REQUEST['CouponType']) ? (int)$_REQUEST['CouponType'] : -1;
		$p['HasPage'] = true; //表示有分页
		
		$m = D('Admin/Coupon');
		$CouponTypeList = $m->getCouponTypeList();
		$this->assign('CouponTypeList', $CouponTypeList);
		
		$this->opIndex($p);
	}
	
	/**
	 * 查看发放优惠券
	 */
	function couponSend(){
		$p = array();
		$p['HasPage'] = true; //表示有分页
		$p['PageSize'] = 15;
		$p['ModuleName'] = 'CouponSend';
		$CouponID = $_REQUEST['CouponID'];
		$p['Parameter']['CouponID'] = $CouponID;
		
		$m = D('Admin/Coupon');
		$Coupon = $m->findCoupon($CouponID);
		$this->assign('Coupon', $Coupon);
		$this->opIndex($p);
	}
	
	function delCouponSend(){
		$p = array();
		$p['ModuleName'] = 'CouponSend';
		if( is_numeric($_REQUEST['p']) ){
			$p['Parameter']['p'] = $_REQUEST['p'];
		}
		$p['Url'] = __URL__."/couponSend";
		$this->opDel( $p );
	}
	
	function add(){
		$tsStart = time();
		$tsEnd = $tsStart + 3600*24*15;
		$startTime = date('Y-m-d H:i:s', $tsStart);
		$endTime = date('Y-m-d H:i:s', $tsEnd);
		$Data = array('CouponMoney'=>50, 'ConsumeMoney'=>200 ,'CouponQuantity'=>500,
				'CouponTime'=>$startTime, 'StartTime'=>$startTime, 'EndTime'=>$endTime);
		$this->assign('Data', $Data);
		
		$m = D('Admin/Coupon');
		$CouponTypeList = $m->getCouponTypeList();
		$this->assign('CouponTypeList', $CouponTypeList);
		
		$p = array();
		$this->opAdd( false, $p );
	}
	
	function saveAdd(){
		$this->checkPost();
		$p = array();
		$this->opSaveAdd( $p );
	}
	
	function modify(){
		$m = D('Admin/Coupon');
		$CouponTypeList = $m->getCouponTypeList();
		$this->assign('CouponTypeList', $CouponTypeList);
		
		$p = array();
		$this->opModify(false, $p);
	}
	
	function saveModify(){
		$this->checkPost();
		$this->opSaveModify();
	}
	
	private function checkPost(){
		if(empty( $_POST['CouponName'])){
			$this->ajaxReturn('CouponName', '优惠卷名称不能为空' , 0);
		}
		
		if( !is_numeric($_POST['CouponQuantity']) || $_POST['CouponQuantity']<0 ){
			$this->ajaxReturn('CouponQuantity', '优惠卷数量必须大于等于0' , 0);
		}
		
		if( $_POST['CouponType']==2 && $_POST['CouponQuantity']<=0 ){
			$this->ajaxReturn('CouponQuantity', '线下优惠卷的发放数量必须大于0' , 0);
		}
	}

	function del(){
		$p = array();
		if( is_numeric($_REQUEST['CouponType']) && $_REQUEST['CouponType'] != -1 ){
			$p['Parameter']['CouponType'] = $_REQUEST['CouponType'];
		}
		if( is_numeric($_REQUEST['p']) ){
			$p['Parameter']['p'] = $_REQUEST['p'];
		}
		$this->opDel( $p );
	}
	
	/**
	 * 发放优惠券首页
	 */
	function send(){
		header("Content-Type:text/html; charset=utf-8");
		$Keywords = isset($_REQUEST['Keywords']) ? YdInput::checkKeyword( $_REQUEST['Keywords'] ) : '';
		$IsCheck =  1;
		$m = D('Admin/Member');
		import("ORG.Util.Page");
		$TotalPage = $m->getCount($Keywords, $IsCheck);
		$PageSize = 15;
	
		$Page = new Page($TotalPage, $PageSize);
		$Page->parameter = "&IsCheck=$IsCheck";
		if( $Keywords != ''){
			$Page->parameter .= "&Keywords=$Keywords";
		}
		$Page->rollPage = $this->AdminRollPage;
		$ShowPage = $Page->show();
		$Member= $m->getMember($Page->firstRow, $Page->listRows, $Keywords, $IsCheck);
		
		$mg = D('Admin/MemberGroup');
		$MemberGroup = $mg->getMemberGroup();
	
		$mc = D('Admin/Coupon');
		$CouponID = intval( $_REQUEST['CouponID'] );
		$Coupon = $mc->where("CouponID=$CouponID")->find();
		$this->assign('Coupon', $Coupon);
		
		$this->assign("AdminPageSize", $PageSize);
		$this->assign('NowPage', $Page->getNowPage());
		$this->assign('Keywords', $Keywords);
		$this->assign('Data', $Member);
		$this->assign('MemberGroup', $MemberGroup);
		$this->assign('Page', $ShowPage); //分页条
		$this->display();
	}
	
	/**
	 * 开始发放优惠券
	 */
	function startSend(){
		header("Content-Type:text/html; charset=utf-8");
		if( !is_numeric($_REQUEST['CouponID']) ){
			$this->ajaxReturn(null, '发放失败!' , 0);
		}
		$mc = D('Admin/CouponSend');
		$data = array(); //存放批量插入数据
		$CouponID= intval($_REQUEST['CouponID']);
		$CouponSendTime = date('Y-m-d H:i:s');
		$LanguageID = get_language_id();
		$CouponType = intval($_REQUEST['CouponType']);
		if($CouponType==1){ //按指定会员发放优惠券
			$MemberID= $_REQUEST['MemberID']; //多个MemberID以逗号隔开
			$idlist = array();
			if(!empty($MemberID)){ //按会员指定发放
				$idlist = explode(',', $MemberID);
			}else{ //按会员分组发放
				$m = D('Admin/Member');
				$idlist = $m->getMemberIDList($_REQUEST['MemberGroupID'], 2);
			}
			
			//指定会员发放优惠券，不能重复发放给同一会员
			$sended = $mc->where("CouponID=$CouponID")->getField('MemberID,CouponID');
			foreach ($idlist as $id){
				if( !isset($sended[$id]) ){
					$data[] = array('CouponID'=>$CouponID, 'MemberID'=>$id, 'CouponCode'=>'',
							'CouponSendTime'=>$CouponSendTime, 'LanguageID'=>$LanguageID);
				}
			}
			
			if(empty($data) && !empty($idlist)){
				$this->ajaxReturn(null, '不能重复发放优惠券' , 0);
			}
		}elseif($CouponType==2){ //线下发放
			$CouponNumber = intval($_REQUEST['CouponNumber']);
			if($CouponNumber <= 0){
				$this->ajaxReturn(null, '发放数量必须大于0' , 0);
			}
			for($i=0; $i<$CouponNumber; $i++){
				$CouponCode = rand_string(8,10);
				$data[] = array('CouponID'=>$CouponID, 'MemberID'=>0, 'CouponCode'=>$CouponCode,
						'CouponSendTime'=>$CouponSendTime, 'LanguageID'=>$LanguageID);
			}
		}
		
		if( empty($data) ){
			$this->ajaxReturn(null, '没有发放优惠券' , 0);
		}
		
		//检查是否超过发放总数
		$countToSend = is_array($data) ? count($data) : 0;
		$leftCount = $mc->getCouponLeftCount($CouponID);
		if( $leftCount != 'infinite' && $countToSend > $leftCount){
			$this->ajaxReturn(null, "优惠券数量不足（剩余：{$leftCount}），当前发放数：{$countToSend}" , 0);
		}
		
		if($mc->addAll( $data )){
			unset($data);
			$this->ajaxReturn(null, '发放成功!' , 1);
		}else{
			unset($data);
			$this->ajaxReturn(null, '发放失败!' , 0);
		}
	}
}
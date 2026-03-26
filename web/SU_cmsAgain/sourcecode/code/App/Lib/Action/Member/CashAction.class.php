<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class CashAction extends MemberBaseAction {
	function index(){
		$MemberID = (int)session('MemberID');
		$p = array(
				'HasPage' => true,
				'PageSize'=>20,
				'Parameter' => array('MemberID' =>$MemberID),
				'DataCallBack'=>'DataCallBack'
		);
		if( is_numeric($_REQUEST['CashType']) && $_REQUEST['CashType'] != -1 ){
			$p['Parameter']['CashType'] = $_REQUEST['CashType'];
		}
		$mm = D('Admin/Member');
		$CashPassword = $mm->getCashPassword($MemberID); //获取提现密码
		$this->assign('HasCashPassword', $CashPassword ? 1 : 0);
		
		$m = D('Admin/Cash');
		$TotalQuantity = $m->getQuantity(1, $MemberID);
		$AvailableQuantity = $m->getAvailableQuantity($MemberID);
		$this->assign('TotalQuantity', $TotalQuantity);
		$this->assign('AvailableQuantity', $AvailableQuantity);
		
		$WithdrawThreshold = $GLOBALS['Config']['WithdrawThreshold'];
		$CanWithdraw = 0; //是否可以提现
		if($AvailableQuantity >= $WithdrawThreshold){
			$CanWithdraw = 1;
		}
		$this->assign('CanWithdraw', $CanWithdraw);
		$this->assign('WithdrawThreshold', $WithdrawThreshold);
		
		$this->opIndex($p);
	}
	
	function DataCallBack(&$data){
		$total = 0;
		if(!empty($data)){
			foreach ($data as $v){
				$total += $v['CashQuantity'];
			}
		}
		$this->assign('Total', $total);
	}
	
	function del(){
		$MemberID = (int)session('MemberID');
		$p['Parameter']['MemberID'] = $MemberID;
		if( is_numeric($_REQUEST['CashType']) && $_REQUEST['CashType'] != -1 ){
			$p['Parameter']['CashType'] = $_REQUEST['CashType'];
		}
		if( is_numeric($_REQUEST['p']) ){
			$p['Parameter']['p'] = $_REQUEST['p'];
		}
		$this->opDel( $p );
	}
	
	//在线充值
	function recharge(){
		header("Content-Type:text/html; charset=utf-8");
		$this->display();
	}
	
	//立即支付
	function payNow(){
		$MemberID = (int)session('MemberID');
		$PayID = intval($_REQUEST['PayID']);
		$CashQuantity = (double)($_REQUEST['CashQuantity']);
		if($CashQuantity <= 0 ){
			$this->ajaxReturn(null, '充值金额必须大于0', 0);
		}

		$m = D('Admin/Pay');
		$data = $m->find($PayID);
		if(empty($data)){
			$this->ajaxReturn(null, '支付方式异常', 0);
		}
		
		//插入充值记录========================
		$mc = D('Admin/Cash');
		$cash['MemberID'] = $MemberID;
		$cash['CashQuantity'] = $CashQuantity;
		$cash['CashType'] = 1;
		$cash['CashStatus'] = 2;
		$cash['CashTime'] = date('Y-m-d H:i:s');
		$cash['PayID'] = $PayID;
		$cash['CashRemark'] = $_REQUEST['CashRemark'];
		$CashID = $mc->add($cash);
		//=================================
		
		$PayRate = (double)($data['PayRate']);
		//当前充值总费用
		$data['TotalOrderPrice'] = sprintf("%.2f", $CashQuantity + $CashQuantity * $PayRate); 
		//构造一个唯一的订单号
		$data['OrderNumber'] = 'ZXCZ'.date('YmdHis').'_'.$CashID;
		$PayTypeID = intval($data['PayTypeID']);
		switch ($PayTypeID){
			case 1: //支付宝支付
				import("@.Common.YdPay");
				$obj = pay_factory_create($PayTypeID, $data['SiteType']);
				$protocol = get_current_protocal();
				$data['ReturnUrl'] = $protocol.$_SERVER['HTTP_HOST'].__APP__;
				$obj->setConfig( $data );   //设置参数
				$data['PayUrl'] = $obj->getPayUrl();  //获取付款链接
				//将链接放到客户端打开更好
				header("Location: ".$data['PayUrl']);
				exit();
				break;
			case 8: //银联支付
				import("@.Common.YdPay");
				$obj = pay_factory_create($PayTypeID, $data['SiteType']);
				$protocol = get_current_protocal();
				$data['ReturnUrl'] = $protocol.$_SERVER['HTTP_HOST'].__APP__;
				$obj->setConfig( $data );   //设置参数
				$PayUrl = $obj->getPayUrl();  //返回一个表单并自动提交post
				echo $PayUrl;
				exit();
				break;
			case 10: //微信支付
				import("@.Common.YdPay");
				$obj = pay_factory_create($PayTypeID, $data['SiteType']);
				$obj->setConfig( $data );   //设置参数
				if( $obj->getType() == 'NATIVE'){
					$data['PayUrl'] = $obj->getPayUrl();
					$data['PayTip'] = L('WeiXinPayScanTip');
					$PayImageSrc = empty($data['PayUrl']) ? '' : PayQrcode( $data['PayUrl'] );
					$data['PayIcon'] = -1;
					$data['PayContent'] = '';
					if( !empty($PayImageSrc) ){
						$data['PayContent'] = "<img src='{$PayImageSrc}' class='payqrcode' />";
					}
				}else{
					//必须提前获取
					if( !empty($_GET['openid']) ){
						$obj->openid = $_GET['openid'];
						$data['PayJson'] = $obj->getPayUrl(); //微信公众号支付返回的是json数据
					}else{
						$data['PayJson'] = '';
					}
				}
				break;
		}
		$data['CashID'] = $CashID;
		unset($data['AccountName'], $data['AccountPassword'], $data['AccountKey'], $data['AccountID']);
		$this->ajaxReturn($data, false, 1);
	}
	
	/**
	 * 获取资金状态
	 */
	function getCashStatus(){
		header("Content-Type:text/html; charset=utf-8");
		$CashID = intval( $_GET['CashID'] );
        $MemberID = (int)session('MemberID');
		$m = D('Admin/Cash');
		$status = $m->where("CashID={$CashID} AND MemberID={$MemberID}")->getField('CashStatus');
		$this->ajaxReturn($status, null , 1);
	}
	
	/**
	 * 提现申请
	 */
	function withdraw(){
		header("Content-Type:text/html; charset=utf-8");

		$m = D('Admin/Cash');
		$MemberID = (int)session('MemberID');
		$AvailableQuantity = $m->getAvailableQuantity($MemberID);
		$MinWithdraw = $GLOBALS['Config']['MinWithdraw']; //最低提现额度
		$Bank = $m->getBank($MemberID);
		
		$this->assign('Bank', $Bank);
		$this->assign('AvailableQuantity', $AvailableQuantity);
		$this->assign('MinWithdraw', $MinWithdraw);
		$this->assign("Action", __URL__."/saveWithdraw");
		$this->display();
	}
	
	/**
	 * 保存提现申请
	 */
	function saveWithdraw(){
		$this->_checkWithdraw();
		$p['SuccessMsg']='提现申请成功，请等待付款';
		$p['FailMsg']='提现申请失败';
		$this->opSaveAdd( $p );
	}
	
	/**
	 * 校验提现数据
	 * @param unknown_type $data
	 */
	private function _checkWithdraw(){
        $_POST = YdInput::checkTextbox($_POST);
		//提现金额合法性检查
		if( !is_numeric($_POST['CashQuantity']) ){
			$this->ajaxReturn("", "提现金额必须为数字" , 0);
		}
		$CashQuantity = (double)($_POST['CashQuantity']);
		$MinWithdraw = $GLOBALS['Config']['MinWithdraw']; //最低提现额度
		if($CashQuantity < $MinWithdraw){
			$this->ajaxReturn("", "提现金额必须大于{$MinWithdraw}" , 0);
		}
		$m = D('Admin/Cash');
		$MemberID = (int)session('MemberID');
		$AvailableQuantity = $m->getAvailableQuantity($MemberID);
		if($CashQuantity>$AvailableQuantity){
			$this->ajaxReturn("", "账户余额不足！" , 0);
		}
	
	
		if( empty($_POST['BankName']) ){
			$this->ajaxReturn("", "收款银行不能为空" , 0);
		}
		if( empty($_POST['BankAccount']) ){
			$this->ajaxReturn("", "收款账号不能为空" , 0);
		}
		if( empty($_POST['OwnerName']) ){
			$this->ajaxReturn("", "开户人姓名不能为空" , 0);
		}
	
		//验证密码
		$MemberID = (int)session('MemberID');
		$CashPassword = $_POST['CashPassword'];
		$m = D('Admin/Member');
		$isCorrect = $m->isCashPasswordCorrect($MemberID, $CashPassword);
		if(!$isCorrect){
			$this->ajaxReturn("", "提现密码错误" , 0);
		}
		$_POST['CashType'] = 4;
		$_POST['CashStatus'] = 2; //未转账状态
		$_POST['MemberID'] = $MemberID;
		$_POST['CashQuantity'] = 0 - $_POST['CashQuantity'];
		$_POST['CashTime'] = date('Y-m-d H:i:s');
	}
	
	/**
	 * 设置提现密码
	 */
	function setPwd(){
		header("Content-Type:text/html; charset=utf-8");
		$MemberID = (int)session('MemberID');
		$m = D('Admin/Member');
		//原始提现密码密码
		$oldPassword = $m->where("MemberID=$MemberID")->getField('CashPassword');
		$HasOldPassword = empty($oldPassword) ? 0 : 1;

		$pwd1 = trim($_POST['pwd1']);  //原始密码
		$pwd2 = trim($_POST['pwd2']);
		$pwd3 = trim($_POST['pwd3']);
			
		if( $HasOldPassword && empty($pwd1) ){
			$this->ajaxReturn(null, '原始密码不能为空!' , 0);
		}
	
		if( empty($pwd2) ){
			$this->ajaxReturn(null, '新密码不能为空!' , 0);
		}
	
		if( empty($pwd3) ){
			$this->ajaxReturn(null, '重复密码不能为空!' , 0);
		}
	
		if( $pwd2 != $pwd3 ){
			$this->ajaxReturn(null, '二次输入的密码不一致!' , 0);
		}
		
		if( $HasOldPassword && $pwd1 == $pwd3 ){
			$this->ajaxReturn(null, '新密码不能和原始密码相同!' , 0);
		}
		
		$options['LogType'] = 8;
		if($HasOldPassword){
            $isCorrect = $m->isCashPasswordCorrect($MemberID, $pwd1);
			if(!$isCorrect){
				$options['UserAction'] = '修改提现密码';
				WriteLog(session('MemberName').'修改提现密码失败，原密码错误', $options);
				$this->ajaxReturn(null, '原密码错误!' , 0);
			}
		}
        $pwd2 = yd_password_hash($pwd2);
		$r = $m->where("MemberID=$MemberID")->setField('CashPassword', $pwd2);
		if($r){
			$options['UserAction'] = '修改提现密码';
			WriteLog(session('MemberName').'修改提现密码成功', $options);
			$this->ajaxReturn(null, '修改密码成功!' , 1);
		}else{
			$this->ajaxReturn(null, '修改密码失败!' , 0);
		}
	}
}
<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class OrderAction extends AdminBaseAction {
	function index(){
		$p = array(
			'HasPage' => true,
		);
		if( !empty($_REQUEST['OrderNumber']) ){
			$p['Parameter']['OrderNumber'] = $_REQUEST['OrderNumber'];
		}
		if( !empty($_REQUEST['ConsigneeRealName']) ){
			$p['Parameter']['ConsigneeRealName'] = $_REQUEST['ConsigneeRealName'];
		}
        $p['Parameter']['OrderStatus'] = !empty($_REQUEST['OrderStatus']) ? (int)$_REQUEST['OrderStatus'] : -1;
		$this->opIndex($p);
	}

	
	function delOrder(){
		$p['DelFunctionName'] = 'delOrder';
	
		if( !empty($_REQUEST['OrderNumber']) ){
			$p['Parameter']['OrderNumber'] = $_REQUEST['OrderNumber'];
		}
	
		if( !empty($_REQUEST['ConsigneeRealName']) ){
			$p['Parameter']['ConsigneeRealName'] = $_REQUEST['ConsigneeRealName'];
		}
	
		if( !isset($_REQUEST['OrderStatus']) ){
			$p['Parameter']['OrderStatus'] = -1;
		}else if( is_numeric($_REQUEST['OrderStatus']) ){
			$p['Parameter']['OrderStatus'] = $_REQUEST['OrderStatus'];
		}
	
		if( is_numeric($_REQUEST['p']) ){
			$p['Parameter']['p'] = $_REQUEST['p'];
		}
		$this->opDel( $p );
	}
	
	function modify(){
		$OrderID = intval( $_REQUEST['id'] );
		$this->assign('OrderID', $OrderID);
		
		$m1 = D('Admin/OrderProduct');
		$Product = $m1->getOrderProduct($OrderID);
		$this->assign('Product', $Product);
		
		$m2 = D('Admin/OrderLog');
		$Log = $m2->getOrderLog(-1, -1, array('OrderID'=>$OrderID) );
		$this->assign('Log', $Log);
		
		$this->opModify();
	}
	
	function saveModify(){
		//过滤禁止修改的字段值
		$list = array(
				'ConsigneeRealName', 'ConsigneeGender', 'ConsigneeEmail', 'ConsigneeMobile', 'ConsigneeTelephone',
				'ConsigneeAddress', 'ConsigneePostcode', 'DeliveryTimeID', 'ConsigneeRemark', 'DiscountPrice',
				'OrderStatus', 'PayStatus', 'ShippingStatus','OrderID', 'OrderRemark');
		foreach ($_POST as $k=>$v){
			if( !in_array($k, $list) ){
				unset($k);
			}
		}
		$this->opSaveModify();
	}
	
	/**
	 * 数据显示参数说明：
	 * 1. 分页调用函数格式：getModuleName($Page->firstRow, $Page->listRows, $Parameter);
	 * 2. 没有分页函数格式：getModuleName($Parameter);
	 * 3. 支持GetFunctionName、GetCountFunctionName自定义
	 */
	function orderLog(){
		$p = array(
			'HasPage' => true,
			'ModuleName'=>'OrderLog',
			//'PageSize'=>3,
		);
		if( !empty($_REQUEST['OrderNumber']) ){
			$p['Parameter']['OrderNumber'] = $_REQUEST['OrderNumber'];
		}
		if( !empty($_REQUEST['OrderLogType']) ){
			$p['Parameter']['OrderLogType'] = $_REQUEST['OrderLogType'];
		}
		$this->opIndex($p);
	}
	
	function modifyOrderLog(){
		$p = array(
			'ModuleName'=>'OrderLog',
			'Action'=> __URL__.'/saveModifyOrderLog',
		);
		$this->opModify(false, $p);
	}
	
	function saveModifyOrderLog(){
		$list = array('OrderLogType', 'Operator');
		foreach ($_POST as $k=>$v){
			if( !in_array($k, $list) ){
				unset($k);
			}
		}
		$p = array(
				'ModuleName'=>'OrderLog',
		);
		$this->opSaveModify($p);
	}
	
	/**
	 * 删除参数数组传入说明：
	 * 1. Parameter：自定义输入参数，需要判断参数的有效性（如：是否为空，是否为数字）以及设置必要参数的默认值
	 * 2. DelFunctionName(id, Parameter)：自定义删除函数 ，默认使用baseDel(id)删除
	 */
	function delOrderLog(){
		$p = array('ModuleName'=>'OrderLog', 'Url' => __URL__."/orderLog");
		if( !empty($_REQUEST['OrderNumber']) ){
			$p['Parameter']['OrderNumber'] = $_REQUEST['OrderNumber'];
		}
		if( !empty($_REQUEST['OrderLogType']) ){
			$p['Parameter']['OrderLogType'] = $_REQUEST['OrderLogType'];
		}
		//当前分页参数
		if( is_numeric($_REQUEST['p']) ){
			$p['Parameter']['p'] = $_REQUEST['p'];
		}
		$this->opDel( $p );
	}
	
	//订单操作：2：付款（需改变orderStatus、payStatus）、3：发货（需改变orderStatus、shippingStatus）、4：退款、5：退货、6：结单、7：作废
	//订单状态 1：新订单、2：已付款、3：已发货、4：退款、5：退货、6：结单、7：作废
	//支付状态：1：已支付、2：未支付
	//发货状态：1：已发货、2：未发货
	function setStatus(){
		header("Content-Type:text/html; charset=utf-8");
		$_POST['Operator'] = session('AdminName');
		$m = D('Admin/OrderLog');
		if( $m->create() ){
			if($m->add()){
				WriteLog("ID:".$m->getLastInsID() );
				$m1 = D('Admin/Order');
				$orderid = $_POST['OrderID'];
				$type = $_POST['OrderLogType'];
				switch ($type){
					case 2:  //付款
						$m1->setOrder($orderid, 2, 1, false); //$orderStatus, $payStatus, $shippingStatus
						break;
					case 3: //发货
						$m1->setOrder($orderid, 3, false, 1);
						break;
					case 4: //退款
						$m1->setOrder($orderid, 4, false, false);
						break;
					case 5: //退货
						$m1->setOrder($orderid, 5, false, false);
						break;
					case 6: //结单
						$m1->setOrder($orderid, 6, false, false);
						break;
					case 7: //作废
						$m1->setOrder($orderid, 7, false, false);
						break;
				}
				
				$_POST['OrderLogTime'] = yd_friend_date( strtotime($_POST['OrderLogTime']) );
				$this->ajaxReturn($_POST, '操作成功!' , 1);
			}else{
				$this->ajaxReturn(null, '操作失败!' , 0);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}
	
	/**
	 * 销售统计
	 */
	function stat(){
		header("Content-Type:text/html; charset=utf-8");
		$StatType = !empty($_REQUEST['StatType']) ? intval( $_REQUEST['StatType'] ) : 4; //统计类型
		//获取起始时间
		$TimeSpan = !empty($_REQUEST['TimeSpan']) ? intval( $_REQUEST['TimeSpan'] ) : 1; //时间跨度
		if( $TimeSpan == 10){ //指定时间段
			$StartTime = !empty($_REQUEST['StartTime']) ? $_REQUEST['StartTime'] : date('Y-m-d 00:00:00');
			$EndTime = !empty($_REQUEST['EndTime']) ? $_REQUEST['EndTime'] : date('Y-m-d 23:59:59');
		}else{
			$StartTime = date('Y-m-d');
			$EndTime = date('Y-m-d');
			getTimeSpan($TimeSpan, $StartTime, $EndTime);
		}
		$m = D('Admin/OrderProduct');
		if( $StatType == 1 || $StatType == 2 ){
			//分页 开始========
			import("ORG.Util.Page");
			$TotalPage = $m->getStatCount($StatType, $StartTime, $EndTime); //获取留言总数
			$PageSize = $this->AdminPageSize;
			$Page = new Page($TotalPage, $PageSize);
			$Page->rollPage = $this->AdminRollPage;
			$Page->parameter = "&StatType=$StatType";
			$Page->parameter .= "&TimeSpan=$TimeSpan";
			if( $TimeSpan == 10){
				$Page->parameter .= "&StartTime=$StartTime";
				$Page->parameter .= "&EndTime=$EndTime";
			}
			$ShowPage = $Page->show();
			//分页 结束========
			$this->assign('NowPage', $Page->getNowPage());
			$this->assign('Page', $ShowPage);
			$data = $m->stat($StatType, $StartTime, $EndTime, $Page->firstRow, $Page->listRows);
			$this->assign('Data', $data);
		}else if($StatType == 3){ //按年月统计
			$data = $m->statMoneyByMonth();
            $this->assign('Data', $data);
		}else if($StatType == 4){ //按天统计
			$data = $m->statMoneyByDay();
            $this->assign('Data', $data);
		}
		$this->assign('StatType', $StatType);
		$this->assign('TimeSpan', $TimeSpan);
		$this->assign('StartTime', $StartTime);
		$this->assign('EndTime', $EndTime);
		$this->display();
	}
	
	function printing(){
		$OrderID = intval( $_REQUEST['id'] );
		//会员只能查看自己的
		$m=D('Admin/Order');
		$this->assign('OrderID', $OrderID);
		$m1 = D('Admin/OrderProduct');
		$Product = $m1->getOrderProduct($OrderID);
		$this->assign('Product', $Product);
		$p['DataCallBack'] = 'ViewDataCallBack';
		$this->opModify(false,$p);
	}
	
	protected function ViewDataCallBack(&$data){
		if( is_numeric($data['OrderID'])){
			$OrderID = $data['OrderID'];
			$m = D('Admin/OrderLog');
			$data['PayTime'] = $m->getPayTime($OrderID);
			$data['ShippingTime'] = $m->getShippingTime($OrderID);
			$data['ShippingNumber'] = $m->getShippingNumber($OrderID);
			$data['FinishTime'] = $m->getFinishTime($OrderID);
		}
	}
}
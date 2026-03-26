<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class OrderAction extends MemberBaseAction {
	function index(){
		$MemberID = (int)session('MemberID');
		$p = array(
				'HasPage' => true,
				'Parameter' => array('MemberID' =>$MemberID),
				'DataCallBack'=>'DataCallBack'
		);
		if( !empty($_REQUEST['OrderNumber']) ){
			$p['Parameter']['OrderNumber'] = $_REQUEST['OrderNumber'];
		}
		if( is_numeric($_REQUEST['OrderStatus']) ){
			$p['Parameter']['OrderStatus'] = $_REQUEST['OrderStatus'];
		}else{
			$p['Parameter']['OrderStatus'] = -1;
		}
		$this->opIndex($p);
	}
	
	/**
	 * 回调函数
	 */
	protected function DataCallBack(&$data){
		$m = D('Admin/OrderProduct');
		$n = is_array($data) ? count($data) : 0;
		for($i=0; $i < $n; $i++){
			//获取支付链接
			if( $data[$i]['PayStatus'] == 2 ){
				$data[$i]['PayUrl'] = PayUrl($data[$i]['OrderID']);
			}
			//获取订单产品相关信息
			$data[$i]['Product'] = $m->getOrderProduct($data[$i]['OrderID']);
			$data[$i]['ProductCount'] = is_array($data[$i]['Product']) ? count($data[$i]['Product']) : 0;
		}
	}
	
	function printing(){
		$OrderID = intval( $_REQUEST['id'] );
		$MemberID = (int)session('MemberID');
		//会员只能查看自己的
		$m=D('Admin/Order');
		$b = $m->orderExist($OrderID, $MemberID);
		if($b){
			$this->assign('OrderID', $OrderID);
			$m1 = D('Admin/OrderProduct');
			$Product = $m1->getOrderProduct($OrderID);
			$this->assign('Product', $Product);
				
			$p['DataCallBack'] = 'ViewDataCallBack';
			$this->opModify(false,$p);
		}
	}
	
	function view(){
		$OrderID = intval( $_REQUEST['id'] );
		$MemberID = (int)session('MemberID');
		//会员只能查看自己的
		$m=D('Admin/Order');
		$b = $m->orderExist($OrderID, $MemberID);
		if($b){
			$this->assign('OrderID', $OrderID);
			$m1 = D('Admin/OrderProduct');
			$Product = $m1->getOrderProduct($OrderID);
			$this->assign('Product', $Product);
			
			$p['DataCallBack'] = 'ViewDataCallBack';
			$this->opModify(false,$p);
		}
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
	
	/**
	 * 取消订单
	 */
	function cancel(){
		$m = D('Admin/Order');
		$p['MemberID'] = (int)session('MemberID');
		$p['MemberName'] = session('MemberName');
		$b = $m->cancelOrder($_REQUEST['OrderID'], $p);
		$this->ajaxReturn(null, '取消订单成功' , 1);
	}
	
	/**
	 * 确认收货
	 */
	function receive(){
		$m = D('Admin/Order');
		$p['MemberID'] = (int)session('MemberID');
		$p['MemberName'] = session('MemberName');
		$b = $m->confirmReceipt($_REQUEST['OrderID'], $p);
		$this->ajaxReturn(null, '确认收货成功' , 1);
	}
	
	function del(){
		$p['DelFunctionName'] = 'delOrder';
		
		$p['Parameter']['MemberID'] = (int)session('MemberID');
		if( !empty($_REQUEST['OrderNumber']) ){
			$p['Parameter']['OrderNumber'] = $_REQUEST['OrderNumber'];
		}

		if( is_numeric($_REQUEST['OrderStatus']) ){
			$p['Parameter']['OrderStatus'] = $_REQUEST['OrderStatus'];
		}else{
			$p['Parameter']['OrderStatus'] = -1;
		}

		if( is_numeric($_REQUEST['p']) ){
			$p['Parameter']['p'] = $_REQUEST['p'];
		}
		$this->opDel( $p );
	}
}
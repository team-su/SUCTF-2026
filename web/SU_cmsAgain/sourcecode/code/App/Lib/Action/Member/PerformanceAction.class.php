<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class PerformanceAction extends MemberBaseAction {
	/**
	 * 信息列表显示首页
	 */
	function index(){
		header("Content-Type:text/html; charset=utf-8");
		$MemberID = (int)session('MemberID');
		$MemberGroupID = (int)session('MemberGroupID');
        $_REQUEST['CustomerID'] = intval($_REQUEST['CustomerID']);
		if( $MemberGroupID == 100){
			$CurrentYear = (int)date('Y');
			$CurrentMonth = (int)date('m');
			$options['Parameter'] = array(
				'CustomerID' => isset($_REQUEST['CustomerID']) ? $_REQUEST['CustomerID'] : -1,
				'ProjectType' => isset($_REQUEST['ProjectType']) ? $_REQUEST['ProjectType'] : -1,
				'OperatorID' =>  $MemberID,
				'Year'=>isset($_REQUEST['Year']) ? $_REQUEST['Year'] : -1,
				'Month'=>isset($_REQUEST['Month']) ? $_REQUEST['Month'] : -1,
				'NeedInvoice' => isset($_REQUEST['NeedInvoice']) ? $_REQUEST['NeedInvoice'] : -1,
				'PayTypeID' => isset($_REQUEST['PayType']) ? $_REQUEST['PayType'] : -1,
			);
			//获取用户信息==================================
			$ma = D('Admin/Authorize');
			$CustomerData = $ma->getCustomerData($MemberID);
			$this->assign('CustomerData', $CustomerData);
			
			$Type = $this->getProjectType();
			$this->assign('Type', $Type);
			
			$PayType = $this->getPayType();
			$this->assign('PayType', $PayType);
			//==========================================
			
			$Parameter = $options['Parameter'];
			$m = D('Admin/Performance');
			import("ORG.Util.Page");
			$TotalPage = $m->getPerformanceCount( $Parameter );
			$PageSize = isset($options['PageSize']) ? $options['PageSize'] : $this->AdminPageSize;
			$Page = new Page($TotalPage, $PageSize);
			$Page->rollPage = $this->AdminRollPage;
			//获取参数
			if( !empty( $Parameter ) ){
				$p = '';
				foreach ($Parameter as $k=>$v){
					$p .= "&{$k}={$v}";
					$this->assign($k, $v); //赋值模板变量
				}
				$Page->parameter = $p;
			}
			$data = $m->getPerformance($Page->firstRow, $Page->listRows, $Parameter );
            $Percentage = 0;
			if(!empty($data)){
				$n = is_array($data) ? count($data) : 0;
				$mm = D('Admin/Member');
				$Percentage = $mm->where("MemberID=$MemberID")->getField('Percentage');
				for($i = 0; $i < $n; $i++){
					$CustomerID = $data[$i]['CustomerID'];
					if( $CustomerID > 0 ){
						$CustomerName = $mm->where("MemberID=$CustomerID")->getField('MemberName');
						$data[$i]['CustomerName'] = $CustomerName;
					}
					
					$typeid = $data[$i]['ProjectType'];
					$payid = $data[$i]['PayType'];
					$data[$i]['ProjectTypeName'] = $Type[ $typeid ]['ProjectTypeName'];
					$data[$i]['PayTypeName'] = $PayType[ $payid ]['PayTypeName'];
					$data[$i]['IsLock'] = 0;//$this->_getDays( $data[$i]['AddTime'] ) > 3 ? 1 : 0;
				}
			}
			$ShowPage = $Page->show();
			$TotalFee = $m->getTotalFee($Parameter);
			$TotalFeeExceptTemplate = $m->getTotalFeeExceptTemplate($Parameter);
			$this->assign('PageSize', $PageSize);
			$this->assign('NowPage', $Page->getNowPage()); //当前页码
			$this->assign('Page', $ShowPage); //分页条
            $YearSpan = array();
			for($i = 2012; $i<=$CurrentYear; $i++){
				$YearSpan[]['Year'] = $i;
			}
			$this->assign('YearSpan', $YearSpan);
			$this->assign('Percentage', $Percentage);
			$this->assign('TotalFee', $TotalFee);
			$this->assign('TotalFeeExceptTemplate', $TotalFeeExceptTemplate);
			$this->assign('Money', $TotalFeeExceptTemplate*$Percentage);
			
			//获取当前代理用户模板数统计
			//if( $Parameter['CustomerID'] != -1 && $Parameter['ProjectType'] == 7){
				//$Template = $m->getTemplateCount( $Parameter['CustomerID'] );
				//$this->assign('Template', $Template);
			//}
			if( $_REQUEST['CustomerID'] > 0 ){
				$AgentFee = $m->getAgentFee( $_REQUEST['CustomerID'] );
				$TemplateFee = $m->getTemplateFee( $_REQUEST['CustomerID'] );
				$LeftFee = $AgentFee - $TemplateFee;
				$this->assign('AgentFee', $AgentFee);
				$this->assign('TemplateFee', $TemplateFee);
				$this->assign('LeftFee', $LeftFee);
			}
		}else{
			$data = false;
		}
		$this->assign('Data', $data);
		$this->display();
	}
	
	//删除、批量删除
	function del(){
		header("Content-Type:text/html; charset=utf-8");
		$PerformanceID = intval($_REQUEST["id"]);
		$CustomerID= isset($_REQUEST['CustomerID']) ? (int)$_REQUEST['CustomerID'] : -1;
		$ProjectType = isset($_REQUEST['ProjectType']) ? (int)$_REQUEST['ProjectType'] : -1;
		$p = (int)$_REQUEST["NowPage"];
		if( $this->_canDel( $PerformanceID ) ){
			$m = D('Admin/Performance');
			if( is_numeric($PerformanceID) ){
				$where['PerformanceID'] = $PerformanceID;
				$where['OperatorID'] = (int)session('MemberID');
				$m->where($where)->delete();
			}
		}
		$url = __URL__."/Index/CustomerID/$CustomerID/ProjectType/$ProjectType/p/$p";
		redirect( $url );
	}
	
	//判断当前信息是否能够被删除（会员可以删除3天的内信息）
	private function _canDel($id){
		$m = D('Admin/Performance');
		$where['PerformanceID'] = intval($id);
		$startTime = $m->where($where)->getField('AddTime');
		if( empty($startTime) ) return true;
		$days = $this->_getDays($startTime);
		if( $days > 3650 ){ //默认只能修改1个月以内的，默认为30
			return false;
		}else{
			return true;
		}
	}
	
	//获取项目类别
	private function getProjectType(){
		$m = D('Admin/Performance');
		$data = $m->getProjectType();
		return $data;
	}
	
	private function getPayType(){
		$m = D('Admin/Performance');
		$data = $m->getPayType();
		return $data;
	}
	
	function modify(){
		header("Content-Type:text/html; charset=utf-8");
		$this->assign('Type', $this->getProjectType() );
		//获取用户信息==================================
		$ma = D('Admin/Authorize');
		$MemberID = (int)session('MemberID');
		$CustomerData = $ma->getCustomerData( $MemberID );
		$this->assign('CustomerData', $CustomerData);
		
		$PayType = $this->getPayType();
		$this->assign('PayType', $PayType);
		//==========================================
		
		$m = D('Admin/Performance');
		$PerformanceID = (int)$_REQUEST["id"];
		$options['OperatorID'] = (int)session('MemberID');
		$Data = $m->findPerformance($PerformanceID, $options);
		
		$this->assign('Data', $Data);
		$this->assign('HiddenName', 'PerformanceID');
		$this->assign('HiddenValue', $PerformanceID);
		$this->assign('Action', __URL__.'/saveModify');
		$this->display();
	}
	
	function saveModify(){
		header("Content-Type:text/html; charset=utf-8");
        $_POST = YdInput::checkTextbox($_POST);
		$this->_checkPost( $_POST );
		if( $this->_canDel( $_POST['PerformanceID'] ) ){
			$m = D('Admin/Performance');
			if( $m->create() ){
				$m->OperatorID = (int)session('MemberID');
				if($m->save() === false){
					$this->ajaxReturn(null, '修改失败!' , 0);
				}else{
					$this->ajaxReturn(null, '修改成功!' , 1);
				}
			}else{
				$this->ajaxReturn(null, $m->getError() , 0);
			}
		}else{
			$this->ajaxReturn(null, '已锁定，无法修改!' , 0);
		}
	}
	
	//检查提交参数
	private function _checkPost($p){
		if( empty($p['ProjectName']) ){
			$this->ajaxReturn(null, '项目名称不能为空' , 0);
		}
	
		if( $p['ProjectType'] != 7  ){ //当登记项目时，不需要判断项目费用
			if( !is_numeric($p['ProjectFee'] ) || $p['ProjectFee'] <= 0 ){
				$this->ajaxReturn(null, '项目费用必须大于0' , 0);
			}
		}else{
			//如果是模板登记，则不需要支付方式
			unset($_POST['PayType']);
		}
	}
	
	
	private function _getDays($startTime, $endTime = false){
		$start = strtotime($startTime);
		$end = ($endTime === false ) ? time() : strtotime($endTime);
		$days = ($end-$start)/3600.0/24.0;
		return $days;
	}
	
	function add(){
		header("Content-Type:text/html; charset=utf-8");
		$this->assign('Type', $this->getProjectType() );
		$MemberID = (int)session('MemberID');
		//获取用户信息==================================
		$ma = D('Admin/Authorize');
		$CustomerData = $ma->getCustomerData($MemberID);
		$this->assign('CustomerData', $CustomerData);
		
		$PayType = $this->getPayType();
		$this->assign('PayType', $PayType);
		//==========================================
	
		//默认数据
		$Data = array('ProjectFee'=>0, 'ProjectType'=>1, 'AddTime'=>date('Y-m-d H:i:s'), 'PayType'=>1,
				'NeedInvoice'=>0, 'TemplateCount'=>0, 'IsAuthorize'=>1, 'Host'=>'');
		$this->assign('Data', $Data);
		$this->assign('Action', __URL__.'/SaveAdd');
		$this->display();
	}
	
	function batchAdd(){
		header("Content-Type:text/html; charset=utf-8");
		$MemberID = (int)session('MemberID');
		$Data = array('AddTime'=>date('Y-m-d H:i:s'), 'ProjectName'=>'三合一模板登记');
		$ma = D('Admin/Authorize');
		$CustomerData = $ma->getCustomerData($MemberID);
		$this->assign('CustomerData', $CustomerData);
		$this->assign('Data', $Data);
		$this->assign('Action', __URL__.'/saveBatchAdd');
		$this->display();
	}
	
	function saveBatchAdd(){
		header("Content-Type:text/html; charset=utf-8");
		$OperatorID = (int)session('MemberID');
		$CustomerID =  intval($_POST['CustomerID']);
		$ProjectName = $_POST['ProjectName'];
		$ProjectType = 7; //7:模板登记
		unset($_POST['PayType']);  //不需要支付方式
		$arrPcNumber = $_POST['PcNumber'];
		$arrWapNumber = $_POST['WapNumber'];
		$arrAddTime = $_POST['AddTime'];
		
		if( empty($ProjectName) ){
			$this->ajaxReturn(null, '项目名称不能为空' , 0);
		}
		
		$data = array();
		$count = count($arrPcNumber);
		for($i = 0; $i<$count; $i++){
			if( $arrPcNumber[$i] != '' || $arrWapNumber[$i] !='' ){
				$data[] = array(
						'ProjectName'=>$ProjectName,
						'ProjectType'=>$ProjectType,
						'OperatorID'=>$OperatorID,
						'CustomerID'=>$CustomerID,
						
						'PcNumber'=>$arrPcNumber[$i],
						'WapNumber'=>$arrWapNumber[$i],
						'AddTime'=>$arrAddTime[$i],
				);
			}
		}
		if( empty($data) ){
			$this->ajaxReturn(null, '添加失败!' , 0);
		}
		
		$m = D('Admin/Performance');
		if($m->addAll( $data )){
			$this->ajaxReturn(null, '添加成功!' , 1);
		}else{
			$this->ajaxReturn(null, '添加失败!' , 0);
		}
	}
	
	function saveAdd(){
		header("Content-Type:text/html; charset=utf-8");
        $_POST = YdInput::checkTextbox($_POST);
		$this->_checkPost( $_POST );
		$m = D('Admin/Performance');
		if( $_POST['ProjectType'] == 7){
			//需要判断模板编号是否存在
			if( $m->pcTemplateExist($_POST['PcNumber'], $_POST['CustomerID']) ){
				$this->ajaxReturn(null, "电脑模板编号{$_POST['PcNumber']}已经存在" , 0);
			}
			if( $m->wapTemplateExist($_POST['WapNumber'], $_POST['CustomerID']) ){
				$this->ajaxReturn(null, "手机模板编号{$_POST['WapNumber']}已经存在" , 0);
			}
		}
		if( $m->create() ){
			$m->OperatorID = (int)session('MemberID');
			if($m->add()){
				$this->ajaxReturn(null, '添加成功!' , 1);
			}else{
				$this->ajaxReturn(null, '添加失败!' , 0);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}
	
	function getInvoiceInfo(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Member');
		$where['MemberID'] = intval($_REQUEST['CustomerID']);
		$where['InviterID'] = (int)session('MemberID');
		$m->field('MemberRealName,MemberName,MemberAddress,MemberMobile');
		$data = $m->where($where)->find();
		if( !empty($data) ){
			$this->ajaxReturn($data, '获取成功!' , 1);
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}
	
	function consume(){
		header("Content-Type:text/html; charset=utf-8");
		$MemberID = (int)session('MemberID');
		$options['Parameter'] = array(
				'ProjectType' => isset($_REQUEST['ProjectType']) ? intval($_REQUEST['ProjectType']) : -1,
				'CustomerID' =>  $MemberID,
		);
		
		//获取用户信息==================================
		$ma = D('Admin/Authorize');
		$Type = $this->getProjectType();
		$this->assign('Type', $Type);
		//==========================================
	
		$Parameter = $options['Parameter'];
		$m = D('Admin/Performance');
		import("ORG.Util.Page");
		$TotalPage = $m->getPerformanceCount( $Parameter );
		$PageSize = isset($options['PageSize']) ? $options['PageSize'] : $this->AdminPageSize;
		$Page = new Page($TotalPage, $PageSize);
		$Page->rollPage = $this->AdminRollPage;
		//获取参数
		if( !empty( $Parameter ) ){
			$p = '';
			foreach ($Parameter as $k=>$v){
				$p .= "&{$k}={$v}";
				$this->assign($k, $v); //赋值模板变量
			}
			$Page->parameter = $p;
		}
		$data = $m->getPerformance($Page->firstRow, $Page->listRows, $Parameter );
		if(!empty($data)){
			$n = is_array($data) ? count($data) : 0;
			$mm = D('Admin/Member');
			$Percentage = $mm->where("MemberID=$MemberID")->getField('Percentage');
			for($i = 0; $i < $n; $i++){
				$typeid = $data[$i]['ProjectType'];
				$payid = $data[$i]['PayType'];
				$data[$i]['ProjectTypeName'] = $Type[ $typeid ]['ProjectTypeName'];
				$data[$i]['IsLock'] = 0;//$this->_getDays( $data[$i]['AddTime'] ) > 3 ? 1 : 0;
			}
		}
		$ShowPage = $Page->show();
		/*
		$TotalFee = $m->getTotalFee($Parameter);
		$Template = $m->getTemplateCount( $MemberID );
		$this->assign('TotalFee', $TotalFee);
		$this->assign('Template', $Template);
		*/
		$ChongZhiFee = $m->getChongZhiFee ($MemberID); //充值总金额
		$UsedFee = $m->getUsedFee($MemberID); //已使用金额
		$LeftFee = $ChongZhiFee-$UsedFee;  //剩余金额
		$this->assign('ChongZhiFee', $ChongZhiFee);
		$this->assign('UsedFee', $UsedFee);
		$this->assign('LeftFee', $LeftFee);
		
		$this->assign('PageSize', $PageSize);
		$this->assign('NowPage', $Page->getNowPage()); //当前页码
		$this->assign('Page', $ShowPage); //分页条
		$this->assign('Data', $data);
		$this->display();
	}
}
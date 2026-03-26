<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class ConsigneeAction extends MemberBaseAction {
	function index(){
		$MemberID = (int)session('MemberID');
		$p = array(
				'Parameter' => array('MemberID' =>$MemberID),
		);
		$m = D('Admin/Consignee');
		$DefaultConsigneeID = $m->getDefaultConsigneeID($MemberID);
		$this->assign('DefaultConsigneeID', $DefaultConsigneeID);
		$this->opIndex($p);
	}
	
	function add(){
		$Data = array('IsDefault'=>0);
		$this->assign('Data', $Data);
		$p = array();
		$this->opAdd( false, $p );
	}
	
	function saveAdd(){
		$this->_check();
		$_POST['MemberID'] = (int)session('MemberID');
		$p = array();
		$this->opSaveAdd( $p );
	}
	
	function modify(){
		$p['Parameter']['MemberID'] = (int)session('MemberID');
		$this->opModify(false, $p);
	}
	
	function saveModify(){
		$this->_check();
        $MemberID = (int)session('MemberID');
		$_POST['MemberID'] = $MemberID;
        $_POST['CurrentMemberID'] = $MemberID;
		$this->opSaveModify();
	}
	
	//表单输入检查
	private function _check(){
        $_REQUEST = YdInput::checkTextbox($_REQUEST);
		if( empty($_REQUEST['ConsigneeRealName'])){
			$this->ajaxReturn('ConsigneeRealName', L('ConsigneeRealNameRequired') , 0);
		}
	
		if( empty($_REQUEST['ConsigneeMobile'])){
			$this->ajaxReturn('ConsigneeMobile', L('ConsigneeMobileRequired') , 0);
		}
	
		if( empty($_REQUEST['ConsigneeAddress'])){
			$this->ajaxReturn('ConsigneeAddress', L('ConsigneeAddressRequired'), 0);
		}
		$_POST = YdInput::checkReg( $_POST ); //xss过滤
	}
	
	function del(){
		$p['Parameter']['MemberID'] = (int)session('MemberID');
		$this->opDel( $p );
	}
	
	function setDefault(){
		$m = D('Admin/Consignee');
		$p['MemberID'] = (int)session('MemberID');
		$m->setDefaultConsignee($_GET['id'], $p);
		$this->ajaxReturn(null, '', 1);
	}
}
<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class ShippingAction extends AdminBaseAction{
	function index(){
		$p = array();
		$this->opIndex($p);
	}
	
	function add(){
		$Data = array('ShippingOrder'=>0, 'ShippingPrice'=>0, 'ShippingInsureRate'=>0, 'IsCod'=>0 );
		$this->assign('Data', $Data);
		$p = array();
		$this->opAdd( false, $p );
	}
	
	function saveAdd(){
		$this->opSaveAdd();
	}
	
	function modify(){
		$p = array();
		$this->opModify(false, $p);
	}
	
	function saveModify(){
		$p = array();
		$this->opSaveModify($p);
	}

	function del(){
		$p = array();
		$this->opDel( $p );
	}
	
	function sort(){
		$p = array();
		$this->opSort($p);
	}
}
<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class AdAction extends AdminBaseAction {
	private $_channelModelID = 11; //频道模型ID
	function index(){
		$p['HasPage'] = true;
		$this->opIndex($p);
	}
	
	function add(){
		$Data = array('AdTime'=>date('Y-m-d H:i:s'));
		$this->assign('Data', $Data);
		$this->opAdd( $this->_channelModelID );
	}
	
	function saveAdd(){
		$this->opSaveAdd();
	}
	
	function del(){
        $p = array();
		if( is_numeric($_REQUEST['p']) ){
			$p['Parameter']['p'] = $_REQUEST['p'];
		}
		$this->opDel( $p );
	}
	
	function modify(){
		$this->opModify( $this->_channelModelID );
	}
	
	function saveModify(){
		$this->opSaveModify();
	}
	
	function sort(){
		$p['Parameter'] = array('p'=>$_POST["p"]);
		$this->opSort($p);
	}
}
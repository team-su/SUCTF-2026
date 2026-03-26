<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class SpecialAction extends AdminBaseAction {
	function index(){
		$p['Parameter'] = array('SpecialCount'=>1);
		$this->opIndex($p);
	}
	
	function add(){
		$Data = array('SpecialOrder'=>0, 'IsEnable'=>1 );
		$this->assign('Data', $Data);
		$p = array();
		$this->opAdd( false, $p );
	}
	
	function saveAdd(){
        YdCache::deleteAll(); //信息管理有缓存，必须先清除
		$this->opSaveAdd();
	}
	
	function modify(){
		$p = array();
		$this->opModify(false, $p);
	}
	
	function saveModify(){
		$p = array();
        YdCache::deleteAll();
		$this->opSaveModify($p);
	}
	
	function del(){
		$p['DelFunctionName'] = 'delSpecial';
        YdCache::deleteAll();
		$this->opDel( $p );
	}
	
	//保存所有修改
	function saveAll(){
		$data = array(
				"SpecialID" => $_POST['SpecialID'],
				"SpecialName" => $_POST['SpecialName'],
				"SpecialOrder" => $_POST['SpecialOrder'],
		);
        $n = is_array($data['SpecialID']) ? count($data['SpecialID']) : 0;
		if($n > 0 ){
			$m = D('Admin/Special');
			$m->saveAll( $data );
			WriteLog();
			YdCache::deleteAll();
		}
		redirect(__URL__."/index");
	}
}
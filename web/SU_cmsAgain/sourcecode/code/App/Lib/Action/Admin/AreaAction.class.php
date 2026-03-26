<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class AreaAction extends AdminBaseAction {
	function index(){
		header("Content-Type:text/html; charset=utf-8");
		$AreaID = empty($_REQUEST['id']) ? 0 : (int)$_REQUEST['id'];
		$Parent = $AreaID; //当前区域的父级
		$Grand = 0; //当前区域的爷爷
		$m = D('Admin/Area');
		$data = $m->getArea($AreaID);
		if(!empty($data)){
			$n = is_array($data) ? count($data) : 0;
			for($i=0; $i<$n; $i++){
				$data[$i]['ChildCount'] = $m->getChildCount( $data[$i]['AreaID'] );
			}
		}
		
		if($Parent>0){
			$Grand = $m->where("AreaID={$Parent}")->getField('Parent');
		}
		
		$this->assign('Grand', $Grand);
		$this->assign('Parent', $Parent);
		$this->assign('AreaID', $AreaID);
		$this->assign('Data', $data);
		$this->display();
	}
	
	function saveAll(){
		$n = is_array($_POST['AreaNameEn']) ? count($_POST['AreaNameEn']) : 0;
		for($i=0; $i<$n; $i++){
			$AreaNameEn = $_POST['AreaNameEn'][$i];
			if( empty($AreaNameEn) ){  //如果为空则 自动生成英文
				$_POST['AreaNameEn'][$i] = yd_pinyin($_POST['AreaName'][$i], false, 'UTF8', 3);
			}
		}
		
		$data = array(
			"AreaID" => $_POST['AreaID'],
			"AreaName" => $_POST['AreaName'],
			"AreaNameEn" => $_POST['AreaNameEn'],
			"AreaOrder" => $_POST['AreaOrder'],
		);
	
		if( is_array($data['AreaID']) && count($data['AreaID']) > 0 ){
			$m = D('Admin/Area');
			$m->saveAll( $data );
		}
        WriteLog('', array('LogType'=>4, 'UserAction'=>'区域管理->保存所有'));
		$AreaID = empty($_REQUEST['id']) ? 0 : $_REQUEST['id'];
		redirect(__URL__."/index/id/{$AreaID}");
	}
	
	function del(){
		header("Content-Type:text/html; charset=utf-8");
		$AreaID = empty($_REQUEST['id']) ? 0 : $_REQUEST['id'];
		$m = D('Admin/Area');
		$n = $m->getChildCount($AreaID);
		if($n==0){
			$b = $m->delArea($AreaID);
			if($b){
                $description = "id:{$AreaID}";
                WriteLog($description, array('LogType'=>3, 'UserAction'=>'区域管理->删除区域'));
				$this->ajaxReturn($AreaID, '删除成功' , 1);
			}else{
				$this->ajaxReturn($AreaID, '删除失败' , 0);
			}
		}else{
			$this->ajaxReturn($AreaID, '请先删除下级数据' , 0);
		}
	}
	
	/**
	 * 批量删除
	 */
	function batchDel(){
		//如果有子栏目就不删除
		$m = D('Admin/Area');
		$n = count( $_REQUEST['id'] );
		for($i=0; $i < $n; $i++){
			$ChildCount = $m->getChildCount($_REQUEST['id'][$i]);
			if($ChildCount > 0){
				unset( $_REQUEST['id'][$i] );
			}
		}
		$p['Url'] = __URL__."/index/id/".$_REQUEST['AreaID'];
		$this->opDel( $p );
	}
	
	function add(){
		header("Content-Type:text/html; charset=utf-8");
		$Parent = empty($_POST['Parent']) ? 0 : $_POST['Parent'];
		$AreaName = trim($_POST['AreaName']);
		if(empty($AreaName)){
			$this->ajaxReturn(null, '区域名称不能为空！' , 0);
		}
		
		$list = str_replace(array("\r\n","\r"), "\n", $AreaName);
		$list = explode ("\n", $list);
		$data = array();
		foreach ($list as $v){
			$name = trim($v);
			$AreaNameEn = yd_pinyin($name,false,'UTF8',3);
			if(!empty($name)){
				$data[] = array(
					'Parent'=>$Parent, 
					'AreaName'=>$name,
					'AreaNameEn'=>$AreaNameEn,
				);
			}
		}
		
		if(empty($data)){
			$this->ajaxReturn(null, '区域数据不能为空！' , 0);
		}
		
		$m = D('Admin/Area');
		$b = $m->addAll($data);
		if($b){
            $description = var_export($data, true);
            WriteLog($description, array('LogType'=>4, 'UserAction'=>'区域管理->添加区域'));
			$this->ajaxReturn(null, '添加成功' , 1);
		}else{
			$this->ajaxReturn(null, '添加失败' , 0);
		}
	}
	
	/**
	 * 一键生成拼音
	 */
	function makePinyin(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Area');
		$data = $m->where("AreaNameEn='' and AreaName!=''")->field('AreaName,AreaID')->select();
		foreach ($data as $v){
			$AreaNameEn = yd_pinyin($v['AreaName'], false, 'UTF8', 3);
			$m->where("AreaID={$v['AreaID']}")->setField('AreaNameEn', $AreaNameEn);
		}
        WriteLog("", array('LogType'=>4, 'UserAction'=>'区域管理->一键生成拼音'));
		$this->ajaxReturn(null, '' , 1);
	}
}
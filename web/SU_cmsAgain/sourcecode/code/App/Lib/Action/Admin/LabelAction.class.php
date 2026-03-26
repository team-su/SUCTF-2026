<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class LabelAction extends AdminBaseAction{
	function index(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Label');
		import("ORG.Util.Page");
		$TotalPage = $m->getCount(); //获取标记总数
		$PageSize = $this->AdminPageSize;
		
		$Page = new Page($TotalPage, $PageSize);
		$Page->rollPage = $this->AdminRollPage;
		$ShowPage = $Page->show();
		
		$data= $m->getLabel(-1, -1, -1, $Page->firstRow, $Page->listRows);
		
		$this->assign('NowPage', $Page->getNowPage()); //分页条
		$this->assign('Label', $data);
		$this->assign('Page', $ShowPage); //分页条
		$this->display();
	}
	
	function add(){
		header("Content-Type:text/html; charset=utf-8");
	
		//模型属性信息==============================================
		$m = D('Admin/Attribute');
		$Attribute = $m->getAttribute(18);
		$Group = $m->getGroup(18);
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//======================================================
		$this->assign('Action', __URL__.'/SaveAdd');
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}
	
	function saveAdd(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Label');
		if( $m->create() ){
			if($m->add()){
				WriteLog("ID:".$m->getLastInsID() );
				$this->ajaxReturn(null, '添加成功!' , 1);
			}else{
				$this->ajaxReturn(null, '添加失败!' , 0);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}
	
	function del(){
		header("Content-Type:text/html; charset=utf-8");
		$LabelID = $_GET["LabelID"];
		$p = $_GET["p"];
		
		if( is_numeric($LabelID) && is_numeric($p)){
			D('Admin/Label')->safeDel($LabelID);
			WriteLog("ID:$LabelID");
		}
		redirect(__URL__."/index/p/$p");
	}
	
	function batchDel(){
		$id = $_POST['LabelID'];
		$NowPage = (int)$_POST["NowPage"];
		if( count($id) > 0 ){
			D('Admin/Label')->batchDelLabel($id);
			WriteLog("ID:".implode(',', $id));
		}
		redirect(__URL__."/index/p/$NowPage");
	}
	
	function batchSort(){
		$LabelOrder = $_POST['LabelOrder']; //排序
		$LabelID = $_POST['LabelOrderID']; //排序
		$NowPage = (int)$_POST["NowPage"];
		if( is_array($LabelID) && is_array($LabelOrder) && count($LabelID) > 0 && count($LabelOrder) > 0 ){
			D('Admin/Label')->batchSortLabel($LabelID, $LabelOrder);
			WriteLog();
		}
		redirect(__URL__."/index/p/$NowPage");
	}
	
	function modify(){
		header("Content-Type:text/html; charset=utf-8");
		//参数有效性检查===========================
		$LabelID = $_GET['LabelID'];
		if( !is_numeric($LabelID)){
			alert("非法参数", __URL__.'/index');
		}
		//====================================
	
		//模型属性信息=================================================
		$m = D('Admin/Attribute');
		$Attribute = $m->getAttribute(18);
		$Group = $m->getGroup(18);
	
		//获取专题数据======================================================
		$m = D('Admin/Label');
		$Info = $m->find( $LabelID );
		for($n = 0; $n < count($Attribute); $n++){
			if( $this->IsSelectedAttribute( $Attribute[$n]['DisplayType'] ) ){
				$Attribute[$n]['SelectedValue'] = $Info[ $Attribute[$n]['FieldName'] ]; //获取频道设置值
			}else{
				$Attribute[$n]['DisplayValue'] = $Info[ $Attribute[$n]['FieldName'] ];
			}
		}
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//==============================================================
	
		$this->assign('HiddenName', 'LabelID');
		$this->assign('HiddenValue', $LabelID);
		$this->assign('Action', __URL__.'/saveModify');
	
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}
	
	function saveModify(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Label');
		if( $m->create() ){
			if($m->save() === false){
				$this->ajaxReturn(null, '修改失败!' , 0);
			}else{
				WriteLog("ID:".$_POST['LabelID'] );
				$this->ajaxReturn(null, '修改成功!' , 1);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}
	
}
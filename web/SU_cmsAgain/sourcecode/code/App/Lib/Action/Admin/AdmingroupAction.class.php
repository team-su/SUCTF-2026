<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class AdminGroupAction extends AdminBaseAction {
	function index(){
		header("Content-Type:text/html; charset=utf-8");
		$IsSuperAdmin = $this->checkAdmin(false);
        $this->assign('IsSuperAdmin', $IsSuperAdmin);
		$m = D('Admin/AdminGroup');
		$p = array('Count'=>1);
		$this->assign('AdminGroup', $m->getAdminGroup(-1, $p));
		$this->display();
	}
	
	function add(){
		header("Content-Type:text/html; charset=utf-8");
	
		//模型属性信息==============================================
		$m = D('Admin/Attribute');
		$Attribute = $m->getAttribute(15);
		$Group = $m->getGroup(15);
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//======================================================
		$this->assign('Action', __URL__.'/SaveAdd');
		$this->assign('Group', $Group);
		$this->assign('AdminGroupID', intval(session('AdminGroupID')));
		$this->assign('Attribute', $Attribute);
		$this->display();
	}
	
	function saveAdd(){
		header("Content-Type:text/html; charset=utf-8");
		$this->checkAdmin();
		$m = D('Admin/AdminGroup');
		if( $m->create() ){
			$m->MenuTopPurview = is_array($m->MenuTopPurview) ? implode(',', $m->MenuTopPurview) : '';
			$m->MenuGroupPurview = is_array($m->MenuGroupPurview) ? implode(',', $m->MenuGroupPurview) : '';
			$m->MenuPurview = is_array($m->MenuPurview) ? implode(',', $m->MenuPurview) : '';
            $FieldName = 'ChannelPurview'.LANG_SET;
            $m->$FieldName =  is_array($m->$FieldName) ? implode(',', $m->$FieldName) : '';
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
        $this->checkAdmin();
		$m = D('Admin/AdminGroup');
		$id = (int)$_GET["AdminGroupID"];
		$data = "#tr$id";
		
		if( $m->hasData($id) ){
			$this->ajaxReturn($data, '当前分组存在会员数据，请先删除!' , 3);
		}
		
		if( !is_numeric($id) ){
			$this->ajaxReturn($data, '参数非法!' , 3);
		}
	
		//删除操作
		if( $m->where("IsSystem = 0 and AdminGroupID=$id")->delete() ){
			WriteLog("ID:$id");
			$this->ajaxReturn($data, '删除成功!' , 1);
		}else{
			$this->ajaxReturn($data, '删除失败!' , 0);
		}
	}
	
	function batchDel(){
        $this->checkAdmin();
		$id = $_POST['AdminGroupID'];
		$len = is_array($id) ? count($id) : 0;
		$m = D('Admin/AdminGroup');
		for($i = 0; $i < $len; $i++){
			if( is_numeric($id[$i]) && !$m->hasData($id[$i]) ){
			    $TempID = (int)$id[$i];
				$m->where("IsSystem=0 and AdminGroupID={$TempID}")->delete();
			}
		}
		WriteLog("ID:".implode(',', $id));
		redirect(__URL__."/index");
	}
	
	function modify(){
		header("Content-Type:text/html; charset=utf-8");
		//参数有效性检查===========================
		$AdminGroupID = $_GET['AdminGroupID'];
		if( !is_numeric($AdminGroupID)){
			alert("非法参数", __URL__.'/index');
		}
		//====================================
	
		//模型属性信息=================================================
		$m = D('Admin/Attribute');
		$Attribute = $m->getAttribute(15);
		$Group = $m->getGroup(15);
	
		//获取专题数据======================================================
		$m = D('Admin/AdminGroup');
		$Info = $m->find( $AdminGroupID );
		for($n = 0; $n < count($Attribute); $n++){
			if( $this->IsSelectedAttribute( $Attribute[$n]['DisplayType'] ) ){
				$Attribute[$n]['SelectedValue'] = $Info[ $Attribute[$n]['FieldName'] ]; //获取频道设置值
			}else{
				$Attribute[$n]['DisplayValue'] = $Info[ $Attribute[$n]['FieldName'] ];
			}
		}
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//==============================================================
	
		$this->assign('HiddenName', 'AdminGroupID');
		$this->assign('HiddenValue', $AdminGroupID);
		$this->assign('AdminGroupID', $AdminGroupID);
		$this->assign('Action', __URL__.'/saveModify');
		
		$this->assign('MenuTopPurview', $Info['MenuTopPurview']);
		$this->assign('MenuGroupPurview', $Info['MenuGroupPurview']);
		$this->assign('ChannelPurview', $Info['ChannelPurview'.LANG_SET]);
		$this->assign('MenuPurview', $Info['MenuPurview']);
	
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}
	
	function saveModify(){
		header("Content-Type:text/html; charset=utf-8");
        $this->checkAdmin();
		$m = D('Admin/AdminGroup');
		if( $m->create() ){
			$m->MenuTopPurview =  is_array($m->MenuTopPurview) ? implode(',', $m->MenuTopPurview) : '';
			$m->MenuGroupPurview =  is_array($m->MenuGroupPurview) ? implode(',', $m->MenuGroupPurview) : '';
			$m->MenuPurview =  is_array($m->MenuPurview) ? implode(',', $m->MenuPurview) : '';
			$FieldName = 'ChannelPurview'.LANG_SET;
			$m->$FieldName =  is_array($m->$FieldName) ? implode(',', $m->$FieldName) : '';
			if($m->save() === false){
				$this->ajaxReturn(null, '修改失败!' , 0);
			}else{
				WriteLog("ID:".$_POST['AdminGroupID']);
				$this->ajaxReturn(null, '修改成功!' , 1);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}

}
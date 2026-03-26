<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class TypeAction extends AdminBaseAction{
	function index(){
		$p['HasPage'] = true;
		$p['Parameter']['IsCount'] = 1; //表示统计分组个数和属性个数
		$this->opIndex($p);
	}
	
	function add(){
		$Data = array('TypeOrder'=>0);
		$this->assign('Data', $Data);
		$p = array();
		$this->opAdd( false, $p );
	}
	
	function saveAdd(){
		$p = array();
		$this->opSaveAdd( $p );
	}
	
	function modify(){
		$p = array();
		$this->opModify(false, $p);
	}
	
	function saveModify(){
		$this->opSaveModify();
	}

	function del(){
		$p = array();
		$this->opDel( $p );
	}
	
	function sort(){
		$this->opSort();
	}
	
	//类型分组管理 开始======================================
	function group(){
		$p['ModuleName'] = 'TypeGroup';
		$TypeID = intval($_REQUEST['TypeID']);
		$p['Parameter']['TypeID'] = $TypeID;
		$this->assign('TypeID', $TypeID);
		$this->opIndex($p);
	}
	
	function addGroup(){
		$TypeID = intval($_REQUEST['TypeID']);
		$Data = array('TypeGroupOrder'=>0);
		$this->assign('Data', $Data);
		$p['ModuleName'] = 'TypeGroup';
		$p['Action'] = __URL__.'/saveAddGroup';
		
		$m = D('Admin/Type');
		$TypeName = $m->where("TypeID=$TypeID")->getField('TypeName');
		$this->assign('TypeName', $TypeName);
		
		$this->assign('TypeID', $TypeID);
		$this->opAdd( false, $p );
	}
	
	function saveAddGroup(){
		$TypeID = intval($_REQUEST['TypeID']);
		$p['ModuleName'] = 'TypeGroup';
		$this->assign('TypeID', $TypeID);
		$this->opSaveAdd( $p );
	}
	
	function modifyGroup(){
		$TypeID = intval($_REQUEST['TypeID']);
		$p['ModuleName'] = 'TypeGroup';
		$p['Action'] = __URL__.'/saveModifyGroup';
		
		$m = D('Admin/Type');
		$TypeName = $m->where("TypeID=$TypeID")->getField('TypeName');
		$this->assign('TypeName', $TypeName);
		
		$this->assign('TypeID', $TypeID);
		$this->opModify(false, $p);
	}
	
	function saveModifyGroup(){
		$p['ModuleName'] = 'TypeGroup';
		$this->opSaveModify($p);
	}
	
	function delGroup(){
		$TypeID = intval($_REQUEST['TypeID']);
		$p['ModuleName'] = 'TypeGroup';
		$p['Url'] = __URL__.'/group/TypeID/'.$TypeID;
		$this->opDel( $p );
	}
	
	function sortGroup(){
		$TypeID = intval($_REQUEST['TypeID']);
		$p['ModuleName'] = 'TypeGroup';
		$p['Url'] = __URL__.'/group/TypeID/'.$TypeID;
		$this->opSort($p);
	}
	//类型分组管理 结束======================================
	
	//类型属性管理 开始======================================
	function attribute(){
		$p['ModuleName'] = 'TypeAttribute';
		$TypeID = intval($_REQUEST['TypeID']);
		$p['GetFunctionName'] = 'getAllTypeAttribute';
		$p['Parameter']['TypeID'] = $TypeID;
		
		//获取类型
		$m = D('Admin/Type');
		$Type = $m->getType(-1, -1);
		$this->assign('Type', $Type);
		$this->assign('TypeID', $TypeID);
		
		$this->opIndex($p);
	}
	
	function addAttribute(){
		$TypeID = intval($_REQUEST['TypeID']);
		$Data = array('TypeAttributeOrder'=>0, 'IsSearch'=>0, 'IsEnable'=>1);
		$this->assign('Data', $Data);
		$p['ModuleName'] = 'TypeAttribute';
		$p['Action'] = __URL__.'/saveAddAttribute';
	
		$m = D('Admin/Type');
		$TypeName = $m->where("TypeID=$TypeID")->getField('TypeName');
		$this->assign('TypeName', $TypeName);
		
		//获取分组信息
		$mg = D('Admin/TypeGroup');
		$TypeGroup = $mg->getTypeGroup( array('TypeID'=> $TypeID) );
		$this->assign('TypeGroup', $TypeGroup);
	
		$this->assign('TypeID', $TypeID);
		$this->opAdd( false, $p );
	}
	
	function saveAddAttribute(){
		$TypeID = intval($_REQUEST['TypeID']);
		$p['ModuleName'] = 'TypeAttribute';
		$this->assign('TypeID', $TypeID);
		$this->opSaveAdd( $p );
	}
	
	function modifyAttribute(){
		$TypeID = intval($_REQUEST['TypeID']);
		$p['ModuleName'] = 'TypeAttribute';
		$p['Action'] = __URL__.'/saveModifyAttribute';
	
		$m = D('Admin/Type');
		$TypeName = $m->where("TypeID=$TypeID")->getField('TypeName');
		$this->assign('TypeName', $TypeName);
		
		//获取分组信息
		$mg = D('Admin/TypeGroup');
		$TypeGroup = $mg->getTypeGroup( array('TypeID'=> $TypeID) );
		$this->assign('TypeGroup', $TypeGroup);
	
		$this->assign('TypeID', $TypeID);
		$this->opModify(false, $p);
	}
	
	function saveModifyAttribute(){
		$p['ModuleName'] = 'TypeAttribute';
		$this->opSaveModify($p);
	}
	
	function delAttribute(){
		$TypeID = intval($_REQUEST['TypeID']);
		$p['ModuleName'] = 'TypeAttribute';
		$p['DelFunctionName'] = 'delTypeAttribute';
		$p['Url'] = __URL__.'/attribute/TypeID/'.$TypeID;
		$this->opDel( $p );
	}
	
	function sortAttribute(){
		$TypeID = intval($_REQUEST['TypeID']);
		$p['ModuleName'] = 'TypeAttribute';
		$p['Url'] = __URL__.'/attribute/TypeID/'.$TypeID;
		$this->opSort($p);
	}
	//类型属性管理 结束======================================
}
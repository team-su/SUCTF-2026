<?php

class MemberGroupAction extends AdminBaseAction {
	
	function index(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/MemberGroup');
		$p = array('Count'=>1);
		$this->assign('MemberGroup', $m->getMemberGroup($p));
		$this->display();
	}
	
	function add(){
		header("Content-Type:text/html; charset=utf-8");
		$Data = array('DiscountRate'=>1);
		$this->assign('Data', $Data);
		$this->assign('Action', __URL__.'/saveAdd');
		$this->display();
	}
	
	function saveAdd(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/MemberGroup');
		if( $m->create() ){
			$m->MenuTopPurview = is_array($m->MenuTopPurview) ? implode(',', $m->MenuTopPurview) : '';
			$m->MenuGroupPurview = is_array($m->MenuGroupPurview) ? implode(',', $m->MenuGroupPurview) : '';
			$m->MenuPurview = is_array($m->MenuPurview) ? implode(',', $m->MenuPurview) : '';
            $FieldName = 'ChannelPurview'.LANG_SET;
            $m->$FieldName = is_array($m->$FieldName) ? implode(',', $m->$FieldName) : '';
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
		$m = D('Admin/MemberGroup');
		$id = $_GET["MemberGroupID"];
		$data = "#tr$id";
		
		if( $m->hasData($id) ){
			$this->ajaxReturn($data, '当前分组存在会员数据，请先删除!' , 3);
		}

		if( !is_numeric($id) ){
			$this->ajaxReturn($data, '参数非法!' , 3);
		}
	
		//删除操作
		if( $m->where("IsSystem = 0 and MemberGroupID=$id")->delete() ){
			WriteLog("ID:$id");
			$this->ajaxReturn($data, '删除成功!' , 1);
		}else{
			$this->ajaxReturn($data, '删除失败!' , 0);
		}
	}
	
	function batchDel(){
		$id = $_POST['MemberGroupID'];
		$len = is_array($id) ? count($id) : 0;
		$m = D('Admin/MemberGroup');
		for($i = 0; $i < $len; $i++){
			if( is_numeric($id[$i]) && !$m->hasData($id[$i])){
				$m->where("IsSystem = 0 and MemberGroupID=$id[$i]")->delete();
			}
		}
		WriteLog("ID:".implode(',', $id));
		redirect(__URL__.'/index');
	}
	
	function modify(){
		header("Content-Type:text/html; charset=utf-8");
		//参数有效性检查===========================
		$MemberGroupID = $_GET['MemberGroupID'];
		if( !is_numeric($MemberGroupID)){
			alert("非法参数", __URL__.'/index');
		}
		//====================================
		
		$m = D('Admin/MemberGroup');
		$Data = $m->find( $MemberGroupID );
		$this->assign('Data', $Data);
		
		$this->assign('HiddenName', 'MemberGroupID');
		$this->assign('HiddenValue', $MemberGroupID);
		
		$this->assign('MenuTopPurview', $Data['MenuTopPurview']);
		$this->assign('MenuGroupPurview', $Data['MenuGroupPurview']);
		$this->assign('ChannelPurview', $Data['ChannelPurview'.LANG_SET]);
		$this->assign('MenuPurview', $Data['MenuPurview']);
		
		$this->assign('Action', __URL__.'/saveModify');
		$this->display();
	}
	
	function saveModify(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/MemberGroup');
		if( $m->create() ){
			$m->MenuTopPurview = is_array($m->MenuTopPurview) ? implode(',', $m->MenuTopPurview) : '';
			$m->MenuGroupPurview = is_array($m->MenuGroupPurview) ? implode(',', $m->MenuGroupPurview) : '';
			$m->MenuPurview = is_array($m->MenuPurview) ? implode(',', $m->MenuPurview) : '';
			$FieldName = 'ChannelPurview'.LANG_SET;
			$m->$FieldName = is_array($m->$FieldName) ? implode(',', $m->$FieldName) : '';
			if($m->save() === false){
				$this->ajaxReturn(null, '修改失败!' , 0);
			}else{
				WriteLog("ID:".$_POST['MemberGroupID'] );
				$this->ajaxReturn(null, '修改成功!' , 1);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}

}
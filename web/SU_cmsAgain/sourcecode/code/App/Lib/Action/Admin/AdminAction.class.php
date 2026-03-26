<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class AdminAction extends AdminBaseAction {
	function index(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Admin');
		import("ORG.Util.Page");
		$TotalPage = $m->count();
		$PageSize = $this->AdminPageSize;
	
		$Page = new Page($TotalPage, $PageSize);
		$Page->rollPage = $this->AdminRollPage;
		$ShowPage = $Page->show();
	
		$Admin= $m->getAdmin($Page->firstRow, $Page->listRows);

        $IsSuperAdmin = $this->checkAdmin(false);
        $this->assign('IsSuperAdmin', $IsSuperAdmin);

		$this->assign('NowPage', $Page->getNowPage()); //分页条
		$this->assign('Admin', $Admin);
		$this->assign('Page', $ShowPage); //分页条
		$this->display();
	}
	
	function del(){
        $this->checkAdmin();
		header("Content-Type:text/html; charset=utf-8");
		$AdminID = (int)$_GET["AdminID"];
		$p = (int)$_GET["p"];
	
		if( is_numeric($AdminID) && is_numeric($p)){
		    $m = D('Admin/Admin');
            $m->where("IsSystem = 0 and AdminID={$AdminID}")->delete();
			WriteLog("ID:$AdminID");
		}
		redirect(__URL__."/index/p/$p");
	}
	
	function batchDel(){
        $this->checkAdmin();
		$id = $_POST['AdminID'];
		$NowPage = (int)$_POST["NowPage"];
		$len = is_array($id) ? count($id) : 0;
		
		$m = D('Admin/Admin');
		for($i = 0; $i < $len; $i++){
			if( is_numeric($id[$i]) ){
				$m->where("IsSystem = 0 and AdminID=$id[$i]")->delete();
			}
		}
		WriteLog("ID:".implode(',', $id));
		redirect(__URL__."/Index/p/$NowPage");
	}
	
	function modify(){
		header("Content-Type:text/html; charset=utf-8");
		//参数有效性检查===========================
		$AdminID = $_GET['AdminID'];
		if( !is_numeric($AdminID)){
			alert("非法参数", __URL__.'/Admin');
		}
		//====================================
	
		//模型属性信息=================================================
		$m = D('Admin/Attribute');
		$Attribute = $m->getAttribute(14);
		$Group = $m->getGroup(14);
	
		//获取专题数据======================================================
		$m = D('Admin/Admin');
		$Info = $m->find( $AdminID );
		for($n = 0; $n < count($Attribute); $n++){
			if( $this->IsSelectedAttribute( $Attribute[$n]['DisplayType'] ) ){
				$Attribute[$n]['SelectedValue'] = $Info[ $Attribute[$n]['FieldName'] ]; //获取频道设置值
			}else{
				$Attribute[$n]['DisplayValue'] = $Info[ $Attribute[$n]['FieldName'] ];
			}
			
			if( $Info['IsSystem'] == 1 ){
				if( $Attribute[$n]['FieldName'] == 'IsLock' ){
					unset($Attribute[$n]);
				}
				
				if( $Attribute[$n]['FieldName'] == 'AdminGroupID' ){
					$Attribute[$n]['DisplayType'] = "label";
					$ag = D('Admin/AdminGroup');
					$Attribute[$n]['DisplayValue'] = $ag->getGroupName($Info['AdminGroupID']);
				}
			}

		}
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//==============================================================
	
		$this->assign('HiddenName', 'AdminID');
		$this->assign('HiddenValue', $AdminID);
		$this->assign('Action', __URL__.'/saveModify');
	
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}
	
	function saveModify(){
		header("Content-Type:text/html; charset=utf-8");
        $this->checkAdmin();
		$m = D('Admin/Admin');
		if( $m->create() ){
			if($m->save() === false){
				$this->ajaxReturn(null, '修改失败!' , 0);
			}else{
				WriteLog("ID:".$_POST['AdminID'].' '.$_POST['AdminName']);
				$this->ajaxReturn(null, '修改成功!' , 1);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}
	
	function batchLock(){
        $this->checkAdmin();
		$id = $_POST['AdminID'];
		$NowPage = (int)$_POST["NowPage"];
		$Lock = $_GET['Lock'];  //审核值
		if( count($id) > 0 ){
			D('Admin/Admin')->batchLock( $id , $Lock);
		}
		WriteLog("ID:".implode(',', $id));
		redirect(__URL__."/index/p/$NowPage");
	}

	function batchModifyPwd(){
        $this->checkAdmin();
		$id = $_POST['AdminID'];
		$pwd1 = $_POST['pwd1'];
		$pwd2 = $_POST['pwd2'];
		if($pwd1 !== $pwd2){
            $this->ajaxReturn(null, '2次输入密码不一致！' , 1);
        }
		if( count($id) > 0){
			// md5 return 32-character hexadecimal number
			$m = D('Admin/Admin');
			$pwd = yd_password_hash($pwd1);
			$m->batchModifyPwd( $id , $pwd );
			WriteLog("ID:".implode(',', $id));
			$this->ajaxReturn(null, '修改成功!' , 1);
		}
		$this->ajaxReturn(null, '修改失败!' , 0);
	}
	
	function add(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/AdminGroup');
		$data = $m->getAdminGroup();
		$this->assign('AdminGroup', $data);
		$this->assign('Action', __URL__.'/SaveAdd');
		$this->display();
	}
	
	function saveAdd(){
		header("Content-Type:text/html; charset=utf-8");
        $this->checkAdmin();
		$AdminName = trim($_POST['AdminName']);
		$AdminGroupID = trim($_POST['AdminGroupID']);
		//判断前台用户是否存在
		$m1 = D('Admin/Member');
		$where1['MemberName'] = $AdminName;
		$n = $m1->where($where1)->count();
		if($n >= 1){
			$this->ajaxReturn(null, '会员名称已经存在!' , 0);
		}
		//判断管理员是否存在
		$m2 = D('Admin/Admin');
		$where2['AdminName'] = $AdminName;
		$n = $m2->where($where2)->count();
		if($n >= 1){
			$this->ajaxReturn(null, '管理员名称已经存在!' , 0);
		}
		
		$pwd1 = $_POST['pwd1'];
		$pwd2 = $_POST['pwd2'];
		if( empty($pwd1) ){
			$this->ajaxReturn(null, '密码不能为空!' , 0);
		}
		if( empty($pwd2) ){
			$this->ajaxReturn(null, '重复密码不能为空!' , 0);
		}
		if( $pwd1 != $pwd2 ){
			$this->ajaxReturn(null, '二次输入的密码不同!' , 0);
		}
        $pwd = yd_password_hash($pwd1);
		
		$MemberRealName= trim($_POST['MemberRealName']);
		$MemberMobile = trim($_POST['MemberMobile']);
		$MemberEmail = trim($_POST['MemberEmail']);
		$MemberGender = trim($_POST['MemberGender']);
		$MemberTelephone= trim($_POST['MemberTelephone']);
		$data = array(
			'MemberName'=>$AdminName, 'MemberPassword'=>$pwd, 'IsCheck'=>1, 'RegisterTime'=>date('Y-m-d H:i:s'),
			'MemberRealName'=>$MemberRealName, 'MemberMobile'=>$MemberMobile, 'MemberEmail'=>$MemberEmail,
			'MemberGender'=>$MemberGender, 'MemberTelephone'=>$MemberTelephone
		);
		$MemberID = $m1->data($data)->add(); //返回新添加用户的MemberID
		if( $MemberID > 0){
			$data = array(
			        'AdminName'=>$AdminName, 'AdminPassword'=>$pwd, 'MemberID'=>$MemberID,
					'AdminGroupID'=>$AdminGroupID,
            );
			if( $m2->data($data)->add() ){
				WriteLog($AdminName);
				$this->ajaxReturn(null, '添加成功!' , 1);
			}else{
				$this->ajaxReturn(null, '添加失败!' , 0);
			}
		}else{
			$this->ajaxReturn(null, '添加失败!' , 1);
		}
	}
}
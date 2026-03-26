<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class ResumeAction extends MemberBaseAction {
	function index(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Resume');
		import("ORG.Util.Page");
		$gid = (int)session('MemberID');
		$TotalPage = $m->getCount("GuestID=$gid"); //获取留言总数
		$PageSize = $this->AdminPageSize;
		
		$Page = new Page($TotalPage, $PageSize);
		$Page->rollPage = $this->AdminRollPage;
		$ShowPage = $Page->show();
		
		$data = $m->getResume($Page->firstRow, $Page->listRows, $gid);
		
		$this->assign('NowPage', $Page->getNowPage()); //分页条
		$this->assign('Resume', $data);
		$this->assign('Page', $ShowPage); //分页条
		$this->display();
	}
	
	function delResume(){
		header("Content-Type:text/html; charset=utf-8");
		$ResumeID = intval($_GET["ResumeID"]);
		$p = (int)$_GET["p"];
	
		if( is_numeric($ResumeID) && is_numeric($p)){
		    $MemberID = (int)session('MemberID');
			$where = "ResumeID=$ResumeID and GuestID={$MemberID}";
			if( D('Admin/Resume')->where($where)->delete() ){
				WriteLog("ID:$ResumeID");
				redirect(__URL__."/Index/p/$p");
			}
		}
	}
	
	function batchDelResume(){
		$id = $_POST['ResumeID'];
		$NowPage = intval($_POST["NowPage"]);
		if( count($id) > 0 ){
            $MemberID = (int)session('MemberID');
			D('Admin/Resume')->batchDelResume($id, $MemberID);
			WriteLog("ID:".implode(',', $id));
		}
		redirect(__URL__."/Index/p/$NowPage");
	}

	function modify(){
		header("Content-Type:text/html; charset=utf-8");
		//参数有效性检查===========================
		$ResumeID = $_GET['ResumeID'];
		if( !is_numeric($ResumeID)){
			alert("非法参数", __URL__.'/Index');
		}
        $MemberID = (int)session('MemberID');
		//====================================
		$m = D('Admin/Resume');
		$Info = $m->findResume( $ResumeID ,$MemberID);
		$this->assign('Resume', $Info);
		$this->assign('Action', __URL__.'/saveModify');
		$this->display();
	}
	
	function saveModify(){
		header("Content-Type:text/html; charset=utf-8");
		$_POST = YdInput::checkReg( $_POST );
		$MemberID = (int)session('MemberID');
		$m = D('Admin/Resume');
		if( $m->create() ){
		    $m->where("GuestID={$MemberID}");
			if($m->save() === false){
				$this->ajaxReturn(null, '修改失败!' , 0);
			}else{
				WriteLog( "ID:".$_POST['ResumeID'] );
				$this->ajaxReturn(null, '修改成功!' , 1);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}
}
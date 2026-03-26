<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class GuestbookAction extends MemberBaseAction {
	function index(){
		//$this->checkPurview();
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Guestbook');
		import("ORG.Util.Page");
		$gid = (int)session('MemberID');
		$TotalPage = $m->getCount("GuestID=$gid"); //获取留言总数
		$PageSize = $this->AdminPageSize;
		
		$Page = new Page($TotalPage, $PageSize);
		$Page->rollPage = $this->AdminRollPage;
		$ShowPage = $Page->show();
		
		$Message= $m->getMessage($Page->firstRow, $Page->listRows, -1, $gid);
		getAllInfo($Message, 6);
		
		$this->assign('NowPage', $Page->getNowPage()); //分页条
		$this->assign('Message', $Message);
		$this->assign('Page', $ShowPage); //分页条
		$this->display();
	}
	
	function delMessage(){
		header("Content-Type:text/html; charset=utf-8");
		$MessageID = $_GET["MessageID"];
		$p = $_GET["p"];
	
		if( is_numeric($MessageID) && is_numeric($p)){
			$where = "MessageID=$MessageID and GuestID=".session('MemberID');
			if( D('Admin/Guestbook')->where($where)->delete() ){
				WriteLog("ID:$MessageID");
				redirect(__URL__."/Index/p/$p");
			}
		}
	}
	
	function batchDelMessage(){
		$id = $_POST['MessageID'];
		$NowPage = intval($_POST["NowPage"]);
		if( count($id) > 0 ){
			D('Admin/Guestbook')->batchDelMessage($id, session('MemberID'));
			WriteLog("ID:".implode(',', $id));
		}
		redirect(__URL__."/Index/p/$NowPage");
	}
}
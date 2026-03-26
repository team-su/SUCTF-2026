<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class CommentAction extends AdminBaseAction {
	function index(){
		$MemberID = (int)session('MemberID');
        $SearchWords = !empty($_REQUEST['SearchWords']) ? YdInput::checkKeyword( $_REQUEST['SearchWords'] ) : '';
        $IsCheck = isset($_REQUEST['IsCheck']) && is_numeric($_REQUEST['IsCheck'])  ? (int)$_REQUEST['IsCheck'] : -1;
		$p = array(
			'HasPage' => true,
			'Parameter' => array('SearchWords' =>$SearchWords, 'ReplyComments' => 1, 'IsCheck'=>$IsCheck),
		);
		$this->opIndex($p);
	}
	
	function del(){
	    $nowPage = isset($_REQUEST['p']) ? intval($_REQUEST['p']) : 1;
        $SearchWords = !empty($_REQUEST['SearchWords']) ? YdInput::checkKeyword( $_REQUEST['SearchWords'] ) : '';
        $IsCheck = isset($_REQUEST['IsCheck']) && is_numeric($_REQUEST['IsCheck'])  ? (int)$_REQUEST['IsCheck'] : -1;
		$p['Parameter'] =  array('p' =>$nowPage, 'SearchWords'=>$SearchWords, 'IsCheck'=>$IsCheck);
		$this->opDel( $p );
	}
	
	function batchCheck(){
		$id = $_POST['id'];  //数组
        $SearchWords = !empty($_REQUEST['SearchWords']) ? YdInput::checkKeyword( $_REQUEST['SearchWords'] ) : '';
        $IsCheck = isset($_REQUEST['IsCheck']) && is_numeric($_REQUEST['IsCheck'])  ? (int)$_REQUEST['IsCheck'] : -1;  //不能使用empty判断，0也是空
		$NowPage = (int)$_POST["p"];
		$Check = (int)$_GET['Check'];  //审核值
		if( count($id) > 0 ){
			$m = D('Admin/Comment');
			$m->batchCheckComment( $id , $Check);
			WriteLog("ID:".implode(',', $id));
		}
		redirect(__URL__."/index/p/$NowPage/IsCheck/$IsCheck/SearchWords/{$SearchWords}");
	}
	
	/**
	 * 管理员回复
	 */
	function reply(){
		header("Content-Type:text/html; charset=utf-8");
		$CommentID = intval($_GET['id']);
		$m = D('Admin/Comment');
		//查询参数
		$p = array(
				'CommentID' => $CommentID,
				'Parent' => 0,
				'ReplyComments' => 1
		);
		$data = $m->getComment(-1, -1, $p);
		$this->assign('CommentID', $CommentID);
		$this->assign('InfoID', isset($data[0]['InfoID']) ? $data[0]['InfoID'] : 0);
		$this->assign('Data', $data);
		$this->display();
	}
	
	function saveReply(){
		header("Content-Type:text/html; charset=utf-8");
		$MemberID = intval(session("AdminMemberID"));
		$CommentID = intval($_POST['CommentID']);
		$m = D('Admin/Comment');
		$data['InfoID'] = $m->where("CommentID=$CommentID")->getField('InfoID');
		$data['GuestID'] = $MemberID;
		$data['GuestName'] = session('AdminName');
		$data['GuestIP'] = get_client_ip();
		
		$data['CommentContent'] = strip_tags($_POST['CommentContent']);
		$tm = time();
		$data['CommentTime'] = date('Y-m-d H:i:s', $tm); 
		$data['IsCheck'] = 1;
		$data['IsLanguage'] = get_language_id();
		$data['Parent'] = intval($_POST['CommentID']);
		$newCommentID = $m->add($data);
		if( $newCommentID ){
			$m1 = D('Admin/Member');
			$MemberAvatar = $m1->where('MemberID='.$MemberID)->getField('MemberAvatar');
			$data['MemberAvatar'] = DefaultAvatar($MemberAvatar);
			$data['CommentTime'] = yd_friend_date($tm);
			$data['CommentID'] = $newCommentID;
			$this->ajaxReturn($data, '回复成功!' , 1);
		}else{
			$this->ajaxReturn(null, '回复失败!' , 0);
		}
	}
	
	function delReply(){
		header("Content-Type:text/html; charset=utf-8");
		$CommentID = intval($_GET["id"]);
		$m = D('Admin/Comment');
		$result = $m->delComment($CommentID);
		if($result){
			$this->ajaxReturn($CommentID, '删除成功' , 1);
		}else{
			$this->ajaxReturn(null, '删除失败' , 0);
		}
	}
}
?>
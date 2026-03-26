<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class CommentAction extends MemberBaseAction {
	//后台管理首页
    function index(){
    	$MemberID = (int)session('MemberID');
    	$p = array(
    			'HasPage' => true,
    			'PageSize'=>8,
    			'Parameter' => array('GuestID' =>$MemberID, 'ReplyComments' => 1),
    			'NotPageParameterKey'=>array('GuestID'),
    	);
    	$this->opIndex($p);
    }
    
    function del(){
    	$MemberID = (int)session('MemberID');
		$p = array(
			'Parameter' => array('p' =>intval( $_REQUEST['p']), 'GuestID' =>$MemberID),
			'NotPageParameterKey'=>array('GuestID'),
		);
		$this->opDel( $p );
    }
    
    function reply(){
    	header("Content-Type:text/html; charset=utf-8");
    	$CommentID = intval($_GET['id']);
    	$m = D('Admin/Comment');
    	//查询参数
    	$p = array(
    		'CommentID' => $CommentID,
	    	'GuestID' => (int)session('MemberID'),
	    	'Parent' => 0,
	    	'ReplyComments' => 1
    	);
    	$data = $m->getComment(-1, -1, $p);
    	$this->assign('CommentID', $CommentID);
    	$this->assign('InfoID', isset($data[0]['InfoID']) ? $data[0]['InfoID'] : 0);
    	$this->assign('Data', $data);
    	$this->display();
    }
}
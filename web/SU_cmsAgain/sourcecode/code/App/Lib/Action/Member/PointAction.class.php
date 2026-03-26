<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class PointAction extends MemberBaseAction {
	function index(){
		$MemberID = (int)session('MemberID');
		$p = array(
				'HasPage' => true,
				'Parameter' => array('MemberID' =>$MemberID),
		);
		$m = D('Admin/Point');
		$TotalPoint = $m->getTotalPoint($MemberID);
		$this->assign('TotalPoint', $TotalPoint);
		$this->opIndex($p);
	}
}
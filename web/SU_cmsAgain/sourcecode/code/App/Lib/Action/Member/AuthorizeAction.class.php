<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class AuthorizeAction extends MemberBaseAction {
	function index(){
		header("Content-Type:text/html; charset=utf-8");
		$MemberGroupID = (int)session('MemberGroupID');
		if( $MemberGroupID == 100){ //只有客服才能按客服检索
			$this->_indexKefu(); //客服
		}else{
			$this->_indexAgent(); //代理
		}
	}
	
	//客服-我的授权
	private function _indexKefu(){
		$Host = !empty($_REQUEST['Host']) ? $_REQUEST['Host'] : '';
        $Host = YdInput::checkLetterNumber($Host);
		$CustomerID = !empty($_REQUEST['CustomerID']) ? intval($_REQUEST['CustomerID']) : -1;
		$OperatorID = (int)session('MemberID');
		$MemberGroupID = (int)session('MemberGroupID');
		$CurrentCustomerID = $CustomerID;

		//获取我的客户数据============================
		$ma = D('Admin/Authorize');
		$CustomerData = $ma->getCustomerData($OperatorID);
		$this->assign('CustomerData', $CustomerData);
		if( $CustomerID == -1 ){
			$CurrentCustomerID = '';
			foreach ($CustomerData as $c){
				$CurrentCustomerID .= $c['MemberID'].',';
			}
			$CurrentCustomerID = trim($CurrentCustomerID, ',');
		}
		//=======================================

		$this->assign('MemberGroupID', $MemberGroupID);
		$this->assign('CustomerID', $CustomerID);
		//=====================================
		
		$m = D('Admin/Authorize');
		if( empty($Host) ){
			import("ORG.Util.Page");
			$TotalPage = $m->getUserCount($Host, $CurrentCustomerID); //总页数
			$PageSize = $this->AdminPageSize;
			$Page = new Page($TotalPage, $PageSize);
				
			if($Host!=''){
				$Page->parameter .= "&Host=$Host";
			}
			if($CustomerID!=-1){
				$Page->parameter .= "&CustomerID=$CustomerID";
			}
			$Page->rollPage = $this->AdminRollPage;
			$ShowPage = $Page->show();
			$Data = $m->getUserAuthorize($Page->firstRow, $Page->listRows, $Host, $CurrentCustomerID);
			$this->assign('NowPage', $Page->getNowPage()); //分页条
			$this->assign('Page', $ShowPage); //分页条
		}else{
			$Data = $m->searchAuthorize($Host);
		}
		
		if(!empty($Data)){
			$mm = D('Admin/Member');
			$n = is_array($Data) ? count($Data) : 0;
			for($i=0; $i<$n;$i++){
				$CustomerID = $Data[$i]['CustomerID'];
				if( $CustomerID > 0 ){
					$CustomerName = $mm->where("MemberID=$CustomerID")->getField('MemberName');
					$Data[$i]['CustomerName'] = $CustomerName;
				}
		
				$OperatorID = $Data[$i]['OperatorID'];
				if( $OperatorID > 0 ){
					$OperatorName = $mm->where("MemberID=$OperatorID")->getField('MemberName');
					$Data[$i]['OperatorName'] = $OperatorName;
				}
			}
		}
		$AuthorizeCount = $m->GetUserAuthorizeCount($CurrentCustomerID);
		
		$this->assign('AuthorizeCount', $AuthorizeCount);
		$this->assign('Host', $Host); //当前频道
		$this->assign('Data', $Data);
		$this->display();
	}
	
	//代理
	private function _indexAgent(){
		$Host = !empty($_REQUEST['Host']) ? $_REQUEST['Host'] : '';
        $Host = YdInput::checkLetterNumber($Host);
		$OperatorID = (int)session('MemberID');
		
		$m = D('Admin/Authorize');
		if( empty($Host) ){
			import("ORG.Util.Page");
			$TotalPage = $m->getAgentCount($Host, $OperatorID); //总页数
			$PageSize = $this->AdminPageSize;
			$Page = new Page($TotalPage, $PageSize);
		
			if($Host!=''){
				$Page->parameter .= "&Host=$Host";
			}
			$Page->rollPage = $this->AdminRollPage;
			$ShowPage = $Page->show();
			$Data = $m->getAgentAuthorize($Page->firstRow, $Page->listRows, $Host, $OperatorID);
			$this->assign('NowPage', $Page->getNowPage()); //分页条
			$this->assign('Page', $ShowPage); //分页条
		}else{
			$Data = $m->searchAuthorize($Host);
		}
		
		if(!empty($Data)){
			$mm = D('Admin/Member');
			$n = is_array($Data) ? count($Data) : 0;
			for($i=0; $i<$n;$i++){
				$CustomerID = $Data[$i]['CustomerID'];
				if( $CustomerID > 0 ){
					$CustomerName = $mm->where("MemberID=$CustomerID")->getField('MemberName');
					$Data[$i]['CustomerName'] = $CustomerName;
				}
		
				$TempID = $Data[$i]['OperatorID'];
				if( $TempID > 0 ){
					$OperatorName = $mm->where("MemberID=$TempID")->getField('MemberName');
					$Data[$i]['OperatorName'] = $OperatorName;
				}
			}
		}
		$AuthorizeCount = $m->GetAgentAuthorizeCount($OperatorID);
		
		$this->assign('AuthorizeCount', $AuthorizeCount);
		$this->assign('Host', $Host); //当前频道
		$this->assign('Data', $Data);
		$this->display('indexagent');
	}
	
	//设置域名所有者
	function setCustomer(){
		header("Content-Type:text/html; charset=utf-8");
		$CustomerID = intval($_REQUEST['CustomerID']);
		$AuthorizeID = intval($_REQUEST['AuthorizeID']);
		if( is_numeric($CustomerID)){
			$m = D('Admin/Authorize');
			$where['AuthorizeID'] = $AuthorizeID;
			if($m->where($where)->setField('CustomerID', $CustomerID)){
				$this->ajaxReturn(null, '修改成功!' , 1);
			}else{
				$this->ajaxReturn(null, '修改失败!' , 0);
			}
		}
	}
	
	function authorize(){
		$id = $_REQUEST['AuthorizeID'];
		$NowPage = intval($_REQUEST["NowPage"]);
		$Host = !empty($_REQUEST['Host']) ? $_REQUEST['Host'] : '';
        $Host = YdInput::checkLetterNumber($Host);

		$Authorize = $_REQUEST['Authorize'];
		$OperatorID = (int)session('MemberID');
		$MemberGroupID = (int)session('MemberGroupID');
		$AgentID = -1;
		if( $MemberGroupID >= 110 && $MemberGroupID <= 115){
			$AgentID = $OperatorID;
		}
		$parameter = "?Host={$Host}&p={$NowPage}";
		if($Host!=''){
			$parameter .= "&Host={$Host}";
		}
		
		if( count($id) > 0 ){
			$m = D('Admin/Authorize');
			$m->authorize($id, $OperatorID, $AgentID, 1); //普通用户只能授权，不能取消授权
		}
		
		redirect(__URL__."/index".$parameter);
	}
}
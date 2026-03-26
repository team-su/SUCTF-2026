<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class AuthorizeAction extends AdminBaseAction {
	/**
	 * 信息列表显示首页
	 */
	function index(){
		header("Content-Type:text/html; charset=utf-8");
		$Host = !empty($_REQUEST['Host']) ? $_REQUEST['Host'] : '';
		$IsAuthorize = isset($_REQUEST['IsAuthorize']) ? (int)$_REQUEST['IsAuthorize'] : -1;
		$AgentID = isset($_REQUEST['AgentID']) ? (int)$_REQUEST['AgentID'] : -1;
		$OperatorID = isset($_REQUEST['OperatorID']) ? (int)$_REQUEST['OperatorID'] : -1;
        $SourceID = isset($_REQUEST['SourceID']) ? (int)$_REQUEST['SourceID'] : -1;
	
		import("ORG.Util.Page");
		$m = D('Admin/Authorize');
		$TotalPage = $m->getCount($Host, $IsAuthorize, $AgentID, $OperatorID,$SourceID); //总页数
		$PageSize = $this->AdminPageSize;
		$Page = new Page($TotalPage, $PageSize);
		
		if($IsAuthorize != -1 ){
			$Page->parameter .= "&IsAuthorize=$IsAuthorize";
		}
		
		if($Host!=''){
			$Page->parameter .= "&Host=$Host";
		}
		
		if($AgentID != -1 ){
			$Page->parameter .= "&AgentID=$AgentID";
		}
		
		if($OperatorID !=-1 ){
			$Page->parameter .= "&OperatorID=$OperatorID";
		}
        if($SourceID !=-1 ){
            $Page->parameter .= "&SourceID=$SourceID";
        }
		
		$Page->rollPage = $this->AdminRollPage;
		$ShowPage = $Page->show();
	
		$Data = $m->getAuthorizeInfo($Page->firstRow, $Page->listRows, $Host, $IsAuthorize, $AgentID, $OperatorID,$SourceID);
		
		$AgentData = $m->getAgentData();
		$this->assign('AgentData', $AgentData);
		$this->assign('AgentID', $AgentID);
        $this->assign('SourceID', $SourceID);
		
		$OperatorData = $m->getOperatorData();
		$this->assign('OperatorData', $OperatorData);
		$this->assign('OperatorID', $OperatorID);
		
		if(!empty($Data)){
			$mm = D('Admin/Member');
			$n = is_array($Data) ? count($Data) : 0;
			for($i=0; $i<$n;$i++){
				$CustomerID = (int)$Data[$i]['CustomerID'];
				if( $CustomerID > 0 ){
					$CustomerName = $mm->where("MemberID=$CustomerID")->getField('MemberRealName');
					$Data[$i]['CustomerName'] = $CustomerName;
				}
				
				$AgentID = (int)$Data[$i]['AgentID'];
				if( $AgentID > 0 ){
					$AgentName = $mm->where("MemberID=$AgentID")->getField('MemberRealName');
					$Data[$i]['AgentName'] = $AgentName;
				}
				
				$OperatorID = (int)$Data[$i]['OperatorID'];
				if( $OperatorID > 0 ){
					$OperatorName = $mm->where("MemberID=$OperatorID")->getField('MemberRealName');
					$Data[$i]['OperatorName'] = $OperatorName;
				}
			}
		}
		
		$TotalCount = $m->GetAuthorizeCount();
		$AuthorizeCount = $m->GetAuthorizeCount(1);
		$UnAuthorizeCount = $m->GetAuthorizeCount(0);
		if($TotalCount>0){
            $Percent = number_format(100*$AuthorizeCount/$TotalCount, 2);
        }else{
            $Percent = 0;
        }
		$Stat = $m->statNumByOS();
		$this->assign('Stat', $Stat);
		$this->assign('Total', $TotalCount);
		$this->assign('AuthorizeCount', $AuthorizeCount );
		$this->assign('UnAuthorizeCount', $UnAuthorizeCount );
		$this->assign('Percent', $Percent);
		//来源
        $source = $m->getSource(false);
        $this->assign('Source', $source);
	
		$this->assign('NowPage', $Page->getNowPage()); //分页条
		$this->assign('Page', $ShowPage); //分页条

		$this->assign('Host', $Host); //当前频道
		$this->assign('IsAuthorize', $IsAuthorize); //当前频道
		$this->assign('Data', $Data);
		$this->display();
	}
	
	function add(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Authorize');
		$CustomerData = $m->getCustomerData();
		$this->assign('CustomerData', $CustomerData);
		$this->assign('Action', __URL__.'/SaveAdd');
		$this->display();
	}
	
	function saveAdd(){
		header("Content-Type:text/html; charset=utf-8");
		$this->_checkPost( $_POST );
		$m = D('Admin/Authorize');
		if( $m->create() ){
			$m->OperatorID = (int)session('AdminMemberID');
			if($m->add()){
				$this->ajaxReturn(null, '添加成功!' , 1);
			}else{
				$this->ajaxReturn(null, '添加失败!' , 0);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}
	
	function modify(){
		header("Content-Type:text/html; charset=utf-8");
		//参数有效性检查===========================
		$AuthorizeID = $_GET['AuthorizeID'];
		if( !is_numeric($AuthorizeID)){
			alert("非法参数", __URL__.'/index');
		}
		//====================================
		$m = D('Admin/Authorize');
		$Data = $m->find( $AuthorizeID );
		$CustomerData = $m->getCustomerData();

		$this->assign('CustomerData', $CustomerData);
		$this->assign('Data', $Data);
		
		$this->assign('HiddenName', 'AuthorizeID');
		$this->assign('HiddenValue', $AuthorizeID);
		$this->assign('Action', __URL__.'/saveModify');
	
		$this->display();
	}
	
	function saveModify(){
		header("Content-Type:text/html; charset=utf-8");
		$this->_checkPost( $_POST );
		$m = D('Admin/Authorize');
		if( $m->create() ){
			$m->OperatorID = (int)session('MemberID');
			if($m->save() === false){
				$this->ajaxReturn(null, '修改失败!' , 0);
			}else{
				$this->ajaxReturn(null, '修改成功!' , 1);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}
	
	//检查提交参数
	private function _checkPost($p){
		if( empty($p['Host']) ){
			$this->ajaxReturn(null, '授权域名不能为空' , 0);
		}
		
		if( stripos($p['Host'], 'http://') !== false || stripos($p['Host'], 'https://') !== false){
			$this->ajaxReturn(null, '授权域名不能包含http://或https://' , 0);
		}
		
		if( stripos($p['Host'], '.') === false || strlen($p['Host']) <= 3 ){
			$this->ajaxReturn(null, '授权域名无效' , 0);
		}
	}
	
	//批量审核
	function authorize(){
		$id = (int)$_REQUEST['AuthorizeID'];
		$NowPage = (int)$_REQUEST["NowPage"];
		
		$Authorize = $_REQUEST['Authorize'];
		$Host = !empty($_REQUEST['Host']) ? $_REQUEST['Host'] : '';
		$IsAuthorize = isset($_REQUEST['IsAuthorize']) ? (int)$_REQUEST['IsAuthorize'] : -1;
        $SourceID = isset($_REQUEST['SourceID']) ? (int)$_REQUEST['SourceID'] : -1;
		$OperatorID = (int)session('AdminMemberID');  //管理员对于前端ID

		$parameter = "?IsAuthorize=$IsAuthorize&p=$NowPage";
		if($Host!=''){
			$parameter .= "&Host=$Host";
		}
        if($SourceID !=-1 ){
            $parameter .= "&SourceID=$SourceID";
        }
		
		if( $id>0 || count($id) > 0 ){
			$m = D('Admin/Authorize');
			$m->authorize( $id , $OperatorID, -1, $Authorize);
		}
		
		redirect(__URL__."/index".$parameter);
	}
	
	function del(){
		$id = $_REQUEST['AuthorizeID'];
		$NowPage = (int)$_REQUEST["NowPage"];
		
		$Host = !empty($_REQUEST['Host']) ? $_REQUEST['Host'] : '';
		$IsAuthorize = isset($_REQUEST['IsAuthorize']) ? (int)$_REQUEST['IsAuthorize'] : -1;
		
		$m = D('Admin/Authorize');
		$m->delete($id);
		$parameter = "?IsAuthorize=$IsAuthorize&p=$NowPage";
		if($Host!=''){
			$parameter .= "&Host=$Host";
		}
		redirect(__URL__."/index".$parameter);
	}
}
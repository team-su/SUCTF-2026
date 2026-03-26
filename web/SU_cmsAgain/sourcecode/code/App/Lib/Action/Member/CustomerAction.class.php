<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class CustomerAction extends MemberBaseAction {
	/**
	 * 信息列表显示首页
	 */
	function index(){
		header("Content-Type:text/html; charset=utf-8");
		$keywords = isset($_REQUEST['keywords']) ? YdInput::checkKeyword( $_REQUEST['keywords'] ) : '';
		$mid = (int)session('MemberID');
		
		$m = D('Admin/Member');
		import("ORG.Util.Page");
		$TotalCount = $m->getCustomerCount( $keywords, 1,  $mid);
		$PageSize = $this->AdminPageSize;
		$Page = new Page($TotalCount, $PageSize);
		$Page->rollPage = $this->AdminRollPage;
		//获取参数
		if( !empty( $keywords ) ){
			$Page->parameter = "&keywords=$keywords";
		}
		$data = $m->getCustomer($Page->firstRow, $Page->listRows, $keywords, 1, $mid);

		$ShowPage = $Page->show();
		$this->assign('TotalCount', $TotalCount);
		$this->assign('Keywords', $keywords);
		$this->assign('PageSize', $PageSize);
		$this->assign('NowPage', $Page->getNowPage()); //当前页码
		$this->assign('Page', $ShowPage); //分页条
		$this->assign('Data', $data);
		$this->display();
	}
	
	
	function modify(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Member');
		$MemberID = intval($_REQUEST["id"]);
		$mid = (int)session('MemberID');
		$Data = $m->findCustomer($MemberID, $mid);

		$CustomerGroup = $m->getCustomerGroup();
		
		$this->assign('CustomerGroup', $CustomerGroup);
		$this->assign('Data', $Data);
		$this->assign('Area', $this->getArea() );
		$this->assign('HiddenName', 'MemberID');
		$this->assign('HiddenValue', $MemberID);
		$this->assign('Action', __URL__.'/saveModify');
		$this->display();
	}
	
	function saveModify(){
		header("Content-Type:text/html; charset=utf-8");
		$this->_checkPost();
		unset( $_POST['InviterID'], $_POST['IsEnable']);
		$m = D('Admin/Member');
		$where = "MemberID=".intval($_POST['MemberID']);
		$inviterID = $m->where($where)->getField('InviterID');
		//检查当前MemberID是否自己的客户
		if( $inviterID == session('MemberID')){
			if( $m->create() ){
				if($m->save() === false){
					$this->ajaxReturn(null, '修改失败!' , 0);
				}else{
					$this->ajaxReturn(null, '修改成功!' , 1);
				}
			}else{
				$this->ajaxReturn(null, $m->getError() , 0);
			}
		}else{
			$this->ajaxReturn(null, '数据异常' , 0);
		}
	}
	
	//检查提交参数
	private function _checkPost(){
        $_POST = YdInput::checkTextbox($_POST);
		if( empty($_POST['MemberName']) ){
			$this->ajaxReturn(null, '昵称不能为空' , 0);
		}
		
		if( $_POST['MemberPassword'] != ''){
			$_POST['MemberPassword'] = md5($_POST['MemberPassword']);
		}else{
			unset( $_POST['MemberPassword'] );
		}
	}
	
	private function getArea(){
		$m = D('Admin/Area');
		$data = $m->getArea(-1, 1); //获取所有的区域数据
		$n =  0;
		$all = array();
		foreach ($data as $p){
			if( $p['Parent'] == 0 ){
				$all[$n]['AreaID'] = $p['AreaID'];
				$all[$n]['AreaName'] = $p['AreaName'];
				foreach ($data as $c){
					if( $c['Parent'] == $p['AreaID']){
						$all[$n]['Childs'] .= "{$c['AreaID']},{$c['AreaName']}@";
					}
				}
				$all[$n]['Childs'] = rtrim($all[$n]['Childs'], '@');
				$n++;
			}
		}
		return $all;
	}
	
	function add(){
		header("Content-Type:text/html; charset=utf-8");
		//默认数据
		$Data = array('MemberGender'=>0, 'MemberGroupID'=>101, 'RegisterTime'=>date('Y-m-d H:i:s'), 
				'IsOEM'=>0);
		$m = D('Admin/Member');
		$CustomerGroup = $m->getCustomerGroup();
		$this->assign('CustomerGroup', $CustomerGroup);
		$this->assign('Area', $this->getArea() );
		$this->assign('Data', $Data);
		$this->assign('Action', __URL__.'/SaveAdd');
		$this->display();
	}
	
	function saveAdd(){
		header("Content-Type:text/html; charset=utf-8");
		$this->_checkPost();
		$m = D('Admin/Member');
		if( $m->create() ){
			$m->IsCheck = 1;
			$m->InviterID = session('MemberID');
			if($m->add()){
				$this->ajaxReturn(null, '添加成功!' , 1);
			}else{
				$this->ajaxReturn(null, '添加失败!' , 0);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}
}
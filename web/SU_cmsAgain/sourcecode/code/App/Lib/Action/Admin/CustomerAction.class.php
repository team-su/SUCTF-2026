<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class CustomerAction extends AdminBaseAction {
	/**
	 * 信息列表显示首页
	 */
	function index(){
		header("Content-Type:text/html; charset=utf-8");
		$keywords = isset($_REQUEST['keywords']) ? YdInput::checkKeyword( $_REQUEST['keywords'] ) : '';
		$p['Province'] = isset($_REQUEST['Province']) ? $_REQUEST['Province'] : -1;
		
		//获取省份=====================
		$m1 = D('Admin/Area');
		$Province = $m1->getArea(0, 1);
		$this->assign('Province', $Province);
		$Province1 = $m1->getAssoArea();
		//==========================
		
		$m2 = D('Admin/Authorize');
		$Operator = $m2->getAssoOperatorData();
		
		$m = D('Admin/Member');
		import("ORG.Util.Page");
		$TotalCount = $m->getCustomerCount( $keywords, 1, -1, $p);
		$PageSize = $this->AdminPageSize;
		$Page = new Page($TotalCount, $PageSize);
		$Page->rollPage = $this->AdminRollPage;
		//获取参数
		if( !empty( $keywords ) ){
			$Page->parameter = "&keywords=$keywords";
		}
		$data = $m->getCustomer($Page->firstRow, $Page->listRows, $keywords, 1, -1, $p);
		$n = is_array($data) ? count($data) : 0;
		for($i = 0; $i < $n; $i++){
			$mid = $data[$i]['InviterID'];
			$data[$i]['InviterName'] = isset( $Operator[$mid] ) ? $Operator[$mid] : '';
			$k = $data[$i]['Province'];
			$data[$i]['ProvinceName'] = isset( $Province1[$k] ) ? $Province1[$k] : '';
			$k = $data[$i]['City'];
			$data[$i]['CityName'] = isset( $Province1[$k] ) ? $Province1[$k] : '';
		}
		$ShowPage = $Page->show();

		$this->assign('CurrentProvince', $p['Province']);
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
		$MemberID = $_REQUEST["id"];
		$Data = $m->findCustomer($MemberID);

		$CustomerGroup = $m->getCustomerGroup();
		$this->assign('CustomerGroup', $CustomerGroup);
		
		$m = D('Admin/Authorize');
		$OperatorData = $m->getOperatorData();
		$this->assign('OperatorData', $OperatorData);
		
		$this->assign('Data', $Data);
		$this->assign('Area', $this->getArea() );
		$this->assign('HiddenName', 'MemberID');
		$this->assign('HiddenValue', $MemberID);
		$this->assign('Action', __URL__.'/saveModify');
		$this->display();
	}
	
	function saveModify(){
		header("Content-Type:text/html; charset=utf-8");
		$this->_checkPost( $_POST );
		$m = D('Admin/Member');
		if( $m->create() ){
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
		if( empty($p['MemberName']) ){
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
				'IsOEM'=>0, 'InviterID'=>0);
		$m = D('Admin/Member');
		$CustomerGroup = $m->getCustomerGroup();
		$this->assign('CustomerGroup', $CustomerGroup);
		
		$m = D('Admin/Authorize');
		$OperatorData = $m->getOperatorData();
		$this->assign('OperatorData', $OperatorData);
		
		$this->assign('Area', $this->getArea() );
		$this->assign('Data', $Data);
		$this->assign('Action', __URL__.'/SaveAdd');
		$this->display();
	}
	
	function saveAdd(){
		header("Content-Type:text/html; charset=utf-8");
		$this->_checkPost( $_POST );
		$m = D('Admin/Member');
		if( $m->create() ){
			$m->IsCheck = 1;
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
<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class CashAction extends AdminBaseAction{
	function index(){
		$p = array(
			'HasPage' => true,
			'DataCallBack'=>'DataCallBack'
		);
		if( !empty($_REQUEST['SearchWord']) ){
			$p['Parameter']['SearchWord'] = $_REQUEST['SearchWord'];
		}
        $p['Parameter']['CashType'] = !empty($_REQUEST['CashType']) ? (int)$_REQUEST['CashType'] : -1;
		$m = D('Admin/Cash');
		$TotalQuantity = $m->getQuantity(1);
		$AvailableQuantity = $m->getAvailableQuantity();
		$this->assign('TotalQuantity', $TotalQuantity);
		$this->assign('AvailableQuantity', $AvailableQuantity);
		$this->opIndex($p);
	}
	
	function DataCallBack(&$data){
		$total = 0;
		if(!empty($data)){
			foreach ($data as $v){
				$total += $v['CashQuantity'];
			}
		}
		$this->assign('Total', $total);
	}
	
	function modify(){
		$p = array();
		$this->opModify(false, $p);
	}
	
	function saveModify(){
		//过滤禁止修改的字段值
		$list = array('MemberID', 'CashType', 'CashQuantity');
		foreach ($_POST as $k=>$v){
			if( !in_array($k, $list) ){
				unset($k);
			}
		}
		$this->opSaveModify();
	}
	
	function del(){
	    $p = array();
		if( !empty($_REQUEST['SearchWord']) ){
			$p['Parameter']['SearchWord'] = $_REQUEST['SearchWord'];
		}
		if( is_numeric($_REQUEST['CashType']) && $_REQUEST['CashType'] != -1 ){
			$p['Parameter']['CashType'] = $_REQUEST['CashType'];
		}
	
		if( is_numeric($_REQUEST['p']) ){
			$p['Parameter']['p'] = $_REQUEST['p'];
		}
		$this->opDel( $p );
	}
	
	//给会员转电子币
	function transfer(){
		if( empty($_REQUEST['Member'])){
			$this->ajaxReturn(null, '会员不能为空' , 0);
		}
		$m = D('Admin/Member');
		$MemberID = $m->getMemberIDByKeywords($_REQUEST['Member']);
		if(empty($MemberID)){
			$this->ajaxReturn(null, $_REQUEST['Member'].' 不存在' , 0);
		}
		
		if( !is_numeric($_REQUEST['CashQuantity'])){
			$this->ajaxReturn(null, '转账金额必须为数字' , 0);
		}
		if( 0 == $_REQUEST['CashQuantity'] ){
			$this->ajaxReturn(null, '转账金额不能为0' , 0);
		}
		$m1 = D('Admin/Cash');
		$data['CashQuantity'] = $_REQUEST['CashQuantity'];
		$data['MemberID'] = $MemberID;
		$data['CashRemark'] = $_REQUEST['CashRemark'];
		$data['CashType'] = 3;    //表示转账
		$data['CashStatus'] = 1; //表示转账状态为成功
		$data['CashTime'] = date('Y-m-d H:i:s');
		$CashID = $m1->add($data);
		if($CashID){
			WriteLog("给会员{$MemberID}转电子币".$_REQUEST['CashQuantity']);
			$this->ajaxReturn(null, '转账成功!' , 1);
		}else{
			$this->ajaxReturn(null, '转账失败!' , 0);
		}
	}
	
	/**
	 * 设置
	 */
	function config(){
		$m = D('Admin/Config');
		$data = $m->getConfig('other'); //配置数据不从缓存中提取
		$this->assign('WithdrawThreshold', $data['WithdrawThreshold'] );
		$this->assign('MinWithdraw', $data['MinWithdraw'] );

		$this->assign('Action', __URL__.'/saveConfig' );
		$this->display();
	}
	
	/**
	 * 保存设置
	 */
	function saveConfig(){
        $fieldMap = array('WithdrawThreshold'=>1, 'MinWithdraw'=>1);
        $data = GetConfigDataToSave($fieldMap);
        $m = D("Admin/Config");
        if( $m->saveConfig($data) ){
            $description = var_export($data, true);
            WriteLog($description);
            $this->ajaxReturn(null, '保存成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败!' , 0);
        }
	}
}
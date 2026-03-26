<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class PluginAction extends MemberBaseAction {
	/**
	 * 我的下线/推广
	 */
	function downline(){
		header("Content-Type:text/html; charset=utf-8");
		$MemberID = (int)session('MemberID');
		$m = D('Admin/Member');
		$result = $m->getDownline($MemberID);
		$Level = isset($_REQUEST['Level']) ? intval($_REQUEST['Level']) : -1;
		$list = array(1=>'一级下线', 2=>'二级下线', 3=>'三级下线');
		$AllLevel = ($Level == -1) ? array(1,2,3) : array($Level);
		$data = array();
		$m = D('Admin/DistributorLevel');
		foreach ($result as $k=>$v){
			if(in_array($k, $AllLevel)){
				if(!empty($v)){
					$n = count($v);
					$DownLineLevel = $list[$k];
					for($i=0; $i<$n; $i++){
						$v[$i]['DownLineLevelName'] = $DownLineLevel;
						$v[$i]['DownLineLevelID'] = $k;
						$where = 'DistributorLevelID = '.intval($v[$i]['DistributorLevelID']);
						$DistributorLevelName = $m->where($where)->getField('DistributorLevelName');
						$v[$i]['DistributorLevelName'] = $DistributorLevelName;
						$data[] = $v[$i];
					}
				}
			}
		}
		$DownlineCount = count($result[1]) + count($result[2]) + count($result[3]);
		
		$this->assign('Level', $Level);
		$this->assign('DownlineCount', $DownlineCount);
		$this->assign('Downline1Count', count($result[1]) );
		$this->assign('Downline2Count', count($result[2]) );
		$this->assign('Downline3Count', count($result[3]) );
		$this->assign('Data',$data);
		$this->display();
	}
	
	/**
	 * 我的收益
	 */
	function income(){
		$MemberID = (int)session('MemberID');
		$CashType = 5; //表示分佣金额
		$p = array(
			'ModuleName'=>'Cash',
			'HasPage' => true,
			'PageSize'=>20,
			'Parameter' => array('MemberID' =>$MemberID),
			'DataCallBack'=>'DataCallBack'
		);
		$p['Parameter']['CashType'] = $CashType; 

		$m = D('Admin/Cash');
		$TotalQuantity = $m->getQuantity($CashType, $MemberID);
		$this->assign('TotalQuantity', $TotalQuantity);
		$this->opIndex($p);
	}
}
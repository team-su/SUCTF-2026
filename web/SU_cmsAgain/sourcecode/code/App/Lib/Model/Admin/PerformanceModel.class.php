<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */
class PerformanceModel extends Model{
	function getPerformance($offset = -1, $length = -1, $p=array()){
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}
		$where = $this->getWhere( $p );
		$result = $this->where($where)->order('AddTime desc')->select();
		return $result;
	}
	
	function findPerformance($id, $options = array() ){
		if( !is_numeric($id) ) return false;
		$where['PerformanceID'] = intval($id);
		if(  isset($p['OperatorID']) && $options['OperatorID'] != -1 ){
			$where['OperatorID'] = $options['OperatorID'];
		}
		$result = $this->where($where)->find();
		return $result;
	}
	
	//统计日志总数
	function getPerformanceCount($p=array() ){
		$where = $this->getWhere( $p );
		$n = $this->where($where)->count();
		return $n;
	}
	
	function getTotalFee( $p=array() ){
		$where = $this->getWhere( $p );
		$n = $this->where($where)->sum('ProjectFee');
		return $n;
	}
	
	//除了模板等级的费用
	function getTotalFeeExceptTemplate( $p=array() ){
		$where = $this->getWhere( $p );
		$where['ProjectType'] = array('neq', 7);
		$n = $this->where($where)->sum('ProjectFee');
		return $n;
	}
	
	//获取条件数组
	function getWhere( $p=array() ){
		$where = array();
		if( isset($p['CustomerID']) && $p['CustomerID'] != -1 ){
			$where['CustomerID'] = intval($p['CustomerID']);
		}
		
		if(  isset($p['OperatorID']) && $p['OperatorID'] != -1 ){
			$where['OperatorID'] = intval($p['OperatorID']);
		}
		
		if(  isset($p['ProjectType']) && $p['ProjectType'] != -1 ){
			$where['ProjectType'] = intval($p['ProjectType']);
		}
		
		if(  isset($p['PayTypeID']) && $p['PayTypeID'] != -1 ){
			$where['PayType'] = intval($p['PayTypeID']);
		}
		
		if(  isset($p['NeedInvoice']) && $p['NeedInvoice'] != -1 ){
			$where['NeedInvoice'] = intval($p['NeedInvoice']);
		}
		
		//month(AddTime) =month(curdate()) and year(AddTime) = year(curdate())
		if(  isset($p['Year']) && $p['Year'] != -1 ){
            $p['Year'] = intval($p['Year']);
			$where['_string'] = "year(AddTime) = {$p['Year']}";
		}
		
		if(  isset($p['Month']) && $p['Month'] != -1 && $p['Month'] >= 1 && $p['Month'] <=12){
            $p['Month'] = YdInput::checkLetterNumber($p['Month']);
			if( isset($where['_string']) ){
				$where['_string'] .= " and month(AddTime) = {$p['Month']}";
			}else{
				$where['_string'] = "month(AddTime) = {$p['Month']}";
			}
		}
		
		return $where;
	}
	
	function getProjectType(){
		$data = array(
				1=>array('ProjectTypeID'=>1, 'ProjectTypeName'=>'模板授权'),
				2=>array('ProjectTypeID'=>2, 'ProjectTypeName'=>'定制开发'),
				3=>array('ProjectTypeID'=>3, 'ProjectTypeName'=>'空间域名'),
				4=>array('ProjectTypeID'=>4, 'ProjectTypeName'=>'网站备案'),
				5=>array('ProjectTypeID'=>5, 'ProjectTypeName'=>'维护服务'),
				6=>array('ProjectTypeID'=>6, 'ProjectTypeName'=>'招商合作'),
				7=>array('ProjectTypeID'=>7, 'ProjectTypeName'=>'模板登记'),
				15=>array('ProjectTypeID'=>15, 'ProjectTypeName'=>'其它'),
		);
		return $data;
	}
	
	//付款方式
	function getPayType(){
		$data = array(
				1=>array('PayTypeID'=>1, 'PayTypeName'=>'支付宝'),
				2=>array('PayTypeID'=>2, 'PayTypeName'=>'淘宝'),
				3=>array('PayTypeID'=>3, 'PayTypeName'=>'财付通'),
				4=>array('PayTypeID'=>4, 'PayTypeName'=>'微信支付'),
				5=>array('PayTypeID'=>5, 'PayTypeName'=>'微店'),
				6=>array('PayTypeID'=>6, 'PayTypeName'=>'一团网'),
				
				10=>array('PayTypeID'=>10, 'PayTypeName'=>'对公转账'),
				11=>array('PayTypeID'=>11, 'PayTypeName'=>'建设银行'),
				12=>array('PayTypeID'=>12, 'PayTypeName'=>'农业银行'),
				13=>array('PayTypeID'=>13, 'PayTypeName'=>'工商银行'),
				14=>array('PayTypeID'=>14, 'PayTypeName'=>'招商银行'),
				15=>array('PayTypeID'=>15, 'PayTypeName'=>'长沙银行'),
				16=>array('PayTypeID'=>16, 'PayTypeName'=>'民生银行'),
				17=>array('PayTypeID'=>17, 'PayTypeName'=>'中国银行'),
				
				20=>array('PayTypeID'=>8, 'PayTypeName'=>'现金支付'),
				21=>array('PayTypeID'=>9, 'PayTypeName'=>'在线支付'),
		);
		return $data;
	}
	
	//获取代理模板数
	function getTemplateCount($agentID){
		//统计已使用模板时注意：2个模板算一套
		$where['ProjectType'] = 7;
		$where['CustomerID'] = intval($agentID);
		$data['UsedCount'] = $this->where($where)->count();
		
		$where['ProjectType'] = 6; //招商登记
		$data['TotalCount'] = $this->where($where)->sum('TemplateCount');
		if( empty($data['TotalCount']) ) $data['TotalCount'] = 0;
		$data['LeftCount'] = $data['TotalCount'] - $data['UsedCount'];
		return $data;
	}
	
	/**
	 * 充值总金额
	 * @param int $agentID
	 * @return number
	 */
	function getChongZhiFee($agentID){
		$n = $this->where("ProjectType=6 and CustomerID=".intval($agentID))->sum('ProjectFee');
		return $n;
	}
	
	function getUsedFee($agentID){
		$n = $this->where("ProjectType!=6 and CustomerID=".intval($agentID))->sum('ProjectFee');
		return $n;
	}
	
	//按业务员统计业绩
	function statPerformanceByMember($year){
        $year = intval($year);
		$where = "year(a.AddTime)={$year} and ProjectType !=7";
		$this->field('a.OperatorID, b.MemberRealName, sum(ProjectFee) as Total');
		$this->table($this->tablePrefix.'performance a');
		$this->join($this->tablePrefix.'member b On a.OperatorID = b.MemberID and b.MemberGroupID=100');
		$data = $this->where( $where )->group('a.OperatorID')->order('Total desc')->select();
		return $data;
	}
	
	//按月统计业绩（type 1：返回一维关联数值，2：返回2维数组）
	function statPerformanceByMonth($year, $type=1){
        $year = intval($year);
		$where = "year(AddTime)={$year} and ProjectType !=7";
		$this->field('OperatorID, MONTH(AddTime) as Month, sum(ProjectFee) as Total');
		$data = $this->where( $where )->group('OperatorID,MONTH(AddTime)')->select();
		$result = array();
		if( !empty( $data) ){
			if($type == 1){
				foreach ($data as $v){
					$key = $v['OperatorID'].$v['Month'];
					$result[ $key ] = $v['Total'];
				}
				unset($data);
				return $result;
			}elseif($type==2){
				return $data;
			}
		}else{
			return false;
		}
	}
	
	//按业务员统计工资
	function statSalaryByMember($year){
        $year = intval($year);
		$where = "year(a.AddTime)={$year} and ProjectType !=7";
		$this->field('a.OperatorID, b.MemberRealName, (sum(ProjectFee)*b.Percentage) as Total');
		$this->table($this->tablePrefix.'performance a');
		$this->join($this->tablePrefix.'member b On a.OperatorID = b.MemberID and b.MemberGroupID=100');
		$data = $this->where( $where )->group('a.OperatorID')->order('Total desc')->select();
		return $data;
	}
	
	//按月统计工资（type 1：返回一维关联数值，2：返回2维数组）
	function statSalaryByMonth($year, $type=1){
        $year = intval($year);
		$where = "year(AddTime)={$year} and ProjectType !=7";
		$this->field('a.OperatorID, MONTH(a.AddTime) as Month, (sum(a.ProjectFee)*b.Percentage) as Total');
		$this->table($this->tablePrefix.'performance a');
		$this->join($this->tablePrefix.'member b On a.OperatorID = b.MemberID and b.MemberGroupID=100');
		$data = $this->where( $where )->group('a.OperatorID,MONTH(a.AddTime)')->select();
		$result = array();
		if( !empty( $data) ){
			if($type == 1){
				foreach ($data as $v){
					$key = $v['OperatorID'].$v['Month'];
					$result[ $key ] = $v['Total'];
				}
				unset($data);
				return $result;
			}elseif($type==2){
				return $data;
			}
		}else{
			return false;
		}
	}
	
	//按项目类型统计业绩
	function statPerformanceByProjectType($year){
        $year = intval($year);
		$where = "year(AddTime)={$year} and ProjectType !=7";
		$this->field('ProjectType, sum(ProjectFee) as Total');
		$data = $this->where( $where )->group('ProjectType')->order('Total desc')->select();
		return $data;
	}
	
	//按月统计项目业绩
	function statProjectPerformanceByMonth($year, $type=1){
        $year = intval($year);
		$where = "year(AddTime)={$year} and ProjectType !=7";
		$this->field('ProjectType, MONTH(AddTime) as Month, sum(ProjectFee) as Total');
		$data = $this->where( $where )->group('ProjectType,MONTH(AddTime)')->select();
		$result = array();
		if( !empty( $data) ){
			if($type == 1){
				foreach ($data as $v){
					$key = $v['ProjectType'].$v['Month'];
					$result[ $key ] = $v['Total'];
				}
				unset($data);
				return $result;
			}elseif($type==2){
				return $data;
			}
		}else{
			return false;
		}
	}
	
	//统计模板数量
	function statTemplate(){
		//1:购买模板，7：模板登记
		$where = "ProjectType in(1,7) and PcNumber!=''";
		$this->field('PcNumber, count(PcNumber) as Total');
		$data = $this->where( $where )->group('PcNumber')->order('Total desc')->select();
		return $data;
	}
	
	function statTemplateByYear($type=1){
		//1:购买模板，7：模板登记
		$where = "ProjectType in(1,7) and PcNumber!=''";
		$this->field('year(AddTime) as Year, PcNumber, count(PcNumber) as Total');
		$data = $this->where( $where )->group('year(AddTime),PcNumber')->order('Total desc')->select();
		$result = array();
		if( !empty( $data) ){
			if($type == 1){
				foreach ($data as $v){
					$key = $v['PcNumber'].$v['Year'];
					$result[ $key ] = $v['Total'];
				}
				unset($data);
				return $result;
			}elseif($type==2){
				return $data;
			}
		}else{
			return false;
		}
		return $data;
	}
	
	//按付款方式统计业绩
	function statPerformanceByPayType($year){
        $year = intval($year);
		$where = "year(AddTime)={$year} and ProjectType!=7";
		$this->field('PayType, sum(ProjectFee) as Total');
		$data = $this->where( $where )->group('PayType')->order('Total desc')->select();
		return $data;
	}
	
	//按月统计付款方式业绩（type 1：返回一维关联数值，2：返回2维数组）
	function statPayTypePerformanceByMonth($year, $type=1){
        $year = intval($year);
		$where = "year(AddTime)={$year} and ProjectType!=7";
		$this->field('PayType, MONTH(AddTime) as Month, sum(ProjectFee) as Total');
		$data = $this->where( $where )->group('PayType,MONTH(AddTime)')->select();
		$result = array();
		if( !empty( $data) ){
			if($type == 1){
				foreach ($data as $v){
					$key = $v['PayType'].$v['Month'];
					$result[ $key ] = $v['Total'];
				}
				unset($data);
				return $result;
			}elseif($type==2){
				return $data;
			}
		}else{
			return false;
		}
	}
	
	//按会员分组统计客户数
	function statCustomerByMemberGroup($year){
        $year = intval($year);
		$where = "year(b.RegisterTime)={$year}";
		$this->field('a.MemberGroupName, a.MemberGroupID,count(*) as Total');
		$this->table($this->tablePrefix.'member_group a');
		$this->join($this->tablePrefix.'member b On a.MemberGroupID = b.MemberGroupID');
		$data = $this->where( $where )->group('a.MemberGroupID')->order('Total desc')->select();
		return $data;
	}
	
	function statCustomerByMemberGroupMonth($year, $type=1){
        $year = intval($year);
        $type = intval($type);
		$where = "year(RegisterTime)={$year}";
		$this->table($this->tablePrefix.'member');
		$this->field('MemberGroupID, MONTH(RegisterTime) as Month, count(*) as Total');
		$data = $this->where( $where )->group('MemberGroupID,MONTH(RegisterTime)')->select();
		$result = array();
		if( !empty( $data) ){
			if($type == 1){
				foreach ($data as $v){
					$key = $v['MemberGroupID'].$v['Month'];
					$result[ $key ] = $v['Total'];
				}
				unset($data);
				return $result;
			}elseif($type==2){
				return $data;
			}
		}else{
			return false;
		}
	}
	
	//按省份统计代理数
	function statAgentByProvince($year){
        $year = intval($year);
		$where = "year(a.RegisterTime)={$year} and a.MemberGroupID>=110 and a.MemberGroupID<=119";
		$this->field('b.AreaName, a.Province,count(*) as Total');
		$this->table($this->tablePrefix.'member a');
		$this->join($this->tablePrefix.'area b On a.Province = b.AreaID');
		$data = $this->where( $where )->group('a.Province')->order('Total desc')->select();
		return $data;
	}
	
	function statAgentByProvinceMonth($year, $type=1){
        $year = intval($year);
		$where = "year(RegisterTime)={$year} and MemberGroupID>=110 and MemberGroupID<=119";
		$this->table($this->tablePrefix.'member');
		$this->field('Province, MONTH(RegisterTime) as Month, count(*) as Total');
		$data = $this->where( $where )->group('Province,MONTH(RegisterTime)')->select();
		$result = array();
		if( !empty( $data) ){
			if($type == 1){
				foreach ($data as $v){
					$key = $v['Province'].$v['Month'];
					$result[ $key ] = $v['Total'];
				}
				unset($data);
				return $result;
			}elseif($type==2){
				return $data;
			}
		}else{
			return false;
		}
	}
	
	//按省份统计客户数
	function statCustomerByProvince($year){
        $year = intval($year);
		$where = "year(a.RegisterTime)={$year} and a.MemberGroupID>=101 and MemberGroupID<=119";
		$this->field('b.AreaName, a.Province,count(*) as Total');
		$this->table($this->tablePrefix.'member a');
		$this->join($this->tablePrefix.'area b On a.Province = b.AreaID');
		$data = $this->where( $where )->group('a.Province')->order('Total desc')->select();
		return $data;
	}
	
	function statCustomerByProvinceMonth($year, $type=1){
        $year = intval($year);
		$where = "year(RegisterTime)={$year} and MemberGroupID>=101 and MemberGroupID<=119";
		$this->table($this->tablePrefix.'member');
		$this->field('Province, MONTH(RegisterTime) as Month, count(*) as Total');
		$data = $this->where( $where )->group('Province,MONTH(RegisterTime)')->select();
		$result = array();
		if( !empty( $data) ){
			if($type == 1){
				foreach ($data as $v){
					$key = $v['Province'].$v['Month'];
					$result[ $key ] = $v['Total'];
				}
				unset($data);
				return $result;
			}elseif($type==2){
				return $data;
			}
		}else{
			return false;
		}
	}
	
	/**
	 * 判断PC模板是否重复
	 * @param string $number
	 * @param string $memberID
	 */
	function pcTemplateExist($number, $memberID){
        $number = YdInput::checkLetterNumber($number);
		$where['CustomerID'] = intval($memberID);
		$where['PcNumber'] = $number;
		$n = $this->where($where)->count();
		if($n>0){
			return true;
		}else{
			return false;
		}
	}
	
	function wapTemplateExist($number, $memberID){
        $number = YdInput::checkLetterNumber($number);
		$where['CustomerID'] = intval($memberID);
		$where['WapNumber'] = $number;
		$n = $this->where($where)->count();
		if($n>0){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * 获取客户的代理预存费用
	 * @param int $memberID
	 */
	function getAgentFee($memberID){
		$where['CustomerID'] = intval($memberID);
		$where['ProjectType'] = 6; //招商合作
		$n = $this->where($where)->sum('ProjectFee');
		if(empty($n)) $n = 0;
		return $n;
	}
	
	/**
	 * 获取客户消费模板金额
	 * @param int $memberID
	 */
	function getTemplateFee($memberID){
		$where['CustomerID'] = intval($memberID);
		$where['ProjectType'] = 7; //招商合作
		$n = $this->where($where)->sum('ProjectFee');
		if(empty($n)) $n = 0;
		return $n;
	}
}

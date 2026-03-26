<?php
class CashModel extends Model{
	function getCash($offset = -1, $length = -1, $p = array()){
		$this->field('b.MemberName,b.MemberEmail,b.MemberMobile,c.PayName, a.*');
		$this->table($this->tablePrefix.'cash a');
		$this->join("Inner Join ".$this->tablePrefix.'member b On a.MemberID = b.MemberID');
		$this->join("Left Join ".$this->tablePrefix.'pay c On a.PayID = c.PayID');
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}

        $where = array();
		if( !empty($p['SearchWord']) ){
			$m = D('Admin/Member');
			$MemberID = $m->getMemberIDByKeywords($_REQUEST['SearchWord']);
			if(empty($MemberID)) return false;
			$where['a.MemberID'] = $MemberID;
		}
		
		if( isset($p['MemberID']) && $p['MemberID'] != -1 ){
			$where['a.MemberID'] = intval($p['MemberID']);
		}
		
		if( isset($p['CashType']) && $p['CashType'] != -1 ){
			$where['a.CashType'] = intval($p['CashType']);
		}
		$result = $this->where($where)->order('a.CashID desc')->select();
		if(!empty($result)){
			$n = is_array($result) ? count($result) : 0;
			$list = $this->getCashType();
			for($i=0; $i<$n; $i++){
				$type = $result[$i]['CashType'];
				$result[$i]['CashTypeName'] = $list[$type]['CashTypeName'] ? $list[$type]['CashTypeName'] : '';
			}
		}
		return $result;
	}
	
	function getCashCount($p = array()){
		$this->table($this->tablePrefix.'cash a');
		$this->join("Inner Join ".$this->tablePrefix.'member b On a.MemberID = b.MemberID');
        $where = array();
		if( !empty($p['SearchWord']) ){
			$m = D('Admin/Member');
			$MemberID = $m->getMemberIDByKeywords($_REQUEST['SearchWord']);
			if(empty($MemberID)) return false;
			$where['a.MemberID'] = $MemberID;
		}
		
		if( isset($p['MemberID']) && $p['MemberID'] != -1 ){
			$where['a.MemberID'] = intval($p['MemberID']);
		}
		
		if( isset($p['CashType']) && $p['CashType'] != -1 ){
			$where['a.CashType'] = intval($p['CashType']);
		}
		$n = $this->where($where)->count();
		return $n;
	}
	
	function getCashType(){
		$data[1] = array('CashTypeID'=>1, 'CashTypeName'=>'充值');
		$data[2] = array('CashTypeID'=>2, 'CashTypeName'=>'余额支付');
		$data[3] = array('CashTypeID'=>3, 'CashTypeName'=>'转账');
		$data[4] = array('CashTypeID'=>4, 'CashTypeName'=>'提现');
		$data[5] = array('CashTypeID'=>5, 'CashTypeName'=>'分销佣金');
		return $data;
	}
	
	function findCash($id, $p = array()){
		$this->field('b.MemberName,b.MemberEmail,b.MemberMobile, a.*');
		$this->table($this->tablePrefix.'cash a');
		$this->join("Inner Join ".$this->tablePrefix.'member b On a.MemberID = b.MemberID');
	
		$where['a.CashID'] = intval($id);
		if( isset($p['MemberID']) && $p['MemberID'] != -1 ){
			$where['a.MemberID'] = intval($p['MemberID']);
		}
		$result = $this->where($where)->find();
		
		if(!empty($result)){
			$list = $this->getCashType();
			$type = $result['CashType'];
			$result['CashTypeName'] = $list[$type]['CashTypeName'] ? $list[$type]['CashTypeName'] : '';
		}
		
		return $result;
	}
	
	/**
	 * 获取总充值金额（$CashType=1）、未使用金额（$CashType=-1）
	 * @param int $CashType
	 * @param int $MemberID
	 * @return int
	 */
	function getQuantity($CashType, $MemberID=-1){
		if( $CashType != -1){
			$where['CashType'] = intval($CashType);
		}
		if( $MemberID != -1){
			$where['MemberID'] = intval($MemberID);
		}
		$where['CashStatus'] = 1;
		$n = $this->where($where)->sum('CashQuantity');
		if(empty($n)) $n=0;
		if( $CashType == 2 || $CashType == 3 || $CashType == 4){ //余额支付和提现必须去绝对值
			$n = abs($n);
		}
		$n = round($n, 2);
		return $n;
	}
	
	/**
	 * 获取会员可用金额，可用金额必须减去提现
	 * @param unknown_type $MemberID
	 */
	function getAvailableQuantity($MemberID=-1){
		//提现不管是什么状态，都必须计算
		$where = "(CashStatus = 1 or CashType = 4)";
		if( $MemberID != -1){
			$where .= " and MemberID=".intval($MemberID);
		}
		$n = $this->where($where)->sum('CashQuantity');
		if(empty($n)) $n=0;
		$n = round($n, 2);
		return $n;
	}
	
	function delCash( $id = array(),  $p = array()){
		if( is_array($id) ){
			$n = count($id);
			for ($i=0; $i < $n; $i++){
				if( !is_numeric( $id[$i] ) ){
					unset( $id[$i] );
				}
			}
			if( count($id) <= 0 )  return false;
			$where = "CashID in(".implode(',', $id).')';
		}else{
			if( !is_numeric($id) ) return false;
			$where = "CashID=$id";
		}
		if( isset($p['MemberID']) && $p['MemberID'] != -1 && is_numeric($p['MemberID'])){
			$where .= " and MemberID=".$p['MemberID'];
		}
		$where .= " and CashStatus=2";  //只能删除未确认，未支付状态的资金
		$result = $this->where($where)->delete();
		return $result;
	}
	
	/**
	 * 获取支付参数
	 * @param string $OrderNumber
	 * @return boolean|array
	 */
	function getPayParams($OrderNumber){
        $OrderNumber = YdInput::checkLetterNumber($OrderNumber);
		//单号格式：'ZXCZ'.date('YmdHis').'_'.$CashID;
		$temp = explode('_', $OrderNumber);  
		$CashID = intval($temp[1]);
		$mc = D('Admin/Cash');
		$cash = $mc->find( $CashID );
		if(empty($cash)) return false;
		//如果已经支付，就直接返回成功
		if( $cash['CashStatus'] == 1 ) return true;
		$mp = D('Admin/Pay');
		$data = $mp->find( $cash['PayID'] );
		if(empty($data)) return false;
		$CashQuantity = (double)($cash['CashQuantity']);
		$PayRate = (double)($data['PayRate']);
		$data['TotalOrderPrice'] = sprintf("%.2f", $CashQuantity + $CashQuantity * $PayRate);
		$data['OrderNumber'] = $OrderNumber;
		return $data;
	}
	
	//设置为已支付状态(用于在线充值)，OrderNumber格式：'ZXCZ'.date('YmdHis').'_'.$CashID
	function setPayStatus($OrderNumber){
        $OrderNumber = YdInput::checkLetterNumber($OrderNumber);
		$temp = explode('_', $OrderNumber);
		$CashID = intval($temp[1]);
		$m = D('Admin/Cash');
		$result = $m->where("CashID=$CashID")->setField('CashStatus',1);
		return $result;
	}
	
	/**
	 * 获取会员分销佣金
	 * @param int $MemberID
	 * @return number
	 */
	function getTotalCommission($MemberID=-1){
		$where['CashType'] = 5;
		if( $MemberID != -1){
			$where['MemberID'] = intval($MemberID);
		}
		$n = $this->where($where)->sum('CashQuantity');
		if(empty($n)) $n=0;
		$n = round($n, 2);
		return $n;
	}
	
	/**
	 * 获取历史提现记录
	 * @param int $MemberID
	 */
	function getBank($MemberID){
		$where = "BankName!='' and BankAccount!='' and OwnerName!='' and MemberID=".intval($MemberID);
		$field="BankName,BankAccount,OwnerName";
		$result = $this->where($where)->distinct(true)->field($field)->order('CashID desc')->select();
		return $result;
	}
}

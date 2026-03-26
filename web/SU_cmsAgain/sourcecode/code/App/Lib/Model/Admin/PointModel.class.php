<?php
/**
 * 积分
 * @author Administrator
 *
 */
class PointModel extends Model{
	function getPoint($offset = -1, $length = -1, $p = array()){
		$this->field('a.*, b.MemberName,c.OrderNumber');
		$this->table($this->tablePrefix.'point a');
		$this->join('Inner Join '.$this->tablePrefix.'member b On a.MemberID = b.MemberID');
		$this->join('Left Join '.$this->tablePrefix.'order c On a.OrderID = c.OrderID');
		
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
		
		if( isset($p['MemberID']) && $p['MemberID'] != -1){
			$where['a.MemberID'] = intval($p['MemberID']);
		}
		
		if( isset($p['PointType']) && $p['PointType'] != -1){
			$where['a.PointType'] = intval($p['PointType']);
		}
		
		$result = $this->where($where)->order('a.PointID desc')->select();
		if(!empty($result)){
			$type = $this->getPointTypeList();
			$n = is_array($result) ? count($result) : 0;
			for($i=0; $i<$n; $i++){
				$key = $result[$i]['PointType'];
				$result[$i]['PointTypeName'] = array_key_exists($key, $type) ? $type[$key]['PointTypeName'] : '';
			}
		}
		return $result;
	}
	
	function getPointCount($p){
		$where = array();
		if( isset($p['MemberID']) && $p['MemberID'] != -1){
			$where['MemberID'] = intval($p['MemberID']);
		}
		$n= $this->where($where)->count();
		return $n;
	}
	
	function getPointTypeList(){
		$data = array(
			1=>array('PointTypeID'=>1, 'PointTypeName'=>'订单赠送'),	
			2=>array('PointTypeID'=>2, 'PointTypeName'=>'管理员调整'),
			3=>array('PointTypeID'=>3, 'PointTypeName'=>'积分兑换'),
		);
		return $data;
	}
	
	/**
	 * 获取会员总积分
	 * @param int $MemberID
	 * @return int
	 */
	function getTotalPoint($MemberID){
		$where['MemberID'] = intval($MemberID);
		$n = $this->where($where)->sum('PointValue');
		if(empty($n)) $n=0;
		return $n;
	}
	
	/**
	 * 订单使用积分(减少积分)
	 * @param int $MemberID
	 * @param int $point
	 */
	function orderUsePoint($OrderID, $MemberID, $PointValue){
		$data['MemberID'] = intval($MemberID);
		$data['OrderID'] = intval($OrderID);
		$data['PointValue'] = 0-intval($PointValue); //积分消费为负数
		$data['PointType'] = 3;
		$data['PointTime'] = date('Y-m-d H:i:s');
		$result = $this->add($data);
		return $result;
	}
	
	/**
	 * 赠送订单积分
	 * @param int $OrderID
	 */
	function orderGivePoint($OrderID){
		$m = D('Admin/Order');
		$where['OrderID'] = intval($OrderID);
		$order = $m->where($where)->field('MemberID,OrderPoint')->find();
		if( !empty($order) && $order['OrderPoint']>0){
			$data['MemberID'] = $order['MemberID'];
			$data['OrderID'] = intval($OrderID);
			$data['PointValue'] = $order['OrderPoint'];
			$data['PointType'] = 1;
			$data['PointTime'] = date('Y-m-d H:i:s');
			$result = $this->add($data);
			return $result;
		}
		return false;
	}
	
}

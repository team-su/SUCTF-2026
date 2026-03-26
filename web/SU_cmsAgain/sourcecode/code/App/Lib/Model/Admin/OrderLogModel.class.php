<?php
class OrderLogModel extends Model{
	function getOrderLog($offset = -1, $length = -1, $p = array()){
		$this->field('a.*, b.OrderNumber');
		$this->table($this->tablePrefix.'order_log a');
		$this->join('Left Join '.$this->tablePrefix.'order b On a.OrderID = b.OrderID');
		
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}
		$where = $this->getWhere($p);
		$result = $this->where($where)->order('a.OrderLogID desc')->select();
		return $result;
	}
	
	function getOrderLogCount($p){
		$this->field('a.*, b.OrderNumber');
		$this->table($this->tablePrefix.'order_log a');
		$this->join('Left Join '.$this->tablePrefix.'order b On a.OrderID = b.OrderID');
		$where = $this->getWhere($p);
		$n= $this->where($where)->count();
		return $n;
	}
	
	function findOrderLog($id,  $p=array() ){
		$this->field('a.*, b.OrderNumber');
		$this->table($this->tablePrefix.'order_log a');
		$this->join('Left Join '.$this->tablePrefix.'order b On a.OrderID = b.OrderID');
		$where['a.OrderLogID'] = intval($id);
		$result = $this->where($where)->find();
		return $result;
	}
	
	private function getWhere($p=array() ){
		$where = get_language_where_array('b');
		if( isset($p['OrderID']) ){
			$where['a.OrderID'] = intval($p['OrderID']);
		}
		
		if( !empty($p['OrderLogType']) &&  $p['OrderLogType'] != -1 ){
			$where['a.OrderLogType'] = intval($p['OrderLogType']);
		}
		
		if( !empty($p['OrderNumber']) ){
			$where['b.OrderNumber'] = YdInput::checkLetterNumber($p['OrderNumber']);
		}
		return $where;
	}
	
	/**
	 * 获取付款时间
	 * @param int $OrderID
	 */
	function getPayTime($OrderID){
		$where['OrderID'] = intval($OrderID);
		$where['OrderLogType'] = 2;
		$result = $this->where($where)->getField('OrderLogTime');
		return $result;
	}
	
	/**
	 * 获取发货时间
	 * @param int $OrderID
	 */
	function getShippingTime($OrderID){
		$where['OrderID'] = intval($OrderID);
		$where['OrderLogType'] = 3;
		$result = $this->where($where)->getField('OrderLogTime');
		return $result;
	}
	
	/**
	 * 获取快递单号
	 * @param int $OrderID
	 */
	function getShippingNumber($OrderID){
		$where['OrderID'] = intval($OrderID);
		$where['OrderLogType'] = 3;
		$result = $this->where($where)->getField('ShippingNumber');
		return $result;
	}
	
	/**
	 * 获取订单完成时间
	 * @param int $OrderID
	 */
	function getFinishTime($OrderID){
		$where['OrderID'] = intval($OrderID);
		$where['OrderLogType'] = 6;
		$result = $this->where($where)->getField('OrderLogTime');
		return $result;
	}
}
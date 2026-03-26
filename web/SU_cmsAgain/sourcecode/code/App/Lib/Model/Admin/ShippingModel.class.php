<?php
class ShippingModel extends Model{
	protected $_auto = array(
			array('LanguageID', 'get_language_id', 1, 'function'),
	);
	
	function getShipping($options=array()){
		$where = get_language_where_array();
		if( isset($options['IsEnable']) ){
			$where['IsEnable'] = intval($options['IsEnable']);
		}
		$result = $this->where($where)->order('ShippingOrder asc, ShippingID desc')->select();
		return $result;
	}
	
	function findShipping($id, $options = array() ){
		if( !is_numeric($id) ) return false;
		$where = get_language_where_array();
		$where['ShippingID'] = $id;
		$result = $this->where($where)->find();
		return $result;
	}
	
	//获取第一个ShippingID作为默认ID值
	function getFirstShippingID(){
		$where = get_language_where();
		$where .= " and IsEnable=1";
		$this->order('ShippingOrder asc, ShippingID desc');
		$id = $this->where($where)->limit(1)->getField('ShippingID');
		return $id;
	}
	
	//获取费用
	function getShippingPrice($id){
		if( !is_numeric($id) ) return false;
		$result = $this->where("ShippingID={$id}")->getField('ShippingPrice');
		return $result;
	}
	
	//是否是货到付款
	function isCod($id){
		if( !is_numeric($id) ) return false;
		$result = $this->where("ShippingID={$id}")->getField('IsCod');
		return $result;
	}
}

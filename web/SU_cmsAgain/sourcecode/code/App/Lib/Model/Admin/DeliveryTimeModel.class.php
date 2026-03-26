<?php
//收货时间
class DeliveryTimeModel extends Model{
	function getDeliveryTime($IsEnable=-1){
		$where = get_language_where();
		if( $IsEnable != -1){
			$IsEnable = intval($IsEnable);
			$where .= " and IsEnable=$IsEnable";
		}
		$result = $this->where($where)->order('DeliveryTimeOrder asc, DeliveryTimeID desc')->select();
		return $result;
	}
}

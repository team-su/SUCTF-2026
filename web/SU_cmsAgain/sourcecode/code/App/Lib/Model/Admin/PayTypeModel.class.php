<?php
//支付类别
class PayTypeModel extends Model{
	function getPayType($IsEnable=-1){
		if( $IsEnable != -1){
			$IsEnable = intval($IsEnable);
			$this->where("IsEnable=$IsEnable");
		}
		$result = $this->order('PayTypeOrder asc, PayTypeID desc')->select();
		return $result;
	}
}

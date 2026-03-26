<?php
class CouponModel extends Model{
	protected $_auto = array(
			array('LanguageID', 'get_language_id', 1, 'function'),
	);
	
	function getCoupon($offset = -1, $length = -1, $options=array()){
		$where = get_language_where_array();
		if( isset($options['CouponType']) && $options['CouponType'] != -1 ){
			$where['CouponType'] = intval($options['CouponType']);
		}
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}
		$result = $this->where($where)->order('CouponID desc')->select();
		if(!empty($result)){
			$m = D('Admin/CouponSend');
			$type = $this->getCouponTypeList();
			$n = is_array($result) ? count($result) : 0;
			for($i=0; $i<$n; $i++){
				$k = $result[$i]['CouponType'];
				$TypeName= isset( $type[$k] ) ? $type[$k]['CouponTypeName'] : '';
				$result[$i]['CouponTypeName'] = $TypeName;
				//获取已发放
				$options['CouponID'] = $result[$i]['CouponID'];
				$result[$i]['CouponSended'] = $m->getCouponSendCount($options);
				$result[$i]['CouponUsed'] = $m->getCouponUsedCount($result[$i]['CouponID']);
				if( $result[$i]['CouponQuantity'] <= 0){ //表示发放数量无限
					$result[$i]['HasLeft'] = 1;
				}else{
					$CouponLeft = intval($result[$i]['CouponQuantity']-$result[$i]['CouponSended']);
					$result[$i]['HasLeft'] = ($CouponLeft<=0) ? 0 : 1;
				}
			}
		}
		
		return $result;
	}
	
	function getCouponCount($options=array()){
		$where = get_language_where_array();
		if( isset($options['CouponType']) && $options['CouponType'] != -1 ){
			$where['CouponType'] = intval($options['CouponType']);
		}
		$n = $this->where($where)->count();
		return $n;
	}
	
	function findCoupon($id, $options = array() ){
		if( !is_numeric($id) ) return false;
		$where = get_language_where_array();
		$where['CouponID'] = intval($id);
		if( isset($options['CouponType']) ) {
			$where['CouponType'] = intval($options['CouponType']);
		}
		$result = $this->where($where)->find();
		return $result;
	}
	
	function getCouponTypeList(){
		$data[1] = array('CouponTypeID'=>1, 'CouponTypeName'=>'指定发放');
		$data[2] = array('CouponTypeID'=>2, 'CouponTypeName'=>'线下发放');
		return $data;
	}
	
	function delCoupon( $id = array(),  $p = array()){
		if( is_array($id) ){
			$n = count($id);
			for ($i=0; $i < $n; $i++){
				if( !is_numeric( $id[$i] ) ){
					unset( $id[$i] );
				}
			}
			if( count($id) <= 0 )  return false;
			$where = "CouponID in(".implode(',', $id).')';
		}else{
			if( !is_numeric($id) ) return false;
			$where = "CouponID=$id";
		}
		if( isset($p['MemberID']) && is_numeric($p['MemberID']) && $p['MemberID'] > 0){
			$where .=' and MemberID='.$p['MemberID'];
		}
		$result = $this->where($where)->delete();
		return $result;
	}
}

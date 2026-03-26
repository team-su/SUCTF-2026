<?php
class CouponSendModel extends Model{
	protected $_auto = array(
			array('LanguageID', 'get_language_id', 1, 'function'),
	);
	
	function getCouponSend($offset = -1, $length = -1, $options=array()){
		$where = get_language_where_array('a');
		if( isset($options['CouponID']) ){
			$where['a.CouponID'] = intval($options['CouponID']);
		}
		if( isset($options['MemberID']) ){
			$where['a.MemberID'] = intval($options['MemberID']);
		}
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}
		
		$this->field('b.*, a.*');
		$this->table($this->tablePrefix.'coupon_send a');
		$this->join('Inner Join '.$this->tablePrefix.'coupon b On a.CouponID = b.CouponID');
		
		$result = $this->where($where)->order('a.CouponSendTime desc, a.CouponSendID desc')->select();
		if(!empty($result)){
			$m = D('Admin/Member');
			$ma = D('Admin/Order');
			$n = is_array($result) ? count($result) : 0;
			$time = time();
			for($i=0; $i<$n; $i++){
				$data = $ma->getCouponInfo($result[$i]['OrderID']);
				if(!empty($data)){
					$result[$i]['OrderNumber'] = $data['OrderNumber'];
					$result[$i]['OrderTime'] = $data['OrderTime'];
					//使用会员必须从订单表获取MemberID，而不是CouponSend表，因为线下优惠券没有MemberID
					$MemberName = $m->where('MemberID='.$data['MemberID'])->getField('MemberName');
					$result[$i]['MemberName'] = $MemberName;
				}else{
					$result[$i]['OrderNumber'] = '';
					$result[$i]['OrderTime'] = '';
					$result[$i]['MemberName'] = '';
				}
				
				$result[$i]['CouponStatus'] = 1; //正常未使用状态
				if(!empty($result[$i]['OrderID'])){
					$result[$i]['CouponStatus'] = 2; //已使用
				}else if( $time > strtotime($result[$i]['EndTime']) ){
					$result[$i]['CouponStatus'] = 3; //过期
				}
			}
		}
		return $result;
	}
	
	function getCouponSendCount($options=array()){
		$where = get_language_where_array();
		if( isset($options['CouponID']) ){
			$where['CouponID'] = intval($options['CouponID']);
		}
		if( isset($options['MemberID']) ){
			$where['MemberID'] = intval($options['MemberID']);
		}
		$n = $this->where($where)->count();
		return $n;
	}
	
	/**
	 * 获取指定优惠券已使用数
	 * @param int $CouponID
	 */
	function getCouponUsedCount($CouponID){
		$where = get_language_where_array();
		$where['CouponID'] = intval($CouponID);
		$where['OrderID'] = array('gt', 0);
		$n = $this->where($where)->count();
		return $n;
	}
	
	/**
	 * 获取优惠券剩余发放数量
	 * @param int $CouponID
	 */
	function getCouponLeftCount($CouponID){
		$m = D('Admin/Coupon');
		$where['CouponID'] = intval($CouponID);
		$total = $m->where($where)->getField('CouponQuantity');
		$left = 'infinite';
		if($total > 0){
			$nSendedCount = $this->getCouponSendCount($where);
			$left = $total - $nSendedCount;
		}
		return $left;
	}
	
	function delCouponSend( $id = array(),  $p = array()){
		if( is_array($id) ){
			$n = count($id);
			for ($i=0; $i < $n; $i++){
				if( !is_numeric( $id[$i] ) ){
					unset( $id[$i] );
				}
			}
			if( count($id) <= 0 )  return false;
			$where = "CouponSendID in(".implode(',', $id).')';
		}else{
			if( !is_numeric($id) ) return false;
			$where = "CouponSendID=$id";
		}
		if( isset($p['MemberID']) ){
			$where .= " and MemberID=".intval($p['MemberID']);
		}
		$where .= " and OrderID=0"; //只能删除未使用的订单
		$result = $this->where($where)->delete();
		return $result;
	}
	
	/**
	 * 获取会员可用优惠券列表
	 */
	function getAvailableCoupon($MemberID){
		if(empty($MemberID)) return false;
		$where = get_language_where('a');
		$where .= ' and a.MemberID='.intval($MemberID);
		$where .= ' and NOW() >= StartTime and NOW()<=EndTime';
		$where .= ' and a.OrderID=0';  //0表示未被使用
		
		$this->field('b.CouponName,b.ConsumeMoney,b.CouponMoney,a.*');
		$this->table($this->tablePrefix.'coupon_send a');
		//CouponType=1：表示只显示线上优惠券
		$this->join("inner join ".$this->tablePrefix.'coupon b On a.CouponID = b.CouponID and CouponType=1');
		$result = $this->where($where)->order('CouponSendTime desc, CouponSendID desc')->select();
		return $result;
	}
	
	/**
	 * 检查线下优惠券码是否有效
	 * @param string $CouponCode
	 * @param double $TotalPrice 商品金额
	 */
	function checkCouponCode($CouponCode){
        $CouponCode = YdInput::checkLetterNumber($CouponCode);
		$where['CouponCode'] = $CouponCode;
		$data = $this->where($where)->field('CouponSendID,CouponID,OrderID')->find();
		if(empty($data)){
			return 2; //判断优惠券是否存在
		}
		if( $data['OrderID'] > 0){
			return 3; //判断优惠券已被使用
		}
		$CouponID = (int)$data['CouponID'];
		
		//判断优惠券是否过期
		$m = D('Admin/Coupon');
		$where = "CouponID=$CouponID and NOW() >= StartTime and NOW()<=EndTime and CouponType=2";
		$result = $m->where($where)->field('CouponID,CouponMoney,ConsumeMoney')->find();
		if(empty($result)){
			return 1;  //表示优惠券已过期
		}
		$result['CouponSendID'] = $data['CouponSendID'];
		return $result;
	}
	
	/**
	 * 检查优惠券是否有效（线下券除外）
	 * @param int $CouponSendID
	 */
	function checkCoupon($CouponSendID){
		$where['CouponSendID'] = intval($CouponSendID);
		$data = $this->where($where)->field('CouponSendID,CouponID,OrderID')->find();
		if(empty($data)){
			return 2; //判断优惠券是否存在
		}
		if( $data['OrderID'] > 0){
			return 3; //判断优惠券已被使用
		}
		$CouponID = $data['CouponID'];
		
		//判断优惠券是否过期
		$m = D('Admin/Coupon');
		$where = "CouponID={$CouponID} and NOW() >= StartTime and NOW()<=EndTime";
		$result = $m->where($where)->field('CouponID,CouponMoney,ConsumeMoney')->find();
		if(empty($result)){
			return 1;  //表示优惠券已过期
		}
		$result['CouponSendID'] = $data['CouponSendID'];
		return $result;
	}
	
	/**
	 * 设置优惠券订单ID
	 * @param int $CouponSendID
	 * @param int $OrderID
	 */
	function SetOrderID($CouponSendID, $OrderID){
		$where['CouponSendID'] = intval($CouponSendID);
		$result = $this->where($where)->setField('OrderID', (int)$OrderID);
		return $result;
	}
}

<?php
class OrderModel extends Model{
	protected $_auto = array(
			array('LanguageID', 'get_language_id', 1, 'function'),
	);
	
	function getOrder($offset = -1, $length = -1, $p = array()){
		$this->field('a.*, b.MemberName,b.MemberRealName,c.*,d.ShippingName,e.IsOnline');
		$this->table($this->tablePrefix.'order a');
		$this->join('Left Join '.$this->tablePrefix.'member b On a.MemberID = b.MemberID');
		$this->join('Left Join '.$this->tablePrefix.'pay c On a.PayID = c.PayID');
		$this->join('Left Join '.$this->tablePrefix.'shipping d On a.ShippingID = d.ShippingID');
		$this->join('Left Join '.$this->tablePrefix.'pay_type e On c.PayTypeID = e.PayTypeID');
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);	
		}
		$where = get_language_where_array('a');
		if( isset($p['MemberID']) && $p['MemberID'] != -1 && is_numeric($p['MemberID'])){
			$where['a.MemberID'] = intval($p['MemberID']);
		}
		
		if( !empty($p['OrderNumber'])  ){
            $OrderNumber = YdInput::checkLetterNumber(trim($p['OrderNumber']));
			$where['a.OrderNumber'] = array('like', "%{$OrderNumber}%");
		}
		
		if( !empty($p['ConsigneeRealName'])  ){
			$where['a.ConsigneeRealName'] = trim($p['ConsigneeRealName']);
		}
		
		if( isset($p['OrderStatus']) && $p['OrderStatus'] != -1 ){
			$where['a.OrderStatus'] = intval($p['OrderStatus']);
		}
		
		$result= $this->where($where)->order('a.OrderTime desc')->select();
		if( !empty($result)){
			$status = get_order_status();
			$n = is_array($result) ? count($result) : 0;
			for($i = 0; $i < $n; $i++){
				$result[$i]['TotalOrderPrice'] = $result[$i]['TotalPrice'] + $result[$i]['ShippingPrice'] + $result[$i]['PayPrice'] - $result[$i]['CouponPrice']- $result[$i]['PointPrice'] + $result[$i]['DiscountPrice'];
				$id = $result[$i]['OrderStatus'];
				$result[$i]['OrderStatusName'] = isset( $status[$id] ) ? $status[$id]['OrderStatusName'] : '';
				$result[$i]['MemberOrderStatusName'] = isset( $status[$id] ) ? $status[$id]['MemberOrderStatusName'] : '';
			}
		}
		return $result;
	}
	
	function getOrderCount($p){
		$where = get_language_where_array();
		if( isset($p['MemberID']) && $p['MemberID'] != -1 && is_numeric($p['MemberID'])){
			$where['MemberID'] = intval($p['MemberID']);
		}
		
		if( !empty($p['OrderNumber'])  ){
            $p['OrderNumber'] = YdInput::checkLetterNumber(trim($p['OrderNumber']));
			$where['OrderNumber'] = $p['OrderNumber'];
		}
		
		if( !empty($p['ConsigneeRealName'])  ){
			$where['ConsigneeRealName'] = trim($p['ConsigneeRealName']);
		}
		
		if( isset($p['OrderStatus']) && $p['OrderStatus'] != -1 ){
			$where['OrderStatus'] = intval($p['OrderStatus']);
		}
		$n= $this->where($where)->count();
		return $n;
	}
	
	//id:自动识别为OrderID还是OrderNumber
	function findOrder($id, $p=array('MemberID' => '-1') ){
		$this->field('a.*, b.MemberName,b.MemberRealName,c.*,d.ShippingName,e.IsOnline');
		$this->table($this->tablePrefix.'order a');
		$this->join('Left Join '.$this->tablePrefix.'member b On a.MemberID = b.MemberID');
		$this->join('Left Join '.$this->tablePrefix.'pay c On a.PayID = c.PayID');
		$this->join('Left Join '.$this->tablePrefix.'shipping d On a.ShippingID = d.ShippingID');
		$this->join('Left Join '.$this->tablePrefix.'pay_type e On c.PayTypeID = e.PayTypeID');
		if( strlen($id) > 14){
            $id = YdInput::checkLetterNumber($id);
			$where['a.OrderNumber'] = $id;
		}else{
			$where['a.OrderID'] = intval($id);
		}
		if( isset($p['MemberID']) && $p['MemberID'] != -1 && is_numeric($p['MemberID'])){
			$where['a.MemberID'] = intval($p['MemberID']);
		}

		$result = $this->where($where)->find();
		if( !empty($result)){
			$status = get_order_status();
			$id = $result['OrderStatus'];
			$result['OrderStatusName'] = isset( $status[$id] ) ? $status[$id]['OrderStatusName'] : '';
			$result['MemberOrderStatusName'] = isset( $status[$id] ) ? $status[$id]['MemberOrderStatusName'] : '';
			$result['TotalOrderPrice'] = $result['TotalPrice'] + $result['ShippingPrice'] + $result['PayPrice'] - $result['CouponPrice']- $result['PointPrice'] + $result['DiscountPrice'];
		}
		return $result;
	}
	
	/**
	 * 批量删除订单
	 * @param int $id
	 * @param int $GuestID
	 * @return unknown
	 */
	function delOrder( $id = array(),  $p = array()){
		if( is_array($id) ){
			$n = count($id);
			for ($i=0; $i < $n; $i++){
				if( !is_numeric( $id[$i] ) ){
					unset( $id[$i] );
				}
			}
			if( count($id) <= 0 )  return false;
			$where = "OrderID in(".implode(',', $id).')';
		}else{
			if( !is_numeric($id) ) return false;
			$where = "OrderID=$id";
		}
		$where1 = $where;
		if( isset($p['MemberID']) && $p['MemberID'] != -1 && is_numeric($p['MemberID'])){
			$where .= " and MemberID=".$p['MemberID'];
		}
		$where .= " and OrderStatus in(7,8)";  //只能删除已作废或已取消的的订单
		
		//删除订单数据
		$result = $this->where($where)->delete();
		if($result){
			//1.先删除订单的产品
			$m = D('Admin/OrderProduct');
			$result = $m->where($where1)->delete();
			//2. 删除订单赠送的积分
			$mp = D('Admin/Point');
			$result = $mp->where($where1)->delete();
			//3. 删除订单日志
			$mol = D('Admin/OrderLog');
			$result = $mol->where($where1)->delete();
			//4. 删除分销返佣
			$mc = D('Admin/Cash');
			$result = $mc->where($where1)->delete();
		}
		return $result;
	}
	
	//获取最近一次收货人信息
	function getLatestConsignee($memberID){
		$memberID = intval($memberID);
		$where = get_language_where();
		$where .= " and MemberID=$memberID ";
		$field="MemberID,ConsigneeRealName,ConsigneeGender,ConsigneeEmail,ConsigneePostcode";
		$field .=",ConsigneeMobile,ConsigneeTelephone,ConsigneeAddress,ConsigneeRemark";
		$field .=",DeliveryTimeID,ShippingID,PayID";
		$data = $this->field($field)->where($where)->limit(1)->order("OrderID desc")->find();
		if(empty($data)){
			$m = D('Admin/Member');
			$where = " MemberID=$memberID ";
			$field = "MemberID,MemberRealName as ConsigneeRealName,MemberGender as ConsigneeGender";
			$field .= ",MemberAddress as ConsigneeAddress,MemberPostCode as ConsigneePostcode";
			$field .= ",MemberEmail as ConsigneeEmail,MemberTelephone as ConsigneeTelephone";
			$field .=" ,MemberMobile as ConsigneeMobile";
			$data = $m->field($field)->where($where)->find();
			if(!empty($data)){
				$m1 = D('Admin/Shipping');
				$m2 = D('Admin/Pay');
				$data['ConsigneeRemark']="";
				$data['DeliveryTimeID']=1;
				$data['ShippingID']=$m1->getFirstShippingID();
				$data['PayID']=$m2->getFirstPayID();
			}
		}
		return $data;
	}
	
	//生成订单编号
	function makeOrderNumber(){
		$prefix = $GLOBALS['Config']['ORDER_PREFIX'];
		$time =  date('YmdHis');
		$last4 = rand(1000, 9999); //后4位数
		$number = $prefix.$time.$last4;
		return $number;
	}
	
	/**
	 * 用户取消订单
	 * @param int $orderid
	 * @param int $memberID
	 */
	function cancelOrder($orderid, $p=array()){
        $orderid = intval($orderid);
        $OrderStatus = $this->_getOrderStatus($orderid);
        if(1 != $OrderStatus) return;  //1：新订单
		$m = D('Admin/OrderLog');
		$data['OrderID'] = $orderid;
		$data['OrderLogType'] = 8;
		if( isset( $p['MemberName']) ){ //会员自己操作
			$data['Operator'] = $p['MemberName'];
		}elseif(isset( $p['AdminName']) ){ //有管理员操作
			$data['Operator'] = $p['AdminName'];
		}
		$data['OrderLogTime'] = date('Y-m-d H:i:s');
		$result = $m->add($data);
		if($result){
			$this->setOrderStatus($orderid, 8, $p);
		}
	}
	
	/**
	 * 用户确认收货
	 * @param int $orderid
	 * @param int $memberID
	 */
	function confirmReceipt($orderid, $p=array()){
        $orderid = intval($orderid);
	    $OrderStatus = $this->_getOrderStatus($orderid);
	    if(3 != $OrderStatus) return;  //3：已发货
		$m = D('Admin/OrderLog');
		$data['OrderID'] = $orderid;
		$data['OrderLogType'] = 6;
		if( isset( $p['MemberName']) ){ //会员自己操作
			$data['Operator'] = $p['MemberName'];
		}elseif(isset( $p['AdminName']) ){ //有管理员操作
			$data['Operator'] = $p['AdminName'];
		}
		if( isset($p['OrderLogRemark'])){
			$data['OrderLogRemark'] = $p['OrderLogRemark'];
		}
		$data['OrderLogTime'] = date('Y-m-d H:i:s');
		$result = $m->add($data);
		if($result){
			//1. 设置订单状态
			$this->setOrderStatus($orderid, 6, $p);
			//2. 确认收货以后，赠送积分
			$mp = D('Admin/Point');
			$b = $mp->orderGivePoint($orderid);
			//3. 三级分销，计算返利
			$b = distribute_rebate($orderid);
			//4. 自动成为分销商（必须放在最后）
			auto_set_distributor($orderid);
		}
	}

	private function _getOrderStatus($orderid){
        $where = "OrderID=".intval($orderid);
        $status = $this->where($where)->getField('OrderStatus');
        return $status;
    }
	
	/**
	 * 自动确认收货
	 */
	function autoConfirmReceipt(){
		$days = intval($GLOBALS['Config']['AUTO_RECEIVE_DAYS']); //自动收货时间
		if($days <= 0 ) return;
		$where = "PayStatus=1 and ShippingStatus=1 and OrderStatus=3";
		$order = $this->where($where)->field('OrderID,MemberID')->select();
		if(!empty($order)){
			$mo = D('Admin/OrderLog');
			foreach ($order as $v){
				//获取发货时间
				$OrderID = $v['OrderID'];
				$wh = "OrderID={$OrderID} and OrderLogType=3 and TIMESTAMPDIFF(DAY,OrderLogTime,NOW())>={$days}";
				$Operator = $mo->where($wh)->getField('Operator');
				if($Operator){ //如果存在，则说明可以自动收货了
					$p['MemberName'] = $Operator;
					$p['OrderLogRemark'] = '系统自动确认收货';
					$this->confirmReceipt($OrderID, $p);
				}
			}
		}
	}
	
	//设置订单状态： 1：待处理、2：已处理、3：退款、4：退货
	function setOrderStatus($orderid, $status, $p=array()){
		return $this->setOrder($orderid, $status, false, false, $p);
	}
	
	//设置支付状态：1:已支付，2：未支付，同时改变OrderStatus和PayStatus
	function setPayStatus($orderid, $status, $p=array()){
		return $this->setOrder($orderid, 2, $status, false, $p);
	}
	
	function setOrder($orderid, $orderStatus=false, $payStatus=false, $shippingStatus=false, $p=array()){
        $orderid = YdInput::checkLetterNumber($orderid);
        $where = array();
		if( strlen($orderid) > 14){
			$where['OrderNumber'] = $orderid;
		}else{
            $orderid = intval($orderid);
			$where['OrderID'] = $orderid;
		}
		
		if( isset($p['MemberID']) && $p['MemberID'] != -1 && is_numeric($p['MemberID'])){
			$where['MemberID'] = $p['MemberID'];
		}

        $data = array();
		if( $orderStatus!==false) {
			$orderStatus = intval($orderStatus);
			$data['OrderStatus'] = $orderStatus;
			if($orderStatus==8){ //表示用户取消订单
				$where['OrderStatus'] = 1;
			}elseif($orderStatus==6){ //确认收货，前提是必须已支付、已发货
				$where['PayStatus'] = 1;
				$where['ShippingStatus'] = 1;
			}
		}
		
		if( $payStatus!==false) {
			$data['PayStatus'] = intval($payStatus);
		}
		
		if( $shippingStatus!==false) {
			$data['ShippingStatus'] = intval($shippingStatus);
			if($shippingStatus==1){ //发货时，必须同时减少库存和更新销量
				$mo = D('Admin/OrderProduct');
				$wh['OrderID'] = $orderid;
				$result = $mo->where($wh)->field('ProductID,ProductQuantity')->select();
				if(!empty($result)){
					$mi = D('Admin/Info');
					foreach ($result as $v){
						$n = $v['ProductQuantity'];
						$sql = "Update {$this->tablePrefix}info set StockCount=StockCount-{$n},SalesCount=SalesCount+{$n}";
						$sql .= " Where InfoID={$v['ProductID']}";
						$b = $mi->execute($sql);
					}
				}
			}
		}
		return $this->where($where)->setField($data);
	}
	
	/**
	 * 获取优惠券的订单信息
	 * @param int $OrderID
	 */
	function getCouponInfo($OrderID){
		if($OrderID==0) return false;
		$where['OrderID'] = intval($OrderID);
		$result = $this->where($where)->field('OrderNumber,OrderTime,MemberID')->find();
		return $result;
	}
	
	/**
	 * 指定会员的订单是否存在
	 * @param int $OrderID
	 * @param int $MemberID
	 */
	function orderExist($OrderID, $MemberID){
		$where['OrderID'] = intval($OrderID);
		$where['MemberID'] = intval($MemberID);
		$result = $this->where($where)->getField('OrderID');
		if($result){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * 订单统计
	 */
	function statOrder($MemberID){
		$MemberID = intval($MemberID);
		$sql = "SELECT count(OrderID) as OrderCount, OrderStatus FROM {$this->tablePrefix}order";
		$sql .= " WHERE MemberID={$MemberID} and LanguageID=".get_language_id();
		$sql .= " GROUP BY OrderStatus";
		$result = $this->query($sql);
        $data = array();
		if(empty($result)){
			$data= array(1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>0);
		}else{
			foreach ($result as $v){
				$data[$v['OrderStatus']] = $v['OrderCount'];
			}
		}
		return $data;
	}
	
	/**
	 * 获取会员总消费金额
	 * @param int $MemberID
	 */
	function getTotalOrderPrice($MemberID, $OrderID=-1){
		$where['MemberID']=intval($MemberID);
		$where['OrderStatus']=6; //6：表示已结单
		if($OrderID!=-1){
			$where['OrderID'] = intval($OrderID);
		}
		$total = 0;
		$result = $this->where($where)->field("TotalPrice,PayPrice,ShippingPrice,CouponPrice,PointPrice,DiscountPrice")->select();
		if(!empty($result)){
			foreach ($result as $v){
				$total += $v['TotalPrice']+$v['PayPrice']+$v['ShippingPrice']-$v['CouponPrice']-$v['PointPrice']+$v['DiscountPrice'];
			}
			$total = round($total, 2);
		}
		return $total;
	}
}
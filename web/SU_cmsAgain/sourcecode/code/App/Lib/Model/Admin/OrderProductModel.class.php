<?php
//订购的商品
class OrderProductModel extends Model{
	function getOrderProduct($OrderID=-1){
		if( $OrderID != -1){
			$where['OrderID'] = intval($OrderID);
		}
		if(!empty($where)){
			$this->where($where);
		}
		$data = $this->order('OrderProductID asc')->select();
		if(!empty($data)){
			$n = is_array($data) ? count($data) : 0;
			for($i=0; $i < $n; $i++){
				$data[$i]['TotalPrice'] = $data[$i]['ProductQuantity'] * $data[$i]['ProductPrice'];
				$data[$i]['ProductAttributes'] = $this->AttributeString2Array( $data[$i]['ProductAttribute'] );
			}
		}
		return $data;
	}
	
	function AttributeString2Array($string){
		if(empty($string)) return false;
		$row = explode('@@@', $string);
		$result = array();
		foreach ($row as $r){
			$col = explode('###', $r);
			$result[] = array(
				'AttributeValueID'=>intval($col[0]),
				'AttributeValue'=>$col[1],
				'AttributePrice'=>$col[2],
				'TypeAttributeName'=>$col[3]
			);
		}
		return $result;
	}
	
	/**
	 * 销售统计
	 * @param int $type 1:销售排行榜  2:销售明细
	 * @param string $startTime
	 * @param string $endTime
	 * @param array $p
	 */
	function stat($type, $startTime, $endTime, $offset = -1, $length = -1) {
        $startTime = YdInput::checkDatetime($startTime);
        $endTime = YdInput::checkDatetime($endTime);
		$where = get_language_where_array('b');
		$where['PayStatus'] = 1;
		$where['OrderStatus'] = array('not in', '4,5,7'); //4,5,7 退款 退货 作废
		$where['OrderTime'] = array(array('egt', $startTime), array('elt', $endTime), 'AND');
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}
		$data = array();
        $type = intval($type);
		switch($type){
			case 1: //销售排行榜
				$this->field('ProductID,ProductName,sum(ProductQuantity) as TotalQuantity, sum(ProductQuantity*ProductPrice) as TotalMoney');
				$this->table($this->tablePrefix.'order_product a');
				$this->join($this->tablePrefix.'info b On a.ProductID = b.InfoID');
				$this->join("inner join ".$this->tablePrefix.'order c On a.OrderID = c.OrderID');
				$this->order('TotalQuantity desc')->group("ProductID");
				$data = $this->where($where)->select();
				if(!empty($data)){
					$n = is_array($data) ? count($data) : 0;
					for($i = 0; $i < $n; $i++){
						$data[$i]['AveragePrice'] = sprintf("%.2f", $data[$i]['TotalMoney']/$data[$i]['TotalQuantity']);
						$data[$i]['TotalMoney'] = sprintf("%.2f", $data[$i]['TotalMoney']);
					}
				}
				break;
			case 2: //销售明细
				$this->field('ProductID,ProductName,ProductQuantity,OrderNumber,OrderTime,ProductPrice');
				$this->table($this->tablePrefix.'order_product a');
				$this->join($this->tablePrefix.'info b On a.ProductID = b.InfoID');
				$this->join("inner join ".$this->tablePrefix.'order c On a.OrderID = c.OrderID');
				$this->order('c.OrderTime desc');
				$data = $this->where($where)->select();
				break;
		}
		return $data;
	}
	
	function getStatCount($type, $startTime, $endTime){
        $startTime = YdInput::checkDatetime($startTime);
        $endTime = YdInput::checkDatetime($endTime);
		$where = get_language_where_array('b');
		$where['PayStatus'] = 1;
		$where['OrderStatus'] = array('not in', '4,5,7'); //4,5,7 退款 退货 作废
		$where['OrderTime'] = array(array('egt', $startTime), array('elt', $endTime), 'AND');
        $type = intval($type);
		switch($type){
			case 1:
				$this->table($this->tablePrefix.'order_product a');
				$this->join($this->tablePrefix.'info b On a.ProductID = b.InfoID');
				$this->join('inner join '.$this->tablePrefix.'order c On a.OrderID = c.OrderID');
				$this->group("ProductID");
				break;
			case 2:
				$this->table($this->tablePrefix.'order_product a');
				$this->join($this->tablePrefix.'info b On a.ProductID = b.InfoID');
				$this->join('inner join '.$this->tablePrefix.'order c On a.OrderID = c.OrderID');
				break;
		}
		$n = $this->where($where)->count();
		return $n;
	}

	/**
	 * 按天统计销售金额
	 * @param int $year
	 * @param int $month
	 * 	@param int $dayOfMonth 当月的第几天
	 * @return array
	 */
	function statMoneyByDay(){
        $currentYear = date('Y');
        $where = get_language_where();
        $where .= " and PayStatus=1 and OrderStatus not in(4,5,7) and YEAR(OrderTime)='{$currentYear}'";
        $this->field("DATE_FORMAT(OrderTime,'%Y-%m-%d') as Day,sum(PayPrice+ShippingPrice+TotalPrice+DiscountPrice) as TotalMoney");
        $this->table($this->tablePrefix.'order');
        $this->order('OrderTime DESC')->group("Day");
        $data = $this->where($where)->select();
        return $data;
	}
	
	function statMoneyByMonth(){
        $currentYear = date('Y');
        $where = get_language_where();
        $where .= " and PayStatus=1 and OrderStatus not in(4,5,7) and YEAR(OrderTime)='{$currentYear}'";
		$this->field("DATE_FORMAT(OrderTime,'%Y-%m') as YearMonth,sum(PayPrice+ShippingPrice+TotalPrice+DiscountPrice) as TotalMoney");
		$this->table($this->tablePrefix.'order');
		$this->order('OrderTime DESC')->group("YearMonth");
		$data = $this->where($where)->select();
		return $data;
	}
}
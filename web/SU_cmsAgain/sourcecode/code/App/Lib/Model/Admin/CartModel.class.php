<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class CartModel extends Model{
	protected $_auto = array(
			array('LanguageID', 'get_language_id', 1, 'function'),
	);
	
	/**
	 * 获取购物车所有商品
	 * @param unknown_type $p
	 * @return array 二维数组的键是ProductID，便于判断产品是否存在
	 */
	function getCart($p=array() , $MemberID=false){
		$this->field('a.CartID,a.ProductID, a.ProductQuantity, a.AttributeValueID,b.InfoTitle as ProductName,b.InfoPrice as ProductPrice,b.InfoPicture as ProductPicture,b.Html,b.LinkUrl,b.ChannelID');
		$this->table($this->tablePrefix.'cart a');
		$this->join('Inner Join '.$this->tablePrefix.'info b On a.ProductID = b.InfoID');
		$where = get_language_where_array('a');
		$where['b.IsEnable'] = 1;
		
		if(!empty($MemberID)){
			$where['a.MemberID'] = intval($MemberID);
		}else{
			$where['a.MemberID'] = session('MemberID');
		}
		
		$result = $this->where($where)->order('a.CartID asc')->select();
		return $result;
	}
	
	/**
	 * 将cookie中的购物车数据存放到数据库
	 * @param array $data（ProductID、ProductName、ProductQuantity、ProductPrice）
	 */
	function addAllCart($cookiedata, $MemberID=false){
		$mid = !empty($MemberID) ? intval($MemberID) : session('MemberID');
		$lid = get_language_id();
		$data = array();
		foreach ($cookiedata as $k=>$v){
			$data[] = array(
				'ProductID' => intval($v['ProductID']),
				'ProductQuantity'=>intval($v['ProductQuantity']),
				'MemberID' => intval($mid),
				'LanguageID' => intval($lid),
				'AttributeValueID'=>YdInput::checkCommaNum($v['AttributeValueID'])
			);
		}

		if( $this->addAll($data) ){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * 清空购物车
	 */
	function clearCart($MemberID=false){
		$where = get_language_where_array();
		if(!empty($MemberID)){
			$where['MemberID'] = intval($MemberID);
		}else{
			$where['MemberID'] = session('MemberID');
		}
		$result = $this->where($where)->delete();
		return $result;
	}
	
	/**
	 * 添加商品到购物车，当购物车中已存在所要添加的商品时,只进行库存更改操作
	 * @param array $data 一维数组关联数组 array ('ProductID'=>$id, 'ProductQuantity'=>$n);
	 * @return boolean
	 */
	function addCart($data,$MemberID=false){
		$mid = !empty($MemberID) ? intval($MemberID) : session('MemberID');
		$where['ProductID'] = intval($data['ProductID']);
		$where['MemberID'] = $mid;
		$where['AttributeValueID'] = $data['AttributeValueID']; //规格必须相同才能算同一个产品
		$n = $this->where($where)->count();
		if( $n > 0 ){ //表示商品已经存在，只更新数量
			if( is_numeric($data['ProductQuantity'])){
				$this->where($where)->setInc('ProductQuantity', $data['ProductQuantity']);
			}
		}else{
			$data['MemberID'] = $mid;
			$data['LanguageID'] = get_language_id();
			$this->where($where)->add($data);
		}
		return true;
	}
	
	/**
	 * 设置产品数量
	 * @param int $id CartID
	 * @param int $n 数量
	 * @param int $type  1: 设置，2：加1，3：减1
	 */
	function setQuantity($id, $n=0, $type=1,$MemberID=false){
		$where['CartID'] = intval($id);
		if(!empty($MemberID)){
			$where['MemberID'] = intval($MemberID);
		}else{
			$where['MemberID'] = session('MemberID');
		}
        $type = intval($type);
		switch ($type){
			case 2:
				$ProductQuantity = $this->where($where)->getField('ProductQuantity');
				$ProductQuantity++;
				break;
			case 3:
				$ProductQuantity = $this->where($where)->getField('ProductQuantity');
				$ProductQuantity--;
				if( $ProductQuantity < 1) $ProductQuantity = 1;
				break;
			default:
				$ProductQuantity = $n;
		}
		$b = $this->where($where)->setField('ProductQuantity', (int)$ProductQuantity);
		return $ProductQuantity;
	}
	
	/**
	 * 删除购物车指定产品
	 * @param int $id 购物车CartID
	 * @param string $valueid 规格值ID,多个以逗号隔开
	 * @return bool
	 */
	function deleteCart($id,$MemberID=false){
		$where['CartID'] = YdInput::checkCommaNum($id);
		if(!empty($MemberID)){
			$where['MemberID'] = intval($MemberID);
		}else{
			$where['MemberID'] = session('MemberID');
		}
		$result = $this->where($where)->delete();
		return $result;
	}
	
	/**
	 * 获取当前购物车商品种类数
	 * @return int
	 */
	function getItemCount($MemberID=false){
		$where = get_language_where_array();
		if(!empty($MemberID)){
			$where['MemberID'] = intval($MemberID);
		}else{
			$where['MemberID'] = session('MemberID');
		}
		$n = $this->where($where)->count();
		return $n;
	}
	
	/**
	 * 获取购物车商品总数
	 * @param int $id 不为空，表示获取某个商品的数量
	 * @param string $valueid 规格值ID,多个以逗号隔开
	 * @return int
	 */
	function getTotalCount($id=false, $valueid='', $MemberID=false){
		$where = get_language_where_array();
		if(!empty($MemberID)){
			$where['MemberID'] = intval($MemberID);
		}else{
			$where['MemberID'] = session('MemberID');
		}
		
		$where['AttributeValueID'] = YdInput::checkCommaNum($valueid);
		if( is_numeric($id) ){
			$where['ProductID'] = $id;
			$n = $this->where($where)->getField('ProductQuantity');
		}else{
			$n = $this->where($where)->sum('ProductQuantity');
		}
		return $n;
	}
	
	
	/**
	 * 获取购物车总金额
	 * @param int $id CartID
	 * @param string $valueid 规格值ID,多个以逗号隔开
	 * @return double 购物车总金额，保留2位小数
	 */
	function getTotalPrice($id=false, $MemberID=false){
		$where = get_language_where_array('a');
		if(!empty($MemberID)){
			$where['a.MemberID'] = intval($MemberID);
		}else{
			$where['a.MemberID'] = session('MemberID');
		}

		$this->table($this->tablePrefix.'cart a');
		$this->join('Inner Join '.$this->tablePrefix.'info b On a.ProductID = b.InfoID');
		if( is_numeric($id) ){ //表示获取单个产品的总金额
			$where['CartID'] = $id;
		}
		$total = 0;
		$this->field('a.AttributeValueID, a.ProductQuantity, b.InfoPrice');
		$result = $this->where($where)->select();
		if(!empty($result)){
			$mt = D('Admin/TypeAttributeValue');
			foreach ($result as $v){
				$AttributePrice= $mt->getAttributePriceByAttributeValueID( $v['AttributeValueID'] );
				$total += ( $v['InfoPrice'] + $AttributePrice) * $v['ProductQuantity'] * $GLOBALS['DiscountRate'];
			}
		}
		$total = sprintf("%.2f", $total);
		return $total;
	}
	
	/**
	 * 判断指定商品是否在购物车中
	 * @param int $id 产品ID
	 * @param string $valueid 产品规格属性值，多个以逗号隔开
	 */
	function has($id, $valueid='',$MemberID=false){
		$where = get_language_where_array();
		if(!empty($MemberID)){
			$where['MemberID'] = intval($MemberID);
		}else{
			$where['MemberID'] = session('MemberID');
		}
		$where['ProductID'] = intval($id);
		$where['AttributeValueID'] = YdInput::checkCommaNum($valueid);
		$n = $this->where($where)->count();
		$b = ( $n>0 ) ? true : false;
		return $b;
	}
	
	/**
	 * 判断当前购物车是否为空
	 */
	function isEmpty($MemberID=false){
		$where = get_language_where_array();
		if(!empty($MemberID)){
			$where['MemberID'] = intval($MemberID);
		}else{
			$where['MemberID'] = session('MemberID');
		}
		$n = $this->where($where)->count();
		$b = ( $n>0 ) ? false : true;
		return $b;
	}
	
	/**
	 * 获取当前购物车可兑换积分总和（最大可用积分）
	 * @return int
	 */
	function getCartExchangePoint($MemberID){
		return $this->sumPoint($MemberID, 1);
	}
	
	function getCartGivePoint($MemberID){
		return $this->sumPoint($MemberID, 1);
	}
	
	/**
	 * 统计积分
	 * @param int $MemberID
	 * @param int $type 1: ExchangePoint, 2:GivePoint, 3:2者(返回数组)
	 * @return number
	 */
	function sumPoint($MemberID, $type=1){
		$maxPoint = ($type==1 || $type==2) ? 0 : array('ExchangePoint'=>0, 'GivePoint'=>0);
		$where = get_language_where();
		$where .= ' and MemberID='.intval($MemberID);
		$result = $this->where($where)->field('ProductID,ProductQuantity')->select();
		if(empty($result)) return $maxPoint;
		
		//获取相应的产品信息
		$id = array();
		foreach ($result as $v){
			$id[] = $v['ProductID'];
		}
		$where1 = "InfoID in(".implode(',', $id).')';
		$m = D('Admin/Info');
		$product = $m->where($where1)->getField("InfoID,ExchangePoint,GivePoint");
		if(empty($product)) return $maxPoint;

		$TotalExchangePoint = 0;
		$TotalGivePoint = 0;
		foreach ($result as $v){
			$k = $v['ProductID'];
			$n = $v['ProductQuantity'];
			$ExchangePoint = isset($product[$k]) ? intval($product[$k]['ExchangePoint']) : 0;
			$GivePoint = isset($product[$k]) ? intval($product[$k]['GivePoint']) : 0;
			$TotalExchangePoint += ($ExchangePoint*$n);
			$TotalGivePoint += ($GivePoint*$n);
		}
		if($type==1){ //ExchangePoint
			$maxPoint = $TotalExchangePoint;
		}elseif($type==2){ //GivePoint
			$maxPoint = $TotalGivePoint;
		}else{ //2者
			$maxPoint['ExchangePoint'] = $TotalExchangePoint;
			$maxPoint['GivePoint'] = $TotalGivePoint;
		}
		return $maxPoint;
		
		/* 以下算法存在bug，计算积分的时候，没有计算数量
		$where = get_language_where();
		$where .= ' and MemberID='.intval($MemberID);
		$lastSql = " from {$this->tablePrefix}info where InfoID in";
		$lastSql .= "(select ProductID from {$this->tablePrefix}cart where {$where})";
		if($type==1){
			$sql = "select sum(ExchangePoint) as ExchangePoint".$lastSql;
			$result = $this->query($sql);
			$maxPoint = 0;
			if($result && isset($result[0]['ExchangePoint'])){
				$maxPoint = intval($result[0]['ExchangePoint']);
			}
		}elseif($type==2){
			$sql = "select sum(GivePoint) as GivePoint".$lastSql;
			$result = $this->query($sql);
			$maxPoint = 0;
			if($result && isset($result[0]['GivePoint'])){
				$maxPoint = intval($result[0]['GivePoint']);
			}
		}else{ //2者
			$sql = "select sum(ExchangePoint) as ExchangePoint, sum(GivePoint) as GivePoint".$lastSql;
			$maxPoint = array('ExchangePoint'=>0, 'GivePoint'=>0);
			$result = $this->query($sql);
			if($result && isset($result[0])){
				$maxPoint = $result[0];
			}
		}
		return $maxPoint;
		*/
	}
	
	/**
	 * 保存订单(仅在ApiAction.class.php中调用)
	 * 
	 */
	function saveOrderProduct($OrderID, $MemberID=false){
        $OrderID = intval($OrderID);
		if( $OrderID <= 0 ) return false;
		$data = $this->getCart(false, $MemberID);
		if(empty($data)) return false;
		$product = array();
		$mt = D('Admin/TypeAttributeValue');
		foreach ($data as $v){
			$result = $mt->getAttributeByAttributeValueID( $v['AttributeValueID'] );
			$ProductPrice = ($v['ProductPrice'] + $result['TotalPrice']) * $GLOBALS['DiscountRate'];
			$product[] = array(
					'OrderID'=>$OrderID,
					'ProductID'=>$v['ProductID'],
					'ProductName'=>$v['ProductName'],
					'ProductPrice'=>$ProductPrice,
					'ProductQuantity'=>$v['ProductQuantity'],
					'AttributeValueID'=>$v['AttributeValueID'],
					'ProductAttribute'=>$result['AttributeString']
			);
		}
		//保存到数据库
		$m = D('Admin/OrderProduct');
		if( $m->addAll($product) ){
			return true;
		}else{
			return false;
		}
	}
}

<?php
/**
 * 购物车类库
 */
if (!defined('APP_NAME')) exit();
class YdCart{
	private $cookieName = 'y_shopping_cart'; //购物车cookie名称
	private $cookiePath = '/';  //购物车cookie存放路径
	private $cookieExpire = 2592000; //购物车cookie生存周期(单位:秒)，默认30天
	
	private static $_instance; //购物车使用单例模式实现
	private function __construct(){
		if( $this->isLogin() ){
			$data = cookie( $this->cookieName );
			$data = unserialize(stripslashes($data));
			//登录以后将cookie购物出写入数据库购物车
			if( $data ){
				$m = D('Admin/Cart');
				$this->clear(); //cookie数据添加到购物车前，先清除数据库中的数据
				$m->addAllCart($data);
				cookie($this->cookieName, null);
			}
		}
	}
	
	//静态方法，单例统一访问入口
	static public function getInstance() {
		if (is_null ( self::$_instance ) || isset ( self::$_instance )) {
			self::$_instance = new self ();
		}
		return self::$_instance;
	}
	
	/**
	 * 判断是否用户是否登录
	 */
	function isLogin(){
		return session('?MemberID');
	}
	
	/**
	 * 获取购物车所有数据，此函数被cartlist标签调用
	 * @param sring $cartName购物车名
	 * @return array
	 */
	function getAll(){
		if( $this->isLogin() ){
			$m = D('Admin/Cart');
			$data = $m->getCart();
		}else{
			$data = cookie( $this->cookieName );
			$data = unserialize(stripslashes($data));  //只有：CartID、ProductID、ProductQuantity、AttributeValueID
			//需要获取InfoTitle、InfoPrice、InfoPicture、Html、LinkUrl、ChannelID字段
			$m = D('Admin/Info');
			$data = $m->getCartInfo($data); //通过cookie数据获取购物数据
		}
		if(empty($data)) return false;
		
		//计算扩展字段
		$m1 = D('Admin/TypeAttributeValue');
		foreach ($data as $k=>$v){
			$result = $m1->getAttributeByAttributeValueID( $v['AttributeValueID'] );
			$ProductPrice = ($v['ProductPrice'] + $result['TotalPrice']) * $GLOBALS['DiscountRate'];
			//计算扩展字段，保留2位小数
			$data[$k]['ProductPrice'] = sprintf("%.2f", $ProductPrice);
			$data[$k]['TotalItemPrice'] = sprintf("%.2f", $v['ProductQuantity'] * $ProductPrice );
			$data[$k]['ProductAttributes'] = $result['Attributes'];
			$data[$k]['ProductUrl'] = InfoUrl($v['ProductID'], $v['Html'], $v['LinkUrl'],  false, $v['ChannelID']);
		}
		return $data;
	}
	
	/**
	 * 获取购物车所有产品数据
	 * @param sring $cartName购物车名
	 * @return array 二维数组的键是ProductID，便于判断产品是否存在
	 */
	function get(){
		if( $this->isLogin() ){
			$m = D('Admin/Cart');
			$data = $m->getCart();
		}else{
			$data = cookie( $this->cookieName );
			$data = unserialize(stripslashes($data));
		}
		return $data;
	}
	
	/**
	 * 设置购物车数据
	 * @param array $data 产品二维数据
	 */
	function set($data){
		if( !empty($data) ){
			if( $this->isLogin() ){ //已经登录，这里的语句永远不会运行
				$m = D('Admin/Cart');
				$data = $m->addAllCart( $data );
			}else{
				$p = array('expire'=>$this->cookieExpire, 'path'=>$this->cookiePath);
				cookie($this->cookieName, serialize($data), $p);
			}
		}else{
			$this->clear();
		}
	}
	
	//保存订购商品到数据库
	function save($OrderID){
		if( $OrderID <= 0 ) return false;
		$data = $this->get();
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
	
	/**
	 * 清空购物车
	 */
	function clear(){
		if( $this->isLogin() ){
			$m = D('Admin/Cart');
			$m->clearCart();
		}else{
			cookie($this->cookieName, null);
		}
	}
	
	/**
	 * 添加商品到购物车
	 * @param integer $id 商品ID(唯一)
	 * @param string $productName 商品名称
	 * @param integer $n 商品数量
	 * @param string $AttributeValueID 商品值ID，多个以逗号隔开，如：23,34,68
	 * @return boolean
	 */
	function add($id, $n = 1, $AttributeValueID=''){
		if( $this->isLogin() ){
			$m = D('Admin/Cart');
			$data = array('ProductID' => $id, 'ProductQuantity' => $n, 'AttributeValueID'=>$AttributeValueID);
			$m->addCart( $data );
		}else{
			$data = $this->get();
			$key = cart_make_cartid($data);
			//在已在public判断当前商品是否存在，所以这里无需判断
			$data [$key] = array ('CartID'=>$key, 'ProductID'=>$id, 'ProductQuantity'=>$n, 'AttributeValueID'=>$AttributeValueID);
			$this->set($data);
		}
		return true;
	}
	
	/**
	 * 设置商品数量
	 * @param int $id CartID
	 * @param int $n
	 * @param int $type 1: 设置，2：加1，3：减1
	 * @return boolean
	 */
	function setQuantity($id, $n=0, $type=1){
		if( !is_numeric($n) || !is_numeric($id) ){
			return false;
		}
		if( $this->isLogin() ){
			$m = D('Admin/Cart');
			$ProductQuantity = $m->setQuantity( $id, $n, $type );
		}else{
			$data = $this->get();
			if( empty($data) || !isset ( $data [$id] ) ){
				return false;
			}
			switch ($type){
				case 2:
					$data[$id]['ProductQuantity']++;
					break;
				case 3:
					$data[$id]['ProductQuantity']--;
					break;
				default:
					$data[$id]['ProductQuantity'] = $n;
			}
			//数量最小值等于1
			if( $data[$id]['ProductQuantity'] < 1){
				$data[$id]['ProductQuantity'] = 1;
			}
			$ProductQuantity = $data[$id]['ProductQuantity'];
			$this->set($data);
		}
		return $ProductQuantity;
	}
	
	//商品数量加1
	function incQuantity($id){
		return $this->setQuantity($id, 0, 2);
	}
	
	//商品数量减1
	function decQuantity($id){
		return $this->setQuantity($id, 0, 3);
	}
	
	/**
	 * 删除购物车中的某商品。注:当购物车中没有商品ID为$key的商品时,同样返回true
	 * @param integer $id 购物车cartid
	 * @return boolean
	 */
	function delete($id){
		if (! is_numeric($id)) {
			return false;
		}
		if( $this->isLogin() ){
			$m = D('Admin/Cart');
			$m->deleteCart($id);
		}else{
			$data = $this->get();
			if ($data && isset ( $data [$id] )) {
				unset ( $data [$id] );
				$this->set($data);
			}
		}
		return true;
	}

	/**
	 * 获取购物车内的总商种数(商品种类)
	 * @return integer
	 */
	public function getItemCount() {
		if( $this->isLogin() ){
			$m = D('Admin/Cart');
			$n = $m->getItemCount();
		}else{
			$data = $this->get();
			$n = empty($data) ? 0 : count ($data);
		}
		return $n;
	}
	
	/**
	 * 获取购物车商品总数
	 * 	 * $id 不为空，表示获取某个商品的数量
	 * @return integer
	 */
	public function getTotalCount($id=false, $valueid='') {
		$totalNum = 0;
		if( $this->isLogin() ){
			$m = D('Admin/Cart');
			$totalNum = $m->getTotalCount($id, $valueid);
		}else{
			$data = $this->get();
			if( is_numeric($id)){
				$totalNum = 0;  //获取数量
				foreach($data as $v){
					if ($v['ProductID'] == $id && $v['AttributeValueID'] == $valueid ) {
						$totalNum = $v['ProductQuantity'];
						break;
					}
				}
			}else{
				if ($data) {
					foreach ( $data as $lines ) {
						$totalNum += $lines['ProductQuantity'];
					}
				}
			}
		}
		return $totalNum;
	}
	
	/**
	 * 获取购物车总金额
	 * @param int $id CartID
	 * @param string $valueid 多个以逗号隔开
	 * @return double
	 */
	public function getTotalPrice($id=false) {
		$totalPrice = 0.0;
		if( $this->isLogin() ){
			$m = D('Admin/Cart');
			$totalPrice = $m->getTotalPrice($id);
		}else{
			$data = $this->get();
			if(empty($data)) return 0;
			$AttributePrice = 0;
			$m=D('Admin/Info');
			if( is_numeric($id)){
				$InfoID = $data[$id]['ProductID'];
				$ProductQuantity = $data[$id]['ProductQuantity'];
				$AttributeValueID = $data[$id]['AttributeValueID'];
				if(!empty($AttributeValueID)){
					$mt = D('Admin/TypeAttributeValue');
					$AttributePrice =  $mt->getAttributePriceByAttributeValueID( $AttributeValueID );
				}
				$InfoPrice = $m->where("InfoID=$InfoID")->getField('InfoPrice');
				$totalPrice = ($InfoPrice + $AttributePrice) * $ProductQuantity * $GLOBALS['DiscountRate'];
			}else{ //计算全部
				$idlist = '';
				foreach ($data as $v){
					$idlist .= $v['ProductID'].',';
				}
				$idlist = rtrim($idlist, ',');
				$result = $m->where("InfoID in($idlist)")->getField('InfoID,InfoPrice');
				if(empty($result)) return 0;
				$mt = D('Admin/TypeAttributeValue');
				foreach ($data as $k=>$v){
					$AttributePrice = 0;
					if( !empty($v['AttributeValueID']) ){
						$AttributePrice =  $mt->getAttributePriceByAttributeValueID( $v['AttributeValueID'] );
					}
					$totalPrice += ( $result[ $v['ProductID'] ] + $AttributePrice) * $v['ProductQuantity'] * $GLOBALS['DiscountRate'] ;
				}
			}
		}
		$totalPrice = sprintf("%.2f", $totalPrice);
		return $totalPrice;
	}

	/**
	 * 购物车中是存在$id的商品
	 * @param mixted $id 商品id
	 * @param mixted $valueid 商品规格值
	 * param mixted $data 当前购物车cookie数据
	 * @return boolean
	 */
	public function has($id, $valueid) {
		$b = false;
		if( $this->isLogin() ){
			$m = D('Admin/Cart');
			$b = $m->has($id, $valueid);
		}else{
			$data = $this->get();
			foreach($data as $v){
				if ($v['ProductID'] == $id && $v['AttributeValueID'] == $valueid ) {
					$b = true;
					break;
				}
			}
		}
		return $b;
	}
	
	/**
	 * 购物车是否有商品
	 */
	public function isEmpty(){
		$b = false;
		if( $this->isLogin() ){
			$m = D('Admin/Cart');
			$b = $m->isEmpty();
		}else{
			$data = $this->get();
			$b = empty($data) ? true : false;
		}
		return $b;
	}

	/**
	 * 设置购物车名
	 * @param sring $cartName购物车名
	 * @return $this
	 */
	public function setCookieName($name) {
		if (! $name) {
			return false;
		}
		$this->cookieName = trim ( $name );
		return $this;
	}
	
	/**
	 * 设置购物车cookie存放路径
	 * @param string $pathcookie路径
	 * @return $this
	 */
	public function setCookiePath($path) {
		if (! $path) {
			return false;
		}
		$this->cookiePath = trim ( $path );
		return $this;
	}
	
	/**
	 * 设置cookie有效周期
	 * @param integer $expire
	 * @return $this
	 */
	public function setCookieExpire($expire) {
		if (!is_numeric($expire) ) {
			return false;
		}
		$this->cookieExpire = ( int ) $expire;
		return $this;
	}
}

//生成唯一的CartID
function cart_make_cartid(&$data){
	$max = 0;
	foreach ($data as $k=>$v){
		if($k > $max) $max = $k;
	}
	$max++;
	return $max;
}
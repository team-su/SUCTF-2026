<?php
if (!defined('APP_NAME')) exit();
//支付公共函数 开始=========================================================
function pay_factory_create($type, $sitetype, $para=array() ){
	switch($type){
		case 10: //微信支付
			$obj = new YdWxPay($para);
			break;
		case 9: //网银在线
			break;
		case 8: //银联支付
			$obj = new YdUnionPay($para);
			break;
		case 7: //余额支付
			$obj = new YdBalancePay();
			break;
		case 6: //货到付款
			break;
		case 5: //银行汇款/转账、邮局汇款
			break;
		case 4: //paypal支付
			$obj = new YdPaypalPay($para);
			break;
		case 3: //财付通担保支付
			break;
		case 2: //财付通即时到账
			break;
		case 1: //支付宝（默认）
		default:
			//1：2者，2：电脑，3：手机
			if( $sitetype == 3 || ($sitetype == 1 && GROUP_NAME == 'Wap') ){
				$obj = new YdAlipayWap($para);
			}else{
				$obj = new YdAlipay($para);
			}
	}
	return $obj;
}

//支付异步通知Url地址
function pay_notify_url($class_name){
	$protocol = get_current_protocal();
	$url = $protocol.$_SERVER['HTTP_HOST'].__APP__;
	if( GROUP_NAME == 'Wap' ) {
		$domain = strtolower(get_wap_domain());
		$current = strtolower($_SERVER['HTTP_HOST']);
		if($domain != $current){
			$url .= '/wap';
		}
	}
	$url .= '/public/pay/type/notifyurl/classname/'.$class_name;
	return $url;
}

//支付同步通知Url地址
function pay_return_url($class_name){
	$protocol = get_current_protocal();
	$url = $protocol.$_SERVER['HTTP_HOST'].__APP__;
	if( GROUP_NAME == 'Wap' ) {
		$domain = strtolower(get_wap_domain());
		$current = strtolower($_SERVER['HTTP_HOST']);
		if($domain != $current){
			$url .= '/wap';
		}
	}
	$url .= '/public/pay/type/returnurl/classname/'.$class_name;
	return $url;
}

//用户付款中途退出返回商户的地址。需http://格式的完整路径，不允许加?id=123这类自定义参数
function pay_merchant_url(){
	$protocol = get_current_protocal();
	$url = $protocol.$_SERVER['HTTP_HOST'].__GROUP__;
	return $url;
}

/**
 * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
 * @param $para 需要拼接的数组
 * @param $urlEncode 对值进行urlencode编码
 * return 拼接完成以后的字符串
 */
function create_link_string($para, $urlEncode=true) {
	$arg  = '';
	foreach ($para as $key=>$val ) {
		if($urlEncode) $val = urlencode($val);
		$arg .= "{$key}={$val}&";
	}
	//去掉最后一个&字符
	$arg = substr($arg, 0, -1);

	//如果存在转义字符，那么去掉转义
	if(get_magic_quotes_gpc()){
		$arg = stripslashes($arg);
	}
	return $arg;
}
//支付公共函数 结束==========================================================
/*
 * 余额支付
 */
class YdBalancePay {
	function setConfig($para=array()){
		$MemberID = $para['MemberID'];
		$TotalOrderPrice = $para['TotalOrderPrice'];
		$OrderID = $para['OrderID'];
		
		$m = D('Admin/Cash');
		$AvailableBalance = $m->getAvailableQuantity($MemberID); //可用余额
		
		//返回值
		$para['PayTip'] = L('FriendTip');
		$para['PayContent'] = L('OrderOnlinePayFinish');
		$para['PayIcon'] = 1;
		if( $TotalOrderPrice > $AvailableBalance){ //余额不足
			$para['PayContent'] = L('NotEnoughBalance');
			$para['PayIcon'] = 2;
		}else{
			//从资本表里扣除费用
			$data['MemberID'] = $MemberID;
			$data['CashQuantity'] = 0-$TotalOrderPrice; //必须存储负数，表示消费
			$data['CashType'] = 2; //表示余额支付
			$data['CashStatus'] = 1;
			$data['CashTime'] = date('Y-m-d H:i:s');
			$data['PayID'] = intval($para['PayID']);
			$data['OrderID'] = $OrderID;
			$data['CashRemark'] = '订单号：'.$para['OrderNumber'];
			$b = $m->add($data);
			if($b){ //将订单状态重置为已支付状态
				$mo = D('Admin/Order');
				$mo->setPayStatus($OrderID, 1);
			}
		}
		return $para;
	}
}

/**
 * 支付宝手机支付
 */
class YdAlipayWap {
	var $config;  //支付宝配置关联数组
	//支付宝网关地址（新）
	var $alipay_gateway_new = 'http://wappaygw.alipay.com/service/rest.htm?';
	function __construct($para=array()){
		$this->setConfig($para);
	}
	
	function __destruct(){
		unset($this->config);
	}
	
	/**
	 * 设置参数
	 * @param array $para
	 */
	function setConfig($para=array()){
		$this->config = $para;
		$this->config['key'] = trim($para['AccountKey']);
	}
	
	/**
	 * 生成要请求给支付宝的参数数组
	 * @param $para_temp 请求前的参数数组
	 * @return 要请求的参数数组
	 */
	function getPayUrl() {
		//参数初始化 开始===============================================================================
		$partner = trim($this->config['AccountID']); //合作身份者id，以2088开头的16位纯数字
		$key =  trim($this->config['AccountKey']);
		$this->config['key'] = $key;
		$sign_type = 'MD5';
		
		$format = 'xml';
		$v = '2.0';
		$req_id = date('Ymdhis');
		$_input_charset = 'utf-8';

		$seller_email = trim($this->config['AccountName']);
		$out_trade_no = $this->config['OrderNumber'];   //String(64)商户订单号，必须唯一
		$subject = $this->config['OrderNumber'];            //String(256),商品的标题/交易标题/订单标题/订单关键字等
		$total_fee = $this->config['TotalOrderPrice'];
		
		$merchant_url = pay_merchant_url();  //用户付款中途退出返回商户的地址
		$notify_url = isset($this->config['NotifyUrl']) ? $this->config['NotifyUrl'] : pay_notify_url(__CLASS__ );              //服务器异步通知页面路径
		$call_back_url = isset($this->config['ReturnUrl']) ? $this->config['ReturnUrl'] : pay_return_url(__CLASS__ );        //页面跳转同步通知页面路径
		
		$req_data = "<direct_trade_create_req>";
		$req_data .= "<notify_url>{$notify_url}</notify_url>";
		$req_data .= "<call_back_url>{$call_back_url}</call_back_url>";
		$req_data .= "<seller_account_name>{$seller_email}</seller_account_name>";
		$req_data .= "<out_trade_no>{$out_trade_no}</out_trade_no>";
		$req_data .= "<subject>{$subject}</subject>";
		$req_data .= "<total_fee>{$total_fee}</total_fee>";
		$req_data .= "<merchant_url>{$merchant_url}</merchant_url>";
		$req_data .= "</direct_trade_create_req>";
		//参数初始化 结束===============================================================================
		
		//获取授权Token
		$para_token = array(
			'service' => 'alipay.wap.trade.create.direct',
			"format"	=> $format,
			"v"	=> $v,
			'partner' => $partner,
			"req_id"	=> $req_id,
			'sec_id'  => $sign_type,
			'_input_charset'=> $_input_charset, //字符编码格式 目前支持 gbk 或 utf-8
			'req_data'=>$req_data,
    	);
		$para_temp = $this->filterPara($para_token);
		//2.对待签名参数数组排序
		ksort($para_temp); reset($para_temp);
		//3.生成签名结果，签名结果与签名方式加入请求提交参数组中
		$para_temp['sign'] = $this->buildSign($para_temp);
		$html_text = urldecode(yd_curl_post($this->alipay_gateway_new, $para_temp, 30));
		$para_html_text = $this->parseResponse($html_text);
		$request_token = $para_html_text['request_token']; //获取request_token
		
		//构造要请求的参数数组，无需改动
		$para = array(
				"service" => "alipay.wap.auth.authAndExecute",
				"format"	=> $format,
				"v"	=> $v,
				"partner" => $partner,
				
				"req_id"	=> $req_id,
				"sec_id" => $sign_type,
				"_input_charset"	=> $_input_charset,
				"req_data"	=> '<auth_and_execute_req><request_token>' . $request_token . '</request_token></auth_and_execute_req>',
				
		);
		$para = $this->filterPara($para);
		ksort($para); reset($para);
		$para['sign'] = $this->buildSign($para);
		$urlPara = create_link_string($para);
		$payUrl = $this->alipay_gateway_new.$urlPara;
		return $payUrl;
	}
	
	private function parseResponse($str_text) {
		//以“&”字符切割字符串
		$para_split = explode('&',$str_text);
		//把切割后的字符串数组变成变量与数值组合的数组
		foreach ($para_split as $item) {
			//获得第一个=字符的位置
			$nPos = strpos($item,'=');
			//获得字符串长度
			$nLen = strlen($item);
			//获得变量名
			$key = substr($item,0,$nPos);
			//获得数值
			$value = substr($item,$nPos+1,$nLen-$nPos-1);
			//放入数组中
			$para_text[$key] = $value;
		}
	
		if( ! empty ($para_text['res_data'])) {
			//token从res_data中解析出来（也就是说res_data中已经包含token的内容）
			$doc = new DOMDocument();
			$doc->loadXML($para_text['res_data']);
			$para_text['request_token'] = $doc->getElementsByTagName( "request_token" )->item(0)->nodeValue;
		}
		return $para_text;
	}
	
	/**
	 * 生成签名结果
	 * @param $para_sort 已排序要签名的数组
	 * return 签名结果字符串
	 */
	private function buildSign($para) {
		//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$str = create_link_string($para, false); //计算签名参数不进行urlencode编码
		$sign = md5($str.$this->config['key']);
		return $sign;
	}
	
	//验证签名
	function verifySign($data){
		if( empty($data)) return false;
		$sign1 = $data['sign']; //支付服务器传入的签名
		
		//排序是固定的
		$temp['service'] = $data['service'];
		$temp['v'] = $data['v'];
		$temp['sec_id'] = $data['sec_id'];
		$temp['notify_data'] = $data['notify_data'];
		$sign2 = $this->buildSign($temp);
		
		if( $sign1==$sign2){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * 除去数组中的空值和签名参数
	 * @param $para 签名参数组
	 * return 去掉空值与签名参数后的新签名参数组
	 */
	private function filterPara($para) {
		$para_filter = array();
		foreach ($para as $key=>$val) {
			if($key == 'sign' || $key == 'sign_type' || $key=='key' || $val === '') {
				continue;
			}else{
				$para_filter[$key] = $para[$key];
			}
		}
		return $para_filter;
	}
	
	/**
	 * 同步返回处理，需要返回数据
	 */
	public function returnurl(){
		$m = D('Admin/Order');
		$OrderNumber = $_GET['out_trade_no'];  //获取订单号
		if( !is_numeric($OrderNumber)) return false;
		$data = $m->findOrder($OrderNumber);
		return $data;
	}
	
	/**
	 * 异步返回处理，不需要返回数据
	 */
	public function notifyurl(){
		//POST键包含有：service,v,sec_id,sign,notify_data(xml数据)
		$t = (array) simplexml_load_string($_POST['notify_data']);
		$OrderNumber = $t['out_trade_no'];
		if( substr($OrderNumber, 0, 4) == 'ZXCZ' && false !== strpos($OrderNumber, '_') ){ //会员充值
			$m = D('Admin/Cash');
			$data = $m->getPayParams($OrderNumber);
			if(false === $data) exit('fail');
			if(true === $data) exit('success');
			$this->setConfig( $data );  //设置参数
			$isVerified = $this->verifySign($_POST); //验证签名是否正确
			if($isVerified){
				if ($t['trade_status'] == 'TRADE_FINISHED' || $t['trade_status'] == 'TRADE_SUCCESS') {
					$m->setPayStatus($OrderNumber); //设置为已支付状态
					exit('success');
				}
			}
		}else{
			$m = D('Admin/Order');
			$data = $m->findOrder( $OrderNumber );
			$this->setConfig( $data );  //设置参数
			$isVerified = $this->verifySign($_POST);
			if( $isVerified ){
				if ($t['trade_status'] == 'TRADE_FINISHED' || $t['trade_status'] == 'TRADE_SUCCESS') {
					$m->setOrder($data['OrderID'], 2, 1); //已付款，已支付
					exit('success');
				}
			}
		}
		exit('fail');
	}
}

/**
 * 支付宝类
 */
class YdAlipay {
	var $config;  //支付宝配置关联数组
	//支付宝网关地址（新）
	var $alipay_gateway_new = 'https://mapi.alipay.com/gateway.do?';
	function __construct($para=array()){
		$this->setConfig($para);
	}
	
	function __destruct(){
		unset($this->config);
	}
	
	/**
	 * 设置参数
	 * @param array $para
	 */
	function setConfig($para=array()){
		if(empty($para)) return;
		switch ($para['PayInterface']){
			case '3': //标准双接口
				$service = 'create_partner_trade_by_buyer';
				break;
			case '2': //标准双接口
				$service = 'trade_create_by_buyer';
				break;
			case '1':  //即时到账接口（默认值）
			default:
				$service = 'create_direct_pay_by_user';
				break;
		}
		
		$seller_email = trim($para['AccountName']); //支付宝帐户
		$key = trim($para['AccountKey']);        //交易安全校验码
		$partner = trim($para['AccountID']);  //合作者身份ID
		
		$this->config = array(
    		'partner' => $partner,  //合作身份者id，以2088开头的16位纯数字
    		'key' => $key, //安全检验码，以数字和字母组成的32位字符
    		'sign_type'  => 'MD5',     //签名方式 DSA、RSA、MD5，必须为大写
    		'_input_charset'=> 'utf-8', //字符编码格式 目前支持 gbk 或 utf-8
    		//'cacert'=> '',   //getcwd().'\\cacert.pem'; ca证书路径地址，用于curl中ssl校验，请保证cacert.pem文件在当前文件夹目录中
    		//'transport' => 'http', //访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
    		
    		'payment_type' => "1",  //收款类型 , 默认为1：商品购买，4：捐赠，47：电子卡卷
    		'seller_email' => $seller_email,      //卖家支付宝帐户
    		'out_trade_no' => $para['OrderNumber'],   //String(64)商户订单号，必须唯一
    		'service' => $service,
    		'notify_url' => isset($para['NotifyUrl']) ? $para['NotifyUrl'] : pay_notify_url(__CLASS__),   //服务器异步通知页面路径
    		'return_url' => isset($para['ReturnUrl']) ? $para['ReturnUrl'] : pay_return_url(__CLASS__),  //页面跳转同步通知页面路径
    		
    		'subject' => $para['OrderNumber'],  //String(256),商品的标题/交易标题/订单标题/订单关键字等
    		'total_fee' => $para['TotalOrderPrice'],
    		'body' => "",  //String(1000), 商品描述，可空
    		'show_url' => "",
    		'anti_phishing_key' => "",
    		'exter_invoke_ip' => "",
    	);
	}
	
	/**
	 * 生成要请求给支付宝的参数数组
	 * @param $para_temp 请求前的参数数组
	 * @return 要请求的参数数组
	 */
	function getPayUrl() {
		$para_temp = $this->config;
		if( empty( $para_temp ) ) return '';
		//1.除去待签名参数数组中的空值和签名参数
		$para_temp = $this->filterPara($para_temp);
		//2.对待签名参数数组排序
		ksort($para_temp); reset($para_temp);
		//3.生成签名结果，签名结果与签名方式加入请求提交参数组中
		$para_temp['sign'] = $this->buildSign($para_temp);
		$para_temp['sign_type'] = $this->config['sign_type'];
		$urlPara = create_link_string($para_temp);
		$payUrl = $this->alipay_gateway_new.$urlPara;
		return $payUrl;
	}
	
	/**
	 * 生成签名结果
	 * @param $para_sort 已排序要签名的数组
	 * return 签名结果字符串
	 */
	private function buildSign($para) {
		//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$str = create_link_string($para, false); //计算签名参数不进行urlencode编码
		switch (strtoupper(trim($this->config['sign_type']))) {
			case 'MD5' :
				$sign = md5($str.$this->config['key']);
				break;
			default :
				$sign = '';
		}
		return $sign;
	}
	
	/**
	 * 验证签名
	 * @param array $data
	 * @return boolean 返回签名的字符串
	 */
	function verifySign($data){
		if( empty($data)) return false;
		$sign1 = $data['sign']; //支付服务器传入的签名
		$temp = $this->filterPara($data);
		ksort($temp); reset($temp);
		$sign2 = $this->buildSign($temp);
		if( $sign1==$sign2){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * 除去数组中的空值和签名参数
	 * @param $para 签名参数组
	 * return 去掉空值与签名参数后的新签名参数组
	 */
	private function filterPara($para) {
		$para_filter = array();
		foreach ($para as $key=>$val) {
			if($key == 'sign' || $key == 'sign_type' || $key=='key' || $val === '') {
				continue;
			}else{
				$para_filter[$key] = $para[$key];
			}
		}
		return $para_filter;
	}

	/**
	 * 同步返回处理
	 */
	public function returnurl(){
		$OrderNumber = $_GET['out_trade_no'];  //获取订单号
		if( !is_numeric($OrderNumber)) return false;
		$m = D('Admin/Order');
		$data = $m->findOrder($OrderNumber);
		return $data;
	}
	
	/**
	 * 异步返回处理
	 */
	public function notifyurl(){
		$m = D('Admin/Order');
		$OrderNumber = $_POST['out_trade_no'];  //获取订单号
		if( substr($OrderNumber, 0, 4) == 'ZXCZ' && false !== strpos($OrderNumber, '_') ){ //会员充值
			$m = D('Admin/Cash');
			$data = $m->getPayParams($OrderNumber);
			if(false === $data) exit('fail');
			if(true === $data) exit('success');
			$this->setConfig( $data );  //设置参数
			$isVerified = $this->verifySign($_POST); //验证签名是否正确
			if($isVerified){
				if( $_POST['trade_status'] == 'TRADE_FINISHED' || $_POST['trade_status'] == 'TRADE_SUCCESS' ){
					$m->setPayStatus($OrderNumber); //设置为已支付状态
					exit('success');
				}
			}
		}else{
			$data = $m->findOrder($OrderNumber);
			//判断消费金额是否相等
			//if(empty($data) || $data['TotalPrice'] != $_POST['total_fee'] ) exit('fail');
			//如果是已经支付，就直接返回
			if( $data['PayStatus'] == 1 ) exit('success');
			$this->setConfig( $data );  //设置参数
			$isVerified = $this->verifySign($_POST);  //验证签名是否正确
			if( $isVerified) {  //签名验证成功
				if( $_POST['trade_status'] == 'TRADE_FINISHED' || $_POST['trade_status'] == 'TRADE_SUCCESS' ){
					$m->setOrder($data['OrderID'], 2, 1); //已处理，已支付
					exit('success');
				}
			}
		}
		exit('fail');
	}
}

/**
 * 微信扫码支付（实现模式2）
 */
class YdWxPay {
	var $config;  //配置关联数组
	var $unifiedorder_url= 'https://api.mch.weixin.qq.com/pay/unifiedorder'; //下单接口
	var $type; //交易类型，取值如下：JSAPI，NATIVE，APP
	var $openid; //微信公众号支付时，回调code（有效期：5分钟）
	function __construct($para=array()){
		$this->type = stripos($_SERVER['HTTP_USER_AGENT'],"MicroMessenger") ? 'JSAPI' : 'NATIVE';
		$this->setConfig($para);
	}

	function __destruct(){
		unset($this->config);
	}
	
	function getType(){
		return $this->type;
	}

	/**
	 * 设置参数
	 * @param array $para
	 */
	function setConfig($para=array()){
		if(empty($para)) return;
		$this->config = array(
				'appid' => trim($para['AccountID']),       //公众账号ID
				'mch_id'=>trim($para['AccountName']), //商户号
				'key'=>trim($para['AccountKey']), //秘钥
				'appsecret'=>trim($para['AccountPassword']),
				
				'body'=>$para['OrderNumber'],  //商品或支付单简要描述
				'out_trade_no' => $para['OrderNumber'],   //商户系统内部的订单号,32个字符内
				'total_fee' => intval($para['TotalOrderPrice']*100), //订单总金额，单位为分，不能有小数
				'product_id'=>$para['OrderNumber'], //trade_type=NATIVE 此参数必传。此id为二维码中包含的商品ID，商户自行定义。
				
				'trade_type'=>$this->type,  //交易类型，取值如下：JSAPI，NATIVE，APP
				'spbill_create_ip'=>get_client_ip(),  //终端IP
				'nonce_str'=>rand_string(32), //随机字符串，不长于32位
				'notify_url' => isset($para['NotifyUrl']) ? $para['NotifyUrl'] : pay_notify_url(__CLASS__),   //接收微信支付异步通知回调地址，通知url必须为直接可访问的url，不能携带参数。
		);
	}

	/**
	 * 生成要请求给微信的参数数组
	 * @param $para_temp 请求前的参数数组
	 * @return 要请求的参数数组
	 */
	function getPayUrl() {
		$para_temp = $this->config;
		if( empty( $para_temp ) || $para_temp['total_fee'] <= 0 ) return false;
		if( $this->type == 'JSAPI'){
			$para_temp['openid'] = $this->openid;
			if(empty( $para_temp['openid']) ) {
				if( APP_DEBUG ) WriteErrLog( "无法获取微信回调code" );
				return false;
			}
		}
	    $para_temp = $this->filterPara($para_temp);
		ksort($para_temp); reset($para_temp);
		$para_temp['sign'] = $this->buildSign($para_temp);
		$xml = $this->toXml($para_temp);
		$response = yd_curl_post($this->unifiedorder_url, $xml, 30);
		if( strlen($response) < 1) {
			if( APP_DEBUG ) WriteErrLog( "{$this->type} POST下单接口 获取数据失败" );
			return false;
		}
		$data = $this->fromXml($response);
		//返回状态码 return_code：此字段是通信标识，非交易标识，交易是否成功需要查看result_code来判断
		//业务结果 result_code 是 String(16) SUCCESS SUCCESS/FAIL
		if( $data['return_code'] == 'FAIL' ||  $data['result_code'] == 'FAIL') {
			if( APP_DEBUG ) WriteErrLog( "{$this->type}下单异常。{$data['return_msg']}" );
			return false;
		}
		$b = $this->verifySign($data);
		if(!$b) {
			if( APP_DEBUG ) WriteErrLog( "{$this->type}验证签名失败" );
			return false;
		}
		
		if( $this->type == 'JSAPI'){
			$api['appId'] = $data['appid'];
			$api['timeStamp'] = strval(time()); //转化为字符串，兼容性更好，否则在苹果手机无法支付
			$api['nonceStr'] = rand_string(32);
			$api['package'] = 'prepay_id='.$data['prepay_id'];
			$api['signType'] = 'MD5';
			$api = $this->filterPara($api);
			ksort($api); reset($api);
			$api['paySign'] = $this->buildSign($api);
			$payUrl = json_encode($api);
		}else{
			//weixin：//wxpay/s/An4baqw trade_type 为NATIVE是有返回，可将该参数值生成二维码展示出来进行扫码支付
			$payUrl = $data['code_url'];
		}
		return $payUrl;
	}
	
	
	public function getCodeUrl($appid, $redirectUrl=false){
		//获取当前页面完整url
		$protocol = get_current_protocal();
		//注意：这里必须区分大小写，$_SERVER['PHP_SELF']默认会生成大写的模块名称，和微信支付设置的授权目录会不一致
		//$redirectUrl = $protocol.strtolower($_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']).'?'.$_SERVER['QUERY_STRING'];
		if(empty($redirectUrl)){
			$redirectUrl = WeixinPayDir().'pay?'.$_SERVER['QUERY_STRING'];
			$redirectUrl = urlencode($redirectUrl);
		}
		//$p参数顺序不能有错
		$p["appid"] = $appid;
		$p["redirect_uri"] = $redirectUrl;
		$p["response_type"] = "code";
		$p["scope"] = "snsapi_base";
		$p["state"] = "STATE"."#wechat_redirect";
		$str = create_link_string($p, false);
		$url = "https://open.weixin.qq.com/connect/oauth2/authorize?".$str;
		return $url;
	}
	
	/**
	 * 通过code获取openid
	 * @param string $code
	 */
	public function getOpenidByCode($code, $appid, $appsecret){
		if( empty($code) || empty($appid) || empty($appsecret) ) return false;
		$p["appid"] = $appid;
		$p["secret"] =  $appsecret;
		$p["code"] = $code;
		$p["grant_type"] = "authorization_code";
		$str = create_link_string($p, false);
		//获取access_token的同时，也获取了openid
		$url = "https://api.weixin.qq.com/sns/oauth2/access_token?".$str;
		//初始化curl
		$ch = curl_init();
		//设置超时
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		//运行curl，结果以jason形式返回
		$res = curl_exec($ch);
		curl_close($ch);
		//取出openid
		$data = json_decode($res, true);
		$openid = false;
		if( isset( $data['errcode'] ) ){
			if( APP_DEBUG ) WriteErrLog("获取openid异常：".$data['errmsg']);
		}else{
			$openid = $data['openid'];
		}
		return $openid;
	}
	
	/**
	 * 将参数转换为xml格式
	 * @param array $para 参数数组
	 */
	private function toXml($para){
		$xml = "<xml>";
		foreach ($para as $key=>$val){
			if (is_numeric($val)){
				$xml.="<".$key.">".$val."</".$key.">";
			}else{
				$xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
			}
		}
		$xml.="</xml>";
		return $xml;
	}
	
	/**
	 * 将xml转为array
	 * @param string $xml
	 */
	private function fromXml($xml){
		//禁止引用外部xml实体，部分服务器禁止了此函数
		if( function_exists('libxml_disable_entity_loader') ){
			//by wang 2020-11-15  禁止加载外部实体，导致无法加载xml模板配置
			//libxml_disable_entity_loader(true);
		}
		$data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
		return $data;
	}

	/**
	 * 生成签名结果
	 * @param $para_sort 已排序要签名的数组
	 * return 签名结果字符串
	 */
	private function buildSign($para) {
		//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$str = create_link_string($para, false); //计算签名参数不进行urlencode编码
		$str = $str.'&key='.$this->config['key'];
		$sign = strtoupper(md5($str));
		return $sign;
	}

	/**
	 * 验证签名
	 * @param array $data
	 * @return boolean 返回签名的字符串
	 */
	function verifySign($data){
		if( empty($data)) return false;
		$sign1 = $data['sign']; //支付服务器传入的签名
	    $temp = $this->filterPara($data);
		ksort($temp); reset($temp);
		$sign2 = $this->buildSign($temp);
		if( $sign1==$sign2){
			return true;
		}else{
			return false;
		}
	}

	/**
	 * 除去数组中的空值和签名参数
	 * @param $para 签名参数组
	 * return 去掉空值与签名参数后的新签名参数组
	 */
	private function filterPara($para) {
		$para_filter = array();
		foreach ($para as $key=>$val) {
			if($key == 'sign' || $key=='appsecret' || is_array($val) || $key=='key' || $val === '') {
				continue;
			}else{
				$para_filter[$key] = $para[$key];
			}
		}
		return $para_filter;
	}

	/**
	 * 同步返回处理
	 */
	public function returnurl(){
		return false;
	}

	/**
	 * 异步返回处理
	 */
	public function notifyurl(){
		//获取通知的数据
		//$xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        $xml = file_get_contents("php://input");
		if( APP_DEBUG ) WriteErrLog( "微信支付异步通知。".htmlentities($xml) );
		$xml = $this->fromXml($xml);  //转化为数组
		$SuccessXml = "<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>";
		$FailXml = "<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>";
		if($xml['return_code'] == 'SUCCESS' &&  $xml['result_code'] == 'SUCCESS'){
			$OrderNumber = $xml['out_trade_no'];  //获取订单号
			if( substr($OrderNumber, 0, 4) == 'ZXCZ' && false !== strpos($OrderNumber, '_') ){ //会员充值
				$m = D('Admin/Cash');
				$data = $m->getPayParams($OrderNumber);
				if(false === $data) exit($FailXml);
				if(true === $data) exit($SuccessXml);
				$this->setConfig( $data );  //设置参数
				$b = $this->verifySign($xml); //验证签名是否正确
				if($b){
					$m->setPayStatus($OrderNumber); //设置为已支付状态
					exit($SuccessXml);
				}
			}else{ //购物车订单支付
				$m = D('Admin/Order');
				$data = $m->findOrder($OrderNumber);
				//如果已经支付，直接返回成功
				if( $data['PayStatus'] == 1 ) exit($SuccessXml);
				$this->setConfig( $data );  //设置参数
				$b = $this->verifySign($xml); //验证签名是否正确
				if($b){
					$m->setOrder($data['OrderID'], 2, 1); //已处理，已支付
					exit($SuccessXml);
				}
			}
		}
		exit($FailXml);
	}
}

/**
 * 贝宝标准支付
 */
class YdPaypalPay {
	var $config;  //配置关联数组
	var $url= '';
	var $ipn_url = '';
	var $use_sandbox = 0;  //是否是沙盒模式，用于测试
	function __construct($para=array()){
		$this->setConfig($para);
		if($this->use_sandbox == 1){
			$this->url= 'https://www.sandbox.paypal.com/cgi-bin/webscr';
			$this->ipn_url = 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr';
		}else{
			$this->url= 'https://www.paypal.com/cgi-bin/webscr';
			$this->ipn_url = 'https://ipnpb.paypal.com/cgi-bin/webscr';
		}
	}

	function __destruct(){
		unset($this->config);
	}
	
	/**
	 * 设置参数
	 * @param array $para
	 */
	function setConfig($para=array()){
		if(empty($para)) return;
		//用于异步验证
		$sign = md5($para['OrderID'].$para['OrderNumber'].$para['AccountKey']);
		$this->config = array(
				'business'=>trim($para['AccountName']), //商户号
				'currency_code'=>$para['PayCurrency'], //交易货币
				
				'item_name'=>$para['OrderNumber'],  //商品名称，可以传入订单编号
				'item_number' => $para['OrderID'],   //商品编号，可以传入订单ID
				'amount' => $para['TotalOrderPrice'], //订单总金额
				'custom' => $sign, //存储签名
				
				'notify_url' => isset($para['NotifyUrl']) ? $para['NotifyUrl'] : pay_notify_url(__CLASS__),
				//'return' => isset($para['ReturnUrl']) ? $para['ReturnUrl'] : pay_return_url(__CLASS__),
				'return'=>'',  //设置为空，支付完成后就没有提示返回商家的按钮
				
				'cmd'=>'_xclick', //_xclick:立即支付
				'rm'=>'2', //定义IPN的返回方式，2代表post
				'no_shipping'=>'1', //送货地址。1：不要求提供送货地址；如果省略或设为 "0"，将提示输入送货地址
				'no_note'=>'1', //为付款加入提示。1：不提示输入提示；如果省略或设为 "0"，将提示输入提示
		);
	}
	
	function getPayUrl() {
		$getParams = http_build_query($this->config);
		$url = $this->url.'?'.$getParams;
		return $url;
	}
	
	/**
	 * 同步返回处理
	 */
	public function returnurl(){
		$OrderNumber = $_REQUEST['item_number'];  //OrderID
		$m = D('Admin/Order');
		$data = $m->findOrder($OrderNumber);
		return $data;
	}
	
	/**
	 * 异步返回处理
	 */
	public function notifyurl(){
		/* POST的数据如下：
		 transaction_subject=&payment_date=21%3A13%3A29+May+17%2C+2017+PDT
		&txn_type=web_accept&last_name=buyer&residence_country=CN
		&item_name=XX201705181213363774&payment_gross=16.78&mc_currency=USD
		
		&business=34137592-facilitator%40qq.com&payment_type=instant&protection_eligibility=Eligible
		&verify_sign=AC4L.om9RdvsJ275OO1P6hHeLVBHAqTd4ZRtZ9CRo2ne7sWxlAPwbKPi
		&payer_status=verified&test_ipn=1&payer_email=34137592-buyer%40qq.com
		&txn_id=8AG81436NY386625K
		
		&quantity=1&receiver_email=34137592-facilitator%40qq.com&first_name=test
		&payer_id=RLEVLHCZVZC42&receiver_id=MMR9MSND2MCLL&item_number=142
		&payment_status=Completed&payment_fee=0.87&mc_fee=0.87&mc_gross=16.78
		&custom=&charset=gb2312&notify_version=3.8&ipn_track_id=bb38d91034695
		 */
		/*
		$b = $this->validateIpn();
		if(!$b)return false;
		$m = D('Admin/Order');
		$OrderNumber = $_REQUEST['item_number'];  //获取OrderID
		$data = $m->findOrder($OrderNumber);
		//如果是已经支付，就直接返回
		if( $data['PayStatus'] == 1 ) return true;
		$m->setOrder($data['OrderID'], 2, 1); //已处理，已支付
		return true;
		*/
		//todo：palpay支付校验签名还需要优化
		$m = D('Admin/Order');
		$OrderNumber = intval($_REQUEST['item_number']);  //获取OrderID
		$data = $m->findOrder($OrderNumber);
		if(empty($data['AccountKey'])) {
		    return false;
        }
		//如果是已经支付，就直接返回
		if( $data['PayStatus'] == 1 ) return true;
		$sign = md5($_REQUEST['item_number'].$_REQUEST['item_name'].$data['AccountKey']);
		if($sign == $_REQUEST['custom']){
			$m->setOrder($data['OrderID'], 2, 1); //已处理，已支付
		}
		return true;
	}
	
	/**
	 * 验证IPN
	 */
	private function validateIpn(){
		return true;
		$data = "cmd=_notify-validate";
		$get_magic_quotes_exists = false;
		if (function_exists('get_magic_quotes_gpc')) {
			$get_magic_quotes_exists = true;
		}
		foreach ($_POST as $key => $value) {
			if ($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
				$value = urlencode(stripslashes($value));
			}else{
				$value = urlencode($value);
			}
			$data .= "&$key=$value";
		}
		
		$ch = curl_init($this->ipn_url);
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt($ch, CURLOPT_SSLVERSION, 6);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		
		curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 50);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
		$res = curl_exec($ch);
		if (!$res) {
			$errno = curl_errno($ch);
			$errstr = curl_error($ch);
			curl_close($ch);
			//提示：[35]error:14077410:SSL routines:SSL23_GET_SERVER_HELLO:sslv3 alert handshake failure
			//file_put_contents("./6.txt", "error:[$errno]$errstr");
			return false;
		}
		$info = curl_getinfo($ch);
		$http_code = $info['http_code'];
		//file_put_contents("./6.txt", "code:$http_code, res:$res");
		
		if ($http_code != 200) {
			curl_close($ch);
			return false;
		}
		curl_close($ch);
		
		if ($res == 'VERIFIED') {
			return true;
		} else {
			return false;
		}
		
		/*
		$parse = parse_url($this->ipn_url);
		$header = "POST ".$parse['path']." HTTP/1.1\r\n";
		$header .= "Host: ".$parse['host']."\r\n";
		$header .= "Content-Type:application/x-www-form-urlencoded\r\n";
		$header .= "Content-Length:".strlen($data)."\r\n\r\n";
		$fp = fsockopen($parse['host'], 80, $errnum, $errstr, 60);
		if(!$fp) return false;
		fputs($fp, $header.$data);
		
		//loop through the response from the server and append to variable
		$response = '';
		while(!feof($fp)){
			$response .= fgets($fp, 1024);
		}
		fclose($fp);
		
		file_put_contents("./6.txt", $header."==============\r\n".$response);
		if ( false !== strpos($response, 'VERIFIED') ) {
			return true;
		}else{
			return false;
		}
		*/
	}
}

/**
 * 小程序支付
 */
class YdXcxPay {
	var $config;  //配置关联数组
	var $unifiedorder_url= 'https://api.mch.weixin.qq.com/pay/unifiedorder'; //下单接口
	function __construct($para=array()){
		$this->setConfig($para);
	}

	function __destruct(){
		unset($this->config);
	}

	/**
	 * 设置参数
	 * @param array $para
	 */
	function setConfig($para=array()){
		if(empty($para)) return;
		$this->config = array(
				'appid' => trim($para['AccountID']),       //公众账号ID
				'key' => trim($para['AccountKey']),       //支付秘钥
				'mch_id'=>trim($para['AccountName']), //商户号
				
				'nonce_str'=>rand_string(32, 11),
				'sign_type'=>'MD5',

				'fee_type'=>'CNY',  //默认人民币：CNY
				'body'=>$para['OrderNumber'],  //商品或支付单简要描述
				'out_trade_no' => $para['OrderNumber'],   //商户系统内部的订单号,32个字符内
				'total_fee' => intval($para['TotalOrderPrice']*100), //订单总金额，单位为分，不能有小数
				'openid'=>$para['OpenID'], //trade_type=JSAPI 此参数必传

				'trade_type'=>'JSAPI',  //交易类型，小程序必须为JSAPI
				'spbill_create_ip'=>get_client_ip(),  //终端IP
				'notify_url' => isset($para['NotifyUrl']) ? $para['NotifyUrl'] : pay_notify_url(__CLASS__),   //接收微信支付异步通知回调地址，通知url必须为直接可访问的url，不能携带参数。
		);
	}

	/**
	 * 生成要请求给微信的参数数组
	 * @param $para_temp 请求前的参数数组
	 * @return 要请求的参数数组
	 */
	function getPayUrl() {
		$para_temp = $this->config;
		if( empty( $para_temp ) || $para_temp['total_fee'] <= 0 ) return false;
		
		$para_temp = $this->filterPara($para_temp);
		ksort($para_temp); reset($para_temp);
		$para_temp['sign'] = $this->buildSign($para_temp);
		$xml = $this->toXml($para_temp);
		$response = yd_curl_post($this->unifiedorder_url, $xml, 30);
		if( strlen($response) < 1) {
			if( APP_DEBUG ) WriteErrLog( "{$this->type} POST下单接口 获取数据失败" );
			return false;
		}
		$data = $this->fromXml($response);
		//返回状态码 return_code：此字段是通信标识，非交易标识，交易是否成功需要查看result_code来判断
		//业务结果 result_code 是 String(16) SUCCESS SUCCESS/FAIL
		if( $data['return_code'] == 'FAIL' ||  $data['result_code'] == 'FAIL') {
			if( APP_DEBUG ) WriteErrLog( "{$this->type}下单异常。{$data['return_msg']}" );
			return false;
		}
		$b = $this->verifySign($data);
		if(!$b) {
			if( APP_DEBUG ) WriteErrLog( "{$this->type}验证签名失败" );
			return false;
		}
		
		if(empty($data['prepay_id'])){
			return false;
		}

		//小程序支付接口参数
		$api['appId'] = $para_temp['appid'];
		$api['nonceStr'] = rand_string(32);
		$api['package'] = "prepay_id=".$data['prepay_id'];
		$api['signType'] = 'MD5';
		$api['timeStamp'] = time();
		$api['paySign'] = $this->buildSign($api);
		unset($api['appId']);
		return $api;
	}

	/**
	 * 将参数转换为xml格式
	 * @param array $para 参数数组
	 */
	private function toXml($para){
		$xml = "<xml>";
		foreach ($para as $key=>$val){
			if (is_numeric($val)){
				$xml.="<".$key.">".$val."</".$key.">";
			}else{
				$xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
			}
		}
		$xml.="</xml>";
		return $xml;
	}

	/**
	 * 将xml转为array
	 * @param string $xml
	 */
	private function fromXml($xml){
		//禁止引用外部xml实体，防止xml注入攻击，但有些虚拟主机商不支持这个函数
		if( function_exists('libxml_disable_entity_loader') ){
			//by wang 2020-11-15  禁止加载外部实体，导致无法加载xml模板配置
			//libxml_disable_entity_loader(true);
		}
		$data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
		return $data;
	}

	/**
	 * 生成签名结果
	 * @param $para_sort 已排序要签名的数组
	 * return 签名结果字符串
	 */
	private function buildSign($para) {
		//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$str = create_link_string($para, false); //计算签名参数不进行urlencode编码
		$str = $str.'&key='.$this->config['key'];
		$sign = strtoupper(md5($str));
		return $sign;
	}

	/**
	 * 验证签名
	 * @param array $data
	 * @return boolean 返回签名的字符串
	 */
	function verifySign($data){
		if( empty($data)) return false;
		$sign1 = $data['sign']; //支付服务器传入的签名
		$temp = $this->filterPara($data);
		ksort($temp); reset($temp);
		$sign2 = $this->buildSign($temp);
		if( $sign1==$sign2){
			return true;
		}else{
			return false;
		}
	}

	/**
	 * 除去数组中的空值和签名参数
	 * @param $para 签名参数组
	 * return 去掉空值与签名参数后的新签名参数组
	 */
	private function filterPara($para) {
		$para_filter = array();
		foreach ($para as $key=>$val) {
			if($key == 'sign' || $key=='appsecret' || is_array($val) || $key=='key' || $val === '') {
				continue;
			}else{
				$para_filter[$key] = $para[$key];
			}
		}
		return $para_filter;
	}

	/**
	 * 同步返回处理
	 */
	public function returnurl(){
		return false;
	}

	/**
	 * 异步返回处理
	 */
	public function notifyurl(){
		//获取通知的数据
		//$xml = $GLOBALS['HTTP_RAW_POST_DATA'];
		//HTTP_RAW_POST_DATA：This feature was DEPRECATED in PHP 5.6.0, and REMOVED as of PHP 7.0.0.
		$xml = file_get_contents("php://input");
		
		if( APP_DEBUG ) WriteErrLog( "微信支付异步通知。".htmlentities($xml) );
		$xml = $this->fromXml($xml);  //转化为数组
		$SuccessXml = "<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>";
		$FailXml = "<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>";
		if($xml['return_code'] == 'SUCCESS' &&  $xml['result_code'] == 'SUCCESS'){
			$OrderNumber = $xml['out_trade_no'];  //获取订单号
			if( substr($OrderNumber, 0, 4) == 'ZXCZ' && false !== strpos($OrderNumber, '_') ){ //会员充值
				$m = D('Admin/Cash');
				$data = $m->getPayParams($OrderNumber);
				if(false === $data) exit($FailXml);
				if(true === $data) exit($SuccessXml);
				$config['AccountKey'] = $GLOBALS['Config']['XCX_ACCOUNT_KEY']; //微信支付秘钥
				$this->setConfig( $config );  //设置参数
				$b = $this->verifySign($xml); //验证签名是否正确
				if($b){
					$m->setPayStatus($OrderNumber); //设置为已支付状态
					exit($SuccessXml);
				}
			}else{ //购物车订单支付
				$config['AccountKey'] = $GLOBALS['Config']['XCX_ACCOUNT_KEY']; //微信支付秘钥
				$this->setConfig( $config );
				$b = $this->verifySign($xml); //验证本次返回的xml数据签名是否正确
				if($b){
					$m = D('Admin/Order');
					$m->setOrder($OrderNumber, 2, 1); //已处理，已支付
					exit($SuccessXml);
				}
			}
		}
		exit($FailXml);
	}
}

/**
 * 银联网关支付
 *
 */
class YdUnionPay {
	var $config;  //配置关联数组
	//测试下单接口
	//var $frontTransUrl= 'https://gateway.test.95516.com/gateway/api/frontTransReq.do';
	var $frontTransUrl= 'https://gateway.95516.com/gateway/api/frontTransReq.do';
	function __construct($para=array()){
		$this->setConfig($para);
	}

	function __destruct(){
		unset($this->config);
	}

	/**
	 * 设置参数
	 * @param array $para
	 */
	function setConfig($para=array()){
		if(empty($para)) return;
		$merId = trim($para['AccountName']); //商户号
		$key = trim($para['AccountKey']);        //秘钥
		$orderId = trim($para['OrderNumber']); //订单编号
		$orderId = str_replace('_', 'a', $orderId); //仅对在线充值有效，订单不允许有下划线
		if(isset( $para['OrderTime'] )){
			$ts = strtotime($para['OrderTime']);
			$txnTime =  date('YmdHis', $ts);
		}else{ //如果是在线充值则没有OrderTime
			$txnTime =  date('YmdHis');
		}
		$txnAmt = intval(((double)$para['TotalOrderPrice'])*100);  //消费金额，单位：分
		$this->config = array(
				//以下信息非特殊情况不需要改动
				'version' => '5.1.0',       //报文版本号，固定5.1.0，请勿改动
				'encoding' => 'utf-8',	//编码方式
				'txnType' => '01',			//交易类型，固定为01
				'txnSubType' => '01',	//交易子类，固定为01
				'bizType' => '000201',	//业务类型，固定为000201
				'frontUrl' => isset($para['ReturnUrl']) ? $para['ReturnUrl'] : pay_return_url(__CLASS__),   //服务器异步通知页面路径
				'backUrl' => isset($para['NotifyUrl']) ? $para['NotifyUrl'] : pay_notify_url(__CLASS__),	    //后台通知地址
				'signMethod' => '11',	//签名方法:01 RSA，11：SHA256、12：SM3
				'channelType' => '08',	         //渠道类型，07-PC，08-手机
				'accessType' => '0',		         //接入类型 0：普通商户直连接入、1：收单机构接入、2：平台类商户接入
				'currencyCode' => '156',	     //交易币种，境内商户固定156
				
				'merId' => $merId,	     //商户代码，请改自己的测试商户号
				'orderId' => $orderId,	 //商户订单号，8-32位数字字母，不能含“-”或“_”
				'txnTime' => $txnTime,	 //订单发送时间，格式为YYYYMMDDhhmmss
				'txnAmt' => $txnAmt,	//交易金额，单位分
				'key' => $key,  //加密秘钥
				'payTimeout' => date('YmdHis', strtotime('+15 minutes')),
		);
		if(APP_DEBUG){
			WriteErrLog("setConfig:<br/>".var_export($this->config, true));
		}
	}
	
	/**
	 * 生成要请求给支付宝的参数数组
	 * @param $para_temp 请求前的参数数组
	 * @return 要请求的参数数组
	 */
	function getPayUrl() {
		$para_temp = $this->config;
		if( empty( $para_temp ) ) return '';
		//1.除去待签名参数数组中的空值和签名参数
		$para_temp = $this->filterPara($para_temp);
		//2.对待签名参数数组排序
		ksort($para_temp); reset($para_temp);
		//3.生成签名结果，签名结果与签名方式加入请求提交参数组中
		$para_temp['signature'] = $this->buildSign($para_temp);
		$para_temp['signMethod'] = $this->config['signMethod'];

		$payUrl="<html>
		<head><meta http-equiv='Content-Type' content='text/html; charset=utf-8' /></head>
		<body onload='submitForm()'>
		<form id='pay' name='pay' action='{$this->frontTransUrl}' method='post'>";
		foreach ( $para_temp as $key => $value ) {
			$payUrl .= "<input type='hidden' name='{$key}' id='{$key}' value='{$value}' />\n";
		}
		$payUrl .="</form>
		</body>
		</html>
		<script>
			function submitForm(){
				 document.getElementById('pay').submit();
			}
		</script>";
		return $payUrl;
	}

	
	/**
	 * 生成签名结果
	 * @param $para_sort 已排序要签名的数组
	 * return 签名结果字符串
	 */
	private function buildSign($para) {
		//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$str = create_link_string($para, false);  //计算签名参数不进行urlencode编码
		//签名方法:01 RSA，11：SHA256、12：SM3
		switch (strtoupper(trim($this->config['signMethod']))) {
			case '11' : 
				$key = $this->config['key'];
				$params_before_sha256 = hash('sha256', $key);
				$params_before_sha256 = $str.'&'.$params_before_sha256;
				$sign = hash('sha256',$params_before_sha256);
				break;
			default :
				$sign = '';
		}
		return $sign;
	}
	
	/**
	 * 验证签名
	 * @param array $data
	 * @return boolean 返回签名的字符串
	 */
	function verifySign($data){
		if( empty($data)) return false;
		$sign1 = $data['signature']; //支付服务器传入的签名
		$temp = $this->filterPara($data);
		ksort($temp); reset($temp);
		$sign2 = $this->buildSign($temp);
		if( $sign1==$sign2){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * 除去数组中的空值和签名参数
	 * @param $para 签名参数组
	 * return 去掉空值与签名参数后的新签名参数组
	 */
	private function filterPara($para) {
		$para_filter = array();
		foreach ($para as $key=>$val) {
			if($key == 'signature' || $key == 'sign_type' || $key=='key' || $val === '') {
				continue;
			}else{
				$para_filter[$key] = $para[$key];
			}
		}
		return $para_filter;
	}
	
	/**
	 * 同步返回处理
	 */
	public function returnurl(){
		$OrderNumber = $this->getOrderNumber($_POST['orderId']);  //获取订单号
		$m = D('Admin/Order');
		$data = $m->findOrder($OrderNumber);
		return $data;
	}
	
	private function getOrderNumber($OrderNumber){
		//在线充值订单组成：'ZXCZ'.date('YmdHis').'_'.$CashID;
		if(substr($OrderNumber, 0, 4) == 'ZXCZ') {
			$OrderNumber = str_replace('a', '_', $OrderNumber);
		}
		return $OrderNumber;
	}
	
	/**
	 * 异步返回处理
	 */
	public function notifyurl(){
		if(APP_DEBUG){
			WriteErrLog("UnionPay Notifyurl：<br/>".var_export($_POST,true) );
		}
		$m = D('Admin/Order');
		//获取原始订单号
		$OrderNumber = $this->getOrderNumber($_POST['orderId']);  
		if( substr($OrderNumber, 0, 4) == 'ZXCZ'){ //会员充值
			$m = D('Admin/Cash');
			$data = $m->getPayParams($OrderNumber);
			if(false === $data) exit('fail');
			if(true === $data) exit('success');
			$this->setConfig( $data );  //设置参数
			$isVerified = $this->verifySign($_POST); //验证签名是否正确
			if($isVerified){
				$m->setPayStatus($OrderNumber); //设置为已支付状态
				exit('success');
			}
		}else{
			$data = $m->findOrder($OrderNumber);
			if( $data['PayStatus'] == 1 ) exit('success');
			$this->setConfig( $data );  //设置参数
			$isVerified = $this->verifySign($_POST);  //验证签名是否正确
			if(APP_DEBUG){
		    	WriteErrLog("verifySign校验结果：<br/>".($isVerified?1:0) );
			}
			if( $isVerified) {  //签名验证成功
				$m->setOrder($data['OrderID'], 2, 1); //已处理，已支付
				exit('success');
			}
		}
		exit('fail');
	}
}
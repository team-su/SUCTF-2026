<?php
if (!defined('APP_NAME')) exit();
class YdJiguangPush {
	private $appKey;
    private $masterSecret;
    private $lastError;
	function __construct($para=array()){
		$this->appKey = $GLOBALS['Config']['APP_KEY_JIGUANG'];
		$this->masterSecret = $GLOBALS['Config']['APP_MASTER_SECRET_JIGUANG'];
		$this->lastError = '';
	}
	
	function __destruct(){
		
	}
	
	/**
	 * 推送通知消息（会在手机通知栏显示）
	 * @param string $title
	 * @param string $content
	 * @param array $options
	 */
	function pushNotification($title, $content, $options=array()){
		$api = 'https://api.jpush.cn/v3/push';
		$json = $this->_constructMessage($title, $content, $options);
		$response= $this->_post($api, $json);
		if( is_array($response) ){
			if($response['http_code'] == 200) {
				//推送成功返回：{"sendno":"18","msg_id":"1828256757"}，可以通过msg_id查询当前消息状态
				return true;
			}else{
				$this->lastError = $response['headers']['http_code'];
				return false;
			}
		}else{
			$this->lastError = $response;
			return false;
		}
	}
	
	/**
	 * 构造消息Json字符串
	 */
	private function _constructMessage($title, $content, $options=array()){
		//JPush 当前支持："android", "ios", "winphone"
		$json['platform'] = $options['platform']; 
		$json['audience'] = 'all';
		
		//android推送消息结构
		if($options['platform'] == 'all' || $options['platform'] == 'android'){
			$json['notification']['android'] = array();
			$json['notification']['android']['alert'] = $content; //通知内容
			$json['notification']['android']['title'] = $title;  //如果指定了，则通知里原来展示 App名称的地方，将展示成这个字段。
			$json['notification']['android']['extras'] = $this->_getExtras($options['type'], $options);
		}
		
		//ios推送消息结构
		if($options['platform'] == 'all' || $options['platform'] == 'ios'){
			$json['notification']['ios'] = array();
			$json['notification']['ios']['alert'] = $content;  //通知内容
			$json['notification']['ios']['sound'] = 'default';
			$json['notification']['ios']['badge'] = '+1';
			$json['notification']['ios']['extras'] = $this->_getExtras($options['type'], $options);
		}
		$json = json_encode($json); //转化为json对象
		return $json;
	}
	
	/**
	 * 获取附加参数
	 * @param int $type
	 * @param string $platform
	 * @param array $options
	 */
	private function _getExtras($type, $options=array()) {
        $extras = array();
		foreach($options as $k=>$v){
			$extras[$k] = $v;
		}
		return $extras;
	}
	
	private function _post($url, $body='') {
		return $this->_request($url, $body, 'POST');
	}
	
	private function _get($url, $body='') {
		return $this->_request($url, $body, 'GET');
	}
	
	/**
	 * 发送HTTP请求
	 * @param $url string 请求的URL
	 * @param $method int 请求的方法
	 * @param null $body String POST请求的Body
	 * @return array 返回数组表示成功，否则返回错误信息
	 */
	private function _request($url, $body='', $method='POST') {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		// 设置User-Agent
		curl_setopt($ch, CURLOPT_USERAGENT, 'JPush-API-PHP-Client');
		// 连接建立最长耗时，单位：秒
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); 
		// 请求最长耗时，单位：秒
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		// 设置SSL版本 1=CURL_SSLVERSION_TLSv1, 不指定使用默认值,curl会自动获取需要使用的CURL版本
		// curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		// 如果报证书相关失败,可以考虑取消注释掉该行,强制指定证书版本
		//curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'TLSv1');
		// 设置Basic认证
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, $this->appKey . ":" . $this->masterSecret);
		// 设置Post参数
		if ($method === 'POST') {
			curl_setopt($ch, CURLOPT_POST, true);
		} else if ($method === 'DELETE' || $method === 'PUT') {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		}
		if (!is_null($body)) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		}
	
		// 设置headers
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Connection: Keep-Alive'
		));
	
		// 执行请求
		$output = curl_exec($ch);
		// 解析Response
		$response = array();
		$errorCode = curl_errno($ch);
		if ($errorCode) {
			$errorMsg = "请求发送错误！".curl_error();
			return $errorMsg;
		} else {
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$header_text = substr($output, 0, $header_size);
			$body = substr($output, $header_size);
			$headers = array();
			foreach (explode("\r\n", $header_text) as $i => $line) {
				if (!empty($line)) {
					if ($i === 0) {
						$headers['http_code'] = $line;
					} else if (strpos($line, ": ")) {
						list ($key, $value) = explode(': ', $line);
						$headers[$key] = $value;
					}
				}
			}
			$response['headers'] = $headers;
			$response['body'] = $body;
			$response['http_code'] = $httpCode;
		}
		curl_close($ch);
		return $response;
	}
	
	/**
	 * 获取最后一次错误信息
	 * @return string
	 */
	function getLastError(){
		return $this->lastError;
	}
	
	/**
	 * 推送自定义消息（不会会在手机通知栏显示）
	 * @param string $title
	 * @param string $content
	 * @param array $options
	 */
	function pushMessage($title, $content, $options=array()){
	
	}
}
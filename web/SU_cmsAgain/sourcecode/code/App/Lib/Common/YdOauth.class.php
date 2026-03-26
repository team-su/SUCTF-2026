<?php
if (!defined('APP_NAME')) exit();
/**
 * 第三方授权基类
 */
abstract class YdOauth{
	protected $AppID = '';     //申请应用时分配的appid
	protected $AppKey = '';   //申请应用时分配的 app_key
	protected $Callback = '';  //回调路径
	
	protected $Code = '';      //回调$_GET['code']
	protected $Token = '';     //Token，通过Code可以获取Code
	protected $OpenID = '';   //对同一个网站OpenID是唯一的
	
	public function __construct($token = null){
		
	}
	/**
	 * 取得Oauth实例
	 * @static
	 * @return mixed 返回Oauth
	 */
	public static function getInstance($type) {
		$className = 'YdOauth'.ucfirst(strtolower($type));
		if (class_exists($className)) {
			$obj = new $className();
			return $obj;
		} else {
			return false;
		}
	}
	
	public function setAppID($id){
		$this->AppID = $id;
	}
	
	public function setAppKey($key){
		$this->AppKey = $key;
	}
	
	public function setCode($code){
		$this->Code = $code;
	}
	
	public function setToken($token){
		$this->Token = $token;
	}
	
	/**
	 * 获取回调路径
	 */
	public function getCallback(){
		$url = OauthCallback(GROUP_NAME);
		return $url;
	}
	
	//抽奖方法
	abstract protected function getRequestUrl();
	abstract protected function getAccessToken();
	abstract protected function getOpenID();
	abstract protected function getUserInfo();
}

/**
 * QQ登录授权
 */
class YdOauthQq extends YdOauth {
	/**
	 * 构造方法，配置应用信息
	 * @param array $token
	 */
	public function __construct(){
		$this->Callback = $this->getCallback();
	}
	
	/**
	 * 请求code
	 */
	public function getRequestUrl(){
		//获取requestCode的api接口
		$apiUrl = 'https://graph.qq.com/oauth2.0/authorize?';
		$state = md5(uniqid(rand(), TRUE)).'_qq';  //带一个标识，可以在回调的时候知道类型
		session('oauth_state_qq', $state);
		//Oauth 标准参数
		$params = array(
				'client_id'     => $this->AppID,
				'redirect_uri'  => $this->Callback,
				'response_type' => 'code',
				'state'=>$state, //client端的状态值。用于第三方应用防止CSRF攻击，成功授权后回调时会原样带回
				'scope'=>'get_user_info,add_share',
		);
		//如果当前是手机网站
		if(GROUP_NAME=='Wap'){
			$params['display'] = 'mobile';  //用于展示的样式。不传则默认展示为PC下的样式
			$params['g_ut'] = 2;  //仅WAP网站接入时使用，1：wml版本(默认)； 2：xhtml版本
		}
		$url = $apiUrl.http_build_query($params);
		return $url;
	}
	
	/**
	 * 获取access_token
	 * @param string $code 上一步请求到的code
	 */
	public function getAccessToken(){
		//获取access_token的api接口
		$apiUrl = 'https://graph.qq.com/oauth2.0/token'; 
		$params = array(
				'client_id'     => $this->AppID,
				'client_secret' => $this->AppKey,
				'grant_type'    => 'authorization_code',
				'code'          => $this->Code,
				'redirect_uri'  => $this->Callback,
		);
		//如果出错返回：callback( {"error":100001,"error_description":"param client_id is wrong or lost "} );
		//成功则返回：access_token=YOUR_ACCESS_TOKEN&expires_in=3600
		$result = yd_curl_get($apiUrl, $params, 30);
		if (strpos($result, "callback") === false){
			$data = array();
			parse_str($result, $data);
			$this->Token = $data['access_token'];
		}
		return $this->Token;
	}
	
	/**
	 * 获取当前授权应用的openid
	 * @return string
	 */
	public function getOpenID() {
		//获取OpenID的api接口
		$apiUrl = 'https://graph.qq.com/oauth2.0/me'; 
		$params = array('access_token'=>$this->Token);
		$result = yd_curl_get($apiUrl, $params, 30);
		//返回的数据格式：callback( {"client_id":"YOUR_APPID","openid":"YOUR_OPENID"} );
		if (strpos($result, "callback") !== false){
			$lpos = strpos($result, "(");
			$rpos = strrpos($result, ")");
			$result  = substr($result, $lpos + 1, $rpos - $lpos -1);
			$result = trim($result);
		}
		$data = json_decode($result, true);
		$this->OpenID = $data['openid'];
		return $data['openid'];
	}
	
	/**
	 * 获取QQ用户信息
	 */
	public function getUserInfo(){
		//获取用户信息接口
		$apiUrl = 'https://graph.qq.com/user/get_user_info';
		$params = array(
				'access_token'     => $this->Token,
				'oauth_consumer_key' => $this->AppID,
				'openid'    => $this->OpenID,
				'code'          => $this->Code,
				'redirect_uri'  => $this->Callback,
		);
		$result = yd_curl_get($apiUrl, $params, 30);
		$result = json_decode($result, true);
		if( $result['ret'] < 0 ) return false;
		$data['MemberName'] = $result['nickname'];
		$data['MemberGender'] = ($result['gender'] == '男') ? 0 : 1;
		//优先使用100x100的头像
		$data['MemberAvatar'] = $result['figureurl_qq_1']; //40x40
		if( !empty($result['figureurl_qq_2'] ) ){
			$data['MemberAvatar'] = $result['figureurl_qq_2']; //100x100
		}
		return $data;
	}
}

/**
 * 微信授权
 */
class YdOauthWx extends YdOauth{
	/**
	 * 构造方法，配置应用信息
	 * @param array $token
	 */
	public function __construct(){
		$this->Callback = $this->getCallback();
	}
	
	/**
	 * 请求code
	 */
	public function getRequestUrl(){
		//获取requestCode的api接口
		$apiUrl = 'https://open.weixin.qq.com/connect/oauth2/authorize?';
		$state = md5(uniqid(rand(), TRUE)).'_wx';  //带一个标识，可以在回调的时候知道类型
		session('oauth_state_wx', $state);
		//Oauth 标准参数 appid=APPID&redirect_uri=REDIRECT_URI&response_type=code
		//&scope=SCOPE&state=STATE#wechat_redirect
		$params = array(
				'appid'     => $this->AppID,
				'redirect_uri'  => $this->Callback,
				'response_type' => 'code',
				'state'=>$state, //client端的状态值。用于第三方应用防止CSRF攻击，成功授权后回调时会原样带回
				'scope'=>'snsapi_userinfo',  //不能为：snsapi_base
		);
		$url = $apiUrl.http_build_query($params)."#wechat_redirect";
		return $url;
	}
	
	/**
	 * 获取access_token
	 * @param string $code 上一步请求到的code
	 */
	public function getAccessToken(){
		//获取access_token的api接口
		$apiUrl = 'https://api.weixin.qq.com/sns/oauth2/access_token';
		$params = array(
				'appid'     => $this->AppID,
				'secret' => $this->AppKey,
				'grant_type'    => 'authorization_code',
				'code'          => $this->Code,
		);
		//如果出错返回：{"errcode":40029,"errmsg":"invalid code"} 
		//成功则返回：{ "access_token":"ACCESS_TOKEN","expires_in":7200,  "refresh_token":"REFRESH_TOKEN","openid":"OPENID","scope":"SCOPE" } 
		$result = yd_curl_get($apiUrl, $params, 30);
		$result = json_decode($result, true);
		if( isset($result['access_token']) ){
			$this->Token = $result['access_token'];
			$this->OpenID = $result['openid'];
		}
		return $this->Token;
	}
	
	/**
	 * 获取当前授权应用的openid
	 * @return string
	 */
	public function getOpenID() {
		return $this->OpenID;
	}
	
	/**
	 * 获取用户信息
	 */
	public function getUserInfo(){
		//获取用户信息接口
		$apiUrl = 'https://api.weixin.qq.com/sns/userinfo';
		$params = array(
				'access_token'     => $this->Token,
				'openid'    => $this->OpenID,
				'lang'          => 'zh_CN',
		);
		$result = yd_curl_get($apiUrl, $params, 30);
		//file_put_contents("./1.txt", $this->OpenID."==".$result);
		$result = json_decode($result, true);
		//错误返回：{"errcode":40003,"errmsg":" invalid openid "} 
		//正确时返回的JSON数据包如下：{"openid":" OPENID",  "nickname": NICKNAME, "sex":"1","headimgurl":"http://xx.jpg",
		//"unionid": "o6_bmasdasdsad6_2sgVt7hMZOPfL" }
		if( isset($result['errcode']) ) return false;
		$data['MemberName'] = $result['nickname'];
		//值为1时是男性，值为2时是女性，值为0时是未知
		$data['MemberGender'] = ($result['sex'] == 1) ? 0 : 1;
		$data['MemberAvatar'] = $result['headimgurl']; //40x40
		return $data;
	}
}
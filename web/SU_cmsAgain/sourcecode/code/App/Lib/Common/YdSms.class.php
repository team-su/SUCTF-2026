<?php
/**
 * 第三方授权基类
 */
if (!defined('APP_NAME')) exit();
abstract class YdSms{
	protected $AccountName = '';       //账号吗
	protected $AccountPassword = '';   //密码或秘钥
	protected $needSave = 1;       //是否保存发送日志
	protected $Message='';       //最后一次错误信息
	protected $SmsIpMax = -1;       //每个IP每天最多能发送几条短信
	protected $SmsNumMax = -1;       //每个手机每天最多能发送几条短信
	protected $Placeholder = false; //关联数组：如：array('{$Content}'=>'123')

	public function __construct(){
	}
	/**
	 * 取得Oauth实例
	 * @static
	 * @return mixed 返回Oauth
	 */
	public static function getInstance($type) {
		$className = 'YdSms'.ucfirst(strtolower($type));
		if (class_exists($className)) {
			$obj = new $className();
			return $obj;
		} else {
			return false;
		}
	}
	
	/**
	 * 设置参数
	 * @param array $data
	 */
	public function setConfig($p=array() ){
		$this->AccountName = $p['SMS_ACCOUNT'];
		$this->AccountPassword = $p['SMS_PASSWORD'];
		$this->SmsIpMax = is_numeric($p['SMS_IP_MAX']) ? $p['SMS_IP_MAX'] : -1;
		$this->SmsNumMax = is_numeric($p['SMS_NUM_MAX']) ? $p['SMS_NUM_MAX'] : -1;
	}
	
	public function setAccountName($name){
		$this->AccountName = $name;
	}
	
	public function setAccountPassword($pwd){
		$this->AccountPassword = $pwd;
	}
	
	public function setSmsIpMax ($max){
		$this->SmsIpMax = is_numeric($max) ? $max : -1;
	}
	
	public function setSmsNumMax($max){
		$this->SmsNumMax = is_numeric($max) ? $max : -1;
	}
	
	public function getMessage(){
		return $this->Message;
	}
	
	/**
	 * 是否需要保存日志
	 * @param int $b：1：保存，0：不保存
	 */
	public function needSave($b){
		$this->needSave = $b;
	}
	
	/**
	 * 设置占位符
	 * @param array $placeholder 关联数组，如：array("{$Content}","abc");
	 */
	public function setPlaceholder($placeholder){
		if( is_array($placeholder) ){
			$this->Placeholder = $placeholder;
		}
	}
	
	/**
	 * 发送通知短信
	 * @param string $mobile 电话号码，只能是一个
	 * @param string $content 短信内容
	 */
	abstract protected function sendNotifyMessage($mobile, $content);
	
	/**
	 * 发送广告短信
	 * @param mixed $mobilelist 当时string时，多个电话以逗号隔开；为数组是直接存储多个号码
	 * @param string $content 短信内容
	 */
	abstract protected function sendAdsMessage($mobilelist, $content);
	
	/**
	 * 获取剩余短信数量
	 */
	abstract protected function getLeftNum();
	
}

/**
 * 互亿无线短信接口
 */
class YdSmsHuyi extends YdSms {
	/**
	 * 构造方法，配置应用信息
	 * @param array $token
	 */
	public function __construct(){
		
	}
	
	public function sendAdsMessage($mobilelist, $content){
	
	}
	
	public function getLeftNum(){
		$api = "http://106.ihuyi.com/webservice/sms.php?method=GetNum";
		$time = time();
		//生成签名
		$password = md5($this->AccountName.$this->AccountPassword.$time);
		$params = array(
				'account'=>$this->AccountName,
				'password'=>$password,
				'time'=>$time
		);
		$result = yd_curl_post($api, $params, 60);
		$result = $this->parseResult($result);
		$this->Message = $result['msg'];
		if( $result['code'] == 2 ){
			return $result['num'];
		}else{
			return $result['msg'];
		}
	}
	
	public function sendNotifyMessage($mobile, $content){
		if( !$this->check($mobile) ) {
			$this->writeLog($mobile, $content, "发送失败，".$this->Message);
			return false;
		}
		$content = $this->parsePlaceholder($content);
		//$api = "http://106.ihuyi.cn/webservice/sms.php?method=Submit";  //旧版api接口
		$api = "http://106.ihuyi.com/webservice/sms.php?method=Submit";
		            
		$time = time();
		//生成签名
		$password = md5($this->AccountName.$this->AccountPassword.$mobile.$content.$time);
		$params = array(
			'account'=>$this->AccountName,
			'password'=>$password,
			'mobile'=>$mobile,
			'content'=>$content,
			'time'=>$time
		);
		$result = yd_curl_post($api, $params, 60);
		$result = $this->parseResult($result);
		$this->Message = $result['msg'];
		if( $result['code'] == 2  ){
			$this->writeLog($mobile, $content, $result['msg']);
			return true;
		}else{
			$this->writeLog($mobile, $content, "发送失败，".$result['msg']);
			return false;
		}
	}
	
	/**
	 * 替换短信内容的占位符变量
	 * @param string $template
	 */
	private function parsePlaceholder($template){
		//1，变量长度最长350个字符
		//2，整条短信不要超过500个字符
		//3，一个汉字，一个标点符号 一个英文字母等都各算一个字符
		if( is_array($this->Placeholder) && function_exists('mb_strlen') ){
			//获取模板中实际的变量个数
			$n = preg_match_all('/\{\$[a-zA-Z0-9]+\}/U', $template, $matches);
			if( $n > 0 ){
				$maxVar = 340;  //变量最大长度
				$maxSms = 490; //短信最大长度
				$lenTemplate = mb_strlen( $template , 'utf-8');
				$len = ($maxSms - $lenTemplate) / $n;
				//档$len<=0时，忽略
				if($len > $maxVar || $len <= 0 ) $len = $maxVar;
				foreach ($this->Placeholder as $k=>$v){
					$n = mb_strlen( $v , 'utf-8');
					if( $n > $len ){ //截取字符串
						$v = msubstr( $v, 0, $len, 'utf-8', '...');
					}
					$template = str_ireplace($k, $v, $template);
				}
			}
		}
		$template = str_ireplace('<br/>', "\n\r", $template);
		//删除里面的所有HTML标记
		$template = strip_tags($template); 
		return $template;
	}
	
	/**
	 * 安全检查
	 * @param string $mobile
	 */
	private function check($mobile){
		$ipMax = $this->SmsIpMax;
		$numMax = $this->SmsNumMax;
		if( $ipMax == -1 && $numMax == -1 ) return true;
		$m = D('Admin/Log');
		if( $ipMax != -1 ){
			$ip = get_client_ip();
			$n = $m->getDaySmsCountByIp($ip);
			if( $n >= $ipMax ) {
				$this->Message = "每个IP每天最多发送{$ipMax}条短信";
				return false;
			}
		}
		
		if( $numMax != -1 ){
			$n = $m->getDaySmsCountByNum($mobile);
			if( $n >= $numMax ) {
				$this->Message = "每个手机号码每天最多发送{$numMax}条短信";
				return false;
			}
		}
		return true;
	}
	
	private function writeLog($mobile, $content, $msg){
		if($this->needSave == 1 ){ //保存日志
			if(!empty($msg)){
				$description = "手机号码：{$mobile}；<br/>发送状态：{$msg}；<br/>短信内容：{$content}";
			}else{
				$description = "手机号码：{$mobile}；<br/>短信内容：{$content}";
			}
			$options = array('LogType'=>9, 'UserAction'=>$mobile);
			WriteLog($description, $options);
		}
	}
	
	/**
	 * 解析返回xml响应结果，返回数组
	 * @param string $str
	 */
	private function parseResult($str){
		/* 返回格式如下：
		 <?xml version="1.0" encoding="utf-8"?>
		<SubmitResult xmlns="http://106.ihuyi.cn/">
		<code>2</code>
		<msg>提交成功</msg>
		<smsid>67472311</smsid>
		</SubmitResult>';
		*/

		$xml = simplexml_load_string($str);
		$data['code'] = (string)$xml->code;
		$data['msg'] = (string)$xml->msg;
		if( !empty($xml->smsid) ){
			$data['smsid'] = (string)$xml->smsid;
		}
		//短信剩余数量
		$num = (int)$xml->num;
		if( is_numeric($num) ){
			$data['num'] = $num;
		}
		return $data;
	}
}
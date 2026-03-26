<?php
if (!defined('APP_NAME')) exit();
/**
 * 微信js sdk 开发
 * @author Administrator
 *
 */
class YdJsSdk {
  private $appId;
  private $appSecret;
  private $url;
  public function __construct($appId, $appSecret, $url) {
    $this->appId = $appId;
    $this->appSecret = $appSecret;
	$this->url = $url;
  }

  public function getSignPackage() {
    $jsapiTicket = $this->getJsApiTicket();
    //签名用的url必须是调用JS接口页面的完整URL
    //$url = "http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
	$url = $this->url;
    $timestamp = time();
    $nonceStr = rand_string(32);

    // 这里参数的顺序要按照 key 值 ASCII 码升序排序
    $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
    $signature = sha1($string);
	//file_put_contents("./Data/2.txt", $string."\n\r".$signature);
    $signPackage = array(
      "appId"     => $this->appId,
      "nonceStr"  => $nonceStr,
      "timestamp" => $timestamp,
      "url"       => $url,
      "signature" => $signature,
      "rawString" => $string
    );
    return $signPackage; 
  }

  private function getJsApiTicket() {
    // jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例，jsapi_ticket的有效期为7200秒
  	$fileName = "./Data/jsapi_ticket.php"; //不建议使用json格式，会被所有人看到
    $data = file_exists($fileName) ? json_decode(file_get_contents($fileName)): array('expire_time'=>0);
    if ($data->expire_time < time()) { //如果已经过期，就重新获取
      $accessToken = $this->getAccessToken();
      $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
      $res = yd_curl_get($url);
	  //file_put_contents("./Data/11.txt", "res:".$res);
      $res = json_decode( $res );
      
      $ticket = $res->ticket;
      if ($ticket) {
        $data->expire_time = time() + 7000;
        $data->jsapi_ticket = $ticket;
        file_put_contents($fileName, json_encode($data));
      }
    } else {
      $ticket = $data->jsapi_ticket;
    }
    return $ticket;
  }

  private function getAccessToken() {
  	$fileName = "./Data/access_token.php";
    // access_token 应该全局存储与更新，以下代码以写入到文件中做示例
    $data = file_exists($fileName) ? json_decode(file_get_contents($fileName)) : array('expire_time'=>0);
    if ($data->expire_time < time()) {
      $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->appId}&secret={$this->appSecret}";
      $res = json_decode( yd_curl_get($url) );
      $access_token = $res->access_token;
      if ($access_token) {
        $data->expire_time = time() + 7000;
        $data->access_token = $access_token;
        file_put_contents($fileName, json_encode($data));
      }
    } else {
      $access_token = $data->access_token;
    }
    return $access_token;
  }
}

function trimall($str){
	$str = Left(strip_tags($str), 60);
	$search = array('<br />', '<br/>', '<br>',"\n", "\r", "\n\r");
	$str = str_replace($search, '', $str);
	return $str;
}
<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */
if (!defined('APP_NAME')) exit();

//大转盘
function dazhuanpan($params){
	return _lottery($params, 0);
}

//刮刮卡
function guaguaka($params){
	return _lottery($params, 1);
}

//$type 0:大转盘，1：刮刮卡
function _lottery($params, $type){
		import('@.Common.YdWxMsg');
		$toUser = $params['fromUser'];
		$fromUser = $params['toUser'];
		$m = D('Admin/WxApp');
		$appid = $params['parameter']['appid'];
		if(empty($appid)){
			$appid = -1;
		}
		$data = $m->getLotteryInfo($type, $appid);
		if( empty($data) ) return false;
		$msg = "<xml>
		<ToUserName><![CDATA[%s]]></ToUserName>
		<FromUserName><![CDATA[%s]]></FromUserName>
		<CreateTime>%s</CreateTime>
		<MsgType><![CDATA[news]]></MsgType>
		<ArticleCount>%s</ArticleCount>
		<Articles>";
		$n= is_array($data) ? count($data) : 0;
		if( $n > 10 ) $n = 10;
		for($i=0; $i < $n; $i++){
			$p = explode('@@@', $data[$i]['AppParameter']);
			$title = $data[$i]['AppName'];
			$description = empty($p[1]) ? $p[2] : $p[1]; //1:LotteryIntroduction 2:LotteryDescription
			$picUrl = YdWxMsg::parseResourceUrl( $p[6] );
			$url = WxLotteryUrl($data[$i]['AppID']);
			$url .= "&fu=$toUser";
			$msg .= "<item>
			<Title><![CDATA[$title]]></Title>
			<Description><![CDATA[$description]]></Description>
			<PicUrl><![CDATA[$picUrl]]></PicUrl>
			<Url><![CDATA[$url]]></Url>
			</item>";
		}
		$msg .="</Articles></xml>";
		$str = sprintf($msg, $toUser, $fromUser, time(), $n);
		return $str;
	}

//兑奖
function duijiang($params){
		import('@.Common.YdWxMsg');
		$m = D('Admin/WxAward');
		$data = $m->getUserAward($params['fromUser']);
		$msg = '';
		if( empty($data) ){
			$msg = "没有中奖信息!";
			return YdWxMsg::constructTextReplyMsg($params['fromUser'], $params['toUser'], $msg);
		}
		$t = array(1=>array("一等奖", 10),2=>array("二等奖", 13),3=>array('三等奖', 16));
		$n = is_array($data) ? count($data) : 0;
		if($n==1){ //一条中奖信息
			$p = explode('@@@', $data[0]['AppParameter']);
			$i = $data[0]['AwardNumber']; //几等奖
			$AppName =$data[0]['AppName'];
			$AwardLevel = $t[ $i ][0];   //奖项名称
			$AwardName = $p[ $t[$i][1] ]; //奖品名称
			$Status = $data[0]['IsCheck'] == 1 ? "已兑奖" : "未兑奖";
			$msg = "恭喜您在活动\"{$AppName}\"中中了{$AwardLevel}[{$AwardName} {$Status}],兑奖SN码:{$data[0]['AwardSN']},请及时联系我们!";
		}else{ //多条中奖信息
			$msg = "恭喜您在活动\n";
			for($j = 0; $j < $n; $j++){
				$p = explode('@@@', $data[$j]['AppParameter']);
				$i = $data[$j]['AwardNumber']; //几等奖
				$AppName =$data[$j]['AppName'];
				$AwardLevel = $t[ $i ][$j];   //奖项名称
				$AwardName = $p[ $t[$i][1] ]; //奖品名称
				$Status = $data[$j]['IsCheck'] == 1 ? "已兑奖" : "未兑奖";
				$SN = $data[$j]['AwardSN'];
				$x = $j+1;
				$msg .= "[{$x}]{$AppName}\"中中了{$AwardLevel}[{$AwardName} {$Status}],兑奖SN码:{$SN}\n";
			}
			$msg .= "请及时联系我们!";
		}
		return YdWxMsg::constructTextReplyMsg($params['fromUser'], $params['toUser'], $msg);
}
	
//商家兑奖（定做）
function shangjiaduijiang($params){
	import('@.Common.YdWxMsg');
	$pwd = $params['matches'][1]; //密码
	$mobile = $params['matches'][2]; //手机号码
	$place = $params['matches'][3]; //奖品等级

    $pwd = YdInput::checkKeyword($pwd);
    $mobile = YdInput::checkLetterNumber($mobile);
    $place = YdInput::checkKeyword($place);

	if( $place == 0){
		$msg = "奖品等级参数错误";
		return YdWxMsg::constructTextReplyMsg($params['fromUser'], $params['toUser'], $msg);
	}
	
	$m = D('Admin/WxApp');
	
	//判断商家兑奖密码是否正确
	$where = "AppParameter like '%@@@$pwd%' and IsEnable=1";
	$appid = $m->where($where)->getField('AppID');
	if( empty($appid) ){
		$msg = "商家兑奖密码错误！";
		return YdWxMsg::constructTextReplyMsg($params['fromUser'], $params['toUser'], $msg);
	}
	
	//获取fu
	$mm = D('Admin/Member');
	$FromUser = $mm->where("MemberMobile='$mobile'")->getField('FromUser');
	if( empty($FromUser) ){
		$msg = "{$mobile}不存在";
		return YdWxMsg::constructTextReplyMsg($params['fromUser'], $params['toUser'], $msg);
	}
	
	//兑奖
	$ma = D('Admin/WxAward');
	$where ="AppID=$appid and FromUser='$FromUser' and AwardNumber=$place";
	$result = $ma->where($where)->setField('IsCheck', 1);
	if(empty($result)){
		$msg = "没有中{$place}等奖或者已经兑奖！";
	}else{
		$msg = "{$mobile}兑换{$place}等奖成功！";
	}
	return YdWxMsg::constructTextReplyMsg($params['fromUser'], $params['toUser'], $msg);
}

//微调查
function diaocha($params){
	import('@.Common.YdWxMsg');
	$toUser = $params['fromUser'];
	$fromUser = $params['toUser'];
	$m = D('Admin/WxApp');
	$appid = $params['parameter']['appid'];
	if(empty($appid)){
		$appid = -1;
	}
	$data = $m->getResearch($appid, 1);
	if( empty($data) ) return false;
	$msg = "<xml>
	<ToUserName><![CDATA[%s]]></ToUserName>
	<FromUserName><![CDATA[%s]]></FromUserName>
	<CreateTime>%s</CreateTime>
	<MsgType><![CDATA[news]]></MsgType>
	<ArticleCount>%s</ArticleCount>
	<Articles>";
	$n= is_array($data) ? count($data) : 0;
	if( $n > 10 ) $n = 10;
	for($i=0; $i < $n; $i++){
		$title = $data[$i]['AppName'];
		$picUrl=YdWxMsg::parseResourceUrl( $data[$i]['ResearchPicture'] );
		$url = WxResearchUrl($data[$i]['AppID']);
		$url .= "&fu=$toUser";
		$description = $data[$i]['ResearchDescription'];
		$msg .= "<item>
		<Title><![CDATA[$title]]></Title>
		<Description><![CDATA[$description]]></Description>
		<PicUrl><![CDATA[$picUrl]]></PicUrl>
		<Url><![CDATA[$url]]></Url>
		</item>";
	}
	$msg .="</Articles></xml>";
	$str = sprintf($msg, $toUser, $fromUser, time(), $n);
	return $str;
}

//微投票
function toupiao($params){
	import('@.Common.YdWxMsg');
	$toUser = $params['fromUser'];
	$fromUser = $params['toUser'];
	$m = D('Admin/WxApp');
	$appid = $params['parameter']['appid'];
	if(empty($appid)){
		$appid = -1;
	}
	$data = $m->getVote($appid, 1);
	if( empty($data) ) return false;
	$msg = "<xml>
	<ToUserName><![CDATA[%s]]></ToUserName>
	<FromUserName><![CDATA[%s]]></FromUserName>
	<CreateTime>%s</CreateTime>
	<MsgType><![CDATA[news]]></MsgType>
	<ArticleCount>%s</ArticleCount>
	<Articles>";
	$n= is_array($data) ? count($data) : 0;
	if( $n > 10 ) $n = 10;
	for($i=0; $i < $n; $i++){
		$title = $data[$i]['AppName'];
		$picUrl=YdWxMsg::parseResourceUrl( $data[$i]['VotePicture'] );
		$url = WxVoteUrl( $data[$i]['AppID'] );
		$url .= "&fu=$toUser";
		$description = $data[$i]['AppDescription'];
		$msg .= "<item>
		<Title><![CDATA[$title]]></Title>
		<Description><![CDATA[$description]]></Description>
		<PicUrl><![CDATA[$picUrl]]></PicUrl>
		<Url><![CDATA[$url]]></Url>
		</item>";
	}
	$msg .="</Articles></xml>";
	$str = sprintf($msg, $toUser, $fromUser, time(), $n);
	return $str;
}

//微会员卡
function huiyuanka($params){
	$m = D('Admin/WxApp');
	$data = $m->findCardConfig(1);
	if( empty($data) ) return false;
	import('@.Common.YdWxMsg');
	$toUser = $params['fromUser'];
	$fromUser = $params['toUser'];
	$time = time();
	$title = $data['AppName'];
	$description = $data['CardTip'];
	$picUrl=YdWxMsg::parseResourceUrl( $data['CardPicture'] );
	$url = WxCardUrl();
	$url .= (strpos($url, '?') === false ) ? '?' : '&';
	$url .= "fu=$toUser";
	
	$msg = "<xml>
	 <ToUserName><![CDATA[{$toUser}]]></ToUserName>
	 <FromUserName><![CDATA[{$fromUser}]]></FromUserName>
	 <CreateTime>{$time}</CreateTime>
	 <MsgType><![CDATA[news]]></MsgType>
	 <ArticleCount>1</ArticleCount>
	 <Articles>
		 <item>
			 <Title><![CDATA[$title]]></Title>
			 <Description><![CDATA[$description]]></Description>
			 <PicUrl><![CDATA[$picUrl]]></PicUrl>
			 <Url><![CDATA[$url]]></Url>
		 </item>
	 </Articles>
	 </xml>";
	return $msg;
}


//天气查询接口
function weather($params){
	import('@.Common.YdWxMsg');
	//应用传入参数
	$city = $params['matches'][1];
	if(empty($city)){
		$city = $params['parameter']['city']; //获取默认值
	}
	$num = $params['parameter']['days']; //返回几天的天气情况
	
	$url = "http://php.weather.sina.com.cn/xml.php";
	$name = mb_convert_encoding($city,'gb2312','UTF-8');
	$weekarray=array(" 星期日"," 星期一"," 星期二"," 星期三"," 星期四"," 星期五"," 星期六");
	//参数：day: 0:代表当天最大取值为4
	$param = array('password'=>'DJOYnieT8234jlsK', 'city'=>$name, 'day'=>'0');
	$msg = "{$city}天气预报\n";
	
	for($i = 0; $i < $num; $i++){
		$param['day'] = $i;
		$content = yd_curl_get($url, $param);
		if( strlen($content) < 120 ){
			$msg = $params['parameter']['errormsg'];
			break;
		}
		$xml = simplexml_load_string($content);
		$ts = time() + $i*24*60*60;
		$time = date('Y-m-d', $ts).$weekarray[date('w', $ts)];
		foreach($xml as $x){
			$msg .= ( $x->status1 == $x->status2 ) ? "{$time}\n$x->status1" : "{$time}\n{$x->status1}转{$x->status2}";
			$msg .= " {$x->temperature1}℃/{$x->temperature2}℃ ";
			if($x->direction1 == $x->direction2 && $x->power1 == $x->power2 ){
				$msg .= "{$x->direction1}{$x->power1}级";
			}else{
				$msg .= "{$x->direction1}{$x->power1}级转{$x->direction2}{$x->power2}级";
			}
		}
		if( $i < $num-1 ) $msg .= "\n\n";
	}
	$reply = YdWxMsg::constructTextReplyMsg($params['fromUser'], $params['toUser'], $msg);
	return $reply;
}

//手机归属地查询
function guishudi($params){
	/*
	{
		  "error_code": 0,
		  "reason": "Succes",
		  "result": {
		    "mobilenumber": "1302167",
		    "mobilearea": "山东 青岛市",
		    "mobiletype": "联通如意通卡",
		    "areacode": "0532",
		    "postcode": "266000"
		  }
	}
	 */
	$mobile = $params['matches'][0];
	$api = 'http://api.avatardata.cn/MobilePlace/LookUp';
	$p = array('mobileNumber'=>$mobile, 'key'=>'9ac4a550645445e183212106a0c5c7ef', 'dtype'=>'json');
	$data = yd_curl_get($api, $p);
	$data = json_decode($data, true);
	if( 0 == $data['error_code'] && isset($data['result']) ){
		$msg = '';
		foreach ($data['result'] as $k=>$v){
			if($k != 'mobilenumber'){
				$msg .= "{$v}  ";
			}
		}
	}else{
		$msg = '查询失败';
	}
	import('@.Common.YdWxMsg');
	$reply = YdWxMsg::constructTextReplyMsg($params['fromUser'], $params['toUser'], $msg);
	return $reply;
}


//健康查询
function jiankang($params) {
	// 体重指数 = 体重（公斤） 除 身高（米）的平方
	import('@.Common.YdWxMsg');
	$height = $params['matches'][1] / 100;
	$weight = $params['matches'][2];
	$index = round ( $weight / ($height * $height), 1 );
	$msg="您的身高是{$params['matches'][1]}厘米，体重是{$params['matches'][2]}公斤，";
	if($index < 18){
		$msg .= '您的身材偏瘦，请及时补充营养';
	}elseif ($index >= 18 && $index < 25) {
		$msg .= '您的身材很标准，继续保持哦';
	}elseif ($index >= 25 && $index < 30) {
		$msg .= '您的身材超重，请多多锻炼身体';
	}elseif ($index >= 30 && $index < 35) {
		$msg .= '您的身材轻度肥胖，请多多锻炼身体';
	}elseif ($index >= 35 && $index < 40) {
		$msg .= '您的身材中度肥胖，请多多锻炼身体';
	}else if($index >= 40){
		$msg .= '您的身材重度肥胖，请多多锻炼身体';
	}
	$reply = YdWxMsg::constructTextReplyMsg($params['fromUser'], $params['toUser'], $msg);
	return $reply;
}

//笑话
function xiaohua($params) {
	import('@.Common.YdWxMsg');
	$list = array("");
	$i = rand(0, count($list)-1);
	$reply = YdWxMsg::constructTextReplyMsg($params['fromUser'], $params['toUser'], $list[$i]);
	return $reply;
}


function kuaidi($params) {
	import('@.Common.YdWxMsg');
	$com = $params['matches'][1];  //快递公司
	$nu = $params['matches'][2]; //快递单号
	/*
	$api = "http://api.kuaidi100.com/api";
	$p = array('com'=>$com,'nu'=>$nu, 'id'=>'授权ID', 'show'=>'json', 'order'=>'desc');
	$result = yd_curl_get($api, $p);
	$result = json_decode($result, true);
	if( $result['status'] == 0 ){
		$msg = '物流单暂无结果';
	}elseif($result['status'] == 1){
		$msg = '';
		foreach ($result['data'] as $k=>$v){
			$msg .= "{$v['time']}  {$v['context']}\n";
		}
		$msg = trim($msg);
	}else{
		$msg = '接口出现异常';
	}
	*/
	/*接口说明
	 * 使用爱快递接口 http://www.aikuaidi.cn/api/
	* key	string	是	授权密钥
	* order	string	是	快递单号，请注意区分大小写
	* id	string	是	快递代号，如：圆通（yuantong）、申通（shentong），点击此处 [ 查看完整快递代号 ]
	* ord	string	可选	排序规则：asc：按时间旧到新排序，desc：按时间新到旧排序，不传默认值：asc
	* show	string	可选	返回类型：json：返回json字符串，xml：返回xml字符串，html：返回html字符串，不传默认值：json
	* status	订单跟踪状态：0：查询出错（即errCode!=0），1：暂无记录，2：在途中，3：派送中，4：已签收，5：拒收，6：疑难件7：退回
	*/
	$api = "http://www.aikuaidi.cn/rest/";
	$status = array('查询出错', '暂无记录', '在途中', '派送中', '已签收', '拒收', '疑难件', '退回');
	$p = array('id'=>$com,'order'=>$nu, 'key'=>'530d64201299411887cf015ede866da4', 'show'=>'json', 'ord'=>'asc');
	$result = yd_curl_get($api, $p);
	$result = json_decode($result, true);
	if( isset($result['errCode']) && $result['errCode'] == 0 ){
		$msg = "{$result['name']} {$result['id']}\n";
		$msg .= "快递状态：".$status[ $result['status'] ]. "\n";
		$i = 1;
		foreach ($result['data'] as $k=>$v){
			$msg .= "[{$i}] {$v['time']}\n{$v['content']}\n";
			$i++;
		}
		$msg = trim($msg);
	}else{
		$msg = '查询失败';
	}
	$reply = YdWxMsg::constructTextReplyMsg($params['fromUser'], $params['toUser'], $msg);
	return $reply;
}

//翻译
function fanyi($params) {
	//{"from":"en","to":"zh","trans_result":[{"src":"today","dst":"\u4eca\u5929"},{"src":"tomorrow","dst":"\u660e\u5929"}]}
	//  dst:译文，src:原文 只有发生错误时，返回的JSON中才包含error_code和error_msg字段，成功返回的结果中不会包含这两个字段。
	//分段：待翻译内容中的换行符’\n’经urlencode后变为%0A）
	//当API服务发生错误时，返回数据格式如下
	//{"error_code": "52001","error_msg": "TIMEOUT","from": "auto",    "to": "auto",    "query": "he's"}
	$lang = array(
			'中文'=>'zh'	,        '英语'=>'en',          '日语'=>'jp',       '韩语'=> 'kor',
			'西班牙语'=>'spa',	 '法语'=> 'fra',         '泰语'=>'th',	   '阿拉伯语'=>'ara',
			'俄罗斯语'=>'ru'	,    '葡萄牙语'	=>'pt',    '粤语'=>'yue',    '文言文'=> 'wyw',
			'德语'=>'de',          '意大利语'	=> 'it',    '荷兰语'=> 'nl'	,   '希腊语'=>'el'
	);
	import('@.Common.YdWxMsg');
	$apiurl = "http://api.fanyi.baidu.com/api/trans/vip/translate";
	$appid  = $GLOBALS['Config']['BAIDU_TRANSLATE_APPID'];
	$apikey = $GLOBALS['Config']['BAIDU_TRANSLATE_APIKEY'];
	$timeout = 30; //超时时间，单位：秒
	$from = 'zh';
	$to = $lang[$params['matches'][1]];
	$myText = $params['matches'][2];
	
	$salt = rand(10000,99999);
	$sign = md5($appid . $myText . $salt . $apikey); //计算签名（appid+q+salt+密钥）
	$p = array('appid'=>$appid,'q'=>$myText, 'salt' => $salt , 'from'=>$from, 'to'=>$to, 'sign'=>$sign);
	$result = yd_curl_post($apiurl, $p, $timeout);
	$result = json_decode($result, true);
	
	//如果翻译出错，返回：{"error_code":"52003","error_msg":"UNAUTHORIZED USER"}
	if( !isset($result['error_code']) ){
		$msg = '';
		$n = count($result['trans_result']);
		for($i=0; $i<$n; $i++){
			$msg .= $result['trans_result'][$i]['dst'];
		}
	}else{
		$code = array(
				'52000'=>'成功',
				'52001'=>'请求超时, 请重试',
				'52002'=>'系统错误, 请重试',
				'52003'=>'未授权用户, 请检查您的appid是否正确',
				'54000'=>'必填参数为空, 请检查是否少传参数',
				'58000'=>'客户端IP非法	检查您填写的IP地址是否正确, 请可修改您填写的服务器IP地址',
				'54001'=>'签名错误, 请请检查您的签名生成方法',
				'54003'=>'访问频率受限，请降低您的调用频率',
				'58001'=>'译文语言方向不支持, 请检查译文语言是否在语言列表里',
				'54004'=>'账户余额不足, 请前往管理控制台为账户充值',
				'54005'=>'长query请求频繁, 请降低长query的发送频率，3s后再试',
		);
		$error_code = $result['error_code'];
		$msg = isset( $code[$error_code] ) ? $code[$error_code] : '翻译异常';
	}
	$reply = YdWxMsg::constructTextReplyMsg($params['fromUser'], $params['toUser'], $msg);
	return $reply;
}

//人品计算
function renpin($params){
	import('@.Common.YdWxMsg');
	$name = $params['matches'][1];
    $a = 0;
	for($i = 0;$i < strlen($name); $i++){
		$a=$a+ord($name[$i]);
	}
	$shuzi=($a*57+77)%100;  //人品数值
	$str = "{$name}的人品得分是{$shuzi}\n";
	if ($shuzi== 0) {
		$msg="你一定不是人吧？怎么一点人品都没有？！";
	} elseif (($shuzi>0)&&($shuzi<=5)) {
		$msg=   "算了，跟你没什么人品好谈的...";
	} else if (($shuzi > 5) && ($shuzi <= 10)) {
		$msg= "是我不好...不应该跟你谈人品问题的...";
	} else if (($shuzi > 10) && ($shuzi <= 15)) {
		$msg= "杀过人没有?放过火没有?你应该无恶不做吧?";
	} else if (($shuzi > 15) && ($shuzi <= 20)) {
		$msg= "你貌似应该三岁就偷看隔壁大妈洗澡的吧...";
	} else if (($shuzi > 20) && ($shuzi <= 25)) {
		$msg= "你的人品之低下实在让人惊讶啊...";
	} else if (($shuzi > 25) && ($shuzi <= 30)) {
		$msg= "你的人品太差了。你应该有干坏事的嗜好吧?";
	} else if (($shuzi > 30) && ($shuzi <= 35)) {
		$msg= "你的人品真差!肯定经常做偷鸡摸狗的事...";
	} else if (($shuzi > 35) && ($shuzi <= 40)) {
		$msg= "你拥有如此差的人品请经常祈求佛祖保佑你吧...";
	} else if (($shuzi > 40) && ($shuzi <= 45)) {
		$msg= "老实交待..那些论坛上面经常出现的偷拍照是不是你的杰作?";
	} else if (($shuzi > 45) && ($shuzi <= 50)) {
		$msg= "你随地大小便之类的事没少干吧?";
	} else if (($shuzi > 50) && ($shuzi <= 55)) {
		$msg= "你的人品太差了..稍不小心就会去干坏事了吧?";
	} else if (($shuzi > 55) && ($shuzi <= 60)) {
		$msg= "你的人品很差了..要时刻克制住做坏事的冲动哦..";
	} else if (($shuzi > 60) && ($shuzi <= 65)) {
		$msg= "你的人品比较差了..要好好的约束自己啊..";
	} else if (($shuzi > 65) && ($shuzi <= 70)) {
		$msg= "你的人品勉勉强强..要自己好自为之..";
	} else if (($shuzi > 70) && ($shuzi <= 75)) {
		$msg= "有你这样的人品算是不错了..";
	} else if (($shuzi > 75) && ($shuzi <= 80)) {
		$msg= "你有较好的人品..继续保持..";
	} else if (($shuzi > 80) && ($shuzi <= 85)) {
		$msg= "你的人品不错..应该一表人才吧?";
	} else if (($shuzi > 85) && ($shuzi <= 90)) {
		$msg= "你的人品真好..做好事应该是你的爱好吧..";
	} else if (($shuzi > 90) && ($shuzi <= 95)) {
		$msg= "你的人品太好了..你就是当代活雷锋啊...";
	} else if (($shuzi > 95) && ($shuzi <= 99)) {
		$msg= "你是世人的榜样！";
	} else if ($shuzi == 100) {
		$msg= "天啦！你不是人！你是神！！！";
	} else {
		$msg= "你的人品竟然负溢出了...我对你无语..";
	}
	$msg = $str.$msg;
	$reply = YdWxMsg::constructTextReplyMsg($params['fromUser'], $params['toUser'], $msg);
	return $reply;
}

//周公解梦
function zhougongjiemeng($params){
	/*
	{
	  "result": [
	    {
	      "title": "梦见妈妈",
	      "content": "母亲是自己生的源头，象征着自己的寿",
	      "type": "人物类"
	    },
	    {
	      "title": "梦见妈妈失踪",
	      "content": "梦见妈妈失踪，意味着最近和妈妈沟通不好",
	      "type": "生活类"
	    },
	  ],
	  "error_code": 0,
	  "reason": "Succes"s
	}
	*/
	import('@.Common.YdWxMsg');
	$meng = urlencode($params['matches'][1]);
	$api = 'http://api.avatardata.cn/ZhouGongJieMeng/LookUp?key=8028ac3d1e204b148226f00881241c23';
	$api .= '&rows=1&dtype=json&keyword='.$meng;
	$data = yd_curl_get($api);
	$data = json_decode($data, true);
	$msg = (0==$data['result']['error_code']) ? strip_tags($data['result'][0]['content']) : '抱歉，周公不能解此梦';
	$reply = YdWxMsg::constructTextReplyMsg($params['fromUser'], $params['toUser'], $msg);
	return $reply;
}

//成语词典
function chengyu($params){
	import('@.Common.YdWxMsg');
	$str = urlencode($params['matches'][1]);
	$api = 'http://api.46644.com/chengyu/?keyword='.$str.'&appkey=1307ee261de8bbcf83830de89caae73f';
    //api: http://apistore.baidu.com/astore/serviceinfo/27463.html
	//{"total":"1","data":[{"name":"冰销叶散","pronounce":"bīng xiāo yè sàn","content":"比喻事物消失瓦解。",
    //"comefrom":"《隋书·越王侗传》：“若王师一临，旧章暂睹，自应解甲倒戈，冰销叶散。”","antonym":"","thesaurus":""}]}
	$json = yd_curl_get($api);
	$json = json_decode ($json, true );
	$msg = "没有查询结果";
	if( isset($json['data'][0]) ){
		$v = $json['data'][0];
		$msg = "成语：{$v['name']}\n拼音：{$v['pronounce']}\n含义：{$v['content']}";
	}
	$reply = YdWxMsg::constructTextReplyMsg($params['fromUser'], $params['toUser'], $msg);
	return $reply;
}

//邮编查询
function youbian($params){
	import('@.Common.YdWxMsg');
	$str = urlencode($params['matches'][1]);
	$api = 'http://api.46644.com/zipcode/?zipcode='.$str.'&appkey=1307ee261de8bbcf83830de89caae73f';
	// "518057：广东省 深圳市 南山区"
	/*
	{"error": "0",
		"msg": "浙江省杭州市西湖区 文一路80号浙江省省委党校图书馆(杂志)
		浙江省杭州市西湖区 余杭塘路388号浙江医科大学图书馆(杂志)
		浙江省杭州市西湖区 文二路125号浙江幼儿师范学校图书馆(杂志)
		浙江省杭州市西湖区 教工路149号杭州市商业学院图书馆(杂志)
		浙江省杭州市西湖区 花园南村
		"} */
	$json = yd_curl_get($api);
	$data = json_decode ($json, true );
	$msg = isset( $data['msg'] ) ? str_ireplace(array('<br>','<br/>','<br />'), "\n",rtrim($data['msg'],'<br />')) : '没有查询结果';
	$reply = YdWxMsg::constructTextReplyMsg($params['fromUser'], $params['toUser'], $msg);
	return $reply;
}



//谜语
function miyu($params){
	import('@.Common.YdWxMsg');
	$list = array(
			array('title'=>'女人生孩子（打一成语）', 'answer'=>'血口喷人'),
			array('title'=>'两点钟（打一字）', 'answer'=>'冲'),
	);
	$total = count($list);
	if( is_numeric($params['matches'][1])){
		$i = $params['matches'][1];
		if( $i < 0 || $i > $total){
			$msg = '谜底不存在';
		}else{
			$msg = '谜底:'.$list[$i]['answer'];
		}
	}else{
		$index = rand(0, $total-1);
		$msg = "谜面：{$list[$index]['title']}\n回复 谜语{$index} 查看谜底";
	}
	$reply = YdWxMsg::constructTextReplyMsg($params['fromUser'], $params['toUser'], $msg);
	return $reply;
}

//完善资料
function wanshanziliao($params){
	import('@.Common.YdWxMsg');
	$m = D('Admin/Member');
	$m->updateWxUser($params['fromUser']);
	$reply = YdWxMsg::constructTextReplyMsg($params['fromUser'], $params['toUser'], $params['msgtpl']);
	return $reply;
}

//绑定会员资料
function bindmember($params){
	import('@.Common.YdWxMsg');
	$msg = $params['msgtpl'];
	$url = WxBindMemberUrl();
	if( stripos($url, '?') !== false ){
		$url .= '&fu='.$params['fromUser'];
	}else{
		$url .= '?fu='.$params['fromUser'];
	}
	$msg .= "，请点击以下链接绑定：\n\r<a href='{$url}'>立即绑定</a>";
	$reply = YdWxMsg::constructTextReplyMsg($params['fromUser'], $params['toUser'], $msg);
	return $reply;
}

function _ajaxsns($msg){
	/*
	 智能API接口
	支持功能：天气、翻译、藏头诗、笑话、歌词、计算、域名信息/备案/收录查询、IP查询、手机号码归属、人工智能聊天
	接口地址：http://api.ajaxsns.com/api.php?key=free&appid=0&msg=关键词
	　　　　　key　固定参数free
	　　　　　appid 设置为0，表示智能识别，其它数字暂不可能识别
	　　　　　msg　关键词，请参考下方参数示例，该参数可智能识别，该值请经过 urlencode 处理后再提交
	返回信息：{"result":0,"content":"内容"}
	温馨提示：
	本API完全免费使用（建议频率控制在20次/10秒以内），我们将尽可能提供最快最稳定的免费服务器　赞助我们
	因本站服务器资源有限，同时建议有条件的朋友可以购买该API数据及程序
	================参数说明================
	天气：msg=天气深圳
	中英翻译：msg=翻译i love you
	　藏头诗：msg=藏头诗春节快乐
	　歌词⑴：msg=歌词后来
	　歌词⑵：msg=歌词后来-刘若英
	　　笑话：msg=笑话
	　计算⑴：msg=计算1+1*2/3-4
	　计算⑵：msg=1+1*2/3-4
	　域名⑴：msg=域名ajaxsns.com
	　域名⑵：msg=ajaxsns.com
	　ＩＰ⑴：msg=归属127.0.0.1
	　ＩＰ⑵：msg=127.0.0.1
	　手机⑴：msg=归属13430107662
	　手机⑵：msg=13430107662
	智能聊天：msg=你好
	*/
	$param=array("key"=> "free", "appid" =>"0", "msg"=>$msg);
	$result =yd_curl_get("http://api.ajaxsns.com/api.php", $param);
	$json=json_decode($result);
	if($json->result==0){
		$content=str_replace("{br}","\n",$json->content);
		//替换广告信息:九酷音乐网<a href="/cgi-b939912" target="_blank"></a>提供
		$pos = stripos($content, "九酷音乐网");
		if( $pos !== false ){
			$content = substr($content, 0, $pos);
		}
	}else{
		$content="从前有座山,山上有座庙,庙里有个小和尚,-^-,连接出错,请稍后再试,^_^.";
	}
	return $content;
}

//指令帮助
function zhilingbangzhu($params){
	import('@.Common.YdWxMsg');
	$applist = include CONF_PATH.'wxapp.php';  //加载微信配置文件
	$i = 1;
	$msg = "指令帮助：\n(约定：[]表选填 ()表必填 |表或者)\n";
	$keyword = $params['matches'][1];
	if( empty($keyword)){
		foreach ($applist as $k=>$v){
			$msg .= "[{$i}]{$v['name']}\n{$v['description']}\n\n";
			$i++;
		}
	}else{
		foreach ($applist as $k=>$v){
			if( stripos($v['name'], $keyword) !== false){
				$msg .= "[{$i}]{$v['name']}\n{$v['description']}\n\n";
				$i++;
			}
		}
		if($i==1){
			$msg = "没有找到符合条件的指令帮助!";
		}
	}
	//$msg过长可能导致无法显示
	$msg = rtrim($msg, "\n");
	$reply = YdWxMsg::constructTextReplyMsg($params['fromUser'], $params['toUser'], $msg);
	return $reply;
}


//地理位置应用start＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝
function lbspre(&$params){
	import('@.Common.YdWxMsg');
	if( empty($params['parameter']['longitude']) ){
		$m = D('Admin/Member');
		$position = $m->wxGetPosition($params['fromUser']);
		if(empty($position)) {
			$params['parameter']['errormsg'] = "请先发送位置消息！操作步骤：点击微信底部的'+'号，选择'位置'，位置识别后，点击'发送'按钮";
			return false;
		}
		$params['parameter']['longitude'] = $position[1]; //经度
		$params['parameter']['latitude'] = $position[2];  //纬度
	}
	return true;
}

function lbsplace($params){
	if( lbspre($params) === false ) {
		$msg = $params['parameter']['errormsg'];
	}else{
		$longitude = $params['parameter']['longitude']; //经度
		$latitude = $params['parameter']['latitude'];  //纬度
		$msg = "您当前的位置：\n经度:{$longitude}\n纬度:{$latitude}\n你可以输入'附近+关键词'查找附近区域。如：附近酒店";
	}
	$reply = YdWxMsg::constructTextReplyMsg($params['fromUser'], $params['toUser'], $msg);
	return $reply;
}

function lbsnear($params){
	if( lbspre($params) === false ) {
		$msg = $params['parameter']['errormsg'];
	}else{
		//参数
		$longitude = $params['parameter']['longitude']; //经度
		$latitude = $params['parameter']['latitude'];  //纬度
		//搜索半径，默认为3000m,好像搜索半径无效
		$radius = empty($params['matches'][1]) ? 3000 : $params['matches'][1]; 
		$keyword = $params['matches'][2];
		$keyword1 = urlencode($params['matches'][2]);  //匹配关键词

		$api = "http://api.map.baidu.com/place/v2/search?ak=&output=json&query=$keyword1";
		$api .= "&page_size=10&page_num=0&scope=2&location={$latitude},{$longitude}&radius=$radius";
	
		$json = yd_curl_get($api);
		$json = json_decode($json, true );
		if( strtolower($json['status']) == '0'){
			$i = 1;
			$msg = "您附近{$radius}米范围的{$keyword}:\n显示前10条\n";
			foreach ($json['results'] as $p){
					$tel = $p['telephone'];
					$msg .= "{$i}.{$p['name']}\n";
					if( !empty($tel)) $msg .="电话：{$tel}\n";
					$msg .= "地址：{$p['address']}\n距离：{$p['detail_info']['distance']}m\n\n";
					$i++;
			}
			if($i==1) $msg = "抱歉，没有找到相关信息！";
		}else{
			$msg ="抱歉，没有找到相关信息！";
		}
	}
	//最大长度2048字节
	$msg = rtrim($msg, "\n");
	$reply = YdWxMsg::constructTextReplyMsg($params['fromUser'], $params['toUser'], $msg);
	return $reply;
}
//地理位置应用end＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝

function cmsauthorize($params){
	import('@.Common.YdWxMsg');
	$str = $params['matches'][1];
    $m = D('Admin/Customer');
    $data = $m->GetAuthorize( $str );
    if( $data === false){
    	$msg = "您所查询的域名没有取得商业授权！";
    }else{
    	$msg = "此域名已取得商业授权\n授权域名：$str\n授权日期：{$data['Date']}\n有效期：永久授权";
    }
	$reply = YdWxMsg::constructTextReplyMsg($params['fromUser'], $params['toUser'], $msg);
	return $reply;
}

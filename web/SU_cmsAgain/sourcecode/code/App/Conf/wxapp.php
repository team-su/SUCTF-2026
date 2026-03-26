<?php
if (!defined('APP_NAME')) exit();
return array (
 		'/^([\w\W]+)(天气)$/i'=>array(
 				'name'=>'天气查询', 'type'=>'3', 'function'=>'weather',
 				'parameter'=>array('days'=>2, 'city'=>'北京', 'errormsg'=>'抱歉，没有天气数据或系统繁忙，请稍后再试！'),
 				'msgtype'=>'text', 'msgtpl'=>'',
 				'description'=>"格式：(城市名称)天气\n举例：长沙天气",
 		),
		'/^([\w\W]+)快递([\w\W]+)$/'=>array(
				'name'=>'快递查询','type'=>'3', 'function'=>'kuaidi',
				'description'=>"格式：(快递公司代码)快递(快递单号)\n举例：tiantian快递1238898898",
		),
		'/^13[0-9]{1}[0-9]{8}$|15[0189]{1}[0-9]{8}$|189[0-9]{8}$/'=>array(
				'name'=>'手机归属地查询', 'type'=>'3', 'function'=>'guishudi',
				'parameter'=>array(),
				'description'=>"格式：(手机号码)\n举例：13285877889",
		),
		'/^高([\d]+)重([\d]+)$/'=>array(
				'name'=>'健康指数查询', 'type'=>'3', 'function'=>'jiankang',
				'description'=>"格式：高(身高cm)重(体重kg)\n举例：高175重75",
		),
		'/^([\w\W]*)翻译([\w\W]+)$/'=>array(
				'name'=>'翻译', 'type'=>'3', 'function'=>'fanyi',
				'description'=>"格式：[目标语言]翻译(待翻译的中文)，支持语言：英语、日语、韩语、西班牙语、法语、泰语、阿拉伯语、俄罗斯语、葡萄牙语、德语、意大利语、荷兰语、希腊语\n举例：俄语翻译我爱你",
		),
		'/^([\w\W]+)人品$/'=>array(
				'name'=>'人品计算','type'=>'3', 'function'=>'renpin',
				'description'=>"格式：(姓名)人品\n举例：李白人品",
		),
		'/^梦见([\w\W]+)$/'=>array(
				'name'=>'周公解梦','type'=>'3', 'function'=>'zhougongjiemeng',
				'description'=>"格式：梦见(梦的内容)\n举例：梦见下雨",
		),
		'/^成语([\w\W]+)$/'=>array(
				'name'=>'成语词典','type'=>'3', 'function'=>'chengyu',
				'description'=>"格式：成语(成语名称)\n举例：成语百步穿杨",
		),
		'/^邮编([\w\W]+)$/'=>array(
				'name'=>'邮编查询','type'=>'3', 'function'=>'youbian',
				'description'=>"格式：邮编(6位邮政编码或地址)\n举例：邮编北京、邮编410000",
		),
		
		/*
		'/^([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+)/i'=>array(
				'name'=>'域名授权查询','type'=>'3', 'function'=>'cmsauthorize',
				'description'=>"格式：(域名)\n举例：youdiancms.com",
		),
		
		'/^([\w\W]+)授权([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+)/i'=>array(
				'name'=>'域名授权','type'=>'3', 'function'=>'cmssetauthorize',
				'description'=>"格式：(密码)授权(域名)\n举例：123456授权youdiancms.com",
		),
		*/
		'/^(\d+)dj([\d]{7,16})a([\d]{1,1})$/i'=>array(
				'name'=>'商家兑奖','type'=>'3', 'function'=>'shangjiaduijiang',
				'description'=>"格式：(密码)dj(手机号码)a1\n举例：123456dj13582388899a1",
		),
		
		'/^谜语(\d+)$/'=>array(
				'name'=>'谜语答案','type'=>'3', 'function'=>'miyu',
				'description'=>"格式：谜语(谜语编号)\n举例：谜语8",
		),
		'/^绑定|BD$/i'=>array(
				'name'=>'绑定','type'=>'3', 'function'=>'bindmember',
				'msgtpl'=>'用于将微信绑定会员，绑定后，可在电脑端和手机端登陆',
				'description'=>"格式：绑定\n备注：主要用于微信绑定会员，绑定后，可在电脑端登陆",
		),
		'/^([\w\W]*)(帮助|\?)$/i'=>array(
				'name'=>'查看指令帮助','type'=>'3', 'function'=>'zhilingbangzhu',
				'description'=>"格式：[指令关键词]帮助|?\n举例：?、天?、天帮助\n备注：指令关键词支持模糊检索",
		),
		
		'谜语'=> array('name'=>'谜语', 'type'=>'3', 'function'=>'miyu', 'description'=>"格式：谜语"),
		'笑话'=> array('name'=>'笑话', 'type'=>'3', 'function'=>'xiaohua', 'description'=>"格式：笑话"),
 		'大转盘'=> array('name'=>'大转盘抽奖活动', 'type'=>'1', 'function'=>'dazhuanpan', 'description'=>"格式：大转盘\n备注：返回最新大转盘应用"),
 		'刮刮卡'=> array('name'=>'刮刮卡刮奖活动', 'type'=>'1', 'function'=>'guaguaka', 'description'=>"格式：刮刮卡\n备注：返回最新刮刮应用"),
		'兑奖'=> array('name'=>'活动兑奖', 'type'=>'1', 'function'=>'duijiang', 'description'=>"格式：兑奖\n备注：查询活动中奖信息"),
		'调查'=> array('name'=>'微调查', 'type'=>'5', 'function'=>'diaocha', 'description'=>"格式：调查\n备注：返回所有微调查"),
		'投票'=> array('name'=>'微投票', 'type'=>'2', 'function'=>'toupiao', 'description'=>"格式：投票\n备注：返回所有微投票"),
		'会员卡'=> array('name'=>'微会员卡', 'type'=>'6', 'function'=>'huiyuanka', 'description'=>"格式：会员卡\n备注：返回微会员卡"),
		
		//关键词不用于直接录入
		'我的位置'=> array('name'=>'我的位置', 'type'=>'4', 'function'=>'lbsplace',
				'description'=>"格式：我的位置\n备注：必须先发送位置消息"),
		'/^附近(\d*)([\w\W]+)$/'=> array('name'=>'附近位置查询', 'type'=>'4', 'function'=>'lbsnear', 
				'parameter'=>array('radius'=>'3000',),
				'description'=>"格式：附近[搜索半径m](关键词)\n举例：附近500酒店:表示附近500米范围的酒店，若省略搜索范围，则默认为3000米"),
);
<?php
if (!defined('APP_NAME')) exit();
return array (
	  'APP_AUTOLOAD_PATH' => '@ORG.Util,@ORG.Io',
	  'APP_GROUP_LIST' => 'Home,Admin,Member,Wap',
	  'DEFAULT_GROUP' => 'Home',
	  'APP_SUB_DOMAIN_DEPLOY'=>1, //开启子域名部署,子域名规则定义文件见domain.php
	  'URL_CASE_INSENSITIVE' => true,
	  'LOAD_EXT_CONFIG' => 'db,copy',
	  'LOAD_EXT_FILE' => 'tag,extend',
	  'TMPL_CLOSE'=>'close_1.html',
	  'TMPL_404'=>'404_1.html',
	  
	  'SHOW_PAGE_TRACE'  =>false,   //显示页面Trace信息，对调试模式和部署模式均有效
	  'SHOW_RUN_TIME'=>false,  //显示运行时间
	  'SHOW_ADV_TIME'=>false,  //显示详细运行时间
	  'SHOW_DB_TIMES'=>false, 
	  'SHOW_CACHE_TIMES'=>false,
	  'SHOW_USE_MEM'=>false,
	  'SHOW_LOAD_FILE'=>false,
	  
   'TMPL_STRIP_SPACE' =>false,  //是否去除html中的空格
   //'TAGLIB_LOAD'=>true,
   //'TAGLIB_PRE_LOAD' => 'YouDian',  //部署模式必须去除
   'TAGLIB_BUILD_IN' => 'youDian,cx',
	'TMPL_CACHE_ON'=>true,
	'TMPL_CACHE_TIME'=>'0',   //模板缓存有效期 0为永久
	'TMPL_DETECT_THEME'=>true,
	'URL_ROUTER_ON'  => true,   //是否开启URL路由
	
	'URL_ROUTE_RULES'  => array(
			//管理后台登录
			'admin$'=>'admin/public/login',

			//wap网站路由规则
			'/^wap[\/]?(en|cn)?$/i' => 'wap/index/index?l=:1', //用于Wap频道,匹配：www.csyoudian.com/wap
			'/^wap\/(en\/|cn\/)?order(\d+)$/i'=>'wap/channel/index?id=order&infoid=:2&l=:1', //d:匹配在线订购，参数id,infoid必须为小写
			'/^wap\/(en\/|cn\/)?resume(\d+)$/i'=>'wap/channel/index?id=resume&jobid=:2&l=:1', //匹配投递简历，参数id,infoid必须为小写
			'/^wap\/(en\/|cn\/)?([\w-]+)$/i' => 'wap/channel/index?id=:2&l=:1', //用于不带参数的频道
			'/^wap\/(en\/|cn\/)?(?!channel|app|public|api)[\w-]+\/([\w-]+)$/i'=>'wap/info/read?id=:2&l=:1',  //信息阅读
			
			//Home分组路由规则
			'/^(en|cn)$/i' => 'index/index?l=:1', //用于不带参数的频道
			'/^(en\/|cn\/)?order(\d+)$/i'=>'channel/index?id=order&infoid=:2&l=:1', //在线订购，GET参数必须为小写
			'/^(en\/|cn\/)?resume(\d+)$/i'=>'channel/index?id=resume&jobid=:2&l=:1', //投递简历，GET参数必须为小写
			'/^(en\/|cn\/)?([\w-]+)$/i' => 'channel/index?id=:2&l=:1', //用于不带参数的频道
			'/^(en\/|cn\/)?(?!channel|app|public|api)[\w-]+\/([\w-]+)$/i'=>'info/read?id=:2&l=:1',  //信息阅读
	),
	'COOKIE_PREFIX'         => 'youdian',  //Cookie前缀 避免冲突
	'WX_URL_APPEND'    => 'wxref=mp.weixin.qq.com',
	'XUaCompatible' =>'',
    'UcUrl'=>'https://u.youdiansoft.cn'
);
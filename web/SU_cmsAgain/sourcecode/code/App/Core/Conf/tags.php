<?php
if (!defined('THINK_PATH')) exit();
// 系统默认的核心行为扩展列表文件
return array(
    'app_init'=>array(),
    'app_begin'=>array( //因为项目中也可能用到语言行为,最好放在项目开始的地方
    	'CheckLang', //检测语言, 一定放在ReadHtmlCache前，否则会导致静态缓存有问题
    	'BadIP',  //ip过滤
    	'StartWeb', //启动Web
    	'ReadHtmlCache', // 读取静态缓存
    ),
    'route_check'=>array(
        'CheckRoute', // 路由检测
    ), 
    'app_end'=>array(),
    'path_info'=>array(),
    'action_begin'=>array(),
    'action_end'=>array(),
    'view_begin'=>array(),
    'view_template'=>array(
        'LocationTemplate', // 自动定位模板文件
    ),
    'view_parse'=>array(
        'ParseTemplate', // 模板解析 支持PHP、内置模板引擎和第三方模板引擎
    ),
    'view_filter'=>array(
        'ContentReplace', // 模板输出替换
        'TokenBuild',   // 表单令牌
        'WriteHtmlCache', // 写入静态缓存
        'ShowRuntime', // 运行时间显示
        'BadWords', // 模板输出替换
    ),
    'view_end'=>array( 'ShowPageTrace', /*页面Trace显示*/ ),
    
    'info_content'=>array(
    		'AutoLink', //关键词自动生成链接
    ),
    'channel_content'=>array(
    		'AutoLink', //关键词自动生成链接
    ),
    'baseaction_init'=>array(
    	
    ),
);
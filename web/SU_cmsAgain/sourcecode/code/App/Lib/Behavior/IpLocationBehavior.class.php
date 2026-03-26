<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */
if (!defined('APP_NAME')) exit();
class IpLocationBehavior extends Behavior {
    // 行为参数定义（默认值） 可在项目配置中覆盖
    protected $options   =  array(
            'IP_LOCATION_ON'        => false,         // 默认开启IP定位功能
            'VAR_IP'          => 'myip',		                // 默认ip切换变量
    		'VAR_COUNTRY'   => 'mycountry',		// 默认country切换变量
    		'VAR_PROVINCE'   => 'myprovince',		// 默认province切换变量
    		'VAR_CITY'   => 'mycity',		                // 默认city切换变量
            'LOCATION' =>array('Country'=>'中国','Province'=>'湖南','City'=>'长沙'),   //位置默认值
        );

    // 行为扩展的执行入口必须是run, $params为一个BaseAction对象
    public function run(&$params){
         if( !C('IP_LOCATION_ON') ) return true;
         if(isset($_REQUEST[C('VAR_IP')])){ //通过GET传递IP
                $ip = trim($_REQUEST[C('VAR_IP')]);// url中设置了语言变量
                $location = yd_ip2location($ip);
                if( empty($location['City']) ) $location = C('LOCATION');
                cookie('mycountry', $location['Country']);
                cookie('myprovince', $location['Province']);
                cookie('mycity', $location['City']);
         }elseif( isset($_REQUEST[C('VAR_CITY')]) ){ //通过GET传递City
         		//编码转换，防止直接在浏览器中输入http://localhost/test?mycity=永州&myprovince=hu南
         		//出现乱码
	         	//$location['City'] = iconv("gb2312","utf-8", $_REQUEST[C('VAR_CITY')]);
         	    $location['City'] = trim($_REQUEST[C('VAR_CITY')]);
	         	$location['Province'] = trim($_REQUEST[C('VAR_PROVINCE')]);
	         	$location['Country'] = trim($_REQUEST[C('VAR_COUNTRY')]);
	         	cookie('mycountry', $location['Country']);
	         	cookie('myprovince', $location['Province']);
	         	cookie('mycity', $location['City']);
         }elseif( cookie('mycity') ){ // 获取上次用户的选择
                $location['City'] = cookie('mycity');
                $location['Province'] = cookie('myprovince');
                $location['Country'] = cookie('mycountry');
         }else{  //获取当前用户的位置
         	   $ip = get_client_ip();
         	   $location = yd_ip2location($ip);
         	   if( empty($location['City']) ) $location = C('LOCATION');
         	   cookie('mycountry', $location['Country']);
         	   cookie('myprovince', $location['Province']);
         	   cookie('mycity', $location['City']);
         }
         
		if( method_exists($params, 'assignValue') ){ //对Action对象赋值
			// $s = $params->getActionName(); //这里$params无法调用任何基类Action的方法, 因为getActionName是protected限定
			$params->assignValue('Location', $location);
		}
    }
}
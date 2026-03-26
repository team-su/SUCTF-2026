<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
if (!defined('APP_NAME')) exit();
class BadIPBehavior extends Behavior {
    public function run(&$content) {
    	//admin分组不过滤，防止死锁
    	if( strtolower(GROUP_NAME) == 'admin' ) return;
        $IsEnable = intval($GLOBALS['Config']['BadIPEnable']);
        if(empty($IsEnable)) return;
    	$data = $GLOBALS['Config']['WEB_BAD_IP'];
    	if(empty($data)) return;
    	$data = str_replace(array("\r\n","\r"), "\n", $data);
    	$data = explode ("\n", $data);
    	$ip = get_client_ip(); //获取客户端ip地址
    	
    	if( $this->in_ip($ip, $data) ){ //属于过滤ip
    		exit();
    	}
    }
    
    /**
     * 判断当前ip是否属于指定列表中,支持通配符*
     * @param string $ip
     * @param array $list
     */
    public function in_ip($ip, $list){
    	$ip1 = explode('.', $ip);
    	foreach($list as $v){
    		$ip2 = explode('.', $v);
    		if( $this->is_equal($ip1, $ip2 ) ){
    			return true;
    		}
    	}
    	return false;
    }
    
    public function is_equal($ip1, $ip2 ){
		$n = is_array($ip2) ? count($ip2) : 0;
    	if( $n != 4) return false;
    	if( ( ($ip1[0]==$ip2[0]) || $ip2[0] == '*' ) &&
    			( ($ip1[1]==$ip2[1] ) || $ip2[1] == '*' ) &&
    			( ($ip1[2]==$ip2[2] ) || $ip2[2] == '*' ) &&
    			( ($ip1[3]==$ip2[3] ) || $ip2[3] == '*' ) ){
    		 return true;
    	}
    	return false;
    }
   
}
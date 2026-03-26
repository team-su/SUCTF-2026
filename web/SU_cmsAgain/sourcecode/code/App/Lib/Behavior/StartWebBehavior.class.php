<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
if (!defined('APP_NAME')) exit();
class StartWebBehavior extends Behavior {
    public function run(&$content) {
    	$this->_checkSiteStatus();  //检查网站状态
    	$this->_clearLog();
    	$this->_makeSitemap();  //生成网站地图
        $this->_configSafe();
    	$this->_saveHistory();
    	$this->autoConfirmReceipt(); //自动确认收货
    	$this->_wapAutoRedirect(); //是否自动跳转到Wap网站判断
    	$this->_wapPcAccess();       //是否禁止电脑访问手机网站
        $this->_siteDispatch();
    }

    private function _configSafe(){
        make_secure_file(array(APP_DATA_PATH, APP_DATA_PATH.'zip/', APP_DATA_PATH.'sql/', RUNTIME_PATH) ); //生成安全文件
        //解决PHPSESSID部位httponly的问题
        $httponly  = C('COOKIE_HTTPONLY');
        if($httponly){
            ini_set("session.cookie_httponly", 1);
        }
        $secure = yd_is_https() ? true : false;
        if($secure){
            ini_set("session.cookie_secure", 1);
            header("X-XSS-Protection: 1; mode=block");
            header("X-Content-Type-Options: nosniff");
            header("X-Frame-Options: SAMEORIGIN");
        }
    }
    
    //保存信息浏览记录到cookie里
    private function _saveHistory(){
    	if( MODULE_NAME == 'Info' && ACTION_NAME == 'read' && is_numeric($_GET['id'])){
    		//存在小bug 不是商品的ID，也加入进去了
    		$h = YdHistory::getInstance();
    		$h->push( $_GET['id'] );
    	}
    }
    
    //清除runtime下面的日志文件，防止充满空间
    private function _clearLog(){
    	//仅在调试模式下清除
    	$logDir = RUNTIME_PATH."Logs";
    	if( APP_DEBUG && is_dir($logDir) ){
    		$max = 50; //50M字节
    		$size = getdirsize( $logDir ) / (1024*1024);
    		if( $size > $max){
    			//不能直接删除log目录，thinkphp不会重新生成
    			foreach (glob($logDir.'/*.log') as $filename) {
    				@unlink($filename);
    			}
    		}
    	}
    }
    
    /**
     * 检查网站状态
     */
    private function _checkSiteStatus(){
    	//关闭网站不影响API的调用
    	if( MODULE_NAME == 'Api' ) {
    		return;
    	}
    	$an = strtolower(ACTION_NAME);
    	//不拦截支付回调，app和小程可能会使用
    	if( MODULE_NAME== 'Public' && $an=='pay') {
    		return;
    	}
    	
    	$gn = strtolower(GROUP_NAME); //当前分组名称
    	//启动时判断网站是否关闭==========================================================
    	if( $gn != 'admin' ){
    		if( $GLOBALS['Config']['WEB_STATUS'] == 0 ){
    			$html = $GLOBALS['Config']['WEB_CLOSE_REASON'];
    			if(empty($html)){ //如果没有设置显示文字，则直接返回404页面
	    			header('HTTP/1.1 404 Not Found');
	    			exit();
    			}else{
    				header("Content-Type:text/html; charset=utf-8");
    				exit($html);
    			}
    		}
    	}
    }
    
    /**
     * 是否自动跳转到Wap网站判断
     */
    private function _wapAutoRedirect(){
    	if( MODULE_NAME == 'Api' ) return;
    	$gn = strtolower(GROUP_NAME); //当前分组名称
    	//自动跳转到Wap网站判断
    	//只有Home分组才自动跳转到Wap
    	if( $gn == 'home' && 1==$GLOBALS['Config']['WAP_STATUS']){
    		$wapAutoRedirect = $GLOBALS['Config']['WAP_AUTO_REDIRECT'];
    		if($wapAutoRedirect == 1){
    			/*
    			 import("@.Common.MobileDetect");
    			$d= new Mobile_Detect;
    			if( $d->isMobile() ){ //终端设备是Mobile
    			redirect(__APP__.'/Wap'); //自动跳转到Wap分组
    			}
    			*/
    			if( yd_is_mobile() ){ //终端设备是Mobile
    				$list = array('Channel', 'Info'); //只有这2个模块才自动跳转
    				$wapDomain = get_wap_domain();
    				$redirectUrl = get_current_protocal();
    				if(  in_array(MODULE_NAME, $list) && !empty($wapDomain) ){
						if ($_SERVER["SERVER_PORT"] != "80") {
							$redirectUrl .= $wapDomain.":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
						} else {
							$redirectUrl .= $wapDomain.$_SERVER["REQUEST_URI"];
						}
    				}else{
    					$redirectUrl = !empty($wapDomain) ? $redirectUrl.$wapDomain : __APP__.'/Wap';
    				}
    				redirect( $redirectUrl );
    			}
    		}
    	}
    }
    
    /**
     * 是否禁止电脑访问手机网站
     */
    private function _wapPcAccess(){
    	if( isset($_REQUEST['IsApi']) ) return;
    	$gn = strtolower(GROUP_NAME); //当前分组名称
    	//是否禁止电脑访问手机网站（1：禁止，0：允许）================
    	//只有wap分组才有效
    	if( $gn == 'wap' ){
    		$wapPcAccess = $GLOBALS['Config']['WAP_PC_ACCESS'];
    		if($wapPcAccess == 1){
    			/*
    			 import("@.Common.MobileDetect");
    			$d= new Mobile_Detect;
    			if( !$d->isMobile() ){
    			$url = (__APP__=='')? '/' : __APP__;
    			redirect($url); //自动跳转到Home分组首页
    			}
    			*/
    			if( !yd_is_mobile() ){
    				$url = (__APP__=='')? '/' : __APP__;
    				//bug: 当手机站绑定单独的域名时，以上$url返回的也是：/，所以存在问题
    				//redirect($url); //自动跳转到Home分组首页
    				header("HTTP/1.1 404 Not Found");  //使HTTP返回404状态码
    				exit();
    			}
    		}
    	}
    }
    
    /**
     * 生成网站地图
     */
    private function _makeSitemap(){
    	$mapDir = APP_DATA_PATH.'map/';
    	//如果目录不存在则创建目录
    	if(!is_dir($mapDir)){
    		mk_dir($mapDir);
    	}
    	$files = array($mapDir.'sitemap.xml', $mapDir.'sitemap.txt', $mapDir.'sitemap.html');
    	if( $GLOBALS['Config']['SITEMAP_ENABLE'] == 1){
    		$cacheTime = $GLOBALS['Config']['SITEMAP_TIME'];
    		foreach ($files as $f){
    			//只要有一个地图文件不存在或过期，就全部重新生成
    			if( !file_exists($f) || time() > filemtime($f) + $cacheTime ){
    				makeSitemap();
    				break;
    			}
    		}
    	}else{
    	    /* 未启用生成地图就不删除地图，在后台人工删除
    		foreach ($files as $f){
    			//只要有一个地图文件不存在或过期，就全部重新生成
    			if( file_exists($f) ){
    				@unlink($f);
    			}
    		}
    	    */
    	}
    }
    
    /**
     * 系统自动确认收货(一天只运行一次)
     */
    private function autoConfirmReceipt(){
    	$days = intval($GLOBALS['Config']['AUTO_RECEIVE_DAYS']); //自动收货时间
    	$lockFileName = RUNTIME_PATH.date('Ymd').'.auto';
    	if($days > 0 && !file_exists($lockFileName)){
    		$m = D('Admin/Order');
    		$m->autoConfirmReceipt();
    		@touch( $lockFileName ); //创建锁定文件，保证每天仅执行一次自动确认收货操作
    	}
    }

    /**
     * 多城市站点路由
     */
    private function _siteDispatch(){
        if(MODULE_NAME == 'Api') return;
        $gn = strtolower(GROUP_NAME); //当前分组名称
        if(!isset($GLOBALS['Config']['SiteEnable'])) return;
        $SiteEnable = $GLOBALS['Config']['SiteEnable'];
        if($gn=="admin" || $gn=='member' || empty($SiteEnable)) return;
        $host    = strtolower($_SERVER['HTTP_HOST']);
        if(!preg_match("/^[:\.a-zA-Z0-9_-]+$/i", $host)){
            return;
        }
        
        $m = D('Admin/Site');
        $where = "IsEnable=1 AND SiteDomain='{$host}'";

        $data = $m->where($where)->find();
        if(!empty($data)){
            define('SITE_ID', $data['SiteID']);
            define('SITE_NAME', $data['SiteName']);
            if(!empty($data['SiteTitle'])){
                $GLOBALS['Config']['TITLE'] = $data['SiteTitle'];
            }
            if(!empty($data['SiteKeywords'])){
                $GLOBALS['Config']['KEYWORDS'] = $data['SiteKeywords'];
            }
            if(!empty($data['SiteDescription'])){
                $GLOBALS['Config']['DESCRIPTION'] = $data['SiteDescription'];
            }
        }else{
            define('SITE_ID', 0);
            define('SITE_NAME', '');
        }
    }
}
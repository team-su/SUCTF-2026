<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */
class SeoAction extends AdminBaseAction {
	private $_mapDir = "";
	function _initialize(){
		$this->_mapDir = APP_DATA_PATH.'map/';
		//如果目录不存在则创建目录
		if(!is_dir($this->_mapDir)){
			mk_dir($this->_mapDir);
		}
		parent::_initialize();
	}
	
	/**
	 * 搜索引擎登录
	 */
	function SearchEngineLogin(){
		header("Content-Type:text/html; charset=utf-8");
		$Seo = array(
		array('ID'=>1, 'Name'=>'百度', 'Url'=>'https://www.baidu.com','LogoUrl'=>'http://www.baidu.com/search/img/logo.gif','LoginUrl'=>'http://www.baidu.com/search/url_submit.html'),
		//array('ID'=>2, 'Name'=>'百度单个网页提交', 'Url'=>'http://zhanzhang.baidu.com/sitesubmit','LogoUrl'=>'http://www.baidu.com/search/img/logo.gif','LoginUrl'=>'http://zhanzhang.baidu.com/sitesubmit'),
		//array('ID'=>3, 'Name'=>'百度博客提交', 'Url'=>'http://ping.baidu.com/ping.html','LogoUrl'=>'http://www.baidu.com/search/img/logo.gif','LoginUrl'=>'http://ping.baidu.com/ping.html'),
		//array('ID'=>4, 'Name'=>'谷歌', 'Url'=>'http://www.google.com.hk','LogoUrl'=>'http://www.google.com.hk/images/logo.gif','LoginUrl'=>'https://www.google.com/webmasters/tools/submit-url'),
		array('ID'=>9, 'Name'=>'360搜索', 'Url'=>'https://www.so.com','LogoUrl'=>'https://p.ssl.qhimg.com/t01e92920a7b90351cc.png','LoginUrl'=>'http://info.so.360.cn/site_submit.html'),
		array('ID'=>5, 'Name'=>'搜狗', 'Url'=>'https://www.sogou.com','LogoUrl'=>'http://www.sogou.com/images/logo_l.gif','LoginUrl'=>'http://fankui.help.sogou.com/index.php/web/web/index?type=1'),
		//array('ID'=>6, 'Name'=>'搜搜', 'Url'=>'http://www.soso.com','LogoUrl'=>'http://soso.qstatic.com/30d/img/logo/logo_index.png','LoginUrl'=>'http://www.soso.com/help/usb/urlsubmit.shtml'),
		//array('ID'=>7, 'Name'=>'雅虎', 'Url'=>'http://search.cn.yahoo.com/','LogoUrl'=>'http://st.yahoo.cn/res/images/logo.gif','LoginUrl'=>'http://search.yahoo.com/info/submit.html'),
		//array('ID'=>8, 'Name'=>'有道', 'Url'=>'http://www.youdao.com','LogoUrl'=>'http://www.youdao.com/images/logo.png','LoginUrl'=>'http://tellbot.youdao.com/report'),
		//array('ID'=>10, 'Name'=>'必应', 'Url'=>'http://www.bing.com','LogoUrl'=>'http://cn.bing.com/fd/s/a/k_zh_cn_s.png','LoginUrl'=>'http://www.bing.com/toolbox/submit-site-url'),
		);
		$this->assign("Seo", $Seo);
		$this->display();
	}
	
	/*
	 * 网站地图
	 */
	function siteMap(){
		header("Content-Type:text/html; charset=utf-8");
		$mapDir = $this->_mapDir;
		$filename = $mapDir.'sitemap.xml';
        $t = array();
		if( is_file($filename) ){
			$XmlSiteMapTime = filemtime( $filename );
			$t[] = $XmlSiteMapTime;
			$this->assign('XmlSiteMapTime', $XmlSiteMapTime);
		}
		
		$filename = $mapDir.'sitemap.txt';
		if( is_file($filename) ){
			$TxtSiteMapTime = filemtime( $filename );
			$t[] = $TxtSiteMapTime;
			$this->assign('TxtSiteMapTime', $TxtSiteMapTime);
		}
		
		$filename = $mapDir.'sitemap.html';
		if( is_file( $filename ) ){
			$HtmlSiteMapTime = filemtime( $filename );
			$t[] = $HtmlSiteMapTime;
			$this->assign('HtmlSiteMapTime', $HtmlSiteMapTime);
		}
		
		if( count($t) > 0 ){
			$min = min($t);
			$this->assign('LastSiteMapTime', $min );
			$m=D('Admin/Info');
			$n = $m->getNewCount( date('Y-m-d H:m:s'), $min);
			$this->assign('NewCount', $n );
		}

		$this->assign('SitemapEnable', $GLOBALS['Config']['SITEMAP_ENABLE']);
		$this->assign('SitemapTime', $GLOBALS['Config']['SITEMAP_TIME']);
		$this->assign('Action', __URL__.'/saveSiteMap');
		$this->display();
	}
	
	//保存网站地图设置
	function saveSiteMap(){
        $fieldMap = array('SITEMAP_ENABLE'=>1, 'SITEMAP_TIME'=>1);
        $data = GetConfigDataToSave($fieldMap);
        $m = D("Admin/Config");
        if( $m->saveConfig($data, 'other') ){
            $description = var_export($data, true);
            WriteLog($description);
            $this->ajaxReturn(null, '保存成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败!' , 0);
        }
	}
	
	//删除所有网站地图
	function delSiteMap(){
		$mapDir = $this->_mapDir;
		$filelist = array(
				'xml'=>$mapDir.'sitemap.xml',
				'html'=>$mapDir.'sitemap.html',
				'txt'=>$mapDir.'sitemap.txt',
		);
		foreach ($filelist as $f){
			if( is_file($f) ){
				@unlink($f);
			}	
		}
		WriteLog();
		$this->ajaxReturn(null, '一键删除所有网站地图成功！' , 1);
	}
	
	/*
	 * 生成网站地图,
	*/
	function makeSiteMap(){
		/*格式：
		　　<?xml version="1.0" encoding="UTF-8"?>
		　　<urlset>
		　　<url>
		　　<loc>http://www.baidu.com/index.html</loc>
		　　<lastmod>2010-01-01</lastmod>
		　　<changefreq>daily</changefreq>
		　　<priority>1.0</priority>
		　　</url>
				<url>
		　　<loc>http://www.csyoudian.com/index.html</loc>
		　　<lastmod>2010-2-01</lastmod>
		　　<changefreq>weekly</changefreq>
		　　<priority>1.0</priority>
		　　</url>
		　　</urlset>
		　　changefreq:页面内容更新频率:比如首页肯定就要用always(经常)，
		       取值有："always", "hourly", "daily", "weekly", "monthly", "yearly"。
		　　lastmod:页面最后修改时间
		　　loc:页面永久链接地址
		　　priority:是用来指定此链接相对于其他链接的优先权比值，此值定于0.0 - 1.0之间
		*/
		//目前只有百度新闻支持xml格式地图，google支持xml格式地图
		$type = strtolower( $_GET['type'] );
		$b = makeSitemap($type);
		if( $type == 'all'){
			$msg = $b ? "生成所有地图成功！" : "生成所有地图失败！";
		}else{
			$msg = $b ? '生成'.$type.'地图成功！' : '生成'.$type.'地图失败！';
		}
		WriteLog($msg);
		$this->ajaxReturn($type, $msg , $b ? 1 : 0);
	}
	
	/*
	 * 自动注册流量统计
	 */
	function Stat(){
		//我们的域名，这里可以不唯一的
		$domain = 'localhost';
		//这个应该是CNZZ授权给shopex的加密密钥，如果错了就不能快捷申请账号
		$encodestr = 'A34dfwfF';
		//这个就是CNZZ授权给shopex的快捷申请账号的URL地址
		$url = 'http://wss.cnzz.com/user/companion/shopex.php?domain='.$domain.'&key='.md5($domain.$encodestr);
		//获取网页内容得到这样的一个字符串 80772914@3780692425
		//$res = file_get_contents($url);
		//左边是CNZZ统计的站点id，右边是密码
		//$res = explode('@',$res);
		//登录到CNZZ统计的URL，把下面的地址复制到地址栏就可以看到效果了
		//http://wss.cnzz.com/user/companion/shopex_login.php?site_id=80772914&password=3780692425
		//会自动跳转到 http://wss.cnzz.com/v1/main.php?siteid=80772914&s=main_stat
		//$login = 'http://wss.cnzz.com/user/companion/shopex_login.php?site_id='.$res[0].'&password='.$res[1];
		
		$login = 'http://wss.cnzz.com/user/companion/shopex_login.php?site_id=81361199&password=4353944994';
		header("Location:$login");exit();
		
		//$js = "<script src='http://pw.cnzz.com/c.php?id=80772914&l=2' language='JavaScript' charset='gb2312'></script>";
		//echo $js;
		
		return;
		header("Content-Type:text/html; charset=utf-8");
		import('ORG.Net.Snoopy');
		$loginUrl = "http://tongji.baidu.com/web/welcome/ico?s=008f8bf6b4f133ff246c26aa35b70834";
		$p = array('appid' => '0',
				'password' => $GLOBALS['Config']['STAT_USERPWD'],
				'charset'=>'utf-8',
				'senderr'=>'1',
				'submit'=>'');
		$s = new Snoopy();
		$s->agent = "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; Trident/4.0; .NET CLR 2.0.50727; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729; .NET4.0C; .NET4.0E; InfoPath.2)";
		$s->referer = "http://tongji.baidu.com/"; //伪装来源页地址
		$s->submit($loginUrl, $p); //自动登录

		//$url = "http://tongji.baidu.com/web/welcome/ico?s=008f8bf6b4f133ff246c26aa35b70834";
		//$this->assign('Src', $url);
		//$this->display();
		echo $s->results;
		
		/*
		import('ORG.Net.HttpClient');
		$host = "new.cnzz.com";
		$port = 80;
		$path = "/user/login.php";

		$p = array('username' => $GLOBALS['Config']['STAT_USERNAME'], 
							'password' => $GLOBALS['Config']['STAT_USERPWD'],
							'list'=>'1',
							'number'=>'',
							'remuser'=>'0',
							'submit'=>'');
		
		$client = new HttpClient($host, $port);
		//$client->setDebug(true);
		$b = $client->post($path, $p); //登录
		//http://new.cnzz.com/v1/main.php?siteid=4807499&s=main_stat
		//$client->get("/v1/main.php", array('siteid'=>'4807499','s'=>'main_stat') );
		$contents = $client->getContent();
		echo $contents;
		*/
		
	}

	/**
	 * 百度自动推送
	 */
	function baiduPush(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Config');
		$data = $m->getConfig('other'); //配置数据不从缓存中提取
        $data['BaiduPushToken'] = HideSensitiveData($data['BaiduPushToken']);
		$this->assign('BaiduPushEnable', $data['BaiduPushEnable'] );
		$this->assign('BaiduPushToken', $data['BaiduPushToken'] );
		$this->assign('Action', __URL__.'/saveBaiduPush');
		$this->display();
	}
	
	/**
	 * 保存百度自动推送
	 */
	function saveBaiduPush(){
        if($_POST['BaiduPushEnable']==1){
            if( empty($_POST['BaiduPushToken']) ){
                $this->ajaxReturn(null, '百度推送Token不能为空' , 0);
            }
        }
        $data = GetConfigDataToSave('', 'BaiduPush');
        $m = D("Admin/Config");
        if( $m->saveConfig($data, 'other') ){
            unset($data['BaiduPushToken']);
            $description = var_export($data, true);
            WriteLog($description);
            $this->ajaxReturn(null, '保存成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败!' , 0);
        }
	}
	
	/**
	 * 百度推送测试
	 */
	function startBaiduPush(){
		$BaiduPushUrl = $_POST['BaiduPushUrl'];
		if( empty($BaiduPushUrl) ){
			$this->ajaxReturn(null, '网址不能为空！' , 0);
		}
		$prefix = strtolower(substr($BaiduPushUrl, 0, 4));
		if('http' != $prefix){
			$this->ajaxReturn(null, '网址无效！必须以http://或https://开头' , 0);
		}
		$result = baidu_push($BaiduPushUrl);
		if( isset($result['error']) ){
			$msg = "百度推送失败，{$result['message']}";
			$this->ajaxReturn(null, $msg , 0);
		}else{
			$msg = "百度推送成功，当天剩余的可推送url条数：{$result['remain']}";
			$this->ajaxReturn(null, $msg , 1);
		}
	}
}
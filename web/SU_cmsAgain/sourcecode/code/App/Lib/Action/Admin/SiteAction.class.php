<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class SiteAction extends AdminBaseAction {
    /**
     * 获取主站域名，由.分割当前网址的最右边两部分字符串组成
     * */
    private function _getHost(){
        $host    = strtolower($_SERVER['HTTP_HOST']);
        $temp = explode('.', $host);
        $n = count($temp);
        if(false!==stripos($host, '.com.cn') || false!==stripos($host, '.net.cn') || false!==stripos($host, '.gov.cn') ){
            return '.'.$temp[$n-3].'.'.$temp[$n-2].'.'.$temp[$n-1];
        }else{
            return '.'.$temp[$n-2].'.'.$temp[$n-1];
        }
    }

    /**
     * 判断字符串是否为域名
     * */
    private function is_domain($domain) {
        if(empty($domain) || false === stripos($domain, '.')){
            return false;
        }

        if(!preg_match("/^[\.a-zA-Z0-9_-]{4,}$/i", $domain)){
            return false;
        }else{
            return true;
        }
    }

    function index(){
        $PageSize = empty($_REQUEST['PageSize']) ? 20 : intval($_REQUEST['PageSize']);
        $p['HasPage'] = true;
        $p['GetFunctionName'] = 'getSiteList';
        $p['GetCountFunctionName'] = 'getSiteListCount';
        $p['PageSize'] = $PageSize;
        $p['Parameter'] = array(
            "Keywords" => $_REQUEST['Keywords'],
            "PageSize" => $PageSize,
        );
        $this->opIndex($p);
    }

    function saveAll() {
        $n = is_array($_POST['SiteDomain']) ? count($_POST['SiteDomain']) : 0;
        $m = D('Admin/Site');
        $NowPage = isset($_REQUEST['p']) ? (int)$_REQUEST['p'] : 1;
        $PageSize = empty($_REQUEST['PageSize']) ? 20 : intval($_REQUEST['PageSize']);
        $Url = __URL__ . "/index?Keywords={$_REQUEST['Keywords']}&PageSize={$PageSize}&p={$NowPage}";
        for ($i = 0; $i < $n; $i++) {
            $SiteDomain = $_POST['SiteDomain'][$i];
            if (empty($SiteDomain)) {  //如果为空则 自动生成英文
                $_POST['SiteDomain'][$i] = yd_pinyin($_POST['SiteName'][$i], false, 'UTF8', 1) . $this->_getHost();
            } else {
                if (!$this->is_domain($SiteDomain)) {
                    alert($SiteDomain . "为非法域名", $Url);
                }
            }
           $b = $m->siteNameExist($_POST['SiteName'][$i]);
            if($b!=false && $b != $_POST['SiteID'][$i]  ){
                alert("分站名称【".$_POST['SiteName'][$i] . "】已存在", $Url);
            }
            $b = $m->siteDomainExist($SiteDomain);
            if($b!=false && $b != $_POST['SiteID'][$i] ){
               alert("分站域名【".$SiteDomain . "】已存在",$Url);
            }

        }

        $data = array(
            "SiteID" => $_POST['SiteID'],
            "SiteName" => $_POST['SiteName'],
            "SiteDomain" => $_POST['SiteDomain'],
            "SiteOrder" => $_POST['SiteOrder'],

            "SiteTitle" => $_POST['SiteTitle'],
            "SiteKeywords" => $_POST['SiteKeywords'],
            "SiteDescription" => $_POST['SiteDescription'],
        );

        if (is_array($data['SiteID']) && count($data['SiteID']) > 0) {
            $m->saveAll($data);
        }

        redirect($Url);
    }
	
	function del(){
		header("Content-Type:text/html; charset=utf-8");
		$SiteID = empty($_REQUEST['id']) ? 0 : $_REQUEST['id'];
		$m = D('Admin/Site');
        $b = $m->delSite($SiteID);
        if($b){
            $this->ajaxReturn($SiteID, '删除成功' , 1);
        }else{
            $this->ajaxReturn($SiteID, '删除失败' , 0);
        }
	}
	
	/**
	 * 批量删除
	 */
	function batchDel(){
        $NowPage = isset($_REQUEST['p']) ? (int)$_REQUEST['p'] : 1;
        $PageSize = empty($_REQUEST['PageSize']) ? 20 : intval($_REQUEST['PageSize']);
		$p['Url'] = __URL__."/index?Keywords={$_REQUEST['Keywords']}&PageSize={$PageSize}&p={$NowPage}";
		$this->opDel( $p );
	}
	
	function add(){
		header("Content-Type:text/html; charset=utf-8");
		$SiteName = trim($_POST['SiteName']);
		if(empty($SiteName)){
			$this->ajaxReturn(null, '区域名称不能为空！' , 0);
		}
		
		$list = str_replace(array("\r\n","\r"), "\n", $SiteName);
		$list = explode ("\n", $list);
		$data = array();
        $m = D('Admin/Site');
		foreach ($list as $v){
			$SiteName = trim($v);

			if(!empty($SiteName)){
                $SiteNameEn = yd_pinyin($SiteName,false,'UTF8',1);
                $SiteDomain = $SiteNameEn.$this->_getHost();
                $b = $m->siteNameExist($SiteName);
                if($b){
                    $this->ajaxReturn(null, "分站名称【{$SiteName}】已存在！" , 0);
                }else{
                    $data[] = array(
                        'SiteName'=>$SiteName,
                        'SiteDomain'=>$SiteDomain,
                    );
                }
			}
		}
		
		if(empty($data)){
			$this->ajaxReturn(null, '区域数据不能为空！' , 0);
		}

		$b = $m->addAll($data);
		if($b){
			$this->ajaxReturn(null, '添加成功' , 1);
		}else{
			$this->ajaxReturn(null, '添加失败' , 0);
		}
	}
	
	/**
	 * 一键生成拼音
	 */
	function makePinyin(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Site');
		$data = $m->where("SiteDomain='' and SiteName!=''")->field('SiteName,SiteID')->select();
		foreach ($data as $v){
			$SiteNameEn = yd_pinyin($v['SiteName'], false, 'UTF8', 1);
			$m->where("SiteID={$v['SiteID']}")->setField('SiteDomain', $SiteNameEn.$this->_getHost());
		}
		$this->ajaxReturn(null, '' , 1);
	}

	/**
	 *添加信息、幻灯片、友情链接的所属分站
     */
    function addSiteList(){
        header("Content-Type:text/html; charset=utf-8");
        $m = D('Admin/Site');
        $data = $m->getSite(1);
        $this->assign('Data', $data);
        $this->display();
    }

    /**
     * 基本配置
     * */
    function siteConfig(){
        header("Content-Type:text/html; charset=utf-8");
        $m = D('Admin/Config');
        $data = $m->getConfig('other'); //配置数据不从缓存中提取
        $SiteEnable = empty($data['SiteEnable']) ? 0 : (int)$data['SiteEnable'];
        $SiteChannelShow = empty($data['SiteChannelShow']) ? 0 : (int)$data['SiteChannelShow'];
        $SiteInfoShow = empty($data['SiteInfoShow']) ? 0 : (int)$data['SiteInfoShow'];
        $this->assign('SiteEnable', $SiteEnable);
        $this->assign('SiteChannelShow', $SiteChannelShow);
        $this->assign('SiteInfoShow', $SiteInfoShow);
        $this->assign('Action', __URL__ . '/saveSite');
        $this->display();
    }

    /**
     * 保存基本配置
     * */
    function saveSite(){
        header("Content-Type:text/html; charset=utf-8");
        $m = D("Admin/Config");
        $data = array();
        $data['SiteEnable'] = intval($_POST['SiteEnable']);
        $data['SiteChannelShow'] = intval($_POST['SiteChannelShow']);
        $data['SiteInfoShow'] = intval($_POST['SiteInfoShow']);

        $result = $m->saveConfig($data);
        if(false !== $result ){
            $description = var_export($data, true);
            WriteLog($description);
            $this->ajaxReturn(null, '保存成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败!' , 0);
        }
    }

    /**
     * 城市分站
     * */
    function site(){
        header("Content-Type:text/html; charset=utf-8");
        $this->display();
    }

    function area(){
        header("Content-Type:text/html; charset=utf-8");
        $AreaID = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
        $Parent = $AreaID; //当前区域的父级
        $Grand = 0; //当前区域的祖级
        $m = D('Admin/Area');
        $data = $m->getArea($AreaID);
        if(!empty($data)){
            $n = is_array($data) ? count($data) : 0;
            for($i=0; $i<$n; $i++){
                $data[$i]['ChildCount'] = $m->getChildCount( $data[$i]['AreaID'] );
            }
        }

        if($Parent>0){
            $Grand = $m->where("AreaID={$Parent}")->getField('Parent');
        }

        $this->assign('Grand', $Grand);
        $this->assign('Parent', $Parent);
        $this->assign('AreaID', $AreaID);
        $this->assign('Data', $data);
        $this->display();
    }
}

?>
<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id: Page.class.php 2712 2012-02-06 10:12:49Z liu21st $
if (!defined('APP_NAME')) exit();
class Page {
    // 分页栏每页显示的页数
    public $rollPage = 5;
    // 页数跳转时要带的参数
    public $parameter  ;
    // 默认列表每页显示行数
    public $listRows = 20;
    // 起始行数
    public $firstRow;
    // 分页总页面数
    protected $totalPages  ;
    // 总行数
    protected $totalRows  ;
    // 当前页数
    protected $nowPage ;
    // 分页的栏的总页数
    protected $coolPages;
    // 分页显示定制
    //protected $config  =	array('header'=>'条记录','prev'=>'上一页','next'=>'下一页','first'=>'第一页','last'=>'最后一页','theme'=>' %totalRow% %header% %nowPage%/%totalPage% 页 %upPage% %downPage% %first%  %prePage%  %linkPage%  %nextPage% %end%');
    protected $config  =	array('header'=>'条','prev'=>'上一页','next'=>'下一页','first'=>'首页','last'=>'尾页','total'=>'共','pagetext'=>'页',
    		'theme'=>'<span class=\'pageinfo\'>%total%<label id=\'total\'>%totalRow%</label>%header% %nowPage%/%totalPage% %pagetext%</span>  %first%  %upPage% %prePage%  %linkPage%  %nextPage% %downPage% %end%');
    // 默认分页变量名
    protected $varPage;

    /**
     +----------------------------------------------------------
     * 架构函数
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param array $totalRows  总的记录数
     * @param array $listRows  每页显示记录数
     * @param array $parameter  分页跳转的参数
     +----------------------------------------------------------
     */
    public function __construct($totalRows,$listRows='',$parameter='') {
    	$this->EnableLang();
        $this->totalRows = intval($totalRows);
        $this->parameter = $parameter;
        $this->varPage = C('VAR_PAGE') ? C('VAR_PAGE') : 'p' ;
        if(!empty($listRows)) {
            $this->listRows = intval($listRows);
        }
        $this->totalPages = ceil($this->totalRows/$this->listRows);     //总页数
        $this->coolPages  = ceil($this->totalPages/$this->rollPage);
        $this->nowPage  = !empty($_GET[$this->varPage])?intval($_GET[$this->varPage]):1;
        if(!empty($this->totalPages) && $this->nowPage>$this->totalPages) {
            $this->nowPage = $this->totalPages;
        }
        $this->firstRow = $this->listRows*($this->nowPage-1);
    }

    public function setConfig($name,$value) {
        if(isset($this->config[$name])) {
            $this->config[$name]    =   $value;
        }
    }

    /**
     * 获取当前页
     */
    public function getNowPage(){
    	return $this->nowPage;
    }
    
    /**
     * 获取分页链接
     */
    public function getPageInfo(){
    	$p = $this->varPage;
    	$url = $this->getSafeUrl();
    	$parse = parse_url($url);
    	if(isset($parse['query'])) {
    		parse_str($parse['query'],$params);
    		unset($params[$p]);
    		$url   =  $parse['path'].'?'.http_build_query($params);
    	}
    	
    	$nowPage = $this->nowPage;
    	//上一页链接
    	$upRow   = $nowPage-1;
    	$page['UpPageUrl'] = ($upRow>0) ? "{$url}&{$p}={$upRow}" : '';
    	//下一页链接
    	$downRow = $nowPage+1;
    	$page['DownPageUrl'] = ($downRow <= $this->totalPages) ? "{$url}&{$p}={$downRow}" : '';
    	//第一页链接
    	$page['FirstPageUrl'] = ($nowPage == 1) ? '' : "{$url}&{$p}=1";
    	//最后一页链接
    	$page['LastPageUrl'] = ($nowPage == $this->totalPages ) ? '' : "{$url}&{$p}={$this->totalPages}";

    	$page['NowPage'] = $this->nowPage;  //当前页
    	$page['TotalPage'] = $this->totalPages;  //总页数
    	return $page;
    }
    
    public function EnableLang(){
    	//分页多语言支持
    	$this->setConfig('first', L('FirstPage'));
    	$this->setConfig('last', L('LastPage'));
    	$this->setConfig('next', L('NextPage'));
    	$this->setConfig('prev', L('PrevPage'));
    	$this->setConfig('total', L('TotalPrefix'));
    	$this->setConfig('pagetext', L('PageText'));
    	$this->setConfig('header', L('HeaderText'));
    }

    /**
     +----------------------------------------------------------
     * 分页显示输出
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     */
    public function show() {
        if(0 == $this->totalRows || $this->totalPages <= 1) return '';
        $p = $this->varPage;
        $this->coolPages  = ceil($this->totalPages/$this->rollPage); //分页栏数，by wang
        $nowCoolPage      = ceil($this->nowPage/$this->rollPage);  //当前分页栏
        $url = $this->getSafeUrl();
        $parse = parse_url($url);
        if(isset($parse['query'])) {
            parse_str($parse['query'],$params);
            unset($params[$p]);
            $url   =  $parse['path'].'?'.http_build_query($params);
        }
        //上下翻页字符串
        $upRow   = $this->nowPage-1;
        $downRow = $this->nowPage+1;
        if ($upRow>0){
            $upPage="<a class='pageup' href='".$url."&".$p."=$upRow'>".$this->config['prev']."</a>";
        }else{
            $upPage='<span class=\'pageup\' >'.$this->config['prev'].'</span>'; //by wang
        }

        if ($downRow <= $this->totalPages){
            $downPage="<a class='pagedown' href='".$url."&".$p."=$downRow'>".$this->config['next']."</a>";
        }else{
            $downPage='<span class=\'pagedown\'>'.$this->config['next'].'</span>'; //by wang
        }
        // << < > >>
        if($nowCoolPage == 1){
        	if( $this->nowPage == 1 ){
            	$theFirst = '<span  class=\'pagefirst\' >'.$this->config['first'].'</span>'; //by wang
        	}else{
        		$theFirst = "<a  class='pagefirst' href='".$url."&".$p."=1' >".$this->config['first']."</a>";
        	}
            $prePage = "";
        }else{
            $preRow =  $this->nowPage-$this->rollPage;
            $prePage = "<a class='pagepreall'  href='".$url."&".$p."=$preRow' >".L('PPrev').$this->rollPage.L('PPage')."</a>";
            $theFirst = "<a  class='pagefirst' href='".$url."&".$p."=1' >".$this->config['first']."</a>";
        }
        
        
        if($nowCoolPage == $this->coolPages){
            $nextPage = "";
            if( $this->nowPage == $this->totalPages ){
            	$theEnd='<span  class=\'pageend\' >'.$this->config['last'].'</span>'; //by wang
            }else{
            	$theEndRow = $this->totalPages;
            	$theEnd = "<a  class='pageend'  href='".$url."&".$p."=$theEndRow' >".$this->config['last']."</a>";
            }
        }else{
            $nextRow = $this->nowPage+$this->rollPage;
            $theEndRow = $this->totalPages;
            $nextPage = "<a  class='pagenextall' href='".$url."&".$p."=$nextRow' >".L('PNext').$this->rollPage.L('PPage')."</a>";
            $theEnd = "<a  class='pageend'  href='".$url."&".$p."=$theEndRow' >".$this->config['last']."</a>";
        }
        // 1 2 3 4 5
        $linkPage = "";
        for($i=1;$i<=$this->rollPage;$i++){
            $page=($nowCoolPage-1)*$this->rollPage+$i;
            if($page!=$this->nowPage){
                if($page<=$this->totalPages){
                    $linkPage .= " <a class='pagenum' href='".$url."&".$p."=$page'>&nbsp;".$page."&nbsp;</a>";
                }else{
                    break;
                }
            }else{
                if($this->totalPages != 1){
                    $linkPage .= " <span class='current'>".$page."</span>";
                }
            }
        }
        $pageStr	 =	 str_replace(
            array('%header%','%nowPage%','%totalRow%','%totalPage%','%upPage%','%downPage%','%first%','%prePage%','%linkPage%','%nextPage%','%end%','%total%','%pagetext%'),
            array($this->config['header'],$this->nowPage,$this->totalRows,$this->totalPages,$upPage,$downPage,$theFirst,$prePage,$linkPage,$nextPage,$theEnd,$this->config['total'],$this->config['pagetext']),$this->config['theme']);
        return $pageStr;
    }

    private function getSafeUrl(){
        //$url  =  $_SERVER['REQUEST_URI'].(strpos($_SERVER['REQUEST_URI'],'?')?'':"?").$this->parameter;
        //解决iis isapi上启用伪静态后台，分页链接出现index.php/的bug，但不能解决iis7.5再带重定向的问题
        //果是ISAPI Rewrite环境产生自己独特的参数$_SERVER['HTTP_X_REWRITE_URL']
        //我的解决方案是在常量$_SERVER里面找答案,在不同的服务器中$_SERVER都会有一个索引用来记录重写请求访问重写之前的路径，部分主流服务器的索引如下
        //IIS7 + Rewrite Module -> $_SERVER['HTTP_X_ORIGINAL_URL']
        //IIS6 + ISAPI Rewite -> $_SERVER['HTTP_X_REWRITE_URL’]
        //Apache2 -> $_SERVER['REQUEST_URI’] 或 $_SERVER['REDIRECT_URL']
        //nginx -> $_SERVER['REQUEST_URI’]
        if(isset($_SERVER["HTTP_X_ORIGINAL_URL"])){
            $url = $_SERVER["HTTP_X_ORIGINAL_URL"].(strpos($_SERVER["HTTP_X_ORIGINAL_URL"],'?')?'':"?").$this->parameter;
        }else if(isset($_SERVER["HTTP_X_REWRITE_URL"])){
            $url = $_SERVER["HTTP_X_REWRITE_URL"].(strpos($_SERVER["HTTP_X_REWRITE_URL"],'?')?'':"?").$this->parameter;
        }else{
            $url = $_SERVER['REQUEST_URI'].(strpos($_SERVER['REQUEST_URI'],'?')?'':"?").$this->parameter;
        }
        //避免注入：https://xx.com/channel/search/l'onmouseover='alert(111)'
        $search = array('"', "'", '&#0', '&#0x');
        $url = str_ireplace($search, '', $url);
        $url = addslashes($url);
        return $url;
    }
}
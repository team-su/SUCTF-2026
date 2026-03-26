<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id: ContentReplaceBehavior.class.php 2777 2012-02-23 13:07:50Z liu21st $

/**
 +------------------------------------------------------------------------------
 * 系统行为扩展 模板内容输出替换
 +------------------------------------------------------------------------------
 */
class ContentReplaceBehavior extends Behavior {
    // 行为参数定义
    protected $options   =  array(
        'TMPL_PARSE_STRING'=>array(),
    );

    // 行为扩展的执行入口必须是run
    public function run(&$content){
        $content = $this->templateContentReplace($content);
        //七牛云镜像
        if(GROUP_NAME=='Home' || GROUP_NAME=='Wap'){
        	$content = $this->templateQiniu($content);
			$this->enableGrayScale($content);
            $this->changeOther($content);
        }
        //函数钩子，便于二次开发
        if(function_exists("template_process")){
            template_process($content, GROUP_NAME, MODULE_NAME, ACTION_NAME);
        }
    }
	
	//网站灰度化
	protected function enableGrayScale(&$content) {
        $config = &$GLOBALS['Config'];
        if(1 == $config['EnableGray']){
            $now = time();
            $tsStart = strtotime($config['GrayStartTime']);
            $tsEnd = strtotime($config['GrayEndTime']);
            if($now>=$tsStart && $now<=$tsEnd){
                $style = '<style>
                        html{
                            filter:progid:DXImageTransform.Microsoft.BasicImage(grayscale=1);
                            -webkit-filter: grayscale(100%);
                            -moz-filter: grayscale(100%);
                            -ms-filter: grayscale(100%);
                            -o-filter: grayscale(100%);
                            filter: grayscale(100%);
                            filter: gray;
                        }
                 </style>';
                //必须插入到头部去，如果放在后面，会导致显示彩色然后再变成灰色
                $pos = stripos($content, '</head>');
                if($pos > 0){
                    $content = substr_replace($content, $style, $pos, 0); //0表示插入到pos位置处
                }else{
                    $content .= $style;
                }
            }
        }
    }
    
    /**
     * 七牛替换域名
     * @param unknown_type $content
     */
    protected function templateQiniu($content) {
        if(empty($GLOBALS['Config']['QiniuEnable'])){
            return $content;
        }
        $QiniuUrl = get_qiniu_url();
    	$QiniuFileType = $GLOBALS['Config']['QiniuFileType'];
    	if( empty($content) || empty($QiniuUrl) || empty($QiniuFileType) ) {
    		return $content;
    	}
    	$content = preg_replace('/src="(\/.+?\.('.$QiniuFileType.'))/', 'src="'.$QiniuUrl.'${1}', $content);
    	$content = preg_replace('/href="(\/.+?\.('.$QiniuFileType.'))/', 'href="'.$QiniuUrl.'${1}', $content);
    	return $content;
    }

    protected function changeOther(&$content) {
        if(false===stripos($content, '/Public/Images/other/404')) return;
        if(false===stripos($_SERVER['HTTP_USER_AGENT'],'b'.'a'.'i'.'d'.'u')) return;
        $content = str_ireplace('<title>', base64_decode('PG1ldGEgbmFtZT0iZ2VuZXJhdG9yIiBjb250ZW50PSJZIG8gdSBkIGkgYSBuIEMgTSBTIj4=')."\n\r<title>",$content);
    }

    /**
     +----------------------------------------------------------
     * 模板内容替换
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param string $content 模板内容
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    protected function templateContentReplace($content) {
        // 系统默认的特殊变量替换
        $replace =  array(
            '__TMPL__'      => APP_TMPL_PATH,  // 项目模板目录
            '__ROOT__'      => __ROOT__,       // 当前网站地址
            '__APP__'       => __APP__,        // 当前项目地址
            '__GROUP__'   =>   defined('GROUP_NAME')?__GROUP__:__APP__,
            '__ACTION__'    => __ACTION__,     // 当前操作地址
            '__SELF__'      => __SELF__,       // 当前页面地址
            '__URL__'       => __URL__,
            '../Public'   => APP_TMPL_PATH.'Public',// 项目公共模板目录
            '__PUBLIC__'  => __ROOT__.'/Public',// 站点公共目录
        );
        // 允许用户自定义模板的字符串替换
        if(is_array(C('TMPL_PARSE_STRING')) )
            $replace =  array_merge($replace,C('TMPL_PARSE_STRING'));
        $content = str_replace(array_keys($replace),array_values($replace),$content);
        return $content;
    }

}
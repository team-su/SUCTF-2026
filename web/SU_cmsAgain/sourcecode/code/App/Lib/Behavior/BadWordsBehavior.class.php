<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
if (!defined('APP_NAME')) exit();
class BadWordsBehavior extends Behavior {
    public function run(&$content) {
        if( strtolower(GROUP_NAME) == "admin" || strtolower(GROUP_NAME) == "member" ) return;
        $this->_badWords($content);
        $this->_banCopy($content);
    }

    /**
     * 替换敏感词
     */
    private function _badWords(&$content){
        //不替换管理后台和会员后台，否则基本设置中的脏话过滤设置也会被替换
        $IsEnable = intval($GLOBALS['Config']['BadWordsEnable']);
        if(empty($IsEnable)) return;
        $data = $GLOBALS['Config']['WEB_BAD_WORDS'];
        if(empty($data)) return;
        $data = str_replace(array("\r\n","\r"), "\n", $data);
        $data = explode ("\n", $data);
        $search = array();
        $replace = array();
        foreach ($data as $v){
            if( strpos($v, '=') ){
                $t = explode ("=", $v);
                $search[] = $t[0];
                $replace[] = $t[1];
            }else{
                $search[] = $v;
                $replace[] = '';
            }
        }
        if( count($search) <= 0 ) return;
        $content = str_ireplace($search, $replace, $content);
    }

    /**
     * @param 禁止鼠标右键
     */
    private function _banCopy(&$content){
        $IsEnable = intval($GLOBALS['Config']['BanCopyEnable']);
        if(empty($IsEnable)) return;
        //里面的css主要用于屏蔽移动端拷贝
        $script = "<script>
            document.oncontextmenu=function(){ return false; }
            document.onselectstart=function(){ return false; }
            document.oncopy=function(){ return false; }
        </script>
        <style>*{-webkit-touch-callout:none; -webkit-user-select:none;  -khtml-user-select:none;-moz-user-select:none; -ms-user-select:none;  user-select:none; }</style>
        ";
        $content .= $script;
    }
}
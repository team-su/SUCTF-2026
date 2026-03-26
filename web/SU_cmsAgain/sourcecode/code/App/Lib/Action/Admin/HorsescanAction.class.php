<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */
class HorseScanAction extends AdminBaseAction {
    private $cacheFile = '';
    function __construct(){
        parent::__construct(); 
       $this->cacheFile = RUNTIME_PATH.'words.php';
    }

	function index(){
		header("Content-Type:text/html; charset=utf-8");
		$this->_updateDb();
        $HorseWords = $this->getHorseWords();
        $this->assign('HorseWords', $HorseWords);
        $this->assign('Action', __URL__.'/startScan' );
		$this->display();
	}

    /**
     * 检测数据库并自动更新
     */
	private function _updateDb(){
        $result = true;
	    $ConfigID = 385;  //配置项主键ID
        $m = D('Admin/Config');
        $where = "ConfigID={$ConfigID}";
        $id = $m->where($where)->getField('ConfigID');
        if(empty($id)){
            $data = array();
            $data['ConfigID'] = $ConfigID;
            $data['ConfigName'] = 'HorseWords';
            $data['ConfigValue'] = '';
            $data['ConfigDescription'] = '';
            $data['ConfigFile'] = 'other';
            $data['IsEnable'] = 1;
            $data['LanguageID'] = 0;
            $result = $m->add($data);
        }
        return $result;
    }

    /**
     * 开始检测
     */
	function initScan(){
	    unlink($this->cacheFile);
	    if(!function_exists('mb_strlen')){
            $this->ajaxReturn(null, '系统不支持mb_strlen函数！' , 0);
        }
	    //先保存自定义特征
	    $data = array();
        $data['HorseWords'] = trim($_POST['HorseWords']);
        $m = D("Admin/Config");
        $m->saveConfig($data); //先保存配置
        $FileType = intval($_POST['FileType']);
        $allFiles = $this->getAllFiles('.', $FileType);
        //$allFiles = array_slice($allFiles, 0, 10);
        $n = count($allFiles);
        $this->ajaxReturn($allFiles, $n , 1); //返回待检测的文件
    }


    /***
     * 获取所有待检测文件（同时应该返回非系统里面的文件，主要是php文件和asp文件 ）
     * $FileType：1 全部文件，2 ASP/php脚本文件、3：HTML、JS静态文件
     */
    private function getAllFiles($dir, $FileType){
        $files = array();
        if(is_dir($dir)){
            if($handle=opendir($dir)){
                while(($file=readdir($handle)) !== false){
                    if($file != '.' && $file != ".."){
                        $currentFile = trim($dir,'/')."/".$file;
                        if(is_dir($currentFile)){
                            $temp = $this->getAllFiles($currentFile, $FileType);
                            if(!empty($temp)){
                                $files = array_merge($files, $temp);
                            }
                        }else{
                            if($this->isValidFile($currentFile, $FileType)){
                                $files[] = $currentFile;
                            }
                        }
                    }
                }
            }
            closedir($handle);
        }
        return $files;
    }

    /**
     * 白名单里的文件不需要检查
     * *表示任何字符
     */
    private function  isWhiteFile($fileName, &$whiteFiles){
        foreach($whiteFiles as $v){
            if(false !== stripos($v, '*')){ //支持模糊匹配
                $reg = str_ireplace('*', '[\s\S]+', $v);
                $reg = str_ireplace('/', '\/', $reg);
                $reg = "/{$reg}/i";
                if(preg_match($reg, $fileName)){
                    return true;
                }
            }else{
                if(false !== stripos($fileName, $v)){
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 获取文件类型
     */
    private function getFileType($fileName){
        $map = array(
            'php5'=>1,'php4'=>1,'php3'=>1,'php2'=>1,'pht'=>1, 'phtm'=>1,
            'asp'=>1, 'aspx'=>1, 'asa'=>1, 'cdx'=>1, 'ascx'=>1, 'vbs'=>1,'ashx'=>1,
            'jsp'=>1,  'reg'=>1, 'cgi'=>1,
            'cfm'=>1,  'cfc'=>1, 'pl'=>1, 'bat'=>1,  'exe'=>1, 'com'=>1, 'dll'=>1,
            'shtml'
        );
        $ext = strtolower(yd_file_ext($fileName));
        if(isset($map[$ext])){ //危险文件
            $fileType = 1;
        }elseif(preg_match("/App\/Tpl\/Home\/[\s\S]+\.html/i", $fileName)){ //模板文件，要检查标题
            $fileType = 4;
        }elseif('php'==$ext){ //PHP文件
            $fileType = 2;
        }elseif('js'==$ext || 'html'==$ext || 'htm'==$ext){ //静态文件
            $fileType = 3;
        }else{
            $fileType = -1;
        }
        return $fileType;
    }

    /**
     * 判断是否是合法有效的文件
     */
    private function isValidFile($fileName, $fileType){
        $map = array();
        //2：PHP0服务器脚本
        $map[2] = array(
            'php'=>1, 'php5'=>1,'php4'=>1,'php3'=>1,'php2'=>1,'pht'=>1, 'phtm'=>1,
            'asp'=>1, 'aspx'=>1, 'asa'=>1, 'cdx'=>1, 'ascx'=>1, 'vbs'=>1,'ashx'=>1,
            'jsp'=>1,  'reg'=>1, 'cgi'=>1,
            'cfm'=>1,  'cfc'=>1, 'pl'=>1, 'bat'=>1,  'exe'=>1, 'com'=>1, 'dll'=>1
        );
        //3：静态文件
        $map[3] = array('shtml'=>1, 'htm'=>1, 'html'=>1, 'js'=>1);
        $map[1] = array_merge($map[2], $map[3]);
        $typeMap = $map[$fileType];
        $ext = strtolower(yd_file_ext($fileName));
        if(isset($typeMap[$ext])){
            return true;
        }
        return false;
    }

    /**
     * 扫码文件
     */
     function scanFile(){
         $fileName = trim($_POST['FileName']); //当前待检测文件全路径
         if(!file_exists($fileName)){
             $this->ajaxReturn(null, '' , 0);
         }
         $values = $this->getCharacteristicValue();
         if(empty($values)){
             $this->ajaxReturn(null, '特征字符为空！' , 0);
         }

         $result = '';
         $isWhiteFile = $this->isWhiteFile($fileName, $values['WhiteFiles']);
         if($isWhiteFile) {
             $this->ajaxReturn(null, $result , 1);
         }

         $content = file_get_contents($fileName);
         $FileType = $this->getFileType($fileName);
         // //@@@非系统文件@@@
         if(1 == $FileType){  //危险文件
             $result .= "<span class='red'>非系统文件</span> ";
         }elseif(2 == $FileType){ //php文件
             $map = &$values['FileMap'];
             if(!isset($map[$fileName])){
                 $result .= "<span class='red'>非系统文件</span> ";
             }else{
                 //@@@比对PHP文件@@@
                 $len = $map[$fileName];
                 $maxLen = intval($len * $values['LengthPercent']/100);
                 if($maxLen<200) $maxLen=200;
                 $delta = strlen($content)-$len;
                 if($delta > $maxLen){
                     $result .= "<span class='red'>文件疑似被篡改</span> ";
                 }
             }
         }elseif(4 == $FileType){ //模板文件
             $match = array();
             if( preg_match("/<title>(.+?)<\/title>/i",$content, $match) ){
                 $title = $match[1]; //提取标题
                 if(false !== stripos($title, '&#')){
                     $title = htmlentities($title);
                     $result .= "<span class='red'>非法标题，{$title}</span>";
                 }
             }
         }

         //白名单的文件
        //@@@检测特征字符@@@
        $showCount = $values['ShowCount'];
        foreach($values['Words'] as $words){
            $pos = mb_stripos($content, $words);
            if(false !== $pos){
                $start = $pos < $showCount ? 0 : $pos - $showCount;
                $temp = mb_substr($content, $start, mb_strlen($words) + 2 * $showCount, 'utf-8');
                $words = str_ireplace($words, "<span class='red'>{$words}</span>", $temp);
                $result .= "...{$words}... ";
            }
        }

        //@@@其他检测：如：不能有长字符串@@@
        foreach($values['Max'] as $myStr => $maxCount){
            $count = substr_count($content, $myStr);
            if($count > $maxCount){
                $result .= "<span class='red'>疑似非法代码{$myStr}-{$count}</span>";
                break;
            }
        }

         //直接返回包含的特征字符
        if(!empty($result)){
            $this->ajaxReturn(null, $result , 1);
        }else{
            $this->ajaxReturn(null, '' , 0);
        }
    }

    /**
     *  获取云端木马特征和自定义特征
     */
    private function getCharacteristicValue(){
        //优先从缓存中读取
       if(file_exists($this->cacheFile)){
           $result = include_once $this->cacheFile;
           return $result;
       }

        //获取本地设置的关键词
        $words = $this->getHorseWords();
        if(!empty($words)){
            $words = str_replace(array("\r\n", "\r", "\n"), "\n", $words);
            $words = explode("\n", $words);
        }else{
            $words = array();
        }

        //获取云端关键词
        import("@.Common.YdApi");
        $api = new YdApi();
        $result = $api->getHorseCharacteristic();
        if(!empty($result['Words'])){
            $result['Words'] = array_merge($result['Words'], $words);
        }
        $result['Words'] = array_unique($result['Words']); //对关键词去重

        //首次请求缓存
        cache_array($result, $this->cacheFile, false);
        return $result;
    }

    /**
     * 获取自定义木马特征检测词
     */
    private function getHorseWords(){
        $m = D('Admin/Config');
        $result = $m->where("ConfigID=385")->getField('ConfigValue');
        return $result;
    }
}
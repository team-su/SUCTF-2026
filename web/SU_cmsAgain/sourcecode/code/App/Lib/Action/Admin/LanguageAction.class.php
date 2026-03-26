<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class LanguageAction extends AdminBaseAction{
	function index(){
		$p = array();
		$this->opIndex($p);
	}

	function add(){
        $this->_getOtherInfo();
		$Data = array('LanguageOrder'=>99, 'IsDefault'=>0);
		$this->assign('Data', $Data);
        $p = array();
		$this->opAdd( false, $p );
	}
	
	function saveAdd(){
	    $this->_checkData();
		$p = array();
        $p['AddCallBack'] = "addCallback";
		$this->opSaveAdd( $p );
	}

    protected function addCallback($LanguageID){
	    $this->editComplete($LanguageID>0 ? true : false, $LanguageID);
    }

    protected function saveCallBack($result, $LanguageID){
	    $this->editComplete($result, $LanguageID);
    }

    private function editComplete($result, $LanguageID){
	    if(false === $result) return;
        $LanguageID = intval($LanguageID);
        if($LanguageID > 0){  //添加成功以后创建配置项
            $this->_createLanguageConfig($LanguageID);
        }
        $IsDefault = isset($_POST['IsDefault']) ? intval($_POST['IsDefault']) : 0;
        $m = D('Admin/Language');
        if($IsDefault == 1){
            $m->setDefault($LanguageID, $IsDefault);
        }
        //更新缓存core.php文件
        $config = $m->getLanguageConfig();
        YdCache::writeCoreConfig($config);
        $this->createChannelPurviewField($_POST['LanguageMark']);  //创建权限字段
        $this->createLanguageConfig($_POST['LanguageMark']);  //创建语言配置
        YdCache::deleteAll();  //必须删除所有缓存
    }

    /**
     * 创建权限表对应的字段
     */
    private function createChannelPurviewField($LanguageMark){
        $result = $this->_createChannelPurviewField($LanguageMark, 'AdminGroup');
        $result = $this->_createChannelPurviewField($LanguageMark, 'MemberGroup');
        return $result;
    }

    private function _createChannelPurviewField($LanguageMark, $modelName){
        $fieldName = "ChannelPurview{$LanguageMark}";
        $m = D("Admin/{$modelName}");
        $fields = $m->getDbFields();
        $isExist = false;
        foreach($fields as $v){
            if($v == $fieldName){
                $isExist =  true;
                break;
            }
        }
        if($isExist) return true;

        $tableName = $m->getTableName();  //包含表前缀
        $sql = "ALTER TABLE `{$tableName}` ADD COLUMN `{$fieldName}` TEXT NULL DEFAULT NULL";  //默认向最后面添加
        $result = $m->execute($sql);
        return $result;
    }

    /**
     * 创建语言配置
     */
    private function createLanguageConfig($mark){
        import("@.Common.YdTemplateConfig");
        $list = array('Home', 'Wap');
        foreach($list as $groupName){
            $tc = $this->getTemplateConfigInstance($groupName, $mark);
            if($tc) $tc->createLanguageNode($mark);
        }
    }

    private function getTemplateConfigInstance($groupName, $mark){
        //创建语言节点
        if($groupName=='Home'){
            $t = $this->getHomeTpl(); //获取Home模板数据
            $fileName = $t['pHomeConfig'];
        }else{
            $t = $this->getWapTpl(); //获取Home模板数据
            $fileName = $t['pWapConfig'];
        }
        if(!file_exists($fileName)) return false;
        $tc = new YdTemplateConfig($fileName, $mark);
        if(!$tc){
            $this->ajaxReturn(null, '读取模板配置文件失败' , 0);
        }
        return $tc;
    }

    /**
     * 为语言创建配置项
     */
    private function _createLanguageConfig($LanguageID){
        $LanguageID = intval($LanguageID);
        if(1==$LanguageID) return; //系统默认语言，不能删除也不能修改
        $m = D('Admin/Config');
        $where = "LanguageID={$LanguageID}";
        $id = (int)$m->where($where)->getField('ConfigID');
        if($id > 0) return;  //语言配置项的更新，放到全局缓存更新

        //获取当期默认语言的配置
        $field = "ConfigID,ConfigName,ConfigValue,ConfigFile,IsEnable";
        $datalist = $m->where("LanguageID=1")->field($field)->select();
        if(empty($datalist)) return;
        $startLanguageID = $LanguageID * 1000; //语言标准起始ConfigID
        foreach($datalist as $k=>$v){
            $datalist[$k]['LanguageID'] = $LanguageID;
            $datalist[$k]['ConfigID'] = $startLanguageID + (int)$v['ConfigID']; //配置项ID和语言ID进行标准化对应
        }
        $result = $m->addAll($datalist);
        return $result;
    }
	
	function modify(){
        $this->_getOtherInfo();
		$p = array();
		$this->opModify(false, $p);
	}

	private function _getOtherInfo(){
	    //获取语言
        $LanguageList = AllLanguage();
        $this->assign('LanguageList', $LanguageList);
        $list = glob("./Public/Images/mark/*.png");
        $data = array();
        foreach ($list as $filename) {
            $data[] = basename($filename, '.png');
        }
        $this->assign('MarkList', $data);
    }
	
	function saveModify(){
        $this->_checkData();
        $p['SaveCallBack'] = "saveCallBack";
		$this->opSaveModify($p);
	}

    /**
     * 检查数据
     */
	private function _checkData(){
        $LanguageID = isset($_POST['LanguageID']) ? intval($_POST['LanguageID']) : 0;
        $isAdd = ($LanguageID > 0) ? true : false;
	    $m = D('Admin/Language');
	    //语言名称不能为空
        $name = trim($_POST['LanguageName']);
	    if(empty($name)){
            $this->ajaxReturn(null, '语言名称不能为空!' , 0);
        }
	    $isExist = $m->nameExist($name, 'LanguageName', $LanguageID);
        if($isExist){
            $this->ajaxReturn(null, '语言名称已经存在!' , 0);
        }
        $_POST['LanguageName'] = $name;

        //语言英文标识
	    $mark = trim($_POST['LanguageMark']);
        if(empty($mark)){
            $this->ajaxReturn(null, '语言英文标识不能为空!' , 0);
        }
        $isExist = $m->nameExist($mark, 'LanguageMark', $LanguageID);
        if($isExist){
            $this->ajaxReturn(null, '语言英文标识已经存在!' , 0);
        }
        $_POST['LanguageMark'] = $mark;

        //网站域名
        $domain = trim($_POST['LanguageDomain']);
        if(!empty($domain)){
            if(!preg_match("/^[ a-zA-Z0-9_\.-]+$/i", $domain)){
                $this->ajaxReturn(null, '域名只能是数字、字母、下划线、中划线！' , 0);
            }
            $isExist = $m->nameExist($domain, 'LanguageDomain', $LanguageID);
            if($isExist){
                $this->ajaxReturn(null, '语言单独域名已经存在!' , 0);
            }
        }
        $_POST['LanguageDomain'] = $domain;
    }

	function del(){
	    $m = D('Admin/Language');
        $where['LanguageID'] = intval($_REQUEST['id']);
        $_POST['LanguageMark'] = $m->where($where)->getField('LanguageMark');
		$p = array();
        $p['DelCallBack'] = "delCallBack";
		$this->opDel( $p );
	}

    protected function delCallBack($result){
        $LanguageID = intval($_REQUEST['id']);
	    if(false===$result || empty($LanguageID)) return;
        //中英文的配置项不删除
        if($LanguageID==1 || $LanguageID==2) return;
        $m = D('Admin/Config');
        $where = "LanguageID={$LanguageID}";
        $result = $m->where($where)->delete();
        //删除语言配置
        $this->deleteLanguageConfig($_POST['LanguageMark']);
        return $result;
    }

    /**
     * 删除语言配置
     */
    private function deleteLanguageConfig($mark){
        if('cn'==$mark || 'en'==$mark || empty($mark)) return;
        import("@.Common.YdTemplateConfig");
        $list = array('Home', 'Wap');
        foreach($list as $groupName){
            $tc = $this->getTemplateConfigInstance($groupName, $mark);
            if($tc) $tc->deleteLanguageNode($mark);
        }
    }
	
	function sort(){
		$this->opSort();
	}

    /**
     * 获取语言包
     */
	function getLangPack(){
	    $mark = YdInput::checkLetterNumber($_POST['LanguageMark']);
	    $fileName = LANG_PATH."{$mark}/common.php";
	    $result = false;
	    if(file_exists($fileName)){
            $result = include $fileName;
            foreach($result as $k=>$v){ //部分HTML存在HTML标签
                if(false!==stripos($v, '>') && false!==stripos($v, '</')){
                    $result[$k] = htmlentities($v);
                }
            }
        }
	    unset($result['YouDianSoftware']);  //不显示此项语言包
        $this->ajaxReturn($result, "", 1);
    }

    /**
     * 生成语言包
     */
    function makeLangPack(){
        //1、检查翻译接口
        $appid  = $GLOBALS['Config']['BAIDU_TRANSLATE_APPID'];
        $apikey = $GLOBALS['Config']['BAIDU_TRANSLATE_APIKEY'];
        if(empty($appid) || empty($apikey)){
            $this->ajaxReturn(null, "请先在【应用】-【百度翻译】配置APPID、APPKEY" , 0);
        }

        //2、获取待翻译的内容
        $fileName = LANG_PATH."cn/common.php";
        if(!file_exists($fileName)){
            $this->ajaxReturn(null, "生成语言包失败！中文语言包不存在" , 0);
        }

        $data = include $fileName;
        //$data = array_slice($data, 0,100);
        $content = '';
        foreach($data as $k=>$v){
            /**
             * 百度翻译说明：
             * 您可以在发送的字段 q 中用换行符（在多数编程语言中为转义符号 \n。其中 \n 是需要能被程序解析出来的换行符而不是字符串 \n），
             * 您可以用换行符来分隔要翻译的多个单词或者多段文本，这样您就能得到多段文本独立的翻译结果了。
             * 注意在发送请求之前需对 q 字段做 URL encode！
             */
            //存在html标记会导致翻译不准确
            //必须使用\n\r作为分隔符(不能用@#等，返回后分隔符会部分删除)，翻译后变为<br/>
            $content .= strip_tags($v)."\n";
        }

        //3、获取目标语言
        $mark = YdInput::checkLetterNumber($_POST['LanguageMark']); //目标语言
        if(!yd_is_writable(LANG_PATH)){
            $this->ajaxReturn(null, "语言包目录".LANG_PATH."不可写！" , 0);
        }
        $to = TranslateLang($mark);
        if(empty($to)){
            $this->ajaxReturn(null, "目标语言不存在！" , 0);
        }
        $result = baiduTranslate($content, 'zh', $to);
        if($result && $result['Status']==1){
            //4、生成语言包文件
            $result = $this->_makeLangFile($mark, $data, $result['Content']);
            if(false !== $result){
                $this->ajaxReturn(null, "生成语言包成功！" , 1);
            }else{
                $this->ajaxReturn(null, "创建语言包文件失败！" , 0);
            }
        }else{
            $this->ajaxReturn(null, "生成语言包失败！{$result['ErrorMessage']}" , 0);
        }
    }

    /**
     * 生成目标语言包文件
     */
    private function _makeLangFile($mark, $data, $content){
        $delimiter = "<br/>"; //只能是1个字符
        $temp = explode($delimiter, $content);
        $n1 = count($temp);
        $n2 = count($data);
        if($n1 != $n2){
            return false;
        }
        $i = 0;
        foreach($data as $k=>$v){
            $value = $temp[$i] ? $temp[$i] : '';
            if($k == 'DeleteTip'){
                $value = "<div id='icon_delete'>{$value}</div>";
            }elseif($k == 'SortTip'){
                $value = "<div id='icon_sort'>{$value}</div>";
            }elseif($k == 'ReadLevelTip'){
                $value = "<span id='ReadLevelTip'>{$value}</span>";
            }elseif($k == 'YouDianSoftware'){  //公司名称不翻译
                $value = "{$k}2012";
            }
            $data[$k] = $value;
            $i++;
        }
        $dir = LANG_PATH.$mark;
        if(!is_dir($dir)){
            @mk_dir($dir);
        }
        $fileName = "{$dir}/common.php";
        $b = cache_array($data, $fileName, false);
        if($b){
            $langContent = file_get_contents($fileName);
            $langContent = str_ireplace("'YouDianSoftware2012'", "C('CompanyName')", $langContent);
            file_put_contents($fileName, $langContent);
        }
        return $b;
    }
}
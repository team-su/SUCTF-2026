<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class DecorationAction extends AdminBaseAction{
    private $isAddComponent = false; //表示是否是添加组件操作
	//模板装修首页
	function index(){
        header("Content-Type:text/html; charset=utf-8");
        $m = D('Admin/Member');
        $MemberID = (int)session('AdminMemberID');
        $MemberAvatar = $m->getMemberAvatar($MemberID);
        $this->assign('MemberAvatar', $MemberAvatar);
        $this->assign('ConfigContentUrl', __URL__.'/getConfigContent');
        $this->assign('SaveConfigAction', __URL__.'/saveConfig');
        $this->assign('SaveContentAction', __URL__.'/saveContent');

        $this->assign('SaveCodeAction', __URL__.'/saveCode');
        $this->assign('GetCodeAction', __URL__.'/getCode');

        $this->assign('OrderComponentAction', __URL__.'/orderComponent');
        $this->assign('GetComponentAction', __URL__.'/getComponent');
        $this->assign('AddComponentAction', __URL__.'/addComponent');
        $this->assign('DeleteComponentAction', __URL__.'/deleteComponent');
        $this->assign('QuitDecorationAction', __URL__.'/quitDecoration');
        session('IsMobile', 0);  //重置为非手机模式

        //检查是否启用装修=====================================
        $m = D('Admin/Config');
        $data = $m->getConfig('other'); //配置数据不从缓存中提取
        $this->assign('EnableDecoration', $data['EnableDecoration'] );
        $this->assign('DecorationKey', $data['DecorationKey'] );
        //===============================================
        $ColorList = $this->getColorList(true);
        $this->assign('ColorList', $ColorList);

        $WapExist = $this->_wapExist();
        $this->assign('WapExist', $WapExist);
        $this->_enableDebug();
		$this->display();
	}

	private function _enableDebug(){
        //进入装修，自动开启调试模式
	    $fileName = APP_DATA_PATH.'app.debug';
	    if(!file_exists($fileName)){
            @touch( $fileName );
        }

        $fileName = APP_DATA_PATH.'decoration.lock';
        if(!file_exists($fileName)){
            @touch( $fileName );
        }
    }

	function getColorList($returnString=false){
        $t = $this->getHomeTpl(); //获取Home模板数据
        $fileName = $t['pHomeConfig'];
        if( !file_exists($fileName)){
            return array();
        }
        $lang = get_language_mark();
        import("@.Common.YdTemplateConfig");
        $tc = new YdTemplateConfig($fileName, $lang);
        $colors = $tc->getAllColor();
        if($returnString){ //转化为字符串
            if(!empty($colors)){
                $colors = implode('","', $colors);
                $colors = "\"{$colors}\"";
            }else{
                $colors = 0;
            }
        }
        return $colors;
    }

    /**
     * 退出装修是，关闭调试模式，清除缓存
     */
    function quitDecoration(){
        header("Content-Type:text/html; charset=utf-8");
        @unlink( APP_DATA_PATH.'app.debug' );
        @unlink( APP_DATA_PATH.'decoration.lock' );
        YdCache::writeAll();
        $this->ajaxReturn(null, '' , 1);
    }

	/*
	 * 获取配置内容
	 */
	function getConfigContent(){
        header("Content-Type:text/html; charset=utf-8");
        $GroupID = intval($_POST['GroupID']);
        if(empty($GroupID)){
            $this->ajaxReturn(null, '分组ID不存在!' , 0);
        }
        $TabName = trim($_POST['TabName']);
        $tc = $this->getTemplateConfigInstance();
        $params = array();
        $params['GroupID'] = $GroupID;
        $params['ShowVarNameInHelp'] = false; //是否显示变量名称
        $attributes = $tc->getAttribute($params);

        if(!empty($attributes)){
            $html = $this->_getAttributeHtml($attributes, $TabName);
            //当artdialog包含ckeditor时，会造成编辑器上传等对话框的input无法点击，必须去掉锁屏
            $IsLock = (false !==stripos($html, 'window.CKEDITOR_BASEPATH') || false !==stripos($html, 'window.UEDITOR_HOME_URL') ) ? false : true;
            $data = array('Html'=>$html, 'Count'=>count($attributes), 'IsLock'=>$IsLock);
            $data['MetaData'] = $this->_getMetaData($GroupID);
            $this->ajaxReturn($data, '' , 1);
        }else{
            $this->ajaxReturn(null, '当前没有配置项！' , 0);
        }
    }

    /**
     * 获取组件元数据，找到变量和选择器对应关系，用于实时预览
     */
    private function _getMetaData($GroupID){
        $data = array();
        $fileName = $this->_getOrderTemplateFile($GroupID);
        if(!file_exists($fileName)) return $data;
        //仅用于本地调试=================================
        if(2021==$_SERVER['SERVER_PORT']){
            $baseName = basename($fileName);
            if(false !== stripos($baseName, 'component_')){
                $temp = explode('_', $baseName);
                $fileName = TMPL_PATH."Home/Default/Component/{$temp[1]}";
            }
        }
        //==========================================
        $content = file_get_contents($fileName);
        //1、先匹配获取区块部分的内容
        $pattern = "~<!--区块{$GroupID} 开始-->(.*?)<!--区块{$GroupID} 结束-->~is";
        $matches = array();
        $n = preg_match_all($pattern,$content, $matches);
        if(empty($n)) return $data;
        $content = $matches[1][0];  //区块部分内容

        //2、再匹配样式表部分内容
        $pattern = "~<style type=\"text/css\">(.*?)</style>~is";
        $matches = array();
        $n = preg_match_all($pattern,$content, $matches);
        if(empty($n)) return $data;
        $style = trim($matches[1][0]);
        //$style = $this->_getTestStyle($GroupID); //测试用
        //我们约定样式必须一行一个
        $style = str_replace("\t", '', $style);
        $style = str_replace(array("\r\n", "\r"), "\n", $style);
        //$style = str_replace("}\n#n", '}#n', $style); //有bug，\n#n可能还存在空白字符
        $style = preg_replace("/}\s+?#n{$GroupID}/", "}#n{$GroupID}", $style);
        //必须去掉媒体查询（要求媒体查询必须放在最后面）
        $pos = stripos($style, '@media');
        if($pos>0){
            $style = substr($style, 0, $pos);
        }
        $style = '}'.trim($style);
        $key = "}#n{$GroupID}";
        $temp = explode($key, $style);
        foreach($temp as $k=>$v){
            if(empty($v)) continue;
            $arr = explode('{', $v, 2);
            $list = explode("\n", $arr[1]);
            $Items = array();
            foreach($list as $item){
                $item = trim($item);
                if(strlen($item)<3) continue;
                $Items[] = trim($item,';');
            }
            $data[] = array('Name'=>"#n{$GroupID} {$arr[0]}", 'Items'=>$Items);
        }
        return $data;
    }

    /**
     * 获取测试样式
     */
    private function _getTestStyle($GroupID){
        /**
         *  【情况1】逗号分开：#n207 li.n1, #n207 li.n2{ width: {$TArticle7BigPictureWidth207}%; }
         * 【情况2】带媒体查询：@media screen and (min-width: 1199px) {#n207 li img,#n207 li .Super{height: {$TArticle7SmallHeight207}px;}
         */
        $style = "#n{$GroupID}{
				{\$TArticle7Bg{$GroupID}|ParseBg} 
				padding:{\$TArticle7Padding{$GroupID}}px 0;
			}
			#n{$GroupID} .component_title h2 a{ 
				{\$TArticle7TitleFont{$GroupID}|ParseFont}
			}
			#n{$GroupID} .SuperInfoTime .d{
				{\$TArticle7ListDayFont{$GroupID}|ParseFont}
			}
			#n{$GroupID} li{
				animation-duration: {\$TArticle7AnimationTime{$GroupID}}s; 
				animation-fill-mode: both;
				/*width: {\$TArticle7BigPictureWidth{$GroupID}+100-\$TArticle7BigPictureWidth{$GroupID}}%;*/
			}
			#n{$GroupID} li.n1, #n{$GroupID} li.n2{
				width: {\$TArticle7BigPictureWidth{$GroupID}}%;
			}
			@media screen and (min-width: 1199px) {
				#n{$GroupID} li img,
				#n{$GroupID} li .SuperInfoTime{
					height: {\$TArticle7SmallHeight{$GroupID}}px;
				}
				#n{$GroupID} li.n1 img,
				#n{$GroupID} li.n2 img{
					height: {\$TArticle7BigHeight{$GroupID}}px;
				}
			}";
        return $style;
    }

    /**
     * 获取属性HTML
     */
    private function _getAttributeHtml($attributes, $CurrentTabName=''){
	    $html = '';
        $class = "";
        $tabs = array();
        $tabIndex = 0;
        $style = '';
        $map = array('animation'=>1, 'list'=>1, 'table'=>1, 'editor'=>1, 'line'=>1);
        foreach($attributes as $v){
            if(!empty($v['Tab'])){
                $tabName = $v['Tab'];
                $class = 'tr'.yd_pinyin($tabName,false);
                $tabs[] = array('TabName'=>$tabName, 'TabID'=>$class);
                $class = "trtab {$class}";
                $tabIndex++;
            }
            if(empty($CurrentTabName)){
                $style = ($tabIndex > 1) ? "style='display:none;'" : '';
            }else{
                $style = ($CurrentTabName != $tabName) ? "style='display:none;'" : '';
            }

            $temp = str_ireplace('__URL__', __URL__, $v['html']);
            $html .= "<tr class='config-tr {$class}' {$style}>";
            if(isset($map[$v['DisplayType']]) || $v['FieldName']=='TCustomStyle'){
                $html .= "<td colspan='2'>{$temp}</td>";
            }else{
                $html .= "<th onclick='copyConfigVar(this)' name=\"{$v['FieldName']}\" title=\"配置变量：{$v['FieldName']}\">{$v['DisplayName']}</th><td>{$temp}</td>";
            }
            $html .= "</tr>";
        }

        if(!empty($tabs)){
            $tabHtml = '<tr class="tabbar"><td colspan="2">';
            $tabHtml .= '<span class="tabwrap">';
            foreach($tabs as $k=>$v){
                if(empty($CurrentTabName)){
                    $current = ($k==0) ? 'current' : '';
                }else{
                    $current = ($CurrentTabName==$v['TabName']) ? 'current' : '';
                }
                $tabHtml .= "<span class='tab-item {$current}' onclick='changeTab(this, \"{$v['TabID']}\")'>{$v['TabName']}</span>";
            }
            $tabHtml .= '</span></td></tr>';
            $html = "{$tabHtml}{$html}";
        }
        return $html;
    }

    /*
     * 保存样式设置
     */
    function saveConfig(){
        header("Content-Type:text/html; charset=utf-8");
        $GroupID = intval($_GET['GroupID']);
        $tc = $this->getTemplateConfigInstance();
        $b = $tc->save($_POST, $GroupID);
        if($b === false){
            $this->ajaxReturn(null, '操作失败!' , 0);
        }else{
            if(!APP_DEBUG){
                YdCache::deleteWap();
                YdCache::deleteHome();
            }
            $this->_enableDebug();
            $data = array();
            //$allHtml = yd_curl_get('http://cms9.a.com/index.php', array('random'=>time()));
            /* $n = preg_match("/<body[^>]*?>(.*\s*?)<\/body>/is",$allHtml, $matches); */
            //$data['Html'] = $allHtml;
            $data['colors'] = $this->getColorList();
            $this->ajaxReturn($data, '操作成功!' , 1);
        }
    }

    /**
     * 获取默认配置类实例
     */
    function getTemplateConfigInstance(){
        $PageUrl = $_REQUEST['PageUrl'];
        $GroupName = $this->_getCurrentGroup($PageUrl);
        if($GroupName=='Home'){
            $t = $this->getHomeTpl(); //获取Home模板数据
            $fileName = $t['pHomeConfig'];
        }else{
            $t = $this->getWapTpl(); //获取Home模板数据
            $fileName = $t['pWapConfig'];
        }
        if( !file_exists($fileName)){
            $this->ajaxReturn(null, '模板配置文件不存在' , 0);
        }
        $lang = get_language_mark();
        import("@.Common.YdTemplateConfig");
        $tc = new YdTemplateConfig($fileName, $lang);
        if(!$tc){
            $this->ajaxReturn(null, '读取模板配置文件失败' , 0);
        }
        return $tc;
    }

    /*
     * 保存样式设置，仅对就地修改有效
     */
    function saveContent(){
        header("Content-Type:text/html; charset=utf-8");
        $dataString = trim($_POST['DataString']);
        if(empty($dataString)){
            $this->ajaxReturn(null, '参数错误!' , 0);
        }
        $result = false;
        $content = trim($_POST['Content']); //必须去除2边的空格回车，否则会导致频道名称加回车
        //格式：0数据模型, 1主键ID值, 2字段名称，3编辑类型（必填默认为textarea、image）
        $data = explode(',', $dataString);
        $fieldName = $data[2];
		$data[1] = intval($data[1]);
        $type = strtolower($data[0]);
        if('channel' == $type){ //频道
            $map = 'ChannelName,ChannelSContent,ChannelPicture,ChannelIcon,f1,f2,f3,f4,f5';
            if(false===stripos($map, $fieldName)){
                $this->ajaxReturn(null, '禁止修改' , 0);
            }
            $m = D('Admin/Channel');
            $where = "ChannelID={$data[1]}";
            $result = $m->where($where)->setField($fieldName, $content);
        }elseif('info' == $type){
            $map = 'InfoTitle,InfoSContent,InfoPicture,f1,f2,f3,f4,f5';
            if(false===stripos($map, $fieldName)){
                $this->ajaxReturn(null, '禁止修改' , 0);
            }
            $m = D('Admin/Info');
            $where = "InfoID={$data[1]}";
            $result = $m->where($where)->setField($fieldName, $content);
        }elseif('banner' == $type){ //幻灯片（允许修改所有字段）
            $m = D('Admin/Banner');
            $where = "BannerID={$data[1]}";
            $result = $m->where($where)->setField($fieldName, $content);
        }
        if($result !== false){
            $this->_enableDebug();
            $this->ajaxReturn(null, '保存成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败!' , 0);
        }
    }

    //====================组件编辑 开始====================

    /**
     * 获取所有组件
     */
    function getComponent(){
        header("Content-Type:text/html; charset=utf-8");
        $Slot = isset($_POST['Slot']) ? $_POST['Slot'] : 0;
        $api = $this->_getApiInstance();
        $result = $api->getComponent();
        if(false !== $result){
            //一般添加，不显示子组件==========================
            if(empty($Slot)){
                $data = array();
                foreach($result as $v){
                    if($v['ComponentClassKey'] != 'child'){
                        $data[] = $v;
                    }
                }
                //======================================
                $result = $data;
            }
            $this->ajaxReturn($result, "" , 1);
        }else{
            $error = $api->getLastError();
            $this->ajaxReturn(null,"获取组件失败！{$error}", 0);
        }
    }

    /**
     * 获取api对象
     */
    private function _getApiInstance(){
        import("@.Common.YdApi");
        $api = new YdApi();
        if(!empty($_POST['Token'])){
            $api->setToken($_POST['Token']);
        }
        //如果是删除，需要验证key的有效性，否则不能删除
        //增加了免费插件，所以不需要控制删除
        /*
        $actionName = strtolower(ACTION_NAME);
        if('deletecomponent' == $actionName){
            $isBuy = $api->checkBuyDecoration();
            if(!$isBuy){
                $this->ajaxReturn(null, $api->getLastError(), 0);
            }
        }
        */
        return $api;
    }

    //===========================添加组件  开始===========================
    /*
     * 添加组件
     */
    function addComponent(){
        header("Content-Type:text/html; charset=utf-8");
        $this->isAddComponent = true;
        //1、获取组件数据
        $data = $this->_getComponentData();
        //2、插入配置文件
        if(empty($_POST['MaxGroupID'])){
            $maxGroupID = $this->_addComponentConfig($data);
        }else{ //复用配置
            $maxGroupID = intval($_POST['MaxGroupID']);
            $tc = $this->getTemplateConfigInstance();
            $result = $tc->setUsed($maxGroupID);
            if(!$result){
                $this->ajaxReturn(null, '增加分组计数失败！', 0);
            }
        }

        //3、添加模板HTML代码
        $this->_addComponentHtml($data, $maxGroupID);
        //4、动态获取最新HTML内容，会存在问题：1）动画不生效，2）不会显示虚框
        //$this->_getComponentHtml($data, $maxGroupID);
        if(!APP_DEBUG) {
            YdCache::deleteWap();
            YdCache::deleteHome();
        }
        $this->_enableDebug();
        $this->ajaxReturn($data, '' , 1);
    }

    /**
     * 获取组件数据
     */
    private function _getComponentData(){
        if(empty($_POST['GroupID'])){
            $this->ajaxReturn(null, '分组ID不能为空', 0);
        }
        if(empty($_POST['ComponentClassKey'])){
            $this->ajaxReturn(null, '分类KEY不能为空', 0);
        }
        $GroupID = intval($_POST['GroupID']);
        $ComponentClassKey = $_POST['ComponentClassKey'];
        $api = $this->_getApiInstance();
        //1、获取当前组件的HTML和配置数据
        $data = $api->findComponent($ComponentClassKey, $GroupID);
        if(false===$data){
            $lastError = $api->getLastError();
            $this->ajaxReturn(null, "获取组件数据失败 {$lastError}", 0);
        }
        return $data;
    }

    /**
     *  添加组件配置
     */
    private function _addComponentConfig(&$data){
        //获取当前模板配置最大分组ID
        $tc = $this->getTemplateConfigInstance();
        $maxGroupID = $tc->getMaxGroupID();
        $result = $tc->addGroup($maxGroupID, $data['GroupName'], $data['Config']);
        if(false === $result){
            $msg = $tc->getLastError();
            $this->ajaxReturn(null, "添加配置项失败！{$msg}", 0);
        }
        return $maxGroupID;
    }


    /**
     * 添加组件HTML代码
     */
    private function _addComponentHtml(&$data, $maxGroupID){
        //当前点击的区块的分组ID
        $CurrentGroupID = intval($_POST['CurrentGroupID']);
        $Slot = $_POST['Slot']; //表示子组件ID
        $isHomePage = false;
        $fileName = $this->_getOrderTemplateFile($CurrentGroupID, $isHomePage);
        $this->_checkTemplateFile($fileName);
        if(empty($_POST['GroupID'])){
            $this->ajaxReturn(null, '分组ID不存在!' , 0);
        }
        $classKey = $_POST['ComponentClassKey'];
        //如果是首页
        if($isHomePage){
            if($classKey=='channel'){
                $this->ajaxReturn(null, '首页不能插入【频道内页】组件！' , 0);
            }
            if($classKey=='detail'){
                $this->ajaxReturn(null, '首页不能插入【详情页】组件！' , 0);
            }
        }

        $isHeaderOrFooter = ($classKey=='head' || $classKey=='foot') ? true : false;
        $content = file_get_contents($fileName);
        //当前插入的区块是否存在================================
        $tagToAdd = "<!--区块{$maxGroupID} 开始-->";
        if(false !== stripos($content, $tagToAdd)){
            $this->ajaxReturn(null, "区块{$maxGroupID}已经存在，不能重复添加！" , 0);
        }
        //================================================

        $tag = "<!--区块{$CurrentGroupID} 开始-->";
        $pos = stripos($content, $tag);
        if(!empty($Slot)){
            if($isHeaderOrFooter){
                $this->ajaxReturn(null, '当前位置禁止插入页头和页脚组件！' , 0);
            }
            $tag = "yd-slot=\"{$Slot}\">";
            $pos = stripos($content, $tag, $pos);
            if(false===$pos){
                $this->ajaxReturn(null, '插槽不存在！' , 0);
            }

            //结束符位置
            $endTag = "<!--区块{$CurrentGroupID} 结束-->";
            $endPos = stripos($content, $endTag, $pos);
            $pos += strlen($tag);
            if($endPos===false || $pos>=$endPos){
                $this->ajaxReturn(null, '插入位置错误！' , 0);
            }
        }

        //页头组件只能插入在头部
        if($isHeaderOrFooter){
            if(false ===$pos) $pos = 0;
        }else{
            //如果当前区块不存在，就在body前面插入
            if(false ===$pos) $pos = stripos($content, '<include file="Public:footer" />');
            if(false ===$pos) $pos = stripos($content, '</body>');
        }

        $html = str_ireplace($data['GroupID'], $maxGroupID, $data['ComponentHtml']);
        $content = substr_replace($content, $html, $pos, 0);
        $result = file_put_contents($fileName, $content);
        return $result;
    }

    /**
     * 获取组件最新的内容
     */
    private function _getComponentHtml(&$data, $maxGroupID){
        $url = $_POST['PageUrl'];
        $content = yd_curl_get($url);
        if(empty($content)){
            $this->ajaxReturn(null, '当前页面数据为空', 0);
        }
        $pattern = "<!--区块{$maxGroupID} 开始-->.*?<!--区块{$maxGroupID} 结束-->";
        $pattern = "~{$pattern}~is";
        $matches = array();
        $n = preg_match_all($pattern,$content, $matches);
        if(empty($n)){
            $this->ajaxReturn(null, "区块{$maxGroupID}不存在", 0);
        }
        $data['ComponentHtml'] = $matches[0][0];
    }
    //===========================添加组件  结束===========================

    /*
     * 删除组件
     */
    function deleteComponent(){
        header("Content-Type:text/html; charset=utf-8");
        $this->_getApiInstance();
        $fileName = $this->_getOrderTemplateFile($_POST['GroupID']);
        $this->_checkTemplateFile($fileName);
        if(empty($_POST['GroupID'])){
            $this->ajaxReturn(null, '分组ID不存在!' , 0);
        }
        $content = file_get_contents($fileName);
        $GroupID= trim($_POST['GroupID']);
        /*
        if($GroupID < 5000){
            $this->ajaxReturn(null, "只能删除自定义组件！" , 0);
        }
        */
        $startTag = "<!--区块{$GroupID} 开始-->";
        $endTag = "<!--区块{$GroupID} 结束-->";
        $startPos = stripos($content, $startTag);
        $endPos = stripos($content, $endTag);
        if(false ===$startPos || false === $endPos){
            $this->ajaxReturn(null, "当前区块[{$GroupID}]不存在！" , 0);
        }
        $tc = $this->getTemplateConfigInstance();
        $result = $tc->deleteGroup($GroupID);

        if($result){
            $newContent = substr($content, 0, $startPos);
            $newContent .= substr($content, $endPos+strlen($endTag));
            $result = file_put_contents($fileName, $newContent);
            if(!APP_DEBUG) {
                YdCache::deleteWap();
                YdCache::deleteHome();
            }
            $this->_enableDebug();
            $this->ajaxReturn(null, "删除成功!" , 1);
        }else{
            $errorMsg = $tc->getLastError();
            $this->ajaxReturn(null, "删除失败!{$errorMsg}" , 0);
        }
    }

    /*
     * 保存排序
     */
    function orderComponent(){
        header("Content-Type:text/html; charset=utf-8");
        $fileName = $this->_getOrderTemplateFile($_POST['CurrentGroup']);
        $this->_checkTemplateFile($fileName);
        if(empty($_POST['TargetGroup'])){
            $this->ajaxReturn(null, '不能移动，目标分组ID不存在!' , 0);
        }
        $error = "";
        $result = $this->_orderContent($fileName, $error);
        if($result === true){
            if(!APP_DEBUG) {
                YdCache::deleteWap();
                YdCache::deleteHome();
            }
            $this->_enableDebug();
            $this->ajaxReturn(null, "移动成功!" , 1);
        }else{
            $this->ajaxReturn(null, "移动失败!{$error}" , 0);
        }
    }

    /**
     * 检查模板文件
     */
    private function _checkTemplateFile($fileName){
        if(empty($fileName)){
            $this->ajaxReturn(null, '模板文件名为空!', 0);
        }
        $isWrite = yd_is_writable($fileName);
        if(!$isWrite){
            $this->ajaxReturn(null, '模板文件只读，没有权限修改！' , 0);
        }
    }

    /**
     * 获取当前key的内容
     */
    private function _orderContent($fileName, &$error){
        $content = file_get_contents($fileName);
        $CurrentContent = $this->_getCurrentContent($content, $error);
        if(empty($CurrentContent)){
            return false;
        }
        $TargetGroup = trim($_POST['TargetGroup']);
        //moveup：升序，movedown：降序
        if ($_POST['Order']=='moveup') {
            $tag = "<!--区块{$TargetGroup} 开始-->";
            $pos = stripos($content, $tag);
        }else{
            $tag = "<!--区块{$TargetGroup} 结束-->";
            $pos = stripos($content, $tag);
            if(false !== $pos )$pos += strlen($tag);
        }
        if(false === $pos){
            $error = "目标区块[{$TargetGroup}]不存在！";
            return false;
        }
        $content = substr_replace($content, $CurrentContent, $pos, 0);
        $result = file_put_contents($fileName, $content);
        if(false !== $result){
            return true;
        }else{
            return "";
        }
    }

    /**
     * 获取当前块的内容
     */
    private function _getCurrentContent(&$content, &$error){
        $CurrentGroup = trim($_POST['CurrentGroup']);
        $startTag = "<!--区块{$CurrentGroup} 开始-->";
        $endTag = "<!--区块{$CurrentGroup} 结束-->";
        $startPos = stripos($content, $startTag);
        $endPos = stripos($content, $endTag);
        if(false ===$startPos || false === $endPos){
            $error = "当前区块[{$CurrentGroup}]不存在！";
            return false;
        }
        $endPos += strlen($endTag);
        $length = $endPos - $startPos;
        $CurrentString = substr($content, $startPos, $length);
        if(empty($CurrentString)){
            $error = "当前区块[{$CurrentGroup}]为空！";
            return false;
        }
        $newContent = substr($content, 0, $startPos);
        $newContent .= substr($content, $endPos);
        $content = $newContent;
        //前后加上换行，便于对齐格式
        //$CurrentString = $CurrentString;
        return $CurrentString;
    }

    /**
     * 获取待排序模板的文件路径
     * $CurrentGroupID：当前分组ID
     */
    private function _getOrderTemplateFile($CurrentGroupID, &$isHomePage=false){
        $PageUrl = trim($_POST['PageUrl']);  //必须删除页面锚点：如：/index.php#page1
        $myPos = stripos($PageUrl, '#');
        if($myPos>0){
            $PageUrl = substr($PageUrl, 0, $myPos);
        }
        $langlist = C('LANG_LIST');
        foreach($langlist as $mark=>$v){
            $PageUrl = str_ireplace("/l/{$mark}",  '',  $PageUrl);
            $PageUrl = str_ireplace("/{$mark}/",   '/',  $PageUrl);
        }

        /*
        $PageUrl = str_ireplace(array('/l/cn', '/l/en'), '', $PageUrl);
        //英文版 http://cms9.a.com/index.php/en/news.html，必须要替换里面的en
        $PageUrl = str_ireplace(array('/cn/', '/en/'), '/', $PageUrl);
        */

        $Prefix = $this->_getTemplatePath($PageUrl);
        //PageUrl可能的值：
        //新闻页：http://cms9.a.com/index.php/news.html
        //首页：http://cms9.a.com/index.php，启用伪静态后是：http://cms9.a.com/
        $name =  strtolower(basename($PageUrl)); //basename(""http://cms9.a.com/) 返回cms9.a.com
        $ComponentClassKey = $_POST['ComponentClassKey'];
        $Slot = $_POST['Slot']; //表示子组件ID
        //header和foot组件只能插入头部和底部文件==============================================
        if($ComponentClassKey=='head' || false!==stripos($Slot, 'head')){
            $templateFile = "{$Prefix}Public/header.html";
            if(!file_exists($templateFile)) return false;
            return $templateFile;
        }elseif($ComponentClassKey=='foot'  || false!==stripos($Slot, 'foot')){
            $templateFile = "{$Prefix}Public/footer.html";
            if(!file_exists($templateFile)) return false;
            return $templateFile;
        }
        //=======================================================================
        $dir = 'Channel';  //主页模板目录
        $isHomePage = false;
        //启用伪静态后，首页substr($PageUrl,-1)，返回/
        if('index.php' == strtolower($name) || empty($name) || $name=='wap' || '/'==substr($PageUrl,-1) ){ //首页
            $templateFile = 'index.html';
            $isHomePage = true;
        }elseif($name=='en' || false!==stripos($name, 'l=en')){ //表示英文版首页
            $m = D('Admin/Channel');
            $where = "ChannelID=2";  //2：表示英文版
            $templateFile = $m->where($where)->getField('IndexTemplate');
            $templateFile = str_ireplace('.html', '', $templateFile);
            $templateFile = "{$templateFile}.html";
            $isHomePage = true;
        }elseif(false!==stripos($name, 'resume')){ //应聘页面
            $templateFile = 'resume.html';
        }elseif('search'==$name){ //应聘页面
            $templateFile = 'search.html';
        }else{
            $urlInfo = parse_url($PageUrl);
            $path = str_ireplace('/index.php', '', $urlInfo['path']);
            $n = substr_count($path, '/');
            $where = array();
            $temp = explode('.', $name); //去掉扩展名
            if($n==1){ //主页模板
                $where['Html'] = YdInput::checkHtmlName($temp[0]);
                $fieldName = 'IndexTemplate';
            }else{ //阅读模板
                $dir = 'Info';  //阅读模板目录
                if(is_numeric($temp[0])){
                    $where['ChannelID'] = ChannelID($temp[0]); //先获取信息的频道ID
                }else{  //信息自定义了文件名
                    $where['Html'] = YdInput::checkHtmlName($temp[0]);
                }
                $fieldName = 'ReadTemplate';
            }
            $m = D('Admin/Channel');
            //$where['LanguageID'] = get_language_id(); //不能使用get_language_id获取，它获取的是后端语言
            $where['LanguageID'] = $this->_getPageLanguageID();
            $templateFile = $m->where($where)->getField($fieldName);
            if(!empty($templateFile)){
                $templateFile = str_ireplace('.html', '', $templateFile);
                $templateFile = "{$templateFile}.html";
            }
        }
        $fileName = false;
        $list = array("{$dir}/{$templateFile}", "Public/header.html", "Public/footer.html");
        if(!empty($templateFile)){
            $keywords = "<!--区块{$CurrentGroupID} 开始-->";
            $debugKey = '<include file="Component:'; //主要用于组件本地调试
            foreach($list as $v){
                $templateFile = "{$Prefix}{$v}";
                if(!file_exists($templateFile)) continue;
                $content = file_get_contents($templateFile);
                if(false !== stripos($content, $keywords)){
                    $fileName = $templateFile;
                    break;
                }elseif(2021==$_SERVER['SERVER_PORT'] && false !== stripos($content, $debugKey)){
                    $fileName = $templateFile;
                    break;
                }
            }
        }
        //只有添加组件操作，才做自动判断==================================================
        if(true== $this->isAddComponent ){
            if(false !==stripos($fileName, 'Public/header.html') || false !== stripos($fileName, 'Public/footer.html')){
                $canAdd = $this->_canAddToHeadOrFoot($_POST['GroupID'], $ComponentClassKey);
                if(!$canAdd){
                    $fileName = "{$Prefix}{$list[0]}";
                }
            }
        }
        //======================================================================
        return $fileName;
    }

    /**
     * 获取页面的语言
     * 不能使用get_language_id获取，它获取的是后端语言
     */
    private function _getPageLanguageID(){
        $map = C('LANG_LIST');
        $EnableMultiLauguage = intval(C('LANG_AUTO_DETECT')); //是否启用多语言
        if(1 == $EnableMultiLauguage){  //多语言
            $url = $_POST['PageUrl'];
            foreach($map as $k=>$v){
                if(isset($_REQUEST['l']) && $_REQUEST['l']==$k){
                    $LanguageID = $v['LanguageID'];
                    break;
                }elseif(false!==stripos($url, "/{$k}/")){
                    $LanguageID = $v['LanguageID'];
                    break;
                }
            }
        }else{  //单语言
            $mark = C('DEFAULT_LANG'); //默认语言标记，如：cn、en等
            $LanguageID = intval($map[$mark]['LanguageID']);
        }
        if(empty($LanguageID)) $LanguageID = 1;
        return $LanguageID;
    }

    /**
     * 判断组件能否插入到头部或尾部
     */
    private function _canAddToHeadOrFoot($GroupID, $ComponentClassKey){
        //除了头部组件、页脚组件、幻灯片组件、和以下组件，其他组件只能插入在主页中
        $classMap = array('head'=>1, 'foot'=>1, 'banner'=>1, 'support'=>1);
        if(isset($classMap[$ComponentClassKey])){
            return true;
        }
        $map = array('701'=>'广告图组件', '702'=>'空白占位符', '2301'=>'子导航', '2315'=>'封面图片');
        if(isset($map[$GroupID])){
            return true;
        }
        return false;
    }

    /**
     * 获取当前模板路径
     */
    function _getTemplatePath($PageUrl){
        $GroupName = $this->_getCurrentGroup($PageUrl);
        $ThemeName = C("{$GroupName}_DEFAULT_THEME");
        $path = TMPL_PATH."{$GroupName}/{$ThemeName}/";
        return $path;
    }

    /**
     * 判断手机版是否存在，如果存在，在点击左上角【手机】或【平板】时，打开手机版进行装修
     */
    private function _wapExist(){
        $ThemeName = C("WAP_DEFAULT_THEME");
        $path = TMPL_PATH."Wap/{$ThemeName}/";
        if(!file_exists("{$path}Channel/index.html")){
            return false;
        }
        $config = &$GLOBALS['Config'];
        if(0==$config['WAP_STATUS']){
            return false;
        }
        return true;
    }

    /**
     * 根据当前URL获取当前分组（判断当前是装修手机版还是电脑版）
     */
    function _getCurrentGroup($PageUrl){
        if(empty($PageUrl)){
            $this->ajaxReturn(null, 'PageUrl参数为空' , 0);
        }
        $name = 'Home';
        //如果手机版不存在，就直接返回Home
        if(!$this->_wapExist()){
            return $name;
        }
        $wapDomain = WapUrl();
        if(!empty($wapDomain) && false !== stripos($PageUrl, $wapDomain)){
            $name = 'Wap';
        }elseif(empty($PageUrl)){
            $name = 'Home';
        }else{
            $temp = explode('/', $PageUrl);
            foreach($temp as $v){
                if('wap' == strtolower($v)){
                    $name = 'Wap';
                    break;
                }
            }
        }
        return $name;
    }
    //====================组件编辑 结束====================

    /**
     * 获取自定义代码
     */
    function getCode(){
        header("Content-Type:text/html; charset=utf-8");
        $TemplatePath = $this->_getTemplatePath($_POST['PageUrl']);
        $fileName = "{$TemplatePath}Public/code.html";
        $content = '';
        if(file_exists($fileName)){
            $content = file_get_contents($fileName);
        }
        if(empty($content)){
            $content = "<style>
/*----------------------通用样式----------------------*/ 

/*----------------------大屏幕 大桌面显示器 (≥1200px)----------------------*/
@media screen and (min-width: 1200px) {

}

/*----------------------小屏幕 平板 (≥700px并且≤1199px)----------------------*/
@media screen and (min-width: 700px) and (max-width: 1199px) {

}

/*----------------------超小屏幕 手机 (≤699px)----------------------*/
@media screen and (max-width: 699px) {

}
</style>
  
<!--------------------------自定义脚本代码 开始------------------------>
<script  type=\"text/javascript\">
$(document).ready(function(){
  
});
</script>
<!--------------------------自定义脚本代码 结束------------------------>";
        }
        $this->ajaxReturn($content, '' , 1);
    }

    /**
     * 保存自定义代码
     */
    function saveCode(){
        header("Content-Type:text/html; charset=utf-8");
        $TemplatePath = $this->_getTemplatePath($_POST['PageUrl']);
        $fileName = "{$TemplatePath}Public/code.html";
        $content =  stripslashes($_POST['Content']); //自定义代码
        $content = strip_tags($content, '<style><script><br>');  //仅允许样式和脚本文件
        $result = YdInput::checkTemplateContent($content);
        if(true !== $result){
            $this->ajaxReturn(null, $result , 0);
        }

        // 禁止包含外部文件
        //\1是反向引用，匹配第一个捕获组的内容，即引号
        $pattern = '/<script\s+[^>]*?src\s*=\s*["\'](.*?)["\'][^>]*>/i';
        $matches = array();
        if (preg_match($pattern, $content, $matches)) {
            $this->ajaxReturn(null, '不能引用js脚本文件' , 0);
        }

        //禁止引入css文件 正则表达式匹配 <link rel="stylesheet" href="..."> 标签
        $pattern = '/<link\s+[^>]*?\s*href\s*=\s*["\'](.*?)["\']/i';
        $matches = array();
        if (preg_match($pattern, $content, $matches)) {
            $this->ajaxReturn(null, '不能引用css文件' , 0);
        }

        //不能包含php标签
        $list = array('<php>', '</php>', '{:', '{$', 'sqllist');
        foreach($list as $v){
            if ( false !== stripos($content, $v)) {
                $this->ajaxReturn(null, '包含非法脚本代码' , 0);
            }
        }

        $result = file_put_contents($fileName, $content);
        if(false !== $result){
            if(!APP_DEBUG) {
                YdCache::deleteWap();
                YdCache::deleteHome();
            }
            $this->_enableDebug();
            $this->ajaxReturn(null, '保存成功' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败' , 0);
        }
    }

    /**
     * 备份当前模板
     */
    function backupTemplate(){
        $GroupName = $this->_getCurrentGroup($_POST['PageUrl']);
        $ThemeName = C("{$GroupName}_DEFAULT_THEME");
        $homeTpl = TMPL_PATH."{$GroupName}/";
        $tdir = $homeTpl.$ThemeName;
        if(is_dir( $tdir )){
            import('ORG.Util.PclZip');
            if( !file_exists(APP_DATA_PATH.'zip')){
                mk_dir(APP_DATA_PATH.'zip');
            }
            $time = date("Y-m-d_H_i_s", time()).rand_string(4,10);
            $zipfile = APP_DATA_PATH."zip/{$GroupName}_{$ThemeName}_{$time}.zip";
            $zipfile = strtolower($zipfile);
            $archive = new PclZip($zipfile);
            $v_list = $archive->create($tdir, PCLZIP_OPT_REMOVE_PATH, $homeTpl);
            if ($v_list == 0) {
                $this->ajaxReturn(null, '备份模板失败' , 0);
            }else{
                $this->ajaxReturn(null, "备份模板成功！", 1);
            }
        }else{
            $this->ajaxReturn(null, '备份模板失败，模板目录不存在！' , 0);
        }
    }

    /**
     * 设置预览模式
     */
    function setPreviewMode(){
        header("Content-Type:text/html; charset=utf-8");
        $type = intval($_POST['Type']);
        if(1==$type){
            session('IsMobile', 0);
        }else{
            session('IsMobile', 1);
        }
        $this->_enableDebug();
        $this->ajaxReturn(null, "设置成功！", 1);
    }

    /**
     * 根据当前选中组件的GroupID，获取相同组件类型配置
     */
    function getComponentGroupID(){
        $type = basename($_POST['ComponentPicture'], '.jpg');
        if(empty($type)){
            $this->ajaxReturn(null, '类型参数不能为空！' , 0);
        }
        //<group id="31" name="文章详情样式设置" order="31"/>
        //<var title="是否显示区块" name="TArticle13Show5002" groupid="5002" >
        $tc = $this->getTemplateConfigInstance();
        $data = $tc->getComponentGroupID($type);
        $this->ajaxReturn($data, "", 1);
    }
}
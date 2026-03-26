<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class ChannelAction extends AdminBaseAction {
	function index(){
		header("Content-Type:text/html; charset=utf-8");
		//非超级管理员需要判断权限================
		$gid = intval(session('AdminGroupID'));
		$idlist = false;
		if($gid != 1){
			$m = D('Admin/AdminGroup');
			$idlist = $m->getChannelPurview( $gid );
		}
		//=================================
        $Parent = isset($_REQUEST['Parent']) ? $_REQUEST['Parent'] : 0;
		$c = D('Admin/Channel');
		$channel = $c->getChannelList($Parent, -1, '', $idlist);
		$count = is_array($channel) ? count( $channel ) : 0;
		//找出ChannelDepth最大值
		$maxDepth = -9999;
		for($j = 0; $j < $count; $j++){
			if( $channel[$j]['ChannelDepth'] > $maxDepth ) {
				$maxDepth = $channel[$j]['ChannelDepth'];
			}
		}
		
		//归一化频道深度
		for($i = 0; $i < $count; $i++){
			$channel[$i]['ChannelDepth'] = ($maxDepth - $channel[$i]['ChannelDepth'] + 1);
		}

        $ParentChannel = $c->getChannelSource(0, 2);
        $this->assign('ParentChannel', $ParentChannel);
        $this->assign('Parent', $Parent);
		$this->assign('Channel', $channel);
		$this->display();
	}
	
	function add(){
		header("Content-Type:text/html; charset=utf-8");
		//模型属性信息==============================================
		$m = D('Admin/Attribute');
		$Attribute = $m->getAttribute(1);
		$Group = $m->getGroup(1);
		$gid = intval(session('AdminGroupID'));
		
		for($n = 0; $n < count($Attribute); $n++){
			if( $this->IsSelectedAttribute( $Attribute[$n]['DisplayType'] ) ){
				if ( $Attribute[$n]['DisplayType'] == 'channelselect'){
					$Attribute[$n]['FirstValue'] = "0"; //FirstValue
					$Attribute[$n]['FirstText'] = "作为主频道"; //FirstText
					$Attribute[$n]['AdminGroupID'] = $gid;
				}
			}
		}
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//======================================================
		$this->assign('Action', __URL__.'/saveAdd');
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}
	
	function batchAdd(){
		header("Content-Type:text/html; charset=utf-8");
		//获取频道信息
		$m1 = D('Admin/Channel');
		$gid = intval(session('AdminGroupID'));
		$channel = $m1->getChannel(0,true,true, -1, -1, '&nbsp;&nbsp;&nbsp;&nbsp;├─ ', $gid, 'ChannelID,ChannelName,Html'); //不显示链接频道
		$n = is_array($channel) ? count($channel) : 0;
		
		$m2 = D('Admin/ChannelModel');
		$model = $m2->getChannelModel(0,1,true);
		
		$this->assign('Channel', $channel);
		$this->assign('ChannelModel', $model);
		$this->assign('Action', __URL__.'/saveBatchAdd');
		$this->display();
	}
	
	function saveBatchAdd(){
		header("Content-Type:text/html; charset=utf-8");
		$arrParent = $_POST['Parent'];
		$arrChannelName = $_POST['ChannelName'];
		$arrChannelModelID = $_POST['ChannelModelID'];
		$arrChannelOrder = $_POST['ChannelOrder'];
		$arrPageSize = $_POST['PageSize'];
		$arrIndexTemplate = $_POST['IndexTemplate'];
		$arrReadTemplate = $_POST['ReadTemplate'];
		
		$data = array();
		$count = count($arrChannelName);
		for($i = 0; $i<$count; $i++){
			if( $arrChannelName[$i] != ''){
				$data[] = array('ChannelName'=>$arrChannelName[$i],
					'Parent'=>$arrParent[$i],
					'ReadLevel'=>'',
					'ChannelModelID'=>$arrChannelModelID[$i],
					'ChannelOrder'=>$arrChannelOrder[$i],
					'PageSize'=>$arrPageSize[$i],
					'Html'=>yd_pinyin( $arrChannelName[$i] ),
					'ChannelTarget'=>'_self',
					'IndexTemplate'=>$arrIndexTemplate[$i],
					'ReadTemplate' =>$arrReadTemplate[$i],
					'LanguageID'=>get_language_id(),
				);
			}
		}
		if( empty($data) ){
			$this->ajaxReturn(null, '添加失败!' , 0);
		}
		
		$m = D('Admin/Channel');
		if($m->addAll( $data )){
            YdCache::deleteAdmin();
			YdCache::writeConfig(); //清除频道缓存
			WriteLog(implode(',', $arrChannelName));
			$this->ajaxReturn(null, '添加成功!' , 1);
		}else{
			$this->ajaxReturn(null, '添加失败!' , 0);
		}
	}
	
	function saveAdd(){
		header("Content-Type:text/html; charset=utf-8");
		if( empty($_POST['Html']) ){
			if(function_exists('iconv')){
				$_POST['Html'] = yd_pinyin( $_POST['ChannelName'] );
			}else{
				$this->ajaxReturn(null, "服务器不支持iconv，无法\"生成静态页面名称\"，请录入！" , 0);
			}
		}
        $this->_checkChannel();
		
		$_POST['ChannelDepth'] = 1;  //默认为1
		$c = D('Admin/Channel');
		if( $c->create() ){
			if($GLOBALS['Config']['AUTO_UPLOAD_ENABLE']==1 && stripos($c->ChannelContent , '<img') !== false ){
				$temp = yd_upload_content($c->ChannelContent); //自动上传远程图片
				$c->ChannelContent = $temp[2];
			}
			if($c->add()){
                $this->setTabIndex();
			    YdCache::deleteAdmin();
				$lastID = $c->getLastInsID();
				if( $_POST['Parent'] != 0){ //等于0表示1即栏目，无需更新频道缓存
					YdCache::writeConfig(); //清除频道缓存
				}
				WriteLog("ID:{$lastID} {$_POST['ChannelName']}");
				$msg = baidu_push_channel($lastID);
				$this->ajaxReturn(null, "添加频道成功！{$msg}" , 1);
			}else{
				$this->ajaxReturn(null, '添加频道失败！' , 0);
			}
		}else{
			$this->ajaxReturn(null, $c->getError() , 0);
		}
	}
	
	function modify(){
		header("Content-Type:text/html; charset=utf-8");
		//参数有效性检查===========================
		$ChannelID = $_GET['ChannelID'];
		if( !is_numeric($ChannelID)){
			alert("非法参数", __URL__.'/index');
		}
		//====================================
		
		//模型属性信息=================================================
		$m = D('Admin/Attribute');
		$Attribute = $m->getAttribute(1);
		$Group = $m->getGroup(1);
		
		//获取频道数据=====================
		$c = D('Admin/Channel');
		$ChannelInfo = $c->find( $ChannelID );
		$total = count($Attribute);
		$gid = intval(session('AdminGroupID'));
		for($n = 0; $n < $total; $n++){
			if( $this->IsSelectedAttribute( $Attribute[$n]['DisplayType'] ) ){
				if ( $Attribute[$n]['DisplayType'] == 'channelselect'){
					$Attribute[$n]['SelectedValue'] = $ChannelInfo[ 'Parent' ]; //获取频道设置值
					$Attribute[$n]['FirstValue'] = "0"; //FirstValue
					$Attribute[$n]['FirstText'] = "作为主频道"; //FirstText
					$Attribute[$n]['ExcludeChannel'] = $ChannelID; //不显示某个频道
					$Attribute[$n]['AdminGroupID'] = $gid;
				}else if($Attribute[$n]['DisplayType'] == 'membergroupcheckbox'){ //会员分组checkbox
					$Attribute[$n]['DisplayValue'] = $ChannelInfo[ $Attribute[$n]['FieldName'] ];
				}else{
					$Attribute[$n]['SelectedValue'] = $ChannelInfo[ $Attribute[$n]['FieldName'] ]; //获取频道设置值
				}
			}else{
				$Attribute[$n]['DisplayValue'] = $ChannelInfo[ $Attribute[$n]['FieldName'] ];
			}
		}
		
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//=========================================================
        //当前选中项，用于配合装修使用
        $TabIndex = $this->getTabIndex();
        $this->assign('TabIndex', $TabIndex);
		$this->assign('ChannelID', $ChannelID);
        $this->assign('ChannelModelID', $ChannelInfo['ChannelModelID']);
		$this->assign('Action', __URL__.'/saveModify');
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}
	
	function saveModify(){
		header("Content-Type:text/html; charset=utf-8");
		//Html存在，并且ChannelName不为空在自动生成拼音文件名（单页频道不生成）
		if( isset($_POST['Html']) && empty($_POST['Html'])  && !empty($_POST['ChannelName']) ){
			$_POST['Html'] = yd_pinyin( $_POST['ChannelName'] );
		}
		$this->_checkChannel();
		$c = D('Admin/Channel');
		if( $c->create() ){
			YdCache::deleteChannelHtml( $_POST['Html'] ); //保存频道时，清除频道缓存
            $this->setTabIndex();
			if($GLOBALS['Config']['AUTO_UPLOAD_ENABLE']==1 && stripos($c->ChannelContent , '<img') !== false ){
				//上传远程图片有，需要同步更新编辑器内容
				$temp = yd_upload_content($c->ChannelContent);
				$c->ChannelContent = $temp[2];
				if($c->save() === false){
					$this->ajaxReturn(null, '修改失败!' , 0);
				}else{
					//需要做是否启用自动上传远程图片判断 0:远程地址，1：本地地址
					$this->ajaxReturn($temp[0], $temp[1] , 1);
				}
			}else{
				if($c->save() === false){
					$this->ajaxReturn(null, '修改频道失败!' , 0);
				}else{
                    YdCache::deleteAdmin();
					YdCache::writeConfig(); //清除频道缓存
					WriteLog("ID:{$_POST['ChannelID']} {$_POST['ChannelName']}");
					$this->ajaxReturn(null, '修改频道成功!' , 1);
				}
			}
		}else{
			$this->ajaxReturn(null, $c->getError() , 0);
		}
	}

    /**
     * 编辑频道有效性检查
     */
	private function _checkChannel(){
        unset($_POST['AlbumTitle'], $_POST['AlbumPicture'], $_POST['AlbumDescription']);
        foreach ($_POST as $k=>$v){
            if( is_array($v) ){
                $_POST[$k] = implode(',', $v);
            }
        }
        $this->checkTemplateFile();  //验证模板文件的有效性
        //验证html的有效性
        if(!empty($_POST['Html'])){
            $html = strtolower(trim($_POST['Html']));
            if(!preg_match("/^[a-zA-Z0-9_-]+$/i", $html)){
                $this->ajaxReturn(null, '静态页面名称只能是字母数字、下划线、中划线' , 0);
            }
            $map = array('admin'=>1, 'member'=>1, 'app'=>1, 'home'=>1, 'wap'=>1);
            $loginName = strtolower(C('ADMIN_LOGIN_NAME'));
            if(!empty($loginName)){
                $map[$loginName] = 1;
            }
            if(isset($map[$html])){
                $this->ajaxReturn(null, "静态页面名称不能是【{$html}】" , 0);
            }
        }

        if( !isset($_POST['ReadLevel']) ) $_POST['ReadLevel'] = '';
        if( !isset($_POST['f1']) ) $_POST['f1'] = '';
        if( !isset($_POST['f2']) ) $_POST['f2'] = '';
        if( !isset($_POST['f3']) ) $_POST['f3'] = '';
    }

    /**
     * 检查模板的有效性，防止恶意指定模板路径
     */
    private function checkTemplateFile(){
	    $pattern = "/^[\.a-zA-Z0-9_-]+$/i";
        //频道首页模板(链接频道可以为空)
        $IndexTemplate = strtolower($_POST['IndexTemplate']);
        if(!empty($IndexTemplate)){
            if(!preg_match($pattern, $IndexTemplate)){
                $this->ajaxReturn(null, '频道首页模板无效！' , 0);
            }
            if('html' !== yd_file_ext($IndexTemplate)){
                $this->ajaxReturn(null, '频道首页模板扩展名必须是 .html' , 0);
            }
        }

        //频道阅读模板
        if(!empty($_POST['ReadTemplate'])){ //阅读模板是可以为空的
            $ReadTemplate = strtolower($_POST['ReadTemplate']);
            if(!preg_match($pattern, $ReadTemplate)){
                $this->ajaxReturn(null, '频道阅读模板无效！' , 0);
            }
            if('html' !== yd_file_ext($ReadTemplate)){
                $this->ajaxReturn(null, '频道阅读模板扩展名必须是 .html' , 0);
            }
        }
    }

	function del(){
		header("Content-Type:text/html; charset=utf-8");
		$c = D('Admin/Channel');
		$ChannelID = $_GET["ChannelID"];
		$data = "#t$ChannelID";
		if( !is_numeric($ChannelID) ){
			$this->ajaxReturn($data, '参数非法!' , 3);
		}
		
		if( $c->hasChildChannel($ChannelID) ){
			$this->ajaxReturn($data, '当前频道有子频道,请先删除子频道!' , 2);
		}
		
		if( $c->hasData($ChannelID) ){
			$this->ajaxReturn($data, '当前频道有数据,请先删除频道所有数据!' , 2);
		}
		
		//删除操作
		$fileToDel = $c->getAttachment($ChannelID);
		if( $c->safeDelChannel($ChannelID) ){
			batchDelFile($fileToDel);
            YdCache::deleteAdmin();
			YdCache::writeConfig(); //清除频道缓存
			WriteLog("ID:$ChannelID");
			$this->ajaxReturn($data, '删除频道成功!' , 1);
		}else{
			$this->ajaxReturn($data, '删除频道失败!' , 0);
		}
	}

	function getSpecial(){
		$ChannelID = $_GET['ChannelID'];
		if( is_numeric($ChannelID) ){
			$s = D('Admin/Special');
			$SpecialInfo = $s->getSpecial( array('IsEnable'=>1) );
			$option = "<optgroup label='请选择所属专题（按Ctrl+左键可进行多选）'>";
			foreach($SpecialInfo as $key=>$value){
				$v = $value['SpecialID'];
				$t = $value['SpecialName'];
				$option .= "<option value='$v'>$t</option>";
			}
			$option .= '</optgroup>';
			$this->ajaxReturn(null, $option , 1);
		}
	}
	
	/**
	 * 单页频道
	 */
	function single(){
		header("Content-Type:text/html; charset=utf-8");
		
		$ChannelID = $_GET['ChannelID'];
		if( !is_numeric($ChannelID) ){
			alert("非法参数", __URL__.'/single');
		}

		//模型属性信息==============================================
		$c = D('Admin/Channel');
		$ChannelInfo = $c->find( $ChannelID );
		
		$m = D('Admin/Attribute');
		$Attribute = $m->getAttribute($ChannelInfo['ChannelModelID']);
		$Group = $m->getGroup($ChannelInfo['ChannelModelID']);
		
		for($n = 0; $n < count($Attribute); $n++){
		   if( $this->IsSelectedAttribute( $Attribute[$n]['DisplayType'] ) ){
				if ( $Attribute[$n]['DisplayType'] == 'channelselect'){
					$Attribute[$n]['SelectedValue'] = $ChannelInfo[ 'ChannelID' ]; //获取频道设置值
					$Attribute[$n]['FirstValue'] = "0"; //FirstValue
					$Attribute[$n]['FirstText'] = "作为主频道"; //FirstText
				}else if($Attribute[$n]['DisplayType'] == 'membergroupcheckbox'){ //会员分组checkbox
					$Attribute[$n]['DisplayValue'] = $ChannelInfo[ $Attribute[$n]['FieldName'] ];
				}else{
					$Attribute[$n]['SelectedValue'] = $ChannelInfo[ $Attribute[$n]['FieldName'] ]; //获取频道设置值
				}
			}else{
				$Attribute[$n]['DisplayValue'] = $ChannelInfo[ $Attribute[$n]['FieldName'] ];
			}
		}
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//======================================================
		$this->assign('Action', __URL__.'/saveModify');
		$this->assign('ChannelID', $ChannelID);
		$this->assign('ChannelName', $ChannelInfo['ChannelName']);
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}
	
	/**
	 * 链接频道
	 */
	function link(){
		header("Content-Type:text/html; charset=utf-8");
	
		$ChannelID = $_GET['ChannelID'];
		if( !is_numeric($ChannelID) ){
			alert("非法参数", __URL__.'/Save');
		}
	
		//模型属性信息==============================================
		$c = D('Admin/Channel');
		$ChannelInfo = $c->find( $ChannelID );
	
		$m = D('Admin/Attribute');
		$Attribute = $m->getAttribute($ChannelInfo['ChannelModelID']);
		$Group = $m->getGroup($ChannelInfo['ChannelModelID']);
	
		for($n = 0; $n < count($Attribute); $n++){
			if( $this->IsSelectedAttribute( $Attribute[$n]['DisplayType'] ) ){
					$Attribute[$n]['SelectedValue'] = $ChannelInfo[ $Attribute[$n]['FieldName'] ]; //获取频道设置值
			}else{
				$Attribute[$n]['DisplayValue'] = $ChannelInfo[ $Attribute[$n]['FieldName'] ];
			}
		}
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//======================================================
		$this->assign('Action', __URL__.'/saveModify');
		$this->assign('ChannelID', $ChannelID);
		$this->assign('ChannelName', $ChannelInfo['ChannelName']);
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}
	
	/**
	 * 保存所有修改
	 */
	function saveAll(){
		$data = array(
				"ChannelID" => $_POST['ChannelID'],
				"ChannelName" => $_POST['ChannelName'],
				"Html" => $_POST['Html'],
				"ChannelOrder" => $_POST['ChannelOrder'],
				"PageSize" => $_POST['PageSize'],
				"ChannelSName" => $_POST['ChannelSName'],
				//"IndexTemplate" => $_POST['IndexTemplate'],
				//"ReadTemplate" => $_POST['ReadTemplate']
		);
        $n = is_array($data['ChannelID']) ? count($data['ChannelID']) : 0;
		if( $n > 0 ){
		    //去掉名称里的&nbsp;和空格
		    foreach($data['ChannelName'] as $k=>$v){
                $data['ChannelName'][$k] = trim($v);
            }
			$m = D('Admin/Channel');
			$m->saveAll( $data );
			YdCache::deleteHtml('channel'); //清楚所有频道缓存
			YdCache::deleteHtml('index'); //清楚所有频道缓存
			WriteLog();
		}
        $Parent = isset($_REQUEST['Parent']) ? $_REQUEST['Parent'] : 0;
		redirect(__URL__."/index/Parent/{$Parent}");
	}
	
	/**
	 * 清除频道缓存
	 */
	function clearCache(){
        YdCache::deleteAdmin();
		YdCache::writeConfig();
		$this->ajaxReturn(null, '清除频道缓存成功!' , 1);
	}

    /**
     * 创建模板
     */
	function createTemplate(){
        $TemplateName = trim($_POST['TemplateName']);
        if(!preg_match('/^[A-Za-z0-9_-]+$/',  $TemplateName )){
            $this->ajaxReturn(null, '模板文件名只能是字母、数字、下划线、中划线！' , 0);
        }
        $TemplateName = "{$TemplateName}.html";
        $type = intval($_POST['TemplateType']);
        $modelID = intval($_POST['ChannelModelID']);
        $ThemeName = C("HOME_DEFAULT_THEME");
        $path = TMPL_PATH."Home/{$ThemeName}/";
        if(1==$type){
            $path .= 'Channel/';
        }else{
            $path .= 'Info/';
        }
        $isWrite = yd_is_writable($path);
        if(!$isWrite){
            $this->ajaxReturn(null, '创建模板失败，模板目录没有写入权限！' , 0);
        }
        $fileName = $path.$TemplateName;
        if(file_exists($fileName)){
            $this->ajaxReturn(null, "创建模板失败，{$TemplateName}已经存在！" , 0);
        }
        $content = getEmptyTemplateContent($type, $modelID);
        $result = file_put_contents($fileName, $content);
        if($result){
            WriteLog($fileName);
            $this->ajaxReturn(null, '创建模板成功!' , 1);
        }else{
            $this->ajaxReturn(null, '创建模板失败!' , 0);
        }
    }

    /**
     * 设置当前选项卡，用于记住当前Tab
     */
    private function setTabIndex(){
        if(!isset($_POST['TabIndex'])) return;
        $TabIndex =  intval($_POST['TabIndex']);
        session("TabIndex",$TabIndex);
    }

    private function getTabIndex(){
        $TabIndex = 1;
        if(isset($_GET["TabIndex"])){
            $TabIndex = intval($_GET["TabIndex"]);
        }elseif( session("?TabIndex") ){
            $TabIndex = session("TabIndex");
        }
        return $TabIndex;
    }

    /**
     * 复制频道
     */
    function copy(){
        $LanguageID = intval($_POST['LanguageID']); //目标语言
        if(empty($LanguageID)){
            $this->ajaxReturn(null, "请选择目标语言！" , 0);
        }
        $ChannelID = trim($_POST['ChannelID'], ','); //待复制的频道，多个以逗号分开
        if(empty($ChannelID)){
            $this->ajaxReturn(null, "请选择待拷贝的频道！" , 0);
        }
        $CopyData = intval($_POST['CopyData']); //是否拷贝频道数据

        $m = D('Admin/Channel');
        $data = $m->getChannelToCopy($ChannelID);
        //第1步：用于判断待拷贝的频道是否存在
        $map = $m->where("LanguageID={$LanguageID}")->getField("Html,ChannelID");
        if(!empty($map)){
            foreach($data as $k=>$v){
                $key = $v["Html"];
                if(isset($map[$key])){
                    $this->ajaxReturn(null, "【{$v['ChannelName']}】频道在目标语言中已经存在！" , 0);
                }
            }
        }

        //第2步：复制频道
        $map = array(); //旧ChannelID=>新ChannelID
        foreach($data as $k=>$v){
            $cid = $v['ChannelID'];
            unset($v['ChannelID']);
            $v['LanguageID'] = $LanguageID;
            $newChannelID = $m->add($v);
            if($newChannelID > 0){
                $mid = $v['ChannelModelID'];
                $map[$cid] = $newChannelID;
                //拷贝频道内容（32：	单页模型，33：链接模型）
                if($CopyData && $mid !=32 && $mid != 33){
                    $this->_copyInfo($cid, $newChannelID, $LanguageID);
                }
            }
        }

        if(!empty($map)){
            //第3步：修改父级频道
            foreach($map as $oldChannelID=>$newChannelID){
                $where = "LanguageID={$LanguageID} AND Parent={$oldChannelID}";
                $result = $m->where($where)->setField('Parent', $newChannelID);
            }
            //清除频道缓存
            YdCache::deleteAdmin();
            YdCache::writeConfig();
            WriteLog("ID:{$ChannelID}", array('LogType'=>2, 'UserAction'=>'频道管理->复制'));
            $this->ajaxReturn(null, "拷贝频道成功！" , 1);
        }else{
            $this->ajaxReturn(null, "拷贝频道失败！" , 0);
        }
    }

    /**
     * 拷贝频道文章
     */
    private function _copyInfo($fromChannelID, $toChannelID, $LanguageID){
        $m = D('Admin/Info');
        $where['ChannelID'] = (int)$fromChannelID;
        $where['LanguageID'] = get_language_id();
        $data = $m->where($where)->select();
        if(empty($data)) return;

        //这里的频道是新创建的，不需要判断文章重复
        foreach($data as $k=>$v){
            unset($v['InfoID']);
            $v['LanguageID'] = $LanguageID;
            $v['ChannelID'] = (int)$toChannelID;
            $v['ChannelIDEx'] = ''; //不考虑扩展频道
            $v['SpecialID'] = '';
            $m->add($v);
        }
        $n = count($data);
        $count = 10;  //每次插入的最大数量
        for($i = 0; $i < $n; $i+=$count){
            $datalist = array_slice($data, $i, $count);
            $m->addAll($datalist);
        }
    }

    /**
     * 批量删除频道
     */
    function batchDel(){
        $m = D('Admin/Channel');
        $ChannelID = trim($_POST['ChannelID'], ',');
        $ChannelIDList = explode(',', $ChannelID);
        foreach($ChannelIDList as $id){
            $result = $m->deleteChannelAllData($id);
        }
        WriteLog("删除频道，ID:{$ChannelID}");
        $this->ajaxReturn(null, "删除频道成功！" , 1);
    }
}
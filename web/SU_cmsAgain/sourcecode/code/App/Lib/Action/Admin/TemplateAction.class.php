<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class TemplateAction extends AdminBaseAction {

	/**
	 * 模板管理
	 */
	function index(){
		header("Content-Type:text/html; charset=utf-8");
		$t = $this->getHomeTpl(); //获取Home模板数据
		$CurrentDir = $this->getTplDir();  //相对路径
		$ParentDir = $this->getTplParentDir();
		
		import("ORG.Io.Dir");
		$d = new Dir($t['pHomeTheme'].$CurrentDir);
		$FileList = $d->toArray();
		$count = count($FileList);
		for($i = 0; $i < $count; $i++){
			$FileList[$i]['mtime'] = yd_friend_date($FileList[$i]['mtime']);
			if( $FileList[$i]['isDir'] == 1 ){
				$dirSize = getdirsize($FileList[$i]['path'].'/'.$FileList[$i]['filename']);
				$FileList[$i]['size'] = byte_format( $dirSize );
				$FileList[$i]['filetype'] = '文件夹';
				$FileList[$i]['ico'] = $this->WebPublic.'Images/FileICO/folder.gif';
			}else{
				$FileList[$i]['size'] = byte_format( $FileList[$i]['size'] );
				$FileList[$i]['filetype'] = getTplFileType( $FileList[$i]['filename'] );
				$FileList[$i]['fileurl'] = ($CurrentDir == '/') ?  $t['HomeTheme'].$FileList[$i]['filename'] :  $t['HomeTheme'].$CurrentDir.'/'.$FileList[$i]['filename'];
				$extFile = './Public/Images/FileICO/'.$FileList[$i]['ext'].'.gif';
				if( is_file( $extFile ) ){
					$FileList[$i]['ico'] = $this->WebPublic.'Images/FileICO/'.$FileList[$i]['ext'].'.gif';
				}else{
					$FileList[$i]['ico'] = $this->WebPublic.'Images/FileICO/unknown.gif';
				}
			}
			if( substr($CurrentDir, -1, 1) == '/' ){
				$FileList[$i]['RelativePath'] = $CurrentDir.$FileList[$i]['filename'];
			}else{
				$FileList[$i]['RelativePath'] = $CurrentDir.'/'.$FileList[$i]['filename'];
			}
		}
		
		$this->assign('TemplateName', $t['HomeName']);
		$this->assign('CurrentDir', $CurrentDir);
		$this->assign('ParentDir', $ParentDir);
		$this->assign('FileList', $FileList);
		$this->display();
	}
	
	function modify(){
		header("Content-Type:text/html; charset=utf-8");
		$FileName = YdInput::checkFileName($_GET['file']);
		$ThemeName = C('HOME_DEFAULT_THEME');
		$FullFileName = TMPL_PATH.'Home/'.$ThemeName.'/'.ltrim($FileName,'/');
		
		$FileContent = @file_get_contents( $FullFileName );
		$FileContent = htmlspecialchars($FileContent); //HTML实体编码
		$this->preModifyTplFile($FullFileName);
		$this->assign('Action', __URL__.'/saveModify');
		$this->assign('FileName', $FileName);
		$this->assign('FileContent', $FileContent);
		$this->display();
	}
	
	function saveModify(){
		header("Content-Type:text/html; charset=utf-8");
        $this->_checkEdit();
		$ThemeName = C('HOME_DEFAULT_THEME');
		$_POST['FileName'] = YdInput::checkFileName( $_POST['FileName'] );
		$FullFileName = TMPL_PATH.'Home/'.$ThemeName.'/'.ltrim($_POST['FileName'],'/');
		if( !$this->isValidTplFile($FullFileName)){
			$this->ajaxReturn(null, '由于安全问题，禁止修改当前文件!' , 0);
		}
		//实体解码
		$FileContent = htmlspecialchars_decode($_POST['FileContent']); 
		if (get_magic_quotes_gpc()) {
			$FileContent = stripslashes($FileContent);
		}

		$result = YdInput::checkTemplateContent($FileContent);
		if(true !== $result){
            $this->ajaxReturn(null, $result , 0);
        }
		$b = file_put_contents($FullFileName, $FileContent);
		if($b === false){
			$this->ajaxReturn(null, '保存失败!' , 0);
		}else{
			//若修改了public下的文件，则删除模板缓存{
			$ext = strtolower(yd_file_ext($FullFileName));
			if($ext == 'html'){
				$dir = substr( dirname($FullFileName), -20);
				if( stripos($dir, 'Public') !== false ){
					YdCache::deleteHome();
				}
			}
			//}============================
			WriteLog( $FullFileName );
			$this->ajaxReturn(null, '保存成功!' , 1);
		}
		$this->display();
	}
	
	//获取模板当前路径
	public function getTplDir(){
	    $dir = isset($_GET['dir']) ? trim($_GET['dir']) : '';
		$dir = YdInput::checkFileName($dir);
		if ($dir){
			$tplDir = $dir;
		}else{
			$tplDir = '/';
		}
		return $tplDir;
	}
	
	//获取模板上一层路径
	public function getTplParentDir(){
		$parentDir = '/';
		if ( isset($_GET['dir']) ){
			$parentDir = YdInput::checkFileName(trim($_GET['dir']));
			if( $parentDir == '/' ) return $parentDir;
			$parentDir = dirname( $parentDir ); //获取上一级目录
			if( $parentDir == '\\') $parentDir = '/';
			if( strcmp($parentDir, '/') < 0 ){
				$parentDir = '/';
			}
		}
		return $parentDir;
	}
	
	/**
	 * 模板选择
	 */
	function pick(){
		header("Content-Type:text/html; charset=utf-8");
		import("ORG.Io.Dir");
		$h = $this->getHomeTpl(); //获取Home模板数据
		$d = new Dir( $h['pHomeTpl'] );
		$FileList = $d->toArray();
		$count = $FileList ? count($FileList) : 0;
		$t = array();
		for($i = 0; $i < $count; $i++){
			if( $FileList[$i]['isDir'] == 1 ){
				$name = $h['pHomeTpl'].$FileList[$i]['filename'].'/template.xml';
				if( file_exists($name) ){
					$xml = @simplexml_load_file( $name );
					$t[$i]['name'] = (string)$xml->name;
					$t[$i]['dirname'] = $FileList[$i]['filename'];
					$t[$i]['thumbnail'] = $h['HomeTpl'].$FileList[$i]['filename'].'/'.$xml->thumbnail;
					$t[$i]['IsUse'] = ( strtolower($t[$i]['dirname']) == strtolower($h['HomeThemeName'] ) ) ? 1 : 0;
					$t[$i]['ThemeName'] = $FileList[$i]['filename'];
					$t[$i]['Url'] = __URL__.'/savePick/t/'.$FileList[$i]['filename'];
				}
			}
		}
		$this->assign('TplList', $t);
		$this->display();
	}
	
	function savePick(){
		header("Content-Type:text/html; charset=utf-8");
        if(preg_match("/^[a-zA-Z0-9_-]+$/i", $_GET['t'])){
            $data['HOME_DEFAULT_THEME'] = $_GET['t'];
            YdCache::writeCoreConfig($data);
            YdCache::deleteAll();
            WriteLog($_GET['t']);
        }
		redirect(__URL__.'/pick');
	}
	
	/**
	 * 样式管理
	 */
	function style(){
		header("Content-Type:text/html; charset=utf-8");
		$t = $this->getHomeTpl(); //获取Home模板数据
		
		$tplCurrentDir = $this->getTplDir();
		$tplParentDir = $this->getTplParentDir();
		
		import("ORG.Io.Dir");
		$d = new Dir( $t['pHomeCss'].$tplCurrentDir );
		$FileList = $d->toArray();
		$count = count($FileList);
		for($i = 0; $i < $count; $i++){
			$FileList[$i]['mtime'] = yd_friend_date($FileList[$i]['mtime']);
			if( $FileList[$i]['isDir'] == 1 ){
				$dirSize = getdirsize($FileList[$i]['path'].'/'.$FileList[$i]['filename']);
				$FileList[$i]['size'] = byte_format( $dirSize );
				$FileList[$i]['filetype'] = '文件夹';
				$FileList[$i]['ico'] = $this->WebPublic.'Images/FileICO/folder.gif';
			}else{
				$FileList[$i]['size'] = byte_format( $FileList[$i]['size'] );
				$FileList[$i]['filetype'] = getTplFileType( $FileList[$i]['filename'] );
				$FileList[$i]['fileurl'] = ($tplCurrentDir == '/') ?  $t['HomeCss'].$FileList[$i]['filename'] :  $t['HomeCss'].$tplCurrentDir.'/'.$FileList[$i]['filename'];
				$extFile = './Public/Images/FileICO/'.$FileList[$i]['ext'].'.gif';
				if( is_file( $extFile ) ){
					$FileList[$i]['ico'] = $this->WebPublic.'Images/FileICO/'.$FileList[$i]['ext'].'.gif';
				}else{
					$FileList[$i]['ico'] = $this->WebPublic.'Images/FileICO/unknown.gif';
				}
			}
			
			if( substr($tplCurrentDir, -1, 1) == '/' ){
				$FileList[$i]['RelativePath'] = $tplCurrentDir.$FileList[$i]['filename'];
			}else{
				$FileList[$i]['RelativePath'] = $tplCurrentDir.'/'.$FileList[$i]['filename'];
			}
		}
		
		$this->assign('TemplateName', $t['HomeName']);
		$this->assign('CurrentDir', $tplCurrentDir);
		$this->assign('ParentDir', $tplParentDir);
		$this->assign('FileList', $FileList);
		$this->display();
	}
	
	function modifyStyle(){
		header("Content-Type:text/html; charset=utf-8");
		$FileName = YdInput::checkFileName($_GET['file']);
		$ThemeName = C('HOME_DEFAULT_THEME');
		$FullFileName = TMPL_PATH.'Home/'.$ThemeName.'/Public/css/'.ltrim($FileName,'/');
		
		$FileContent = file_get_contents( $FullFileName );
		$this->preModifyTplFile($FullFileName);
		$this->assign('Action', __URL__.'/saveModifyStyle');
		$this->assign('FileName', $FileName);
		$this->assign('FileContent', $FileContent);
		$this->display();
	}
	
	function saveModifyStyle(){
		header("Content-Type:text/html; charset=utf-8");
        $this->_checkEdit();
		$FileName = YdInput::checkFileName($_POST['FileName']);
		$ThemeName = C('HOME_DEFAULT_THEME');
		$FullFileName = TMPL_PATH.'Home/'.$ThemeName.'/Public/css/'.ltrim($FileName,'/');
		
		if( !$this->isValidTplFile($FullFileName)){
            $this->ajaxReturn(null, '由于安全问题，禁止修改当前文件!' , 0);
		}
		
		$FileContent = $_POST['FileContent'];
		
		if (get_magic_quotes_gpc()) {
			$FileContent = stripslashes($FileContent);
		}
		
		$b = file_put_contents($FullFileName, $FileContent);
		if($b === false){
			$this->ajaxReturn(null, '保存失败!' , 0);
		}else{
			WriteLog($FullFileName );
			$this->ajaxReturn(null, '保存成功!' , 1);
		}
		$this->display();
	}

	/**
	 * 删除模板
	 */
	function del(){
		$tname = YdInput::checkFileName(trim( $_GET['tname'] ) );  //当前模板目录
		$data = "#t$tname";
		$tdir = TMPL_PATH.'Home/'.$tname;
		if(is_dir( $tdir )){
			@deldir( $tdir );
			WriteLog($tdir);
			$this->ajaxReturn($data, '删除成功!' , 1);
		}else{
			$this->ajaxReturn($data, '删除失败!' , 1);
		}
	}
	
	/**
	 * 备份电脑模板和手机模板
	 */
	function backup(){
		$tname = YdInput::checkFileName(trim( $_GET['tname'] ));  //当前模板目录
		//区分手机模板和电脑模板
		$homeTpl = TMPL_PATH.'Home/';
		$tdir = $homeTpl.$tname;
		if(is_dir( $tdir )){
			set_time_limit(300);
			import('ORG.Util.PclZip');
			if( !file_exists(APP_DATA_PATH.'zip')){
				mk_dir(APP_DATA_PATH.'zip');
			}
			$zipfile = APP_DATA_PATH.'zip/home_'.$tname.'_'.date("Y-m-d_H_i_s", time()).'_'.rand_string(6,10).'.zip';
			$zipfile = strtolower($zipfile);
			$archive = new PclZip($zipfile);
			$v_list = $archive->create($tdir, PCLZIP_OPT_REMOVE_PATH, $homeTpl);
			if ($v_list == 0) {
				alert('备份模板失败!', __URL__.'/pick');
			}else{
				//备份不能使用ajax提交
				$downfile = @fopen($zipfile,"r");
				$downsize = @filesize($zipfile);
				$downname = $tname.'_'.date("Y-m-d_H_i_s", time()).'.zip'; //文件名不能包含冒号:
				WriteLog($tdir);
				@Header("Content-type: application/octet-stream"); 
				@Header("Accept-Ranges: bytes"); 
				@Header("Accept-Length: ".$downsize); 
				@Header("Content-Disposition: attachment; filename=".$downname); 
				echo @fread($downfile, $downsize); 
				@fclose($downfile); 
				//2016-01-30 changed by wang 备份文件存储到zip目录永久保存更好
				//@unlink($zipfile); //下载完毕,删除压缩文件
			}
		}else{
			alert('备份模板失败!', __URL__.'/pick');
		}
	}

	function config(){
		header("Content-Type:text/html; charset=utf-8");
		$t = $this->getHomeTpl(); //获取Home模板数据
		$fileName = $t['pHomeConfig'];
        $Tip = '';
		if( !file_exists($fileName)){
			$HasConfig = 0;
		}else{
			$HasConfig = 1;
			$lang = get_language_mark();
			import("@.Common.YdTemplateConfig");
			$tc = new YdTemplateConfig($fileName, $lang);
			$Attribute = $tc->getAttribute();
			$n = count($Attribute);
			$Tip = "暂无模板配置，如需要，请在模板目录建立模板配置文件config.xml";
			if($n > 60) {
			    $HasConfig = 0;
                $Tip = "配置项过多，请点击顶部【模板装修】进行配置！";
            }else{
                $Group = $tc->getGroup();
                $this->preModifyTplFile($fileName);
                $this->assign('Group', $Group);
                $this->assign('Attribute', $Attribute);
            }
		}
		$this->assign('Tip', $Tip);
        $this->assign('HasConfig', $HasConfig);
		$this->assign('Action', __URL__.'/saveConfig');
		$this->display();
	}
	
	function saveConfig(){
		header("Content-Type:text/html; charset=utf-8");
		$t = $this->getHomeTpl(); //获取Home模板数据
		$fileName = $t['pHomeConfig'];
		$lang = get_language_mark();
		import("@.Common.YdTemplateConfig");
		$tc = new YdTemplateConfig($fileName, $lang);
		$b = $tc->save($_POST);
		if($b === false){
			$this->ajaxReturn(null, '保存失败!' , 0);
		}else{
			YdCache::deleteHome();
			WriteLog($fileName);
			$this->ajaxReturn(null, '保存成功!' , 1);
		}
	}
	
	/**
	 * 设置模板语言包
	 */
	function lang(){
		$tname = YdInput::checkFileName(trim( $_GET['tname'] ));  //当前模板目录
		if( empty($tname) ){
			$tname = C('HOME_DEFAULT_THEME');
		}
		$dir = TMPL_PATH.'Home/'.$tname.'/Lang/';
		$fileNameCn = $dir.'common_cn.php';
		$fileNameEn = $dir.'common_en.php';
        $LangPackCn = array();
		if (is_file($fileNameCn) ){
         	$LangPackCn = include $fileNameCn;
        }

        $LangPackEn = array();
        if (is_file($fileNameEn) ){
        	$LangPackEn = include $fileNameEn;
        }
        $LangPack = array();
        foreach ($LangPackCn as $k=>$v){
        	$ItemEnValue = key_exists($k, $LangPackEn) ? $LangPackEn[$k] : '';
        	$LangPack[] = array('ItemName'=>$k, 'ItemCnValue'=>$v, 'ItemEnValue'=>$ItemEnValue);
        }
        $this->assign('TemplateDir', $tname);
        $this->assign('LangPack', $LangPack);
		$this->assign('Action', __URL__.'/saveLang');
		$this->display();
	}
	
	/**
	 * 保存模板语言包
	 */
	function saveLang(){
		header("Content-Type:text/html; charset=utf-8");
        $this->_checkEdit();
		$langDirName = YdInput::checkFileName(trim( $_POST['TemplateDir'] ));
		
		$ItemName = $_POST['ItemName'];
		$ItemCnValue = $_POST['ItemCnValue'];
		$ItemEnValue = $_POST['ItemEnValue'];
		
		$LangPackCn = array();
		$LangPackEn = array();
		$n = count($ItemName);
		for( $i = 0; $i < $n; $i++ ){
			$k = trim($ItemName[$i]);
			if( $k != '' || $ItemCnValue[$i] != '' || $ItemEnValue[$i] != ''){
				$LangPackCn[$k] = $ItemCnValue[$i];
				$LangPackEn[$k] = $ItemEnValue[$i];
			}
		}
		
		$msg[0] = '保存失败';
		$msg[1] = '保存成功';
		$msg[2] = '没有写入权限';
		
		$bCn = $this->_saveLangFile($LangPackCn, $langDirName, 'cn');
		if($bCn != 1){
			$this->ajaxReturn(null, $msg[$bCn] , 0);
		}
		$bEn = $this->_saveLangFile($LangPackEn, $langDirName, 'en');
		if($bEn != 1){
			$this->ajaxReturn(null, $msg[$bEn] , 0);
		}
		WriteLog($langDirName );
		$this->ajaxReturn(null, $msg[1] , 1);
		$this->display();
	}
	
	private function _saveLangFile($data, $dirName='Default', $languageMark='cn'){
		$dir = TMPL_PATH.'Home/'.$dirName.'/Lang/';
		if( !is_dir($dir) ){
			mk_dir($dir);
		}
		$langfileName = $dir.'common_'.$languageMark.'.php';
		
		$file = is_file($langfileName) ? $langfileName : dirname($dir);
		$b = yd_is_writable($file);
		if(!$b) return 2; //没有写人权限
		
		$b = cache_array($data, $langfileName, false);
		if(!$b) return 0; //写入失败
		return 1;  //保存成功
	}

    /**
     * 删除模板文件
     */
    function deleteTemplateFile(){
        header("Content-Type:text/html; charset=utf-8");
        $this->_checkEdit();
        $FileName = YdInput::checkFileName($_POST['file']);
        $ThemeName = C('HOME_DEFAULT_THEME');
        $FullFileName = TMPL_PATH.'Home/'.$ThemeName.'/'.ltrim($FileName,'/');
        $isWrite = yd_is_writable($FullFileName);
        if(!$isWrite){
            $this->ajaxReturn(null, "删除模板失败，{$FileName}文件只读！" , 0);
        }
        if(!file_exists($FullFileName)){
            $this->ajaxReturn(null, "删除模板失败，{$FileName}文件只读！" , 0);
        }
        WriteLog($FullFileName);
        @unlink($FullFileName);
        $this->ajaxReturn(null, "删除模板文件成功！" , 1);
    }

    /**
     * 创建模板文件
     */
    function addTemplateFile(){
        header("Content-Type:text/html; charset=utf-8");
        $TemplateName = YdInput::checkFileName($_POST['TemplateName']);
        if(!preg_match('/^[A-Za-z0-9_-]+$/',  $TemplateName )){
            $this->ajaxReturn(null, '模板文件名只能是字母、数字、下划线、中划线！' , 0);
        }
        $TemplateName = "{$TemplateName}.html";
        $CurrentDir = YdInput::checkFileName($_POST['CurrentDir']);
        $FileName = "{$CurrentDir}/{$TemplateName}";
        $ThemeName = C('HOME_DEFAULT_THEME');
        $FullFileName = TMPL_PATH.'Home/'.$ThemeName.'/'.ltrim($FileName,'/');
        if(file_exists($FullFileName)){
            $this->ajaxReturn(null, "创建模板失败，{$TemplateName}已经存在！" , 0);
        }
        $type = (false!==stripos($FileName, '/Info')) ? 2 : 1;
        $content = getEmptyTemplateContent($type);
        $result = file_put_contents($FullFileName, $content);
        if($result){
            WriteLog($FullFileName);
            $this->ajaxReturn(null, "创建模板文件成功！" , 1);
        }else{
            $this->ajaxReturn(null, "创建模板文件失败！" , 0);
        }
    }

    private function _checkEdit(){
        $this->ajaxReturn(null, "由于安全问题，禁止在线编辑模板！请通过FTP修改" , 0);
    }
}
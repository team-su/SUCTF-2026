<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class PluginAction extends AdminBaseAction {
	private $_pluginMenuGroupID = 31; //我的插件所属的分组菜单ID

    /**
     * 插件列表
     */
    function index(){
        header("Content-Type:text/html; charset=utf-8");
        $IsEnable = isset($_GET['IsEnable']) ? intval($_GET['IsEnable']) : 1;
        $data = $this->_getPlugList($IsEnable);
        cookie("MenuTopID", 17);

        //编译多端小程序api调用=========================================
        $multiXcxFile = "./App/Plugin/Multixcx/h5/index.html";
        $AdminLoginName = file_exists($multiXcxFile) ? C('ADMIN_LOGIN_NAME') : '';
        $this->assign('AdminLoginName', $AdminLoginName);
        //========================================================

        $this->assign("IsEnable", $IsEnable);
        $this->assign("Data", $data);
        $this->display();
    }

    /**
     * 获取插件列表f
     */
    private function _plugList(){
        import("@.Common.YdApi");
        $api = new YdApi();
        $data = $api->getPlugin();
        return $data;
    }

	/**
	 * 获取插件列表
	 */
	private function _getPlugList($IsEnable){
        $result = array();
	    $data = $this->_plugList();
        $m = D('Admin/Menu');
        $where = "MenuGroupID={$this->_pluginMenuGroupID} AND IsEnable=1";
        $map = $m->where($where)->getField('MenuID,MenuOrder');
        $sortkeys = array();
        foreach($data as $PluginID=>$v){
            if($IsEnable == 1){
                if(!isset($map[$PluginID])) continue;
                $IsSystem = $v['IsSystem'];
                if($IsSystem == 0){ //1表示系统插件，跟随主系统一起升级，0表示插件单独升级
                    $v['CurrentVersion'] = $this->getPluginVersion($v['InstallDir'], $v['FileList']); //获取插件当前版本
                    $v['LatestVersion'] = $v['PluginVersion'];
                    $v['CanUpgrade'] = version_compare($v['LatestVersion'], $v['CurrentVersion'], '>') ? 1 : 0;
                }else{
                    $v['CurrentVersion'] = $v['PluginVersion'];
                    $v['CanUpgrade'] = 0;
                }
                $dir = ($v['PluginTarget'] == '_blank') ? $this->WebInstallDir : __GROUP__ . '/';
                $v['PluginUrl'] = "{$dir}{$v['PluginAction']}";
                $v['PluginOrder'] = $map[$PluginID];
                $sortkeys[] = $v['PluginOrder'];
            }else{
                if(isset($map[$PluginID])) continue;
                $v['CurrentVersion'] = $v['PluginVersion'];
                $v['LatestVersion'] = $v['PluginVersion'];
                $v['PluginTarget'] = '_blank'; //未安装时，都在新页面打开
                $sortkeys[] = $v['PluginOrder'];
            }
            $picture = $v['PluginPicture'];
            //大于50是base64图片
            $v['PluginPicture'] = strlen($picture)>50 ? $picture : "{$this->Images}{$picture}";
            $v['PluginID'] = $PluginID;
            $v['IsEnable'] = $IsEnable;
            $result[] = $v;
        }
        //对插件进行排序
        array_multisort($sortkeys,SORT_ASC, SORT_NUMERIC, $result);
	    return $result;
	}

    /**
     * 获取插件的版本
     */
    private function getPluginVersion($InstallDir, $FileList){
        $fileName = "{$InstallDir}version.txt";
        if(file_exists($fileName)){
            $version = file_get_contents($fileName); //获取当前版本号
        }else{
            //<meta name="version" content="1.0">
            $content = '';
            $fileName = "{$InstallDir}index.html";
            if(file_exists($fileName)){
                $content = file_get_contents($fileName);
            }elseif(!empty($FileList)){
                $temp = explode(',', $FileList);
                if(file_exists($temp[0])) { //版本号默认放在第一个文件
                    $content = file_get_contents($temp[0]);
                }
            }
            if(!empty($content)){
                $pattern = '/<meta[\s]+name="version"[\s]+content="([0-9.]+?)"[\s]*>/i';
                $matches = array();
                $n = preg_match($pattern, $content, $matches);
                if($n>0){
                    $version = $matches[1];
                }
            }
        }
        if(empty($version)) $version = '1.0'; //插件版本一律采用2位：主版本.修订版本
        return $version;
    }

    /**
     * 升级插件
     */
    function upgradePlugin(){
        header("Content-Type:text/html; charset=utf-8");
        yd_set_time_limit(300);
        if(empty($_POST['LatestVersion']) || empty($_POST['PluginID'])){
            $this->ajaxReturn(null, "升级参数错误！", 0);
        }
        import('@.Common.YdUpgrade');
        $u = new YdPluginUpgrade();
        $result = $u->upgradePlugin($_POST['PluginID'], $_POST['CurrentVersion'], $_POST['LatestVersion']);
        if($result){
            YdCache::deleteAll();
            $this->ajaxReturn($result, "升级成功！", 1);
        }else{
            $lastError = $u->getLastError();
            $this->ajaxReturn(null, "升级失败！{$lastError}", 0);
        }
    }

    /**
     * 安装插件
     */
    function installPlugin(){
        header("Content-Type:text/html; charset=utf-8");
        yd_set_time_limit(300);
        $PluginID = intval($_POST['PluginID']);
        $IsSystem = intval($_POST['IsSystem']);
        if(0==$IsSystem){
            if(empty($_POST['PluginID']) || empty($_POST['LatestVersion'])){
                $this->ajaxReturn(null, "参数错误！", 0);
            }
            import('@.Common.YdUpgrade');
            $u = new YdPluginUpgrade();
            $result = $u->installPlugin($PluginID, $_POST['LatestVersion']);
            if($result){
                $this->enable(1, false, $_POST);
                YdCache::deleteAll();
                $this->ajaxReturn($result, "安装成功！", 1);
            }else{
                $lastError = $u->getLastError();
                $code = $u->getCode();
                if(64==$code){ //未购买
                    $this->ajaxReturn(null, $lastError, 3);
                }else{
                    $this->ajaxReturn(null, "安装失败！{$lastError}", 0);
                }
            }
        }else{
            $this->enable(1, true, $_POST);
        }
    }

    /**
     * 卸载插件
     */
    function uninstallPlugin(){
        header("Content-Type:text/html; charset=utf-8");
        yd_set_time_limit(300);
        $PluginID = intval($_POST['PluginID']);
        $IsSystem = intval($_POST['IsSystem']);
        if(0==$IsSystem){
            if(empty($_POST['PluginID'])){
                $this->ajaxReturn(null, "参数错误！", 0);
            }
            $InstallDir = isset($_POST['InstallDir']) ? $_POST['InstallDir'] : '';
            import('@.Common.YdUpgrade');
            $u = new YdPluginUpgrade();
            $result = $u->uninstallPlugin($PluginID, $InstallDir, $_POST['FileList']);
            if($result){
                $this->enable(0, false, $_POST);
                YdCache::deleteAll();
                $this->ajaxReturn($result, "卸载成功！", 1);
            }else{
                $lastError = $u->getLastError();
                $this->ajaxReturn(null, "卸载失败！{$lastError}", 0);
            }
        }else{
            $this->enable(0, true, $_POST);
        }
    }

	/**
	 * 插件排序
	 */
	function sort(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Menu');
		$id = $_POST['PluginOrderID'];
		$order = $_POST['PluginOrder'];

		$n = is_array($id) ? count($id) : 0;
		for($i = 0; $i < $n; $i++){
			$where['MenuID'] = intval( $id[$i] );
			$where['MenuGroupID'] = $this->_pluginMenuGroupID;
			$m->where($where)->setField('MenuOrder', intval($order[$i]));
		}
		$this->ajaxReturn(null, '' , 1);
		//redirect(__URL__."/index");
	}
	
	/**
	 * 是否启用插件
	 */
	function enable($IsEnable=-1, $isAjaxReturn=true, $params=array()){
		header("Content-Type:text/html; charset=utf-8");
        $PluginName = $params['PluginName'];  //插件名称
        $PluginAction = $params['PluginAction'];  //插件名称
		$PluginID = intval($_POST['PluginID']);
		$IsEnable = ($IsEnable==-1) ? intval($_POST['IsEnable']) : intval($IsEnable);

        //如果是安装，还需要改变排序，新安装的永远排在第一个
        $m = D('Admin/Menu');
        $PluginOrder = 99999;  //当前插件的排序
        if(1 == $IsEnable){
            $where = "IsEnable=1 AND MenuGroupID={$this->_pluginMenuGroupID}";
            $PluginOrder = $m->where($where)->min('MenuOrder');
            $PluginOrder = empty($PluginOrder) ? -1 : $PluginOrder-1;
        }

		$id = $m->where("MenuID={$PluginID}")->getField('MenuID');
		if($id > 0){
            $where = "MenuID={$PluginID} AND MenuGroupID={$this->_pluginMenuGroupID}";
            $dataToUpdate = array();
            $dataToUpdate['IsEnable'] = $IsEnable;
            if(99999 !== $PluginOrder) {
                $dataToUpdate['MenuOrder'] = $PluginOrder;
            }
            $result = $m->where($where)->setField($dataToUpdate);
        }else{
		    //如果未安装，就添加记录
            $dataToAdd = array();
            $dataToAdd['MenuID'] = $PluginID;
            $dataToAdd['MenuName'] = $PluginName;
            $dataToAdd['MenuOrder'] = $PluginOrder;
            $dataToAdd['MenuGroupID'] = $this->_pluginMenuGroupID;
            if(!empty($PluginAction)){ //插件控制器
                $dataToAdd['MenuContent'] = $PluginAction;
            }
            $dataToAdd['IsEnable'] = $IsEnable;  //是否启用
            $result = $m->add($dataToAdd);
        }

        if($IsEnable==0){ //卸载时，以下插件自动启用
            $map = array(148=>'DistributeEnable', 169=>'BadWordsEnable', 170=>'BadWordsIP',
                149=>'QiniuEnable', 171=>'BanCopyEnable', 172=>'SHARE_ENABLE', 173=>'AliEnable',
                163=>'EnableDecoration',
            );
        }else{ //安装时，以下插件自动启用
            $map = array(163=>'EnableDecoration',);
        }
        $name = isset($map[$PluginID]) ? $map[$PluginID] : '';
        if(!empty($name)){
            $mc = D('Admin/Config');
            $mc->where("ConfigName='{$name}'")->setField('ConfigValue',$IsEnable);
        }

		if($isAjaxReturn){
            $type = ($IsEnable==1) ? '安装' : '卸载';
            if($result){
                WriteLog("{$type}插件 {$PluginName} 成功！", array('LogType'=>1, 'UserAction'=>'插件管理'));
                $this->ajaxReturn(null, "{$type}插件成功！" , 1);
            }else{
                $this->ajaxReturn(null, "{$type}插件失败！" , 0);
            }
        }
	}

	/**
	 * 三级分销首页
	 */
	function distribution(){
		header("Content-Type:text/html; charset=utf-8");
		
		$this->display();
	}
	
	/**
	 * 三级分销设置
	 */
	function distributionConfig(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Config');
		$data = $m->getConfig('other'); //配置数据不从缓存中提取
		
		//设置
		$DistributeEnable = empty($data['DistributeEnable']) ? 0 : $data['DistributeEnable'];
		$DistributeRequirement = empty($data['DistributeRequirement']) ? 1 : $data['DistributeRequirement'];
		$DistributeMode = empty($data['DistributeMode']) ? 1 : $data['DistributeMode'];
		$MinMoney = intval($data['MinMoney']);
		$OrderRate = (double)($data['OrderRate']);
		$BuyerRate  = (double)($data['BuyerRate']);
		//默认为三级分销
		$ReturnGrade = empty($data['ReturnGrade']) ? 3 : $data['ReturnGrade'];
		
		$this->assign('DistributeEnable', $DistributeEnable );
		$this->assign('DistributeRequirement', $DistributeRequirement);
		$this->assign('MinMoney', $MinMoney );
		$this->assign('DistributeMode', $DistributeMode );
		$this->assign('OrderRate', $OrderRate );
		$this->assign('BuyerRate', $BuyerRate );
		$this->assign('ReturnGrade', $ReturnGrade );
		
		//返利设置
		$this->assign('DistributeEmail', $data['DistributeEmail'] );
		$this->assign('DistributeEmailTitle', $data['DistributeEmailTitle']);
		$this->assign('DistributeEmailBody', $data['DistributeEmailBody'] );
		$this->assign('DistributeSms', $data['DistributeSms']);
		$this->assign('DistributeSmsBody', $data['DistributeSmsBody'] );

		$this->assign('Action', __URL__.'/saveDistributionConfig' );
		$this->display();
	}
	
	/**
	 * 保存三级分销设置
	 */
	function saveDistributionConfig(){
        $fieldMap = array('MinMoney'=>2, 'OrderRate'=>2, 'ReturnGrade'=>2, 'BuyerRate'=>2);
        $data = GetConfigDataToSave($fieldMap, 'Distribute');
        $m = D("Admin/Config");
        if( $m->saveConfig($data) ){
            $description = var_export($data, true);
            WriteLog($description);
            $this->ajaxReturn(null, '保存成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败!' , 0);
        }
	}
	
	/**
	 * 分销商品列表
	 */
	function distributionGoods(){
		header("Content-Type:text/html; charset=utf-8");
		$p['ModuleName'] = 'Info'; //表示有分页
		$p['GetFunctionName'] = 'getDistributionGoods';
		$p['GetCountFunctionName'] = 'getDistributionGoodsCount'; 
		
		$p['HasPage'] = true; //表示有分页
		$Keywords = !empty($_REQUEST['Keywords']) ? YdInput::checkKeyword( $_REQUEST['Keywords'] ) : '';
		$ChannelID = !empty($_REQUEST['ChannelID']) ? intval($_REQUEST['ChannelID']) : -1;
		$p['Parameter'] = array(
				'Keywords' => $Keywords,
				'ChannelID' => $ChannelID,
		);
		
		$m = D('Admin/Channel');
		$AdminGroupID = intval(session('AdminGroupID'));
		$MenuOwner = (strtolower(GROUP_NAME)=='admin') ? 1 : 0;
		$Channel = $m->getChannelPurview($MenuOwner, $AdminGroupID);
        $ChannelNew = array();
		foreach ($Channel as $v){
			$hasChild = $v['HasChild'];
			$channelModelID = $v['ChannelModelID'];
			if( $channelModelID == 36 ){
				$ChannelNew[] = array('ChannelID' => $v['ChannelID'],'ChannelName' => $v['ChannelName']);
			}
		}
		unset( $Channel );
		$this->assign('Channel', $ChannelNew);
		
		$this->assign("DistributeMode", $GLOBALS['Config']['DistributeMode']);
		$this->opIndex( $p );
	}
	
	/**
	 * 分销商列表
	 */
	function distributor(){
		header("Content-Type:text/html; charset=utf-8");
		$p['ModuleName'] = 'Member'; //表示有分页
		$p['GetFunctionName'] = 'getDistributor';
		$p['GetCountFunctionName'] = 'getDistributorCount';
		$p['HasPage'] = true; //表示有分页
		$Keywords = !empty($_REQUEST['Keywords']) ? YdInput::checkKeyword( $_REQUEST['Keywords'] ) : '';
		$DistributorLevelID = !empty($_REQUEST['DistributorLevelID']) ? intval($_REQUEST['DistributorLevelID']) : -1;
		$p['Parameter'] = array(
				'Keywords' => $Keywords,
				'DistributorLevelID' => $DistributorLevelID,
				'IsDistributor' => 1,
		);		
		$this->opIndex( $p );
	}
	
	/**
	 * 查找分销商
	 */
	function findDistributor(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D("Admin/Member");
		$result = $m->findDistributor( $_REQUEST['MembeID'] );
		$this->ajaxReturn($result, '' , 1);
	}
	
	/**
	 * 设置分销商
	 */
	function setDistributor(){
		header("Content-Type:text/html; charset=utf-8");
		$MemberID =  intval($_POST['MemberID']);
		$IsDistributor = intval($_POST['IsDistributor']);
		$DistributorLevelID = intval($_POST['DistributorLevelID']);
		$m = D("Admin/Member");
		$where['MemberID'] = $MemberID;
		$data['IsDistributor'] = $IsDistributor;
		if($IsDistributor == 1){
			$data['DistributorLevelID'] = $DistributorLevelID;
		}
		$result = $m->where($where)->setField($data);
		if( $result === false){
			$this->ajaxReturn(null, '设置失败!' , 0);
		}else{
            $description = var_export($data, true);
            WriteLog($description, array('LogType'=>4, 'UserAction'=>'三级分销->设置分销商'));
			$this->ajaxReturn(null, '设置成功!' , 1);
		}
	}
	
	/**
	 * 添加分销商
	 */
	function addDistributor(){
		header("Content-Type:text/html; charset=utf-8");
		$Keywords = isset($_REQUEST['Keywords']) ? $_REQUEST['Keywords'] : '';
		$p['IsDistributor'] = 0;
		$m = D('Admin/Member');
		import("ORG.Util.Page");
		$TotalPage = $m->getCount($Keywords, 1, -1, $p);
		$PageSize = 15;
		
		$Page = new Page($TotalPage, $PageSize);
		if( $Keywords != ''){
			$Page->parameter .= "&Keywords=$Keywords";
		}
		$Page->rollPage = $this->AdminRollPage;
		$ShowPage = $Page->show();
		$Member= $m->getMember($Page->firstRow, $Page->listRows, $Keywords, 1, -1, $p);
		
		$this->assign("AdminPageSize", $PageSize);
		$this->assign('NowPage', $Page->getNowPage());
		$this->assign('Keywords', $Keywords);
		$this->assign('Data', $Member);
		$this->assign('Page', $ShowPage); //分页条
		$this->display();
	}
	
	//保存设置分销商
	function saveAddDistributor(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Member');
		$result = $m->setDistributorLevel($_POST['id'], $_POST['DistributorLevelID']);
		if( $result === false){
			$this->ajaxReturn(null, '设置失败!' , 0);
		}else{
		    unset($_POST['__hash__']);
            $description = var_export($_POST, true);
            WriteLog($description, array('LogType'=>10, 'UserAction'=>'三级分销->添加分销商'));
			$this->ajaxReturn(null, '设置成功!' , 1);
		}
	}
	
	/**
	 * 分销关系
	 */
	function distributionRelation(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Member');
		if(!empty($_REQUEST['Keywords'])){
			$p['Keywords'] = trim($_REQUEST['Keywords']);
		}else{
			$p['Keywords'] = '';
			$p['InviterID']=0; //仅获取顶级分销商
		}
		$p['IsDistributor']=1;
		$data = $m->getDistributor(-1, -1, $p);
		$this->assign('Keywords', $p['Keywords'] );
		$this->assign('Data', $data);
		$this->display();
	}
	
	/**
	 * 获取下级分销商
	 */
	function getNextDistributionRelation(){
		header("Content-Type:text/html; charset=utf-8");
		$MemberID = intval($_REQUEST['MemberID']);
		$m = D('Admin/Member');
		$data = $m->getNextDistributionRelation($MemberID);
		$this->ajaxReturn($data, '', 1);
	}
	
	/**
	 * 分销商等级
	 */
	function distributorLevel(){
		header("Content-Type:text/html; charset=utf-8");
		$p['ModuleName'] = 'DistributorLevel'; //表示有分页
		$this->opIndex( $p );
	}
	
	/**
	 * 删除分销商级别
	 */
	function delDistributorLevel(){
		header("Content-Type:text/html; charset=utf-8");
		$id = intval($_REQUEST['id'] );
		$mm = D('Admin/Member');
		$MemberID = $mm->where("DistributorLevelID={$id}")->getField('MemberID');
		if($MemberID > 0){
			$this->ajaxReturn(null, '此等级存在数据，不能删除', 0);
		}
		
		$m = D('Admin/DistributorLevel');
		$result = $m->delDistributorLevel( $id );
		if($result){
            $description = "id：{$id}";
            WriteLog($description, array('LogType'=>3, 'UserAction'=>'三级分销->删除分销商级别'));
			$this->ajaxReturn(null, '', 1);
		}else{
			$this->ajaxReturn(null, '', 0);
		}
	}
	
	/**
	 * 添加分销商级别
	 */
	function addDistributorLevel(){
		header("Content-Type:text/html; charset=utf-8");
		unset( $_POST['DistributorLevelID'] );
		if( empty($_POST['DistributorLevelName']) ){
			$this->ajaxReturn('DistributorLevelName', '等级名称不能为空', 0);
		}
		$m = D('Admin/DistributorLevel');
		if( $m->create() ){
			$result = $m->add();
			if($result){
				$LogDescription = 'ID:'.$m->getLastInsID();
				WriteLog( $LogDescription );
				$this->ajaxReturn(null, '添加成功', 1);
			}else{
				$this->ajaxReturn(null, '添加失败', 0);
			}
		}
	}
	
	/**
	 * 修改分销商等级
	 */
	function modifyDistributorLevel(){
		header("Content-Type:text/html; charset=utf-8");
		if( empty($_POST['DistributorLevelName']) ){
			$this->ajaxReturn('DistributorLevelName', '等级名称不能为空', 0);
		}
		$m = D('Admin/DistributorLevel');
		if( $m->create() ){
			$result = $m->save();
			if($result===false){
				$this->ajaxReturn(null, '修改失败', 0);
			}else{
				$LogDescription = 'ID:'.$_POST['DistributorLevelID'];
				WriteLog( $LogDescription );
				$this->ajaxReturn(null, '修改成功', 1);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}

    function email(){
        header("Content-Type:text/html; charset=utf-8");
        $this->display();
    }

    function app(){
        header("Content-Type:text/html; charset=utf-8");
        $this->display();
    }

    /**
	 * 图像批量处理
	 */
	function imageProcess(){
		header("Content-Type:text/html; charset=utf-8");
		$data = $this->getImageProcessConfig();
		$MyFont = GetWaterFonts();
		$this->assign('MyFont',$MyFont);
		$this->assign($data);
		$this->assign('Action', __URL__.'/saveImageProcess' );
		$this->display();
	}
	
	//水印预览
	function waterPreview(){
		return;
		header("Content-type: image/jpg");
		$src = './Public/Images/standard/1.jpg';
		$dst = RUNTIME_PATH.'waterpreview.jpg';
		if( is_file($dst) ){
			@unlink($dst);
		}
		import('ORG.Util.Image.ThinkImage');
		$img = new ThinkImage(THINKIMAGE_GD, $src);
		$type = intval($_GET['WaterType']);
		$position = $_GET['WaterPosition'];
		if( $type == 1 ){ //图片水印
			//初始化时，判断水印图片是否存在
			$pic = $_SERVER['DOCUMENT_ROOT'].$_GET['WaterPic'];
			$img->water($pic, $position)->save($dst);
		}else if( $type == 2 ){ //文字水印
			$text = $_GET['WaterText'];
			$font = "";
			if( !empty($_GET['WaterFont']) ){
				$font = './Public/font/'.$_GET['WaterFont'];
				if(!file_exists($font)){
					$font = '';
				}
			}
			$size = $_GET['WaterTextSize'];
			$color= $_GET['WaterTextColor'];
			$angle = $_GET['WaterTextAngle'];
			$offset = array($_GET['WaterOffsetX'], $_GET['WaterOffsetY']);
			$img->text($text, $font, $size, $color, $position, $offset, $angle)->save($dst);
		}
		if( file_exists($dst) ){
			$g = imagecreatefromjpeg($dst);
			imagejpeg($g, NULL, 100);
			imagedestroy($g);
		}
	}
	
	/**
	 * 获取图像处理配置参数
	 */
	private function getImageProcessConfig(){
		$m = D('Admin/Config');
		$data = $m->where("ConfigName='ImageProcess'")->getField('ConfigValue');
		$data = !empty($data) ? json_decode($data, true) : array();
		foreach ($data as $k=>$v){
			if($v == ''){
				unset($data[$k]);
			}
		}
		$data['WaterText'] = urldecode($data['WaterText']);
		$default = array(
				'ThumbType'=>2,
				'ThumbWidth'=>'200',
				'ThumbHeight'=>'200',
		
				'WaterType'=>2,
				'WaterFont'=>'arial.ttf',
				'WaterTextSize'=>12,
				'WaterTextColor'=>'#FF0000',
				'WaterTextAngle'=>0,
				'WaterOffsetX'=>0,
				'WaterOffsetY'=>0,
				'WaterPosition'=>9,
		
				'ImageFormat'=>'jpg',
				'JpegQuality'=>80,
				'SaveType'=>1,
		);
		$data = array_merge($default, $data);
		return $data;
	}
	
	/**
	 * 保存图像批量处理设置
	 */
	function saveImageProcess(){
		if( isset($_POST) ){
			$this->checkImageProcessParams();
			unset( $_POST['__hash__'] );
			$_POST['WaterText'] = urlencode($_POST['WaterText']); //先编码，否则会产生乱码
			$content = json_encode($_POST);
			$m = D("Admin/Config");
			$b = $m->where("ConfigName='ImageProcess'")->setField('ConfigValue',$content);
			if($b !== false){
				$this->ajaxReturn(null, '图像处理初始化成功!' , 1);
			}else{
				$this->ajaxReturn(null, '图像处理初始化失败!' , 0);
			}
		}
	}
	
	//检查图像处理参数
	private function checkImageProcessParams(){
		//调整大小
		if($_POST['Size'] == 1){
			if( !is_numeric($_POST['ThumbWidth'])){
				$this->ajaxReturn(null, '宽度必须为数字!' , 0);
			}
			if( !is_numeric($_POST['ThumbHeight'])){
				$this->ajaxReturn(null, '高度必须为数字!' , 0);
			}

			if(0==$_POST['ThumbWidth'] && 0==$_POST['ThumbHeight']){
                $this->ajaxReturn(null, '宽度和高度不能同时为0' , 0);
            }
		}
		
		//检查水印是否存在
		if($_POST['Water'] == 1){
			if( $_POST['WaterType'] == 1 ){ //图片水印
				$WaterPic = $_SERVER['DOCUMENT_ROOT'].$_POST['WaterPic'];
				if(!file_exists($WaterPic)){
					$this->ajaxReturn(null, "水印图片{$WaterPic}不存在!" , 0);
				}
			}else{ //文字水印
				if( !empty($_POST['WaterFont']) ){
					$WaterFont= './Public/font/'.$_POST['WaterFont'];
					if(!file_exists($WaterFont)){
						$this->ajaxReturn(null, "水印字体文件{$WaterFont}不存在!" , 0);
					}
				}
				$_POST['WaterTextSize'] = intval($_POST['WaterTextSize']);
				$_POST['WaterTextAngle'] = intval($_POST['WaterTextAngle']);
				$_POST['WaterOffsetX'] = intval($_POST['WaterOffsetX']);
				$_POST['WaterOffsetY'] = intval($_POST['WaterOffsetY']);
				$_POST['WaterPosition'] = intval($_POST['WaterPosition']);
			}
		}
		
		//格式转换
		if($_POST['Format'] == 1){
			if( !is_numeric($_POST['JpegQuality'])){
				$this->ajaxReturn(null, '图像品质必须为数字!' , 0);
			}
			if( $_POST['JpegQuality']<0 || $_POST['JpegQuality']>100 ){
				$this->ajaxReturn(null, '图像品质必须>=0或<=100!' , 0);
			}
		}
		
		//检查Upload是否有写入全新
		if(!yd_is_writable("./Upload/") ){
			$this->ajaxReturn(null, './Upload/目录没有写入权限!' , 0);
		}
		
		//创建输出目录
		if($_POST['SaveType'] == 2){
            $savePath = trim($_POST['SavePath'],'/');
		    //SavePath可能是download/xx/1111
            if(empty($savePath) || false !== stripos($savePath, '.')){ //路径不能包含点号
                $this->ajaxReturn(null, "{$savePath}路径无效，不能包含点号" , 0);
            }
            $savePath = YdInput::checkFileName($savePath);
			$SavePath = './Upload/'.$savePath;
			if(!file_exists($SavePath)){
				$b = mkdir($SavePath, 0755, true);
				if(!$b){
					$this->ajaxReturn(null, "创建输出目录{$SavePath}失败!" , 0);
				}
			}
		}
	}
	
	/**
	 * 开始图像处理
	 */
	function startImageProcess(){
		header("Content-Type:text/html; charset=utf-8");
		//转化为绝对路径，若路径存在中文名称，则可能会报错
		//$filename = $_SERVER['DOCUMENT_ROOT'].urldecode($_POST['FileName']);
        $filename = './'.urldecode($_POST['FileName']);
		//需要判断，图像文件是否存在
		if(!file_exists($filename)){
			$errorFileName = basename($_POST['FileName']);
			$this->ajaxReturn(null, "图像 {$errorFileName} 不存在！" , 0);
		}
		//需要判断扩展名
		$ext = strtolower(yd_file_ext($filename));
		if( !in_array($ext, array('jpeg','jpg','gif','bmp','png'))){
			$this->ajaxReturn(null, "无法处理{$ext}格式的图像文件！" , 0);
		}
		import('ORG.Util.Image.ThinkImage');
		$config = $this->getImageProcessConfig();
		$SaveType = $config['SaveType'];
		if($SaveType == 1){ //覆盖
			$destFilename = $filename;
		}else{ //保存到指定目录
			$SavePath = './Upload/'.trim($config['SavePath'],'/');
			$destFilename = $SavePath.'/'.basename($filename);
		}
		$srcFilename = $filename;
		$HasProcessedWithOutput = false; //SavePath=2并且被处理过，便于在格式转换里删除中间结果
		try{
			//调整图像尺寸
			if($config['Size'] == 1){
				$img = new ThinkImage(THINKIMAGE_GD, $srcFilename);
				$srcWidth = $img->width(); //原始图像宽度
				$srcHeight = $img->height();
				$width = $config['ThumbWidth'];
                $height = $config['ThumbHeight'];
				if($width==0){ //设置为0，就自动计算自适应
				    $width = $height*$srcWidth/$srcHeight;
                }
				if($height==0){ //设置为0，就自动计算自适应
                    $height = $width*$srcHeight/$srcWidth;
                }

				$type = $config['ThumbType'];
				$img->thumb($width, $height, $type)->save($destFilename);
				$srcFilename = $destFilename;
				if($SaveType==2)$HasProcessedWithOutput = true;
			}
			
			//添加水印
			if($config['Water'] == 1){
				$img = new ThinkImage(THINKIMAGE_GD, $srcFilename);
				$position = $config['WaterPosition'];
				if( $config['WaterType'] == 2 ){//文字水印
					$text = $config['WaterText'];
					$font = "";
					if( !empty($config['WaterFont']) ){
						$font = './Public/font/'.$config['WaterFont'];
						if(!file_exists($font)){
							$font = '';
						}
					}
					$size = $config['WaterTextSize'];
					$color= $config['WaterTextColor'];
					$angle = $config['WaterTextAngle'];
					$offset = array($config['WaterOffsetX'], $config['WaterOffsetY']);
					$img->text($text, $font, $size, $color, $position, $offset, $angle)->save($destFilename);
				}else if( $config['WaterType'] == 1 ){ //图片水印
					//初始化时，判断水印图片是否存在
					$pic = $_SERVER['DOCUMENT_ROOT'].$config['WaterPic'];
					$img->water($pic, $position)->save($destFilename);
				}
				$srcFilename = $destFilename;
				if($SaveType==2) $HasProcessedWithOutput = true;
			}
			
			//格式转换（放到最后）
			if($config['Format'] == 1){
				$toExt = $config['ImageFormat'];
				$fromExt = strtolower(yd_file_ext($srcFilename));
				$fromExt1 = $fromExt;
				if($fromExt=="jpeg" || $fromExt=="jpg") $fromExt1 = "jpg";
				//如果扩展名相同就无需转换，jpeg除外（因为可以设置单独的品质因数）
				if($toExt=="jpg" || $toExt != $fromExt1){
					$img = new ThinkImage(THINKIMAGE_GD, $srcFilename);
					$other['JpegQuality'] = $config['JpegQuality'];
					if($SaveType == 1){ //覆盖原文件
						$pos = strripos($srcFilename, '.');
						$destFilename = substr_replace($srcFilename, $toExt, $pos+1, 100);
						$img->saveAs($destFilename, $other);
						if($srcFilename!=$destFilename){
							unlink($srcFilename);
						}
					}else{ //输出到指定目录
						$pos = strripos($destFilename, '.');
						$destFilename = substr_replace($destFilename, $toExt, $pos+1, 100);
						$img->saveAs($destFilename, $other);
						//删除中间处理临时文件
						if($HasProcessedWithOutput && $srcFilename!=$destFilename){
							unlink($srcFilename);
						}
					}
				}else if($SaveType ==2 && !$HasProcessedWithOutput){ 
					//如果格式转换和目标文件相同，且之前没有处理过，则仅仅拷贝文件到目标目录
					copy($srcFilename,$destFilename);
				}
			}
		}catch(Exception $e){
			$error = $e->getMessage();
			$errorFileName = basename($_POST['FileName']);
			$msg = "处理图像{$errorFileName}失败，{$error}";
			$this->ajaxReturn(null, $msg, 0);
		}
		$this->ajaxReturn(null, '图像处理完成!' , 1);
	}
	
	/**
	 * 获取图像输出目录
	 */
	function getImageOutputDir(){
		header("Content-Type:text/html; charset=utf-8");
		//传入的参数都是没有路径前缀的
		$currentDir = trim($_GET['dir']);
		$prefix = "./Upload";
		$list = array();
		if(empty($currentDir)){
			$ParentDir = ''; //表示没有上一级目录
			$pattern = $prefix.'/*';
		}else{
			$dir = $prefix.'/'.trim($currentDir, '/').'/';
			$ParentDir = dirname($dir);
			$ParentDir =  ($ParentDir==$prefix) ? '' : substr($ParentDir, 9);
			$pattern = $dir.'*';
		}
		$data = glob($pattern, GLOB_ONLYDIR);
		$list = array();
		foreach ($data as $k=>$v){
			$FullDir = substr($v, 9);
			$DirName = basename($v);
			$list[] = array(
				'FullDir'=>$FullDir,
				'DirName'=>$DirName
			);
		}
		$this->ajaxReturn($list, $ParentDir, 1);
	}

    /**
     * 网站一键变灰
     */
    function grayscale(){
        header("Content-Type:text/html; charset=utf-8");
        $m = D('Admin/Config');
        $data = $m->getConfig('other'); //配置数据不从缓存中提取
        if(1 != $data['EnableGray']){
            $data['EnableGray'] = 0;
        }
        if(empty($data['GrayStartTime'])){
            $data['GrayStartTime'] =  date('Y-m-d H:i:s');
        }
        if(empty($data['GrayEndTime'])){
            $data['GrayEndTime'] =  date("Y-m-d H:i:s", strtotime("+1 day"));
        }
        $this->assign('EnableGray', $data['EnableGray'] );
        $this->assign('GrayStartTime', $data['GrayStartTime'] );
        $this->assign('GrayEndTime', $data['GrayEndTime'] );
        $this->assign('Action', __URL__.'/saveEnableGray' );
        $this->display();
    }

    /**
     * 保存网站一键变灰
     */
    function saveEnableGray(){
        header("Content-Type:text/html; charset=utf-8");
        $m = D("Admin/Config");
        $data = array();
        $data['EnableGray'] = intval($_POST['EnableGray']);
        $data['GrayStartTime'] = $_POST['GrayStartTime'];
        $data['GrayEndTime'] = $_POST['GrayEndTime'];
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
     * robot协议修改
     */
    function robots(){
        header("Content-Type:text/html; charset=utf-8");
        $file = "./robots.txt";
        $robotsContent = "";
        if(file_exists($file)){
            $robotsContent = file_get_contents($file);
        }
        $this->assign('RobotsContent', $robotsContent);
        $this->assign('Action', __URL__.'/saveRobots' );
        $this->display();
    }

    function saveRobots(){
        $file = "./robots.txt";
        if(file_exists($file)){
            $isWritable = yd_is_writable($file);
            if(!$isWritable) { //没有写人权限
                $this->ajaxReturn(null, '保存失败，没有写入权限' , 0);
            }
        }
        $RobotsContent = trim($_POST['RobotsContent']);
        if (get_magic_quotes_gpc()) {
            $RobotsContent = stripslashes($RobotsContent);
        }
        $result = file_put_contents($file, $RobotsContent);
        if(false !== $result){
            WriteLog($RobotsContent);
            $this->ajaxReturn(null, '保存成功' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败' , 0);
        }
    }

    /**
     * 模板装修
     */
    function decoration(){
        header("Content-Type:text/html; charset=utf-8");
        $m = D('Admin/Config');
        $data = $m->getConfig('other'); //配置数据不从缓存中提取
        //是否启用装修
        if(1 != $data['EnableDecoration']){
            $data['EnableDecoration'] = 0;
        }
        $this->assign('EnableDecoration', $data['EnableDecoration'] );
        //$this->assign('DecorationAppID', $data['DecorationAppID'] );
        //$this->assign('DecorationAppKey', $data['DecorationAppKey'] );
        $this->assign('Action', __URL__.'/saveDecoration' );
        $this->display();
    }

    /**
     * 保存模板装修
     */
    function saveDecoration(){
        header("Content-Type:text/html; charset=utf-8");
        $m = D("Admin/Config");
        $data = array();
        $data['EnableDecoration'] = intval($_POST['EnableDecoration']);
        /*
        if($data['EnableDecoration']==1){
            $id = trim($_POST['DecorationAppID']);
            if(empty($id)){
                $this->ajaxReturn(null, 'AppID不能为空' , 0);
            }
            $key = trim($_POST['DecorationAppKey']);
            if(32 != strlen($key)){
                $this->ajaxReturn(null, 'AppKey无效！' , 0);
            }
            $data['DecorationAppID'] = $id;
            $data['DecorationAppKey'] = $key;
        }
        */
        $result = $m->saveConfig($data);
        if(false !== $result ){
            WriteLog();
            $this->ajaxReturn(null, '保存成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败!' , 0);
        }
    }

    /**
     * 敏感词检测
     */
    function wordsCheck(){
        header("Content-Type:text/html; charset=utf-8");
        $m = D('Admin/Config');
        $data = $m->getConfig('other');
        $this->assign('WordsType', $data['WordsType']);
        $this->assign('WordsContent', $data['WordsContent']);
        $this->assign('Action', __URL__.'/saveCheckWords');
        $this->display();
    }

    /**
     * 保存敏感词配置
     */
    function saveCheckWords(){
        header("Content-Type:text/html; charset=utf-8");
        $result = $this->_saveCheckWords();
        if(false !== $result ){
            unset($_POST['__hash__']);
            $description = var_export($_POST, true);
            WriteLog($description);
            $this->ajaxReturn(null, '保存成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败!' , 0);
        }
    }

    private function _saveCheckWords(){
        $m = D("Admin/Config");
        $data = array();
        $data['WordsType'] = intval($_POST['WordsType']);
        $data['WordsContent'] = $_POST['WordsContent'];
        $result = $m->saveConfig($data);
        return $result;
    }

    /**
     * 初始化检测
     */
    function intiCheckWords(){
        if(!function_exists('mb_stripos')){
            $this->ajaxReturn(null, '未开启mb_string扩展！请开启' , 0);
        }
        $type = intval($_POST['WordsType']);
        $words = '';
        if($type==1 || $type==4){ //自定义词汇有效
            $words =  trim($_POST['WordsContent']);
            $words = str_ireplace(array("\n\r", "\n", "\r"), '', $words);
            $words = trim($words, ',');
        }
        if(4 != $type){ //获取云端词汇
            import("@.Common.YdApi");
            $this->_saveCheckWords(); //首先保存当前配置
            $api = new YdApi();
            $str = $api->getSensitiveWords($type);
            if(!empty($str)) $words = "{$words},{$str}";
        }
        if(empty($words)){
            $this->ajaxReturn(null, '获取关键词失败！' , 0);
        }else{
            //保存关键词到本地
            $file = RUNTIME_PATH.'words.txt';
            file_put_contents($file, $words);
        }
        //获取待检测页面地址
        $url = AllPageUrl(1);
        //$url = array_slice($url, 0 , 5);
        $this->ajaxReturn($url, '' , 1);
    }

    /**
     * 开始检测敏感词
     */
    function startCheckWords(){
        $url = trim($_POST['Url']);
        $content = $this->_getPageContent($url);
        $result = "<a target='_blank' href='{$url}'>{$url}</a>";
        $words = file_get_contents(RUNTIME_PATH.'words.txt');
        if(empty($words)){
            $this->ajaxReturn(null,  "{$result}，关键词为空！", 0);
        }
        $msg = "";
        $words = explode(',', $words);
        $total = count($words);
        $max = ($total>4000) ? 3 : 10;
        $n = 0;
        foreach($words as $v){
            if(empty($v)) continue;
            $pos = mb_stripos($content, $v);
            if(false !== $pos){ //有检测到
                $n++;
                $startPos = $pos-5;
                if($startPos<0) $startPos = 0;
                $endPos = $pos+mb_strlen($v)+5;
                $temp = mb_substr($content, $startPos, $endPos-$startPos);
                $temp = str_ireplace($v, "<span style='color:red'>{$v}</span>", $temp);
                $msg .= "<span style='margin-right: 1.5em;'>...{$temp}...</span>";
                if($n>=$max) break;
            }
        }
        if(empty($msg)){
            $result = ''; //如果没有检测到，返回空
        }else{
            $result = "{$result} 包含关键词：{$msg}";
        }
        $this->ajaxReturn(null, $result, 1);
    }

    /**
     * 获取页面内容，并做预处理
     */
    private function _getPageContent($url){
        //$url = 'http://cms9.a.com:81/index.php/video/206.html';
        $result = "<a target='_blank' href='{$url}'>{$url}</a>";
        $content = yd_curl_get("{$url}?debug=1"); //临时开启调试模式，防止缓存
        //$n1 = strlen($content);
        $search = array (
            "~<script[^>]*?>[\s\S]*?</script>~i", //删除script
            "~<style[\s\S]*?>[\s\S]*?</style>~i", //stylescript
            "~<!--[\s\S]*?-->~",  //删除注释
            "~<div[\s\S]*?>~", //删除div标签
            "~<span[\s\S]*?>~", //删除div标签
            "~<a[\s\S]*?>~",
            "~<p[\s\S]*?>~",
            "~<ul[\s\S]*?>~",
            "~<li[\s\S]*?>~",
            "~<link[\s\S]*?>~",
            "~<body[\s\S]*?>~",
            "~<i[\s\S]*?>~",
            "~<input[\s\S]*?>~",
            "~<form[\s\S]*?>~",
            "~<video[\s\S]*?>~",
            "~<html[\s\S]*?>~",
            "~<h2[\s\S]*?>~",
            "~<h1[\s\S]*?>~",
            "~\s*~", //替换所有空白
        );
        $content = preg_replace ($search, '', $content);
        $search = array(
            '</div>', '</span>', '</a>', '</li>', '</ul>', '</p>','<b>', '</b>', '<strong>', '</strong>',
            '<head>', '</head>', '</body>', '</html>', '<br>', '<br/>', '</i>',
            '<h1>', '</h1>',  '<h2>', '</h2>', '</form>', '<title>', '</title>', ',' ,'，', '.', '。', ':', '!', '！',
            '&nbsp;','　'
        );
        $content = str_ireplace($search, '', $content);
        //file_put_contents("./Data/1.txt", $content);
        //$n = $n1-strlen($content); //节省的字符大小
        if(empty($content)){
            $this->ajaxReturn(null,  "{$result}，无法获取页面内容！", 0);
        }
        return $content;
    }

    /**
     * 商城插件
     */
    function shop(){
        header("Content-Type:text/html; charset=utf-8");
        $this->display();
    }

    /**
     * 短信功能
     */
    function sms(){
        header("Content-Type:text/html; charset=utf-8");
        $this->display();
    }

    /**
     * 短信设置
     */
    function smsConfig(){
        header("Content-Type:text/html; charset=utf-8");
        $m = D('Admin/Config');
        $data = $m->getConfig('other'); //配置数据不从缓存中提取
        $data['SMS_PASSWORD'] = HideSensitiveData($data['SMS_PASSWORD']);
        $this->assign('SmsType', $data['SMS_TYPE'] );
        $this->assign('SmsAccount', $data['SMS_ACCOUNT'] );
        $this->assign('SmsPassword', $data['SMS_PASSWORD'] );
        $this->assign('SmsIpMax', $data['SMS_IP_MAX'] );
        $this->assign('SmsNumMax', $data['SMS_NUM_MAX'] );

        $this->assign('SearchPageSize', $data['SearchPageSize'] );
        $this->assign('HomeRollPage', $data['HomeRollPage'] );
        $this->assign('AdminLoginName', C('ADMIN_LOGIN_NAME') );
        $this->assign('Action', __URL__.'/saveSmsConfig' );
        $this->display();
    }

    /**
     * 发送记录
     */
    function smsLog(){
        $p['Parameter'] = array(
            'UserName' => $_REQUEST['UserName'],
            'LogType' => 9,
        );
        $p['ModuleName'] = 'Log';
        $p['HasPage'] = true; //表示有分页
        $this->opIndex( $p );
    }

    /**
     * 保存短信设置
     */
    function saveSmsConfig(){
        $data = GetConfigDataToSave('', 'SMS_');
        $m = D("Admin/Config");
        if( $m->saveConfig($data) ){
            unset($data['SMS_PASSWORD']);
            $description = var_export($data, true);
            WriteLog($description);
            $this->ajaxReturn(null, '保存成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败!' , 0);
        }
    }

    function getLeftNum(){
        $data = $GLOBALS['Config'];
        import("@.Common.YdSms");
        $obj = YdSms::getInstance( $data['SMS_TYPE'] );
        $obj->setConfig( $data );
        $num = $obj->getLeftNum();
        if( is_numeric($num)){
            $this->ajaxReturn(null, $num, 1);
        }else{
            $this->ajaxReturn(null, "查询失败，{$num}" , 0);
        }
    }

    //短信发送测试
    function sendSmsTest(){
        $mobile = $_GET['mobile'];
        $code = $_GET['code']; //校验码
        $content = $GLOBALS['Config']['MOBILE_REG_TEMPLATE']; //读取模板
        $msg = '';
        $result = send_sms($mobile, $content, array('{$Code}'=>$code), 1, $msg);
        if( $result ){  //在send_sms会保存日志，所以这里不需要保存日志
            $this->ajaxReturn(null, $msg, 1);
        }else{
            $this->ajaxReturn(null, "发送失败，{$msg}" , 0);
        }
    }

    /**
     * 百度翻译
     */
    function translate(){
        header("Content-Type:text/html; charset=utf-8");
        $m = D('Admin/Config');
        $data = $m->getConfig('other'); //配置数据不从缓存中提取
        $data['BAIDU_TRANSLATE_APIKEY'] = HideSensitiveData($data['BAIDU_TRANSLATE_APIKEY']);
        $this->assign('BaiduTranslateApiKey', $data['BAIDU_TRANSLATE_APIKEY'] );
        $this->assign('BaiduTranslateAppId', $data['BAIDU_TRANSLATE_APPID'] );
        $this->assign('AllLanguage', AllLanguage() );
        $this->assign('Action', __URL__.'/saveTranslate' );
        $this->display();
    }

    /**
     * 保存百度翻译
     */
    function saveTranslate(){
        $data = GetConfigDataToSave('', 'BAIDU_');
        $m = D("Admin/Config");
        if( $m->saveConfig($data) ){
            unset($data['BAIDU_TRANSLATE_APIKEY']);
            $description = var_export($data, true);
            WriteLog($description);
            $this->ajaxReturn(null, '保存成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败!' , 0);
        }
    }

    /**
     * 翻译测试
     */
    function  translateTest(){
        $content = $_REQUEST['content'];
        $from = 'auto';
        $to = $_REQUEST['to'];
        $result = baiduTranslate($content, $from, $to);
        if( $result['Status'] == 1){
            $this->ajaxReturn(null, $result['Content'] , 1);
        }else{
            $this->ajaxReturn(null, $result['ErrorMessage'], 1);
        }
    }

    /**
     * 敏感词替换
     */
    function badreplace(){
        header("Content-Type:text/html; charset=utf-8");
        $m = D('Admin/Config');
        $data = $m->getConfig('basic'); //配置数据不从缓存中提取
        $this->assign('WebBadWords', $data['WEB_BAD_WORDS'] );
        $this->assign('BadWordsEnable', $data['BadWordsEnable'] );
        $this->assign('Action', __URL__.'/saveBadreplace' );
        $this->display();
    }

    /**
     * 保存敏感词替换提设置
     */
    function saveBadreplace(){
        $data = array();
        $data['BadWordsEnable'] = intval($_POST['BadWordsEnable']);
        $data['WEB_BAD_WORDS'] = $_POST['WEB_BAD_WORDS'];
        $m = D("Admin/Config");
        if( $m->saveConfig($data) ){
            $description = var_export($data, true);
            WriteLog($description);
            $this->ajaxReturn(null, '保存成功!' , 1);
        }else{
            $this->ajaxReturn(null, '保存失败!' , 0);
        }
    }

    /**
     * ip黑名单
     */
    function badip(){
        header("Content-Type:text/html; charset=utf-8");
        $m = D('Admin/Config');
        $data = $m->getConfig('basic'); //配置数据不从缓存中提取
        $this->assign('WebBadIP', $data['WEB_BAD_IP'] );
        $this->assign('BadIPEnable', $data['BadIPEnable'] );
        $this->assign('Action', __URL__.'/saveBadIP' );
        $this->display();
    }

    /**
     * 保存ip黑名单设置
     */
    function saveBadIP(){
        $badIP = trim($_POST['WEB_BAD_IP']);
        $badIP = str_ireplace(' ', '', $badIP);
        if(!empty($badIP)){
            $tempIPList = str_ireplace('*', 0, $badIP);
            $tempIPList = str_replace(array("\r\n","\r"), "\n", $tempIPList);
            $tempIPList = explode ("\n", $tempIPList);
            foreach($tempIPList as $v){
                if(empty($v)) continue;
                $temp = explode('.', $v);
                $n = count($temp);
                if(4 !== $n){
                    $this->ajaxReturn(null, 'IP地址无效' , 0);
                }
                foreach($temp as $item){
                    if(!is_numeric($item) || $item<0 || $item>255){
                        $this->ajaxReturn(null, "IP地址 {$v} 无效" , 0);
                    }
                }
            }
        }
        if( isset($_POST) ){  //保存配置到数据库
            $data = array();
            $data['WEB_BAD_IP'] = $badIP;
            $data['BadIPEnable'] = intval($_POST['BadIPEnable']);
            $m = D("Admin/Config");
            if( $m->saveConfig($data) ){
                $description = var_export($data, true);
                WriteLog($description);
                $this->ajaxReturn(null, '保存成功!' , 1);
            }else{
                $this->ajaxReturn(null, '保存失败!' , 0);
            }
        }
    }

    /**
     * 禁止右键拷贝
     */
    function banCopy(){
        header("Content-Type:text/html; charset=utf-8");
        $m = D('Admin/Config');
        $data = $m->getConfig('other');
        $this->assign('BanCopyEnable', $data['BanCopyEnable'] );
        $this->assign('Action', __URL__.'/saveBanCopy' );
        $this->display();
    }

    /**
     * 保存禁止右键拷贝设置
     */
    function saveBanCopy(){
        if( isset($_POST) ){  //保存配置到数据库
            $data = array();
            $data['BanCopyEnable'] = intval($_POST['BanCopyEnable']);
            $m = D("Admin/Config");
            if( $m->saveConfig($data) ){
                $description = var_export($data, true);
                WriteLog($description);
                $this->ajaxReturn(null, '保存成功!' , 1);
            }else{
                $this->ajaxReturn(null, '保存失败!' , 0);
            }
        }
    }
}




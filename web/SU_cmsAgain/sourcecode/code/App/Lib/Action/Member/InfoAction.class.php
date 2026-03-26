<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class InfoAction extends MemberBaseAction {
	/**
	 * 信息列表显示首页
	 */
	function index(){
		header("Content-Type:text/html; charset=utf-8");
		//如果没有传入频道ID，则获取所有的频道信息
		$ChannelID = intval($_GET['ChannelID']);
		import("ORG.Util.Page");
		$s = D('Admin/Info');
		$mid = (int)session('MemberID');
		$TotalPage = $s->getCount($ChannelID, 1, -1, '', $mid); //总页数
		$PageSize = $this->AdminPageSize;
		$Page = new Page($TotalPage, $PageSize);
		$Page->rollPage = $this->AdminRollPage;
		$ShowPage = $Page->show();
	
		$Info = $s->getInfo($Page->firstRow, $Page->listRows, $ChannelID, 1, -1, '', '', $mid);
		$n = is_array($Info) ? count($Info) : 0;
		for($i = 0; $i<$n; $i++){
			$Info[$i]['InfoUrl'] = InfoUrl($Info[$i]['InfoID'], $Info[$i]['Html'], $Info[$i]['LinkUrl'], false, $Info[$i]['ChannelID']);
			$Info[$i]['ChannelUrl'] = ChannelUrl($Info[$i]['ChannelID']);
		}
	
		$this->assign('NowPage', $Page->getNowPage()); //分页条
		$this->assign('Page', $ShowPage); //分页条
		$this->assign('ChannelID', $ChannelID); //当前频道，如果为0表示所有频道
		$this->assign('GroupID', (int)session('MemberGroupID')); //当前频道
		$this->assign('Info', $Info);
		$this->display();
	}
	
	//反馈模型
	function feedback(){
		header("Content-Type:text/html; charset=utf-8");
		$IsEnable = -1;
		$MemberID =  (int)session('MemberID');
		$IsCheck = isset($_REQUEST['IsCheck']) && is_numeric($_REQUEST['IsCheck'])  ? intval($_REQUEST['IsCheck']) : -1;
		$ChannelID = intval($_REQUEST['ChannelID']);
		$Keywords = isset($_REQUEST['Keywords']) ? $_REQUEST['Keywords'] : '';
        $Keywords = YdInput::checkKeyword( $Keywords );
	
		import("ORG.Util.Page");
		$s = D('Admin/Info');
		//$ChannelID = 0, $IsContainChild = 1, $IsEnable = -1, $Keywords='', $MemberID = -1,
		//$SpecialID = 0, $LabelID = '', $IsCheck=-1
		$TotalPage = $s->getCount($ChannelID, 1, $IsEnable, $Keywords, $MemberID, 0, '', $IsCheck); //总页数
		$PageSize = $this->AdminPageSize;
		$Page = new Page($TotalPage, $PageSize);
	
		$Page->parameter = "&IsCheck=$IsCheck&Keywords=$Keywords";
	
		$Page->rollPage = $this->AdminRollPage;
		$ShowPage = $Page->show();
	
		//参数：$FirstRow, $ListRow, $ChannelID = 0, $IsContainChild = 1, $IsEnable = -1, $LabelID = '',
		//$Keywords='', $MemberID = -1, $SpecialID = 0, $IsCheck=-1
		$Info = $s->getInfo($Page->firstRow, $Page->listRows, $ChannelID, 1, $IsEnable, '', $Keywords, $MemberID, 0, $IsCheck);
	
		$ChannelModelID = 37; //反馈模型ID为37
		//=====================================================
		$m = D('Admin/Attribute');
		$Attribute = $m->getAttribute( $ChannelModelID );
		$Group = $m->getGroup( $ChannelModelID );
		//$n1 = count($Group);
		//$n2 = count($Attribute);
		$f = array();
		foreach($Group as $g){
			foreach($Attribute as $a){
				if( $a['GroupID'] == $g['AttributeID'] ){
					$f[] = array( 'FieldName'=>$a['FieldName'], 'DisplayName'=>$a['DisplayName'] );
				}
			}
		}
		unset($Attribute, $Group);
		//=====================================================
		$n3 = is_array($Info) ? count($Info) : 0;
		for($i = 0; $i<$n3; $i++){
            $Info[$i]['AllInfo'] = '';
			foreach($f as $v){
				$Info[$i]['AllInfo'] .= '<b>'.$v['DisplayName'].'：</b>'.$Info[$i][$v['FieldName']].'<br/>';
			}
		}

		$this->assign('NowPage', $Page->getNowPage()); //分页条
		$this->assign('Page', $ShowPage); //分页条
		$this->assign('ChannelModelID', $ChannelModelID); //当前频道
		$this->assign('ChannelID', $ChannelID); //当前频道
		$this->assign('Keywords', $Keywords);
		$this->assign('AdminGroupID', intval(session('AdminGroupID'))); //当前频道
		$this->assign('Info', $Info);
		$this->display();
	}
	
	
	/**
	 * 删除反馈
	 */
	function delFeedback(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Info');
		$InfoID = (int)$_GET["InfoID"];
		$ChannelID = (int)$_GET["ChannelID"];
		$p = (int)$_GET["p"];
	
		//有问题==================================================================
		$Keywords = isset($_REQUEST['Keywords']) ? $_REQUEST['Keywords'] : '';
        $Keywords = YdInput::checkKeyword( $Keywords );
		$IsCheck = isset($_REQUEST['IsCheck']) && is_numeric($_REQUEST['IsCheck'])  ? (int)$_REQUEST['IsCheck'] : -1;
		$parameter ="/IsCheck/".$IsCheck;
		if( $Keywords != ''){
			$parameter .= "/Keywords/$Keywords";
		}
		//======================================================================
	
		if( is_numeric($InfoID) && is_numeric($ChannelID) && is_numeric($p)){
			$m->delInfo($InfoID, (int)session('MemberID'));
			WriteLog( "ID:$InfoID", array('LogType'=>3,'UserAction'=>'删除信息'));
		}
		redirect(__URL__."/feedback/ChannelID/$ChannelID/p/$p".$parameter);
	}
	
	//批量删除反馈
	function batchDelFeedback(){
		$id = $_POST['InfoID'];
		$ChannelID = intval($_POST["ChannelID"]);
		$NowPage = intval($_POST["NowPage"]);
		$Keywords = isset($_REQUEST['Keywords']) ? $_REQUEST['Keywords'] : '';
        $Keywords = YdInput::checkKeyword( $Keywords );
		$IsCheck = isset($_REQUEST['IsCheck']) && is_numeric($_REQUEST['IsCheck'])  ? intval($_REQUEST['IsCheck']) : -1;
		$parameter = "/IsCheck/$IsCheck";
		if( $Keywords != ''){
			$parameter .= "/Keywords/$Keywords";
		}
		if( count($id) > 0 ){
			$m = D('Admin/Info');
			$m->batchDelInfo($id, (int)session('MemberID') );
			WriteLog("ID:".implode(',', $id), array('LogType'=>3,'UserAction'=>'批量删除信息'));
		}
		redirect(__URL__."/feedback/ChannelID/$ChannelID/p/$NowPage".$parameter);
	}
	
	/**
	 * 删除信息
	 */
	function del(){
		header("Content-Type:text/html; charset=utf-8");
		$i = D('Admin/Info');
		$InfoID = $_GET["InfoID"];
		$ChannelID = $_GET["ChannelID"];
		$p = $_GET["p"];
		$showall = $_GET['showall'];
	
		if( is_numeric($InfoID) && is_numeric($ChannelID) && is_numeric($p)){
		    $MemberID = (int)session('MemberID');
			$where = "InfoID=$InfoID and MemberID=$MemberID";
			if( $i->where($where)->delete() ){
				WriteLog( "ID:$InfoID", array('LogType'=>3,'UserAction'=>'删除信息'));
				if( $showall == 1){
					redirect(__URL__."/index/p/$p");
				}else{
					redirect(__URL__."/index/ChannelID/$ChannelID/p/$p");
				}
			}
		}
	}
	
	//批量删除信息
	function batchDel(){
		$id = $_POST['InfoID'];
		$ChannelID = intval($_POST["ChannelID"]);
		$NowPage = intval($_POST["NowPage"]);
		$showall = $_GET['showall'];
		if( count($id) > 0 ){
			$m = D('Admin/Info');
			$MemberID = (int)session('MemberID');
			$m->batchDelInfo($id,  $MemberID);
			WriteLog("ID:".implode(',', $id), array('LogType'=>3,'UserAction'=>'批量删除信息'));
		}
		if( $showall == 1){
			redirect(__URL__."/index/p/$NowPage");
		}else{
			redirect(__URL__."/index/ChannelID/$ChannelID/p/$NowPage");
		}
	}
	
	function batchMove(){
		$id = $_POST['InfoID'];
		$NowChannelID = intval($_POST["ChannelID"]);
		$NowPage = intval($_POST["NowPage"]); //当前页
		$ChannelID = intval($_POST["cid"]); //目标频道
		$gid = (int)session('MemberGroupID');
		
		$url = __URL__."/Index/ChannelID/$NowChannelID/p/$NowPage";
		if( !channel_allow($ChannelID) ){
			alert('不能移动到目标频道!', $url);
		}
		
		$m = D('Admin/Channel');
		if( $m->hasChannelPurview($ChannelID, 0, $gid) ){  //目标频道权限判断
			$SpecialID = $_POST["sid"];  //目标专题
			if( count($id) > 0 ){
				$m = D('Admin/Info');
				$m->batchMoveInfo($id, $ChannelID, $SpecialID);
				WriteLog("ID:".implode(',', $id), array('LogType'=>1,'UserAction'=>'移动信息'));
			}
		}
		redirect($url);
	}
	
	//批量设置属性
	function batchLabel(){
		$id = $_POST['InfoID'];
		$NowChannelID = $_POST["ChannelID"];
		$NowPage = (int)$_POST["NowPage"]; //当前页
	
		$LabelID = $_POST["lid"]; //目标标记
		$IsEnable = $_POST["IsEnable"]; //是否启用
		if( count($id) > 0 ){
			$m = D('Admin/Info');
			$m->batchLabel($id, $LabelID, $IsEnable, (int)session('MemberID'));
			WriteLog("ID:".implode(',', $id), array('LogType'=>1,'UserAction'=>'设置信息属性'));
		}
		redirect(__URL__."/Index/ChannelID/$NowChannelID/p/$NowPage");
	}
	
	/**
	 * 信息修改
	 */
	function modify(){
		header("Content-Type:text/html; charset=utf-8");
		//参数有效性检查===========================
		$InfoID = $_GET['InfoID'];
		$ChannelModelID = ChannelModelID( $_GET['ChannelID'] );
	
		if( !is_numeric($InfoID) || !is_numeric($ChannelModelID) ){
			alert("非法参数", __URL__.'/Index');
		}
		//====================================
	
		//模型属性信息=================================================
		$m = D('Admin/Attribute');
		$Attribute = $m->getAttribute($ChannelModelID);
		$Group = $m->getGroup($ChannelModelID);
	
		//获取信息数据======================================================
		$Info = D('Admin/Info')->find( $InfoID );
		$total = count($Attribute);
		for($n = 0; $n<$total; $n++){
			if( strtolower($Attribute[$n]['FieldName']) == 'ischeck' ){
				unset( $Attribute[$n] );
				continue; //会员投稿不显示是否审核项
			}
			if( $this->IsSelectedAttribute( $Attribute[$n]['DisplayType'] ) ){
				if ( $Attribute[$n]['DisplayType'] == 'channelselect'){
					$Attribute[$n]['MemberGroupID'] = (int)session('MemberGroupID');
					$Attribute[$n]['SelectedValue'] = $Info[ $Attribute[$n]['FieldName'] ]; //获取频道设置值
					$Attribute[$n]['HasSingleModel'] = true;  //是否是单页频道
					$Attribute[$n]['HasLinkModel'] = false;  //是否是链接频道
					//$Attribute[$n]['FirstValue'] = "0"; //FirstValue
					//$Attribute[$n]['FirstText'] = "所有频道"; //FirstText
				}else if ( $Attribute[$n]['DisplayType'] == 'specialselect'){
					$Attribute[$n]['ChannelID'] = $Info['ChannelID']; //保存当前频道ID
					$Attribute[$n]['SelectedValue'] = explode(',' , $Info[ $Attribute[$n]['FieldName'] ]); //获取频道设置值
				}else if($Attribute[$n]['DisplayType'] == 'labelcheckbox'){ //属性标记
					$Attribute[$n]['ChannelModelID'] = $ChannelModelID;
					$Attribute[$n]['DisplayValue'] = $Info[ $Attribute[$n]['FieldName'] ];
				}else if( $Attribute[$n]['DisplayType'] == 'channelexselect'){
					$Attribute[$n]['MemberGroupID'] = (int)session('MemberGroupID');
					$Attribute[$n]['SelectedValue'] = explode(',' , $Info[ $Attribute[$n]['FieldName'] ]); //获取频道设置值
				}else if($Attribute[$n]['DisplayType'] == 'membergroupcheckbox'){ //会员分组checkbox
					$Attribute[$n]['DisplayValue'] = $Info[ $Attribute[$n]['FieldName'] ];
				}else if( $Attribute[$n]['DisplayType'] == 'areaselect'){
					$Attribute[$n]['ProvinceSelectedValue'] = $Info['ProvinceID'];
					$Attribute[$n]['CitySelectedValue'] = $Info['CityID'];
					$Attribute[$n]['DistrictSelectedValue'] = $Info['DistrictID'];
					$Attribute[$n]['TownSelectedValue'] = $Info['TownID'];
				}else{ //checkbox,radio
					$Attribute[$n]['SelectedValue'] = $Info[ $Attribute[$n]['FieldName'] ];
				}
			}else if( $Attribute[$n]['DisplayType']=='coordinate' ){
				$Attribute[$n]['Longitude'] = $Info['Longitude'];
				$Attribute[$n]['Latitude'] = $Info['Latitude'];
			}else{
				$Attribute[$n]['DisplayValue'] = $Info[ $Attribute[$n]['FieldName'] ];
			}
		}
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//==============================================================
	
		$this->assign('InfoID', $InfoID);
		$this->assign('ChannelID', $Info['ChannelID']);
		$this->assign('Action', __URL__.'/saveModify');
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}
	
	/**
	 * 保存信息
	 */
	function saveModify(){
		header("Content-Type:text/html; charset=utf-8");		
		$temp = $_POST['SpecialID'];
		$mid = (int)session('MemberID');
		$this->prePost($_POST);
		$c = D('Admin/Info');		
		//权限控制======================================
		//防止伪造InfoID攻击
		if( !$c->hasInfoPurview($_POST['InfoID'], $mid) ){
			$this->ajaxReturn(null, '没有权限!' , 0);
		}
		unset($_POST['IsCheck']); //去掉审核字段权限
		//============================================
		
		if( $c->create() ){
			if($c->save() === false){
				$this->ajaxReturn(null, '修改失败!' , 0);
			}else{
				WriteLog("ID:".$_POST['InfoID'], array('LogType'=>4,'UserAction'=>'保存信息修改'));
				save_info_type_attribute($_POST['InfoID'], 2);
				$this->ajaxReturn(null, '修改成功!' , 1);
			}
		}else{
			$this->ajaxReturn(null, $c->getError() , 0);
		}
	}
	
	/**
	 * 显示信息添加界面
	 */
	function add(){
		header("Content-Type:text/html; charset=utf-8");
		$ChannelID = $_GET['ChannelID'];
		if( !is_numeric($ChannelID) ){
			alert("非法参数", __URL__.'/Index');
		}
		$c = D('Admin/Channel');
		$ChannelModelID = $c->getChannelModelID($ChannelID);
		$ChannelName = $c->getFieldByChannelID($ChannelID, "ChannelName");
	
		//模型属性信息==============================================
		$m = D('Admin/Attribute');
		$Attribute = $m->getAttribute($ChannelModelID);
		$Group = $m->getGroup($ChannelModelID);
	
		for($n = 0; $n < count($Attribute); $n++){
			if( strtolower($Attribute[$n]['FieldName']) == 'ischeck' ){
				unset( $Attribute[$n] );
				continue; //会员投稿不显示是否审核项
			}
			if( $this->IsSelectedAttribute( $Attribute[$n]['DisplayType'] ) ){
				if ( $Attribute[$n]['DisplayType'] == 'channelselect'){
					$Attribute[$n]['DisplayType'] = "label";
					$Attribute[$n]['DisplayValue'] = "<b style='color:blue'>$ChannelName</b>";
				}else if( $Attribute[$n]['DisplayType'] == 'specialselect'){
					$Attribute[$n]['ChannelID'] = $ChannelID;  //保存当前频道ID
				}else if($Attribute[$n]['DisplayType'] == 'labelcheckbox'){ //属性标记
					$Attribute[$n]['ChannelModelID'] = $ChannelModelID;
				}else if( $Attribute[$n]['DisplayType'] == 'channelexselect'){
					//这里需要控制频道列表权限
					$Attribute[$n]['MemberGroupID'] = (int)session('MemberGroupID');
				}
			}else if($Attribute[$n]['DisplayType'] == 'datetime'){
				$Attribute[$n]['DisplayValue'] = date('Y-m-d H:i:s'); //显示当期时间
			}
		}
	
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//======================================================
		$this->assign('Action', __URL__.'/saveAdd');
		$this->assign('ChannelID', $ChannelID);
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}
	
	/**
	 * 保存添加
	 */
	function saveAdd(){
		header("Content-Type:text/html; charset=utf-8");
		//权限控制=================================
		$gid = (int)session('MemberGroupID');
		$m = D('Admin/Channel');
		if( !$m->hasChannelPurview($_POST['ChannelID'], 0, $gid) ){
			$this->ajaxReturn(null, '没有权限!' , 1);
		}
		//=======================================
		
		$this->prePost($_POST);
		$info = D('Admin/Info');
		if( $info->create() ){
			$info->MemberID = (int)session('MemberID');
			$info->IsCheck = ($GLOBALS['Config']['MEMBER_ADD_CHECK'] == 1) ? 0 : 1; //是否审核状态
			if($info->add()){
				$lastID = $info->getLastInsID();
				WriteLog("ID:".$lastID , array('LogType'=>2,'UserAction'=>'保存信息添加'));
				save_info_type_attribute($lastID);
				$this->ajaxReturn(null, '添加成功!' , 1);
			}else{
				$this->ajaxReturn(null, '添加失败!' , 0);
			}
		}else{
			$this->ajaxReturn(null, $info->getError() , 0);
		}
	}
	
	//预处理POST变量
	private function prePost(&$p){
		//先处理相册，相册的最终代码在浏览器端使用js脚本控制
		unset($p['AlbumTitle'], $p['AlbumPicture'], $p['AlbumDescription']);
	
		//处理复选框显示
		foreach ($p as $k=>$v){
			if( is_array($v) && substr($k, 0, 5) != 'attr_' ){
				$p[$k] = implode(',', $v);
			}
		}
		if( !isset($p['ReadLevel']) ) $p['ReadLevel'] = '';
		if( !isset($p['LabelID']) ) $p['LabelID'] = '';
		if( !isset($p['SpecialID']) ) $p['SpecialID'] = '';
		if( !isset($p['ChannelIDEx']) ) $p['ChannelIDEx'] = '';
		
		if( !isset($p['f1']) ) $p['f1'] = '';
		if( !isset($p['f2']) ) $p['f2'] = '';
		if( !isset($p['f3']) ) $p['f3'] = '';
		if( !isset($p['f4']) ) $p['f4'] = '';
		if( !isset($p['f5']) ) $p['f5'] = '';
		
		//必须进行XSS过滤防止攻击=======================================
		//yd_remove_xss过滤会有bug，经测试22,33，过滤后变为：2233
		//$_POST = yd_remove_xss( $_POST );
		$_POST = YdInput::checkInfo( $_POST );
		//========================================================
	}
	
	function relation(){
		header("Content-Type:text/html; charset=utf-8");
		$ChannelID = intval($_REQUEST['cid']);
		$InfoID = intval($_REQUEST['iid']);
		$Keywords = !empty($_REQUEST['Keywords']) ? YdInput::checkKeyword( $_REQUEST['Keywords'] ) : '';
		import("ORG.Util.Page");
		$m = D('Admin/Info');
		//$ChannelID = 0, $IsContainChild = 1, $IsEnable = -1, $Keywords='', $MemberID = -1,
		//$SpecialID = 0, $LabelID = '', $IsCheck=-1
		$TotalPage = $m->getCount($ChannelID, 1, 1, $Keywords, -1, 0, '', 1);
		$PageSize = 10;
		$Page = new Page($TotalPage, $PageSize);
		$Page->parameter = "";
		if( $ChannelID != 0){
			$Page->parameter .= "&cid=$ChannelID";
		}
		if( $Keywords != ''){
			$Page->parameter .= "&Keywords=$Keywords";
		}
		$Page->rollPage = 10;
		$ShowPage = $Page->show();
	
		//参数：$FirstRow, $ListRow, $ChannelID = 0, $IsContainChild = 1, $IsEnable = -1, $LabelID = '',
		//$Keywords='', $MemberID = -1, $SpecialID = 0, $IsCheck=-1
		$data = $m->getInfo($Page->firstRow, $Page->listRows, $ChannelID, 1, 1, '', $Keywords, -1, 0, 1);
	
		$m1 = D('Admin/Channel');
		$AdminGroupID = intval(session('AdminGroupID'));
		$MenuOwner = (strtolower(GROUP_NAME)=='admin') ? 1 : 0;
		$Channel = $m1->getChannelPurview($MenuOwner, $AdminGroupID);
        $ChannelNew = array();
		foreach ($Channel as $v){
			$hasChild = $v['HasChild'];
			$channelModelID = $v['ChannelModelID'];
			if( $hasChild == 0 && ($channelModelID==32 || $channelModelID==33) ) continue;
			$ChannelNew[] = array(	'ChannelID' => $v['ChannelID'], 'ChannelName' => $v['ChannelName']);
		}
		unset( $Channel );
	
		$this->assign('Action', __URL__.'/relation');
		$this->assign('NowPage', $Page->getNowPage());
		$this->assign('Page', $ShowPage);
		$this->assign('PageSize', $PageSize);
		$this->assign('SearchWords', $Keywords);
		$this->assign('AdminGroupID', $AdminGroupID ); //当前频道
		$this->assign('MenuOwner', $MenuOwner ); //当前频道
		$this->assign('Relation', $data);
		$this->assign('Channel', $ChannelNew);
		$this->assign('ChannelID', $ChannelID);
		$this->assign('InfoID', $InfoID);
		$this->display();
	}
	
}
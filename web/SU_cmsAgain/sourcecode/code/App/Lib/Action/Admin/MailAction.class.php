<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */
class MailAction extends AdminBaseAction{
	function classIndex(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/MailClass');
		$MailClass = $m->getMailClass();
		$count = is_array($MailClass) ? count($MailClass) : 0;
		$m1 = D('Admin/Mail');
		$MailTotal = $m1->getMailCount(); //邮件总数
		for ($i=0; $i<$count; $i++){
			$MailClass[$i]['MailCount'] = $m1->getMailCount( $MailClass[$i]['MailClassID'] );
		}
		$this->assign('MailTotal', $MailTotal);
		$this->assign('MailClass', $MailClass);
		$this->display();
	}
	
	/**
	 * 添加分类
	 */
	function addClass(){
		header("Content-Type:text/html; charset=utf-8");
	
		//模型属性信息==============================================
		$m = D('Admin/Attribute');
		$Attribute = $m->getAttribute(23);
		$Group = $m->getGroup(23);
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//======================================================
		$this->assign('Action', __URL__.'/SaveAddClass');
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}
	
	function saveAddClass(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/MailClass');
		if( $m->create() ){
			if($m->add()){
				WriteLog("ID:".$m->getLastInsID() );
				$this->ajaxReturn(null, '添加成功!' , 1);
			}else{
				$this->ajaxReturn(null, '添加失败!' , 0);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}
	
	function delClass(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/MailClass');
		$id = $_GET["MailClassID"];
		$data = "#tr$id";
		if( !is_numeric($id) ){
			$this->ajaxReturn($data, '参数非法!' , 3);
		}
		
		if( $m->hasData($id) ){
			$this->ajaxReturn($data, '当前分类包含数据，请先删除!' , 2);
		}
	
		//删除操作
		if( $m->delete($id) ){
			WriteLog("ID:$id");
			$this->ajaxReturn($data, '删除成功!' , 1);
		}else{
			$this->ajaxReturn($data, '删除失败!' , 0);
		}
	}
	
	function batchDelClass(){
		$id = $_POST['MailClassID'];
		//若分类存在数据，则不删除
		$m = D('Admin/MailClass');
		foreach($id as $k=>$v){
			if( $m->hasData($v) ){
				unset( $id[$k] );
			}
		}
		
		if( count($id) > 0 ){
			$m->batchDelMailClass($id);
			WriteLog("ID:".implode(',', $id));
		}
		redirect(__URL__."/classIndex");
	}
	
	function batchSortClass(){
		$MailClassOrder = $_POST['MailClassOrder']; //排序
		$MailClassID = $_POST['MailClassOrderID']; //排序
		if( is_array($MailClassID) && is_array($MailClassOrder) && count($MailClassID) > 0 && count($MailClassOrder) > 0 ){
			D('Admin/MailClass')->batchSortMailClass($MailClassID, $MailClassOrder);
			WriteLog();
		}
		redirect(__URL__."/classIndex");
	}
	
	function modifyClass(){
		header("Content-Type:text/html; charset=utf-8");
		//参数有效性检查===========================
		$MailClassID = $_GET['MailClassID'];
		if( !is_numeric($MailClassID)){
			alert("非法参数", __URL__.'/ClassIndex');
		}
		//====================================
	
		//模型属性信息=================================================
		$m = D('Admin/Attribute');
		$Attribute = $m->getAttribute(23);
		$Group = $m->getGroup(23);
	
		//获取专题数据======================================================
		$m = D('Admin/MailClass');
		$Info = $m->find( $MailClassID );
		for($n = 0; $n < count($Attribute); $n++){
			if( $this->IsSelectedAttribute( $Attribute[$n]['DisplayType'] ) ){
				$Attribute[$n]['SelectedValue'] = $Info[ $Attribute[$n]['FieldName'] ]; //获取频道设置值
			}else{
				$Attribute[$n]['DisplayValue'] = $Info[ $Attribute[$n]['FieldName'] ];
			}
		}
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//==============================================================
	
		$this->assign('HiddenName', 'MailClassID');
		$this->assign('HiddenValue', $MailClassID);
		$this->assign('Action', __URL__.'/saveModifyClass');
	
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}
	
	function saveModifyClass(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/MailClass');
		if( $m->create() ){
			if($m->save() === false){
				$this->ajaxReturn(null, '修改失败!' , 0);
			}else{
				WriteLog( "ID:".$_POST['MailClassID'] );
				$this->ajaxReturn(null, '修改成功!' , 1);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}
	
	function index(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Mail');
		import("ORG.Util.Page");
		
		$MailClassID = isset($_REQUEST['MailClassID']) ? $_REQUEST['MailClassID'] : -1;
		
		$TotalPage = $m->getMailCount($MailClassID);
		$PageSize = $this->AdminPageSize;
		
		$Page = new Page($TotalPage, $PageSize);
		
		if($MailClassID != -1){
			$Page->parameter = "&MailClassID=$MailClassID";
		}
		
		$Page->rollPage = $this->AdminRollPage;
		$ShowPage = $Page->show();
		
		$data= $m->getMail($Page->firstRow, $Page->listRows, $MailClassID);
		
		$mc = D('Admin/MailClass');
		$classData = $mc->getMailClass();
		
		$this->assign('NowPage', $Page->getNowPage()); //分页条
		$this->assign('Mail', $data);
		$this->assign('MailClassID', $MailClassID);
		$this->assign('MailClass', $classData);
		$this->assign('Page', $ShowPage); //分页条
		$this->display();
	}
	
	function add(){
		header("Content-Type:text/html; charset=utf-8");
	
		//模型属性信息==============================================
		$m = D('Admin/Attribute');
		$Attribute = $m->getAttribute(24);
		$Group = $m->getGroup(24);
		for($n = 0; $n < count($Attribute); $n++){
			if($Attribute[$n]['DisplayType'] == 'datetime'){
				$Attribute[$n]['DisplayValue'] = date('Y-m-d H:i:s'); //显示当期时间
				break;
			}
		}
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//======================================================
		$this->assign('Action', __URL__.'/SaveAdd');
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}
	
	function saveAdd(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Mail');
		if( $m->create() ){
			if($m->add()){
				WriteLog("ID:".$m->getLastInsID() );
				$this->ajaxReturn(null, '添加成功!' , 1);
			}else{
				$this->ajaxReturn(null, '添加失败!' , 0);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}
	
	function del(){
		header("Content-Type:text/html; charset=utf-8");
		$MailClassID = isset($_REQUEST['MailClassID']) ? $_REQUEST['MailClassID'] : -1;
		$MailID = $_GET["MailID"];
		$p = $_GET["p"];
		
		if( is_numeric($MailID) && is_numeric($p)){
			$m = D('Admin/Mail');
			if( $m->delete($MailID) ){
				redirect(__URL__."/index?MailClassID=$MailClassID&p=$p");
				WriteLog("ID:$MailID");
			}
		}
	}
	
	function batchDel(){
		$id = $_POST['MailID'];
		$MailClassID = isset($_REQUEST['MailClassID']) ? $_REQUEST['MailClassID'] : -1;
		$NowPage = (int)$_POST["NowPage"];
		if( count($id) > 0 ){
			$m=D('Admin/Mail');
			$m->batchDelMail($id);
			WriteLog("ID:".implode(',', $id));
		}
		redirect(__URL__."/index?MailClassID=$MailClassID&p=$NowPage");
	}
	
	function modify(){
		header("Content-Type:text/html; charset=utf-8");
		//参数有效性检查===========================
		$MailID = $_GET['MailID'];
		if( !is_numeric($MailID)){
			alert("非法参数", __URL__.'/index');
		}
		//====================================
	
		//模型属性信息=================================================
		$m = D('Admin/Attribute');
		$Attribute = $m->getAttribute(24);
		$Group = $m->getGroup(24);
	
		//获取专题数据======================================================
		$m = D('Admin/Mail');
		$Info = $m->find( $MailID );
		for($n = 0; $n < count($Attribute); $n++){
			if( $this->IsSelectedAttribute( $Attribute[$n]['DisplayType'] ) ){
				$Attribute[$n]['SelectedValue'] = $Info[ $Attribute[$n]['FieldName'] ]; //获取频道设置值
			}else{
				$Attribute[$n]['DisplayValue'] = $Info[ $Attribute[$n]['FieldName'] ];
			}
		}
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//==============================================================
	
		$this->assign('HiddenName', 'MailID');
		$this->assign('HiddenValue', $MailID);
		$this->assign('Action', __URL__.'/saveModify');
	
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}
	
	function saveModify(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Mail');
		if( $m->create() ){
			if($m->save() === false){
				$this->ajaxReturn(null, '修改失败!' , 0);
			}else{
				WriteLog("ID:".$_POST['MailID'] );
				$this->ajaxReturn(null, '修改成功!' , 1);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}
	
	//导出邮件，不能使用ajax提交
	function exportMail(){
		$MailClassID = isset($_REQUEST['MailClassID']) ? $_REQUEST['MailClassID'] : -1;
		$csvName = ( $MailClassID == -1 ) ? "all_" : 'mail_';
		$csvName .= date('Y-m-d_H_i').'.csv';  //导出文件名称
		
		$m = D('Admin/Mail');
		$data= $m->getMail(-1, -1, $MailClassID);
		$str= iconv('utf-8', 'gb2312//IGNORE', "电子邮件,订阅者姓名,电话,地址\n");

		//注意导入和导出的过程中，因为我们使用的是统一UTF-8编码，
		//遇到中文字符一定要记得转码，否则可能会出现中文乱码的情况。
		foreach($data as $m){
			$email = $m['MailAccount'];
			$name = iconv('utf-8', 'gb2312//IGNORE',$m['Name']);
			$telephone = iconv('utf-8', 'gb2312//IGNORE',$m['Telephone']);
			$address = iconv('utf-8', 'gb2312//IGNORE',$m['Address']); 
			$str .= "$email,$name,$telephone,$address\n";
		}
		yd_download_csv($csvName, $str); //下载csv
	}
	
	//导入邮件
	function import(){
		$MailClassID = isset($_REQUEST['MailClassID']) ? $_REQUEST['MailClassID'] : -1;
		$m = D('Admin/MailClass');
		$MailClassName = $m->getMailClassName( $MailClassID );
		
		$this->assign('MailClassName', $MailClassName);
		$this->assign('Action', __URL__.'/startImport/MailClassID/'.$MailClassID);
		$this->display();
	}
	
	//开始批量导入
	function startImport(){
		$MailClassID = isset($_REQUEST['MailClassID']) ? $_REQUEST['MailClassID'] : -1;
		
		$filename = $_FILES['csv']['tmp_name'];
		if (empty ($filename)) {
			$this->ajaxReturn(null, '请选择要导入的CSV文件！' , 0);
		}
		
		//读取要导入的数据=======================================
		//电子邮件,订阅者姓名,电话,地址
		$handle = fopen($filename, 'r');
		$m = D('Admin/Mail');
		$data = fgetcsv($handle, 1000);
        $Values = array();
		while ($data) {
			$EmailAccount = $data[0];
			//有效性判断和去重
			if( yd_is_email($EmailAccount) && !$m->hasMail($EmailAccount) ){ 
				//不存在则导入
				$Name = iconv('gb2312', 'utf-8', $data[1]);
				$Telephone = $data[2];
				$Address = iconv('gb2312', 'utf-8', $data[3]);
				$AddTime = date('Y-m-d H:i:s');
                $Values[] = array(
                    'MailClassID'=>$MailClassID,
                    'Telephone'=>$Telephone,
                    'MailAccount'=>$EmailAccount,
                    'Name'=>$Name,
                    'Address'=>$Address,
                    'AddTime'=>$AddTime
                );
			}
			$data = fgetcsv($handle, 1000);
		}
		fclose($handle); //关闭指针
		
		if( empty($Values) ){
			$this->ajaxReturn(null, '导入文件没有数据或数据已经存在！' , 0);
		}
		//===================================================
		$b = $m->addAll($Values);
		if( $b ){
            $n = count($Values);
			$msg = "成功导入{$n}个邮箱帐号";
			WriteLog($msg);
			$this->ajaxReturn(null, $msg , 1);
		}else{
			$this->ajaxReturn(null, '导入CSV数据失败！' , 0);
		} 
	}
	
	//下载cvs模板文件
	function downloadTpl(){
		$csvName = 'mail_'.date('Y-m-d_H_i').'.csv';  //导出文件名称
		$str= iconv('utf-8', 'gb2312//IGNORE', "电子邮件,订阅者姓名,电话,地址\n");
		yd_download_csv($csvName, $str); //下载csv
	}
	
	//邮件群发管理
	function sendIndex(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/MailSend');
		import("ORG.Util.Page");
		
		$TotalPage = $m->getCount();
		$PageSize = $this->AdminPageSize;
		
		$Page = new Page($TotalPage, $PageSize);
		
		$Page->rollPage = $this->AdminRollPage;
		$ShowPage = $Page->show();
		
		$data= $m->getMailSend($Page->firstRow, $Page->listRows);
		
		$this->assign('NowPage', $Page->getNowPage()); //分页条
		$this->assign('MailSend', $data);
		$this->assign('Page', $ShowPage); //分页条
		$this->display();
	}
	
	//添加群发邮件
	function addSend(){
		header("Content-Type:text/html; charset=utf-8");
	
		//模型属性信息==============================================
		$m = D('Admin/Attribute');
		$Attribute = $m->getAttribute(25);
		$Group = $m->getGroup(25);
		for($n = 0; $n < count($Attribute); $n++){
			if($Attribute[$n]['DisplayType'] == 'datetime'){
				$Attribute[$n]['DisplayValue'] = date('Y-m-d H:i:s'); //显示当期时间
				break;
			}
		}
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//======================================================
		$this->assign('Action', __URL__.'/saveAddSend');
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}

	//保存群发邮件
	function saveAddSend(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/MailSend');
		if( $m->create() ){
			if($m->add()){
				WriteLog("ID:".$m->getLastInsID() );
				$this->ajaxReturn(null, '添加成功!' , 1);
			}else{
				$this->ajaxReturn(null, '添加失败!' , 0);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}
	
	function delSend(){
		header("Content-Type:text/html; charset=utf-8");
		$MailSendID = $_GET["MailSendID"];
		$p = $_GET["p"];
	
		if( is_numeric($MailSendID) && is_numeric($p)){
			$m = D('Admin/MailSend');
			if( $m->delete($MailSendID) ){
				WriteLog("ID:$MailSendID");
				redirect(__URL__."/sendIndex/p/$p");
			}
		}
	}
	
	function batchDelSend(){
		$id = $_POST['MailSendID'];
		$NowPage = (int)$_POST["NowPage"];
		if( count($id) > 0 ){
			$m=D('Admin/MailSend');
			$m->batchDelSend($id);
			WriteLog("ID:".implode(',', $id));
		}
		redirect(__URL__."/sendIndex/p/$NowPage");
	}
	
	function modifySend(){
		header("Content-Type:text/html; charset=utf-8");
		//参数有效性检查===========================
		$MailSendID = $_GET['MailSendID'];
		if( !is_numeric($MailSendID)){
			alert("非法参数", __URL__.'/sendIndex');
		}
		//====================================
	
		//模型属性信息=================================================
		$m = D('Admin/Attribute');
		$Attribute = $m->getAttribute(25);
		$Group = $m->getGroup(25);
	
		//获取专题数据======================================================
		$m = D('Admin/MailSend');
		$Info = $m->find( $MailSendID );
		for($n = 0; $n < count($Attribute); $n++){
			if( $this->IsSelectedAttribute( $Attribute[$n]['DisplayType'] ) ){
				$Attribute[$n]['SelectedValue'] = $Info[ $Attribute[$n]['FieldName'] ]; //获取频道设置值
			}else{
				$Attribute[$n]['DisplayValue'] = $Info[ $Attribute[$n]['FieldName'] ];
			}
		}
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//==============================================================
	
		$this->assign('HiddenName', 'MailSendID');
		$this->assign('HiddenValue', $MailSendID);
		$this->assign('Action', __URL__.'/saveModifySend');
	
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}
	
	function saveModifySend(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/MailSend');
		if( $m->create() ){
			if($m->save() === false){
				$this->ajaxReturn(null, '修改失败!' , 0);
			}else{
				WriteLog("ID:".$_POST['MailSendID'] );
				$this->ajaxReturn(null, '修改成功!' , 1);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}
	
	//邮件群发
	function send(){
		header("Content-Type:text/html; charset=utf-8");
		
		//邮件内容================================
		$MailSendID = $_GET["MailSendID"];
		$m = D('Admin/MailSend');
		$data = $m->findMailSend($MailSendID);
		$this->assign('MailTitle', $data['MailTitle']); //邮件标题
		$this->assign('MailContent', $data['MailContent']);
		$this->assign('MailSendID', $MailSendID);
		//======================================
		
		//订阅分组=============================
		$m = D('Admin/MailClass');
		$MailClass = $m->getMailClass(1);
		$this->assign('MailClass', $MailClass);
		//==================================
		
		//会员分组=============================
		$m = D('Admin/MemberGroup');
		$MemberGroup = $m->getMemberGroup();
		$this->assign('MemberGroup', $MemberGroup);
		//==================================
		
		$this->assign('Action', __URL__.'/startSend');
		
		$this->display();
	}
	
	//邮件群发
	function startSend(){
		header("Content-Type:text/html; charset=utf-8");
		//获取订阅邮箱帐号========================
		$MailClassID = $_POST['MailClassID'];
		$m = D('Admin/Mail');
		$d1 = $m->getMailList( $MailClassID );
		if( empty($d1) ) $d1=array();
		//===================================
		
		//获取会员分组邮箱帐号========================
		$MemberGroupID = $_POST['MemberGroupID'];
		$m = D('Admin/Member');
		$d2 = $m->getMemberMail( $MemberGroupID );
		if( empty($d2) ) $d2=array();
		//===================================
		
		//获取自定义接收邮箱========================
		$other = trim($_POST['Other']);
		if( empty($other) || stripos($other, '@') === false ){
			$d3=array();
		}else{
			$d3 = (array)explode(';', $other);
			$nd3 = count($d3);
			for($i=0; $i<$nd3; $i++){
				if( empty($d3[$i]) ){  //删除无效邮箱
					unset( $d3[$i] );
				}
			}
			if( empty($d3) ) $d3=array();
		}

		//====================================
		$all = array_merge($d1, $d2, $d3);
		unset($d1, $d2, $d3);
		$n1 = count($all);
		if($n1 <= 0 ){
			$this->ajaxReturn(null, '没有邮件要发送！' , 0);
		}
		
		//获取待发送邮件内容=======================
		$MailSendID = $_POST['MailSendID'];
		$m = D('Admin/MailSend');
		$data = $m->findMailSend($MailSendID);
		//===================================
        yd_set_time_limit(3600);
		$nSended = 0;
		$log = ''; //发送日志
		for ($i = 0; $i < $n1; $i++){   //删除无效邮件帐号
				$b = sendwebmail($all[$i], $data['MailTitle'], $data['MailContent']);
				if( $b ){
					$nSended++;
					$log .= "<b>".($i+1).".</b>&nbsp;".$all[$i]."&nbsp;&nbsp;&nbsp;<b style='color:blue'>发送成功</b><br/>" ;
				}else {
					$log .= "<b>".($i+1).".</b>&nbsp;".$all[$i]."&nbsp;&nbsp;&nbsp;<b style='color:red'>发送失败</b><br/>" ;
				}
		}
		$nFailed = $n1 - $nSended; //发送失败的邮箱
		$temp = "<b>发送时间：</b>".date('Y-m-d H:i:s')."<br/>";
		$temp .= "共发送<b style='color:blue'>{$n1}</b>封邮件，";
		$temp .= "其中<b  style='color:red'>{$nSended}</b>封邮件发送成功，";
		$temp .= "<b  style='color:red'>{$nFailed}</b>封邮件发送失败！</br>";
		$log = "<div style='padding:5px;overflow-y:scroll;width:380px; height:180px'>".$temp.$log."</div>";
		$m->UpdateSendLog($MailSendID, $log);
		$data = array( 'n1'=>$n1, 'n2'=>$nSended, 'n3'=>$nFailed );
		$this->ajaxReturn($data, '' , 1);
	}
	
	function viewLog(){
		$MailSendID = intval($_GET['MailSendID']);
		$m = D('Admin/MailSend');
		$SendLog = $m->where("MailSendID=$MailSendID")->getField('SendLog');
		echo $SendLog;
	}
}
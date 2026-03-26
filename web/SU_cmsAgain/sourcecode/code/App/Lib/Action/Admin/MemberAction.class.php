<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class MemberAction extends AdminBaseAction {
	function index(){
		header("Content-Type:text/html; charset=utf-8");
		$Keywords = isset($_REQUEST['Keywords']) ? YdInput::checkKeyword( $_REQUEST['Keywords'] ) : '';
		$IsCheck =  isset($_REQUEST['IsCheck']) && is_numeric($_REQUEST['IsCheck'])  ? $_REQUEST['IsCheck'] : -1;
		//由于微信会员显示东西太多，所有默认为注册会员
		$MemberGroupID =  isset($_REQUEST['MemberGroupID']) ? $_REQUEST['MemberGroupID'] : 1;
		$m = D('Admin/Member');
		import("ORG.Util.Page");
		$TotalPage = $m->getCount($Keywords, $IsCheck, $MemberGroupID);
		$PageSize = $this->AdminPageSize;
	
		$Page = new Page($TotalPage, $PageSize);
		$Page->parameter = "&IsCheck=$IsCheck&MemberGroupID=$MemberGroupID";
		if( $Keywords != ''){
			$Page->parameter .= "&Keywords=$Keywords";
		}
		
		$Page->rollPage = $this->AdminRollPage;
		$ShowPage = $Page->show();
	
		$Member= $m->getMember($Page->firstRow, $Page->listRows, $Keywords, $IsCheck, $MemberGroupID);
		
		$mg = D('Admin/MemberGroup');
		$MemberGroup = $mg->getMemberGroup();
	
		$this->assign('NowPage', $Page->getNowPage());
		$this->assign('Keywords', $Keywords);
		$this->assign('IsCheck', $IsCheck);
		$this->assign('Member', $Member);
		$this->assign('MemberGroup', $MemberGroup);
		$this->assign('MemberGroupID', $MemberGroupID);
		$this->assign('Page', $ShowPage); //分页条
		$this->display();
	}
	
	function add(){
		header("Content-Type:text/html; charset=utf-8");
		//模型属性信息==============================================
		$m = D('Admin/Attribute');
		$Attribute = $m->getAttribute(13);
		$Group = $m->getGroup(13);
		for($n = 0; $n < count($Attribute); $n++){
				if( $Attribute[$n]['FieldName'] =='LastLoginTime' || $Attribute[$n]['FieldName'] =='LastLoginIP' ){
					unset($Attribute[$n]);
				}
				if(isset($Attribute[$n]) && $Attribute[$n]['FieldName'] =='RegisterTime' ){
					$Attribute[$n]['DisplayType'] = 'datetime';
					$Attribute[$n]['DisplayValue'] = date('Y-m-d H:i:s');
				}
		}
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//======================================================
		$this->assign('Action', __URL__.'/saveAdd');
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}
	
	function saveAdd(){
		header("Content-Type:text/html; charset=utf-8");
		$m = D('Admin/Member');
        if(!empty($_POST['MemberAnswer'])){
            $_POST['MemberAnswer'] = yd_password_hash($_POST['MemberAnswer']);
        }
		if( $m->create() ){
			//自动成为分销商========================================
			$DistributeEnable = $GLOBALS['Config']['DistributeEnable'];
			$DistributeRequirement = $GLOBALS['Config']['DistributeRequirement'];
			if(1==$DistributeEnable && 1==$DistributeRequirement){
				$md = D('Admin/DistributorLevel');
				$DistributorLevelID = $md->getLowestDistributorLevelID();
				$m->IsDistributor = 1;
				$m->DistributorLevelID = $DistributorLevelID;
				$m->DistributorTime = $_POST['RegisterTime'];
				$m->InviteCode = MakeInviteCode();
			}else{
				$m->IsDistributor = 0;
				$m->DistributorLevelID = 0;
				$m->InviteCode = '';
			}
			//==================================================
			if($m->add()){
			    $des = $_POST['MemberName'];
				WriteLog("ID:".$m->getLastInsID()." {$des}" );
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
		$MemberID = $_GET["MemberID"];
		$p = $_GET["p"];
	
		if( is_numeric($MemberID) && is_numeric($p)){
			D('Admin/Member')->where("IsSystem = 0 and MemberID=$MemberID")->delete();
			WriteLog("ID:$MemberID");
		}
		redirect(__URL__."/index/p/$p");
	}
	
	function batchDel(){
		$id = $_POST['MemberID'];
		$NowPage = (int)$_POST["NowPage"];
		$len = is_array($id) ? count($id) : 0;
		
		$m = D('Admin/Member');
		for($i = 0; $i < $len; $i++){
			if( is_numeric($id[$i]) ){
				$m->where("IsSystem = 0 and MemberID=$id[$i]")->delete();
			}
		}
		WriteLog("ID:".implode(',', $id));
		redirect(__URL__."/index/p/$NowPage");
	}
	
	function modify(){
		header("Content-Type:text/html; charset=utf-8");
		//参数有效性检查===========================
		$MemberID = $_GET['MemberID'];
		if( !is_numeric($MemberID)){
			alert("非法参数", __URL__.'/index');
		}
		//====================================
	
		//模型属性信息=================================================
		$m = D('Admin/Attribute');
		$Attribute = $m->getAttribute(13);
		$Group = $m->getGroup(13);
	
		//获取专题数据======================================================
		$m = D('Admin/Member');
		$Info = $m->find( $MemberID );
		$Info['MemberAnswer'] = ''; //不能显示密保答案
		for($n = 0; $n < count($Attribute); $n++){
			if( $this->IsSelectedAttribute( $Attribute[$n]['DisplayType'] ) ){
				$Attribute[$n]['SelectedValue'] = $Info[ $Attribute[$n]['FieldName'] ]; //获取频道设置值
			}else{
				$Attribute[$n]['DisplayValue'] = $Info[ $Attribute[$n]['FieldName'] ];
			}
		}
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//==============================================================
	
		$this->assign('HiddenName', 'MemberID');
		$this->assign('HiddenValue', $MemberID);
		$this->assign('Action', __URL__.'/saveModify');
	
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}
	
	function saveModify(){
		header("Content-Type:text/html; charset=utf-8");
		//留空表示不修改密保答案
		if(empty($_POST['MemberAnswer'])){
		    unset($_POST['MemberAnswer']);
        }else{
            $_POST['MemberAnswer'] = yd_password_hash($_POST['MemberAnswer']);
        }
		$m = D('Admin/Member');
		//处理复选框显示
		foreach ($_POST as $k=>$v){
			if( is_array($v) ){ //不处理类型属性字段
				$_POST[$k] = implode(',', $v);
			}
		}
		if( $m->create() ){
			if($m->save() === false){
				$this->ajaxReturn(null, '修改失败!' , 0);
			}else{
				WriteLog( "ID:{$_POST['MemberID']} {$_POST['MemberName']}" );
				$this->ajaxReturn(null, '修改成功!' , 1);
			}
		}else{
			$this->ajaxReturn(null, $m->getError() , 0);
		}
	}
	
	function batchCheck(){
		$id = $_POST['MemberID'];
		$NowPage = (int)$_POST["NowPage"];
		$Check = $_GET['Check'];  //审核值
		if( count($id) > 0 ){
			D('Admin/Member')->batchCheckMember( $id , $Check);
		}
		WriteLog("ID:".implode(',', $id));
		redirect(__URL__."/index/p/$NowPage");
	}
	
	function batchLock(){
		$id = $_POST['MemberID'];
		$NowPage = (int)$_POST["NowPage"];
		$Lock = $_GET['Lock'];  //审核值
		if( count($id) > 0 ){
			D('Admin/Member')->batchLockMember( $id , $Lock);
		}
		WriteLog("ID:".implode(',', $id));
		redirect(__URL__."/index/p/$NowPage");
	}
	
	function batchModifyPwd(){
		$id = $_POST['MemberID'];
		$pwd1 = $_POST['pwd1'];
		$pwd2 = $_POST['pwd2'];
		if( count($id) > 0 && $pwd1 == $pwd2 ){
			// md5 return 32-character hexadecimal number
			$m = D('Admin/Member');
            $pwd1 = yd_password_hash($pwd1);
			$m->batchModifyPwd( $id , $pwd1 );
			WriteLog("ID:".implode(',', $id));
			$this->ajaxReturn(null, '修改成功!' , 1);
		}
		$this->ajaxReturn(null, '修改失败!' , 0);
	}
	
	function setAdmin(){
		$NowPage = (int)$_POST["NowPage"];
		
		$data['AdminName'] = $_POST['dlgAdminName'];
		$data['MemberID'] = $_POST['dlgMemberID']; //前台成员
		$data['AdminGroupID'] = $_POST['dlgAdminGroupID'];
		$data['AdminPassword'] = $_POST['pwd3']; //管理密码
		
		$pwd  = $_POST['pwd4'];
		if( is_numeric( $data['MemberID'] ) && $data['AdminPassword']  == $pwd ){
			$data['AdminPassword'] = yd_password_hash($pwd);
			$a = D('Admin/Admin');
			if( $a->create($data) ){
				$b = $a->add();
				WriteLog("ID:".$_POST['dlgMemberID'] );
			}
		}
		redirect(__URL__."/index/p/$NowPage");
	}

	/**
	 * 会员代管
	 */
	function take(){
		header("Content-Type:text/html; charset=utf-8");
		$MemberID = $_GET["MemberID"];
		if(!is_numeric($MemberID)) return;
		$m = D('Admin/Member');
		$data = $m->findMember($MemberID);
		session('MemberID', (int)$data['MemberID']);
		session('MemberName', $data['MemberName']);
		session('MemberGroupID', (int)$data['MemberGroupID']);
		session('MemberGroupName', $data['MemberGroupName']);
		session('MemberAvatar', $data['MemberAvatar']);
		session('DiscountRate', is_numeric($data['DiscountRate']) ? $data['DiscountRate'] : 1);
		WriteLog("ID:".$MemberID);
		redirect(__APP__.'/Member/Public/index');
	}
	
	//导出
	function export(){
		$csvName = 'member'.date('Y-m-d_H_i').'.csv';  //导出文件名称
	
		$m = D('Admin/Member');
		$data= $m->getMember();
		$g = array(0=>'男',1=>'女');
		$str= "会员名称,会员分组ID,会员分组,性别,真实姓名,生日,邮政编码,移动电话,电话,QQ,E-mail,联系地址\n";
		//注意导入和导出的过程中，因为我们使用的是统一UTF-8编码，
		//遇到中文字符一定要记得转码，否则可能会出现中文乱码的情况。
		$i=0;
		foreach($data as $v){
			$MemberName = $v['MemberName'];
			$MemberGroupID = $v['MemberGroupID'];
			$MemberGroupName = $v['MemberGroupName'];
			$MemberGender = $g[$v['MemberGender']];
			$MemberRealName = $v['MemberRealName'];
			
			$MemberBirthday = $v['MemberBirthday'];
			$MemberPostCode = $v['MemberPostCode'];
			$MemberMobile = $v['MemberMobile'];
			$MemberTelephone = $v['MemberTelephone'];
			$MemberQQ = $v['MemberQQ'];
			
			$MemberEmail = $v['MemberEmail'];
			$MemberAddress = $v['MemberAddress'];
			
			$str .= "$MemberName,$MemberGroupID,$MemberGroupName,$MemberGender,$MemberRealName,$MemberBirthday,";
			$str .= "$MemberPostCode,$MemberMobile,$MemberTelephone,$MemberQQ,$MemberEmail,$MemberAddress\n";
			
			if($i==47){
				$temp = "$MemberName,$MemberGroupID,$MemberGroupName,$MemberGender,$MemberRealName,$MemberBirthday,";
				$temp .= "$MemberPostCode,$MemberMobile,$MemberTelephone,$MemberQQ,$MemberEmail,$MemberAddress\n";
			}
			$i++;
		}
		WriteLog();
		//不加//IGNORE，碰到无法转换的字符会断开，从而导致转换后的字符串不完整
		$str= iconv('utf-8', 'gb2312//IGNORE', $str);
		yd_download_csv($csvName, $str); //下载csv
	}
}
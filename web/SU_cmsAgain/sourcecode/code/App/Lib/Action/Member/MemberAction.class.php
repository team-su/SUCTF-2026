<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class MemberAction extends MemberBaseAction {
	//后台管理首页
    public function index(){
       	header("Content-Type:text/html; charset=utf-8");
		$this->display();
    }
    
    function modify(){
    	header("Content-Type:text/html; charset=utf-8");
    	$MemberID = (int)session('MemberID');
    	if( !is_numeric($MemberID)){
    		alert("非法参数", __URL__.'/Index');
    	}

    	$m = D('Admin/Attribute');
    	$Attribute = $m->getAttribute(13);
    	$Group = $m->getGroup(13);
    	
    	//获取会员数据====================================================
    	$m = D('Admin/Member');
    	$data = $m->find( $MemberID );
    	for($n = 0; $n < count($Attribute); $n++){
    		$FieldName = strtolower( $Attribute[$n]['FieldName'] );
    		if( $this->IsSelectedAttribute( $Attribute[$n]['DisplayType'] ) ){
    			$Attribute[$n]['SelectedValue'] = $data[ $Attribute[$n]['FieldName'] ]; //获取频道设置值
    		}else{
    			$Attribute[$n]['DisplayValue'] = $data[ $Attribute[$n]['FieldName'] ];
    		}  		
    		//会员名称和分组以label形式显示
    		switch( $FieldName ){
    			case 'membergroupid':
    				$Attribute[$n]['DisplayClass'] = '';
    				$Attribute[$n]['DisplayType'] = 'label';
    				$Attribute[$n]['DisplayValue'] = "<b style='color:red'>".session('MemberGroupName')."</b>";
    				break;
    			case 'membername':
    				if( !empty($Attribute[$n]['DisplayValue'])){
	    				$Attribute[$n]['DisplayClass'] = '';
	    				$Attribute[$n]['DisplayType'] = 'label';
	    				$Attribute[$n]['DisplayValue'] = "<b style='color:red'>".$Attribute[$n]['DisplayValue']."</b>";
    				}
    				break;
    			case 'ischeck':
    				$Attribute[$n]['DisplayClass'] = '';
    				$Attribute[$n]['DisplayType'] = 'label';
    				$Attribute[$n]['DisplayValue'] = "<b style='color:red'>已审核</b>";
    				break;
    			case 'islock': 
    				$Attribute[$n]['DisplayClass'] = '';
    				$Attribute[$n]['DisplayType'] = 'label';
    				$Attribute[$n]['DisplayValue'] = "<b style='color:red'>未锁定</b>";
    				break;
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
    	//防止注入, 过滤关键字段，防止用户自己修改权限
    	unset($_POST['MemberPassword'], $_POST['IsCheck'],$_POST['IsLock'],$_POST['MemberGroupID'],$_POST['IsSystem']);
    	unset($_POST['IsDistributor'],$_POST['DistributorLevelID'],$_POST['CashPassword'],$_POST['InviteCode']);
    	unset($_POST['InviterID'],$_POST['RegisterTime'],$_POST['LoginCount'],$_POST['OpenID']);
        unset($_POST['MemberQuestion'],$_POST['MemberQuestion']);
    	//处理复选框显示
    	foreach ($_POST as $k=>$v){
    		if( is_array($v) ){ //不处理类型属性字段
    			$_POST[$k] = implode(',', $v);
    		}
    	}
    	$_POST = YdInput::checkReg( $_POST ); //xss过滤
    	$m = D('Admin/Member');
    	if( $m->create() ){
    		$m->MemberID = (int)session('MemberID'); //作为查询条件
    		if($m->save() === false){
    			$this->ajaxReturn(null, '修改失败!' , 0);
    		}else{
    			WriteLog( "ID:".(int)session('MemberID') );
    			$this->ajaxReturn(null, '修改成功!' , 1);
    		}
    	}else{
    		$this->ajaxReturn(null, $m->getError() , 0);
    	}
    }
}
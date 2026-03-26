<?php

class MembermodelAction extends AdminBaseAction {
	//会员模型管理首页
	function index(){
		header("Content-Type:text/html; charset=utf-8");
		$ChannelModelID = 13;  //会员模型ID
		$m = D('Admin/Attribute');
		$data = $m->getAttribute($ChannelModelID, true, -1, -1);
		$type = $m->getDisplayType();
        $t = array();
		foreach ($type as $v){
			$t[ $v['DisplayTypeID'] ] = $v['DisplayTypeName'];
		}
		$n = is_array($data) ? count($data) : 0;
		for($i = 0; $i < $n; $i++){
		    $key = $data[$i]['DisplayType'];
			$data[$i]['DisplayTypeName'] = isset($t[$key]) ? $t[$key] : '';
		}
		$d = array();
		for($i = 0; $i < $n; $i++){
			if( $data[$i]['GroupID'] == 0 ){ //分组
				$d[] = $data[$i];
				$CurrentGroup = $data[$i]['AttributeID'];
				for($j = 0; $j < $n; $j++){
					if($data[$j]['GroupID'] == $CurrentGroup){
						$d[] = $data[$j];
					}
				}
			}
		}
		$this->assign('ChannelModelID', $ChannelModelID);
		$this->assign('Attribute', $d);
		unset($data, $d);
		$this->display();
	}
	
	/**
	 * 修改模型字段
	 */
	function modify(){
		header("Content-Type:text/html; charset=utf-8");
		//参数有效性检查==================================
		$ChannelModelID = intval($_GET['ChannelModelID']);
		$AttributeID = $_GET['AttributeID'];
		if( !is_numeric($ChannelModelID) || !is_numeric($AttributeID)){
			alert("非法参数", __URL__.'/index');
		}
		//===========================================
	
		//模型属性信息===================================================================
		$m = D('Admin/Attribute');
		$id = ( $m->IsGroup($AttributeID) ? 21 : 20 );
		$Attribute = $m->getAttribute($id);
		$Group = $m->getGroup($id);
				
		$ChannelModelName = D('Admin/ChannelModel')->where("ChannelModelID=$ChannelModelID")->getField('ChannelModelName');
			
		//获取模型数据===================================================================
		$m = D('Admin/Attribute');
		$info = $m->find( $AttributeID );
		for($n = 0; $n < count($Attribute); $n++){
			if( $this->IsSelectedAttribute( $Attribute[$n]['DisplayType'] ) ){
				if ( $Attribute[$n]['DisplayType'] == 'displaytypeselect'){
					$Attribute[$n]['SelectedValue'] = $info[ $Attribute[$n]['FieldName'] ]; //获取频道设置值
				}else if($Attribute[$n]['DisplayType'] == 'attributegroupselect'){
					$Attribute[$n]['SelectedValue'] = $info[ $Attribute[$n]['FieldName'] ];
					$Attribute[$n]['ChannelModelID'] = $ChannelModelID;
				}else{
					$Attribute[$n]['SelectedValue'] = $info[ $Attribute[$n]['FieldName'] ]; //获取频道设置值
				}
			}else if ( strtolower($Attribute[$n]['DisplayType']) == 'label'){
				$Attribute[$n]['DisplayValue'] =  "<b style='color:blue'>".$info[ $Attribute[$n]['FieldName'] ]."</b>";
			}else{
				$Attribute[$n]['DisplayValue'] = $info[ $Attribute[$n]['FieldName'] ];
			}
			
			if ( strtolower($Attribute[$n]['FieldName']) == 'channelmodelid'){
				$Attribute[$n]['DisplayValue'] =  "<b style='color:blue'>$ChannelModelName</b>"; 
			}
		}
		$Attribute = parent::parseAttribute($Attribute);  //解析属性信息
		//==============================================================
	
		$this->assign('ChannelModelID', $ChannelModelID);
		$this->assign('AttributeID', $AttributeID);
		$this->assign('Action', __URL__.'/saveModify');
		$this->assign('Group', $Group);
		$this->assign('Attribute', $Attribute);
		$this->display();
	}
	
	/**
	 * 更新模型字段
	 */
	function saveModify(){
		header("Content-Type:text/html; charset=utf-8");
		$c = D('Admin/Attribute');
		if( $c->create() ){
			if($c->save() === false){
				$this->ajaxReturn(null, '修改失败!' , 0);
			}else{
				WriteLog("ID:".$_POST['AttributeID'] );
				$this->ajaxReturn(null, '修改成功!' , 1);
				YdCache::deleteTemp(); //清除缓存
			}
		}else{
			$this->ajaxReturn(null, $c->getError() , 0);
		}
	}
	
	function batchSort(){
		$ChannelModelID = 13;
		$Order = $_POST['DisplayOrder'];
		$ID = $_POST['AttributeID'];
		if( is_array($ID) && is_array($Order) && count($ID) > 0 && count($Order) > 0 ){
			D('Admin/Attribute')->batchSortAttribute($ID, $Order);
			YdCache::deleteTemp(); //清除缓存
			WriteLog();
		}
		redirect(__URL__."/index");
	}
	
}
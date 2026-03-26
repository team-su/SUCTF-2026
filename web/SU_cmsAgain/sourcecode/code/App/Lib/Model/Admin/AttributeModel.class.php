<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class AttributeModel extends Model {
	
	/**
	 * 获取频道模型所有属性
	 * @param int $channelID
	 * @return array attribute
	 */
	function getAttribute($channeModellID, $ContailGroup=false, $IsEnable=1, $IsSystem=-1, $idlist=-1){
		$channeModellID = intval($channeModellID);
		$where = "ChannelModelID=$channeModellID";
		if($ContailGroup==false){
			$where .= " and GroupID != 0";	
		}
		
		if($IsEnable != -1){
			$IsEnable = intval($IsEnable);
			$where .= " and IsEnable = $IsEnable";
		}
		
		if($IsSystem != -1){
			$IsSystem = intval($IsSystem);
			$where .= " and IsSystem = $IsSystem";
		}
		
		$flag = substr($idlist, 0, 1);
		if($idlist != -1 && $flag != '^'){
            $idlist = YdInput::checkCommaNum($idlist);
			$where .= " and AttributeID in ($idlist)";
			$this->where( $where )->order("field(AttributeID,$idlist)");
		}else{
			if( $flag == '^'){
			    $tempIdList = substr($idlist, 1);
                $tempIdList = YdInput::checkCommaNum($tempIdList);
				$where .= " and AttributeID not in ({$tempIdList})";
			}
			$this->where($where)->order('DisplayOrder asc, AttributeID desc');
		}

		if( APP_DEBUG ){ //如果是调试模式，就不缓存
			$result = $this->select();
		}else{
			$result = $this->cache(true, 0)->select();
		}
		return $result;
	}
	
	/**
	 * 获取分组信息
	 * @return array group
	 */
	function getGroup($channeModellID, $IsEnable = 1){
		$channeModellID = intval($channeModellID);
		$where = "ChannelModelID=$channeModellID and  GroupID=0";
		if($IsEnable != -1){
			$IsEnable = intval($IsEnable);
			$where .= " and IsEnable = $IsEnable";
		}
		$this->field('AttributeID,DisplayName');
		$result = $this->where($where)->order('DisplayOrder asc, AttributeID desc')->select();
		return $result;
	}
	
	function IsGroup($AttributeID){
		$AttributeID = intval($AttributeID);
		$GroupID = $this->where("AttributeID=$AttributeID")->getField('GroupID');
		if( $GroupID == 0 ){
			return true;
		}else{
			return false;
		}
	}

	//批量排序
	function batchSortAttribute($AttributeID=array(), $DisplayOrder = array() ){
		$n = is_array($AttributeID) ? count($AttributeID) : 0;
		for($i = 0; $i < $n; $i++){
			if( is_numeric($DisplayOrder[$i]) ){
			    $id = intval($AttributeID[$i]);
				$this->where("AttributeID={$id}")->setField('DisplayOrder', $DisplayOrder[$i]);
			}
		}
	}
	
	
	/**
	 * 获取显示类型
	 */
	function getDisplayType(){
		$data = array();
		//基本类型
		$data[] = array('DisplayTypeID'=>'text', 'DisplayTypeName'=>'单行文本');
		$data[] = array('DisplayTypeID'=>'textarea', 'DisplayTypeName'=>'多行文本');
		$data[] = array('DisplayTypeID'=>'radio', 'DisplayTypeName'=>'单选按钮');
	    $data[] = array('DisplayTypeID'=>'checkbox', 'DisplayTypeName'=>'复选按钮');
	    $data[] = array('DisplayTypeID'=>'password', 'DisplayTypeName'=>'密码框');
		$data[] = array('DisplayTypeID'=>'select', 'DisplayTypeName'=>'下拉列表');
		$data[] = array('DisplayTypeID'=>'label', 'DisplayTypeName'=>'文本标签');
		$data[] = array('DisplayTypeID'=>'datetime', 'DisplayTypeName'=>'日期时间');
		$data[] = array('DisplayTypeID'=>'image', 'DisplayTypeName'=>'图片上传');
		$data[] = array('DisplayTypeID'=>'imageex', 'DisplayTypeName'=>'扩展图片上传');
		$data[] = array('DisplayTypeID'=>'attachment', 'DisplayTypeName'=>'附件上传');
		$data[] = array('DisplayTypeID'=>'editor', 'DisplayTypeName'=>'编辑器');
		$data[] = array('DisplayTypeID'=>'editormini', 'DisplayTypeName'=>'简单编辑器');
		
		$data[] = array('DisplayTypeID'=>'channelselect', 'DisplayTypeName'=>'频道列表');
		$data[] = array('DisplayTypeID'=>'channelexselect', 'DisplayTypeName'=>'多选频道列表');
		$data[] = array('DisplayTypeID'=>'specialselect', 'DisplayTypeName'=>'专题列表');
		$data[] = array('DisplayTypeID'=>'adgroupselect', 'DisplayTypeName'=>'广告位列表');
		$data[] = array('DisplayTypeID'=>'bannergroupselect', 'DisplayTypeName'=>'幻灯分组列表');
		$data[] = array('DisplayTypeID'=>'linkclassselect', 'DisplayTypeName'=>'友情链接分组列表');
		$data[] = array('DisplayTypeID'=>'typeselect', 'DisplayTypeName'=>'信息类型列表');
		
		
		$data[] = array('DisplayTypeID'=>'labelcheckbox', 'DisplayTypeName'=>'属性复选按钮');
		$data[] = array('DisplayTypeID'=>'membergroupcheckbox', 'DisplayTypeName'=>'会员分组复选按钮');
		$data[] = array('DisplayTypeID'=>'membergroupselect', 'DisplayTypeName'=>'会员组列表');
		
		$data[] = array('DisplayTypeID'=>'coordinate', 'DisplayTypeName'=>'地理位置');
		$data[] = array('DisplayTypeID'=>'color', 'DisplayTypeName'=>'颜色选择');
		$data[] = array('DisplayTypeID'=>'album', 'DisplayTypeName'=>'相册');
		$data[] = array('DisplayTypeID'=>'relation', 'DisplayTypeName'=>'相关信息');
		$data[] = array('DisplayTypeID'=>'areaselect', 'DisplayTypeName'=>'省/市二级联动');
		$data[] = array('DisplayTypeID'=>'areaselect4', 'DisplayTypeName'=>'省/市/区县/城镇四级联动');

        $data[] = array('DisplayTypeID'=>'site', 'DisplayTypeName'=>'所属分站');
		return $data;
	}

	/**
	 * 获取指定模型的采集属性，如果有子频道则不获取
	 */
	function getCollectAttribte($channelModelID,$hasChild){
		$channelModelID = intval($channelModelID);
		$result = array('FieldName'=>'', 'DisplayName'=>'');
		if( $hasChild == 0 ){
			$where = "IsEnable = 1 and ChannelModelID=$channelModelID";
			$where .= " and FieldName not in('IsCheck', 'IsHtml', 'Html', 'IsEnable' )";
			$where .= " and GroupID in (select AttributeID from {$this->tablePrefix}attribute ";
			$where .= " where ChannelModelID=$channelModelID and  GroupID=0 and IsEnable = 1) ";
			$this->where($where)->order('GroupID asc, DisplayOrder asc, AttributeID desc');
			$data = $this->field('AttributeID,FieldName,DisplayName')->select();
			if( !empty($data) ){
                $t1 = array();
                $t2 = array();
                $t3 = array();
				foreach ($data as $v){
					$t1[] =$v['FieldName'];
					$t2[] =$v['DisplayName'];
					$t3[] = $v['AttributeID'];
				}
				$result['FieldName'] = implode('@', $t1);
				$result['DisplayName'] = implode('@', $t2);
				$result['AttributeID'] = implode('@', $t3);
			}
		}
		return $result;
	}
}

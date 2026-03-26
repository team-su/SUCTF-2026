<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */
class ChannelModelModel extends Model {
	protected $_validate = array(
			array('ChannelModelName', 'require', '模型名称不能为空!'),
			array('ChannelModelName', '', '模型名称已经存在!', '0', 'unique'),
			array('ChannelModelOrder', '/^[-]?\d+$/', '排序必须为数字!', '2','regex'),
	);
	
	//$IsChannelModel:是否是系统频道模型
	function getChannelModel($IsSystem = 0, $IsEnable=1, $IsChannelModel = false){
		$where = "1=1";
		if($IsSystem != -1){
			$IsSystem = intval($IsSystem);
			$where .= " and IsSystem=$IsSystem";
		}
		if($IsEnable != -1){
			$IsEnable = intval($IsEnable);
			$where .= " and IsEnable=$IsEnable";
		}
		if($IsChannelModel){ //频道模型
			$where .= " and ChannelModelID>=30";
		}
		$result = $this->where($where)->order('ChannelModelOrder asc, ChannelModelID desc')->select();
		return $result;
	}
	
	//批量排序
	function batchSortChannelModel($ChannelModelID=array(), $ChannelModelOrder = array() ){
		$n = count($ChannelModelID);
		for($i = 0; $i < $n; $i++){
			if( is_numeric($ChannelModelOrder[$i]) ){
				$id = intval($ChannelModelID[$i]);
				$this->where("ChannelModelID={$id}")->setField('ChannelModelOrder', $ChannelModelOrder[$i]);
			}
		}
	}

	//初始化模型
	function InitModel($ChannelModelID){
		$ChannelModelID = intval($ChannelModelID);
		$max= D('Admin/Attribute')->max('AttributeID');
		$max1 = $max + 1;
		$max2= $max + 2;
		$max3 = $max+ 3;
		$max4= $max + 4;
		$max5 = $max + 5;
		$max6 = $max + 6;
	
		$sqlGroup = "INSERT INTO {$this->tablePrefix}attribute(AttributeID,ChannelModelID,FieldName,FieldType,DisplayName,DisplayType,DisplayOrder,DisplayWidth,DisplayHeight,DisplayClass,DisplayValue,DisplayHelpText,IsValidate,ValidateRule,GroupID,IsEnable,IsSystem)  VALUES
		('$max1', '$ChannelModelID', null, null, '基本信息', null, '0', null, null, null, null, null, null, null, '0', '1', '0'),
		('$max3', '$ChannelModelID', null, null, '搜索引擎优化设置', null, '1', null, null, null, null, null, null, null, '0', '1', '1'),
		('$max6', '$ChannelModelID', null, null, '相册/相关信息', null, '3', null, null, null, null, null, null, null, '0', '1', '1'),
		('$max2', '$ChannelModelID', null, null, '其它信息', null, '5', null, null, null, null, null, null, null, '0', '0', '0'),
		('$max4', '$ChannelModelID', null, null, '扩展分组1', null, '6', null, null, null, null, null, null, null, '0', '0', '0'),
		('$max5', '$ChannelModelID', null, null, '扩展分组2', null, '7', null, null, null, null, null, null, null, '0', '0', '0');
		";
	
		$result = $this->execute($sqlGroup);
		if(!$result) return false;
	
		$sqlField = "INSERT INTO {$this->tablePrefix}attribute(ChannelModelID,FieldName,FieldType,DisplayName,DisplayType,DisplayOrder,DisplayWidth,DisplayHeight,DisplayClass,DisplayValue,DisplayHelpText,IsValidate,ValidateRule,GroupID,IsEnable,IsSystem) VALUES
		('$ChannelModelID', 'ChannelID', 'int', '所属频道', 'channelselect', '0', null, null, null, null, null, null, null, '$max1', '1', '1'),
		('$ChannelModelID', 'ChannelIDEx', 'varchar(255)', '所属扩展频道', 'channelexselect', '0', null, null, null, null, null, null, null, '$max1', '1', '1'),
		('$ChannelModelID', 'SpecialID', 'varchar(512)', '所属专题', 'specialselect', '3', null, null, null, null, null, null, null, '$max2', '0', '1'),
		('$ChannelModelID', 'InfoTitle', 'varchar(100)', '标题', 'text', '1', '50%', null, 'textinput', null, null, null, null, '$max1', '1', '1'),
		('$ChannelModelID', 'InfoSContent', 'text', '简短内容', 'textarea', '7', '100%', '100px', null, null, null, null, null, '$max2', '0', '0'),
		('$ChannelModelID', 'InfoContent', 'text', '详细内容', 'editor', '8', '100%', '450px', null, null, null, null, null, '$max1', '1', '1'),
		('$ChannelModelID', 'HasPicture', 'int', '是否启用代表图片', 'radio', '5', '', null, null, '1|启用\r\n0|禁用|1', null, null, null, '$max2', '0', '0'),
		('$ChannelModelID', 'InfoPicture', 'varchar(255)', '代表图片', 'image', '6', '100%', null, 'textinput', null, null, null, null, '$max2', '0', '0'),
		('$ChannelModelID', 'ReadLevel', 'int', '阅读权限', 'text', '9', null, null, 'textinput', null, null, null, null, '$max2', '0', '1'),
		('$ChannelModelID', 'InfoAttachment', 'varchar(255)', '附件', 'text', '11', null, null, 'textinput', null, '启用附件后有效！', null, null, '$max2', '0', '0'),
		('$ChannelModelID', 'HasAttachment', 'int', '是否启用附件', 'radio', '10', null, null, null, '1|启用\r\n0|禁用|1', null, null, null, '$max2', '0', '0'),
		('$ChannelModelID', 'IsLinkUrl', 'int', '是否是转向链接', 'radio', '12', null, null, null, '1|是\r\n0|否|1', null, null, null, '$max2', '0', '0'),
		('$ChannelModelID', 'LinkUrl', 'varchar(255)', '链接', 'text', '13', '270px', null, 'textinput', null, '是转向链接时有效！', null, null, '$max2', '0', '0'),
		
		('$ChannelModelID', 'Html', 'varchar(50)', '静态页面名称', 'text', '0', '150px', '', 'textinput', '', '设置频道在前台页面显示的静态文件名称，无需添加扩展名，常常以频道名称拼音命名，<b>留空则取频道名称拼音首字母作为页面名称！</b>如：wangzhanjianshe', '0', '', '$max3', '1', '1'),
		('$ChannelModelID', 'Title', 'varchar(255)', '页面标题Title', 'text', '0', '100%', '', 'textinput', '', '设置页面标题，留空表示继承上级频道设置！建议在多个关键词之间以竖线\"|\"分隔，并且最好不要超过80个字符！如：软件开发|网站建设|友点软件', '0', '', '$max3', '1', '1'),
		('$ChannelModelID', 'Keywords', 'varchar(255)', '页面关键词Keywords', 'text', '1', '100%', null, 'textinput', null, '设置页面关键词，留空表示继承上级频道设置！建议在多个关键词之间以英文逗号\",\"分隔，并且最好不要超过100个字符！如：软件开发,网站建设,友点软件', null, null, '$max3', '1', '1'),
		('$ChannelModelID', 'Description', 'varchar(512)', '页面描述Description', 'textarea', '2', '100%', null, null, null, '设置页面描述，留空表示继承上级频道设置！建议不要超过200个字符！', null, null, '$max3', '1', '1'),
		('$ChannelModelID', 'Tag', 'varchar(255)', 'Tag标签', 'text', '5', '100%', null, 'textinput', null, '多个标签以英文逗号隔开，一般设置2到5个。TAG标签是一种由您自己定义的，比分类更准确、更具体，可以概括文章主要内容的关键词，能使文章更容易被搜索到', null, null, '$max3', '1', '1'),
		
		('$ChannelModelID', 'InfoAlbum',    'text',              '相册',       'album',   '1', '', '', '', '', '', null, null, '$max6', '1', '1'),
		('$ChannelModelID', 'InfoRelation', 'varchar(512)', '相关信息', 'relation', '3', '', '', '', '', '', null, null, '$max6', '1', '1'),
		
		('$ChannelModelID', 'InfoOrder', 'int', '排序', 'text', '16', null, null, 'textinput', '0', '请输入数字，值越小排名越靠前！', null, null, '$max1', '1', '1'),
		('$ChannelModelID', 'InfoTime', 'datetime', '时间', 'datetime', '17', '', null, 'textinput', null, null, null, null, '$max1', '1', '1'),
		('$ChannelModelID', 'InfoAuthor', 'varchar(50)', '作者', 'text', '18', '270px', null, 'textinput', null, null, null, null, '$max2', '0', '0'),
		('$ChannelModelID', 'InfoHit', 'int', '点击次数', 'text', '19', '270px', null, 'textinput', '0', null, null, null, '$max1', '1', '1'),
		('$ChannelModelID', 'InfoFrom', 'varchar(50)', '来源', 'text', '20', null, null, 'textinput', null, null, null, null, '$max2', '0', '0'),
		('$ChannelModelID', 'IsCheck', 'int', '是否审核', 'radio', '20', null, null, null, '1|已审核|1\r\n0|未审核', null, null, null, '$max1', '1', '1'),
		('$ChannelModelID', 'IsEnable', 'int', '是否启用', 'radio', '21', null, null, null, '1|启用|1\r\n0|禁用', null, null, null, '$max1', '1', '1'),
		('$ChannelModelID', 'IsHtml', 'int', '是否启用Html静态缓存', 'radio', '22', null, null, null, '1|启用|1\r\n0|禁用', '启用后，将生成信息Html静态缓存', null, null, '$max1', '1', '1'),
		('$ChannelModelID', 'LabelID', 'varchar(512)', '属性', 'labelcheckbox', '21', null, null, null, null, null, null, null, '$max2', '0', '1'),
		
		('$ChannelModelID', 'f1', 'varchar(255)', '扩展字段1', 'text', '1', '200px', null, 'textinput', null, null, null, null, '$max4', '0', '0'),
		('$ChannelModelID', 'f2', 'varchar(255)', '扩展字段2', 'text', '2', '200px', null, 'textinput', null, null, null, null, '$max4', '0', '0'),
		('$ChannelModelID', 'f3', 'varchar(255)', '扩展字段3', 'text', '3', '200px', null, 'textinput', null, null, null, null, '$max4', '0', '0'),
		('$ChannelModelID', 'f4', 'varchar(255)', '扩展字段4', 'text', '4', '200px', null, 'textinput', null, null, null, null, '$max5', '0', '0'),
		('$ChannelModelID', 'f5', 'varchar(255)', '扩展字段5', 'text', '5', '200px', null, 'textinput', null, null, null, null, '$max5', '0', '0');
		";
	
		$result = $this->execute($sqlField);
		if(!$result) return false;
		return true;
	}

	//删除指定模型所有属性
	function deleteAttribute($ChannelModelID){
		$ChannelModelID = intval($ChannelModelID);
		$m = D('Admin/Attribute');
		$result = $m->where("ChannelModelID=$ChannelModelID")->delete();
		return $result;
	}
	
	//是否能删除频道模型
	function canDelete($ChannelModelID){
		$ChannelModelID = intval($ChannelModelID);
		$m = D('Admin/Channel');
		$n = $m->where("ChannelModelID=$ChannelModelID")->count();
		if( $n > 0 ){
			return false; //表示不能删除
		}else{
			return true; //可以删除
		}
	}
}

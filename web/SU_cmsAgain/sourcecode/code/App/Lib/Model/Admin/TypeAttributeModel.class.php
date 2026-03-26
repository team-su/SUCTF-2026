<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class TypeAttributeModel extends Model{
	function getTypeAttribute($p=array() ){
		$where = array();
		if( isset($p['IsEnable']) && $p['IsEnable'] != -1){
			$where['IsEnable'] = intval($p['IsEnable']);
		}
		if( isset($p['TypeID'])  && $p['TypeID'] != -1){
			$where['TypeID'] = intval($p['TypeID']);
		}
		$result = $this->where($where)->order('TypeAttributeOrder asc, TypeAttributeID desc')->select();
		return $result;
	}
	
	function getAllTypeAttribute($p=array() ){
		$this->field('b.TypeGroupName, a.*');
		$this->table($this->tablePrefix.'type_attribute a');
		$this->join('left join '.$this->tablePrefix.'type_group b On a.TypeGroupID = b.TypeGroupID');
		
		$where = array();
		if( isset($p['IsEnable']) && $p['IsEnable'] != -1){
			$where['a.IsEnable'] = intval($p['IsEnable']);
		}
		if( isset($p['TypeID'])  && $p['TypeID'] != -1){
			$where['a.TypeID'] = intval($p['TypeID']);
		}
		$result = $this->where($where)->order('b.TypeGroupOrder asc,b.TypeGroupID desc,a.TypeAttributeOrder asc, a.TypeAttributeID desc')->select();
		if(!empty($result)){
			$n = is_array($result) ? count($result) : 0;
			$m = D('Admin/TypeAttributeValue');
			for($i=0; $i<$n; $i++){
				$UsedCount = $m->where("TypeAttributeID=".$result[$i]['TypeAttributeID'])->count();
				$result[$i]['UsedCount'] = $UsedCount;
			}
		}
		return $result;
	}
	
	function findTypeAttribute($id, $options = array() ){
		if( !is_numeric($id) ) return false;
		$where['TypeAttributeID'] = $id;
		if( isset($options['IsEnable']) && $options['IsEnable'] != -1 ) {
			$where['IsEnable'] = intval($options['IsEnable']);
		}
		$result = $this->where($where)->find();
		return $result;
	}
	
	//删除类型属性会同时删除其属性值
	function delTypeAttribute($id, $options=array()){
		if( is_array($id) ){
			$n = count($id);
			for ($i=0; $i < $n; $i++){
				if( !is_numeric( $id[$i] ) ){
					unset( $id[$i] );
				}
			}
			if( count($id) <= 0 )  return false;
			$where = "TypeAttributeID in(".implode(',', $id).')';
		}else{
			if( !is_numeric($id) ) return false;
			$where = "TypeAttributeID=$id";
		}
		$result = $this->where($where)->delete();
		if($result){ //如果删除成功，则删除对应的属性值
			$m = D('Admin/TypeAttributeValue');
			$b = $m->where($where)->delete();
		}
		return $result;
	}
	
	//以下三个函数用于typeattributelist标签=========================================
	//获取检索条件属性 添加a.AttributeValueID asc的目录为了后台顺序和前台顺序保持一致
	function getConditionAttribute($channelid=-1, $specialid=-1, $minprice=-1, $maxprice=-1){
		$m = D('Admin/Info');
		$list = $m->getIdlistForConditionAttribute($channelid, $specialid, $minprice, $maxprice);
		$result = false;
		if(!empty($list)){
			$this->field('b.TypeAttributeName,b.TypeAttributeID,a.AttributeValueID,a.AttributeValue, a.AttributePicture');
			$this->table($this->tablePrefix.'type_attribute_value a');
			$this->join($this->tablePrefix.'type_attribute b On a.TypeAttributeID = b.TypeAttributeID');
			$where = "a.InfoID in ($list) and b.IsSearch=1 and b.IsEnable=1";
			$result = $this->where($where)->order('b.TypeAttributeOrder asc,b.TypeAttributeID desc,a.AttributeValueID asc')->select();
		}
		if(empty($result)) return false;
		//转为为关联数组，由于采用属性值作为主表进行左连接，所以没有属性值也不会出现属性名称
		$data = array();
		foreach ($result as $v){
			$key = $v['TypeAttributeID']; //用于去重
			if( !array_key_exists($key, $data)){
				$data[$key]['TypeAttributeID'] = $key;
				$data[$key]['TypeAttributeName'] = $v['TypeAttributeName'];
			}
			$value = $v['AttributeValue']; //属性值去重
			if( !array_key_exists($value, $data[$key]['AttributeValue'])){
				$data[$key]['AttributeValue'][$value] = array(
						'AttributeValueID'=>$v['AttributeValueID'],
						'AttributeValue'=>$value,
						'AttributePicture'=>$v['AttributePicture']
				);
			}
		}
		return $data;
	}
	
	//获取规格属性
	function getSpecAttribute($infoid){
		if(!is_numeric($infoid)) return false;
		$this->field('b.TypeAttributeName,b.TypeAttributeID,b.ValueType,a.AttributeValueID,a.AttributeValue,a.AttributePicture,a.AttributePrice');
		$this->table($this->tablePrefix.'type_attribute_value a');
		$this->join('Inner Join '.$this->tablePrefix.'type_attribute b On a.TypeAttributeID = b.TypeAttributeID');
		$where = "a.InfoID={$infoid} and b.ValueType!=1 and b.IsEnable=1";
		$result = $this->where($where)->order('b.TypeAttributeOrder asc,b.TypeAttributeID desc,a.AttributeValueID asc')->select();
		if(empty($result)) return false;
		
		//转为为关联数组，由于采用属性值作为主表进行左连接，所以没有属性值也不会出现属性名称
		$data = array();
		foreach ($result as $v){
			$key = $v['TypeAttributeID']; //用于去重
			if( !array_key_exists($key, $data)){
				$data[$key]['TypeAttributeID'] = $key;
				$data[$key]['TypeAttributeName'] = $v['TypeAttributeName'];
				$data[$key]['ValueType'] = $v['ValueType'];
			}
			$value = $v['AttributeValue']; //属性值去重
			if( !array_key_exists($value, $data[$key]['AttributeValue'])){
				$data[$key]['AttributeValue'][$value] = array(
						'AttributeValueID'=>$v['AttributeValueID'],
						'AttributeValue'=>$value,
						'AttributePicture'=>$v['AttributePicture'],
						'AttributePrice'=>$v['AttributePrice'],
						'AttributeDiscountPrice'=>$v['AttributePrice']*$GLOBALS['DiscountRate'],
				);
			}
		}
		return $data;
	}
	
	//获取所有类型属性（包含分组）（主要是商城使用）
	function getAllAttribute($infoid){
		if(!is_numeric($infoid)) return false;
		$prefix = $this->tablePrefix;
		$sql = "SELECT c.TypeGroupID, c.TypeGroupName, b.TypeAttributeID, b.TypeAttributeName, group_concat(a.AttributeValue SEPARATOR '，') as AttributeValue
		FROM {$prefix}type_attribute_value a
		Left JOIN {$prefix}type_attribute b On a.TypeAttributeID=b.TypeAttributeID and b.IsEnable=1
		Left JOIN {$prefix}type_group c On b.TypeGroupID=c.TypeGroupID and c.IsEnable=1
		WHERE a.InfoID={$infoid}
		GROUP BY a.TypeAttributeID
		ORDER BY c.TypeGroupOrder ASC, c.TypeGroupID DESC, b.TypeAttributeOrder ASC, b.TypeAttributeID DESC";
		$result = $this->query($sql);
		//转为为关联数组
		$data = array();
		foreach ($result as $v){
			$key = empty($v['TypeGroupID']) ? 0 : $v['TypeGroupID'];
			if( !array_key_exists($key, $data)){
				$data[$key] = array('TypeGroupID' => $v['TypeGroupID'], 'TypeGroupName' => $v['TypeGroupName']);
			}
			$data[$key]['TypeAttributes'][] = array(
				'TypeAttributeID' => $v['TypeAttributeID'],
				'TypeAttributeName'=>$v['TypeAttributeName'],
				'AttributeValue'=>$v['AttributeValue']
			);
		}
		return $data;
	}
	//====================================================================
	
	/**
	 * 判断指定的TypeID是否有价格属性
	 * @param int $InfoID
	 */
	function hasPriceAttribute($TypeID){
		$where['TypeID'] = intval($TypeID);
		$where['ValueType'] = array('in','2,3');
		$TypeAttributeID = $this->where($where)->getField('TypeAttributeID');
		if( empty($TypeAttributeID) ) {
			return 0;
		}else{
			return 1;
		}
	}
}

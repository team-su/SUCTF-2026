<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class TypeAttributeValueModel extends Model{
	function getTypeAttributeValue($infoid, $p=array() ){
		$where['InfoID'] = intval($infoid);
		$result = $this->where($where)->select();
		return $result;
	}
	
	//获取关联数组，键是TypeAttributeID，值为数组
	function getAllTypeAttributeValue($infoid, $typeid){
		/*
		$this->table($this->tablePrefix.'type_attribute_value a');
		$this->join($this->tablePrefix.'type_attribute b On a.TypeAttributeID = b.TypeAttributeID and b.IsEnable=1');
		$where['a.InfoID'] = intval($infoid);
		$where['b.TypeID'] = intval($typeid);
		$result = $this->where($where)->order('b.TypeAttributeOrder asc, b.TypeAttributeID desc, a.AttributeValueID asc')->select();
		return $result;
		*/
		$this->field("a.*,b.InfoID,b.AttributeValueID,b.AttributeValue,b.AttributePicture,b.AttributePrice");
		$this->table($this->tablePrefix.'type_attribute a');
		$this->join($this->tablePrefix.'type_attribute_value b On a.TypeAttributeID = b.TypeAttributeID and b.InfoID='.intval($infoid) );
		$where['a.TypeID'] = intval($typeid);
		$where['a.IsEnable'] = 1;
		$result = $this->where($where)->order('a.TypeAttributeOrder asc, a.TypeAttributeID desc, b.AttributeValueID asc')->select();
		return $result;
	}
	
	function deleteTypeAttributeValueByInfoID($InfoID){
		$InfoID = intval($InfoID);
		$n = $this->where("InfoID={$InfoID}")->delete();
		return $n;
	}
	
	//获取选中属性 $attr:多个id以_隔开
	function getSelectedAttribute($attr='', $specialid=-1, $minprice=-1, $maxprice=-1){
		if(is_numeric($attr)){
			$where = "a.AttributeValueID = {$attr}";
		}else{
			$in_str = str_replace('_', ',', $attr);
            $in_str = YdInput::checkCommaNum($in_str);
			$where = "a.AttributeValueID in ($in_str)";
		}
		$this->field("a.AttributeValue,a.AttributeValueID,a.TypeAttributeID,b.TypeAttributeName");
		$this->table($this->tablePrefix.'type_attribute_value a');
		$this->join($this->tablePrefix.'type_attribute b On a.TypeAttributeID = b.TypeAttributeID');
		$result = $this->where($where)->order('b.TypeAttributeOrder asc, b.TypeAttributeID desc, a.AttributeValueID asc')->select();
		if(!empty($result)){
			$sign = '_';
			$query = '';
			if( isset($specialid) && is_numeric($specialid) && $specialid > 0 ) $query .= "&specialid={$specialid}";
			if( isset($minprice) && is_numeric($minprice) && $minprice >= 0) $query .= "&minprice={$minprice}";
			if( isset($maxprice) && is_numeric($maxprice) && $maxprice >= 0) $query .= "&maxprice={$maxprice}";
			$attr1 = $sign.$attr.$sign;
			$n = is_array($result) ? count($result) : 0;
			for($i=0; $i<$n; $i++){
				$id = $sign.$result[$i]['AttributeValueID'].$sign;
				$attr_str = trim(str_replace($id, $sign, $attr1), $sign);
				$QueryString = !empty($attr_str) ? $query."&attr=$attr_str" : $query;
				$result[$i]['QueryString'] = (!empty($QueryString)) ? '?'.ltrim($QueryString,'&') : '';
			}
		}
		return $result;
	}
	
	//主要用于infolist标签筛选，传入的是AttributeValueID，多个以_隔开，如：30_33_38
	function getInfoIDListByAttributeValueID($id){
		static $_cache = array();
		if (isset($_cache[$id])){
			return $_cache[$id];
		}
		
		if(is_numeric($id)){
			$in_str = $id;
		}else{
			if( !preg_match('/^([0-9]+_?)+$/',$id) ) return false;
			$in_str = str_replace('_', ',', $id);
		}		
		$where = "AttributeValueID in($in_str) ";
		$result = $this->where($where)->field('DISTINCT TypeAttributeID, AttributeValue')->select();
		if( empty($result) ) return false;
		$n = is_array($result) ? count($result) : 0;
		$prefix = $this->tablePrefix;
		$sql = array();
		//必须通过取交集的方式获取满足所有条件的id
		foreach ($result as $v){
			$id = $v['TypeAttributeID'];
			$value = $v['AttributeValue'];
			$sql[] = " select DISTINCT InfoID from {$prefix}type_attribute_value where TypeAttributeID={$id} and AttributeValue='{$value}' ";
		}
		$sql = "SELECT InfoID FROM (".implode('UNION ALL', $sql).") a GROUP BY InfoID having count(InfoID)={$n}";
		$result = $this->query($sql);
		$idlist = '';
		foreach ($result as $v){
			$idlist .= $v['InfoID'].',';
		}
		$idlist = trim($idlist, ',');
		$_cache[$id] = $idlist;
		return $idlist;
	}
	
	//idlist:多个以逗号隔开，如：11,22,33
	function getAttributeByAttributeValueID($idlist){
		$result = array('AttributeString'=>'', 'TotalPrice'=>0, 'Attributes'=>false);
		if(empty($result)) return $result;
		$b = YdInput::checkCommaNum($idlist);
		if(!$b) return $result;
		$this->field("a.AttributeValueID,a.AttributeValue,a.AttributePrice,b.TypeAttributeName,b.TypeAttributeID");
		$this->table($this->tablePrefix.'type_attribute_value a');
		$this->join($this->tablePrefix.'type_attribute b On a.TypeAttributeID = b.TypeAttributeID');
		$where = "a.AttributeValueID in($idlist)";
		$this->order('b.TypeAttributeOrder asc,b.TypeAttributeID desc,a.AttributeValueID asc');
		$result = $this->where($where)->select();
		$AttributeString = array();
		$TotalPrice = 0;
		foreach ($result as $v){
			$AttributeString[] = "{$v['AttributeValueID']}###{$v['AttributeValue']}###{$v['AttributePrice']}###{$v['TypeAttributeName']}";
			$TotalPrice += (double)( $v['AttributePrice'] );
		}
		$data = array('AttributeString'=>implode('@@@', $AttributeString), 'TotalPrice'=>$TotalPrice, 'Attributes'=>$result);
		return $data;
	}
	
	//获取属性总价
	function getAttributePriceByAttributeValueID($idlist){
		$TotalPrice = 0;
		if(empty($idlist)) return $TotalPrice;
		$b = YdInput::checkCommaNum($idlist);
		if(!$b) return $TotalPrice;
		$where = "AttributeValueID in($idlist)";
		$TotalPrice = $this->where($where)->sum('AttributePrice');
		return $TotalPrice;
	}
	
}

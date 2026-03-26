<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */
class AreaModel extends Model{
	function getArea($parent=-1, $IsEnable=-1){
        $where = array();
		if( $parent != -1){
			$where['Parent'] = intval($parent);
		}
		
		if( $IsEnable != -1){
			$where['IsEnable'] = intval($IsEnable);
		}
		
		$result = $this->where($where)->order('AreaOrder asc, AreaID asc')->select();
		return $result;
	}
	
	function getChildCount($AreaID, $p=array()){
		$where['Parent'] = intval($AreaID);
		if( isset($p['IsEnable']) && $p['IsEnable'] != -1 ){
			$where['IsEnable'] = (int)$p['IsEnable'];
		}
		$n = $this->where($where)->count();
		return $n;
	}
	
	//获取关联数组，AreaID=AreaName
	function getAssoArea($parent=-1, $IsEnable=-1){
        $where = array();
		if( $parent != -1){
			$where['Parent'] = intval($parent);
		}
		
		if( $IsEnable != -1){
			$where['IsEnable'] = intval($IsEnable);
		}
		$result = $this->where($where)->getField('AreaID,AreaName');
		return $result;
	}
	
	/**
	 * 获取省市列表
	 */
	function getProvinceAndCity($type=1){
		$where['IsEnable'] = 1;
		$result = $this->where($where)->order('AreaOrder asc, AreaID asc')->select();
		$data = array();
		if(!empty($result)){
			//获取省份
			foreach ($result as $v){
				if($v['Parent']==0){
					$data[$v['AreaID']] = array('AreaID'=>$v['AreaID'], 'AreaName'=>$v['AreaName']);
				}
			}
			//获取城市
			if($type==1){
				foreach($data as $k=>$v){
				    if(!isset($data[$k]['CityList'])) $data[$k]['CityList'] = '';
					foreach ($result as $r){
						if($r['Parent']==$v['AreaID']){
							$data[$k]['CityList'] .= "{$r['AreaID']},{$r['AreaName']}@";
						}
					}
					$data[$k]['CityList'] = rtrim($data[$k]['CityList'], '@');
				}
			}
		}
		unset($result);
		return $data;
	}
	
	function delArea( $id = array(),  $p = array()){
		$id = YdInput::filterCommaNum($id);
		if( is_array($id)){
			$where = 'AreaID in('.implode(',', $id).')';
		}else{
			$where = "AreaID={$id}";
		}
		$result = $this->where($where)->delete();
		return $result;
	}
	
	function saveAll( $data ){
		$n = is_array($data['AreaID']) ? count($data['AreaID']) : 0;
		for($i = 0; $i < $n; $i++){
			if( is_numeric( $data['AreaID'][$i] ) ){
				$value['AreaName'] = $data['AreaName'][$i];
				$value['AreaNameEn'] = $data['AreaNameEn'][$i];
				$value['AreaOrder'] = $data['AreaOrder'][$i];
				$this->where('AreaID='.intval($data['AreaID'][$i]) )->setField( $value );
			}
		}
	}
}

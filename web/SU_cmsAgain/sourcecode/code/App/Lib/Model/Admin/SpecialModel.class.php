<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class SpecialModel extends Model {
	protected $_validate = array(
			array('SpecialName', 'require', '专题名称不能为空!'),
			array('SpecialName', '', '专题名称已经存在!', '0', 'lang_unique'),
			array('SpecialOrder', '/^[-]?\d+$/', '排序必须为数字!', '2','regex'),
	);
	
	protected $_auto = array(
			array('LanguageID', 'get_language_id', 1, 'function'),
	);
	
	function getSpecial($options=array()){
		$where = get_language_where_array();
		if( isset($options['IsEnable']) && $options['IsEnable'] != -1){
			$where['IsEnable'] = intval($options['IsEnable']);
		}

        //有效字符为：^1,2,3
        $idlist = isset($options['idlist']) ? $options['idlist'] : -1;
		if(!empty($idlist) && -1 != $idlist){
            if(!preg_match("/^[ 0-9,\^]+$/i", $idlist)){
                return false;
            }
        }

	    if( isset($idlist) && $idlist != -1 && substr($idlist, 0, 1) != '^'){
	   		$where['SpecialID']  = array('in', $idlist);
	   		$order = "field(SpecialID,$idlist)";
	    }else{
	    	if( substr($idlist, 0, 1) == '^' ){
	    		$where['SpecialID']  = array('not in', substr($idlist, 1) );
	    	}
	    	$order = 'SpecialOrder asc,SpecialID desc';
	    	//其他的条件写在这里
	    	if( isset($options['ChannelID']) && $options['ChannelID'] != -1 ){
	    		$list = $this->getSpecailID($options['ChannelID']);
	    		if( empty($list) ) return false;
	    		$where['SpecialID']  = array('in', implode(',', $list));
	    	}
	    }
	    $result = $this->where($where)->order($order)->select();
	    
	    //计算专题的信息数量
	    if( isset($options['SpecialCount']) && $options['SpecialCount'] == 1 ){
	    	$n = is_array($result) ? count($result) : 0;
	    	$m = D('Admin/Info');
	    	for($i=0; $i<$n;$i++){
	    		$result[$i]['SpecialCount'] = $m->specialCount( $result[$i]['SpecialID'] );
	    	}
	    }
	    return $result;
	}
	
	//通过频道ID,获取所有的专题ID
	function getSpecailID($ChannelID){
        $ChannelID = intval($ChannelID);
		$m = D('Admin/Channel');
		$all = $m->getChildChannel( $ChannelID );
		if( empty($all) ) $all[] = $ChannelID;
		$where['IsEnable'] = 1;
		$where['IsCheck'] = 1;
		$where['SpecialID'] = array('neq','NULL');
		$where['ChannelID']  = array('in', implode(',', $all) );
		$mi = D('Admin/Info');
		$result = $mi->where($where)->distinct(true)->field('SpecialID')->select();
		if( empty($result) ) return false;
		$list = '';
		foreach ($result as $k){
			$list .= $k['SpecialID'].',';
		}
		$list = rtrim($list,',');
		$list = explode(',', $list);
		$list = array_unique($list); //去重
		return $list;
	}
	
	function findSpecial($id, $options = array() ){
		if( !is_numeric($id) ) return false;
		$where = get_language_where_array();
		$where['SpecialID'] = $id;
		$result = $this->where($where)->find();
		return $result;
	}
	
	//如果专题正在被使用，则不能删除
	function delSpecial($id, $options = array() ){
		$m = D('Admin/Info');
		if( is_array($id) ){
			$n = count($id);
			for ($i=0; $i < $n; $i++){
				if( !is_numeric($id[$i]) || $m->specialCount( $id[$i] ) > 0 ){
					unset( $id[$i] );
				}
			}
			if( count($id) <= 0 )  return false;
			$where = "SpecialID in(".implode(',', $id).')';
		}else{
			if( $m->specialCount( $id ) > 0 ) return false;
			$where = "SpecialID=".intval($id);
		}
		$result = $this->where($where)->delete();
		return $result;
	}
	
	//保存全部数据
	function saveAll( $data ){
		$n = is_array($data['SpecialID']) ? count($data['SpecialID']) : 0;
		for($i = 0; $i < $n; $i++){
			if( is_numeric( $data['SpecialID'][$i] ) ){
				$value['SpecialName'] = $data['SpecialName'][$i];
				$value['SpecialOrder'] = $data['SpecialOrder'][$i];
				$this->where('SpecialID='.$data['SpecialID'][$i])->setField( $value );
			}
		}
	}
}

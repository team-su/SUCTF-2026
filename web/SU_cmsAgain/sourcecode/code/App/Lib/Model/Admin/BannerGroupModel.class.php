<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class BannerGroupModel extends Model{
	protected $_validate = array(
			array('BannerGroupName', 'require', '幻灯分组名称不能为空!'),
			array('BannerGroupName', '', '幻灯分组名称已经存在!', '0', 'lang_unique'),
	);
	
	protected $_auto = array(
			array('LanguageID', 'get_language_id', 1, 'function'),
	);
	
	function getBannerGroup($p=false){
		$where = get_language_where();
		$result = $this->where($where)->order('BannerGroupOrder asc,BannerGroupID desc')->select();
		if( isset($p['Count']) && $p['Count'] == 1 && !empty($result)){ //统计数量
			$m = D('Admin/Banner');
			$n = is_array($result) ? count($result) : 0;
			for($i = 0; $i < $n; $i++){
				$result[$i]['BannerCount'] = $m->where("BannerGroupID={$result[$i]['BannerGroupID']}")->count();
			}
		}
		return $result;
	}
	
	function batchDelBannerGroup( $id = array() ){
		$id = YdInput::filterCommaNum($id);
		$where['BannerGroupID']  = array('in', implode(',', $id));
		$where['IsSystem']  = 0;
		$result = $this->where($where)->delete();
		return $result;
	}
	
	//删除幻灯分组
	function delBannerGroup($id){
		$id = intval($id);
		$where = "BannerGroupID={$id} and IsSystem=0";
		$result = $this->where($where)->delete();
		return $result;
	}
	
	//幻灯分组是否包含数据
	function hasData($id){
		$id = intval($id);
		$m = D('Admin/Banner');
		$c = $m->where("BannerGroupID={$id}")->count();
		if( $c > 0){
			return true;
		}else{
			return false;
		}
	}
	
	//批量排序
	function batchSort($BannerGroupID=array(), $BannerGroupOrder = array() ){
		$n = is_array($BannerGroupID) ? count($BannerGroupID) : 0;
		for($i = 0; $i < $n; $i++){
			if( is_numeric($BannerGroupOrder[$i]) ){
				$id = intval($BannerGroupID[$i]);
				$this->where("BannerGroupID=".$id)->setField('BannerGroupOrder', $BannerGroupOrder[$i]);
			}
		}
	}
}

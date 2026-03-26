<?php
class OauthModel extends Model{
	function getOauth($options=array()){
		$where = array();
		if( isset($options['IsEnable']) && $options['IsEnable'] != -1){
			$where['IsEnable'] = intval($options['IsEnable']);
		}
		if( isset($options['RemoveEmpty']) && $options['RemoveEmpty'] != -1){
			$where['OauthAppID'] = array('neq', '');
			$where['OauthAppKey'] = array('neq', '');
			$where['OauthMark'] = array('neq', '');
		}
		$result = $this->where($where)->order('OauthOrder asc, OauthID desc')->select();
		return $result;
	}
	
	function findOauthByMark($mark){
		$where['OauthMark'] = $mark;
		$where['IsEnable'] = 1;
		$result = $this->where($where)->find();
		return $result;
	}
}

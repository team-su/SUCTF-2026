<?php
//收货人信息
class ConsigneeModel extends Model{
	protected $_auto = array(
			array('LanguageID', 'get_language_id', 1, 'function'),
	);
	
	function getConsignee( $p=array() ){
		$where = get_language_where_array();
		if( isset($p['MemberID']) && $p['MemberID'] != -1){
			$where['MemberID'] = intval($p['MemberID']);
		}
		$result = $this->where($where)->order('ConsigneeID desc')->select();
		return $result;
	}
	
	function getConsigneeCount($p=array()){
		$where = get_language_where_array();
		if( isset($p['MemberID']) && $p['MemberID'] != -1){
			$where['MemberID'] = intval($p['MemberID']);
		}
		$n= $this->where($where)->count();
		return $n;
	}
	
	
	function findConsignee($id, $p = array() ){
		if( !is_numeric($id) ) return false;
		$where = get_language_where_array();
		$where['ConsigneeID'] = intval($id);
		if( isset($p['MemberID']) && $p['MemberID'] != -1){
			$where['MemberID'] = intval($p['MemberID']);
		}
		$result = $this->where($where)->find();
		return $result;
	}
	
	function delConsignee( $id = array(),  $p = array()){
		$id = YdInput::filterCommaNum($id);
		if( is_array($id)){
			$where = 'ConsigneeID in('.implode(',', $id).')';
		}else{
			$where = "ConsigneeID={$id}";
		}
		
		if( isset($p['MemberID']) && $p['MemberID'] > 0){
			$where .=' and MemberID='.intval($p['MemberID']);
		}
		$result = $this->where($where)->delete();
		return $result;
	}
	
	function setDefaultConsignee( $id,  $p = array()){
		if( isset($p['MemberID']) && $p['MemberID'] != -1){
			$where['MemberID'] = intval($p['MemberID']);
		}
		//先取消默认
		$where['IsDefault'] = 1;
		$result = $this->where($where)->setField('IsDefault', 0);
		unset($where['IsDefault']);
		
		$where['ConsigneeID'] = intval($id);
		$result = $this->where($where)->setField('IsDefault', 1);
		return $result;
	}
	
	function getDefaultConsigneeID($MemberID){
		$where['IsDefault'] = 1;
		$where['MemberID'] = intval($MemberID);
		$id = $this->where($where)->getField('ConsigneeID');
		return $id;
	}
	
	/**
	 * 获取默认收货人
	 * @param int $MemberID
	 * @return array
	 */
	function getDefaultConsignee($MemberID){
		$where['IsDefault'] = 1;
		$where['IsEnable'] = 1;
		$where['MemberID'] = intval($MemberID);
		$data = $this->where($where)->find();
		return $data;
	}
}
	
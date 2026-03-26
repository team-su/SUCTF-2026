<?php
//分销商级别
class DistributorLevelModel extends Model{
	function getDistributorLevel(){
		$result = $this->order('CommissionThreshold asc')->select();
		if(!empty($result)){
			$m = D('Admin/Member');
			$n = is_array($result) ? count($result) : 0;
			for($i=0; $i<$n; $i++){
				$p['DistributorLevelID'] = $result[$i]['DistributorLevelID'];
				$result[$i]['DistributorCount'] = $m->getDistributorCount($p);
			}
		}
		return $result;
	}
	
	function findDistributorLevel($id, $options = array() ){
		if( !is_numeric($id) ) return false;
		$where = get_language_where_array();
		$where['DistributorLevelID'] = $id;
		$result = $this->where($where)->find();
		return $result;
	}
	
	function delDistributorLevel( $id = array(),  $p = array()){
		if( is_array($id) ){
			$n = count($id);
			for ($i=0; $i < $n; $i++){
				if( !is_numeric( $id[$i] ) ){
					unset( $id[$i] );
				}
			}
			if( count($id) <= 0 )  return false;
			$where = "DistributorLevelID in(".implode(',', $id).')';
		}else{
			if( !is_numeric($id) ) return false;
			$where = "DistributorLevelID=$id";
		}
		$result = $this->where($where)->delete();
		return $result;
	}
	
	/**
	 * 升级会员等级
	 * @param $MemberID
	 * @param $TotalCommission
	 */
	function upgradeDistributorLevel($MemberID=array()){
		if(empty($MemberID)) return;
		$list = $this->field('DistributorLevelID,CommissionThreshold')->order('CommissionThreshold desc')->select();
		$m = D('Admin/Member');
		$mc = D('Admin/Cash');
		foreach ($MemberID as $id){
		    $id = intval($id);
			$TotalCommission = $mc->getTotalCommission($id); //获取当前会员总佣金
			if($TotalCommission <= 0) continue;
			$data = $m->where("MemberID={$id}")->field('DistributorLevelID,IsDistributor')->find();
			if($data['IsDistributor'] == 0) continue; //不是分销商直接跳过
			$OldID = $data['DistributorLevelID'];
			$NewID = $data['DistributorLevelID'];
			foreach ($list as $v){
				$CurrentID = $v['DistributorLevelID'];
				if($OldID==$CurrentID) break; //防止降级
				if( $TotalCommission >= $v['CommissionThreshold']){
					$NewID = $CurrentID;
					break;
				}
			}
			//如果不相等，则确定升级
			if($OldID != $NewID){
				$result = $m->where("MemberID=$id")->setField('DistributorLevelID', $NewID);
			}
		}
	}
	
	/**
	 * 返回指定级别分销商，不同下线的佣金
	 * @param int $DistributorLevelID
	 * @param int $Level 下线等级 1表示一级下线、 2表示二级下线、 3表示三级下线、
	 */
	function getCommissionRate($DistributorLevelID, $Level=1){
        $Level = intval($Level);
		$where['DistributorLevelID'] = intval($DistributorLevelID);
		$rate = $this->where($where)->getField('CommissionRate'.$Level);
		if(empty($rate)) $rate = 0;
		$rate = (double)$rate/100.0;
		return $rate;
	}
	
	/**
	 * 获取最低等级的分销商ID，主要用于自动成为分销商
	 */
	function getLowestDistributorLevelID(){
		$DistributorLevelID = $this->order('CommissionThreshold asc')->getField('DistributorLevelID');
		return $DistributorLevelID;
	}
}

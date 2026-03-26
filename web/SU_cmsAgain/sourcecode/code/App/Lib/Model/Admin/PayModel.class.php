<?php
class PayModel extends Model{
	protected $_auto = array(
			array('LanguageID', 'get_language_id', 1, 'function'),
	);
	
	function getPay($options=array()){
		$where = get_language_where_array('a');
		if( isset($options['IsEnable']) ){
			$where['a.IsEnable'] = intval($options['IsEnable']);
		}
		
		//1:都可以，2：电脑，3：手机
		if( isset($options['SiteType']) && $options['SiteType'] != 1 ){
            $options['SiteType'] = intval($options['SiteType']);
			$where['a.SiteType'] = array('in', $options['SiteType'].',1');
			//$where['a.SiteType']  =  array(array('eq',$options['SiteType']),array('eq',1), 'or');
		}
		
		if( isset($options['IsOnline']) && $options['IsOnline'] != -1 ){
			$where['b.IsOnline'] = intval($options['IsOnline']);
		}
		
		$this->field('b.PayTypeName, a.*');
		$this->table($this->tablePrefix.'pay a');
		$this->join($this->tablePrefix.'pay_type b On a.PayTypeID = b.PayTypeID');
		
		$result = $this->where($where)->order('a.PayOrder asc, a.PayID desc')->select();
		if(!empty($result) && session('?MemberID')){
			$MemberID = session('MemberID');
			$m = D('Admin/Cash');
			$AvailableBalance = $m->getAvailableQuantity($MemberID);
			$n = is_array($result) ? count($result) : 0;
			for($i=0; $i<$n; $i++){
				if( $result[$i]['PayTypeID'] == 7 ){
					$result[$i]['AvailableBalance'] = $AvailableBalance;
				}
			}
		}
		return $result;
	}
	
	function findPay($id, $options = array() ){
		if( !is_numeric($id) ) return false;
		$where = get_language_where_array();
		$where['PayID'] = intval($id);
		if( isset($options['IsEnable']) ) {
			$where['IsEnable'] = intval($options['IsEnable']);
		}
		$result = $this->where($where)->find();
		return $result;
	}
	
	function findPayAll($id=0){
		$where = get_language_where_array('a');
		$where['a.IsEnable'] = 1;
		$this->field('b.PayTypeName, a.*');
		$this->table($this->tablePrefix.'pay a');
		$this->join($this->tablePrefix.'pay_type b On a.PayTypeID = b.PayTypeID');
		$result = $this->where($where)->find();
		return $result;
	}
	
	//获取第一个PayID作为默认ID值
	function getFirstPayID(){
		$where = get_language_where();
		$where .= " and IsEnable=1";
		$this->order('PayOrder asc, PayID desc');
		$id = $this->where($where)->limit(1)->getField('PayID');
		return $id;
	}
	
	//获取支付比例
	function getPayRate($payID){
		$payID = intval($payID);
		$result = $this->where("PayID=$payID")->getField('PayRate');
		return $result;
	}

}

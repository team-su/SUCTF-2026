<?php
class AdGroupModel extends Model{
	protected $_validate = array(
			array('AdGroupName', 'require', '名称不能为空!'),
			array('AdGroupName', '', '名称已经存在!', '0', 'unique'),
	);
	
	protected $_auto = array(
			array('LanguageID', 'get_language_id', 1, 'function'),
	);
	
	/**
	 * 
	 * @param int $IsEnable
	 * @param array $options AdCount:表示统计广告数
	 * @return unknown
	 */
	function getAdGroup($options = array()){
		$where = get_language_where_array();
		if( isset($options['IsEnable']) ){
			$where['IsEnable'] = intval($options['IsEnable']);
		}
		$result = $this->where($where)->order('AdGroupID desc')->select();
		if( isset($options['AdCount']) && $options['AdCount'] && !empty($result) ){
			$m = D('Admin/Ad');
			$n = is_array($result) ? count($result) : 0;
			for($i = 0; $i < $n; $i++){
				$result[$i]['AdCount'] = $m->getAdCount( array('AdGroupID'=>$result[$i]['AdGroupID']) );
			}
		}
		return $result;
	}
	
	function findAdGroup($id, $options = array() ){
		if( !is_numeric($id) ) return false;
		$where = get_language_where_array();
		$where['AdGroupID'] = intval($id);
		$result = $this->where($where)->find();
		return $result;
	}
	
	//安全删除，删除前判断是否有关联的广告
	function delAdGroup($id, $options = array() ){
		if( !is_array($id) ) $id = (array)$id;
		$m = D('Admin/Ad');
		foreach($id as $k=>$v){
			$v = intval($v);
			if( $m->getAdCount(  array('AdGroupID'=>$v)  ) > 0){
				unset( $id[$k] );
			}
		}
		$this->baseDel($id);
	}
}


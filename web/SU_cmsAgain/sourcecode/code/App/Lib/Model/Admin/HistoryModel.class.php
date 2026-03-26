<?php
//我浏览器记录
class HistoryModel extends Model{
	function getHistory($offset = -1, $length = -1, $p=array()){
		$where = get_language_where_array('b');
		if( isset($p['MemberID']) && $p['MemberID'] != -1){
			$where['a.MemberID'] = intval($p['MemberID']);
		}
		$this->field('b.InfoTitle,b.InfoPrice,b.Html,b.LinkUrl,b.ChannelID,b.InfoPicture,b.InfoTime,c.ChannelName,c.ChannelModelID,a.*');
		$this->table($this->tablePrefix.'history a');
		$this->join('Inner Join '.$this->tablePrefix.'info b On a.InfoID = b.InfoID');
		$this->join('Inner Join '.$this->tablePrefix.'channel c On b.ChannelID = c.ChannelID');
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}
		$result = $this->where($where)->order('a.HistoryTime desc')->select();
		if(!empty($result)){
			$n = is_array($result) ? count($result) : 0;
			for($i=0; $i<$n; $i++){
				$result[$i]['DiscountPrice'] = DiscountPrice($result[$i]['InfoID'], $result[$i]['InfoPrice']);
				$result[$i]['InfoPrice'] = yd_to_money($result[$i]['InfoPrice']);
				$result[$i]['InfoUrl'] = InfoUrl($result[$i]['InfoID'], $result[$i]['Html'], $result[$i]['LinkUrl'],  false, $result[$i]['ChannelID']);
			}
		}
		return $result;
	}
	
	function getHistoryCount($p=array()){
		$where = get_language_where_array('b');
		if( isset($p['MemberID']) && $p['MemberID'] != -1){
			$where['a.MemberID'] = intval($p['MemberID']);
		}
		$this->table($this->tablePrefix.'history a');
		$this->join('Inner Join '.$this->tablePrefix.'info b On a.InfoID = b.InfoID');
		$n= $this->where($where)->count();
		return $n;
	}
	
	//id为infolist数组
	function delHistory( $id = array(),  $p = array()){
		$id = YdInput::filterCommaNum($id);
		if( is_array($id)){
			$where = 'InfoID in('.implode(',', $id).')';
		}else{
			$where = "InfoID={$id}";
		}
		if( isset($p['MemberID']) && $p['MemberID'] > 0){
			$where .=' and MemberID='.$p['MemberID'];
		}
		$result = $this->where($where)->delete();
		return $result;
	}

	/**
	 * 添加阅读历史，不重复添加
	 * @param int $infoid
	 * @param int $memberid
	 */
	function addHistory($InfoID, $MemberID){
		if( !empty($MemberID) ){
			$where['InfoID'] = intval($InfoID);
			$where['MemberID'] = intval($MemberID);
			//先判断用户是否已经收藏当前文章
			$n = $this->where($where)->count();
			if( $n <= 0 ){
				$data['InfoID'] = intval($InfoID);
				$data['MemberID'] = intval($MemberID);
				$data['HistoryTime'] = date('Y-m-d H:i:s');
				$result = $this->add($data);
			}else{ //如果已经存在，则更新时间
				$result = $this->where($where)->setField('HistoryTime', date('Y-m-d H:i:s'));
			}
		}
		return true;
	}
}

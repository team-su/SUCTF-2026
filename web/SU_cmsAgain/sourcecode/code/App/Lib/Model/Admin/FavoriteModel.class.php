<?php
//我的收藏
class FavoriteModel extends Model{
	function getFavorite($offset = -1, $length = -1, $p=array()){
		$where = get_language_where_array('b');
		if( isset($p['MemberID']) && $p['MemberID'] != -1){
			$where['a.MemberID'] = intval($p['MemberID']);
		}
		$this->field('b.InfoTitle,b.InfoPrice,b.Html,b.LinkUrl,b.ChannelID,b.InfoPicture,b.InfoTime,c.ChannelName,c.ChannelModelID,a.*');
		$this->table($this->tablePrefix.'favorite a');
		$this->join('Inner Join '.$this->tablePrefix.'info b On a.InfoID = b.InfoID');
		$this->join('Inner Join '.$this->tablePrefix.'channel c On b.ChannelID = c.ChannelID');
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}
		$result = $this->where($where)->order('a.FavoriteID desc')->select();
		if(!empty($result)){
			$n = is_array($result) ? count($result) : 0;
			for($i=0; $i<$n; $i++){
				$result[$i]['DiscountPrice'] = DiscountPrice($result[$i]['InfoID'], $result[$i]['InfoPrice']);
				$result[$i]['InfoUrl'] = InfoUrl($result[$i]['InfoID'], $result[$i]['Html'], $result[$i]['LinkUrl'],  false, $result[$i]['ChannelID']);
			}
		}
		return $result;
	}
	
	function getFavoriteCount($p=array()){
		$where = get_language_where_array('b');
		if( isset($p['MemberID']) && $p['MemberID'] != -1){
			$where['a.MemberID'] = intval($p['MemberID']);
		}
		$this->table($this->tablePrefix.'favorite a');
		$this->join('Inner Join '.$this->tablePrefix.'info b On a.InfoID = b.InfoID');
		$n= $this->where($where)->count();
		return $n;
	}
	
	/**
	 * 是否被加入收藏
	 * @param unknown_type $InfoID
	 * @param unknown_type $MemberID
	 */
	function isAdd($InfoID, $MemberID){
		$where['InfoID'] = intval($InfoID);
		$where['MemberID'] = intval($MemberID);
		$n = $this->where($where)->count();
		if( $n > 0 ){
			return true;
		}else{
			return false;
		}
	}
	
	function delFavorite( $id = array(),  $p = array()){
		$id = YdInput::filterCommaNum($id);
		if( is_array($id)){
			$where = 'FavoriteID in('.implode(',', $id).')';
		}else{
			$where = "FavoriteID={$id}";
		}
		if( isset($p['MemberID']) && $p['MemberID'] > 0){
            $p['MemberID'] = intval($p['MemberID']);
			$where .=' and MemberID='.$p['MemberID'];
		}
		$result = $this->where($where)->delete();
		return $result;
	}
	
	function delFavoriteByInfoID( $id = array(),  $p = array()){
		$id = YdInput::filterCommaNum($id);
		if( is_array($id)){
			$where = 'InfoID in('.implode(',', $id).')';
		}else{
			$where = "InfoID={$id}";
		}
		if( isset($p['MemberID']) && $p['MemberID'] > 0){
            $p['MemberID'] = intval($p['MemberID']);
			$where .=' and MemberID='.$p['MemberID'];
		}
		$result = $this->where($where)->delete();
		return $result;
	}
}

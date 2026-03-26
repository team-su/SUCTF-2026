<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */
class InfoModel extends Model {
	protected $_validate = array(
			array('InfoTitle', 'require', '标题不能为空!'),
			//array('InfoTitle', '', '标题已经存在!', '0', 'lang_unique'),
			array('Html', '', '静态文件名称已经存在!', '2', 'unique'),
			array('Html', '/[a-zA-Z_-]+/', '静态文件名称须包含非数字字符!', '2','regex'),
			array('InfoOrder', '/^[-]?\d+$/', '排序必须为数字!', '2','regex'),
			//array('ChannelID', 'channel_allow', '不能将数据放到单页或链接频道!', '0', 'function'),
	);
	
	protected $_auto = array(
			array('LanguageID', 'get_language_id', 1, 'function'),
	);

	function getInfo($FirstRow, $ListRow, $ChannelID = 0, $IsContainChild = 1, $IsEnable = -1, $LabelID = '', $Keywords='', $MemberID = -1, $SpecialID = 0, $IsCheck=-1, $orderby=false, $groupid=-1, $options=false){	
		if( isset($options['LanguageID']) &&  $options['LanguageID'] != -1){
			$where = 'a.LanguageID='.$options['LanguageID'];
		}else{
			$where = get_language_where('a');
		}
		$where .= ( $IsEnable == - 1) ? '' : " and a.IsEnable=".intval($IsEnable);
		$where .= ( $IsCheck == - 1) ? '' : " and a.IsCheck=".intval($IsCheck);
        if(isset($options['Field'])) {  //过滤字段，防止非法注入
            $options['Field'] = YdInput::checkTableField($options['Field']);
        }
		if(!empty($Keywords) ){
			$Keywords = addslashes(stripslashes($Keywords));
            $Keywords = YdInput::checkKeyword( $Keywords );
			$where .= " and (a.InfoTitle like '%{$Keywords}%' or a.Tag like '%{$Keywords}%')";
		}
		$where .= ( $MemberID !=  -1) ? " and a.MemberID=".intval($MemberID) : '';
		
		if( $ChannelID != 0 ){
			if( strpos($ChannelID, ',') !== false ){
				$list = explode(',', $ChannelID); //直接在属性值中写多个id，如20,30,30，此时$IsContainChild无效
				foreach ($list as $k=>$v){ //强制转化为数字
					$list[$k] = intval($v);  
				}
			}else{
                $ChannelID = intval($ChannelID);
				if( $IsContainChild ){ //如果包含子栏目
					$mc = D('Admin/Channel');
					$list = $mc->getChildChannel($ChannelID, $groupid);
					$list[] = $ChannelID;
				}else{
					$list[] = $ChannelID;
				}
			}
			$where .= ' and (a.ChannelID in('.implode(',', $list).')';
			foreach ($list as $v){
				$where .= " or FIND_IN_SET('$v', a.ChannelIDEx) ";
			}
			$where .= ") ";
		}
		
		if($LabelID != ''){
            $LabelID = ChannelLabelID($LabelID, $ChannelID);
			$list = explode(',', $LabelID);
			foreach ($list as $k=>$v){
				if( is_numeric($v)){
					$where .= " and FIND_IN_SET('$v', a.LabelID) ";
				}
			}
		}
		
		if( $SpecialID != 0 ){
			$SpecialID = intval($SpecialID);
			$where .= " and FIND_IN_SET('$SpecialID', a.SpecialID) ";
		}
		
		//实现定时发布文章
		if( isset($options['Time']) &&  $options['Time'] == 1){
			$where .= ' and NOW() >= a.InfoTime';
		}

		if( isset($options['MinPrice']) && is_numeric( $options['MinPrice']) &&  $options['MinPrice'] > -1){
			$where .= ' and a.InfoPrice >= '.$options['MinPrice']/$GLOBALS['DiscountRate'];
		}
		if( isset($options['MaxPrice']) && is_numeric( $options['MaxPrice']) && $options['MaxPrice'] > -1){
			$where .= ' and a.InfoPrice <= '.$options['MaxPrice']/$GLOBALS['DiscountRate'];
		}
		
		//省-市-区县-镇
		if( isset($options['ProvinceID']) &&  $options['ProvinceID'] != -1){
			$where .= ' and a.ProvinceID = '.intval($options['ProvinceID']);
		}
		if( isset($options['CityID']) &&  $options['CityID'] != -1){
			$where .= ' and a.CityID = '.intval($options['CityID']);
		}
		if( isset($options['DistrictID']) &&  $options['DistrictID'] != -1){
			$where .= ' and a.DistrictID = '.intval($options['DistrictID']);
		}
		if( isset($options['TownID']) &&  $options['TownID'] != -1){
			$where .= ' and a.TownID = '.intval($options['TownID']);
		}
		
		//按属性筛选，多个属性以_隔开
		if( isset($options['Attr']) &&  $options['Attr'] != ''){
			$mt = D('Admin/TypeAttributeValue');
			$idlist = $mt->getInfoIDListByAttributeValueID( $options['Attr'] );
			if(!empty($idlist)){
				$where .= " and a.InfoID in ($idlist)";
			}else{
				return false;
			}
		}
		$HasMemberTable = true; //默认关联Member表
		$HasChannelTable = true; //默认关联Channel表
		if( !empty($options['Field']) ){
			//$Field = str_replace(' ', '', $options['Field']);  //存在InfoTitle AS title，AS两边会有空格
			$Field = explode(',', $options['Field']);
			//info表字段
			$Field = array_merge($Field, array('InfoID','ChannelID','InfoTitle','InfoPrice','InfoTime','Html','LinkUrl','InfoPicture','InfoAttachment','InfoHit') );
			$Field = array_unique($Field);
			foreach($Field as $k=>$v){
				//排除channel表字段
				if( 0 != strcasecmp($v, 'ChannelName') && 0 != strcasecmp($v, 'ChannelModelID') && 0 != strcasecmp($v, 'ChannelUrl') ){
					$Field[$k] = "a.{$v}";
				}else{
					unset($Field[$k]);
				}
			}
			$Field = implode(',', $Field);
			
			//channel表字段，只要出现以下3个字段，则以下固定字段
			//在infolist标签中，只能获取channel表的ChannelName、ChannelModelID、ChannelUrl字段
			if( false === stripos($options['Field'], 'ChannelName') && false === stripos($options['Field'], 'ChannelModelID')
				&& false === stripos($options['Field'], 'ChannelUrl') ){
				$HasChannelTable = false;
			}else{
				$Field .= ',b.ChannelName,b.ChannelModelID,b.Html as ChannelHtml,b.LinkUrl as ChannelLinkUrl';
			}
			
			//member表字段
			if( false === stripos($Field, 'MemberName') ){
				$HasMemberTable = false;
			}else{
				$Field .= ',c.MemberName';
			}
            $Field = YdInput::checkTableField($Field);
			$this->field($Field);
		}else{
			if( isset($options['Flag']) &&  $options['Flag'] == 1){
				$HasMemberTable = false;
				$this->field('b.ChannelName,b.ChannelModelID,b.Html as ChannelHtml,b.LinkUrl as ChannelLinkUrl,a.*');
			}else{
				$this->field('b.ChannelName,b.ChannelModelID,b.Html as ChannelHtml,b.LinkUrl as ChannelLinkUrl,c.MemberName,a.*');
			}
		}
		
		$this->table($this->tablePrefix.'info a');
		if( $HasChannelTable ){
			$this->join('Inner join '.$this->tablePrefix.'channel b On a.ChannelID = b.ChannelID');
		}
		if( $HasMemberTable ){
			$this->join('Left join '.$this->tablePrefix.'member c On a.MemberID = c.MemberID');
		}
        $where .= get_site_where();
        $this->where($where);
		if(empty($orderby) || $orderby==1){ //如果为空，表示按后台指定顺序排序
			$this->order('a.InfoOrder asc, a.InfoTime desc');
		}else{
			if ($orderby==2){ //按销量降序（只有降序）
				$this->order('a.SalesCount desc, a.InfoTime desc');
			}elseif($orderby==3){ //按价格降序
				$this->order('a.InfoPrice desc, a.InfoTime desc');
			}elseif($orderby==4){ //按价格升序
				$this->order('a.InfoPrice asc, a.InfoTime desc');
			}elseif($orderby==5){ //按上架时间降序（只有降序）
				$this->order('a.InfoTime desc');
			}elseif($orderby==6){ //按价积分降序
				$this->order('a.ExchangePoint desc, a.InfoTime desc');
			}elseif($orderby==7){ //按积分升序
				$this->order('a.ExchangePoint asc, a.InfoTime desc');
			}elseif($orderby==99){ //随机排序
                $this->order('rand()');
            }else{ //自定义字符串的形式：如:a.InfoPicture desc
			    $orderby = YdInput::checkOrderField($orderby);
				$this->order($orderby);
			}
		}
		//分页
		if(is_numeric($FirstRow) && is_numeric($ListRow) && $FirstRow >= 0 && $ListRow > 0){
			$this->limit($FirstRow.','.$ListRow);
		}
		$result = $this->select();
		return $result;
	}

	
	function getCount($ChannelID = 0, $IsContainChild = 1, $IsEnable = -1, $Keywords='', $MemberID = -1, $SpecialID = 0, $LabelID = '', $IsCheck=-1, $groupid=-1, $options=false){
	    $key = $ChannelID.$IsContainChild.$IsEnable.$Keywords.$MemberID.$SpecialID.$LabelID.$IsCheck.$groupid;
        if(isset($options['Time'])) $key .= $options['Time'];
        if(isset($options['MinPrice'])) $key .= $options['MinPrice'];
        if(isset($options['MaxPrice'])) $key .= $options['MaxPrice'];
        if(isset($options['Attr'])) $key .= $options['Attr'];

		if(isset($options['LanguageID'])) $key .= $options['LanguageID'];
        if(isset($options['Field'])) $key .= $options['Field'];

		$key = md5($key);
		static $_cache = array();
		if (isset($_cache[$key])){
			return $_cache[$key];
		}
		if( isset($options['LanguageID']) &&  $options['LanguageID'] != -1){
			$where = 'LanguageID='.$options['LanguageID'];
		}else{
			$where = get_language_where();
		}
		$where .= ( $IsEnable == - 1) ? '' : " and IsEnable=".intval($IsEnable);
		$where .= ( $IsCheck == - 1) ? '' : " and IsCheck=".intval($IsCheck);
		if(!empty($Keywords) ){
			$Keywords = addslashes(stripslashes($Keywords));
            $Keywords = YdInput::checkKeyword($Keywords);
			$where .= " and (InfoTitle like '%{$Keywords}%' or Tag like '%{$Keywords}%')";
		}
		$where .= ( $MemberID !=  -1) ? " and MemberID=".intval($MemberID) : '';

		if( $ChannelID != 0 ){
			if( strpos($ChannelID, ',') !== false ){
				$list = explode(',', $ChannelID); //直接在属性值中写多个id，如20,30,30，此时$IsContainChild无效
				foreach ($list as $k=>$v){ //强制转化为数字
					$list[$k] = intval($v);
				}
			}else{
                $ChannelID = intval($ChannelID);
				if( $IsContainChild ){ //如果包含子栏目
					$mc = D('Admin/Channel');
					$list = $mc->getChildChannel($ChannelID, $groupid);
					$list[] = intval($ChannelID);
				}else{
					$list[] = intval($ChannelID);
				}
			}
			$where .= ' and (ChannelID in('.implode(',', $list).')';
			foreach ($list as $v){
				$where .= " or FIND_IN_SET('$v', ChannelIDEx) ";
			}
			$where .= ") ";
		}
		
		if($LabelID != ''){
		    $LabelID = ChannelLabelID($LabelID, $ChannelID);
			$list = explode(',', $LabelID);
			foreach ($list as $k=>$v){
				if( is_numeric($v)){
					$where .= " and FIND_IN_SET('$v', LabelID) ";
				}
			}
		}
	
		if( $SpecialID != 0 ){
			$SpecialID = intval($SpecialID);
			$where .= " and FIND_IN_SET('$SpecialID', SpecialID) ";
		}
		
		//实现定时发布文章
		if( isset($options['Time']) &&  $options['Time'] == 1){
			$where .= ' and NOW() >= InfoTime';
		}
	
		if( isset($options['MinPrice']) && is_numeric($options['MinPrice']) && $options['MinPrice'] > -1){
			$where .= ' and InfoPrice >= '.$options['MinPrice']/$GLOBALS['DiscountRate'];
		}
		if( isset($options['MaxPrice']) && is_numeric($options['MaxPrice']) && $options['MaxPrice'] > -1){
			$where .= ' and InfoPrice <= '.$options['MaxPrice']/$GLOBALS['DiscountRate'];
		}
		
		//省市区
		if( isset($options['ProvinceID']) &&  $options['ProvinceID'] != -1){
			$where .= ' and ProvinceID = '.intval($options['ProvinceID']);
		}
		if( isset($options['CityID']) &&  $options['CityID'] != -1){
			$where .= ' and CityID = '.intval($options['CityID']);
		}
		if( isset($options['DistrictID']) &&  $options['DistrictID'] != -1){
			$where .= ' and DistrictID = '.intval($options['DistrictID']);
		}
		
		//按属性筛选，多个属性以_隔开
		if( isset($options['Attr']) &&  $options['Attr'] != ''){
			$mt = D('Admin/TypeAttributeValue');
			$idlist = $mt->getInfoIDListByAttributeValueID( $options['Attr'] );
			if(!empty($idlist)){
				$where .= " and InfoID in ($idlist)";
			}else{
				return 0;
			}
		}
        $where .= get_site_where();
		$n = $this->where($where)->count();
		$_cache[$key] = $n;
		return $n;
	}

	/**
	 * 获取所有信息（各种语言），主要用户生成网站xml地图
	 */
	function getAllInfo($LanguageID=-1){
		$this->field('InfoID,InfoTitle,InfoTime,Html,LinkUrl,LanguageID,ChannelID');
		$where = "IsEnable=1 and IsCheck=1 and NOW() >= InfoTime";
		if( $LanguageID != -1 ){
			$LanguageID = intval($LanguageID);
			$where .= " and LanguageID=$LanguageID";
		}
		$data = $this->where($where)->order('LanguageID asc,InfoOrder asc, InfoTime desc')->select();
		return $data;
	}
	
	//通过指定得ID列表获取信息，$idlist各ID以逗号隔开
	function getInfoByIDList($idlist, $options=array() ){
	    $idlist = YdInput::checkCommaNum($idlist);
		if( empty($idlist) ) return false;
		$where = get_language_where_array();
		$where['IsEnable'] = 1;
		$where['IsCheck'] = 1;
		$where['InfoID'] = array('in',$idlist);
		if( isset($options['Time']) &&  $options['Time'] == 1){
			$where['_string'] = 'NOW()>=InfoTime';
		}
		//自定义字段
		if( !empty($options['Field']) ){
            $options['Field'] = YdInput::checkTableField($options['Field']); //过滤字段，防止非法注入
			$this->field( $options['Field'] );
		}
		$this->order('InfoOrder asc, InfoTime desc, InfoID desc');
		$data = $this->where($where)->select();
		return $data;
	}
	
	function getInfoRelation($infoid, $fieldname='InfoRelation'){
		$where['InfoID'] = intval($infoid);
		$where['IsEnable'] = 1;
        $fieldname = YdInput::checkTableField($fieldname);
		$content = $this->where($where)->getField($fieldname);
		$options['Time'] = 1;
		$result = $this->getInfoByIDList( $content, $options);
		return $result;
	}
	
	function getInfoAlbum($infoid, $fieldname='InfoAlbum'){
		$result = false;
		$where['InfoID'] = intval($infoid);
		$where['IsEnable'] = 1;
        $fieldname = YdInput::checkTableField($fieldname);
		$content = $this->where($where)->getField($fieldname);
		if( !empty($content) ){
			$result = yd_split($content, array('AlbumTitle','AlbumPicture','AlbumDescription'));
		}
		return $result;
	}
	
	//$id参数是InfoID或Html
	function findInfo($id){
		if( empty($id) ) return false;
		$where = get_language_where_array();
		//预览时可以显示未审核、禁用、定时发布的文章
		if(empty($_GET['preview'])){
			$where['IsEnable'] = 1;
			$where['IsCheck'] = 1;
			$where['_string'] = 'NOW()>=InfoTime';
		}
		if( is_numeric($id) ){
			$where['InfoID'] = $id;
		}else{
            $id = YdInput::checkHtmlName($id);
			$where['Html'] = $id;
		}
		$data = $this->where($where)->find();
		return $data;
	}
	
	function getIsHtml($id){
		$wh = get_language_where();
		if( is_numeric($id) ){
			$wh .= " and IsEnable=1 and IsCheck=1 and InfoID={$id}";
		}else{
            $id = YdInput::checkHtmlName($id);
			$wh .= " and IsEnable=1 and IsCheck=1 and html='{$id}'";
		}
		$data = $this->where($wh)->getfield('IsHtml');
		return $data;
	}
	
	//获取信息banner显示信息
	function getInfoImage($ChannelID, $top=-1, $labelid=-1){
		$where = get_language_where();
		$m = D('Admin/Channel');
		$ChannelList = $m->getChildChannel($ChannelID);
        $ChannelID = intval($ChannelID);
		if( $ChannelList ){
			$where .= ' and ChannelID in('.$ChannelID.','.implode(',', $ChannelList).')';
		}else{
			$where .= " and ChannelID={$ChannelID}";
		}
		
		if($labelid != -1){
			$list = explode(',', $labelid);
			foreach ($list as $k=>$v){
				if(is_numeric($v)){
					$where .= " and FIND_IN_SET('$v', LabelID) ";
				}
			}
		}
		
		$where .= " and IsEnable = 1 and InfoPicture!='' ";
		$this->field('InfoTitle,InfoPicture,InfoID,ChannelID')->where($where);

		$top = intval($top);
		if($top == -1 ) $top = 9;  //最多显示9张图片
		$this->limit($top); //banner最多显示9个banner
		
		$data = $this->order('InfoOrder asc, InfoTime desc, InfoID desc')->select();
		return $data;
	}
	
	//统计频道信息数
	function getInfoCount($ChannelID){
		$m = D('Admin/Channel');
		$childs = $m->getChildChannel($ChannelID);
        $ChannelID = intval($ChannelID);
		if(!empty($childs)){
			$idlist = implode(',', $childs);
			$idlist .= ",$ChannelID";
			$where = "ChannelID in ($idlist) ";
		}else{
			$where = "ChannelID={$ChannelID}";
		}
		$where .= " and IsEnable=1 and IsCheck=1";
		$n = $this->where($where)->count();
		return $n;
	}
	
	function batchDelInfo( $id = array(), $MemberID = -1){
		$id = YdInput::filterCommaNum($id);
		$where['InfoID']  = array('in', implode(',', $id));
		if($MemberID !=  -1){
			$where['MemberID'] = intval($MemberID);
		}
		$result = $this->where($where)->delete();
		return $result;
	}
	
	function delInfo( $id, $MemberID = -1){
		$where['InfoID']  = intval($id);
		if($MemberID !=  -1){
			$where['MemberID'] = intval($MemberID);
		}
		$result = $this->where($where)->delete();
		return $result;
	}
	
	//批量排序
	function batchSortInfo($InfoID=array(), $InfoOrder = array() , $MemberID = -1){
		$n = count($InfoID);
        $MemberID = intval($MemberID);
		for($i = 0; $i < $n; $i++){
			if( is_numeric($InfoOrder[$i]) ){
				$id = intval($InfoID[$i]);
				$where = "InfoID={$id}";
				$where .= ( $MemberID !=  -1) ? " and MemberID=$MemberID" : '';
				$this->where( $where )->setField('InfoOrder', $InfoOrder[$i]);
			}
		}
	}
	
	//批量移动
	function batchMoveInfo($InfoID, $ChannelID, $SpecailID, $MemberID = -1){
        $MemberID = intval($MemberID);
        $ChannelID = intval($ChannelID);
        //选择专题多个或1个，都会传入数组。未选择传入的是0
        $SpecailID = YdInput::filterCommaNum($SpecailID);
        $sid = is_array($SpecailID) ? implode(',', $SpecailID) : $SpecailID;
        if( !empty($sid) ) $fields['SpecialID'] = $sid;
		$n = count($InfoID);
		$fields['ChannelID'] = $ChannelID;
		for($i = 0; $i < $n; $i++){
			if( is_numeric( $InfoID[$i] ) ){
				$where = "InfoID={$InfoID[$i]}";
				$where .= ( $MemberID !=  -1) ? " and MemberID=$MemberID" : '';
				$this->where( $where )->setField($fields);
			}
		}
	}
	
	//批量设置标记属性
	function batchLabel($InfoID, $LabelID, $IsEnable, $MemberID = -1){
		if( !isset($IsEnable) ) return false;
		if(!empty($LabelID)){
            $LabelID = YdInput::filterCommaNum($LabelID);
        }
        $IsEnable = intval($IsEnable);
		$MemberID = intval($MemberID);
		$len = count($InfoID);
		$lid= is_array($LabelID) ? implode(',', $LabelID) : $LabelID;
		for($i = 0; $i < $len; $i++){
			if( is_numeric( $InfoID[$i] ) ){
				if( $lid ) $fields['LabelID'] = $lid;
				$fields['IsEnable'] = $IsEnable;
				$where = "InfoID=".$InfoID[$i];
				$where .= ( $MemberID !=  -1) ? " and MemberID=$MemberID" : '';
				$this->where( $where )->setField($fields);
			}
		}
		return true;
	}
	
	function batchCheck( $id = array() , $Check = 0){
		$id = YdInput::filterCommaNum($id);
		$where['InfoID']  = array('in', implode(',', $id));
        $Check = intval($Check);
		if( $Check != 0 ) $Check = 1;
		$result = $this->where($where)->setField('IsCheck', $Check);
		if( $result === false ){
			return false;
		}else{
			return true;
		}
	}
	
	function getReadTemplate($InfoID){
		$InfoID = intval($InfoID);
		$ChannelID = $this->where("InfoID={$InfoID}")->getField('ChannelID');
		$name = ChannelReadTemplate($ChannelID);
		return $name;
	}
	
	//获取当前信息的上一条和下一条相关信息
	function getNextPrevious($ChannelID, $InfoID){
		$ChannelID = intval($ChannelID);
        $InfoID = intval($InfoID);
		$where = get_language_where();
		$where .= " and IsEnable = 1  and IsCheck=1 and ChannelID={$ChannelID} and NOW() >= InfoTime";
		$this->field('InfoID')->where($where);
		$data = $this->order('InfoOrder asc, InfoTime desc')->select();
		$n = is_array($data) ? count($data) : 0;
		$nextID = -1;
		$previousID = -1;
		for ($i = 0; $i < $n; $i++){
			if( $data[$i]['InfoID'] == $InfoID){
				$previousID = ( $i == 0 ) ? -1 : $data[$i-1]['InfoID'];
				$nextID = ($i == $n-1) ? -1 : $data[$i+1]['InfoID'];
				break;
			}
		}
		
		//下一条信息
		if( $nextID == -1 ){
			$result['next']['HasNext'] = 0;
			$result['next']['NextInfoID'] = false;
			$result['next']['NextInfoTitle'] = false;
			$result['next']['NextInfoUrl'] = false;
		}else{
			$result['next']['HasNext'] = 1;
			$result['next']['NextInfoID'] = $nextID;
			$result['next']['NextInfoTitle'] = InfoTitle($nextID);
			$result['next']['NextInfoUrl'] = InfoUrl($nextID);
		}
		//上一条信息
		if( $previousID == -1 ){
			$result['previous']['HasPrevious'] = 0;
			$result['previous']['PreviousInfoID'] = false;
			$result['previous']['PreviousInfoTitle'] = false;
			$result['previous']['PreviousInfoUrl'] = false;
		}else{
			$result['previous']['HasPrevious'] = 1;
			$result['previous']['PreviousInfoID'] = $previousID;
			$result['previous']['PreviousInfoTitle'] = InfoTitle($previousID);
			$result['previous']['PreviousInfoUrl'] = InfoUrl($previousID);
		}
		return $result;
	}
	
	//获取当前信息的下一条信息（已遗弃）被getNextPrevious替代
	function getNext($ChannelID, $InfoID, $InfoOrder, $InfoTime){
		$ChannelID = intval($ChannelID);
        $InfoID = intval($InfoID);
		$InfoOrder = intval($InfoOrder);
		$InfoTime = YdInput::checkDatetime($InfoTime);
		
		$where = get_language_where();
		$where .= " and InfoOrder = $InfoOrder and ChannelID=$ChannelID";
		$n = $this->where($where)->count();
		$this->field('InfoID, InfoTitle');
		if($n == 1){ //表示序号没有重复
			$this->where("InfoOrder > $InfoOrder and ChannelID=$ChannelID");
			$this->order('InfoOrder asc'); //返回第一条记录
		}else{  //排序有重复
			$this->where("InfoOrder=$InfoOrder and ChannelID=$ChannelID and InfoTime < '$InfoTime'");
			$this->order('InfoTime desc, InfoID desc'); //返回第一条记录
		}
		$result =  $this->find();
		return $result;
	}
	
	
	//获取当前信息的上一条信息（已遗弃）被getNextPrevious替代
	function getPrevious($ChannelID, $InfoID, $InfoOrder, $InfoTime){
		$ChannelID = intval($ChannelID);
		$InfoOrder = intval($InfoOrder);
        $InfoID = intval($InfoID);
		$InfoTime =  YdInput::checkDatetime($InfoTime);
		
		$where = get_language_where();
		$where .= " and InfoOrder = $InfoOrder and ChannelID=$ChannelID";
		$n = $this->where($where)->count();
		$this->field('InfoID, InfoTitle');
		if($n == 1){ //表示序号没有重复
			$this->where("InfoOrder < $InfoOrder and ChannelID=$ChannelID");
			$this->order('InfoOrder desc'); //返回第一条记录
		}else{  //排序有重复
			$this->where("InfoOrder=$InfoOrder and ChannelID=$ChannelID and InfoTime > '$InfoTime'");
			$this->order('InfoTime asc, InfoID asc'); //返回第一条记录
		}
		$result =  $this->find();
		return $result;
	}
	
	function IncHit($InfoID){
	    try{
            $where['InfoID'] = intval($InfoID);
            return $this->where($where)->setInc('InfoHit');
        }catch(Exception $e){

        }
	}
	
	//是否有信息权限
	function hasInfoPurview($InfoID, $MemberID){
		$where['InfoID'] = intval($InfoID);
		$where['MemberID'] = intval($MemberID);
		$n = $this->where($where)->count();
		if( $n > 0 ){
			return true;
		}else{
			return false;
		}
	}
	
	//获取指定日期后更新的文章数[不区分语言]
	function getNewCount($date){
        $date = YdInput::checkDatetime($date);
		$n = $this->where("InfoTime>'{$date}'")->count();
		return $n;
	}
	
	function getTag($InfoID){
		if( $InfoID < 0 ) return false;
		$where['InfoID'] = intval($InfoID);
		$where['IsEnable'] = 1;
		$where['IsCheck'] = 1;
		$result = $this->where($where)->getField('Tag');
		return $result;
	}
	
	//获取产品信息（标题、价格）
	function findProduct($id){
		if( !is_numeric($id)) return false;
		$wh = get_language_where();
		$wh .= " and IsEnable=1 and IsCheck=1 and InfoID={$id}";
		$data = $this->field("InfoTitle,InfoPrice")->where($wh)->find();
		return $data;
	}
	
	//判断指定专题ID是否被信息使用
	function specialCount($SpecialID){
		$SpecialID = intval($SpecialID);
		$where = "FIND_IN_SET('$SpecialID', SpecialID)";
		$n = $this->where($where)->count();
		return $n;
	}
	
	//获取历史数据，$data：1维数组，存储InfoID
	function getHistory($data, $top=-1){
		if( empty($data) ) return false;
		$data = YdInput::filterCommaNum($data);
		$idlist = is_array($data) ? implode(',', $data) : $data;
		$where = get_language_where_array();
		$where['IsEnable'] = 1;
		$where['IsCheck'] = 1;
		$where['InfoID'] = array('in',$idlist);
		$where['InfoPrice'] = array('gt',0); //认为价格大于0的才是商品
		$this->field("InfoID,ChannelID,InfoTitle,InfoPicture,InfoPrice,InfoTime,InfoSContent");
		$this->order("field(InfoID,$idlist)"); //必须按指定的顺序排序
		if( $top != -1 && is_numeric($top) && $top > 0 ){
			$this->limit($top);
		}
		$data = $this->where($where)->select();
		if( empty($data) ) return false;
		$Total = is_array($data) ? count($data) : 0;
		for($i = 0; $i < $Total; $i++){
			$data[$i]['InfoUrl'] = InfoUrl($data[$i]['InfoID'], false, false, false, $data[$i]['ChannelID']);
			$data[$i]['DiscountPrice'] = DiscountPrice($data[$i]['InfoID'], $data[$i]['InfoPrice']);
			$data[$i]['InfoPrice'] = yd_to_money($data[$i]['InfoPrice']);
		}
		return $data;
	}
	
	//type:sales 按销量排序
	function getTopOrderbySales($channelid=-1, $top = -1, $order='desc'){
		$this->field('InfoID,ChannelID,InfoTitle,InfoPicture,InfoPrice,InfoTime,InfoSContent, sum(ProductQuantity) as Quantity');
		$this->table($this->tablePrefix.'order_product a');
		$this->join('Inner join '.$this->tablePrefix.'info b On a.ProductID = b.InfoID');
		$where = get_language_where_array();
		if( $channelid != -1 && is_numeric($channelid)){
			$m = D('Admin/Channel');
			$all = $m->getChildChannel( $channelid );
			if( empty($all) ){
				$where['ChannelID'] = $channelid;
			}else{
				$where['ChannelID'] = array( 'in', implode(',', $all) );
			}
		}
		
		//输出前几条
		if( $top != -1 && is_numeric($top) && $top > 0 ){
			$this->limit($top);
		}
		
		//排序
		if( $order == 'desc'){
			$this->order("Quantity desc");
		}else{
			$this->order("Quantity asc");
		}
		$data = $this->where($where)->group('InfoID')->select();
		if( empty($data) ) return false;
		$Total = is_array($data) ? count($data) : 0;
		for($i = 0; $i < $Total; $i++){
			$data[$i]['DiscountPrice'] = DiscountPrice($data[$i]['InfoID'], $data[$i]['InfoPrice']);
			$data[$i]['InfoPrice'] = yd_to_money($data[$i]['InfoPrice']);
			$data[$i]['InfoUrl'] = InfoUrl($data[$i]['InfoID'], false, false, false, $data[$i]['ChannelID']);
		}
		return $data;
	}
	
	//获取最大最小价格
	function getMinMaxPrice($channelid=-1){
		$data = array('MinPrice'=>0, 'MaxPrice'=>0);
		$where = get_language_where_array();
		if( $channelid != -1 && is_numeric($channelid)){
			$m = D('Admin/Channel');
			$all = $m->getChildChannel( $channelid );
			if( empty($all) ){
				$where['ChannelID'] = $channelid;
			}else{
				$where['ChannelID'] = array( 'in', implode(',', $all) );
			}
		}
		$where['InfoPrice'] = array('gt',0);
		$min = $this->where($where)->min('InfoPrice');
		if( $min > 0 ) $data['MinPrice'] = $min * $GLOBALS['DiscountRate'];
		
		$max =$this->where($where)->max('InfoPrice');
		if( $max > 0 ) $data['MaxPrice'] = $max * $GLOBALS['DiscountRate'];
		return $data;
	}
	
	/**
	 * 获取会员发布的文章数
	 * @param int $MemberID
	 */
	function getMemberInfoCount($MemberID){
		$where['MemberID'] = intval($MemberID);
		$n = $this->where($where)->count();
		return $n;
	}
	
	/**
	 * 为检索条件属性返回信息ID(以逗号隔开)
	 * @param int $channelid
	 * @param int $specialid
	 * @param int $minprice
	 * @param int $maxprice
	 */
	function getIdlistForConditionAttribute($channelid, $specialid, $minprice, $maxprice){
		$where = get_language_where();
		if( $channelid != -1 ){ //必须计算子栏目
			$mc = D('Admin/Channel');
			$channelid = intval($channelid);
			$list = $mc->getChildChannel( $channelid );
			$list[] = $channelid;
			$where .= ' and ChannelID in('.implode(',', $list).')';
		}
		if( $specialid != -1 ){
			$specialid = intval($specialid);
			$where .= " and FIND_IN_SET('{$specialid}', SpecialID)";
		}
		if( $minprice > -1){
			$where .= ' and InfoPrice >='.(double)$minprice;
		}
		if( $maxprice > -1){
			$where .= ' and InfoPrice <='.(double)$maxprice;
		}
		$data = $this->where($where)->field('InfoID')->select();
		if(!empty($data)){
			$idlist = '';
			foreach ($data as $v){
				$idlist .= $v['InfoID'].',';
			}
			$idlist = rtrim($idlist,',');
			return $idlist;
		}else{
			return false;
		}
	}

	/**
	 * 通过cookie数据获取购物车数据 
	 * @param array $data 购物车cookie二维关联数据（CartID、ProductID、ProductQuantity、AttributeValueID）
	 */
	function getCartInfo($data){
		if(empty($data)) return false;
		$idlist = '';
		foreach ($data as $v){
			$idlist .= intval($v['ProductID']).',';
		}
		$idlist = rtrim($idlist, ',');
		$where = "InfoID in($idlist)";
		$result = $this->where($where)->getField('InfoID,InfoTitle,InfoPrice,InfoPicture,Html,LinkUrl,ChannelID');
		foreach ($data as $k=>$v){
			$id = $data[$k]['ProductID'];
			$data[$k]['ProductName'] = $result[$id]['InfoTitle'];
			$data[$k]['ProductPrice'] = $result[$id]['InfoPrice'];
			$data[$k]['ProductPicture'] = $result[$id]['InfoPicture'];
			$data[$k]['Html'] = $result[$id]['Html'];
			$data[$k]['LinkUrl'] = $result[$id]['LinkUrl'];
			$data[$k]['ChannelID'] = $result[$id]['ChannelID'];
		}
		return $data;
	}
	
	/**
	 * 判断是否是商品
	 * @param int $InfoID
	 */
	function isGoods($InfoID){
		$b = false;
		$ChannelID = $this->where('InfoID='.intval($InfoID))->getField('ChannelID');
		if($ChannelID){
			$m = D('Admin/Channel');
			$ChannelModelID = $m->where('ChannelID='.intval($ChannelID))->getField('ChannelModelID');
			if( 36 == $ChannelModelID ){
				$b = true;
			}
		}
		return $b;
	}
	
	/**
	 * 判断是否购买产品
	 * @param int $InfoID
	 * @param int $MemberID
	 */
	function hasBuy($InfoID, $MemberID){
		$InfoID = intval($InfoID);
		$MemberID = intval($MemberID);
		$sql = "SELECT count(OrderID) as BuyCount From {$this->tablePrefix}order ";
		$sql .=" where PayStatus=1 and MemberID={$MemberID}";
		$sql .=" and OrderID in(SELECT DISTINCT OrderID FROM {$this->tablePrefix}order_product where ProductID={$InfoID})";
		$result = $this->query($sql);
		if( !empty($result) && $result[0]['BuyCount'] > 0 ){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * 获取分销商品列表
	 * @param int $FirstRow
	 * @param int $ListRow
	 * @param array $options
	 */
	function getDistributionGoods($offset = -1, $length = -1, $p=array()){
		if(  is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}
		$where = 'a.IsEnable=1 and a.IsCheck=1 and a.Commission>0 ';
		if( isset($p['LanguageID']) && $p['LanguageID'] != -1){
			$where .= ' and a.LanguageID = '.intval($p['LanguageID']);
		}else{
			$where .= ' and '.get_language_where('a');
		}
		
		//按频道检索
		if( isset($p['ChannelID']) && $p['ChannelID'] != -1){
			$where .= " and a.ChannelID=".intval($p['ChannelID']);
		}
		
		//按关键词检索
		if( isset($p['Keywords']) && !empty($p['Keywords'])) {
			$Keywords = addslashes(stripslashes($p['Keywords']));
			$Keywords = YdInput::checkKeyword($Keywords);
			$where .= " and (a.InfoTitle like '%{$Keywords}%' or a.Tag like '%{$Keywords}%')";
		}
		
		$this->field('a.*,b.ChannelName');
		$this->table($this->tablePrefix.'info a');
		$this->join($this->tablePrefix.'channel b On a.ChannelID = b.ChannelID');
		$result = $this->where($where)->order('a.InfoOrder asc, a.InfoTime desc')->select();
		if( !empty($result)){
			$n = count($result);
			for($i=0; $i < $n; $i++){
				$InfoPrice = $result[$i]['InfoPrice'];
				if($InfoPrice>0){
					$CommissionRatio = (100*$result[$i]['Commission'])/$InfoPrice;
				}else{
					$CommissionRatio = 0;
				}
				$result[$i]['CommissionRatio'] = round($CommissionRatio, 2);
			}
		}
		return $result;
	}
	
	/**
	 * 获取分销商品数量
	 * @param array $p
	 */
	function getDistributionGoodsCount($p=array()){
		$where = 'IsEnable=1 and IsCheck=1 and Commission>0  ';
		if( isset($p['LanguageID']) && $p['LanguageID'] != -1){
			$where .= ' and LanguageID = '.intval($p['LanguageID']);
		}else{
			$where .= ' and '.get_language_where();
		}
		
		//按频道检索
		if( isset($p['ChannelID']) && $p['ChannelID'] != -1){
			$where .= " and ChannelID=".intval($p['ChannelID']);
		}
		
		//按关键词检索
		if( isset($p['Keywords']) && !empty($p['Keywords'])) {
			$Keywords = addslashes(stripslashes($p['Keywords']));
			$Keywords = YdInput::checkKeyword($Keywords);
			$where .= " and (InfoTitle like '%{$Keywords}%' or Tag like '%{$Keywords}%')";
		}
		$n = $this->where($where)->count();
		return $n;
	}
	
	/**
	 * 获取订单商品单独设置的佣金
	 * @param int $OrderID
	 */
	function getOrderCommission($OrderID){
		$m = D('Admin/OrderProduct');
		$OrderID = intval($OrderID);
		$data =$m->where("OrderID=$OrderID")->getField("ProductID,ProductQuantity");
		$total = 0; //总佣金
		if(!empty($data)){
			$idlist = array_keys($data); //ProductID数组
			$where = 'InfoID in('.implode(',', $idlist).')';
			$list = $this->where($where)->getField("InfoID,Commission");
			foreach ($data as $id=>$n){
				$Commission = isset($list[$id]) ? (double)($list[$id]) : 0;
				$total += $Commission * $n;
			}
		}
		return $total;
	}

    /**
     * 获取网站统计信息
     */
	function statInfo(){
        $where = get_language_where('a');
        $where .= " AND a.IsEnable=1 AND b.ChannelModelID in(30,31,34,35,36,37)";
        $prefix = C('DB_PREFIX');
        $sql = "SELECT ChannelModelID,COUNT(InfoID) AS n
            FROM {$prefix}info a 
            INNER JOIN {$prefix}channel b On a.ChannelID=b.ChannelID AND b.IsEnable=1
            WHERE {$where} GROUP BY ChannelModelID";
        $data = $this->query($sql);
        $map = array();
        foreach($data as $k=>$v){
            $key = $v['ChannelModelID'];
            $map[$key] = $v['n'];
        }
        $stat = array();
        $stat[] = array('Name'=>'文章', 'Count'=>$map['30'] ? $map['30']:0 );
        $stat[] = array('Name'=>'图片', 'Count'=>$map['31'] ? $map['31']:0 );
        $stat[] = array('Name'=>'视频', 'Count'=>$map['34'] ? $map['34']:0 );
        $stat[] = array('Name'=>'下载', 'Count'=>$map['35'] ? $map['35']:0 );
        $stat[] = array('Name'=>'产品', 'Count'=>$map['36'] ? $map['36']:0 );
        $stat[] = array('Name'=>'反馈', 'Count'=>$map['37'] ? $map['37']:0 );
        //获取留言条数
        $m = D('Admin/Guestbook');
        $where = get_language_where();
        $n = $m->where($where)->count();
        if(empty($n)) $n=0;
        $stat[] = array('Name'=>'留言', 'Count'=>$n);
        //订单
        $m = D('Admin/Order');
        $where = get_language_where();
        $n = $m->where($where)->count();
        if(empty($n)) $n=0;
        $stat[] = array('Name'=>'订单', 'Count'=>$n);
        return $stat;
    }
}
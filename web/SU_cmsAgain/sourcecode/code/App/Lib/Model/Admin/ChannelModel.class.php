<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */
class ChannelModel extends Model {

	protected $_validate = array(
			array('ChannelName', 'require', '频道名称不能为空!'),
			array('ChannelOrder', '/^[-]?\d+$/', '排序必须为数字!', '2','regex'),
			array('PageSize', 'number', '分页条数必须为数字!'),
			array('Html', 'require', '静态文件名称不能为空!'),
			);
	
	protected $_auto = array(
			array('LanguageID', 'get_language_id', 1, 'function'),
	);
	
	/**
	 * 获取当前频道所有信息
	 * @param int $ParentID, 0:表示获取所有栏目
	 * @param int $depth: 栏目级数,取负数:表示获取所有级数栏目; 整数: 返回指定栏目数
	 * @param string $prefix: 栏目前缀字符 , 如:└ ├├─ 
	 * @return array
	 */
	function getChannel($ParentID = 0, $HasSingleModel = true, $hasLinkModel = true, $ExcludeChannel = -1, $depth = -1, $prefix = '&nbsp;&nbsp;&nbsp;&nbsp;├─ ', $groupid=-1, $field='ChannelID,ChannelName'){
		$ParentID = intval($ParentID);
		$depth = intval($depth);
		if( 0 == $depth) return false;
        $field = YdInput::checkTableField($field); //过滤字段，防止非法注入
		$this->field($field);
		
		$where = get_language_where();
		$where .= " and IsEnable=1 and Parent=$ParentID";
		if( $HasSingleModel === false){
			$where .=" and ChannelModelID != 32 ";
		}
		if( $hasLinkModel === false){
			$where .=" and ChannelModelID != 33 ";
		}
		
		if( $ExcludeChannel && $ExcludeChannel != -1){
            $ExcludeChannel = intval($ExcludeChannel);
			$cid = $this->getChildChannel($ExcludeChannel, $groupid);
			if( $cid ){
				$cid = "($ExcludeChannel,".implode(',', $cid).')';
				$where .=" and ChannelID not in  $cid ";
			}else{
				$where .=" and ChannelID != $ExcludeChannel ";
			}
		}
		
		if( $groupid != -1 && $groupid != 1){
			$ma = D('Admin/AdminGroup');
			$list = $ma->getChannelPurview( $groupid );
			$where .= " and ChannelID in ({$list}) ";
		}
		$result = $this->where($where)->order('ChannelOrder asc,ChannelID asc')->select();
		$all = array();
		if( !empty($result) ){
			$nCount1 = is_array($result) ? count($result) : 0;
			for($i = 0; $i < $nCount1; $i++){
				$temp = $this->getChannel( $result[$i]['ChannelID'], $HasSingleModel, $hasLinkModel, $ExcludeChannel, $depth - 1, $prefix , $groupid, $field);
				$all[] =  $result[$i];
				if( $temp ){
					$nCount2 = count($temp);
					for($n = 0; $n < $nCount2; $n++){
						$p = !empty($prefix) && strstr ($temp[$n]['ChannelName'], $prefix) ? "&nbsp;&nbsp;&nbsp;&nbsp;" : $prefix;
						$temp[$n]['ChannelName'] = $p.$temp[$n]['ChannelName'];
					}
					$all = array_merge($all, $temp);
				}
			}
			unset($result);
		}
		return $all;
	}
	
	//$ParentID=0表示返回所有频道，$idlist=^1,2,3 表示过滤1,2,3, $idlist=1,2,3表表示仅返回1,2,3频道，$ParentID无效
	function getChannelList($ParentID = 0, $depth = -1, $prefix = '&nbsp;&nbsp;&nbsp;&nbsp;├─ ', $idlist=false, $lngID=false){
		$ParentID = intval($ParentID);
		$depth = intval($depth);
		$flag = substr($idlist, 0, 1);
		if(!empty($idlist) && $flag !='^'){ //bug:无法获取ChannelDepth的zhi
			$this->table($this->tablePrefix.'channel a');
			$this->field('a.*, b.ChannelModelID,b.ChannelModelName');
			$this->join(' Inner Join '.$this->tablePrefix.'channel_model b On a.ChannelModelID = b.ChannelModelID');
			$where = get_language_where('a', $lngID);
            $idlist = YdInput::checkCommaNum($idlist, -2);
			$where .= " and a.ChannelID in ($idlist)";
			//"field(a.ChannelID,$idlist)" 保持$idlist的顺序
			$result = $this->where( $where )->order("field(a.ChannelID,$idlist)")->select();
			//计算ChannelDepth
			if( !empty($result) ){
				$n = is_array($result) ? count($result) : 0;
				for($i=0; $i<$n; $i++){
					$depth = $this->getChannelDepth( $result[$i]['ChannelID'] );
					$result[$i]['ChannelDepth'] = $depth;
					if( strlen($prefix) > 0 ){
						$p = '';
						if( abs($depth) != 1){
							$p = str_repeat('&nbsp;&nbsp;',  abs($depth) );
							$p .= '├─';
						}
						$result[$i]['ChannelName'] = $p.$result[$i]['ChannelName'];
					}
				}
			}
			return $result;
		}else{
			if( 0 == $depth) return false;
			$this->table($this->tablePrefix.'channel a');
			$this->field('a.*, b.ChannelModelID,b.ChannelModelName');
			$this->join(' Inner Join '.$this->tablePrefix.'channel_model b On a.ChannelModelID = b.ChannelModelID');
			$where = get_language_where('a', $lngID);
			$where .= " and a.Parent=$ParentID";
			if( $flag=='^'){
                $tempIDList = substr($idlist, 1);
                $tempIDList = YdInput::checkCommaNum($tempIDList);
                if(!empty($tempIDList)){
                    $where .= " and a.ChannelID not in (" . $tempIDList . ")";
                }
			}
			$result = $this->where( $where )->order('a.ChannelOrder asc,a.ChannelID asc')->select();
			$all = array();
			if( !empty($result) ){
				for($i = 0; $i < count($result); $i++){
					$result[$i]['ChannelDepth'] = $depth;
					$temp = $this->getChannelList( $result[$i]['ChannelID'], $depth - 1, $prefix , $idlist, $lngID);
					$all[] =  $result[$i];
					if( $temp ){
						for($n = 0; $n < count($temp); $n++){
							$p = !empty($prefix) && strstr ($temp[$n]['ChannelName'], $prefix) ? "&nbsp;&nbsp;&nbsp;&nbsp;" : $prefix;
							$temp[$n]['ChannelName'] = $p.$temp[$n]['ChannelName'];
						}
						$all = array_merge($all, $temp);
					}
				}
				unset($result);
			}
			return $all;
		}
	}
	
	//$IDList: 频道ID列表，以逗号分开（不输出指定id子频道），若以^开头，表示不包含
	//$ParentID=0表示返回所有频道，$idlist=^1,2,3 表示过滤1,2,3, $idlist=1,2,3表表示仅返回1,2,3频道，$ParentID无效
	function getNavigation($ParentID = 0, $depth = -1, $idlist = -1, $isshow=1, $channelmodelid=-1, $LanguageID=-1, $Field=''){
		//构造条件
		if($LanguageID != -1 ){
			$where['LanguageID'] = $LanguageID;
		}else{
			$where = get_language_where_array();
		}
		
		if($isshow != -1 ){
			$where['IsShow'] = intval($isshow);
		}
		if($channelmodelid != -1 ){
			$where['ChannelModelID'] = intval($channelmodelid);
		}
		if( !empty($Field) ){
            $Field = YdInput::checkTableField($Field);
			$Field = explode(',', $Field);
			$Field = array_merge($Field, array('ChannelID','ChannelName','ChannelModelID','Html','LinkUrl','ChannelPicture','ChannelIcon','Parent','HasChild','ChannelTarget') );
			$Field = implode(',', array_unique($Field));
			$this->field($Field); //指定字段
		}
		$where['IsEnable'] = 1;
		
		$flag = substr($idlist, 0, 1);
		if($idlist != -1 && $flag !='^'){
			//处理指定ID的情况，$depth无效
            $idlist = YdInput::checkCommaNum($idlist, -2);
			$where['ChannelID'] = array('in', $idlist);
			$result = $this->where( $where )->order("field(ChannelID,$idlist)")->select();
			return $result;
		}else{
			if( 0 == $depth) return false;
			$where['Parent'] = intval($ParentID);
			if($flag=='^'){
			    $tempIDList = substr($idlist, 1);
                $tempIDList = YdInput::checkCommaNum($tempIDList);
                if(!empty($tempIDList)){
                    $where['ChannelID'] = array('not in', $tempIDList);
                }
			}
			$result = $this->where( $where )->order('ChannelOrder asc,ChannelID asc')->select();
			
			$all = array();
			if( !empty($result) ){
				$nTotal = is_array($result) ? count($result) : 0;
				for($i = 0; $i < $nTotal; $i++){
					$result[$i]['ChannelDepth'] = $depth;
					$temp = $this->getNavigation( $result[$i]['ChannelID'], $depth - 1, $idlist, $isshow,$channelmodelid);
					$all[] =  $result[$i];
					if( $temp ){
						$nCount = count($temp);
						for($n = 0; $n < $nCount; $n++){
							$temp[$n]['ChannelName'] = $temp[$n]['ChannelName'];
						}
						$all = array_merge($all, $temp);
					}
				}
				unset($result);
			}
			return $all;
		}
	}
	
	//$MenuOwner: 0会员， 1：管理员
	function getChannelPurview($MenuOwner, $groupID, $prefix = '&nbsp;&nbsp;&nbsp;&nbsp;├─ ', $lngID=false){
        $MenuOwner = intval($MenuOwner);
		$groupID = intval($groupID);
		if( $MenuOwner == 1 ){
			if( $groupID == 1 ){ //超级管理组拥有全部权限
				$result = $this->getChannelList(0, -1, $prefix, false, $lngID);
				return $result;
			}
			$m = D('Admin/AdminGroup');
			$m->where("AdminGroupID=$groupID");
		}else{
			$m = D('Admin/MemberGroup');
			$m->where("MemberGroupID=$groupID");
		}
		$list = $m->getField('ChannelPurview'.LANG_SET);  //获取id号
		if( empty($list) ) return false;

		$result = $this->getChannelList(0, -1, $prefix, $list, $lngID);
		return $result;
	}
	
	//会员是否有指定频道的操作权限
	function hasChannelPurview($ChannelID, $MenuOwner, $groupID){
		$ChannelID = intval($ChannelID);
        $MenuOwner = intval($MenuOwner);
		$groupID = intval($groupID);
		$FieldName = 'ChannelPurview'.LANG_SET;
		if( $MenuOwner == 1 ){
			$m = D('Admin/AdminGroup');
			$m->where("AdminGroupID=$groupID and FIND_IN_SET('$ChannelID', $FieldName)");
		}else{
			$m = D('Admin/MemberGroup');
			$m->where("MemberGroupID=$groupID and FIND_IN_SET('$ChannelID', $FieldName)");
		}
		$n = $m->count();
		if($n > 0){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * 判断指定频道是否存在子频道
	 * @param  $ChannelID
	 * @return boolean
	 */
	function hasChildChannel($ChannelID){
		//$where = get_language_where(); 加上会产生bug
		$where['Parent'] = intval($ChannelID);
		$c = $this->where($where)->count();
		if( $c > 0){
			return true;
		}else{
			return false;
		}
	}
	
	//写入HasChild缓存
	function writeCache(){
		/*
		$data = $this->field('ChannelID，')->select();  //获取所有频道ID
		foreach ($data as $v){
			$where = "ChannelID=".$v['ChannelID'];
			$HasChild = $this->hasChildChannel( $v['ChannelID'] ) ? 1 : 0;
			$this->where($where)->setfield('HasChild', $HasChild);
		}
		*/
		
		//$where = get_language_where();
        $where = array(); //生成地图需要用到所有数据，因此需要缓存全部
		$data = $this->where($where)->getField('ChannelID,HTML');  //获取所有频道ID
        try{
            foreach ($data as $id=>$html){
                $where = "ChannelID=".$id;
                $HasChild = $this->hasChildChannel( $id ) ? 1 : 0;
                $this->where($where)->setfield('HasChild', $HasChild);
            }
        }catch(Exception $e){

        }
		return $data;
	}
	
	/**
	 * 是否有数据信息
	 * @param int $ChannelID
	 * @return boolean
	 */
	function hasData($ChannelID){
		$m = D('Admin/Info');
		$c = $m->where("ChannelID=".intval($ChannelID))->count();
		if( $c > 0){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * 安全模式下删除频道(即不能删除锁定和系统频道)
	 * @param  $ChannelID 频道ID
	 */
	function safeDelChannel($ChannelID){
		return $this->where("IsLock=0 and IsSystem=0 and ChannelID=".intval($ChannelID))->delete();
	}
	
	/**
	 * 获取频道模型ID
	 * @param int $ChannelID
	 */
	function getChannelModelID($ChannelID){
		$ChannelModelID = $this->where("ChannelID=".intval($ChannelID))->getField('ChannelModelID');
		return $ChannelModelID;
	}
	
	/**
	 * 禁用频道不显示, 仅在_getChannel中调用
	 * @param string $html 频道静态文件名
	 */
	function findChannelByHtml($html){
        $html = YdInput::checkHtmlName($html);
		$where = get_language_where_array();
		$where['IsEnable'] = 1;
		$where['Html'] = $html;
        $result = $this->where($where)->find();
		return $result;
	}
	
	//通过html获取IsHtml字段
	function IsHtmlByHtml($html){
        $html = YdInput::checkHtmlName($html);
		$where = get_language_where_array();
		$where['IsEnable'] = 1;
		$where['Html'] = $html;
        $result = $this->where($where)->getField('IsHtml');
        return $result;
	}
	
	function getFieldByChannelID($ChannelID, $FieldName="ChannelName"){
        $FieldName = YdInput::checkTableField($FieldName);
		$where['ChannelID'] = intval($ChannelID);
		$name = $this->where($where)->getField($FieldName);
		return $name;
	}
	
	function findField($ChannelID, $field=false){
	    if($field){
            $field = YdInput::checkTableField($field);
        }
		$where['ChannelID'] = intval($ChannelID);
		$data = $this->where($where)->field($field)->find();
		return $data;
	}
	
	/**
	 * 获取指定频道所有子频道
	 * @param int $ChannelID
	 * @param int $groupid -1 表示不限制分组，否则仅获取有这个分组权限的子频道
	 * @return array
	 */
	function getChildChannel($ChannelID, $groupid=-1){
		$ChannelID = intval($ChannelID);
		$groupid = intval($groupid);
		//必须缓存，可以大幅度提高速度，否则频道一多，在修改频道时，会超过30秒
		$k = $ChannelID.$groupid;
		static $_cache = array();
		if (isset($_cache[$k])){
			return $_cache[$k];
		}
		
		$c= $this->field('ChannelID')->where("Parent=$ChannelID")->select();
		if( !$c ) return false;
		$Channels= array();
		foreach($c as $key=>$value){
			if( HasChannelPurview($value['ChannelID'], $groupid) ){
				$Channels[] = $value['ChannelID'];
				$temp = $this->getChildChannel( $value['ChannelID'] );
				if( $temp ){
					$Channels = array_merge($Channels, $temp);
				}
			}
		}
		$_cache[$k] = $Channels;
		return $Channels;
	}
	
	//获取频道深度
	function getChannelDepth($ChannelID){
		$ChannelID = intval($ChannelID);
		$Depth = 0;
		while($ChannelID != 0){
			$Depth--;
			$ChannelID = $this->where("ChannelID=$ChannelID")->getField('Parent');
		}
		return $Depth;
	}
	
	//获取最顶层频道
	function getTopChannel($ChannelID){
		$ChannelID = intval($ChannelID);
		$parentID = $this->where("ChannelID=$ChannelID")->getField('Parent');
		if($parentID != 0){
			return $this->getTopChannel($parentID);
		}else{
			return $ChannelID;
		}
	}
	
	//获取父频道
	function getParentChannel($ChannelID){
		//获取当前频道父频道ID
		$ChannelID = intval($ChannelID);
		$parentID = $this->where("ChannelID=$ChannelID")->getField('Parent');
		$info = $this->where("ChannelID=$ChannelID")->find();
		return $info;
	}
	
	
	//保存全部数据
	function saveAll( $data ){
		$n = is_array($data['ChannelID']) ? count( $data['ChannelID'] ) : 0;
		for($i = 0; $i < $n; $i++){
			if( is_numeric( $data['ChannelID'][$i] ) ){
                $value = array();
				$value['ChannelName'] = $data['ChannelName'][$i];
				$value['Html'] = $data['Html'][$i];
				$value['ChannelOrder'] = $data['ChannelOrder'][$i];
				$value['PageSize'] = $data['PageSize'][$i];
				$value['ChannelSName'] = $data['ChannelSName'][$i];
				
				$value['ChannelOrder'] = $data['ChannelOrder'][$i];
				//$value['IndexTemplate'] = $data['IndexTemplate'][$i];
				//$value['ReadTemplate'] = $data['ReadTemplate'][$i];
				
				$this->where('ChannelID='.$data['ChannelID'][$i])->setField( $value );
			}
		}
	}
	
	//指定频道是否禁用，(如果父频道禁用，则子频道也禁用, 赞不判断)
	function ChannelIsEnable($ChannelID){
		$ChannelID = intval($ChannelID);
		$n = $this->where("ChannelID=$ChannelID and IsEnable=1")->count();
		if( $n == 1 ){
			return true;
		}else{
			return false;
		}
	}
	
	//判断是否是单页频道
	function IsSingleChannel($ChannelID){
		$ChannelID = intval($ChannelID);
		$id = $this->where("ChannelID=$ChannelID")->getField('ChannelModelID');
		if( $id == 32){
			return true;
		}else{
			return false;
		}
	}
	
	//获取所有频道，不区分语言，用于生成网站地图
	function getAllChannel($LanguageID=-1){
		$this->field('ChannelID,ChannelName,Html,LinkUrl,ChannelModelID,LanguageID');
		$this->where('IsEnable=1 and ChannelID not in(6,7,10,11)');
		$this->order('LanguageID asc,ChannelOrder asc,ChannelID asc');
		if( $LanguageID != -1 ){
			$LanguageID = intval($LanguageID);
			$this->where("LanguageID=$LanguageID");
		}
		$data = $this->select();
		return $data;
	}
	
	function getChannelAlbum($channelid, $fieldname='ChannelAlbum'){
		$result = false;
		$where['ChannelID'] = intval($channelid);
		$where['IsEnable'] = 1;
        $fieldname = YdInput::checkTableField($fieldname);
		$content = $this->where($where)->getField($fieldname);
		if( !empty($content) ){
			$result = yd_split($content, array('AlbumTitle','AlbumPicture','AlbumDescription'));
		}
		return $result;
	}
	
	function getChannelRelation($channelid, $fieldname='ChannelRelation'){
		$where['ChannelID'] = intval($channelid);
		$where['IsEnable'] = 1;
        $fieldname = YdInput::checkTableField($fieldname);
		$content = $this->where($where)->getField($fieldname);
		$m = D('Admin/Info');
		$options['Time'] = 1;
		$result = $m->getInfoByIDList( $content,$options);
		return $result;
	}

    //主要是模板装修调用
    function getChannelSource($ParentID = 0, $depth = -1, $prefix = '&nbsp;&nbsp;&nbsp;&nbsp;├─ ', $lngID=false){
        $ParentID = intval($ParentID);
        $depth = intval($depth);
        if( 0 == $depth) return false;
        $this->table($this->tablePrefix.'channel a');
        $this->field('ChannelID,ChannelName,ChannelModelID,HasChild,ChannelPicture,f3,ChannelIcon');
        $where = get_language_where('', $lngID);
        $where .= " and Parent={$ParentID}";
        //排除一些频道
        $where .= " and ChannelID NOT IN(1,2,6)";
        $result = $this->where( $where )->order('ChannelOrder asc,ChannelID asc')->select();
        $all = array();
        if( !empty($result) ){
            for($i = 0; $i < count($result); $i++){
                $result[$i]['ChannelDepth'] = $depth;
                $temp = $this->getChannelSource( $result[$i]['ChannelID'], $depth - 1, $prefix, $lngID);
                $all[] =  $result[$i];
                if( $temp ){
                    for($n = 0; $n < count($temp); $n++){
                        $p = !empty($prefix) && strstr ($temp[$n]['ChannelName'], $prefix) ? "&nbsp;&nbsp;&nbsp;&nbsp;" : $prefix;
                        $temp[$n]['ChannelName'] = $p.$temp[$n]['ChannelName'];
                    }
                    $all = array_merge($all, $temp);
                }
            }
            unset($result);
        }
        return $all;
    }

    /**
     * 获取待拷贝的频道
     * @param $channelid 多个以逗号分开
     */
    function getChannelToCopy($channelid){
        $channelid = YdInput::checkCommaNum($channelid);
        $where['ChannelID'] = array('IN', $channelid);
        $where['LanguageID'] = get_language_id();
        $data= $this->where($where)->select();
        return $data;
    }

    /**
     * 删除频道及其子频道，以及频道文章
     * @param $ChannelID
     */
    function deleteChannelAllData($ChannelID){
        if(!is_numeric($ChannelID)) return false;
        $Channels = $this->getChildChannel($ChannelID);
        if(!is_array($Channels)) $Channels = array();
        $Channels[] = $ChannelID;
        $where = array();
        $where['LanguageID'] = get_language_id();
        $where['ChannelID'] = array('IN', $Channels);
        $where['IsSystem'] = 0;
        $result = $this->where($where)->delete();
        if($result > 0){
            $m = D('Admin/Info');
            unset($where['IsSystem']);
            $result = $m->where($where)->delete();
        }
        return true;
    }
}
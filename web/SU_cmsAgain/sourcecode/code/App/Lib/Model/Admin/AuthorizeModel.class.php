<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */
class AuthorizeModel extends Model {
	protected $_validate = array(
			array('Host', '', '域名已经存在!', '0', 'unique'),
	);
	
	function getAuthorizeInfo($offset = -1, $length = -1, $Host='', $IsAuthorize=-1, $AgentID=-1, $OperatorID=-1,$SourceID=-1){
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}
        $where = $this->getWhere($Host, $IsAuthorize, $AgentID, $OperatorID,$SourceID);
		$result = $this->where($where)->order('AuthorizeID desc')->select();
		if(!empty($result)){
            $map = $this->getSource();
            foreach($result as $k=>$v){
                $key = $v['Source'];
                $result[$k]['SourceName'] = isset($map[$key]) ? $map[$key] : '';
            }
		}
		return $result;
	}

	function getWhere($Host='', $IsAuthorize=-1, $AgentID=-1, $OperatorID=-1,$SourceID=-1){
        $where = "1=1";
        if( $Host != '' ){
            $Host = addslashes(stripslashes($Host));
            $Host = YdInput::checkLetterNumber($Host);
            $where .= " and Host like '%$Host%'";
        }

        if( $IsAuthorize != -1 ){
            $IsAuthorize = intval($IsAuthorize);
            $where .= " and IsAuthorize= $IsAuthorize";
        }

        if( $AgentID != -1 ){
            $AgentID = intval($AgentID);
            $where .= " and AgentID= $AgentID";
        }

        if( $OperatorID != -1 ){
            $OperatorID = intval($OperatorID);
            $where .= " and OperatorID= $OperatorID";
        }

        if( $SourceID != -1 ){
            $SourceID = intval($SourceID);
            $where .= " and Source=$SourceID";
        }
        return $where;
    }

    function getCount($Host='', $IsAuthorize=-1, $AgentID=-1, $OperatorID=-1,$SourceID=-1){
        $where = $this->getWhere($Host, $IsAuthorize, $AgentID, $OperatorID,$SourceID);
        $n = $this->where($where)->count();
        return $n;
    }

	function getSource($returnMap=true){
	    $data = array();
	    $data[] = array('SourceID'=>1, 'SourceName'=>'其他');
        $data[]  = array('SourceID'=>2, 'SourceName'=>'官方');
        $data[]  = array('SourceID'=>3, 'SourceName'=>'西数');
        $data[]  = array('SourceID'=>5, 'SourceName'=>'A5');

        $data[]  = array('SourceID'=>6, 'SourceName'=>'百川云');
        $data[]  = array('SourceID'=>7, 'SourceName'=>'chinaz');
        $data[]  = array('SourceID'=>8, 'SourceName'=>'保留8');
        $data[]  = array('SourceID'=>9, 'SourceName'=>'保留9');
        $data[]  = array('SourceID'=>10, 'SourceName'=>'保留10');

        $data[]  = array('SourceID'=>11, 'SourceName'=>'保留11');
        $data[]  = array('SourceID'=>12, 'SourceName'=>'保留12');
        $data[]  = array('SourceID'=>13, 'SourceName'=>'保留13');
        if($returnMap){
            $map = array();
            foreach($data as $v){
                $key = $v['SourceID'];
                $map[$key] = $v['SourceName'];
            }
            return $map;
        }else{
            return $data;
        }
    }
	
	/**
	 * 全局检索域名是否授权
	 * @param string $host
	 * @return array
	 */
	function searchAuthorize($host){
        $host = YdInput::checkLetterNumber($host);
		//过滤非法字符
		$host = str_ireplace(array('%',"'",'"'), '', trim($host));
		$prefix = strtolower( substr($host, 0, 4) );
		if( $prefix == 'www.'){
			$where['Host'] = $host;
		}else{
			$where['Host'] = array('in',"{$host},www.{$host}");
		}
		$result = $this->where($where)->select();
		return $result;
	}
	
	//客服获取自己授权的域名
	function getUserAuthorize($offset = -1, $length = -1, $Host='', $CustomerID=-1, $OperatorID=-1){
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}
		$where = "1=1";
		if( $Host != '' ){
            $Host = addslashes(stripslashes($Host));
            $Host = YdInput::checkLetterNumber($Host);
			$where .= " and Host='{$Host}'";
		}
		if( $CustomerID != -1 ){
            $CustomerID = YdInput::checkCommaNum($CustomerID);
			$where .= " and CustomerID in({$CustomerID})";
		}
		if( $OperatorID != -1 ){
            $OperatorID = intval($OperatorID);
			$where .= " and OperatorID = {$OperatorID}";
		}
		$result = $this->where($where)->order('AuthorizeID desc')->select();
		return $result;
	}
	
	function getUserCount($Host='', $CustomerID=-1, $OperatorID=-1){
		$where = "1=1";
		if( $Host != '' ){
            $Host = addslashes(stripslashes($Host));
            $Host = YdInput::checkLetterNumber($Host);
			$where .= " and Host='$Host'";
		}
		if( $CustomerID != -1 ){
            $CustomerID = YdInput::checkCommaNum($CustomerID);
			$where .= " and CustomerID in($CustomerID)";
		}
		if( $OperatorID != -1 ){
            $OperatorID = intval($OperatorID);
			$where .= " and OperatorID = $OperatorID";
		}
		$n = $this->where($where)->count();
		return $n;
	}
	
	//客服获取授权域名总数，$CustomerID取值可能有多个：1,2,3
	function GetUserAuthorizeCount($CustomerID){
        $CustomerID = YdInput::checkCommaNum($CustomerID);
		$where = " CustomerID in({$CustomerID})";
		$n = $this->where($where)->count();
		return $n;
	}
	
	//代理获取自己的授权域名数 代理的MemberID
	function GetAgentAuthorizeCount($MemberID){
        $MemberID = intval($MemberID);
		$where = "CustomerID=$MemberID";
		$n = $this->where($where)->count();
		return $n;
	}
	
	//代理获取自己的授权域名
	function getAgentAuthorize($offset = -1, $length = -1, $Host='',  $OperatorID=-1){
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}
		$where = "1=1";
		if( $Host != '' ){
            $Host = addslashes(stripslashes($Host));
            $Host = YdInput::checkLetterNumber($Host);
			$where .= " and Host='$Host'";
		}
		
		//代理自己操作的和自己名下的都显示
		if( $OperatorID != -1 ){
            $OperatorID = intval($OperatorID);
			$where .= " and (OperatorID=$OperatorID or CustomerID=$OperatorID)";
		}
		$result = $this->where($where)->order('AuthorizeID desc')->select();
		return $result;
	}
	
	function getAgentCount($Host='',  $OperatorID=-1){
		$where = "1=1";
		if( $Host != '' ){
            $Host = addslashes(stripslashes($Host));
            $Host = YdInput::checkLetterNumber($Host);
			$where .= " and Host='$Host'";
		}

		if( $OperatorID != -1 ){
            $OperatorID = intval($OperatorID);
			$where .= " and (OperatorID=$OperatorID or CustomerID=$OperatorID)";
		}
		$n = $this->where($where)->count();
		return $n;
	}
	
	//获取域名授权数量
	function GetAuthorizeCount($IsAuthorize=-1, $MemberID=-1){
        $where = array();
		if( $IsAuthorize != -1 ){
            $IsAuthorize = intval($IsAuthorize);
			$where['IsAuthorize'] = $IsAuthorize;
		}
		if( $MemberID != -1 ){
            $MemberID = intval($MemberID);
			$where['CustomerID'] = $MemberID;
		}
		$n = $this->where($where)->count();
		return $n;
	}

	
	function authorize( $id = array() , $OperatorID=0, $AgentID=-1, $IsAuthorize = 0){
        $id = YdInput::filterCommaNum($id);
		if( is_array($id)){
			$where = 'AuthorizeID in('.implode(',', $id).')';
		}else{
			$where = "AuthorizeID=$id";
		}
		if( $IsAuthorize != 0 ) $IsAuthorize = 1;
		$data['IsAuthorize'] = intval($IsAuthorize);
		$data['OperatorID'] = intval($OperatorID);
		if( $AgentID != -1){
			$data['CustomerID'] = intval($AgentID);
		}
		$result = $this->where($where)->setField($data);
		if( $result === false ){
			return false;
		}else{
			return true;
		}
	}
	
	//查询域名是否授权
	function getAuthorize($host){
		$host = strtolower(trim($host)); //转化为小写
        $host = addslashes(stripslashes($host));
        $host = YdInput::checkLetterNumber($host);
		if( substr($host, 0, 4) == 'www.' ){ //输入的域名以3w开头
			$nowww = substr($host, 4);
			$www = $host;
		}else{
			$nowww = $host;
			$www = 'www.'.$host;
		}
		
		$where = "IsAuthorize=1 and Host in('$nowww','$www') ";
		$result = $this->where($where)->find();
		if( empty($result) ){
			return false;
		}else{
			return $result;
		}
	}
	
	//获取所有客户列表(包含代理、直接客户、业务合作)
	function getCustomerData($memberID=-1){
		$m = D('Admin/Member');
		$m->table($m->tablePrefix.'member a');
		$m->join($m->tablePrefix.'member_group b On a.MemberGroupID = b.MemberGroupID');
		$where = "a.MemberGroupID > 100";   //客户分组ID大于100的都是客户
		if($memberID != -1){
            $memberID = intval($memberID);
			$where .= " and a.InviterID=$memberID";
		}
		$m->field('a.MemberID,a.MemberGroupID,a.MemberName,a.MemberRealName,a.MemberEmail,a.MemberMobile,b.MemberGroupName');
		$data = $m->where($where)->select();
		return $data;
	}
	
	//获取所有代理列表
	function getAgentData($memberID=-1){
		$m = D('Admin/Member');
		$m->table($m->tablePrefix.'member a');
		$m->join($m->tablePrefix.'member_group b On a.MemberGroupID = b.MemberGroupID');
		$where = "a.MemberGroupID in(110,111,112,113,114,115)";   //代理客户分组ID
		if($memberID != -1){
            $memberID = intval($memberID);
			$where .= " and a.InviterID=$memberID";
		}
		$m->field('a.MemberID,a.MemberGroupID,a.MemberName,a.MemberRealName,a.MemberEmail,a.MemberMobile,b.MemberGroupName');
		$data = $m->where($where)->select();
		return $data;
	}
	
	//获取所有操作员（客服）
	function getOperatorData(){
		$m = D('Admin/Member');
		$where['MemberGroupID'] = 100;   //客服分组ID:100
		$m->field('MemberID,MemberName,MemberRealName,MemberEmail,MemberMobile');
		$data = $m->where($where)->select();
		return $data;
	}
	
	//返回所有操作员（客服）的关联数组 MemberID=>MemberName
	function getAssoOperatorData(){
		$m = D('Admin/Member');
		$where['MemberGroupID'] = 100;   //客服分组ID:100
		$data = $m->where($where)->getField('MemberID,MemberRealName');
		return $data;
	}

	//按操作系统统计授权数
	function statNumByOS(){
		$where = "OS!=''";
		$this->field('OS, count(*) as Total');
		$data = $this->where( $where )->group('OS')->order('Total desc')->select();
		if($data){
			$total = 0;
			foreach ($data as $v){
				$total += $v['Total'];
			}
			$n = is_array($data) ? count($data) : 0;
			for($i = 0; $i < $n; $i++){
				$data[$i]['Percent'] = number_format($data[$i]['Total']*100/$total,2);
			}
		}
		return $data;
	}
}
<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class MemberModel extends Model{
	protected $_validate = array(
			array('MemberName', 'require', '{%UserNameRequired}'),
			array('MemberName', '', '{%UserNameExist}', '0', 'unique'),
			array('MemberPassword', 'require', '{%PasswordRequired}'),
			
			array('MemberEmail', '', '{%MemberEmailExist}', '2', 'unique'),
			array('MemberMobile', '', '{%MemberMobileExist}', '2', 'unique'),
	);
	
	function getMember($offset = -1, $length = -1, $keywords='', $isCheck=-1, $memberGroupID = -1, $p=array()){
		$this->field('a.*,b.MemberGroupName,c.AdminID');
		$this->table($this->tablePrefix.'member a');
		$this->join('Left Join '.$this->tablePrefix.'member_group b On a.MemberGroupID = b.MemberGroupID');
		$this->join('Left Join '.$this->tablePrefix.'admin c On a.MemberID = c.MemberID');
		
		$where = "1=1";
		if( $keywords != ''){
			$keywords = YdInput::checkKeyword($keywords);
			$where .= " and (a.MemberEmail like '%$keywords%' or a.MemberName like '%$keywords%' or a.MemberMobile like '%$keywords%' or a.MemberRealName like '%$keywords%') ";
		}
		
		if($isCheck != -1){
			$isCheck = intval($isCheck);
			$where .= " and a.IsCheck=$isCheck";
		}
		
		if($memberGroupID != -1){
			$memberGroupID = intval($memberGroupID);
			$where .= " and a.MemberGroupID=$memberGroupID";
		}
		
		if( isset($p['IsDistributor']) && $p['IsDistributor'] != -1){
			$where .= " and a.IsDistributor=".intval($p['IsDistributor']);
		}
		
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);	
		}
		$result = $this->where($where)->order('a.MemberID desc')->select();
		return $result;
	}
	
	
	//获取模糊查询个数
	function getCount($keywords='', $isCheck=-1, $memberGroupID = -1, $p=array()){
		$where = "1=1";
		if( $keywords != ''){
            $keywords = YdInput::checkKeyword($keywords);
			$where .= " and (MemberEmail like '%$keywords%' or MemberName like '%$keywords%' or MemberMobile like '%$keywords%' or MemberRealName like '%$keywords%') ";
		}
		if($isCheck != -1){
			$isCheck = intval($isCheck);
			$where .= " and IsCheck=$isCheck";
		}
		if($memberGroupID != -1){
			$memberGroupID = intval($memberGroupID);
			$where .= " and MemberGroupID=$memberGroupID";
		}
		if( isset($p['IsDistributor']) && $p['IsDistributor'] != -1){
			$where .= " and IsDistributor=".intval($p['IsDistributor']);
		}
		$n = $this->where($where)->count();
		return $n;
	}
	
	//获取会员卡信息
	function getMemberCard($offset = -1, $length = -1, $keywords=''){
		$this->field('a.*,b.MemberGroupName,c.AdminID');
		$this->table($this->tablePrefix.'member a');
		$this->join('Left Join '.$this->tablePrefix.'member_group b On a.MemberGroupID = b.MemberGroupID');
		$this->join('Left Join '.$this->tablePrefix.'admin c On a.MemberID = c.MemberID');
	
		$where = "a.CardNumber != '' ";
		if( $keywords != ''){
            $keywords = YdInput::checkKeyword($keywords);
			$where .= " and (a.CardNumber like '%$keywords%' or a.MemberName like '%$keywords%' or a.MemberMobile like '%$keywords%' or a.MemberRealName like '%$keywords%') ";
		}
	
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}
		$result = $this->where($where)->order('a.MemberID desc')->select();
		return $result;
	}
	
	//判断会员卡号是否有效
	function isValidCardNumber($memberID, $cardNumber){
		$memberID = intval($memberID);
		$cardNumber = YdInput::checkLetterNumber($cardNumber);
		$where = "CardNumber='$cardNumber' and MemberID=$memberID";
		$where .= " and IsLock=0 and IsCheck=1";
		$n = $this->where($where)->count();
		if($n>0){
			return true;
		}else{
			return false;
		}
	}
	
	function findMember($MemberID){
		$MemberID = intval($MemberID);
		$this->field('a.*,b.MemberGroupName,b.DiscountRate,c.AdminID');
		$this->table($this->tablePrefix.'member a');
		$this->join('Left Join '.$this->tablePrefix.'member_group b On a.MemberGroupID = b.MemberGroupID');
		$this->join('Left Join '.$this->tablePrefix.'admin c On a.MemberID = c.MemberID');
		$data = $this->where("a.MemberID = $MemberID")->find();
		return $data;
	}
	
	function findMemberByFromUser($fromUser, $field=false, $except=false){
        $fromUser = YdInput::checkLetterNumber($fromUser);
		$where['FromUser'] = $fromUser;
		if(!empty($field)){
		    $field = YdInput::checkTableField($field);
			$this->field($field, $except);
		}
		$result = $this->where($where)->find();
		return $result;
	}
	
	function batchCheckMember( $id = array() , $Check = 0){
        $Check = intval($Check);
		$id = YdInput::filterCommaNum($id);
		$where = 'MemberID in('.implode(',', $id).')';
		if( $Check != 0 ) $Check = 1;
		$result = $this->where($where)->setField('IsCheck', $Check);
		if( $result === false ){
			return false;
		}else{
			return true;
		}
	}
	
	function batchLockMember( $id = array() , $Lock = 0){
        $Lock = intval($Lock);
		$id = YdInput::filterCommaNum($id);
		$where = 'MemberID in('.implode(',', $id).')';
		if( $Lock != 0 ) $Lock = 1;
		$result = $this->where($where)->setField('IsLock', $Lock);
		if( $result === false ){
			return false;
		}else{
			return true;
		}
	}
	
	function batchModifyPwd( $id = array() , $pwd=''){
		$id = YdInput::filterCommaNum($id);
		$where = 'MemberID in('.implode(',', $id).')';
		$result = $this->where($where)->setField('MemberPassword', $pwd);
		if( $result === false ){
			return false;
		}else{
			return true;
		}
	}
	
	function checkLogin($name, $password){
		//$where ="MemberName='$name' and MemberPassword='$password' ";
		//数组写法防止注入
		//$map['a'] =array('like',array('%thinkphp%','%tp'),'OR');
		//$map['b'] =array('notlike',array('%thinkphp%','%tp'),'AND');
		//生成的查询条件就是：(a like '%thinkphp%' OR a like '%tp') AND (b not like '%thinkphp%' AND b not like '%tp')
		
		$where['MemberName|MemberEmail|MemberMobile'] = $name;
		//$where['MemberPassword']=$password;
		$r1 = $this->field('IsSystem', true)->where($where)->find();
		if(!$r1  || !yd_password_verify($password, $r1['MemberPassword']) ) {
			$where1['MemberName|MemberEmail|MemberMobile'] = $name;
			$this->where($where1)->setInc('LoginFailCount');
			return 0;
		}
        unset($r1['MemberPassword']);
		if( $r1['IsLock'] == 1 ) return 1;
		if( $r1['IsCheck'] == 0 ) return 3;
		//认证成功，返回当前用户信息
        $m = D('Member/MemberGroup');
		$r2 = $m->find( $r1['MemberGroupID'] );
		if( !$r2 ) return 2;
		return array_merge($r1, $r2);
	}
	
	function getLoginFailCount($name){
		$where['MemberName|MemberEmail|MemberMobile'] = $name;
        $n = $this->where($where)->getField('LoginFailCount');
		return (int)$n;
	}
	
	function getQuestion($MemberName){
		$where['IsCheck'] = 1;
		$where['IsLock'] = 0;
		$where['MemberName|MemberEmail|MemberMobile'] = $MemberName;
		$question = $this->where($where)->getfield('MemberQuestion');
		return $question;
	}
	
	//获取找回密码有关数据
	function getFindPwdData($MemberName){
		$where['IsCheck'] = 1;
		$where['IsLock'] = 0;
		$where['MemberName|MemberEmail|MemberMobile'] = $MemberName;
		$result = $this->where($where)->field('MemberID,MemberName,MemberMobile,MemberEmail,MemberQuestion')->find();
		return $result;
	}
	
	//密码重置
	function resetPwd($MemberName, $default = '123456'){
		$where['IsCheck'] = 1;
		$where['IsLock'] = 0;
		$where['MemberName|MemberEmail|MemberMobile'] = $MemberName;
        $default = yd_password_hash($default);
		$result = $this->where($where)->setfield('MemberPassword',$default);
		return $result;
	}
	
	//管理员登录后
	function UpdateLogin($memberID){
		/*
		$data["LastLoginTime"] = date('Y-m-d H:i:s');
		$data["LastLoginIP"] = get_client_ip();
		$result = $this->where("MemberID=$memberID")->setField($data);
		return $result;
		*/
		$time = date('Y-m-d H:i:s');
		$ip = get_client_ip();
		$sql = "UPDATE {$this->tablePrefix}member SET LastLoginTime='$time',LastLoginIP='$ip',";
		$sql .= "LoginFailCount=0,LoginCount=LoginCount+1 WHERE MemberID=".intval($memberID);
		$result = $this->execute($sql);
		return $result;
	}
	
	//获取会员邮箱帐号,返回一维数组
	function getMemberMail($GroupID = array()){
		$GroupID = YdInput::filterCommaNum($GroupID);
		$where = 'MemberGroupID in('.implode(',', $GroupID).')';
		$where .= " and IsLock=0 and IsCheck=1 and MemberEmail!=''";
		$data = $this->field('MemberEmail')->where($where)->select();
        $t = array();
		foreach ($data as $v){
			$t[] = $v['MemberEmail'];
		}
		unset($data);
		return $t;
	}
	
	//插入微信用户信息 $subscribe:是否是订阅消息
	function updateWxUser($fromUser, $ischeck=true){
        $fromUser = YdInput::checkLetterNumber($fromUser);
	    //return true; //2021-05 关注不再添加会员
		$where['FromUser'] = $fromUser;
		if( $ischeck === true ){ //审核通过
			$id = $this->where($where)->getField('MemberID');
			if( empty($id) ){ //微信号不存在，则添加微信帐号信息
				$wx['FromUser'] = $fromUser;
				$wx['IsCheck'] = 1;
				$wx['MemberGroupID'] = 2; //分配到微信游客分组（ID=2）里去
				$wx['RegisterTime'] = date('Y-m-d H:i:s');
				$this->data($wx)->add();
			}else{
				$this->where($where)->setField('IsCheck', 1);
			}
		}else{ //取消审核
			$this->where($where)->setField('IsCheck', 0);
		}
	}
	
	//判断当前微信用户是否存在
	function wxUserExist($fromUser){
        $fromUser = YdInput::checkLetterNumber($fromUser);
		if( strlen($fromUser) < 10 ) return false;
		$where['FromUser'] = $fromUser;
		$where['IsCheck'] = 1;
		$where['IsLock'] = 0;
		$n = $this->where($where)->count();
		if( $n > 0 ){
			return true;
		}else{
			return false;
		}
	}
	
	//更新当前用户的经度纬度信息longitude:经度，latitude：纬度
	function wxUpdatePosition($fromUser,$longitude,$latitude){
        $fromUser = YdInput::checkLetterNumber($fromUser);
        $longitude = YdInput::checkLetterNumber($longitude);
        $latitude = YdInput::checkLetterNumber($latitude);
		if( $longitude=='' ||  $latitude== '') return false;
		$position = time().','.$longitude.','.$latitude; //格式：时间,经度,纬度
		$where['FromUser'] = $fromUser;
		return $this->where($where)->setField('Position', $position);
	}
	
	//返回当前经度纬度，若不存在或过期，则返回false
	function wxGetPosition($fromUser){
        $fromUser = YdInput::checkLetterNumber($fromUser);
		$where['FromUser'] = $fromUser;
		$position = $this->where($where)->getField('Position');
		if( strpos($position, ',') === false ) return false;
		$position = explode(',', $position);
		$cachetime = 60*60; //缓存时间为15分钟
		$span = time()-(int)$position[0];
		if( $span < $cachetime ){
			return $position;
		}else{
			return false;
		}
	}
	
	//生成一个新卡号
	function makeCardNumber(){
		$where = "CardNumber != '' ";
		$max = $this->where($where)->max('CardNumber');
		if(empty($max)){
			$max = '10000000';
		}else{
			$max = $max + 1;
		}
		return $max;
	}
	
	//判断当前用户是否已经领卡
	function hasCard($fromUser){
        $fromUser = YdInput::checkLetterNumber($fromUser);
		$where = "CardNumber != '' and FromUser='$fromUser'";
		$n = $this->where($where)->count();
		if($n>0){
			return true;
		}else{
			return false;
		}
	}
	
	function getCardCount($keywords=''){
		$where = "CardNumber!=''";
		if( $keywords != ''){
			$keywords = addslashes(stripslashes($keywords));
            $keywords = YdInput::checkKeyword( $keywords );
			$where .= " and (CardNumber like '%$keywords%' or MemberName like '%$keywords%' or MemberMobile like '%$keywords%' or MemberRealName like '%$keywords%') ";
		}
		$n = $this->where($where)->count();
		return $n;
	}
	
	function findMemberByOpenID($openid){
		$this->field('a.*,b.MemberGroupName,c.AdminID');
		$this->table($this->tablePrefix.'member a');
		$this->join('Left Join '.$this->tablePrefix.'member_group b On a.MemberGroupID = b.MemberGroupID');
		$this->join('Left Join '.$this->tablePrefix.'admin c On a.MemberID = c.MemberID');
		
		$where['a.OpenID'] = $openid;
		$data = $this->where( $where )->find();
		return $data;
	}
	
	//$name可能是昵称、email、手机号码
	function bindMember($name, $pwd, $openid){
		$where['MemberName|MemberEmail|MemberMobile'] = $name;
		$where['MemberPassword'] = yd_password_hash($pwd);
		$result = $this->where( $where )->setField('OpenID', $openid);
		return $result;
	}
	
	/**
	 * 判断手机号码是否存在
	 * @param string $mobile
	 */
	function hasMobile($mobile){
		$where['MemberMobile'] = $mobile;
		$memberID = $this->where($where)->getField('MemberID');
		if( empty( $memberID ) ){
			return false;
		}else{
			return true;
		}
	}
	
	//========================代理平台 开始=========================================
	//获取客户数，仅用于代理平台
	function getCustomer($offset = -1, $length = -1, $keywords='', $isCheck=-1, $inviterID = -1, $p = array() ){
		$this->field('a.*,b.MemberGroupName');
		$this->table($this->tablePrefix.'member a');
		$this->join('Left Join '.$this->tablePrefix.'member_group b On a.MemberGroupID = b.MemberGroupID');
		$where = $this->getCustomerWhere($keywords, $isCheck, $inviterID, $p);
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}
		$result = $this->where($where)->order('a.RegisterTime desc')->select();
		return $result;
	}
	
	//仅用于代理平台
	function getCustomerCount($keywords='', $isCheck=-1, $inviterID = -1, $p = array()){
		$this->table($this->tablePrefix.'member a');
		$where = $this->getCustomerWhere($keywords, $isCheck, $inviterID, $p);
		$n = $this->where($where)->count();
		return $n;
	}
	
	//仅用于代理平台
	function getCustomerWhere($keywords='', $isCheck=-1, $inviterID = -1, $p = array()){
		$where = "a.MemberGroupID!=100";  //认为大于100的都是客户
		if( $keywords != ''){
            $keywords = YdInput::checkKeyword( $keywords );
			$where .= " and (a.MemberEmail like '%$keywords%' or a.MemberName like '%$keywords%' or a.MemberMobile like '%$keywords%' or a.MemberRealName like '%$keywords%') ";
		}
		if($isCheck != -1){
            $isCheck = intval($isCheck);
			$where .= " and a.IsCheck={$isCheck}";
		}
		if($inviterID != -1){
            $inviterID = intval($inviterID);
			$where .= " and a.InviterID={$inviterID}";
		}
		if( isset($p['Province']) && $p['Province'] != -1){
            $p['Province'] = intval($p['Province']);
			$where .= " and a.Province={$p['Province']}";
		}
		return $where;
	}
	
	//获取客户分组
	function getCustomerGroup(){
		$m = D('Admin/MemberGroup');
		$where['MemberGroupID'] = array('gt',100);
		$data = $m->where($where)->order("MemberGroupID asc")->select();
		return $data;
	}
	
	//仅用于代理平台
	function findCustomer($MemberID, $inviterID=-1){
		$where['MemberID'] = intval($MemberID);
		$whrere['IsEnable'] = 1;
		if( $inviterID != -1){
			$whrere['InviterID'] = intval($inviterID);
		}
		$data = $this->where($where)->find();
		return $data;
	}
	//========================代理平台 结束=========================================
	
	/**
	 * 检查指定账号是否存在
	 * @param unknown_type $name
	 * @param unknown_type $password
	 * @return number|multitype:
	 */
	function memberExist($name, $password){
		$where['MemberName|MemberEmail|MemberMobile'] = $name;
		//$where['MemberPassword']=$password;
		$where['IsCheck']=1;
		$where['IsLock']=0;
		$data = $this->where($where)->field('MemberPassword,MemberID')->find();
        $hash = $data['MemberPassword'];
        $b = yd_password_verify($password, $hash);
        if( $b ){
            return $data['MemberID'];
        }else{
            return false;
        }
	}
	
	/**
	 * 判断微信是否绑定
	 * @param unknown_type $name
	 * @return unknown
	 */
	function hasBind($FromUser){
        $FromUser = YdInput::checkLetterNumber($FromUser);
		$where['FromUser']=$FromUser;
		$data = $this->where($where)->field('MemberID,MemberName,MemberEmail,MemberMobile')->find();
		if( !empty($data['MemberEmail']) || !empty($data['MemberMobile']) || !empty($data['MemberName'])){
			$IsBind = 1;
		}else{
			$IsBind = 0;
		}
		return $IsBind;
	}
	
	/**
	 * 通过关键词过去MemberID
	 * @param string $keywords
	 * @return int
	 */
	function getMemberIDByKeywords($keywords){
		if(empty($keywords)) return false;
        $keywords = YdInput::checkKeyword($keywords);
		$where['MemberName|MemberEmail|MemberMobile'] = $keywords;
		$id = $this->where($where)->getField('MemberID');
		return $id;
	}
	
	/**
	 * 根据分组ID获取所有会员ID列表
	 * @param int $MemberGroupID -1表示所有分组
	 * @param int $ReturnType 1：表示返回的ID以逗号隔开，2：返回ID数组
	 */
	function getMemberIDList($MemberGroupID=-1, $ReturnType=1){
		//$MemberGroupID = intval($_REQUEST['MemberGroupID']);
        /*
		$field="GROUP_CONCAT(MemberID) as MemberID";
		if($MemberGroupID != -1){ //全部
			$where['MemberGroupID'] = intval($MemberGroupID);
			$result = $this->where($where)->getField($field);
		}else{
			$result = $this->field($field)->getField($field);
		}

		if( $ReturnType==2 && !empty($result) ){
			$result = explode(',', $result);
		}
        */

        // 为了跨数据库，去掉GROUP_CONCAT函数调用
        $where = array();
        if($MemberGroupID != -1){ //全部
            $where['MemberGroupID'] = intval($MemberGroupID);
        }
        $result = $this->where($where)->groupField('MemberID', $ReturnType);

		return $result;
	}
	
	/**
	 * 获取分销商列表
	 * @param int $offset
	 * @param int $length
	 * @param array $p
	 * @return array
	 */
	function getDistributor($offset = -1, $length = -1, $p=array()){
		$this->field('a.*,b.*');
		$this->table($this->tablePrefix.'member a');
		$this->join('Left Join '.$this->tablePrefix.'distributor_level b On a.DistributorLevelID = b.DistributorLevelID');
	
		$where = "a.IsCheck=1 and a.IsLock=0 ";
		if( !empty($p['Keywords'])){
			$keywords = addslashes(stripslashes($p['Keywords']));
            $keywords = YdInput::checkKeyword($keywords);
			$where .= " and (a.MemberEmail like '%$keywords%' or a.MemberName like '%$keywords%' or a.MemberMobile like '%$keywords%' or a.MemberRealName like '%$keywords%') ";
		}
		
		//是否是分销商
		if( isset($p['IsDistributor']) && $p['IsDistributor'] !=-1 ){
			$where .= " and a.IsDistributor=".intval($p['IsDistributor']);
		}
		
		//InviterID=0，表示顶级分销商
		if( isset($p['InviterID']) && $p['InviterID'] !=-1 ){
			$where .= " and a.InviterID=".intval($p['InviterID']);
		}
	
		//分销商等级
		if( isset($p['DistributorLevelID']) && $p['DistributorLevelID'] !=-1 ){
			$DistributorLevelID = intval($p['DistributorLevelID']);
			$where .= " and a.DistributorLevelID=$DistributorLevelID";
		}
	
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}
		$result = $this->where($where)->order('a.DistributorTime desc')->select();
		
		if(!empty($result)){
			$m = D('Admin/Cash');
			$n = is_array($result) ? count($result) : 0;
			for($i=0; $i<$n; $i++){
				$MemberID = $result[$i]['MemberID'];
				$result[$i]['TotalCommission'] = $m->getTotalCommission($MemberID );
				$Donwline = $this->getDownline($MemberID);
				$result[$i]['DownlineCount1'] = count($Donwline[1]);
				$result[$i]['DownlineCount2'] = count($Donwline[2]);
				$result[$i]['DownlineCount3'] = count($Donwline[3]);
			}
		}
		return $result;
	}
	
	
	//获取模糊查询个数
	function getDistributorCount($p=array()){
		$where = "IsCheck=1 and IsLock=0 and IsDistributor=1";
		if( !empty($p['Keywords'])){
			$keywords = addslashes(stripslashes($p['Keywords']));
            $keywords = YdInput::checkKeyword($keywords);
			$where .= " and (MemberEmail like '%$keywords%' or MemberName like '%$keywords%' or MemberMobile like '%$keywords%' or MemberRealName like '%$keywords%') ";
		}

		//分销商等级
		if( isset($p['DistributorLevelID']) && $p['DistributorLevelID'] !=-1 ){
			$DistributorLevelID = intval($p['DistributorLevelID']);
			$where .= " and DistributorLevelID = $DistributorLevelID";
		}
		$n = $this->where($where)->count();
		return $n;
	}
	
	/**
	 * 查找分销商
	 * @param unknown_type $MemberID
	 * @param unknown_type $p
	 * @return number
	 */
	function findDistributor($MemberID, $p=array()){
		$where = "a.IsCheck=1 and a.IsLock=0 and a.IsDistributor=1 ";
		$where .= " and MemberID=".intval($MemberID);
		$result = $this->where($where)->find();
		return $result;
	}
	
	/**
	 * 设置分销商等级
	 * @param int $MemberID 可能为数组
	 * @param int $DistributorLevelID
	 * @return array
	 */
	function setDistributorLevel($MemberID, $DistributorLevelID){
		$where = "IsCheck=1 and IsLock=0 and IsDistributor=0 ";
		$id = YdInput::filterCommaNum($MemberID);
		if(is_array($id)){
			$where .= ' and MemberID in('.implode(',', $id).')';
		}else{
			$where .= " and MemberID={$id}";
		}
		$time = date('Y-m-d H:i:s');
		$data = $this->where($where)->field('MemberID,InviteCode')->select();
        $result = false;
		foreach ($data as $v){
			if(empty($v['InviteCode'])){
				$update['InviteCode'] = MakeInviteCode();
			}
			$update['IsDistributor'] = 1; //是否是分销商
			$update['DistributorLevelID'] = intval($DistributorLevelID); //分销商等级
			$update['DistributorTime'] = $time; //成为分销商的时间
			$result = $this->where("MemberID={$v['MemberID']}")->setField($update);
		}
		return $result;
	}
	
	/**
	 * 获取会员的下线
	 * @param int $MemberID
	 * @param int $Level 3表示三级下线，2：表示2级下线，1：表示1级下线
	 */
	function getDownline($MemberID, $Level=3){
        $MemberID = intval($MemberID);
        $Level = intval($Level);
		$result = array('1' => array(),'2' => array(),'3' => array());
		$data[] = array('MemberID'=>$MemberID);
		if($Level==1){
			$result[1] = $this->_getDownline($data);
		}elseif($Level==2){
			$result[1] = $this->_getDownline($data);
			$result[2] = $this->_getDownline($result[1]);
		}else{ //默认为3级
			$result[1] = $this->_getDownline($data);
			$result[2] = $this->_getDownline($result[1]);
			$result[3] = $this->_getDownline($result[2]);
		}
		return $result;
	}
	
	/**
	 * 获取会员上线
	 * @param int $MemberID
	 * @param int $Level
	 * @param string $field 获取指定上线属性字段
	 */
	function getUpline($MemberID, $Level=3, $field=""){
		$result = array('1' => array(),'2' => array(),'3' => array());
        $Level = intval($Level);
		$where['MemberID'] = intval($MemberID);
		$InviterID1 = $this->where($where)->getField('InviterID');
		if(empty($field)) $field='MemberID,MemberName,InviterID,IsCheck,IsLock,DistributorLevelID,MemberMobile,MemberEmail';
		$field = YdInput::checkTableField($field);
        $InviterID2 = 0;
        $InviterID3 = 0;
		//1级上线
		if($Level >= 1 && $InviterID1>0){
			$result[1] = $this->where("MemberID=$InviterID1")->field($field)->find();
			$InviterID2 =  !empty($result[1]) ? $result[1]['InviterID'] : 0;
		}
		//2级上线
		if($Level >= 2 && $InviterID2>0){
			$result[2] = $this->where("MemberID=$InviterID2")->field($field)->find();
			$InviterID3 =  !empty($result[2]) ? $result[2]['InviterID'] : 0;
		}
		//3级上线
		if($Level >= 3 && $InviterID3>0){
			$result[3] = $this->where("MemberID=$InviterID3")->field($field)->find();
		}
		return $result;
	}
	
	/**
	 * 仅获取下一级数据
	 * @param int $data 二维数组存储MemberID
	 * @return array
	 */
	private function _getDownline($data){
		$all = array();
		foreach ($data as $v){
			$MemberID = intval($v['MemberID']);
			$where = "IsCheck=1 and IsLock=0 and InviterID=".$MemberID;
			$result = $this->where($where)->select();
			if(!empty($result)){
				if(empty($all)){
					$all = $result;
				}else{
					foreach ($result as $v1){
						array_push($all, $v1);
					}
				}
			}
		}
		return $all;
	}
	
	/**
	 * 获取下一级，注意：下一级不一定是代理
	 * @param int $MemberID
	 * @return array
	 */
	function getNextDistributionRelation($MemberID){
		$where = "IsCheck=1 and IsLock=0 and InviterID=".intval($MemberID);
		$result = $this->where($where)->select();
		return $result;
	}
	
	/**
	 * 获取提现密码
	 * @param int $MemberID
	 */
	function getCashPassword($MemberID){
		$where = "MemberID=".intval($MemberID);
		$result = $this->where($where)->getField('CashPassword');
		return $result;
	}

    /**
     * 获取会员头像
     * @param $MemberID
     */
    function getMemberAvatar($MemberID){
        $where = "MemberID=".intval($MemberID);
        $result = $this->where($where)->getField('MemberAvatar');
        return $result;
    }

    /**
     * 判断用户旧密码是否存在
     * $password：密码明文
     */
    function isOldPasswordCorrect($MemberID, $password){
        $where['MemberID'] = intval($MemberID);
        $hash = $this->where($where)->getField('MemberPassword');
        $b = yd_password_verify($password, $hash);
        if($b){
            return true;
        }else{
            return false;
        }
    }

    /**
     * @param $MemberID
     * @param $password 密码明文
     */
    function isCashPasswordCorrect($MemberID, $password){
        $where['MemberID'] = intval($MemberID);
        $hash = $this->where($where)->getField('CashPassword');
        $b = yd_password_verify($password, $hash);
        if( $b ){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 密码答案是否正确
     * $MemberAnswer：答案明文
     */
    function isAnswerCorrect($MemberID, $MemberAnswer){
        $where['MemberID'] = intval($MemberID);
        $hash = $this->where($where)->getField('MemberAnswer');
        $b = yd_password_verify($MemberAnswer, $hash);
        if( $b ){
            return true;
        }else{
            return false;
        }
    }
}

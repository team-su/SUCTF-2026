<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved.
 */
class AdminModel extends Model{
	
	/**
	 * 
	 * @param string $name 管理员名称
	 * @param string $password $password: 已加密的md5密码
	 * @return mixed 0: 用户名或密码错误，1：用户被锁定，2:用户组不存在，数组：认证成功
	 */
	function checkLogin($name, $password){
		//数组写法防止注入
		$where['AdminName']=$name;
		//$where['AdminPassword']=$password;
		$r1 = $this->field('IsSystem', true)->where($where)->find();
        //检查锁定时间，登录次数过多（大于8次）自动锁定30分钟
        if((int)$r1['LoginFailCount'] >= 8){
            $n = (int)yd_date_diff($r1['LoginFailTime'], date("Y-m-d H:i:s"), 'm');
            if($n < 30){ //锁定30分钟
                return 1;  //1表示锁定
            }
        }

		if(!$r1 || !yd_password_verify($password, $r1['AdminPassword'])) { //登录失败，更新计数器
			//$where1['AdminName'] = $name;
			//$this->where($where1)->setInc('LoginFailCount');
			$this->_updateLoginFailData($name);
			return 0;
		}

		unset($r1['AdminPassword']);
		if( $r1['IsLock'] == 1 ) return 1;
		//认证成功，返回当前用户信息
		$r2 = D('Admin/AdminGroup')->findAdminGroup( $r1['AdminGroupID'] );
		if( !$r2 ) return 2;
		return array_merge($r1, $r2);
	}

    /**
     * 更新登录失败数据
     */
	private function _updateLoginFailData($name){
        $name = addslashes(stripslashes($name));
	    $now = date("Y-m-d H:i:s");
        $sql = "UPDATE {$this->tablePrefix}admin SET LoginFailTime='{$now}', LoginFailCount=LoginFailCount+1 WHERE AdminName='{$name}'";
        $result = $this->execute($sql);
        return $result;
    }

    /**
     * 检查UC登录
     */
	function checkLoginUcOpenID($UcOpenID){
        $UcOpenID = YdInput::checkLetterNumber($UcOpenID);
        $where['UcOpenID'] = $UcOpenID;
        $r1 = $this->field('IsSystem', true)->where($where)->find();
        if(empty($r1) || $r1['IsLock']==1 ) {
            return false;
        }
        $m = D('Admin/AdminGroup');
        $r2 = $m->findAdminGroup( $r1['AdminGroupID'] );
        if(empty($r2)) return false;
        $result = array_merge($r1, $r2);
        return $result;
    }
	
	/**
	 * 判断用户是否存在
	 * @param string $name
	 * @param string $password 密码明文
	 */
	function exist($name, $password){
		$where['AdminName']=$name;
		$hash = $this->where($where)->getField('AdminPassword');
		$b = yd_password_verify($password, $hash);
		if( $b ){
			return true;
		}else{
			return false;
		}
	}
	
	//管理员登录后
	function UpdateLogin($adminID){
		/*
		$data["LastLoginTime"] = date('Y-m-d H:i:s'); 
		$data["LastLoginIP"] = get_client_ip();
		$data["LoginFailCount"] = 0; //登录失败计数器清0
		$result = $this->where("adminID=$adminID")->setField($data);
		return $result;
		*/
        try{
            $time = date('Y-m-d H:i:s');
            $ip = get_client_ip();
            $sql = "UPDATE {$this->tablePrefix}admin SET LastLoginTime='$time',LastLoginIP='$ip',";
            $sql .="LoginFailCount=0, LoginCount=LoginCount+1 WHERE AdminID=".intval($adminID);
            $result = $this->execute($sql);
            return $result;
        }catch(Exception $e){
            return false;
        }
	}
	
	function getTopMenu($adminGroupID){
		$where['AdminGroupID'] = intval($adminGroupID);
		$menuIDList = $this->table($this->tablePrefix.'admin_group')->where($where)->getField('MenuPurview');
		if(!$menuIDList) return false;
		$this->table($this->tablePrefix.'menu')->field('MenuID,MenuName,MenuUrl,Parent,MenuType,IsActive');
		if( strtolower($menuIDList) == 'all'){
			$this->where('MenuGroup=1 and IsShow=1 and Parent=0');
		}else{
			$this->where("MenuGroup=1 and IsShow=1 and Parent=0 And MenuID in ($menuIDList)");
		}
		$result = $this->order("MenuOrder asc,MenuID asc")->select();
		return $result;
	}
	
	function getAdmin($offset = -1, $length = -1){
		$this->field('a.*, c.MemberName,c.MemberGender, b.AdminGroupName');
		$this->table($this->tablePrefix.'admin a');
		$this->join('Left Join '.$this->tablePrefix.'admin_group b On a.AdminGroupID = b.AdminGroupID');
		$this->join('Left Join '.$this->tablePrefix.'member c On a.MemberID = c.MemberID');
		if( is_numeric($offset) && is_numeric($length) && $offset >= 0 && $length > 0){
			$this->limit($offset.','.$length);
		}
		$result = $this->order('a.MemberID desc')->select();
		return $result;
	}
	
	function batchModifyPwd( $id = array() , $pwd=''){
		$id = YdInput::filterCommaNum($id);
		$where = 'AdminID != 1 and AdminID in('.implode(',', $id).')';
		$result = $this->where($where)->setField('AdminPassword', $pwd);
		return $result;
	}
		
	function batchLock( $id = array() , $Lock = 0){
		$id = YdInput::filterCommaNum($id);
		$where = 'AdminID != 1 and AdminID in('.implode(',', $id).')';
        $Lock = intval($Lock);
		if( $Lock != 0 ) $Lock = 1;
		$result = $this->where($where)->setField('IsLock', $Lock);
		return $result;
	}
	
	//更新并返回当前登录失败次数
	function getLoginFailCount($adminName){
		$where['AdminName'] = $adminName;
		$n = $this->where($where)->getField('LoginFailCount');
		if( !is_numeric($n) ) $n = 0;
		return $n;
	}
	
	//是否显示验证码
	function hasCode($adminName){
		$maxLoginFailCount = 5; //最大错误登录次数
		$n = $this->getLoginFailCount($adminName);
		$showCode = ( $n > $maxLoginFailCount ) ? 1 : 0;
		return $showCode;
	}

    /**
     * 根据md5手机号码字符串获取真实手机
     */
    function getRealAdminName($md5AdminName){
        $md5AdminName = YdInput::checkLetterNumber($md5AdminName);
        if(32 !== strlen($md5AdminName)) return '';
        /*
        $where = "MD5(AdminName)='{$md5AdminName}'";
        $name = $this->where($where)->getField('AdminName');
        if(empty($name)) {
            $name='';
        }
        */
        //不使用数据库内置的md5
        $name = '';
        $data = $this->field("AdminName")->select();
        foreach($data as $k=>$v){
            if($md5AdminName == md5($v['AdminName'])){
                $name = $v['AdminName'];
                break;
            }
        }
        return $name;
    }
}

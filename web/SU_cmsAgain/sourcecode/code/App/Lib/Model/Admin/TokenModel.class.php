<?php
class TokenModel extends Model{
	/**
	 * 创建Token
	 * @param int $MemberID
	 */
	function createToken($MemberID){
		//$token = rand_string(32, 999);，经测试，当达到4万就会一定会产生重复
		$token = yd_make_token();
		$data['Token'] = $token;
		$data['Timestamp'] = time();
		$data['MemberID'] = intval($MemberID);
		$this->add($data);
		return $token;
	}
	
	/**
	 * 退出登陆，删除令牌
	 * @param string $token
	 * @return bool
	 */
	function deleteToken($token){
		//可能存在的问题：某些凭据退出后没有删除，会造成凭据越来越多
		//同时删除超过7天的Token
		$token = addslashes(stripslashes($token));
        $token = YdInput::checkHtmlName($token);
		$day7TimeStamp = time() - 7*24*3600; //7天前的时间戳
		$where = "Token='{$token}' or TimeStamp<{$day7TimeStamp}";
		$result = $this->where($where)->delete();
		return $result;
	}
}


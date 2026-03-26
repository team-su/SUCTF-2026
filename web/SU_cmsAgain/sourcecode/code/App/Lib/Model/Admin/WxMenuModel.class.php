<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */
class WxMenuModel extends Model {

	protected $_validate = array();
	
	/**
	 * 获取微信自定义菜单
	 */
	function getMenu($IsEnable=-1){
		$IsEnable = intval($IsEnable);
		$where = "a.Parent=0";
		if( $IsEnable != -1){
			$where .= " and a.IsEnable={$IsEnable}";
		}
		
		$this->table($this->tablePrefix.'wx_menu a');
		$this->field('a.*, b.TypeName,b.IsReply');
		$this->join(' Left Join '.$this->tablePrefix.'wx_type b On a.TypeID = b.TypeID and b.IsEnable=1');
		
		$result = $this->where($where)->order('a.MenuOrder asc,a.MenuID asc')->select();
		$all = array();
		$n = is_array($result) ? count($result) : 0;
		for($i = 0; $i < $n; $i++){
			$all[] =  $result[$i];
			$temp = $this->getSubMenu($result[$i]['MenuID'], $IsEnable);
			if(!empty($temp)){ //若$temp为未定义，array_merge返回空
				$all = array_merge($all, $temp);
			}
		}
		unset($result);
		return $all;
	}
	
	/**
	 * 获取微信菜单数据结构
	 * $json : ture: 直接获取json，否则返回数组
	 */
	function getWxMenu($json=true){
		$d1 = $this->getSubMenu(0, 1);  //获取一级菜单
		$menu = array( "button"=>array() );
		$n1 = 0;
		foreach ($d1 as $v){
			$d2 = $this->getSubMenu($v['MenuID'], 1);  //获取一级菜单下的二级菜单
			if( empty($d2) ){ //无子菜单=======
				$name = urlencode($v['MenuName']);
				if( $v['IsReply']==1 ){  //click
					$menu['button'][$n1] = array('type'=>'click','name'=>$name ,'key'=>$v['MenuID']);
				}else{ //view
					if(8==$v['TypeID']){ //打开小程序
						$menu['button'][$n1] = array(
						    'type'=>'miniprogram', 'name'=>$name ,
                            'url'=>'http://mp.weixin.qq.com',
                            "appid"=>$v['p1'],
                            "pagepath"=>$v['p2']
                        );
					}else{ //打开链接
						$url = $this->getMenuUrl( $v['TypeID'], $v['p1']);
						$menu['button'][$n1] = array('type'=>'view', 'name'=>$name, 'url'=>$url);
					}
				}
			}else{ //有子菜单============
				$name = urlencode($v['MenuName']);
				$menu['button'][$n1] = array('name' => $name );
				foreach($d2 as $v2){
					$name2 = urlencode($v2['MenuName']);
					if( $v2['IsReply']==1 ){ //click
						$menu['button'][$n1]['sub_button'][] = array('type'=>'click', 'name'=>$name2 ,'key'=>$v2['MenuID']);
					}else{ //view
						if(8==$v2['TypeID']){ //打开小程序
							$menu['button'][$n1]['sub_button'][] = array(
							    'type'=>'miniprogram', 'name'=>$name2 ,
                                'url'=>'http://mp.weixin.qq.com',
                                "appid"=>$v2['p1'], "pagepath"=>$v2['p2']
                            );
						}else{ //打开链接
							$url = $this->getMenuUrl( $v2['TypeID'], $v2['p1']  );
							$menu['button'][$n1]['sub_button'][] = array('type'=>'view', 'name'=>$name2 ,'url'=>$url);
						}
					}
				}
			}
			$n1++;
		}
		unset($d1, $d2);
		$count = is_array($menu['button']) ? count($menu['button']) : 0;
		if($count > 0 ){
			if($json){ //转换为json后返回
				$menu = json_encode($menu);
				$menu = urldecode($menu);
			}
			return $menu;
		}else{
			return false;
		}
	}
	
	/**
	 * 获取菜单url
	 */
	private function getMenuUrl($typeID, $p1){
        $typeID = intval($typeID);
        $url = '';
		switch ($typeID){
			case 4:  //频道URL
				$url = WxChannelUrl($p1);
				break;
			case 5: //第三方应用
				$url = $p1;
				break;
			case 6:  //转向链接
				$url = $p1;
				break;
		}
		return $url;
	}
	
	/**
	 * 获取指定菜单的二级菜单
	 * @param int $ParentID
	 * @param int $IsEnable
	 * @return array
	 */
	function getSubMenu($Parent, $IsEnable=-1){
        $Parent = intval($Parent);
		$where = "a.Parent={$Parent}";
		if( $IsEnable != -1){
			$IsEnable = intval($IsEnable);
			$where .= " and a.IsEnable={$IsEnable}";
		}
		$this->table($this->tablePrefix.'wx_menu a');
		$this->field('a.*, b.TypeName,b.IsReply');
		$this->join(' Left Join '.$this->tablePrefix.'wx_type b On a.TypeID = b.TypeID and b.IsEnable=1');
		
		$result = $this->where($where)->order('a.MenuOrder asc,a.MenuID asc')->select();
		return $result;
	}
	
	function findMenu($MenuID){
        $MenuID = intval($MenuID);
		$data = $this->find($MenuID);
		return $data;
	}
	
	//保存全部数据
	function saveAllMenu( $data ){
		$n = is_array($data['MenuID']) ? count($data['MenuID']) : 0;
		for($i = 0; $i < $n; $i++){
			if( is_numeric( $data['MenuID'][$i] ) ){
				$value['MenuName'] = $data['MenuName'][$i];
				$value['MenuOrder'] = $data['MenuOrder'][$i];
				$this->where('MenuID='.$data['MenuID'][$i])->setField( $value );
			}
		}
	}
	
	/**
	 * 获取指定菜单子菜单数
	 * @param int $MenuID
	 */
	function getSubMenuCount($MenuID){
		$MenuID = intval($MenuID);
		$n = $this->where("Parent={$MenuID}")->count();
		return $n;
	}
	
	function hasChild($MenuID){
		$MenuID = intval($MenuID);
		$n = $this->where("Parent={$MenuID} and IsEnable=1")->count();
		if($n>0){
			return true;
		}else{
			return false;
		}
	}
}

<?php
class InfoAction extends HomeBaseAction {
	//信息首页
    public function index(){
        header("Content-Type:text/html; charset=utf-8");
        $this->display();
    }
    
    //显示信息
    public function read(){
    	header("Content-Type:text/html; charset=utf-8");
    	$m = D('Admin/Info');
    	$data = $m->findinfo($_GET['id']); //可以是id或文件名
    	if(!$data){
    		$this->_empty('read');
    		exit();
    	}
    	$id = $data['InfoID'];
    	//判断频道是否禁用，频道禁用后，不能查看频道的信息
    	$ChannelID = $data['ChannelID'];
    	$mc = D('Admin/Channel');
    	$channel = $mc->findField($ChannelID,'Parent,HasChild,ReadLevel,ChannelModelID,ChannelName,IsEnable,ChannelSName,Html,LinkUrl,ReadTemplate,Title,Keywords,Description');
    	$html = strtolower(basename(dirname($_SERVER['REQUEST_URI'])));
    	if( $channel['IsEnable'] == 0 || strtolower($channel['Html']) != $html){
    		$this->_empty('read');
    		exit();
    	}
    	
    	//计算特殊字段值start=================================================
    	//是否有阅读权限
    	if( !empty($data['ReadLevel']) ){
    		$ReadLevel = $data['ReadLevel'];
    	}else{
    		$ReadLevel = ( !empty($channel['ReadLevel']) || $channel['Parent'] == 0) ? $channel['ReadLevel'] : get_read_level( $channel['Parent'] );
    	}
    	$data['HasReadLevel'] = has_read_level( $ReadLevel ) ? 1 : 0;
    	
    	//搜索引擎优化
    	if( empty($data['Title']) ) {
    		$data['Title'] = !empty($channel['Title']) ? $channel['Title'] : get_title( $channel['Parent'] );
    	}
    	$data['Title'] = YdInput::checkSeoString($data['Title'] );
    	
    	if( empty($data['Keywords']) ) {
    		$data['Keywords'] = !empty($channel['Keywords']) ? $channel['Keywords'] : get_keywords( $channel['Parent'] );
    	}
    	$data['Keywords'] = YdInput::checkSeoString($data['Keywords'] );
    	
    	if( !empty($data['Description']) ){
    		$data['Description'] = YdInput::checkSeoString($data['Description'] );
    	}else if( !empty($data['InfoSContent'])  ){
    		$data['Description'] = YdInput::checkSeoString($data['InfoSContent']);
    	}else if( !empty($data['InfoContent'])  ){
    		$data['Description'] = YdInput::checkSeoString($data['InfoContent'] );
    		$data['Description'] = Left($data['Description'], 120);
    	}
    	
        if( C('HTML_CACHE_ON') ){
    		 $data['InfoHit'] = "<script src='".__GROUP__."/public/incInfoHit?infoid={$id}'></script>";
    	 }else{
    		 $m->IncHit($id); //文章点击次数加1
    		 $data['InfoHit'] = $data['InfoHit']+1;
    	 }
    	 $data['ChannelModelID'] = $channel['ChannelModelID'];
    	 $data['ChannelName'] = $channel['ChannelName'];
    	 $data['ChannelUrl'] = ChannelUrl($ChannelID, $channel['Html'], $channel['LinkUrl']);
    	 $data['ChannelSName'] = $channel['ChannelSName'];
    	 if($data['HasReadLevel'] == 1){
	    	 $data['InfoContent'] = ParseTag( $data['InfoContent'] );
	    	 tag('info_content', $data['InfoContent'] );
    	 }else{  //如果没有权限就提示
    	 	$data['InfoContent'] = L('ReadLevelTip');
    	 }
    	 //频道信息
    	 $data['InfoUrl'] = InfoUrl($id, $data['Html'], $data['LinkUrl'], false, $data['ChannelID']);
    	 $data['FullInfoUrl'] = get_current_url().$data['InfoUrl'];
    	 $data['Parent'] = $channel['Parent'];
    	 $data['HasChild'] = $channel['HasChild'];
    	 $data['TopChannelID'] = ($channel['Parent']==0) ? $ChannelID : $mc->getTopChannel( $ChannelID );
    	 $data['TopHasChild'] = ( $channel['HasChild'] == 1 ||  $channel['Parent'] != 0 ) ? 1 : 0;
    	 $data['DiscountPrice'] = yd_to_money($data['InfoPrice'] * $GLOBALS['DiscountRate']);
    	 $data['InfoPrice'] = yd_to_money($data['InfoPrice']);
    	 $data['ExchangePrice'] = ExchangePrice($id, $data['ExchangePoint'], $data['DiscountPrice']);

    	 unset($data['MemberID']); //禁止使用MemberID
		 $this->assign($data);
    	//计算特殊字段值end==================================================
    	
    	//上一信息，下一信息==========================================
    	$np = $m->getNextPrevious($ChannelID, $id);
    	$this->assign('HasNext',  $np['next']['HasNext']);
    	$this->assign('NextInfoID',  $np['next']['NextInfoID']);
    	$this->assign('NextInfoTitle',  $np['next']['NextInfoTitle']);
    	$this->assign('NextInfoUrl',  $np['next']['NextInfoUrl'] );
    	
    	$this->assign('HasPrevious',  $np['previous']['HasPrevious']);
    	$this->assign('PreviousInfoID',  $np['previous']['PreviousInfoID']);
    	$this->assign('PreviousInfoTitle',  $np['previous']['PreviousInfoTitle']);
    	$this->assign('PreviousInfoUrl',  $np['previous']['PreviousInfoUrl']  );
    	//======================================================

    	$SitePath = $this->_getSitePath( $ChannelID, 1, $data['Parent'], $data['ChannelName'], $data['ChannelUrl']);
    	$this->assign('SitePath', $SitePath);
		
    	$IndexTemplate = $channel['ReadTemplate'];
    	$IndexTemplate = str_ireplace('.html', '', trim($IndexTemplate) );
    	$this->display($IndexTemplate);
    }
}
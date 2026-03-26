<?php
class TagLibYouDian extends TagLib{
	// 标签定义
	protected $tags   =  array(
			// 标签定义： attr 属性列表 close 是否闭合（0 或者1 默认1） alias 标签别名 level 嵌套层次
			'editor1'                => array('attr'=>'id,name,style,width,height,type,bgcolor','close'=>1),
			'channelselect'   => array('attr'=>'channelid,name,id,style,change,firstvalue,firsttext,selectvalue ,hasSingleChannel,hasLinkChannel,menuowner,groupid,languageid','close'=>0),
			'specialselect'   => array('attr'=>'name,id,style,change,firstvalue,firsttext,selectvalue,size','close'=>0),
			'modelselect'   => array('attr'=>'name,multiple,id,size,first,change,selected,dblclick','close'=>0),
			'admingroupselect'   => array('attr'=>'name,id,style,change,firstvalue,firsttext,selectvalue,size','close'=>0),
			'labelcheckbox'  => array('attr'=>'name,id,style,channelmodelid,checked,separator','close'=>0),
			
			'menutoplist'=>array('attr'=>'id,menuowner,offset,length,key,mod', 'level'=>3),
			'menugrouplist'=>array('attr'=>'id,menutopid,offset,length,key,mod', 'level'=>3),
			'menulist'=>array('attr'=>'id,menugroupid,offset,length,key,mod', 'level'=>3),
			'menuoperationlist'=>array('attr'=>'id,offset,length,key,mod', 'level'=>3),
			'channellistadmin'   => array('attr'=>'id,channelid,offset,length,key,mod,depth,prefix,idlist', 'level'=>3),
			
			//前台标签===============================================================================
			//列表类标签
			'navigationlist'   => array('attr'=>'id,channelid,channelmodelid,offset,length,key,mod,depth,empty,idlist,showhidden,field', 'level'=>4, 'alias'=>'channellist'),
			'channelalbumlist'   => array('attr'=>'id,channelid,fieldname,offset,length,key,mod', 'level'=>3),
			'channelrelationlist'   => array('attr'=>'id,channelid,fieldname,offset,length,key,mod', 'level'=>3),
			
			'sitemaplist'   => array('attr'=>'id,channelid,offset,length,key,mod,depth,prefix', 'level'=>3),
			'infolist'   => array('attr'=>'id,channelid,offset,length,key,mod,empty,top,timeformat,titlelen,suffix,labelid,nowpage,keywords,specialid,minprice,maxprice,orderby,attr,field,pagesize,provinceid,cityid,districtid,townid', 'level'=>3),
			'linklist'   => array('attr'=>'id,linkclassid,offset,length,key,mod,top', 'level'=>3),
			'joblist'   => array('attr'=>'id,jobclassid,offset,length,key,mod,empty, top,nowpage', 'level'=>3),
			'jobclasslist'   => array('attr'=>'id,offset,length,key,mod', 'level'=>3),
			'guestbooklist'   => array('attr'=>'id,offset,length,key,mod,empty, top,nowpage', 'level'=>3),
			'commentlist'   => array('attr'=>'id,offset,length,key,mod,empty,nowpage,infoid', 'level'=>3),
			'supportlist'   => array('attr'=>'id,offset,length,key,mod,empty,qqstyle', 'level'=>3),
			'labellist'   => array('attr'=>'id,offset,length,key,mod,empty,channelmodelid', 'level'=>3),
			'formlist'   => array('attr'=>'id,channelmodelid,offset,length,key,mod', 'level'=>3),
			'mailclasslist'   => array('attr'=>'id,offset,length,key,mod', 'level'=>3),
			'linkclasslist'   => array('attr'=>'id,offset,length,key,mod', 'level'=>3),
			'bannerlist'   => array('attr'=>'id,bannergroupid,offset,length,key,mod', 'level'=>3),
			'bannergrouplist'   => array('attr'=>'id,offset,length,key,mod', 'level'=>3),
			'votelist'   => array('attr'=>'id,voteid,offset,length,key,mod', 'level'=>3),
			'speciallist'   => array('attr'=>'id,channelid,offset,length,key,mod,idlist', 'level'=>3),
			'adlist'   => array('attr'=>'id,adgroupid,offset,length,key,mod', 'level'=>3),
			'adgrouplist'   => array('attr'=>'id,offset,length,key,mod', 'level'=>3),
			'infoalbumlist'   => array('attr'=>'id,infoid,fieldname,offset,length,key,mod', 'level'=>3),
			'inforelationlist'   => array('attr'=>'id,infoid,fieldname,offset,length,key,mod', 'level'=>3),
			'taglist'   => array('attr'=>'id,infoid,offset,length,key,mod', 'level'=>3),
			'oauthlist'   => array('attr'=>'id,offset,length,key,mod', 'level'=>3),
			'arealist'   => array('attr'=>'id,parent,offset,length,key,mod', 'level'=>5),
			'sitelist'   => array('attr'=>'id,offset,length,key,mod', 'level'=>5),

			//模型列表
			'modellist'   => array('attr'=>'id,offset,length,key,mod,modelid', 'level'=>3),
			'guestbookmodellist'   => array('attr'=>'id,offset,length,key,mod', 'level'=>3),
			'feedbackmodellist'   => array('attr'=>'id,offset,length,key,mod', 'level'=>3),
			'ordermodellist'   => array('attr'=>'id,offset,length,key,mod', 'level'=>3),
			
			//数据源
			'sqllist'   => array('attr'=>'id,sql,offset,length,key,mod,empty,params', 'level'=>3),
			'jsonlist'   => array('attr'=>'id,url,method,offset,length,key,mod,empty,params', 'level'=>3),
			'selectxx'=>array('attr'=>'table,where,order,limit,id,page,sql,field,key,mod,debug','level'=>3),
			
			'banner'   => array('attr'=>'width,height,time,textcolor,textbgcolor,textbgalpha,bartextcolor,barovercolor,baroutcolor,showtext,channelid,bannergroupid,labelid,top','close'=>0),
			'banner1'   => array('attr'=>'width,height,time,showtext,channelid,bannergroupid,labelid,top','close'=>0),
			'banner2'   => array('attr'=>'width,height,time,showtext,channelid,bannergroupid,labelid,top','close'=>0),
			'banner3'   => array('attr'=>'width,height,time,showtext,channelid,bannergroupid,labelid,top','close'=>0),
			'ad'  => array('attr'=>'id,adid,width,height,delay,step;left,right,top','close'=>0),
			'gotop' => array('attr'=>'id,bottom,right,style,title','close'=>0),
			'online' => array('attr'=>'id','close'=>0),
			'online3' => array('attr'=>'id','close'=>0),
			'slide' => array('attr'=>'selector,effect,autopage,titcell,maincell,autoplay,intertime,delaytime,defaultindex,trigger,vis,scroll,prevcell,nextcell,titonclassname','close'=>0),
			'baidushare' => array('attr'=>'id','close'=>0),
			'videoplayer'=>array('attr'=>'title,width,height,allowfullscreen,src,autostart,type','close'=>0),
			
			//购物车
			'cartlist'   => array('attr'=>'id,offset,length,key,mod', 'level'=>3),
			'deliverytimelist'   => array('attr'=>'id,offset,length,key,mod', 'level'=>3),
			'shippinglist'   => array('attr'=>'id,offset,length,key,mod', 'level'=>3),
			'paylist'   => array('attr'=>'id,offset,length,key,mod,sitetype,isonline', 'level'=>3),
			'paytypelist'   => array('attr'=>'id,offset,length,key,mod', 'level'=>3),
			'orderstatuslist'   => array('attr'=>'id,offset,length,key,mod', 'level'=>3),
			'historylist'   => array('attr'=>'id,top,offset,length,key,mod', 'level'=>3),
			'toplist'   => array('attr'=>'id,type,top,order,channelid,offset,length,key,mod', 'level'=>3),
			'pricerangelist'   => array('attr'=>'id,channelid,count,offset,length,key,mod', 'level'=>3),
			'typeattributelist'=>array('attr'=>'id,offset,length,key,mod,type,channelid,specialid,minprice,maxprice,infoid', 'level'=>3),
			'selectedattributelist'   => array('attr'=>'id,offset,length,key,mod,attr,specialid,minprice,maxprice', 'level'=>3),
			'couponlist'   => array('attr'=>'id,offset,length,key,mod', 'level'=>3),
			'consigneelist'   => array('attr'=>'id,offset,length,key,mod', 'level'=>3),
			
			'distributorLevellist'   => array('attr'=>'id,offset,length,key,mod', 'level'=>3),
			'cashtypelist'   => array('attr'=>'id,offset,length,key,mod', 'level'=>3),
			//==================================================================================
			'datalist'   => array('attr'=>'id,offset,length,key,mod,value,columndelimiter,rowdelimiter,field,limit', 'level'=>3),
			'languagelist'   => array('attr'=>'id,offset,length,key,mod', 'level'=>2),
	);
	
	public function _slide($attr, $content){
		/*
		$tag        = $this->parseXmlAttr($attr,'slide');
		if( empty($tag['selector']) ) return "";
		$selector   = $tag['selector'];
		
		$effect  =   !empty($tag['effect']) ? "effect:'".$tag['effect']."'" : "effect:'fade'";
		
		$autoPage =   !empty($tag['autopage']) ?  "autoPage:'".$tag['autopage']."'," : '';
		$titCell =   !empty($tag['titcell']) ?  "titCell:'".$tag['titcell']."'," : '';
		$mainCell =   !empty($tag['maincell']) ?  "mainCell:'".$tag['maincell']."'," : '';
		
		$autoPlay =   !empty($tag['autoplay']) ?  "autoPlay:".$tag['autoplay']."," : '';
		$interTime =   !empty($tag['intertime']) ?  "interTime:".$tag['intertime']."," : '';
		$delayTime =   !empty($tag['delaytime']) ?  "delayTime:".$tag['delaytime']."," : '';
		$defaultIndex =   !empty($tag['defaultindex']) ?  "defaultIndex:'".$tag['defaultindex']."'," : '';
		$trigger = !empty($tag['trigger']) ?  "trigger:'".$tag['trigger']."'," : '';
		
		$scroll= !empty($tag['scroll']) ?  "scroll:".$tag['scroll']."," : '';
		$vis = !empty($tag['vis']) ?  "vis:".$tag['vis']."," : '';
		$prevCell= !empty($tag['prevcell']) ?  "prevCell:'".$tag['prevcell']."'," : '';
		$nextCell = !empty($tag['nextcell']) ?  "nextCell:'".$tag['nextcell']."'," : '';
		$titOnClassName = !empty($tag['titonclassname']) ?  "titOnClassName:'".$tag['titonclassname']."'," : '';
		
		$parseStr = "
		<script type='text/javascript'>
				$(document).ready(function(){
					$('$selector').slide({  $autoPage $titCell $mainCell $autoPlay $interTime $delayTime $defaultIndex $trigger $scroll $vis $prevCell $nextCell $titOnClassName $effect}); 
				})
		</script>";
		return $parseStr;
		*/
		$tag        = $this->parseXmlAttr($attr,'slide');
		if( empty($tag['selector']) ) return "";
		$selector   = $tag['selector'];
		
		$effect  =   !empty($tag['effect']) ? "effect:'".$tag['effect']."'" : "effect:'fade'";
		
		$autoPage =   !empty($tag['autopage']) ?  "autoPage:'".$tag['autopage']."'," : '';
		$titCell =   !empty($tag['titcell']) ?  "titCell:'".$tag['titcell']."'," : '';
		$mainCell =   !empty($tag['maincell']) ?  "mainCell:'".$tag['maincell']."'," : '';
		
		$autoPlay =   !empty($tag['autoplay']) ?  "autoPlay:".$tag['autoplay']."," : '';
		$interTime =   !empty($tag['intertime']) ?  "interTime:".$tag['intertime']."," : '';
		$delayTime =   !empty($tag['delaytime']) ?  "delayTime:".$tag['delaytime']."," : '';
		
		//变量
		$defaultIndex =   !empty($tag['defaultindex']) ? $tag['defaultindex'] : '';
		if('$' == substr($defaultIndex, 0, 1)) {
			$defaultIndex  =  $this->autoBuildVar(substr($defaultIndex,1));
		}
		
		$trigger = !empty($tag['trigger']) ?  "trigger:'".$tag['trigger']."'," : '';
		
		$scroll= !empty($tag['scroll']) ?  "scroll:".$tag['scroll']."," : '';
		$vis = !empty($tag['vis']) ?  "vis:".$tag['vis']."," : '';
		$prevCell= !empty($tag['prevcell']) ?  "prevCell:'".$tag['prevcell']."'," : '';
		$nextCell = !empty($tag['nextcell']) ?  "nextCell:'".$tag['nextcell']."'," : '';
		$titOnClassName = !empty($tag['titonclassname']) ?  "titOnClassName:'".$tag['titonclassname']."'," : '';
		
		$parseStr = "<?php  echo get_slide(";
		$parseStr .= "\"$selector\", \"$autoPage\", \"$titCell\", \"$mainCell\", \"$autoPlay\", \"$interTime\", \"$delayTime\", \"$defaultIndex\", ";
		$parseStr .= "\"$trigger\", \"$scroll\", \"$vis\", \"$prevCell\", \"$nextCell\", \"$titOnClassName\", \"$effect\");  ";
		$parseStr .= "?>";
		return $parseStr;
	}
	
	public function _menutoplist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'menutoplist');
		$id        = $tag['id'];
		$menuowner  = isset($tag['menuowner']) ? $tag['menuowner'] : '0';
		$empty  = isset($tag['empty']) ? $tag['empty'] : '';
		$key     =   !empty($tag['key']) ? $tag['key'] : 'i';
		$mod    =   isset($tag['mod']) ? $tag['mod'] : '2';
		
		$offset     =   isset($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
	
		$parseStr = "<volist name=':get_menu_top($menuowner)' id='$id'  empty=\"$empty\"  key='$key'  mod='$mod'  $offset  $length >$content</volist>";
		return $parseStr;
	}
	
	public function _menugrouplist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'menugrouplist');
		$id        = $tag['id'];
		$MenuTopID  = !empty($tag['menutopid']) ? $tag['menutopid'] : 0;
		$empty  = !empty($tag['empty']) ? $tag['empty'] : '';
		$key     =   !empty($tag['key']) ? $tag['key'] : 'i';
		$mod    =   !empty($tag['mod']) ? $tag['mod'] : '2';
		
		$offset     =   isset($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
	
		if('$' == substr($MenuTopID, 0, 1)) {
			$MenuTopID  =  $this->autoBuildVar(substr($MenuTopID, 1) );
		}
		
		$parseStr = "<volist name=':get_menu_group($MenuTopID)' id='$id'  empty=\"$empty\"  key='$key'  mod='$mod'   $offset  $length>$content</volist>";
		return $parseStr;
	}
	
	public function _menulist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'menulist');
		$id        = $tag['id'];
		$MenuGroupID  = isset($tag['menugroupid']) ? $tag['menugroupid'] : 0;
		$empty  = isset($tag['empty']) ? $tag['empty'] : '';
		$key     =   !empty($tag['key']) ? $tag['key'] : 'i';
		$mod    =   isset($tag['mod']) ? $tag['mod'] : '2';
		
		$offset     =   isset($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
		
		if('$' == substr($MenuGroupID, 0, 1)) {
			$MenuGroupID  =  $this->autoBuildVar(substr($MenuGroupID, 1) );
		}
	
		$parseStr = "<volist name=':get_menu($MenuGroupID)' id='$id'  empty=\"$empty\"  key='$key'  mod='$mod'   $offset  $length>$content</volist>";
		return $parseStr;
	}
	
	public function _menuoperationlist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'menulist');
		$id        = $tag['id'];
		$empty  = isset($tag['empty']) ? $tag['empty'] : '';
		$key     =   !empty($tag['key']) ? $tag['key'] : 'i';
		$mod    =   isset($tag['mod']) ? $tag['mod'] : '2';
	
		$offset     =   isset($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
	
		$parseStr = "<volist name=':get_menu_operation()' id='$id'  empty=\"$empty\"  key='$key'  mod='$mod'   $offset  $length>$content</volist>";
		return $parseStr;
	}
	
	public function _channellistadmin($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'channellistadmin');
		$channelid  = !empty($tag['channelid']) ? $tag['channelid'] : 0;
		$idlist  = !empty($tag['idlist']) ? trim($tag['idlist']) : false;
		
		$id        = $tag['id'];
        $empty  = isset($tag['empty'])?$tag['empty']:'';
        $key     =   !empty($tag['key'])?$tag['key']:'i';
        $mod    =   isset($tag['mod'])?$tag['mod']:'2';
        $depth    =   isset($tag['depth']) ? $tag['depth'] : '1';
        $prefix    =   isset($tag['prefix']) ? $tag['prefix'] : '';
        
		$offset     =   isset($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
        
		/* 下面这段代码无法解析,如：$c.ChannelID此类变量
		if('$' == substr($tag['channelid'], 0, 1) ){
			$varname = substr($tag['channelid'], 1);
			$val = $this->tpl->get( $varname );
			if( $val ) $channelid = $val;
		}
		*/
		if('$' == substr($channelid, 0, 1)) {
			$channelid  =  $this->autoBuildVar(substr($channelid,1));
		}
		
		if('$' == substr($idlist, 0, 1)) {
			$idlist  =  $this->autoBuildVar(substr($idlist,1));
		}
		
		$parseStr = "<volist name=':get_channel($channelid,$depth,\"$prefix\",\"$idlist\")' id='$id'  empty=\"$empty\"  key='$key'  mod='$mod'  $offset  $length>$content</volist>";
		return $parseStr;
	}
	
	public function _speciallist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'speciallist');
		$channelid  = !empty($tag['channelid']) ? $tag['channelid'] : 0;
		$idlist  = !empty($tag['idlist']) ? trim($tag['idlist']) : -1;
	
		$id        = $tag['id'];
		$empty  = isset($tag['empty'])?$tag['empty']:'';
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
	
		$offset     =   isset($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
	
		if('$' == substr($channelid, 0, 1)) {
			$channelid  =  $this->autoBuildVar(substr($channelid,1));
		}
	
		if('$' == substr($idlist, 0, 1)) {
			$idlist  =  $this->autoBuildVar(substr($idlist,1));
		}
	
		$parseStr = "<volist name=':get_special($channelid,\"$idlist\")' id='$id'  empty=\"$empty\"  key='$key'  mod='$mod'  $offset  $length>$content</volist>";
		return $parseStr;
	}
	
	//导航
	public function _navigationlist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'navigationlist');		
		$channelid  = !empty($tag['channelid']) ? $tag['channelid'] : 0;
		$channelmodelid  = ( isset($tag['channelmodelid']) && is_numeric($tag['channelmodelid']) ) ? $tag['channelmodelid'] : -1;
		
		$idlist  = !empty($tag['idlist']) ? trim($tag['idlist']) : -1;

		$id        = $tag['id'];
		$empty  = isset($tag['empty'])?$tag['empty']:'';
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
		$depth    =   isset($tag['depth']) ? $tag['depth'] : '1';
	
		$offset     =   ( isset($tag['offset']) && is_numeric($tag['offset']) ) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
		
		$showhidden  = isset($tag['showhidden']) ? trim($tag['showhidden']) : 0; //默认不显示隐藏频道
		$isshow = ($showhidden == 0) ? 1 : -1;
		$field     =   !empty($tag['field']) ? trim($tag['field']) : '';
		
		if('$' == substr($channelid, 0, 1)) {
			$channelid  =  $this->autoBuildVar(substr($channelid,1));
		}
		if('$' == substr($channelmodelid, 0, 1)) {
			$channelmodelid  =  $this->autoBuildVar(substr($channelmodelid,1));
		}
		
		if('$' == substr($idlist, 0, 1)) {
			$idlist  =  $this->autoBuildVar(substr($idlist,1));
		}
		
		$parseStr = "<volist name=':get_navigation($channelid,$depth,\"$idlist\",$isshow,$channelmodelid,-1,\"$field\")' id='$id'  empty=\"$empty\"  key='$key'  mod='$mod'  $offset  $length>$content</volist>";
		
		return $parseStr;
	}
	
	public function _sitemaplist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'channel');
		$channelid  = !empty($tag['channelid']) ? $tag['channelid'] : 0;
		
		$id        = $tag['id'];
        $empty  = isset($tag['empty'])?$tag['empty']:'';
        $key     =   !empty($tag['key'])?$tag['key']:'i';
        $mod    =   isset($tag['mod'])?$tag['mod']:'2';
        $depth    =   isset($tag['depth']) ? $tag['depth'] : '-1';
        $prefix    =   isset($tag['prefix']) ? $tag['prefix'] : '&nbsp;&nbsp;';
        
		$offset     =   isset($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';

		if('$' == substr($channelid, 0, 1)) {
			$channelid  =  $this->autoBuildVar(substr($channelid,1));
		}
		$parseStr = "<volist name=':get_sitemap($channelid,$depth,\"$prefix\")' id='$id'  empty=\"$empty\"  key='$key'  mod='$mod'   $offset  $length>$content</volist>";
		return $parseStr;
	}
	
	/**
	 * <linklist id="editor" linkid="0" ></linklist>
	 */
	public function _linklist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'linklist');
		$linkClassID  = !empty($tag['linkclassid']) ? $tag['linkclassid'] : -1;
	
		$id        = $tag['id'];
		$empty  = isset($tag['empty'])?$tag['empty']:'';
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
		$top = !empty($tag['top']) ? $tag['top'] : -1;
		
		$offset     =   isset($tag['offset']) && is_numeric($tag['offset'])  ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';

		if('$' == substr($linkClassID, 0, 1)) {
			$linkClassID  =  $this->autoBuildVar(substr($linkClassID, 1));
		}
		
		if('$' == substr($top, 0, 1)) {
			$top  =  $this->autoBuildVar(substr($top, 1) );
		}
		
		$parseStr = "<volist name=':get_link($linkClassID, $top)' id='$id'  empty=\"$empty\"  key='$key'  mod='$mod'   $offset  $length>$content</volist>";
		return $parseStr;
	}
	
	//投票调查
	public function _votelist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'votelist');
		$voteid = $this->_parseAttr($tag['voteid'], -1);
		//volist固有属性
		$id        = $tag['id'];
		$empty = $tag['empty'];
		$key = $tag['key'];
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
		$offset = isset($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length = isset($tag['length']) ? "length='".$tag['length']."'" : '';
		$parseStr = "<volist name=':get_vote_list($voteid)' id='$id'  empty=\"$empty\"  key='$key'  mod='$mod'   $offset  $length>$content</volist>";
		return $parseStr;
	}
	
	public function _bannerlist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'bannerlist');
		$BannerGroupID  = !empty($tag['bannergroupid']) ? $tag['bannergroupid'] : -1;
	
		$id        = $tag['id'];
		$empty  = isset($tag['empty'])?$tag['empty']:'';
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
	
		$offset     =   isset($tag['offset']) && is_numeric($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
	
		if('$' == substr($BannerGroupID, 0, 1)) {
			$BannerGroupID  =  $this->autoBuildVar(substr($BannerGroupID, 1));
		}
		$parseStr = "<volist name=':get_banner_list($BannerGroupID)' id='$id'  empty=\"$empty\"  key='$key'  mod='$mod'   $offset  $length>$content</volist>";
		return $parseStr;
	}
	
	public function _linkclasslist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'linkclasslist');
	
		$id        = $tag['id'];
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
		$empty  = isset($tag['empty'])?$tag['empty']:'';
		$offset     =   isset($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
	
		$parseStr = "<volist name=\":get_link_class()\" ";
		$parseStr .= " id='$id' empty=\"$empty\"  key='$key'  mod='$mod'   $offset  $length>$content";
		$parseStr .= '</volist>';
		return $parseStr;
	}

	public function _jobclasslist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'jobclasslist');

		$id        = $tag['id'];
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
		$empty  = isset($tag['empty'])?$tag['empty']:'';
		$offset     =   isset($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';

		$parseStr = "<volist name=\":get_job_class()\" ";
		$parseStr .= " id='$id' empty=\"$empty\"  key='$key'  mod='$mod'   $offset  $length>$content";
		$parseStr .= '</volist>';
		return $parseStr;
	}

	/**
	 * <JobList id="editor" channelid="0" ></JobList>
	 */
	public function _joblist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'joblist');
	
		$id        = $tag['id'];
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
		$empty  = isset($tag['empty'])?$tag['empty']:'';
	
		$top = !empty($tag['top']) ? $tag['top'] : -1;
		$NowPage = !empty($tag['nowpage']) ? $tag['nowpage'] : 0;
		
		$offset     =   isset($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
	
		if('$' == substr($NowPage, 0, 1)) {
			$NowPage  =  $this->autoBuildVar(substr($NowPage, 1) );
		}
		
		if('$' == substr($top, 0, 1)) {
			$top  =  $this->autoBuildVar(substr($top, 1) );
		}

		$JobClassID  = !empty($tag['jobclassid']) ? $tag['jobclassid'] : -1;
		if('$' == substr($JobClassID, 0, 1)) {
			$JobClassID  =  $this->autoBuildVar(substr($JobClassID, 1));
		}
	
		$parseStr = "<volist name=':get_job($top, $NowPage,$JobClassID)' ";
		$parseStr .= " id='$id' empty=\"$empty\"  key='$key'  mod='$mod'   $offset  $length>$content";
		$parseStr .= '</volist>';
		return $parseStr;
	}
	
	public function _supportlist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'supportlist');
	
		$id        = $tag['id'];
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
		$empty  = isset($tag['empty'])?$tag['empty']:'';	
		$offset     =   isset($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
		
		$qqstyle     =   !empty($tag['qqstyle']) ? $tag['qqstyle'] : '41';
		if('$' == substr($qqstyle, 0, 1)) {
			$qqstyle  =  $this->autoBuildVar(substr($qqstyle, 1) );
		}
	
		$parseStr = "<volist name=\":get_support($qqstyle)\" ";
		$parseStr .= " id='$id' empty=\"$empty\"  key='$key'  mod='$mod'   $offset  $length>$content";
		$parseStr .= '</volist>';
		return $parseStr;
	}
	
	public function _labellist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'labellist');
	
		$id        = $tag['id'];
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
		$empty  = isset($tag['empty'])?$tag['empty']:'';
		$offset     =   isset($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
	
		if( !empty($tag['channelmodelid']) ){
			$channelmodelid = $tag['channelmodelid'];
		}else{
			return '';
		}
		if('$' == substr($channelmodelid, 0, 1)) {
			$channelmodelid  =  $this->autoBuildVar(substr($channelmodelid, 1) );
		}
	
		$parseStr = "<volist name=\":get_label($channelmodelid)\" ";
		$parseStr .= " id='$id' empty=\"$empty\"  key='$key'  mod='$mod'   $offset  $length>$content";
		$parseStr .= '</volist>';
		return $parseStr;
	}
	
	/**
	 * 'formlist'   => array('attr'=>'id,channelmodelid,offset,length,key,mod', 'level'=>3),
	 */
	public function _formlist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'formlist');
	
		$id        = $tag['id'];
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
		$empty  = isset($tag['empty'])?$tag['empty']:'';
		$offset     =   isset($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
	
		if( !empty($tag['channelmodelid']) ){
			$channelmodelid = $tag['channelmodelid'];
		}else{
			return '';
		}
		if('$' == substr($channelmodelid, 0, 1)) {
			$channelmodelid  =  $this->autoBuildVar(substr($channelmodelid, 1) );
		}
	
		$parseStr = "<volist name=\":get_form($channelmodelid)\" ";
		$parseStr .= " id='$id' empty=\"$empty\"  key='$key'  mod='$mod'   $offset  $length>$content";
		$parseStr .= '</volist>';
		return $parseStr;
	}
	
	//模型字段输出start＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝
	public function _modellist($attr, $content){
		return $this->_getmodel($attr, $content, false, 'modellist');
	}
	//留言模型
	public function _guestbookmodellist($attr, $content){
		return $this->_getmodel($attr, $content, 6, 'guestbookmodellist');
	}
	//订购模型
	public function _ordermodellist($attr, $content){
		return $this->_getmodel($attr, $content, 26, 'ordermodellist');
	}
	//反馈模型
	public function _feedbackmodellist($attr, $content){
		return $this->_getmodel($attr, $content, 37, 'feedbackmodellist');
	}
	//模型输出
	private function _getmodel($attr, $content, $channelmodelid, $tagname){
		$tag        = $this->parseXmlAttr($attr, $tagname);
		$id        = $tag['id'];
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
		$empty  = isset($tag['empty'])?$tag['empty']:'';
		$offset     =   isset($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
		if( $channelmodelid === false){  //通用模型
			$channelmodelid  = $tag['modelid'];
			if('$' == substr($channelmodelid, 0, 1)) {
				$channelmodelid  =  $this->autoBuildVar(substr($channelmodelid,1));
			}
		}
		$idlist  = !empty($tag['idlist']) ? trim($tag['idlist']) : -1;
		if('$' == substr($idlist, 0, 1)) {
			$idlist  =  $this->autoBuildVar(substr($idlist,1));
		}
		
		$parseStr = "<volist name=':get_model($channelmodelid, \"$idlist\" )' ";
		$parseStr .= " id='$id' empty=\"$empty\"  key='$key'  mod='$mod'   $offset  $length>$content";
		$parseStr .= '</volist>';
		return $parseStr;
	}
	//模型字段输出end＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝
	
	
	public function _mailclasslist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'mailclasslist');
	
		$id        = $tag['id'];
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
		$empty  = isset($tag['empty'])?$tag['empty']:'';
		$offset     =   isset($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
	
		$parseStr = "<volist name=\":get_mail_class()\" ";
		$parseStr .= " id='$id' empty=\"$empty\"  key='$key'  mod='$mod'   $offset  $length>$content";
		$parseStr .= '</volist>';
		return $parseStr;
	}
	
	/**
	 * <GuestbookList id="editor" channelid="0" ></GuestbookList>
	 */
	public function _guestbooklist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'guestbooklist');
	
		$id        = $tag['id'];
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
		$empty  = isset($tag['empty'])?$tag['empty']:'';
	
		$top = !empty($tag['top']) ? $tag['top'] : -1;
		$NowPage = !empty($tag['nowpage']) ? $tag['nowpage'] : 0;
		
		$offset     =   isset($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
	
		if('$' == substr($NowPage, 0, 1)) {
			$NowPage  =  $this->autoBuildVar(substr($NowPage, 1) );
		}
		
		if('$' == substr($top, 0, 1)) {
			$top  =  $this->autoBuildVar(substr($top, 1) );
		}
	
		$parseStr = "<volist name=':get_guestbook($top, $NowPage)' ";
		$parseStr .= " id='$id' empty=\"$empty\"  key='$key'  mod='$mod'   $offset  $length>$content";
		$parseStr .= '</volist>';
		return $parseStr;
	}
	
	public function _commentlist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'commentlist');
	
		$id        = $tag['id'];
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
		$empty  = isset($tag['empty'])?$tag['empty']:'';
	
		$InfoID= $tag['infoid'];
		$NowPage = !empty($tag['nowpage']) ? $tag['nowpage'] : 0;
	
		$offset     =   isset($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
	
		if('$' == substr($NowPage, 0, 1)) {
			$NowPage  =  $this->autoBuildVar(substr($NowPage, 1) );
		}
		
		if('$' == substr($InfoID, 0, 1)) {
			$InfoID  =  $this->autoBuildVar(substr($InfoID, 1) );
		}
	
		$parseStr = "<volist name=':get_comment($InfoID, $NowPage)' ";
		$parseStr .= " id='$id' empty=\"$empty\"  key='$key'  mod='$mod'   $offset  $length>$content";
		$parseStr .= '</volist>';
		return $parseStr;
	}
	
	//缺陷，不支持分页, params:参数以逗号隔开,每个参数必须以$开头
	//sql不能包含< > 引号等特殊字符,<用&lt;替代，>用&gt;替代, 单引号用^替代
	public function _sqllist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'sqllist');
		$id        = $tag['id'];
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
		$empty  = isset($tag['empty'])?$tag['empty']:'';
		$offset     =   isset($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';

		$sql = isset($tag['sql']) ? $this->_parseAttr( $tag['sql'], '') : '';
		$parseStr = '<?php ';
		$result = array();
		preg_match_all('/{\$(.+?)}/i',$sql, $result);
		if(!empty($result[1])){
			foreach($result[1] as $k=>$v){
				$p = $this->autoBuildVar($v);
				$sql = str_ireplace('{$'.$v.'}', $p, $sql);
			}
		}

		$parseStr .= " \$_sql={$sql}; ?>";
		$parseStr .= "<volist name=':get_data(\$_sql)' ";
		$parseStr .= " id='$id' empty=\"$empty\"  key='$key'  mod='$mod'   $offset  $length>$content";
		$parseStr .= '</volist>';
		
		return $parseStr;
	}
	
		public function _selectxx($attr,$content){
			$tag       = $this->parseXmlAttr($attr,'select');
			$table     =!empty($tag['table'])?$tag['table']:'';
			$order     =!empty($tag['order'])?$tag['order']:'';
			$limit     =!empty($tag['limit'])?intval($tag['limit']):'';
			$id        =!empty($tag['id'])?$tag['id']:'r';
			$where     =!empty($tag['where'])?$tag['where']:' 1 ';
			$key        =!empty($tag['key'])?$tag['key']:'i';
			$mod        =!empty($tag['mod'])?$tag['mod']:'2';
			$page      =!empty($tag['page'])?$tag['page']:false;
			$sql         =!empty($tag['sql'])?$tag['sql']:'';
			$field     =!empty($tag['field'])?$tag['field']:'';
			$field = YdInput::checkTableField($field);
			$debug     =!empty($tag['debug'])?$tag['debug']:false;
			$this->comparison['noteq'] = '<>';
			$this->comparison['sqleq'] = '=';
			$where     =$this->parseCondition($where);
			$sql         =$this->parseCondition($sql);
			$parsestr = '<?php $m=M("'.$table.'");';
			 
			if($sql){
				if($page){
					$limit=$limit?$limit:10;//如果有page，没有输入limit则默认为10
					$parsestr.='import("@.ORG.Page");';
					$parsestr.='$count=count($m->query("'.$sql.'"));';
					$parsestr.='$p = new Page ( $count, '.$limit.' );';
					$parsestr.='$sql.="'.$sql.'";';
					$parsestr.='$sql.=" limit ".$p->firstRow.",".$p->listRows."";';
					$parsestr.='$ret=$m->query($sql);';
					$parsestr.='$pages=$p->show();';
					//$parsestr.='dump($count);dump($sql);';
				}else{
					$sql.=$limit?(' limit '.$limit):'';
					$parsestr.='$ret=$m->query("'.$sql.'");';
				}
			}else{
				if($page){
					$limit=$limit?$limit:10;//如果有page，没有输入limit则默认为10
					$parsestr.='import("@.ORG.Page");';
					$parsestr.='$count=$m->where("'.$where.'")->count();';
					$parsestr.='$p = new Page ( $count, '.$limit.' );';
					$parsestr.='$ret=$m->field("'.$field.'")->where("'.$where.'")->limit($p->firstRow.",".$p->listRows)->order("'.$order.'")->select();';
					$parsestr.='$pages=$p->show();';
				}else{
					$parsestr.='$ret=$m->field("'.$field.'")->where("'.$where.'")->order("'.$order.'")->limit("'.$limit.'")->select();';
				}
			}
			if($debug!=false){
				$parsestr.='dump($ret);dump($m->getLastSql());';
			}
			$parsestr.= 'if ($ret): $'.$key.'=0;';
			$parsestr.= 'foreach($ret as $key=>$'.$id.'):';
			$parsestr.= '++$'.$key.';$mod = ($'.$key.' % '.$mod.' );?>';
			$parsestr.= $this->tpl->parse($content);
			$parsestr.= '<?php endforeach;endif;?>';
			return $parsestr;
		}
		
	
	//缺陷，不支持分页, params:参数以逗号隔开,每个参数必须以$开头
	public function _jsonlist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'jsonlist');
		$id        = $tag['id'];
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
		$empty  = isset($tag['empty'])?$tag['empty']:'';
		$offset     =   isset($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';

		$datakey  = isset($tag['datakey'])?$tag['datakey']:'';
		$method = isset($tag['method']) ? $this->_parseAttr( $tag['method'], '') : 'get';
		$url = isset($tag['url']) ? $this->_parseAttr( $tag['url'], '') : '';

		/*
		$result = array();
		preg_match_all('/{\$(.+?)}/i',$url, $result);
		if(!empty($result[1])){
			foreach($result[1] as $k=>$v){
				$p = $this->autoBuildVar($v);
				$url = str_ireplace('{$'.$v.'}', $p, $url);
			}
		}
		*/
		//调用方式： http://youdiancms.com/index.php/Api/GetChannel?x1=$a1&x2=$a2&x3=$a3
		//反引号作为定界符，替换为空
		$parseStr = "<?php  \$_mymethod={$method}; \$_myurl=str_ireplace('`', '', $url); ?>";
		$parseStr .= "<volist name=':get_json(\$_myurl, \$_mymethod, \"{$datakey}\")' ";
		$parseStr .= " id='$id' empty=\"$empty\"  key='$key'  mod='$mod' $offset  $length>$content";
		$parseStr .= '</volist>';
		return $parseStr;
	}
	
	public function _infolist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'infolist');
		
		$id        = $tag['id'];
		$key     =   !empty($tag['key']) ? $tag['key'] : 'i';
		$mod    =   isset($tag['mod']) ? $tag['mod'] : '2';
		$empty  = isset($tag['empty']) ? $tag['empty'] : '';
		$offset     =   isset($tag['offset']) && is_numeric($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
		
		$channelid = isset($tag['channelid']) ? $this->_parseAttr( $tag['channelid'], 0) : 0;
		$specialid = isset($tag['specialid']) ? $this->_parseAttr( $tag['specialid'], 0) : 0;
		$top = isset($tag['top']) ? $this->_parseAttr( $tag['top'], -1) : -1;
		$timeformat = isset($tag['timeformat']) ? $this->_parseAttr( $tag['timeformat'], 'Y-m-d') : '"Y-m-d"';
		$titlelen = isset($tag['titlelen']) ? $this->_parseAttr( $tag['titlelen'], 0) : 0;
		
		$suffix = isset($tag['suffix']) ? $this->_parseAttr( $tag['suffix'], '...', 2) : '"..."';
		$labelid = isset($tag['labelid']) ? $this->_parseAttr( $tag['labelid'],'' ) : '""';
		$nowpage = isset($tag['nowpage']) ? $this->_parseAttr( $tag['nowpage'], 0) : 0;
		$keywords = isset($tag['keywords']) ? $this->_parseAttr( $tag['keywords'], '') : '""';
		$orderby = isset($tag['orderby']) ? $this->_parseAttr( $tag['orderby'], '') : '""';
		
		$minprice = isset($tag['minprice']) ? $this->_parseAttr( $tag['minprice'], -1) : -1;
		$maxprice = isset($tag['maxprice']) ? $this->_parseAttr( $tag['maxprice'], -1) : -1;
		$attr_info = isset($tag['attr']) ? $this->_parseAttr( $tag['attr'], '') : '""';
		$field     =   !empty($tag['field']) ? $this->_parseAttr($tag['field']) : '""';
		$pagesize = isset($tag['pagesize']) ? $this->_parseAttr( $tag['pagesize'], 0) : 0;
		
		//省市区
		$provinceid = isset($tag['provinceid']) ? $this->_parseAttr( $tag['provinceid'], -1) : -1;
		$cityid = isset($tag['cityid']) ? $this->_parseAttr( $tag['cityid'], -1) : -1;
		$districtid = isset($tag['districtid']) ? $this->_parseAttr( $tag['districtid'], -1) : -1;
		$townid = isset($tag['townid']) ? $this->_parseAttr( $tag['townid'], -1) : -1;
		
		//注意：$keywords为变量时，不能加单引号（'$keywords'）,否则会认为$keywords是一个字符串，
		//当其是常量时,需要加上单引号
		$parseStr = "<?php \$_labelid={$labelid}; \$_timeformat={$timeformat}; \$_keywords={$keywords}; \$_orderby={$orderby}; \$_field={$field};\$_pagesize={$pagesize};";
		$parseStr .= " \$_provinceid={$provinceid};\$_cityid={$cityid};\$_districtid={$districtid};\$_townid={$townid};";
		$parseStr .= " \$_suffix={$suffix}; \$_specialid={$specialid}; \$_minprice={$minprice};\$_maxprice={$maxprice};\$_attr_info={$attr_info}; ?>";
		$parseStr .= "<volist name=':get_info($channelid, \$_specialid, $top, \$_timeformat, $titlelen, \$_suffix, \$_labelid, $nowpage, \$_keywords, \$_orderby, \$_minprice, \$_maxprice, \$_attr_info, -1, \$_field, \$_pagesize, \$_provinceid, \$_cityid, \$_districtid, \$_townid)' ";
		$parseStr .= " id='$id' empty=\"$empty\"  key='$key'  mod='$mod'   $offset  $length>$content";
		$parseStr .= '</volist>';
		return $parseStr;
	}
	
	/**
	 * 处理变量，仅适合volist调用函数的情况
	 * @param mix $value 属性值
	 * @param mix $default 默认值，默认值不能是逻辑值true、false
	 * @param int $type 空函数处理类型，1：empty，2：isset
	 */
	private function _parseAttr($value, $default=0, $type=1){
		$b = ( $type==1) ? empty($value) : !isset($value);
		if( $b){
			$result = is_numeric($default) ? $default : '"'.$default.'"';
		}else{
			if('$' == substr($value, 0, 1)) {
				$result  =  $this->autoBuildVar(substr($value, 1));
			}else{
				if( is_numeric($value)){
					$result = $value;
				}else{
					$result = (strpos($value, '"') === false) ? '"'.$value.'"' : "'".$value."'";
				}
			}
		}
		return $result;
	}
	
	public function _channelselect($attr) {
		$tag        = $this->parseXmlAttr($attr,'channelselect');
		$id         = $tag['id'];
		$name       = $tag['name'];
		$style      = isset($tag['style']) ? $tag['style'] : '';
		$onchange	= isset($tag['change']) ? $tag['change'] : '';
		$LanguageID = empty($tag['languageid']) ? false : $tag['languageid'];
		if('$' == substr($LanguageID, 0, 1)) {
			$varname = substr($tag['languageid'], 1);
			$LanguageID = $this->tpl->get( $varname );
		}
	
		$firstvalue     = isset($tag['firstvalue']) ? $tag['firstvalue'] : '';
		$firsttext      = isset($tag['firsttext']) ? $tag['firsttext'] : '';

		$selectvalue     = isset($tag['selectvalue']) ? $tag['selectvalue'] : '';
		if('$' == substr($tag['selectvalue'], 0, 1) ){
			$varname = substr($tag['selectvalue'], 1);
			$val = $this->tpl->get( $varname );
			if( $val ) $selectvalue = $val;
		}
		
		//menuowner(0:会员，1:管理员), groupid
		$menuowner = !empty($tag['menuowner']) ? $tag['menuowner'] : 0;
		$groupid = !empty($tag['groupid']) ? $tag['groupid'] : -1;
		
		if('$' == substr($menuowner, 0, 1)) {
			$menuowner  =  $this->autoBuildVar(substr($menuowner, 1));
		}
		
		if('$' == substr($tag['groupid'], 0, 1) ){
			$varname = substr($tag['groupid'], 1);
			$groupid = $this->tpl->get( $varname );
		}
	
		$hasSingleChannel     = !empty( $tag['hassinglechannel'] ) ? true : false;
		$hasLinkChannel     = !empty( $tag['haslinkchannel'] ) ? true : false;	
		$ChannelID = !empty( $tag['channelid'] ) ? $tag['channelid'] : 0;

		//必须使用函数调用，否则会产生缓存
		$parseStr = "<?php  
			\$myParams = array(
				'menuowner'=>'{$menuowner}', 'groupid'=>'{$groupid}', 'LanguageID'=>'{$LanguageID}',
				'firstvalue'=>'{$firstvalue}',  'firsttext' =>'{$firsttext}',  'selectvalue' =>'{$selectvalue}',
				'name' =>'{$name}',  'id' =>'{$id}' ,  'style' =>'{$style}' ,  'onchange' =>'{$onchange}' 
			);
			echo getChannelSelect(\$myParams);
		?>";
		return $parseStr;
	}
	/**
	 +----------------------------------------------------------
	 * editor标签解析 插入可视化编辑器
	 * 格式： <youdian:editor id="editor" name="remark" type="CKeditor" style="" >{$vo.remark}</youdian:editor>
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @param string $attr 标签属性
	 +----------------------------------------------------------
	 * @return string|void
	 +----------------------------------------------------------
	 */
	public function _editor1($attr, $content) {
		$tag        =	$this->parseXmlAttr($attr,'editor');
		$id			=	!empty($tag['id'])?$tag['id']: '_editor';
		$name   	=	$tag['name'];
		$style   	    =	!empty($tag['style'])?$tag['style']:'';
		$width		=	!empty($tag['width'])?$tag['width']: '100%';
		$height     =	!empty($tag['height'])?$tag['height'] :'320px';
		$bgcolor     =	!empty($tag['bgcolor'])?$tag['bgcolor'] :'#dddddd'; //#bed393
		$type       =   $tag['type'] ;
		switch(strtoupper($type)) {
			/*
			 CKEDITOR.editorConfig = function (config) {  
02     	config.uiColor = '#AADC6E';  
03     	config.contentsCss = ['/Content/layout.css', '/Content/html.css'];  
04     	config.toolbar_Full = [['Source', '-', 'Save', 'NewPage', 'Preview', '-', 'Templates'],  
05                            ['Undo', 'Redo', '-', 'SelectAll', 'RemoveFormat'],  
06                            ['Styles', 'Format', 'Font', 'FontSize'],  
07                            ['TextColor', 'BGColor'],  
08                            ['Maximize', 'ShowBlocks', '-', 'About'], '/',  
09                            ['Bold', 'Italic', 'Underline', 'Strike', '-', 'Subscript', 'Superscript'],  
10                            ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', 'Blockquote', 'CreateDiv'],  
11                            ['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'],  
12                            ['Link', 'Unlink', 'Anchor'],  
13                            ['Image', 'Flash', 'Table', 'HorizontalRule', 'Smiley', 'SpecialChar', 'PageBreak'],  
14                            ['Code']];   
15     config.extraPlugins = 'CodePlugin';  
			 */
			case 'CKEDITOR':
				$parseStr   =	"<!-- 编辑器调用开始 --><textarea id='".$id."' name='".$name."'>".$content."</textarea>
				<script type='text/javascript'>window.CKEDITOR_BASEPATH='__ROOT__/Public/ckeditor/';</script>
				<script type='text/javascript' src='__ROOT__/Public/ckeditor/ckeditor.js?t=C6HH5UF'></script>
				<script type='text/javascript'>CKEDITOR.replace('".$id."', {'uiColor': '".$bgcolor."', 'width':'".$width."', 'height':'".$height."'});</script>
				<!-- 编辑器调用结束 -->";
				break;
			case 'CKEDITORMINI':
				$parseStr   =	"<!-- 编辑器调用开始 --><textarea id=".$id." name=".$name.">".$content."</textarea>
				<script type='text/javascript'>window.CKEDITOR_BASEPATH='__ROOT__/Public/ckeditor/';</script>
				<script type='text/javascript' src='__ROOT__/Public/ckeditor/ckeditor.js?t=C6HH5UF'></script>
				<script type='text/javascript'>CKEDITOR.replace('".$id."', {'uiColor': '".$bgcolor."', 'width':'".$width."', 'height':'".$height."', 'toolbar':[['Source','-','Bold','Italic','Underline','Strike'],['Image','Link','Unlink','Anchor'],['Styles','Format','Font','FontSize']]});</script><!-- 编辑器调用结束 -->";
				break;
			case 'EWEBEDITOR':
				$parseStr	=	"<!-- 编辑器调用开始 --><script type='text/javascript' src='__ROOT__/Public/Js/eWebEditor/js/edit.js'></script><input type='hidden'  id='{$id}' name='{$name}'  value='{$content}'><iframe src='__ROOT__/Public/Js/eWebEditor/ewebeditor.htm?id={$name}' frameborder=0 scrolling=no width='{$width}' height='{$height}'></iframe><script type='text/javascript'>function saveEditor(){document.getElementById('{$id}').value = getHTML();} </script><!-- 编辑器调用结束 -->";
				break;
			case 'NETEASE':
				$parseStr   =	'<!-- 编辑器调用开始 --><textarea id="'.$id.'" name="'.$name.'" style="display:none">'.$content.'</textarea><iframe ID="Editor" name="Editor" src="__ROOT__/Public/Js/HtmlEditor/index.html?ID='.$name.'" frameBorder="0" marginHeight="0" marginWidth="0" scrolling="No" style="height:'.$height.';width:'.$width.'"></iframe><!-- 编辑器调用结束 -->';
				break;
			case 'UBB':
				$parseStr	=	'<script type="text/javascript" src="__ROOT__/Public/Js/UbbEditor.js"></script><div style="padding:1px;width:'.$width.';border:1px solid silver;float:left;"><script LANGUAGE="JavaScript"> showTool(); </script></div><div><TEXTAREA id="UBBEditor" name="'.$name.'"  style="clear:both;float:none;width:'.$width.';height:'.$height.'" >'.$content.'</TEXTAREA></div><div style="padding:1px;width:'.$width.';border:1px solid silver;float:left;"><script LANGUAGE="JavaScript">showEmot();  </script></div>';
				break;
			case 'KINDEDITOR':
				$parseStr   =  '<script type="text/javascript" src="__ROOT__/Public/Js/KindEditor/kindeditor.js"></script><script type="text/javascript"> KE.show({ id : \''.$id.'\'  ,urlType : "absolute"});</script><textarea id="'.$id.'" style="'.$style.'" name="'.$name.'" >'.$content.'</textarea>';
				break;
			default :
				$parseStr  =  '<textarea id="'.$id.'" style="'.$style.'" name="'.$name.'" >'.$content.'</textarea>';
		}
		return $parseStr;
	}

	public function _ad($attr) {
		//('attr'=>'id,adid,width,height,delay,step','close'=>0)
		$tag        = $this->parseXmlAttr($attr,'ad');
		$id = !empty($tag['id']) ? $tag['id'] : 'myad';
		
		$adid = $tag['adid'];
		if('$' == substr($adid, 0, 1)) {
			$adid  =  $this->autoBuildVar(substr($adid,1));
		}
		
		$width    = !empty($tag['width'])   ? "width='".$tag['width']."'" : '';
		$height   = !empty($tag['height']) ? "height='".$tag['height']."'" : '';
		$delay      = !empty($tag['delay']) ? $tag['delay'] : 10;    //延时，单位：毫秒
		$step        = !empty($tag['step']) ? $tag['step'] : 1;
		
		$left        = !empty($tag['left']) ? $tag['left'] : '8px';
		$right        = !empty($tag['right']) ? $tag['right'] : '8px';
		$top       = !empty($tag['top']) ? $tag['top'] : '260px';
		
		$parseStr = "<?php  echo get_ad($adid, \"$id\", \"$width\", \"$height\", $delay, $step, \"$left\", \"$right\", \"$top\");  ?>";
		
		return $parseStr;
	}
	
	//groupid优先channelid
	public function _banner($attr) {
		//'width,height,time,textcolor,textbgcolor,textalpha,numcolor,baractivecolor,barcolor','close'=>0),
		$tag        = $this->parseXmlAttr($attr,'banner');
		
		$width         = !empty($tag['width']) ? $tag['width'] : 970;
		$height         = !empty($tag['height']) ? $tag['height'] : 268;
		if('$' == substr($width, 0, 1) ){
			$width = $this->autoBuildVar( substr($width, 1) );
		}
		if('$' == substr($height, 0, 1) ){
			$height = $this->autoBuildVar( substr($height, 1) );
		}
		
		$time        = !empty($tag['time']) ? $tag['time'] : 5;
		$groupid        = !empty($tag['bannergroupid']) ? $tag['bannergroupid'] : -1;
		if('$' == substr($groupid, 0, 1)) {
			$groupid  =  $this->autoBuildVar(substr($groupid, 1));
		}
		
		$showtext        = ( isset($tag['showtext']) && $tag['showtext']==1 ) ? 1 : 0;
		
		$top = !empty($tag['top']) ? $tag['top'] : -1;
		if('$' == substr($top, 0, 1)) {
			$top  =  $this->autoBuildVar(substr($top, 1));
		}
		
		$labelid = !empty($tag['labelid']) ? $tag['labelid'] : -1;
		
		$textcolor        = !empty($tag['textcolor']) ? $tag['textcolor'] : 0xff0000;
		$textbgcolor         = !empty($tag['textbgcolor']) ? $tag['textbgcolor'] : 0x000000;
		$textbgalpha         = !empty($tag['textbgalpha']) ? $tag['textbgalpha'] : 10;
		
		$bartextcolor        = !empty($tag['bartextcolor ']) ? $tag['bartextcolor '] : 0xffffff;
		$barovercolor         = !empty($tag['barovercolor']) ? $tag['barovercolor'] : 0xDB4D0B;
		$baroutcolor         = !empty($tag['barcolor']) ? $tag['barcolor'] : 0x000000;
		
		$channelid = !empty($tag['channelid']) ? $tag['channelid'] : 'false';
		if('$' == substr($channelid, 0, 1) ){
			$channelid = $this->autoBuildVar( substr($channelid, 1) );
		}
		$parseStr = "<?php  echo get_banner(\"$width\", \"$height\", $time, $showtext, $textcolor, $textbgcolor,$textbgalpha,$bartextcolor,$barovercolor,$baroutcolor,$channelid,$groupid,$top,\"$labelid\");  ?>";
		return $parseStr;
	}
	
	//bug:当幻灯片只有一张时，无法显示，其它的banner,banner2,banner3都能正常显示
	public function _banner1($attr) {
/*
变量说明：
	focus_width	幻灯片的宽度，单位为像素。
	focus_height	幻灯片的高度，单位为像素。
	interval_time	设置图片的停顿时间，单位为秒，为0则停止自动切换。如果没有此参数，系统默认为5秒。
	text_height	标题文字的高度，单位为像素，为0则不显示标题文字。
	text_align	标题文字的对齐方式(left、center、right)如果没有此参数，系统默认为center。注意：一定要有单引号，如'left'。
	swf_height	影片高度，即幻灯片高度与标题高度之和。相加之和最好是偶数,否则数字会模糊失真。
	pics		图片地址，可以使用相对路径也可以使用绝对地址。多个图片用竖线“|”分割，最多9个！支持的文件格式为：.jpg、.gif、.png、.swf
	links		图片对应的链接地址，可以使用相对路径也可以使用绝对地址。多个链接地址用竖线“|”分割，最多9个！链接中不能包含“&”符号，否则会出错。
	texts		图片对应的标题文字，不能包含“&”符号，否则会出错。多个标题文字用竖线“|”分割，最多9个！
*/	
		$tag        = $this->parseXmlAttr($attr,'banner1');
		$width         = !empty($tag['width']) ? $tag['width'] : 970;
		$height         = !empty($tag['height']) ? $tag['height'] : 268;
		if('$' == substr($width, 0, 1) ){
			$width = $this->autoBuildVar( substr($width, 1) );
		}
		if('$' == substr($height, 0, 1) ){
			$height = $this->autoBuildVar( substr($height, 1) );
		}
		
		$time        = !empty($tag['time']) ? $tag['time'] : 5;
		$showtext        = ($tag['showtext']==1) ? 20 : 0;
		$groupid        = !empty($tag['bannergroupid']) ? $tag['bannergroupid'] : -1;
		if('$' == substr($groupid, 0, 1)) {
			$groupid  =  $this->autoBuildVar(substr($groupid, 1));
		}
		
		$channelid = !empty($tag['channelid']) ? $tag['channelid'] : 'false';
		if('$' == substr($channelid, 0, 1) ){
			$channelid = $this->autoBuildVar( substr($channelid, 1) );
		}
		
		$top = !empty($tag['top']) ? $tag['top'] : -1;
		if('$' == substr($top, 0, 1)) {
			$top  =  $this->autoBuildVar(substr($top, 1));
		}
		
		$labelid = !empty($tag['labelid']) ? $tag['labelid'] : -1;
		
		$parseStr = "<?php  echo get_banner1(\"$width\", \"$height\", $time, $showtext, $channelid,$groupid,$top,\"$labelid\");  ?>";
		return $parseStr;
	}
	
	//效果同banner1, 需要修改
	public function _banner2($attr) {
		$tag        = $this->parseXmlAttr($attr,'banner1');
		$width         = !empty($tag['width']) ? $tag['width'] : 970;
		$height         = !empty($tag['height']) ? $tag['height'] : 268;
		if('$' == substr($width, 0, 1) ){
			$width = $this->autoBuildVar( substr($width, 1) );
		}
		if('$' == substr($height, 0, 1) ){
			$height = $this->autoBuildVar( substr($height, 1) );
		}
		$time        = !empty($tag['time']) ? $tag['time'] : 5;
		$showtext        = ($tag['showtext']==1) ? 20 : 0;
		$channelid = !empty($tag['channelid']) ? $tag['channelid'] : 'false';
		if('$' == substr($channelid, 0, 1) ){
			$channelid = $this->autoBuildVar( substr($channelid, 1) );
		}
		$top = !empty($tag['top']) ? $tag['top'] : -1;
		if('$' == substr($top, 0, 1)) {
			$top  =  $this->autoBuildVar(substr($top, 1));
		}
		$labelid = !empty($tag['labelid']) ? $tag['labelid'] : -1;
		
		$groupid        = !empty($tag['bannergroupid']) ? $tag['bannergroupid'] : -1;
		if('$' == substr($groupid, 0, 1)) {
			$groupid  =  $this->autoBuildVar(substr($groupid, 1));
		}
		
		$parseStr = "<?php  echo get_banner1(\"$width\", \"$height\", $time, $showtext, $channelid,$groupid,$top,\"$labelid\");  ?>";
		return $parseStr;
	}
	
	//3D幻灯片, 图片的宽度和高度必须和设定值一样，否则多出部分会以红色填充，图片本身不会拉伸显示
	//服务器必须支持去index.php重写才能使3D幻灯片显示
	public function _banner3($attr) {
		$tag        = $this->parseXmlAttr($attr,'banner3');	
		$width         = !empty($tag['width']) ? $tag['width'] : 970;
		$height         = !empty($tag['height']) ? $tag['height'] : 268;
		if('$' == substr($width, 0, 1) ){
			$width = $this->autoBuildVar( substr($width, 1) );
		}
		if('$' == substr($height, 0, 1) ){
			$height = $this->autoBuildVar( substr($height, 1) );
		}
		$time        = !empty($tag['time']) ? $tag['time'] : 5;
		$showtext        = ($tag['showtext']==1) ? 'true' : 'false';
		
		$channelid = !empty($tag['channelid']) ? $tag['channelid'] : 'false';
		if('$' == substr($channelid, 0, 1) ){
			$channelid = $this->autoBuildVar( substr($channelid, 1) );
		}
		$top = !empty($tag['top']) ? $tag['top'] : -1;
		if('$' == substr($top, 0, 1)) {
			$top  =  $this->autoBuildVar(substr($top, 1));
		}
		$labelid = !empty($tag['labelid']) ? $tag['labelid'] : -1;
		
		$groupid        = !empty($tag['bannergroupid']) ? $tag['bannergroupid'] : -1;
		if('$' == substr($groupid, 0, 1)) {
			$groupid  =  $this->autoBuildVar(substr($groupid, 1));
		}
		
		$parseStr = "<?php  echo get_banner3(\"$width\", \"$height\", $time, $showtext, $channelid, $groupid,$top,\"$labelid\");  ?>";
		return $parseStr;
	}
	
	//第三方在线客服
	public function _online3($attr) {
		$tag        = $this->parseXmlAttr($attr,'online3');
		$id         = !empty($tag['id']) ? $tag['id'] : 'online3';
		
		$m = D('Admin/Support3');
		$js = $m->where('IsEnable=1')->getField('Support3Js');
		return $js;
	}
	
	public function _baidushare($attr) {
		$tag        = $this->parseXmlAttr($attr,'baidushare');
		$id         = !empty($tag['id']) ? $tag['id'] : 'baidushare';
		$data = &$GLOBALS['Config'];
		$enable = $data['SHARE_ENABLE'];
		if( $enable == 0 ) return;
		
		$style = $data['SHARE_STYLE'];  //样式
		$top = $data['SHARE_TOP'];  //顶部距离
		$pos = empty($data['SHARE_POS']) ? 'right' : $data['SHARE_POS'];   //显示位置
		$size = empty($data['SHARE_SIZE']) ? '2' : $data['SHARE_SIZE']; //显示列数
		
		//'image':{'viewList':['qzone','tsina','tqq','renren','weixin'],'viewText':'分享到：','viewSize':'16'}, 这里删除了图片分享代码
		$js = "<script>
			window._bd_share_config={
			'common':{'bdSnsKey':{},'bdText':'','bdMini':'{$size}','bdMiniList':false,'bdPic':'','bdStyle':'0','bdSize':'16'},
			'slide':{'type':'slide','bdImg':'{$style}','bdPos':'{$pos}','bdTop':'{$top}'},
			
			'selectShare':{'bdContainerClass':null,'bdSelectMiniList':['qzone','tsina','tqq','renren','weixin']}
		};
		with(document)0[(getElementsByTagName('head')[0]||body).appendChild(createElement('script')).src='http://bdimg.share.baidu.com/static/api/js/share.js?v=89860593.js?cdnversion='+~(-new Date()/36e5)];
		</script>";
		return $js;
	}
	
	//'gotop' => array('attr'=>'id,right ,bottom,style','close'=>0),
	public function _gotop($attr) {
		$tag        = $this->parseXmlAttr($attr,'gotop');
		$id         = !empty($tag['id']) ? $tag['id'] : 'yd-gotop';
		$right         = isset($tag['right']) ? $tag['right'] : 120;
		$bottom         = isset($tag['bottom']) ? $tag['bottom'] : 100;
		$style        = !empty($tag['style']) ? $tag['style'] : 1;
		$title        = !empty($tag['title']) ? $tag['title'] : L('GoTop');
		$parseStr = get_gotop($id, $right,$bottom,$style,$title);
		return $parseStr;
	}

	public function _online($attr) {
		$tag        = $this->parseXmlAttr($attr,'online');
		$id         = !empty($tag['id']) ? $tag['id'] : 'yd-online';
		$parseStr = get_online($id);
		return $parseStr;
	}
	
	public function _specialselect($attr){
		$tag        = $this->parseXmlAttr($attr,'channelselect');
		$id         = $tag['id'];
		$name       = $tag['name'];
		$size       = !empty( $tag['size'] ) ? $tag['size'] : 5;
		$style      = isset($tag['style']) ? $tag['style'] : '';
		$onchange	= isset($tag['change']) ? $tag['change'] : '';
		
		$firstvalue     = isset($tag['firstvalue']) ? $tag['firstvalue'] : '';
		$firsttext      = isset($tag['firsttext']) ? $tag['firsttext'] : '';
		$selectvalue     = isset($tag['selectvalue']) ? $tag['selectvalue'] : '';
		
		$s = D('Admin/Special');
		$SpecialInfo = $s->getSpecial( array('IsEnable'=>1));
		
		$id = !empty($id) ? "id='$id'" : '';
		$name  = !empty($name ) ? "name='$name'" : '';
		$style = !empty($style) ? "style='$style'" : '';
		$onchange = !empty($onchange) ? "onchange='$onchange'" : '';
		
		$parseStr = "<select $id $name $style $onchange  size='$size'  multiple='multiple'>";
		$parseStr .= "<optgroup label='请选择所属专题（按Ctrl+左键可进行多选）'>";
		if(  !empty($firsttext) ){
			$parseStr .= "<option value='$firstvalue'>$firsttext</option>";
		}
		
		$selectvalue = explode(',', $selectvalue);
		foreach($SpecialInfo as $k=>$v){
			$cid = $v['SpecialID'];
			$cname = $v['SpecialName'];
			$parseStr .= "<option value='$cid'>$cname</option>";
		}
		$parseStr .= '</optgroup></select>';
		
		return $parseStr;
	}
	
	public function _modelselect($attr) {
		$tag        = $this->parseXmlAttr($attr,'select');
		$id         = $tag['id'];
		$name       = $tag['name'];
		$values     = $tag['values'];
		$output     = $tag['output'];
	
		$multiple   = $tag['multiple'];
		$size       = $tag['size'];
		$first      = $tag['first'];
	
	
		$style      = $tag['style'];
		$ondblclick = $tag['dblclick'];
		$onchange	= $tag['change'];
	
		$parseStr = "<youdian:select options='Model'   id='$id' name='$name'  ";
		$parseStr .= " multiple='$multiple'  size='$size'  first='$first' selected='\$ModelSelected' class='$style'";
		$parseStr .= " ondblclick='$ondblclick'  onchange='$onchange' />";
	
		return $parseStr;
	}
	
	public function _admingroupselect($attr) {
		$tag        = $this->parseXmlAttr($attr,'channelselect');
		$id         = $tag['id'];
		$name       = $tag['name'];
		$style      = isset($tag['style']) ? $tag['style'] : '';

		$onchange	= isset($tag['change']) ? $tag['change'] : '';
		$firstvalue     = isset($tag['firstvalue']) ? $tag['firstvalue'] : '';
		$firsttext      = isset($tag['firsttext']) ? $tag['firsttext'] : '';

		$selectvalue     = isset($tag['selectvalue']) ? $tag['selectvalue'] : '';
		if('$' == substr($tag['selectvalue'], 0, 1) ){
			$varname = substr($tag['selectvalue'], 1);
			$val = $this->tpl->get( $varname );
			if( $val ) $selectvalue = $val;
		}
	
		$c = D('Admin/AdminGroup');
		$info = $c->getAdminGroup();
	
		$id = !empty($id) ? "id='$id'" : '';
		$name  = !empty($name ) ? "name='$name'" : '';
		$style = !empty($style) ? "style='$style'" : '';
		$onchange = !empty($onchange) ? "onchange=$onchange" : '';
	
		$parseStr = "<select $id $name $style $onchange>";
		if(  !empty($firsttext) ){
			$parseStr .= "<option value='$firstvalue'>$firsttext</option>";
		}
		foreach($info as $k=>$v){
			$cid = $v['AdminGroupID'];
			$cname = $v['AdminGroupName'];
			$sel = ($selectvalue == $cid ) ? "Selected='Selected'" : '';
	
			$parseStr .= "<option value='$cid' $sel>$cname</option>";
		}
		$parseStr .= '</select>';
	
		return $parseStr;
	}
	public function _labelcheckbox($attr){
		$tag        = $this->parseXmlAttr($attr,'labelcheckbox');
		$name       = $tag['name'];
		$id       = $tag['id'];
		$style   = $tag['style'];
		$separator  = $tag['separator'];

		$modelid = $tag['channelmodelid'];
		if('$' == substr($tag['channelmodelid'], 0, 1) ){
			$varname = substr($tag['channelmodelid'], 1);
			$modelid = $this->tpl->get( $varname );
		}
		
		//当前选中的box
		$checked = $tag['checked'];
		if('$' == substr($tag['checked'], 0, 1) ){
			$varname = substr($tag['checked'], 1);
			$val = $this->tpl->get( $varname );
			if( $val ) $checked = $val;
		}

		$parseStr = get_labelcheckbox($modelid, $checked, $id, $name, $style, $separator);
		return $parseStr;
	}
	
	public function _adlist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'adlist');
		$AdGroupID  = !empty($tag['adgroupid']) ? $tag['adgroupid'] : -1;
	
		$id        = $tag['id'];
		$empty  = isset($tag['empty'])?$tag['empty']:'';
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
	
		$offset     =   isset($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
	
		if('$' == substr($AdGroupID, 0, 1)) {
			$AdGroupID  =  $this->autoBuildVar(substr($AdGroupID, 1));
		}
		$parseStr = "<volist name=':get_ad_list($AdGroupID)' id='$id'  empty=\"$empty\"  key='$key'  mod='$mod'   $offset  $length>$content</volist>";
		return $parseStr;
	}
	
	public function _arealist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'arealist');
		$id        = $tag['id'];
		$empty  = isset($tag['empty'])?$tag['empty']:'';
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
	
		$offset     =   isset($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
	
		$parent = isset($tag['parent']) ? $this->_parseAttr( $tag['parent'],'' ) : -1;
		
		$parseStr = "<volist name=':get_area($parent)' id='$id'  empty=\"$empty\"  key='$key'  mod='$mod'   $offset  $length>$content</volist>";
		return $parseStr;
	}

	public function _sitelist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'sitelist');
		$id        = $tag['id'];
		$empty  = isset($tag['empty'])?$tag['empty']:'';
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';

		$offset     =   isset($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';

		$parseStr = "<volist name=':get_site()' id='$id'  empty=\"$empty\"  key='$key'  mod='$mod'   $offset  $length>$content</volist>";
		return $parseStr;
	}

	public function _adgrouplist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'adgrouplist');
	
		$id        = $tag['id'];
		$empty  = isset($tag['empty'])?$tag['empty']:'';
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
	
		$offset     =   isset($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
	
		$parseStr = "<volist name=':get_adgroup()' id='$id'  empty=\"$empty\"  key='$key'  mod='$mod'   $offset  $length>$content</volist>";
		return $parseStr;
	}
	
	public function _bannergrouplist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'bannergrouplist');
	
		$id        = $tag['id'];
		$empty  = isset($tag['empty'])?$tag['empty']:'';
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
	
		$offset     =   isset($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
	
		$parseStr = "<volist name=':get_bannergroup()' id='$id'  empty=\"$empty\"  key='$key'  mod='$mod'   $offset  $length>$content</volist>";
		return $parseStr;
	}
	
	public function _infoalbumlist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'infoalbumlist');
		$id        = $tag['id'];
		$InfoID  = !empty($tag['infoid']) ? $tag['infoid'] : -1;
		$empty  = isset($tag['empty'])?$tag['empty']:'';
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
		$fieldname    =   isset($tag['fieldname'])?$tag['fieldname']:'InfoAlbum';
		if('$' == substr($InfoID, 0, 1)) {
			$InfoID  =  $this->autoBuildVar(substr($InfoID, 1));
		}
		$offset     =   isset($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
	
		$parseStr = "<volist name=':get_infoalbum($InfoID,\"$fieldname\")' id='$id'  empty=\"$empty\"  key='$key'  mod='$mod'   $offset  $length>$content</volist>";
		return $parseStr;
	}
	
	public function _inforelationlist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'inforelationlist');
		$id        = $tag['id'];
		$InfoID  = !empty($tag['infoid']) ? $tag['infoid'] : -1;
		$empty  = isset($tag['empty'])?$tag['empty']:'';
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
		$fieldname    =   isset($tag['fieldname'])?$tag['fieldname']:'InfoRelation';
		if('$' == substr($InfoID, 0, 1)) {
			$InfoID  =  $this->autoBuildVar(substr($InfoID, 1));
		}
		$offset     =   isset($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
	
		$parseStr = "<volist name=':get_inforelation($InfoID, \"$fieldname\")' id='$id'  empty=\"$empty\"  key='$key'  mod='$mod'   $offset  $length>$content</volist>";
		return $parseStr;
	}
	
	public function _taglist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'taglist');
		$id        = $tag['id'];
		$InfoID  = !empty($tag['infoid']) ? $tag['infoid'] : -1;
		$empty  = isset($tag['empty'])?$tag['empty']:'';
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
		if('$' == substr($InfoID, 0, 1)) {
			$InfoID  =  $this->autoBuildVar(substr($InfoID, 1));
		}
		$offset     =   isset($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
	
		$parseStr = "<volist name=':get_tag($InfoID)' id='$id'  empty=\"$empty\"  key='$key'  mod='$mod'   $offset  $length>$content</volist>";
		return $parseStr;
	}
	
	public function _oauthlist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'oauthlist');
		$id        = $tag['id'];
		$InfoID  = !empty($tag['infoid']) ? $tag['infoid'] : -1;
		$empty  = isset($tag['empty'])?$tag['empty']:'';
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
		$offset     =   isset($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
	
		$parseStr = "<volist name=':get_oauth()' id='$id'  empty=\"$empty\"  key='$key'  mod='$mod'   $offset  $length>$content</volist>";
		return $parseStr;
	}
	
	
	
	public function _videoplayer($attr) {
		$tag        = $this->parseXmlAttr($attr,'videoplayer');
		if( empty($tag['src']) ) return false;
		$src = isset($tag['src']) ? $this->_parseAttr( $tag['src'], '') : '""';
		$title = isset($tag['title']) ? $this->_parseAttr( $tag['title'], '') : '""';
		$width = isset($tag['width']) ? $this->_parseAttr( $tag['width'], '100%') : '"100%"';
		
		$height = isset($tag['height']) ? $this->_parseAttr( $tag['height'], '450px') : '"450px"';
		$allowfullscreen = isset($tag['allowfullscreen']) ? $this->_parseAttr( $tag['allowfullscreen'], 1) : 1;
		$autostart = isset($tag['autostart']) ? $this->_parseAttr( $tag['autostart'], 0) : 0;
		$type = isset($tag['type']) ? $this->_parseAttr( $tag['type'], 'auto') : '"auto"';

		$parseStr = "<?php  
			\$myattr = array( 'width' => {$width}, 'height' => {$height}, 'allowfullscreen' => {$allowfullscreen},
				'type' => {$type},  'autostart' => {$autostart},  'src' => {$src} ,'title'=>{$title});
			echo get_videopalyer(\$myattr);
		?>";
		return $parseStr;
	}
	
	public function _cartlist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'cartlist');
		$id        = $tag['id'];
		$empty  = isset($tag['empty'])?$tag['empty']:'';
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
	
		$offset     =   isset($tag['offset']) && is_numeric($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
	
		$parseStr = "<volist name=':get_cart()' id='$id'  empty=\"$empty\"  key='$key'  mod='$mod'  $offset  $length>$content</volist>";
		return $parseStr;
	}
	
	//收货时间列表
	public function _deliverytimelist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'deliverytimelist');
		$id        = $tag['id'];
		$empty  = isset($tag['empty'])?$tag['empty']:'';
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
	
		$offset     =   isset($tag['offset']) && is_numeric($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
	
		$parseStr = "<volist name=':get_deliverytime()' id='$id'  empty=\"$empty\"  key='$key'  mod='$mod'  $offset  $length>$content</volist>";
		return $parseStr;
	}
	
	//配送方式
	public function _shippinglist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'shippinglist');
		$id        = $tag['id'];
		$empty  = isset($tag['empty'])?$tag['empty']:'';
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
	
		$offset     =   isset($tag['offset']) && is_numeric($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
	
		$parseStr = "<volist name=':get_shipping()' id='$id'  empty=\"$empty\"  key='$key'  mod='$mod'  $offset  $length>$content</volist>";
		return $parseStr;
	}
	
	//支付方式
	public function _paylist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'paylist');
		$id        = $tag['id'];
		$empty  = isset($tag['empty'])?$tag['empty']:'';
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
		$sitetype    =   isset($tag['sitetype']) ? intval($tag['sitetype']) : 1; //1:所有，2：电脑站，3：手机站
		$isonline    =   isset($tag['isonline']) ? intval($tag['isonline']) : -1;
	
		$offset     =   isset($tag['offset']) && is_numeric($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
	
		$parseStr = "<volist name=':get_pay($sitetype,$isonline)' id='$id'  empty=\"$empty\"  key='$key'  mod='$mod'  $offset  $length>$content</volist>";
		return $parseStr;
	}
	
	//收货人
	public function _consigneelist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'consigneelist');
		$id        = $tag['id'];
		$empty  = isset($tag['empty'])?$tag['empty']:'';
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
	
		$offset     =   isset($tag['offset']) && is_numeric($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
	
		$parseStr = "<volist name=':get_consignee()' id='$id'  empty=\"$empty\"  key='$key'  mod='$mod'  $offset  $length>$content</volist>";
		return $parseStr;
	}
	
	/**
	 * 分销商等级
	 */
	public function _distributorlevellist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'distributorlevellist');
		$id        = $tag['id'];
		$empty  = isset($tag['empty'])?$tag['empty']:'';
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
	
		$offset     =   isset($tag['offset']) && is_numeric($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
	
		$parseStr = "<volist name=':get_distributorlevel()' id='$id'  empty=\"$empty\"  key='$key'  mod='$mod'  $offset  $length>$content</volist>";
		return $parseStr;
	}
	
	/**
	 * 现金类型
	 */
	public function _cashtypelist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'cashtypelist');
		$id        = $tag['id'];
		$empty  = isset($tag['empty'])?$tag['empty']:'';
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
	
		$offset     =   isset($tag['offset']) && is_numeric($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
	
		$parseStr = "<volist name=':get_cashtype()' id='$id'  empty=\"$empty\"  key='$key'  mod='$mod'  $offset  $length>$content</volist>";
		return $parseStr;
	}
	
	//当前会员可用优惠券列表
	public function _couponlist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'couponlist');
		$id        = $tag['id'];
		$empty  = isset($tag['empty'])?$tag['empty']:'';
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
		$offset     =   isset($tag['offset']) && is_numeric($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
	
		$parseStr = "<volist name=':get_coupon()' id='$id'  empty=\"$empty\"  key='$key'  mod='$mod'  $offset  $length>$content</volist>";
		return $parseStr;
	}
	
	//支付类别
	public function _paytypelist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'paytypelist');
		$id        = $tag['id'];
		$empty  = isset($tag['empty'])?$tag['empty']:'';
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
	
		$offset     =   isset($tag['offset']) && is_numeric($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
	
		$parseStr = "<volist name=':get_pay_type()' id='$id'  empty=\"$empty\"  key='$key'  mod='$mod'  $offset  $length>$content</volist>";
		return $parseStr;
	}
	
	public function _orderstatuslist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'orderstatus');
		$id        = $tag['id'];
		$empty  = isset($tag['empty'])?$tag['empty']:'';
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
	
		$offset     =   isset($tag['offset']) && is_numeric($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
	
		$parseStr = "<volist name=':get_order_status()' id='$id'  empty=\"$empty\"  key='$key'  mod='$mod'  $offset  $length>$content</volist>";
		return $parseStr;
	}
	
	public function _channelalbumlist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'channelalbumlist');
		$id        = $tag['id'];
		$ChannelID  = !empty($tag['channelid']) ? $tag['channelid'] : -1;
		$empty  = isset($tag['empty'])?$tag['empty']:'';
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
		$fieldname    =   isset($tag['fieldname'])?$tag['fieldname']:'ChannelAlbum';
		if('$' == substr($ChannelID, 0, 1)) {
			$ChannelID  =  $this->autoBuildVar(substr($ChannelID, 1));
		}
		$offset     =   isset($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
	
		$parseStr = "<volist name=':get_channelalbum($ChannelID,\"$fieldname\")' id='$id'  empty=\"$empty\"  key='$key'  mod='$mod'   $offset  $length>$content</volist>";
		return $parseStr;
	}
	
	public function _channelrelationlist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'channelrelationlist');
		$id        = $tag['id'];
		$ChannelID  = !empty($tag['channelid']) ? $tag['channelid'] : -1;
		$empty  = isset($tag['empty'])?$tag['empty']:'';
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
		$fieldname    =   isset($tag['fieldname'])?$tag['fieldname']:'ChannelRelation';
		if('$' == substr($ChannelID, 0, 1)) {
			$ChannelID  =  $this->autoBuildVar(substr($ChannelID, 1));
		}
		$offset     =   isset($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
	
		$parseStr = "<volist name=':get_channelrelation($ChannelID, \"$fieldname\")' id='$id'  empty=\"$empty\"  key='$key'  mod='$mod'   $offset  $length>$content</volist>";
		return $parseStr;
	}
	
	//历史记录
	public function _historylist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'historylist');
		$id        = $tag['id'];
		$empty  = isset($tag['empty'])?$tag['empty']:'';
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
		$offset     =   isset($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
		$top    =   isset($tag['top']) && is_numeric($tag['top']) ? $tag['top'] : -1;
	
		$parseStr = "<volist name=':get_history($top)' id='$id'  empty=\"$empty\"  key='$key'  mod='$mod'   $offset  $length>$content</volist>";
		return $parseStr;
	}
	
	public function _toplist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'toplist');
		$id        = $tag['id'];
		$empty  = isset($tag['empty'])?$tag['empty']:'';
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
		
		$channelid  = !empty($tag['channelid']) ? $tag['channelid'] : -1;
		if('$' == substr($channelid, 0, 1)) {
			$channelid  =  $this->autoBuildVar(substr($channelid, 1));
		}
		$type   =   isset($tag['type']) ? $tag['type']: 'sales'; //sales: 按销量排序
		$top    =   isset($tag['top']) && is_numeric($tag['top']) ? $tag['top'] : -1;
		$order   =  ($tag['order']=='asc') ? 'asc' : 'desc'; //默认为降序

		$offset     =   isset($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
		$parseStr = "<volist name=':get_top($channelid, \"$type\",$top, \"$order\")' id='$id'  empty=\"$empty\"  key='$key'  mod='$mod'   $offset  $length>$content</volist>";
		return $parseStr;
	}
	
	public function _pricerangelist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'pricerange');
		$id        = $tag['id'];
		$empty  = isset($tag['empty'])?$tag['empty']:'';
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
		$offset     =   isset($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';

		$channelid = $this->_parseAttr($tag['channelid'], -1);
		$count = $this->_parseAttr($tag['count'], 5);
		$parseStr = "<volist name=':get_price_range($channelid, $count)' id='$id'  empty=\"$empty\"  key='$key'  mod='$mod'   $offset  $length>$content</volist>";
		return $parseStr;
	}
	
	//类型属性列表
	public function _typeattributelist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'typeattribute');
		$id        = $tag['id'];
		$empty  = isset($tag['empty'])?$tag['empty']:'';
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
		$offset     =   isset($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
		
		//类别，1：所有属性、2：规格属性、3：检索条件属性
		$type  = isset($tag['type']) ? $tag['type'] : 1;
		$channelid = $this->_parseAttr($tag['channelid'], -1);
		$specialid = $this->_parseAttr($tag['specialid'], -1);
		$minprice = $this->_parseAttr($tag['minprice'], -1);
		$maxprice = $this->_parseAttr($tag['maxprice'], -1);
		$infoid = $this->_parseAttr($tag['infoid'], -1);
		
		$parseStr = "<?php \$_infoid={$infoid}; \$_channelid={$channelid}; \$_specialid={$specialid}; \$_minprice={$minprice};\$_maxprice={$maxprice};?>";
		$parseStr .= "<volist name=':get_type_attribute($type,\$_infoid,\$_channelid,\$_specialid,\$_minprice,\$_maxprice)' id='$id'  empty=\"$empty\"  key='$key'  mod='$mod'   $offset  $length>$content</volist>";
		return $parseStr;
	}
	
	public function _selectedattributelist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'selectedattribute');
		$id        = $tag['id'];
		$empty  = isset($tag['empty'])?$tag['empty']:'';
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
		$offset     =   isset($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';
		
		$specialid = $this->_parseAttr($tag['specialid'], -1);
		$minprice = $this->_parseAttr($tag['minprice'], -1);
		$maxprice = $this->_parseAttr($tag['maxprice'], -1);
	
		$attr = $this->_parseAttr($tag['attr'], '');
		$parseStr = "<?php \$_attr={$attr}; \$_specialid={$specialid}; \$_minprice={$minprice}; \$_maxprice={$maxprice};?>";
		$parseStr .= "<volist name=':get_selected_attribute(\$_attr,\$_specialid,\$_minprice,\$_maxprice)' id='$id'  empty=\"$empty\"  key='$key'  mod='$mod'   $offset  $length>$content</volist>";
		return $parseStr;
	}

	//数据列表
	public function _datalist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'datalist');
		$id        = $tag['id'];
		$empty  = isset($tag['empty'])?$tag['empty']:'';
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';
		$offset     =   isset($tag['offset']) && is_numeric($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';

		if(!isset($tag['rowdelimiter'])) $tag['rowdelimiter'] = '';
		if(!isset($tag['columndelimiter'])) $tag['columndelimiter'] = '';
		if(!isset($tag['field'])) $tag['field'] = '';

		$field = $this->_parseAttr($tag['field'], '');
		$rowdelimiter = $this->_parseAttr($tag['rowdelimiter'], '');
		$columndelimiter = $this->_parseAttr($tag['columndelimiter'], '');
		$limit  = isset($tag['limit']) ? $tag['limit'] : 0;
		$value = $this->_parseAttr($tag['value'], '');
		$parseStr = "<?php \$_myvalue={$value};  \$_myfield={$field};  ?>";
		$parseStr .= "<volist name=':get_mydata(\$_myvalue,\$_myfield,$limit,$rowdelimiter,$columndelimiter)' id='$id'  empty=\"$empty\"  key='$key'  mod='$mod'  $offset  $length>$content</volist>";
		return $parseStr;
	}

	//多语言列表
	public function _languagelist($attr, $content){
		$tag        = $this->parseXmlAttr($attr,'languagelist');
		$id        = $tag['id'];
		$empty  = isset($tag['empty'])?$tag['empty']:'';
		$key     =   !empty($tag['key'])?$tag['key']:'i';
		$mod    =   isset($tag['mod'])?$tag['mod']:'2';

		$offset     =   isset($tag['offset']) &&is_numeric($tag['offset']) ? "offset='".$tag['offset']."'" : '';
		$length    =   isset($tag['length']) ? "length='".$tag['length']."'" : '';

		$parseStr = "<volist name=':get_language()' id='$id'  empty=\"$empty\"  key='$key'  mod='$mod'  $offset  $length>$content</volist>";
		return $parseStr;
	}
}
?>

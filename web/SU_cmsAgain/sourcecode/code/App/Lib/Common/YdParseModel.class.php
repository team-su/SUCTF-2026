<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */

if (!defined('APP_NAME')) exit();
/**
 * 解析模型参数属性
 * @param array $attribute
 */
function parsemodel($attribute){
	if( !$attribute ) return false;
	$SelectPicture = L('SelectPicture');
	$WebPublic  =  __ROOT__.'/Public/';
    $TextEditor = $GLOBALS['Config']['TextEditor'];
    if(empty($TextEditor)) $TextEditor = 1; //默认为CK编辑器
	$nTotal = count($attribute);
	for($i = 0; $i <$nTotal; $i++){
	    //防止报undefined错误
        if(!isset($attribute[$i]['Toggle'])) $attribute[$i]['Toggle'] = '';
        if(!isset($attribute[$i]['Parameter'])) $attribute[$i]['Parameter'] = '';

        changeFieldDisplayType($attribute[$i]);
		//构造样式表=======================
		$style = "";
		$attribute[$i]['html']="";
		$width = $attribute[$i]['DisplayWidth'];
		$height = $attribute[$i]['DisplayHeight'];
		$class = $attribute[$i]['DisplayClass'];
		$helpText = $attribute[$i]['DisplayHelpText'];
		if(!empty($width) ) $style = "width:$width;";
		if(!empty($height) ) $style .= "height:$height;";
		if(!empty($style) ) $style = "style='$style'";
		if(!empty($class) ) $class = "class='$class'";
		//===============================
		if(isset($attribute[$i]['IsRequire']) && $attribute[$i]['IsRequire'] == 1 ){ //必填字段
			$attribute[$i]['DisplayName'] = '<b style="color:red">'.$attribute[$i]['DisplayName'].'</b>';
		}
		$name = $attribute[$i]['FieldName'];
		$value = isset($attribute[$i]['DisplayValue']) ? $attribute[$i]['DisplayValue'] : '';
        $DisplayType = $attribute[$i]['DisplayType'];
        $SelectedValue = isset($attribute[$i]['SelectedValue']) ? $attribute[$i]['SelectedValue'] : null;
        $selected = '';

		static $_js = array(); //保证js文件只加载一次
		switch ($DisplayType){
			case 'editormini':
			case 'editor':  //编辑器
                if ($TextEditor== "1") { //CKEditor
                    $attribute[$i]['html'] = parse_ck_editor($name, $value, $width, $height, $attribute[$i]['Parameter'], $_js);
                }else{ //百度UEditor编辑器
                    $attribute[$i]['html'] = parse_ue_editor($name, $value, $width, $height, $attribute[$i]['Parameter'], $_js);
                }
				break;
			case 'select':    //下拉框
				if($value){
					$value = str_replace(array("\r\n","\r"), "\n", $value);
					$item = explode ("\n", $value);
					$attribute[$i]['html'] = "<select id='$name' name='$name'    $style  $class  >";
					for($j = 0; $j < count($item); $j++){
						$t = explode ('|', $item[$j] ); //value|item|是否是默认
						if( isset($SelectedValue) ){ //自定义选中项
							$selected = ($SelectedValue == $t[0]) ? "selected='selected'" : '';
						}else{ //默认选中项
							$selected = empty($t[2]) ? "" : "selected='selected'";
						}
							
						$attribute[$i]['html'] .= "<option value='$t[0]' $selected>$t[1]</option>";
					}
					$attribute[$i]['html'] .= "</select>";
				}
				break;
            case 'fontselect':
                $attribute[$i]['html'] = "<select id='$name' name='$name'    $style  $class  >";
                for($j = 12; $j <= 48; $j++){
                    if( isset($SelectedValue) ){ //自定义选中项
                        $selected = ($SelectedValue == $j) ? "selected='selected'" : '';
                    }
                    $attribute[$i]['html'] .= "<option value='{$j}' $selected>{$j}px</option>";
                }
                $attribute[$i]['html'] .= "</select>";
                break;
			case 'modelselect':    //模型下拉框
				$m1 = D('Admin/ChannelModel');
				$model = $m1->getChannelModel(0,1,true);
				if($model){
					$attribute[$i]['html'] = "<select id='$name' name='$name'    $style  $class  >";
					for($j = 0; $j < count($model); $j++){
						$value = $model[$j]['ChannelModelID'];
						$text = $model[$j]['ChannelModelName'];
						$indexTemplate = $model[$j]['IndexTemplate'];
						$readTemplate = $model[$j]['ReadTemplate'];
							
						if( isset($SelectedValue) ){ //自定义选中项
							$selected = ( $SelectedValue == $value) ? "selected='selected'" : '';
						}else{ //默认选中项
							$selected = ($j==0) ?  "selected='selected'" : "";
						}
							
						$attribute[$i]['html'] .= "<option it='$indexTemplate' rt='$readTemplate' value='$value'  $selected>$text</option>";
					}
					$attribute[$i]['html'] .= "</select>";
				}
				break;
			case 'displaytypeselect': //显示类型下拉框
				$m1 = D('Admin/Attribute');
				$model = $m1->getDisplayType();
				if($model){
					$attribute[$i]['html'] = "<select  id='$name'  name='$name'    $style  $class  >";
					for($j = 0; $j < count($model); $j++){
						$value = $model[$j]['DisplayTypeID'];
						$text = $model[$j]['DisplayTypeName'];
							
						if( isset($SelectedValue) ){ //自定义选中项
							$selected = ( $SelectedValue == $value) ? "selected='selected'" : '';
						}else{ //默认选中项
							$selected = ($j==0) ?  "selected='selected'" : "";
						}
						$attribute[$i]['html'] .= "<option value='$value'  $selected>$text</option>";
					}
					$attribute[$i]['html'] .= "</select>";
				}
				break;
			case 'channelselectno':  //频道下拉框，并显示文字"不显示这个版块"，主要用于模板配置
            case 'channelselectall': //显示所有频道
            case 'channelselectcurrent':  //频道下拉框：显示当前频道
            case 'channelselectcurrentno':  //频道下拉框：显示当前频道+不显示
			case 'channelselect':  //频道下拉框
				$m3 = D('Admin/Channel');
				if( isset($attribute[$i]['AdminGroupID']) ){
					$HasSingleModel =isset($attribute[$i]['HasSingleModel']) ? $attribute[$i]['HasSingleModel'] : true;
					$HasLinkModel =isset($attribute[$i]['HasLinkModel']) ? $attribute[$i]['HasLinkModel'] : true;
					$ExcludeChannel =isset($attribute[$i]['ExcludeChannel']) ? $attribute[$i]['ExcludeChannel'] : -1;
					$Depth = isset($attribute[$i]['Depth']) ? $attribute[$i]['Depth'] : -1;
					$Prefix =isset($attribute[$i]['Prefix']) ? $attribute[$i]['Prefix'] : '&nbsp;&nbsp;&nbsp;&nbsp;├─';
					$AdminGroupID =isset($attribute[$i]['AdminGroupID']) ? $attribute[$i]['AdminGroupID'] : -1;
					
					//必须缓存，当后台配置项过多时，非常慢
					$key = md5($HasSingleModel.$HasLinkModel.$ExcludeChannel.$Depth.$Prefix.$AdminGroupID);
					static $_cache = array();
					if (isset($_cache[$key])){
						$Channel = $_cache[$key];
					}else{
						$Channel = $m3->getChannel(0, $HasSingleModel, $HasLinkModel, $ExcludeChannel,$Depth, $Prefix, $AdminGroupID );
						$_cache[$key] = $Channel;
					}
				}else{ //会员
					$MemberGroupID = $attribute[$i]['MemberGroupID'];
					$Channel = $m3->getChannelPurview(0, $MemberGroupID, '&nbsp;&nbsp;&nbsp;&nbsp;├─ ', false);
				}
					
				$attribute[$i]['html'] = "<select id='$name' name='$name'    $style  $class  >";
				//频道下拉框，并显示文字"不显示这个版块"，主要用于模板配置
				if($DisplayType=='channelselectno'){
					$selected = ( $SelectedValue == 0) ? "selected='selected'" : '';
					$attribute[$i]['html'] .= "<option value='0' $selected>".L('NotShowSection')."</option>";
				}elseif($DisplayType=='channelselectall'){
                    $selected = ($SelectedValue == 0) ? "selected='selected'" : '';
                    $attribute[$i]['html'] .= "<option value='0' {$selected}>全部频道</option>";
                }elseif($DisplayType=='channelselectcurrent'){
                    $selected = ($SelectedValue == -1) ? "selected='selected'" : '';
                    $attribute[$i]['html'] .= "<option value='-1' {$selected}>当前频道</option>";
                }elseif($DisplayType=='channelselectcurrentno'){ //当前频道+不显示
                    $selected = ( $SelectedValue == 0) ? "selected='selected'" : '';
                    $attribute[$i]['html'] .= "<option value='0' $selected>".L('NotShowSection')."</option>";

                    $selected = ($SelectedValue == -1) ? "selected='selected'" : '';
                    $attribute[$i]['html'] .= "<option value='-1' {$selected}>当前频道</option>";
                }
				if( isset($attribute[$i]['FirstValue']) && isset($attribute[$i]['FirstText']) ){
					$firstText = $attribute[$i]['FirstText'];
					$firstValue = $attribute[$i]['FirstValue'];
					$attribute[$i]['html'] .= "<option value='$firstValue'>$firstText</option>";
				}
				
				if($Channel){
					for($j = 0; $j < count($Channel); $j++){
						//不显示投递简历频道
						if($Channel[$j]['ChannelID'] == 10 || $Channel[$j]['ChannelID'] == 11) continue;
						$value = $Channel[$j]['ChannelID'];
						$text = $Channel[$j]['ChannelName'];
							
						if( isset($SelectedValue) ){ //自定义选中项
							$selected = ( $SelectedValue == $value) ? "selected='selected'" : '';
						}
						$attribute[$i]['html'] .= "<option value='$value'  $selected>$text</option>";
					}
				}
				$attribute[$i]['html'] .= "</select>";
				break;
			case 'supporttypeselect':
				$st = D('Admin/SupportType');
				$stInfo = $st->getSupportType();
				if($stInfo){
					$attribute[$i]['html'] = "<select id='$name' name='$name'    $style  $class  >";
					if( isset($attribute[$i]['FirstValue']) && isset($attribute[$i]['FirstText']) ){
						$firstText = $attribute[$i]['FirstText'];
						$firstValue = $attribute[$i]['FirstValue'];
						$attribute[$i]['html'] .= "<option value='$firstValue'>$firstText</option>";
					}
					for($j = 0; $j < count($stInfo); $j++){
						$value = $stInfo[$j]['SupportTypeID'];
						$text = $stInfo[$j]['SupportTypeName'];

						if( isset($SelectedValue) ){ //自定义选中项
							$selected = ( $SelectedValue == $value) ? "selected='selected'" : '';
						}

						$attribute[$i]['html'] .= "<option value='$value'  $selected>$text</option>";
					}
					$attribute[$i]['html'] .= "</select>";
				}
				break;
			case 'linkclassselectno':
			case 'linkclassselect':
				$lc = D('Admin/LinkClass');
				$lcInfo = $lc->getLinkClass();
				if($lcInfo){
					$attribute[$i]['html'] = "<select id='$name' name='$name'    $style  $class  >";
					if($attribute[$i]['DisplayType']=='linkclassselectno'){
						$selected = ( $attribute[$i]['SelectedValue'] == 0) ? "selected='selected'" : '';
						$attribute[$i]['html'] .= "<option value='0' $selected>".L('NotShowSection')."</option>";
					}
					if( isset($attribute[$i]['FirstValue']) && isset($attribute[$i]['FirstText']) ){
						$firstText = $attribute[$i]['FirstText'];
						$firstValue = $attribute[$i]['FirstValue'];
						$attribute[$i]['html'] .= "<option value='$firstValue'>$firstText</option>";
					}
					for($j = 0; $j < count($lcInfo); $j++){
						$value = $lcInfo[$j]['LinkClassID'];
						$text = $lcInfo[$j]['LinkClassName'];
							
						if( isset($SelectedValue) ){ //自定义选中项
							$selected = ( $SelectedValue == $value) ? "selected='selected'" : '';
						}
							
						$attribute[$i]['html'] .= "<option value='$value'  $selected>$text</option>";
					}
					$attribute[$i]['html'] .= "</select>";
				}
				break;
            case 'jobclassselectno': //职位分类
            case 'jobclassselect':
                $lc = D('Admin/JobClass');
                $lcInfo = $lc->getJobClass();
                if($lcInfo){
                    $attribute[$i]['html'] = "<select id='$name' name='$name'    $style  $class  >";
                    if($attribute[$i]['DisplayType']=='jobclassselectno'){
                        $selected = ( $attribute[$i]['SelectedValue'] == 0) ? "selected='selected'" : '';
                        $attribute[$i]['html'] .= "<option value='0' $selected>".L('NotShowSection')."</option>";
                    }
                    if( isset($attribute[$i]['FirstValue']) && isset($attribute[$i]['FirstText']) ){
                        $firstText = $attribute[$i]['FirstText'];
                        $firstValue = $attribute[$i]['FirstValue'];
                        $attribute[$i]['html'] .= "<option value='$firstValue'>$firstText</option>";
                    }
                    for($j = 0; $j < count($lcInfo); $j++){
                        $value = $lcInfo[$j]['JobClassID'];
                        $text = $lcInfo[$j]['JobClassName'];

                        if( isset($SelectedValue) ){ //自定义选中项
                            $selected = ( $SelectedValue == $value) ? "selected='selected'" : '';
                        }

                        $attribute[$i]['html'] .= "<option value='$value'  $selected>$text</option>";
                    }
                    $attribute[$i]['html'] .= "</select>";
                }
                break;
			case 'adgroupselectno':
			case 'adgroupselect':
				$lc = D('Admin/AdGroup');
				$lcInfo = $lc->getAdGroup(array('IsEnable'=>1) );
				if($lcInfo){
					$attribute[$i]['html'] = "<select id='$name' name='$name'    $style  $class  >";
					//并显示文字"不显示这个版块"，主要用于模板配置
					if($attribute[$i]['DisplayType']=='adgroupselectno'){
						$selected = ( $attribute[$i]['SelectedValue'] == 0) ? "selected='selected'" : '';
						$attribute[$i]['html'] .= "<option value='0' $selected>".L('NotShowSection')."</option>";
					}
					if( isset($attribute[$i]['FirstValue']) && isset($attribute[$i]['FirstText']) ){
						$firstText = $attribute[$i]['FirstText'];
						$firstValue = $attribute[$i]['FirstValue'];
						$attribute[$i]['html'] .= "<option value='$firstValue'>$firstText</option>";
					}
					for($j = 0; $j < count($lcInfo); $j++){
						$value = $lcInfo[$j]['AdGroupID'];
						$text = $lcInfo[$j]['AdGroupName'];
							
						if( isset($SelectedValue) ){ //自定义选中项
							$selected = ($SelectedValue == $value) ? "selected='selected'" : '';
						}
							
						$attribute[$i]['html'] .= "<option value='$value'  $selected>$text</option>";
					}
					$attribute[$i]['html'] .= "</select>";
				}
				break;
			case 'mailclassselect':
				$lc = D('Admin/MailClass');
				$lcInfo = $lc->getMailClass();
				if($lcInfo){
					$attribute[$i]['html'] = "<select id='$name' name='$name'    $style  $class  >";
					if( isset($attribute[$i]['FirstValue']) && isset($attribute[$i]['FirstText']) ){
						$firstText = $attribute[$i]['FirstText'];
						$firstValue = $attribute[$i]['FirstValue'];
						$attribute[$i]['html'] .= "<option value='$firstValue'>$firstText</option>";
					}
					for($j = 0; $j < count($lcInfo); $j++){
						$value = $lcInfo[$j]['MailClassID'];
						$text = $lcInfo[$j]['MailClassName'];
							
						if( isset($SelectedValue) ){ //自定义选中项
							$selected = ( $SelectedValue == $value) ? "selected='selected'" : '';
						}
							
						$attribute[$i]['html'] .= "<option value='$value'  $selected>$text</option>";
					}
					$attribute[$i]['html'] .= "</select>";
				}
				break;
			case 'bannergroupselectno':
			case 'bannergroupselect':
				$lc = D('Admin/BannerGroup');
				$lcInfo = $lc->getBannerGroup();
				if($lcInfo){
					$attribute[$i]['html'] = "<select id='$name' name='$name'    $style  $class  >";
					//显示文字"不显示这个版块"，主要用于模板配置
					if($attribute[$i]['DisplayType']=='bannergroupselectno'){
						$selected = ( $attribute[$i]['SelectedValue'] == 0) ? "selected='selected'" : '';
						$attribute[$i]['html'] .= "<option value='0' $selected>".L('NotShowSection')."</option>";
					}
					if( isset($attribute[$i]['FirstValue']) && isset($attribute[$i]['FirstText']) ){
						$firstText = $attribute[$i]['FirstText'];
						$firstValue = $attribute[$i]['FirstValue'];
						$attribute[$i]['html'] .= "<option value='$firstValue'>$firstText</option>";
					}
					
					for($j = 0; $j < count($lcInfo); $j++){
						$value = $lcInfo[$j]['BannerGroupID'];
						$text = $lcInfo[$j]['BannerGroupName'];
							
						if( isset($SelectedValue) ){ //自定义选中项
							$selected = ($SelectedValue == $value) ? "selected='selected'" : '';
						}
							
						$attribute[$i]['html'] .= "<option value='$value'  $selected>$text</option>";
					}
					$attribute[$i]['html'] .= "</select>";
				}
				break;
			case 'adselect':
				$lc = D('Admin/Ad');
				$lcInfo = $lc->getAd();
				if($lcInfo){
					$attribute[$i]['html'] = "<select id='$name' name='$name'    $style  $class  >";
					if( isset($attribute[$i]['FirstValue']) && isset($attribute[$i]['FirstText']) ){
						$firstText = $attribute[$i]['FirstText'];
						$firstValue = $attribute[$i]['FirstValue'];
						$attribute[$i]['html'] .= "<option value='$firstValue'>$firstText</option>";
					}
					for($j = 0; $j < count($lcInfo); $j++){
						$value = $lcInfo[$j]['AdID'];
						$text = "[{$value}] {$lcInfo[$j]['AdName']}";

						if( isset($SelectedValue) ){ //自定义选中项
							$selected = ( $SelectedValue == $value) ? "selected='selected'" : '';
						}

						$attribute[$i]['html'] .= "<option value='$value'  $selected>$text</option>";
					}
					$attribute[$i]['html'] .= "</select>";
				}
				break;
			case 'areaselect':
				//仅对ProvinceID字段有效
				if( $name!='ProvinceID') break;
				$ma = D('Admin/Area');
				$areaInfo = $ma->getProvinceAndCity(1);
				if(isset($attribute[$i]['ProvinceSelectedValue'])){
					$ProvinceIDSeclected =$attribute[$i]['ProvinceSelectedValue'];
				}else{
					//默认为第一个，$areaInfo为关联数组，不能通过下标获取
					$ProvinceIDSeclected = 0;
					//foreach($areaInfo as $v){
					//	$ProvinceIDSeclected = $v['AreaID'];
					//	break;
					//}
				}
				if($areaInfo){
					//省==
					$attribute[$i]['html'] = "<select id='ProvinceID' name='ProvinceID'  $style  $class>";
					$selected = ($ProvinceIDSeclected== 0) ? "selected='selected'" : '';
					$attribute[$i]['html'] .= "<option value='0' citylist='' $selected>请选择</option>";
					foreach($areaInfo as $v){
						$selected = ($ProvinceIDSeclected== $v['AreaID']) ? "selected='selected'" : '';
						$attribute[$i]['html'] .= "<option value='{$v['AreaID']}' citylist='{$v['CityList']}' $selected>{$v['AreaName']}</option>";
					}
					$attribute[$i]['html'] .= "</select>";
					//==
					
					//市== $areaInfo的key值为ProvinceID
					if( isset($areaInfo[$ProvinceIDSeclected]) ){
						$CityList = explode('@', $areaInfo[$ProvinceIDSeclected]['CityList']);
						$attribute[$i]['html'] .= "&nbsp;<select id='CityID' name='CityID'  $style  $class>";
						foreach($CityList as $v){
							$cities = explode(',', $v);
							$CityID = $cities[0];
							$CityName = $cities[1];
							if( isset($attribute[$i]['CitySelectedValue']) ){ //自定义选中项
								$selected = ( $attribute[$i]['CitySelectedValue'] == $CityID) ? "selected='selected'" : '';
							}else{
								$selected = '';
							}
							$attribute[$i]['html'] .= "<option value='{$CityID}' $selected>{$CityName}</option>";
						}
						$attribute[$i]['html'] .= "</select>";
					}else{
						$attribute[$i]['html'] .= "&nbsp;<select id='CityID' name='CityID'  $class style='display:none;'></select>";
					}
					//==
					$attribute[$i]['html'] .= "<input type='hidden' name='DistrictID' value='' />";
					$attribute[$i]['html'] .= "<input type='hidden' name='TownID' value='' />";
					
					//省市联动脚本==
					$attribute[$i]['html'] .= "<script>
						$(document).ready(function(){
							$('#ProvinceID').change( function(){
								var objProvinceSelect = $('#ProvinceID').children('option:selected');
								var citylist = objProvinceSelect.attr('citylist');
								if(citylist){
									var arrCitylist= citylist.split('@');
									var html = '';
									for(var i = 0; i < arrCitylist.length; i++){
										var temp = arrCitylist[i].split(',');
										html += '<option value=\"'+temp[0]+'\">'+temp[1]+'</option>';
									}
									$('#CityID').html(html);
									$('#CityID').show();
								}else{
									html = '<option value=\"0\"></option>';
									$('#CityID').html(html);
									$('#CityID').hide();
								}
							});					
						});
					</script>";
					//==========
				}
				break;
			case 'areaselect4':  //省市区县镇四级联动
				if( $name!='ProvinceID') break;
				//获取区域数据
				$ProvinceIDSeclected = isset($attribute[$i]['ProvinceSelectedValue']) ? $attribute[$i]['ProvinceSelectedValue'] : 0;
				$CityIDSelected = isset($attribute[$i]['CitySelectedValue']) ? $attribute[$i]['CitySelectedValue'] : 0;
				$DistrictIDSelected  = isset($attribute[$i]['DistrictSelectedValue']) ? $attribute[$i]['DistrictSelectedValue'] : 0;
				$TownIDSelected = isset($attribute[$i]['TownSelectedValue']) ? $attribute[$i]['TownSelectedValue'] : 0;
				
				$ma = D('Admin/Area');
				$area = $ma->getArea(0, 1);
				if($area){
					//==省==
					$attribute[$i]['html'] = "<select id='ProvinceID' name='ProvinceID'  $style  $class>";
					$selected = ($ProvinceIDSeclected== 0) ? "selected='selected'" : '';
					$attribute[$i]['html'] .= "<option value='0' $selected>请选择</option>";
					foreach($area as $v){
						$selected = ($ProvinceIDSeclected== $v['AreaID']) ? "selected='selected'" : '';
						$attribute[$i]['html'] .= "<option value='{$v['AreaID']}'  $selected>{$v['AreaName']}</option>";
					}
					$attribute[$i]['html'] .= "</select>";
					
					//==市==
					$CityDefaultHtml = "&nbsp;<select id='CityID' name='CityID'  style='display:none;'  $class><option value='0'></option></select>";
					if($ProvinceIDSeclected>0 || $CityIDSelected>0){
						$area = $ma->getArea($ProvinceIDSeclected, 1);
						if(!empty($area)){
							$attribute[$i]['html'] .= "&nbsp;<select id='CityID' name='CityID'  $style  $class>";
							$selected = ($CityIDSelected== 0) ? "selected='selected'" : '';
							$attribute[$i]['html'] .= "<option value='0' $selected>请选择</option>";
							foreach($area as $v){
								$selected = ($CityIDSelected== $v['AreaID']) ? "selected='selected'" : '';
								$attribute[$i]['html'] .= "<option value='{$v['AreaID']}'  $selected>{$v['AreaName']}</option>";
							}
							$attribute[$i]['html'] .= "</select>";
						}
					}else{
						$attribute[$i]['html'] .= $CityDefaultHtml;
					}
					
					//==区县==
					$DistrictDefaultHtml = "&nbsp;<select id='DistrictID' name='DistrictID'  style='display:none;'  $class><option value='0'></option></select>";
					if($CityIDSelected > 0 || $DistrictIDSelected > 0 ){
						$area = $ma->getArea($CityIDSelected, 1);
						if(!empty($area)){
							$attribute[$i]['html'] .= "&nbsp;<select id='DistrictID' name='DistrictID'  $style  $class>";
							$selected = ($DistrictIDSelected== 0) ? "selected='selected'" : '';
							$attribute[$i]['html'] .= "<option value='0' $selected>请选择</option>";
							foreach($area as $v){
								$selected = ($DistrictIDSelected== $v['AreaID']) ? "selected='selected'" : '';
								$attribute[$i]['html'] .= "<option value='{$v['AreaID']}'  $selected>{$v['AreaName']}</option>";
							}
							$attribute[$i]['html'] .= "</select>";
						}
					}else{
						$attribute[$i]['html'] .= $DistrictDefaultHtml;
					}
					
					//==城镇==
					$TownDefaultHtml = "&nbsp;<select id='TownID' name='TownID'  style='display:none;'  $class><option value='0'></option></select>";
					if($DistrictIDSelected > 0 || $TownIDSelected > 0){
						$area = $ma->getArea($DistrictIDSelected, 1);
						if(!empty($area)){
							$attribute[$i]['html'] .= "&nbsp;<select id='TownID' name='TownID'  $style  $class>";
							$selected = ($TownIDSelected== 0) ? "selected='selected'" : '';
							$attribute[$i]['html'] .= "<option value='0' $selected>请选择</option>";
							foreach($area as $v){
								$selected = ($TownIDSelected== $v['AreaID']) ? "selected='selected'" : '';
								$attribute[$i]['html'] .= "<option value='{$v['AreaID']}'  $selected>{$v['AreaName']}</option>";
							}
							$attribute[$i]['html'] .= "</select>";
						}
					}else{
						$attribute[$i]['html'] .= $TownDefaultHtml;
					}
					
					$ApiUrl = ApiUrl('GetArea');
					$attribute[$i]['html'] .= "<script>
						$(document).ready(function(){
							function changeArea(currentid, id){
								var seclectedID = $(currentid).val();
								if(currentid=='#ProvinceID'){
									$('#CityID').hide(); $('#DistrictID').hide(); $('#TownID').hide();
									if(seclectedID==0){
										$('#CityID').html(\"{$CityDefaultHtml}\");
										$('#DistrictID').html(\"{$DistrictDefaultHtml}\");
										$('#TownID').html(\"{$TownDefaultHtml}\");
									}
								}else if(currentid=='#CityID'){
									$('#DistrictID').hide(); $('#TownID').hide();
									if(seclectedID==0){
										$('#DistrictID').html(\"{$DistrictDefaultHtml}\");
										$('#TownID').html(\"{$TownDefaultHtml}\");
									}
								}else if(currentid=='#DistrictID'){
									$('#TownID').hide();
									if(seclectedID==0){
										$('#TownID').html(\"{$TownDefaultHtml}\");
									}
								}
								
								if(seclectedID>0) {
									$.get('{$ApiUrl}', {AreaID: seclectedID}, function(data){
										   var html='<option value=\"0\">请选择</option>';
										   if(data.Data.length>0){
												 for(var i=0; i<data.Data.length; i++){
												     html += '<option value='+data.Data[i].AreaID+'>'+data.Data[i].AreaName+'</option>';
						                         }
						                         $(id).html(html);  $(id).show();
											}else{
												 $(id).hide(); $(id).html(html);
							                }
									}, 'json');
				                }
							}
							$('#ProvinceID').change( function(){ changeArea('#ProvinceID','#CityID'); });
							$('#CityID').change( function(){ changeArea('#CityID','#DistrictID'); });
							$('#DistrictID').change( function(){ changeArea('#DistrictID','#TownID'); });
						});
					</script>";
				}
				break;
			case 'membergroupselect':
				$mg = D('Admin/MemberGroup');
				$mgInfo = $mg->getMemberGroup();
				if($mgInfo){
					$attribute[$i]['html'] = "<select id='$name' name='$name'    $style  $class  >";
					if( isset($attribute[$i]['FirstValue']) && isset($attribute[$i]['FirstText']) ){
						$firstText = $attribute[$i]['FirstText'];
						$firstValue = $attribute[$i]['FirstValue'];
						$attribute[$i]['html'] .= "<option value='$firstValue'>$firstText</option>";
					}
					for($j = 0; $j < count($mgInfo); $j++){
						$value = $mgInfo[$j]['MemberGroupID'];
						$text = $mgInfo[$j]['MemberGroupName'];

						if( isset($SelectedValue) ){ //自定义选中项
							$selected = ( $SelectedValue == $value) ? "selected='selected'" : '';
						}

						$attribute[$i]['html'] .= "<option value='$value'  $selected>$text</option>";
					}
					$attribute[$i]['html'] .= "</select>";
				}
				break;
			case 'admingroupselect':
				$ag = D('Admin/AdminGroup');
				$agInfo = $ag->getAdminGroup();
				if($agInfo){
					$attribute[$i]['html'] = "<select id='$name' name='$name'    $style  $class  >";
					if( isset($attribute[$i]['FirstValue']) && isset($attribute[$i]['FirstText']) ){
						$firstText = $attribute[$i]['FirstText'];
						$firstValue = $attribute[$i]['FirstValue'];
						$attribute[$i]['html'] .= "<option value='$firstValue'>$firstText</option>";
					}
					for($j = 0; $j < count($agInfo); $j++){
						$value = $agInfo[$j]['AdminGroupID'];
						$text = $agInfo[$j]['AdminGroupName'];
							
						if( isset($SelectedValue) ){ //自定义选中项
							$selected = ( $SelectedValue == $value) ? "selected='selected'" : '';
						}
							
						$attribute[$i]['html'] .= "<option value='$value'  $selected>$text</option>";
					}
					$attribute[$i]['html'] .= "</select>";
				}
				break;
			case 'specialselectno':
			case 'specialselect':
				$s = D('Admin/Special');
				$Special = $s->getSpecial(array('IsEnable'=>1)); //获取当前频道所有的专题
				if( $Special ){ //如果没有专题, 则没有显示
					$attribute[$i]['html'] = "<select  id='$name' multiple='multiple' size='5' name='".$name."[]'    $style  $class  >";
					$attribute[$i]['html'] .= "<optgroup label='请选择所属专题（按Ctrl+左键可进行多选）'>";
					//频道下拉框，并显示文字"不显示这个版块"，主要用于模板配置
					if($attribute[$i]['DisplayType']=='specialselectno'){
						$selected = in_array(0, $attribute[$i]['SelectedValue'])  ? "selected='selected'" : '';
						$attribute[$i]['html'] .= "<option value='0' $selected>".L('NotShowSection')."</option>";
					}
					if( isset($attribute[$i]['FirstValue']) && isset($attribute[$i]['FirstText']) ){ //默认第一个值
						$firstText = $attribute[$i]['FirstText'];
						$firstValue = $attribute[$i]['FirstValue'];
						$attribute[$i]['html'] .= "<option value='$firstValue'>$firstText</option>";
					}
					$n = count($Special);
					for($j = 0; $j < $n; $j++){
						$value = $Special[$j]['SpecialID'];
						$text = $Special[$j]['SpecialName'];
						if( isset($SelectedValue) ){ //自定义选中项
							$selected = in_array($value, $SelectedValue)  ? "selected='selected'" : '';
						}
						$attribute[$i]['html'] .= "<option value='$value'  $selected>$text</option>";
					}
					$attribute[$i]['html'] .= "</optgroup></select>";
				}
				break;
			case 'channelexselectno':
			case 'channelexselect':
				$mc = D('Admin/Channel');
				if( isset($attribute[$i]['AdminGroupID']) ){ //管理员
					$HasSingleModel =isset($attribute[$i]['HasSingleModel']) ? $attribute[$i]['HasSingleModel'] : true;
					$HasLinkModel =isset($attribute[$i]['HasLinkModel']) ? $attribute[$i]['HasLinkModel'] : true;
					$ExcludeChannel =isset($attribute[$i]['ExcludeChannel']) ? $attribute[$i]['ExcludeChannel'] : -1;
					
					$Depth = isset($attribute[$i]['Depth']) ? $attribute[$i]['Depth'] : -1;
					$Prefix =isset($attribute[$i]['Prefix']) ? $attribute[$i]['Prefix'] : '&nbsp;&nbsp;&nbsp;&nbsp;├─';
					$field = 'ChannelID,ChannelName,HasChild,ChannelModelID';
					$AdminGroupID = $attribute[$i]['AdminGroupID'];
					$Channel = $mc->getChannel(0, $HasSingleModel, $HasLinkModel, $ExcludeChannel,$Depth, $Prefix, $AdminGroupID,$field);
				}else{ //会员
					$MemberGroupID = $attribute[$i]['MemberGroupID'];
					$Channel = $mc->getChannelPurview(0, $MemberGroupID, '&nbsp;&nbsp;&nbsp;&nbsp;├─ ', false);
				}
				
				if( $Channel ){ //如果没有专题, 则没有显示
					$attribute[$i]['html'] = "<select  id='$name' multiple='multiple' size='6' name='".$name."[]'    $style  $class  >";
					$attribute[$i]['html'] .= "<optgroup label='请选择频道（按Ctrl+左键可进行多选）'>";
					//频道下拉框，并显示文字"不显示这个版块"，主要用于模板配置
					if($attribute[$i]['DisplayType']=='channelexselectno'){
						$selected = in_array(0, $attribute[$i]['SelectedValue'])  ? "selected='selected'" : '';
						$attribute[$i]['html'] .= "<option value='0' $selected>".L('NotShowSection')."</option>";
					}
					if( isset($attribute[$i]['FirstValue']) && isset($attribute[$i]['FirstText']) ){ //默认第一个值
						$firstText = $attribute[$i]['FirstText'];
						$firstValue = $attribute[$i]['FirstValue'];
						$attribute[$i]['html'] .= "<option value='$firstValue'>$firstText</option>";
					}
					$n =count($Channel);
					for($j = 0; $j < $n; $j++){
						$value = $Channel[$j]['ChannelID'];
						if($value == 0 || $value == 1) continue;
						$cmid = $Channel[$j]['ChannelModelID'];
						if(!$attribute[$i]['DisplayType']=='channelexselectno'){
							if( $Channel[$j]['HasChild'] == 0 && ($cmid == 33 || $cmid == 32) ) continue;
						}
						$text = $Channel[$j]['ChannelName'];
						if( isset($SelectedValue) ){ //自定义选中项
							$selected = in_array($value, $SelectedValue)  ? "selected='selected'" : '';
						}else{
                            $selected = '';
                        }
						$attribute[$i]['html'] .= "<option value='$value'  $selected>$text</option>";
					}
					$attribute[$i]['html'] .= "</optgroup></select>";
				}
				break;
			case 'attributegroupselect':  //属性组下拉列表
				$m = D('Admin/Attribute');
				$info = $m->getGroup( $attribute[$i]['ChannelModelID'], -1);
				if($info){
					$attribute[$i]['html'] = "<select id='$name' name='$name'    $style  $class  >";
					if( isset($attribute[$i]['FirstValue']) && isset($attribute[$i]['FirstText']) ){
						$firstText = $attribute[$i]['FirstText'];
						$firstValue = $attribute[$i]['FirstValue'];
						$attribute[$i]['html'] .= "<option value='$firstValue'>$firstText</option>";
					}
					for($j = 0; $j < count($info); $j++){
						$value = $info[$j]['AttributeID'];
						$text = $info[$j]['DisplayName'];

						if( isset($SelectedValue) ){ //自定义选中项
							$selected = ($SelectedValue == $value) ? "selected='selected'" : '';
						}
						$attribute[$i]['html'] .= "<option value='$value'  $selected>$text</option>";
					}
					$attribute[$i]['html'] .= "</select>";
				}
				break;
			case 'labelcheckbox': //标记复选框
				$lbl= D('Admin/Label');
				$LabelInfo = $lbl->getLabel( $attribute[$i]['ChannelModelID'] ,-1,1);
				$checked = explode(',', $value); //转化为数组
				$name .= '[]';
				foreach($LabelInfo as $lk=>$lv) {
					$chk = '';
					if($checked == $lv['LabelID']  || in_array($lv['LabelID'], $checked) ) {
						$chk = "checked='checked'";
					}
					$attribute[$i]['html']  .= "<label><input  id='$name'  type='checkbox' $chk  name='$name' value='".$lv['LabelID']."'>".$lv['LabelName'].'</label>&nbsp;&nbsp;&nbsp;&nbsp;';
				}
				break;
			case 'membergroupcheckbox': //会员分组复选框
				$lbl= D('Admin/MemberGroup');
				$mginfo = $lbl->getMemberGroup( );
				$checked = explode(',', $value); //转化为数组
				$name .= '[]';
				foreach($mginfo as $lk=>$lv) {
					$chk = '';
					if($checked == $lv['MemberGroupID']  || in_array($lv['MemberGroupID'], $checked) ) {
						$chk = "checked='checked'";
					}
					$attribute[$i]['html']  .= "<label><input  id='$name'  type='checkbox' $chk  name='$name' value='".$lv['MemberGroupID']."'>".$lv['MemberGroupName'].'</label>&nbsp;&nbsp;&nbsp;&nbsp;';
				}
				break;
			case 'checkbox':  //复选框
				if($value){
					$name .= '[]';
					$value = str_replace(array("\r\n","\r"), "\n", $value);
					$item = explode ("\n", $value);
					$sv = (array)explode(',', $attribute[$i]['SelectedValue']);
					$nItem = count($item);
					//隐藏是为了解决，都不选时，POST里没有数据的bug
                    if('T'==substr($name, 0, 1)){ //仅对模板装修生效
                        $attribute[$i]['html'] .= "<label style='display: none;'><input type='checkbox'  name='$name'  value='' checked /></label>";
                    }
					for($j = 0; $j < $nItem; $j++){
						$t = explode ('|', $item[$j] ); //value|item|是否是默认
						if( isset($sv) ){ //自定义选中项
							$checked = in_array($t[0], $sv) ? "checked" : '';
						}else{ //默认选中项
							$checked = ( !empty($t[2]) ) ?  "checked" : "";
						}
						$attribute[$i]['html'] .= "<label><input type='checkbox'  id='$name'  name='$name'   $class  value='$t[0]'  $checked />$t[1]</label>&nbsp;&nbsp;&nbsp;&nbsp;";
					}
				}
				break;
			case 'radio':   //单选钮
                $attribute[$i]['html'] .= parse_radio($name, $value, $class,$SelectedValue,$attribute[$i]['Toggle']);
				break;
			case 'adtyperadio':
				$at = D('Admin/AdType');
				$AtInfo = $at->getAdType( );
				$attribute[$i]['html']="";
				$atTemp = true;
				foreach ($AtInfo as $k=>$v){
					if( isset($SelectedValue) ){ //自定义选中项
						$checked = ( $SelectedValue == $v['AdTypeID'] ) ? "checked" : '';
					}else{ //默认选中第一项
						$checked = '';
						if( $atTemp ) $checked = "checked";
						$atTemp = false;
					}
					$attribute[$i]['html'] .= "<label><input type='radio'  id='$name'  name='$name'  $style  $class  value='".$v['AdTypeID']."'  $checked />".$v['AdTypeName']."</label>&nbsp;&nbsp;&nbsp;&nbsp;";
				}
				break;
			case 'attachment': //附件上传
			case 'image':      //图片上传
			case 'imageex':  //图片上传升级版本
                $attribute[$i]['html'] .= parse_upload($attribute[$i], $name, $value, $class, $WebPublic, $helpText, $SelectPicture);
				break;
			case 'textarea':    //多行文本
				$attribute[$i]['html'] = "<textarea $style  $class name='$name'  id='$name' >$value</textarea>";
				break;
			case 'label': //文本标签
				$attribute[$i]['html'] = "<span $style  $class name='$name'  id='$name' >$value</span>";
				break;
			case 'datetime': //日期时间
				$attribute[$i]['html'] = "";
				if (!isset($_js['WdatePickerJs'])){
					$attribute[$i]['html'] .= "<script type='text/javascript' src='__ROOT__/Public/My97DatePicker/WdatePicker.js'></script>";
					$_js['WdatePickerJs'] = 1;
				}
				$attribute[$i]['html'] .="<input name='$name' type='text' class='Wdate' id='$name'  $style  $class  onClick=\"WdatePicker({skin:'whyGreen',dateFmt:'yyyy-MM-dd HH:mm:ss'})\" readonly='readonly'   value='$value' />";
				break;
			case 'date': //日期
				$attribute[$i]['html'] = "";
				if (!isset($_js['WdatePickerJs'])){
					$attribute[$i]['html'] .= "<script type='text/javascript' src='__ROOT__/Public/My97DatePicker/WdatePicker.js'></script>";
					$_js['WdatePickerJs'] = 1;
				}
				$attribute[$i]['html'] .="<input name='$name' type='text' class='Wdate' id='$name'  $style  $class  onClick=\"WdatePicker({skin:'whyGreen',dateFmt:'yyyy-MM-dd'})\" readonly='readonly'   value='$value' />";
				break;
			case 'password'://密码框
				$attribute[$i]['html'] = "<input type='password' $style $class name='$name'  id='$name'   value='$value'  />";
				break;
            case 'list': //数据列表
                $attribute[$i]['html'] = parse_list($name, $value, $helpText, $attribute[$i]['Parameter']);
                break;
			case 'album': //相册
				$data = yd_split($value, array('AlbumTitle','AlbumPicture','AlbumDescription'));
				$attribute[$i]['html'].="<table class='table-album' id='table-album{$name}' border='0'><thead><tr>
					<th class='anum'>序号</th><th class='apic'>相册标题 / 图片</th>
					<th class='ades'>相册描述</th><th class='aop'>操作</th></tr></thead><tbody>";
				if( empty($data) ){
					$attribute[$i]['html'] .= "<tr><td class='anum'>1</td><td class='apic'>
						<input type='text' class='at' id='AlbumTitle' name='AlbumTitle'  value=''  /><br/>
						<input onmouseover='imagefloat(this)' type='text' class='ap' id='{$name}File_1Image' name='AlbumPicture'  value='$value'  /><br/>
						<span class='UploadWrapper'>
						    <input class='btnFileUpload' type='button' value='上传' />
						    <input type='file' class='af' id='{$name}File_1' name='{$name}File_1' onchange='startUpload(this)' />
						</span>";
						
						if( GROUP_NAME == 'Admin'){
							$attribute[$i]['html'] .= "<input onclick='BrowserAlbumServer(this)' id='btn{$name}Server'  name='btn{$name}Server'  type ='button' value='{$SelectPicture}'  class='btnUpload'  />";
						}
						
						$attribute[$i]['html'] .= "</td><td><textarea style='width:97%;height:88px;' class='ad' id='AlbumDescription' name='AlbumDescription'>";
						$attribute[$i]['html'] .= "</textarea></td><td><a onclick='delAlbumItem(this, \"{$name}\")'  class='btnDel'>删除</a></td></tr>";
				}else{  //表示修改
					$anum = 1;
					foreach ( $data as $v){
						$AlbumTitle = $v['AlbumTitle'];
						$AlbumPicture= $v['AlbumPicture'];
						$AlbumDescription = $v['AlbumDescription'];
						$attribute[$i]['html'] .= "<tr><td class='anum'>{$anum}</td><td class='apic'>
						<input type='text' class='at' id='AlbumTitle' name='AlbumTitle'  value='{$AlbumTitle}'  /><br/>
						<input onmouseover='imagefloat(this)' type='text' class='ap' id='{$name}File_{$anum}Image' name='AlbumPicture'  value='{$AlbumPicture}'  /><br/>
						<span class='UploadWrapper'>
						    <input class='btnFileUpload' type='button' value='上传' />
							<input type='file' class='af' id='{$name}File_{$anum}' name='{$name}File_{$anum}' onchange='startUpload(this)' />
						</span>";
						if( GROUP_NAME == 'Admin'){
							$attribute[$i]['html'] .= "<input onclick='BrowserAlbumServer(this)' id='btn{$name}Server'  name='btn{$name}Server'  type ='button' value='{$SelectPicture}'  class='btnUpload'  />";
						}
						$attribute[$i]['html'] .= "</td><td><textarea style='width:97%;height:88px;' class='ad' id='AlbumDescription' name='AlbumDescription'>{$AlbumDescription}";
						$attribute[$i]['html'] .= "</textarea></td><td><a onclick='delAlbumItem(this,\"{$name}\")'  class='btnDel'>删除</a></td></tr>";
						$anum++;
					}
				}
				$attribute[$i]['html'] .= "</tbody><tfoot><tr><td class='add' colspan='4'>";
				$attribute[$i]['html'] .= "<textarea style='display:none;' class='ia' id='{$name}' name='{$name}'></textarea>";
				$attribute[$i]['html'] .= "<a onclick='addAlbumItem(\"{$name}\")'  id='btnSaveAll'>+ 添加相册信息</a></td></tr></tfoot></table>";
				unset($data);
				break;
			case 'relation':  //相关信息
				$m = D('Admin/Info');
				$data = $m->getInfoByIDList($value, array('Field'=>'InfoID,InfoTitle,Html,ChannelID,LinkUrl') );
				$attribute[$i]['html'].="<ul class='relationlist' id='relationlist{$name}'>";
				if(!empty($data)){
					foreach ($data as $v){
						$InfoID = $v['InfoID'];
						$InfoUrl = InfoUrl( $InfoID, $v['Html'], $v['LinkUrl'], false, $v['ChannelID'] );
						$InfoTitle = $v['InfoTitle'];
						$attribute[$i]['html'].="<li><a href='{$InfoUrl}' target='_blank'>{$InfoTitle}</a>";
						$attribute[$i]['html'].="<a onclick='delRelationItem(this, \"{$name}\")' class='btnDel'>删除</a>";
						$attribute[$i]['html'].="<input type='hidden' name='{$name}[]' value='$InfoID' /></li>";
					}
				}
				$attribute[$i]['html'].="</ul><a onclick=\"addRelationItem('{$name}')\"  id='btnSaveAll'>+ 添加相关信息</a>";
				break;
			case 'typeselect': //规格属性
				$m = D('Admin/Type');
				$typeInfo= $m->getType();
				if($typeInfo){
					$attribute[$i]['html'] = "<select id='$name' name='$name'    $style  $class  >";
					$attribute[$i]['html'] .= "<option value='0'>==请选择==</option>";
					for($j = 0; $j < count($typeInfo); $j++){
						$value = $typeInfo[$j]['TypeID'];
						$text = $typeInfo[$j]['TypeName'];
						if( isset($SelectedValue) ){ //自定义选中项
							$selected = ( $SelectedValue == $value) ? "selected='selected'" : '';
						}else{
                            $selected = '';
                        }
						$attribute[$i]['html'] .= "<option value='$value'  $selected>$text</option>";
					}
					$attribute[$i]['html'] .= "</select>";
					if( !empty($SelectedValue) ){
						$attribute[$i]['html'] .="<script>$(document).ready(function() { changeInfoType({$SelectedValue}); });</script>";
					}
				}
				break;
			case 'color': //颜色选择
                $attribute[$i]['html'] = parse_color($name, $value);
				break;
            case 'colorex':  //高级颜色组件
                $attribute[$i]['html'] = parse_colorex($name, $value);
                break;
			case 'coordinate': //地理位置坐标
				if( $name!='Longitude') break;
				$Longitude = isset($attribute[$i]['Longitude']) ? $attribute[$i]['Longitude'] : '';
				$Latitude =    isset($attribute[$i]['Latitude']) ? $attribute[$i]['Latitude'] : '';
				$attribute[$i]['html'] = "经度：<input type='text' style='width:80px;margin-right:6px;' $class name='Longitude'  id='Longitude'  value='{$Longitude}'  />
				纬度：<input type='text' style='width:80px;margin-right:6px;' $class name='Latitude'  id='Latitude'  value='{$Latitude}'  />
				<input type='button' value='从地图上选择...' style='padding:3px 8px; cursor:pointer;' onclick='OpenMap()' />";
				$attribute[$i]['html'] .= "<span id='dlgMap' style='display:none;' title='获取坐标'>
					<div style='margin-bottom:8px;'>
				    	地址：<input type='text' class='textinput' style='width:220px; margin-right:3px; ' id='map_address' value='' />
				        <input type='button' value='搜索' style='padding:2px 7px; cursor:pointer;' onclick='SearchMap()' />
				        <span style='float:right'>
				        	经度：<span id='jd' style='margin-right:15px;'>{$Longitude}</span>
				        	纬度：<span id='wd'>{$Latitude}</span></span>
				    </div><div style='width: 700px; height: 440px;'  id='container'></div>
				</span>";
				/*
				$attribute[$i]['html'] .= '<script type="text/javascript" src="//api.map.baidu.com/api?v=3.0&ak="></script>
				<script>
					var map="";
					$(document).ready(function(){
						map = new BMap.Map("container");
						map.setDefaultCursor("crosshair");
						map.enableScrollWheelZoom(true);
						map.addControl( new BMap.NavigationControl() );
						map.centerAndZoom("北京市", 17);
						
						map.addEventListener("click", function (e) { 
							map.clearOverlays(); 
							var marker = new BMap.Marker(new BMap.Point(e.point.lng, e.point.lat)); 
							map.addOverlay(marker);
							$("#jd").text( e.point.lng.toFixed(4) );
							$("#wd").text( e.point.lat.toFixed(4) );
						});
					});
						
					function OpenMap(){
						var jd = $("#Longitude").val();
						var wd = $("#Latitude").val();
						if( jd && wd && jd!=0 && wd !=0){
							var mypoint = new BMap.Point(jd, wd);
							map.centerAndZoom(mypoint, 17);
							map.clearOverlays();
							var marker = new BMap.Marker(mypoint);
							map.addOverlay(marker);
							map.panBy(330, 180);
						}else{
					    	map.centerAndZoom("北京市", 17);
						}
						
						var d = dialog({
							title: "坐标选择",  id: "dlgMap",  padding: 5, 
							content: document.getElementById("dlgMap"),
							ok: function () {
							    $("#Longitude").val( $("#jd").text() );
								$("#Latitude").val( $("#wd").text() );
							},
							okValue: "确定",
							cancelValue: "关闭",
							cancel: true
						});
						d.show();
					}
					
					function SearchMap() {
						var local = new BMap.LocalSearch(map, {
							renderOptions: { map: map }
						});
						var address = $("#map_address").val();
						local.search(address);
					}
				</script>';
				*/
				break;
            case 'range': //范围组件
                $attribute[$i]['html'] .= parse_range($name, $value, $attribute[$i]['DisplayWidth'],$attribute[$i]['Parameter']);
                break;
            case 'animation':   //动画单选钮
                $attribute[$i]['html'] .= parse_animation($name, $value);
                break;
            case 'font':
                $attribute[$i]['html'] .= parse_font($name, $value, $class);
                break;
            case 'xy': //坐标
                $attribute[$i]['html'] .= parse_xy($name, $value, $attribute[$i]['Parameter']);
                break;
            case 'bg'://背景
                $attribute[$i]['html'] .= parse_bg($attribute[$i], $name, $value, $class, $WebPublic, $helpText, $SelectPicture);
                break;
            case 'button': //按钮
                $attribute[$i]['html'] .= parse_button($name, $value, $helpText,$attribute[$i]['Parameter']);
                break;
            case 'table': //表格
                $attribute[$i]['html'] .= parse_table($name, $value, $helpText);
                break;
            case 'link': //链接
                $attribute[$i]['html'] .= parse_link($name, $value);
                break;
            case 'border'://边框
                $attribute[$i]['html'] .= parse_border($name, $value, $helpText,$attribute[$i]['Parameter']);
                break;
            case 'number': //数字
                $attribute[$i]['html'] .= parse_number($name, $value, $attribute[$i]['DisplayWidth'],$attribute[$i]['Parameter']);
                break;
            case 'line': //分割线
                $attribute[$i]['html'] .= parse_line($name, $value);
                break;
            case 'indextemplateselect': //频道首页模板
                $attribute[$i]['html'] .= parse_template(1,$name, $value, $class);
                break;
            case 'readtemplateselect': //频道阅读模板
                $attribute[$i]['html'] .= parse_template(2, $name, $value, $class);
                break;
            case 'margin': //边距
                $attribute[$i]['html'] .= parse_margin($name, $value, $attribute[$i]['Parameter']);
                break;
            case 'site'://分站管理
                $SiteEnable = $GLOBALS['Config']['SiteEnable'];
                if($SiteEnable=="1"){//若开启了“分站管理”功能，才显示分站字段
                    $attribute[$i]['html'] .= parse_site($name, $value, $attribute[$i]['DisplayName']);
                }else{
                    unset($attribute[$i]);
                }

                break;
            case 'text':  //默认类型
			default:
				$attribute[$i]['html'] = "<input type='text' $style $class name='$name'  id='$name'  value='$value'  />";
				break;
		}
		$list = array('image','imageex','attachment', 'list', 'button', 'border');
		if( $helpText != '' && !in_array($attribute[$i]['DisplayType'], $list) && empty($attribute[$i]['IsMobile']) ){
			$attribute[$i]['html'] .= "<span class='Caution'>$helpText</span>";
		}
			
	}
	return $attribute;
}

/**
 *  改变一些特殊字段的显示类型
 */
function changeFieldDisplayType(&$attr){
    $map = array('IndexTemplate'=>'indextemplateselect', 'ReadTemplate'=>'readtemplateselect');
    $key = $attr['FieldName'];
    if(isset($map[$key])){
        $attr['DisplayType'] = $map[$key];
        $attr['DisplayHelpText'] = '';
    }
}

/**
 *  解析分站管理
 */
function parse_site($name, $value,$displayname) {

    $SitelistHtml = "<ul class='sitelist' id='sitelist'>";
    if(!empty($value)){
        $SiteList = D("Admin/Site")->getSite(1, $value);
        if (!empty($SiteList)) {
            foreach ($SiteList as $k => $v)
                $SitelistHtml .= "<li>{$v['SiteName']}<input type='hidden' name='{$name}[]' value='{$v['SiteID']}' /></li>";
        }
    }

    $SitelistHtml .= "</ul>";

    $SitelistCss="<style type='text/css'>
            ul.sitelist{ overflow:hidden; width:100%;}
            .sitelist li{ height:25px; line-height:25px;text-indent:8px; background:url(__ROOT__/App/Tpl/Admin/Default/Public/images/arrow.gif) no-repeat 0 center; overflow:hidden;width:100%;/*解决ie6bug*/}
            .sitelist li a{ float:left; margin-right:8px;}
            .sitelist li a:hover{ text-decoration:none;}
            .sitelist li .btnDel{float:left;}        
        </style>";

    $html = "<script type='text/javascript'>
    var gDlg = '';
   function addSiteList(){
        gDlg = dialog({
            title: '请选择所属分站',
            id: 'Site',
            padding: 0,
            content: '<div id=\"dlgRelation\"><iframe src=\"__GROUP__/Site/addSiteList/SiteFieldName/{$name}\" name=\"frmSite\" id=\"frmSite\"  scrolling=\"auto\" frameborder=\"0\"  height=\"490px\" width=\"800px;\"></iframe></div>',
            ok: function () {
                var obj = window.frames['frmSite'].document;
                var objSelected = $(obj).find('.checkrow:checked');
                var str = '';
                var n = objSelected.length;
                if(n > 0){
                    for(var i=0; i < n; i++){
                        var siteid = objSelected.eq(i).val();
                        var sitename = objSelected.eq(i).attr('sitename');
                        str += \"<li>\"+ sitename + \"<input type='hidden' name='{$name}[]' value='\" + siteid + \"' /></li>\";
                    }
                }else{
                     str = '<input type=\"hidden\" name=\"{$name}[]\" value=\"\" />';
                }
                $('#sitelist').html('');
                $('#sitelist').append(str);
                this.close();
                return false;
            },
            cancel:function(){
                this.close();
            },
            okValue: '添加并继续',
            cancelValue: '关闭',
            cancel: true
        }).show();
    }
</script>
        {$SitelistCss}
        {$SitelistHtml}    
        <a onclick='addSiteList()'  id='btnSaveAll'>+ {$displayname}</a>
    ";
    return $html;
}

/**
 * 解析频道首页模板
 */
function parse_template($type, $name, $value, $class){
    $data = get_template_list($type);
    $html = "<select id='$name' name='$name'  $class style='width: 200px;'>";
    foreach($data as $k=>$v){
        if( !empty($value) ){ //自定义选中项
            $selected = ($value == $v) ? "selected='selected'" : '';
        }else{ //默认选中项
            $selected = ($k==0) ?  "selected='selected'" : "";
        }
        $html .= "<option  value='{$v}' {$selected}>{$v}</option>";
    }
    $html .= "</select><span class='btnCreateTemplate' onclick='createTempate({$type})'>+创建模板</span>";
    return $html;

}

/**
 * 获取模板文件列表
 */
function get_template_list($type=1){
    $data = array();
    $ThemeName = C("HOME_DEFAULT_THEME");
    $path = TMPL_PATH."Home/{$ThemeName}/";
    if(1==$type){
        $path .= 'Channel/';
    }else{
        $path .= 'Info/';
    }
    $map = array('resume.html'=>1, 'search.html'=>1);
    $files = glob("{$path}*.html");
    foreach($files as $v){
        $filename = basename($v);
        if(!isset($map[$filename])){
            $data[] = $filename;
        }
    }
    return $data;
}

/**
 * 判断是否在装修
 */
function isDecoration(){
    if(MODULE_NAME=='Decoration' && GROUP_NAME == 'Admin'){
        return true;
    }else{
        return false;
    }
}

/**
 * 解析CKEditor富文本编辑器
 */
function parse_ck_editor($name, $value, $width, $height, $paramter, &$js) {
    $WebPublic = __ROOT__ . '/Public/';
    $CkeditorJs = "";
    if (!isset($_js['CkeditorJs'])) {
        $CkeditorJs = "<script type='text/javascript' src='{$WebPublic}ckeditor/ckeditor.js'></script>";
        $js['CkeditorJs'] = 1;
    }
    $otherConfig = '';
    $bgcolor = '';
    if (isDecoration()) {
        $bgcolor = '#eeeeee';
        $otherConfig = "window.CKEDITOR.addCss('.cke_editable{background-color: #95cf34}');";
    }
    $filebrowserImageUploadUrl = '';
    $UploadUrl  = CkEditorUploadUrl();
    if(!empty($UploadUrl)){
        $filebrowserImageUploadUrl = "'filebrowserImageUploadUrl':'{$UploadUrl}',";
    }
    $FileBrowser = '';
    $customConfig = '';
    if (GROUP_NAME == 'Admin') {
        $bgcolor = '#f0f0f0';
        //只有管理员才能调用图像浏览器
        $FileBrowser = "";
        $customConfig = '';
    } else if (GROUP_NAME == 'Member') {
        $bgcolor = '#e6e6e6';
        $FileBrowser = "";
        $customConfig = "customConfig:'{$WebPublic}ckeditor/config_member.js',";
    }

    $html = "<!-- 编辑器 开始 -->
        <textarea id='{$name}' name='{$name}'>{$value}</textarea>
        {$CkeditorJs}
        <script type='text/javascript'>
            $(document).ready(function(){
                {$otherConfig}
                window.CKEDITOR_BASEPATH='{$WebPublic}ckeditor/';
                CKEDITOR.replace('{$name}', {
                    'uiColor': '{$bgcolor}', 
                    'width':'{$width}', 
                    'height':'{$height}',
                    {$FileBrowser}
                    {$customConfig}
                    {$filebrowserImageUploadUrl}
                 });
             });
        </script>
    <!-- 编辑器 结束 -->";
    return $html;
}

/**
 * 解析百度UEEditor编辑器
 */
function parse_ue_editor($name, $value, $width, $height, $paramter, &$js) {
    $WebPublic = __ROOT__ . '/Public/';
    $CuditorJs = "";
    if (!isset($_js['CuditorJs'])){
        $CuditorJs = "<!-- 配置文件 -->
            <!-- 加载编辑器的容器 -->
            <script type='text/javascript' src='{$WebPublic}ueditor/ueditor.config.js'></script>
            <!-- 编辑器源码文件 -->
            <script type='text/javascript' src='{$WebPublic}ueditor/ueditor.all.min.js'></script>
            <!-- 编辑器的秀米插件 -->
            <script type='text/javascript' src='{$WebPublic}ueditor/xiumi-ue-dialog-v5.js'></script>
            <link href='{$WebPublic}ueditor/xiumi-ue-v5.css' rel='stylesheet' type='text/css' />
            ";
        $_js['CuditorJs'] = 1;
    }
    if(empty($height)){
        $height =  300;
    }else{
        $height = intval($height)+100;
    }
    //装修时是嵌入在对话框中(dialog的zindex=9999)，所以必须大，否则弹框会被覆盖
    $zindex = isDecoration() ? 10000 : 999;

    $serverUrl = '';
    $UploadUrl  = UEditorUploadUrl();
    if(!empty($UploadUrl)){
        $serverUrl = "serverUrl:'{$UploadUrl}',";
    }

    $html = "
        <!-- 编辑器 开始 -->
        <script type='text/javascript'>window.UEDITOR_HOME_URL ='{$WebPublic}ueditor/';</script>
        <textarea id='".$name."' name='".$name."' style='display:none;' class='ueditor'></textarea>
                {$CuditorJs}
                <script id='{$name}container' name='{$name}content' type='text/plain' style='height:{$height}px;'>{$value}</script>
                <!-- 实例化编辑器 -->
                <script type='text/javascript'>
                    $(document).ready(function(){
                        var ue = UE.getEditor('{$name}container',{ 	
                                initialFrameWidth :'100%',//设置编辑器宽度			
                                zIndex: {$zindex},			               				        	
                                scaleEnabled:true ,
                                {$serverUrl}
                         });
                    });
                </script>			    
        <!-- 编辑器 结束 -->";
    return $html;
}

/**
 * 解析数字
 */
function parse_number($name, $value, $width, $paramter){
    if(empty($width)) $width=100;
    if(!empty($paramter)){
        $data = explode(',', $paramter); //格式：0min, 1max, 2step
        $min= "min='{$data[0]}'";
        $max="max='{$data[1]}'";
        $step = "step='{$data[2]}'";
    }else{
        $min='';
        $max='';
        $step = '';
    }
    $html = "<input type='number' style='width: {$width}px' {$min} {$max} {$step} onkeyup='previewNumber(this)' onchange='previewNumber(this)' class='textinput' name='{$name}' id='{$name}' value='{$value}'  />";
    return $html;
}

function parse_animation($name, $value){
    $types = array();
    $types['进入类型'] = array(
        array('key'=>'', 'name'=>"无效果", 'x'=>'-1', 'y'=>'-1'),

        array('key'=>'bounceIn', 'name'=>"弹性缩放", 'x'=>'-1', 'y'=>'-52'),
        array('key'=>'bounceInDown', 'name'=>"从上弹入", 'x'=>'-1', 'y'=>'-100'),
        array('key'=>'bounceInLeft', 'name'=>"从左弹入", 'x'=>'-1', 'y'=>'-150'),
        array('key'=>'bounceInUp', 'name'=>"从下弹入", 'x'=>'-1', 'y'=>'-200'),
        array('key'=>'bounceInRight', 'name'=>"从右弹入", 'x'=>'-1', 'y'=>'-250'),
        array('key'=>'lightSpeedInRight', 'name'=>"刹车弹入", 'x'=>'-1', 'y'=>'-300'),

        array('key'=>'rotateIn', 'name'=>"旋转", 'x'=>'-1', 'y'=>'-350'),
        array('key'=>'rotateInDownLeft', 'name'=>"左下旋入", 'x'=>'-1', 'y'=>'-400'),
        array('key'=>'rotateInDownRight', 'name'=>"右下旋入", 'x'=>'-1', 'y'=>'-450'),
        array('key'=>'rotateInUpLeft', 'name'=>"左上旋入", 'x'=>'-1', 'y'=>'-500'),
        array('key'=>'rotateInUpRight', 'name'=>"右上旋入", 'x'=>'-1', 'y'=>'-550'),

        array('key'=>'fadeIn', 'name'=>"渐显", 'x'=>'-1', 'y'=>'-600'),
        array('key'=>'fadeInDown', 'name'=>"由上渐显", 'x'=>'-1', 'y'=>'-650'),
        array('key'=>'fadeInUp', 'name'=>"由下渐显", 'x'=>'-1', 'y'=>'-950'),
        array('key'=>'fadeInLeft', 'name'=>"由左渐显", 'x'=>'-1', 'y'=>'-750'),
        array('key'=>'fadeInRight', 'name'=>"由右渐显", 'x'=>'-1', 'y'=>'-850'),

        array('key'=>'slideInDown', 'name'=>"上滑入", 'x'=>'-1', 'y'=>'-1198'),
        array('key'=>'slideInUp', 'name'=>"下滑入", 'x'=>'-1', 'y'=>'-1050'),
        array('key'=>'slideInLeft', 'name'=>"左滑入", 'x'=>'-1', 'y'=>'-1100'),
        array('key'=>'slideInRight', 'name'=>"右滑入", 'x'=>'-1', 'y'=>'-1150'),

        array('key'=>'zoomIn', 'name'=>"弹性放大", 'x'=>'-1', 'y'=>'-1250'),
        array('key'=>'zoomInDown', 'name'=>"下落放大", 'x'=>'-1', 'y'=>'-1300'),
        array('key'=>'zoomInUp', 'name'=>"由上放大", 'x'=>'-1', 'y'=>'-1350'),
        array('key'=>'zoomInLeft', 'name'=>"由左飞入", 'x'=>'-1', 'y'=>'-1400'),
        array('key'=>'zoomInRight', 'name'=>"由右飞入", 'x'=>'-1', 'y'=>'-1450'),
        array('key'=>'rollIn', 'name'=>"翻滚切入", 'x'=>'-1', 'y'=>'-1500'),
    );
    //强调类型
    $types['强调类型'] = array(
        array('key'=>'bounce', 'name'=>"上下晃动", 'x'=>'-50', 'y'=>'-1'),
        array('key'=>'flash', 'name'=>"闪烁", 'x'=>'-50', 'y'=>'-52'),
        array('key'=>'pulse', 'name'=>"心跳", 'x'=>'-50', 'y'=>'-100'),

        array('key'=>'rubberBand', 'name'=>"左右弹跳", 'x'=>'-50', 'y'=>'-150'),
        array('key'=>'shakeX', 'name'=>"左右摇晃", 'x'=>'-50', 'y'=>'-200'),
        array('key'=>'swing', 'name'=>"左右摇摆", 'x'=>'-50', 'y'=>'-250'),
        array('key'=>'tada', 'name'=>"引起注意", 'x'=>'-50', 'y'=>'-300'),

        array('key'=>'wobble', 'name'=>"晃动", 'x'=>'-50', 'y'=>'-350'),
        array('key'=>'jello', 'name'=>"果冻摇晃", 'x'=>'-50', 'y'=>'-400'),
        array('key'=>'flip', 'name'=>"中心翻转", 'x'=>'-50', 'y'=>'-450'),
        array('key'=>'flipInX', 'name'=>"竖向翻转", 'x'=>'-50', 'y'=>'-500'),

        array('key'=>'flipInY', 'name'=>"横向翻转", 'x'=>'-50', 'y'=>'-550'),
        array('key'=>'flipOutX', 'name'=>"竖向翻出", 'x'=>'-50', 'y'=>'-600'),
        array('key'=>'flipOutY', 'name'=>"横向翻出", 'x'=>'-50', 'y'=>'-650'),
    );
    $root = __ROOT__;
    $html = "<div class='animate-container animate-container{$name}'>
    <style>
        .animate-container{$name} .type{
            clear: both; font-size:14px; width: 100px; border: 1px solid #ccc; text-align: center;
            margin: 0 auto;  padding: 5px 0; border-radius: 5px; background: #ddd; color: #333;
         }
        .animate-container{$name} li{ float: left; width: 74px; padding: 3px 5px; cursor: pointer;}
        .animate-container{$name} li .ani-bg{ width: 50px; height: 50px; margin: 0 auto;
            background-image: url('{$root}/App/Tpl/Admin/Default/Public/images/animate.png');
            background-repeat: no-repeat;
            background-position: -1px,-1px;
        }
        .animate-container{$name} li:hover .ani-bg, .animate-container{$name} li.current .ani-bg{ 
            background-image: url('{$root}/App/Tpl/Admin/Default/Public/images/animate-hover.png');
             background-color: #2588fe;
          }
        .animate-container{$name} li .ani-name{ font-size:12px; text-align: center;}
        .animate-container{$name} li:hover .ani-name, .animate-container{$name} li.current .ani-name{ color: #2588fe;}
    </style>
    <script>
            $(document).ready(function(){
                $('.animate-container{$name} li').click(function(){
                    $('.animate-container{$name} li').removeClass('current');
                    $(this).addClass('current');
                    var data = $(this).attr('data');
                    $('#{$name}').val(data);
                });
             });
     </script>";
    foreach($types as $type=>$list){
        $html .= "<div class='type'>{$type}</div>";
        $html .= '<ul>';
        foreach($list as $v){
            $key = "animate__animated animate__{$v['key']}";
            $class = ($value==$key) ? 'class="current"' : '';
            $html .= "<li data='{$key}' {$class} onmousedown='previewAnimation(this)'>
                <div class='ani-bg' style='background-position: {$v['x']}px {$v['y']}px'></div>
                <div class='ani-name'>{$v['name']}</div>
           </li>";
        }
         $html .= '</ul>';
    }
    $html .= "<input type='hidden' id='{$name}' name='{$name}' value='{$value}' />";
    $html .= '</div>';
    return $html;
}

/**
 * 解析上传
 * $parentName：表示父级的name
 */
function parse_upload($attribute, $name, $value, $class, $WebPublic, $helpText, $SelectPicture){
    $isthumb = ($name=='InfoPicture') ? 1 : 0; //只有信息的缩略图才生成缩略图
    $nowaterlist = array('MemberAvatar'); //不加水印的上传
    $addwater = in_array($name, $nowaterlist) ? '/addwater/no' : '';
    $html = '';
    if( empty($attribute['IsMobile'] ) ){
        $html.="<script type='text/javascript'>\n";
        if( GROUP_NAME == 'Admin'){ //会员端不显示从服务器浏览
            $html.="function Browser{$name}Server(){
            	            if(checkSafeQuestion()) return;
						    FileManager.selectActionFunction = Set{$name}FileField;
						    FileManager.popup();
						}
						function Set{$name}FileField(fileUrl){
						    var obj = document.getElementById( '{$name}FileImage' );
						    obj.value = fileUrl;
						    obj.focus();
						}";
        }
        $height = isset($attribute['DisplayHeight']) ? (int)$attribute['DisplayHeight'] : 0;  //2024-05-29
        $style = "width:497px;";  //固定宽度
        if(!empty($height) ) $style .= "height:$height;";
        $style = "style='$style'";
        $isDecoration = isDecoration() ? 1 : 0;
        //jquery加载
        $html.="$(document).ready(function(){
					    $('#{$name}FileImage').powerFloat({targetMode: 'ajax',targetAttr: 'value',position: '5-7'});
					    $('#{$name}File').change(function(){
					    	  if( !$(this).val() ) return;
					    	  var action = '__URL__/Upload/currentfile/{$name}File/isthumb/{$isthumb}{$addwater}';
					    	  var isDecoration= '{$isDecoration}';
					    	  if(isDecoration==1){
					    	        $('#frmSaveConfig').attr('action', action);
					    	        ajaxSubmit();
					    	   }else{
					    	   		$('form:first').attr('action', action);
						            $('form:first').submit();
					    	   }

					    });
					});
					</script>";

        if( $height > 50 ){
            $html.="<textarea  {$style} {$class} id='{$name}FileImage' name='$name' >$value</textarea>";
        }else{
            if(empty($class)){
                $class = 'class="UploadInput"' ;
            }else{
                $class = substr_replace($class," UploadInput", -1, 0);
            }
            $html.="<input type='text' $style $class id='{$name}FileImage' name='$name'  value='$value'  />";
        }
        //在以下span标签加上id，是为了实现toggle显示和隐藏
        $html.="<span class='UploadWrapper' id='{$name}'>
					    <input class='btnFileUpload' type='button' value='上传' />
					    <input class='InputFile' id='{$name}File' name='{$name}File' type ='file' $class />
					</span>";
        //$attribute[$i]['html'].="<input id='btn{$name}Upload' name='btn{$name}Upload'  type ='submit' value='点击上传' class='btnUpload'  />";
        if( GROUP_NAME == 'Admin'){ //会员端不显示从服务器浏览
            $html.="<input id='btn{$name}Server' onclick='Browser{$name}Server()' name='btn{$name}Server'  type ='button' value='{$SelectPicture}'  class='btnUpload'  />";
        }
        if( $helpText != ''){
            if( strlen($helpText) > 50 ) {
                $html .= "<br/><span class='Caution' style='margin-left:0;'>{$helpText}</span>";
            }else{
                $html .= "<span class='Caution'>{$helpText}</span>";
            }
        }
    }else{
        $html.="<script type='text/javascript'>
						$(document).ready(function(){
						    $('#{$name}File').change(function(){
							      $('form:first').attr('action','__URL__/Upload/currentfile/{$name}File/isthumb/{$isthumb}{$addwater}');
							      $('form:first').submit();
						    });
						});
					</script>
					<div class='weui-uploader'>
							<div class='weui-uploader__bd'>
						          <ul class='weui-uploader__files' id='uploaderFiles'>
						            <li id='{$name}FileImage' class='weui-uploader__file' style='background-image:url({$value})'></li>
						          </ul>
						          <div class='weui-uploader__input-box'>
						          	<input class='{$name}FileImage' name='{$name}' type ='hidden' value='{$value}' />
						            <input id='{$name}File' name='{$name}File' class='weui-uploader__input' type='file' accept='image/*' >
						          </div>
					        </div>
					</div>";
    }
    return $html;
}

/**
 * 解析背景
 */
function parse_bg($attribute, $name, $value, $class, $WebPublic, $helpText, $SelectPicture){
    $wayName = "{$name}Way";
    $colorStartName = "{$name}StartColor";
    $colorEndName = "{$name}EndColor";
    $list = array('无背景', '纯色填充', '渐变填充', '图片填充'); //0,1,2,3
    //格式：0背景类型, 1背景颜色(开始),2背景颜色(结束)，3背景图片，4平铺,5背景大小自适应，6背景位置，7锁定背景位置,8:角度
    $temp = explode(',', $value);
    $bgWay = $temp[0];
    $html = "<div class='bg-container' style='padding-top: 5px;'>
        <style>
            .{$colorStartName}, .{$colorEndName}{ 
                display: none;
            }
        </style>";
    foreach($list as $k=>$v){
        $checked = ($bgWay == $k) ? "checked" : '';
        $html .= "<label style='padding-right:15px;'><input type='radio'  id='{$wayName}'  name='{$wayName}'  value='{$k}'  $checked />{$v}</label>";
    }
    $html .= "<div class='{$name}bg-container'  style='margin-top:8px;'>";
    $html .= parse_upload($attribute, $name.'Image', $temp[3], "class='textinput'", $WebPublic, $helpText, $SelectPicture);
    $html .= '</div>';

    $html .= "<div style='margin-top:5px;'><span class='{$colorStartName}'>";
    $params = array('name'=>$name, 'type'=>'bg');
    $html .= parse_colorex($colorStartName, $temp[1], '', $params);
    $html .= "</span>";

    $data = array(
        'repeat'=>'平铺', 'no-repeat'=>'不平铺', 'repeat-y'=>'纵向平铺', 'repeat-x'=>'横向平铺'
    );
    $html .= parse_myselect("{$name}Repeat", $temp[4], 'style="margin-left:10px;"', "{$name}bg-container", $data);

    $data = array(
        ''=>'背景auto', 'contain'=>'自适应contain',
        'cover'=>'完整显示cover', 'auto 100%'=>'宽度适应', '100% auto'=>'高度适应'
    );
    $html .= parse_myselect("{$name}Size", $temp[5], 'style="margin-left:10px;"', "{$name}bg-container", $data);

    $data = array(
        ''=>'背景位置',
        'left top'=>'左上',        'center top'=>'中上', 'right top'=>'右上',
        'left center'=>'左中',    'center center'=>'正中', 'right center'=>'右中',
        'left bottom'=>'左下', 'center bottom'=>'中下', 'right bottom'=>'右下',
    );
    $html .= parse_myselect("{$name}Position", $temp[6], 'style="margin-left:10px;"', "{$name}bg-container", $data);
    $checked = ($temp[7]=='fixed') ? "checked='checked'" : '';
    $html .= "<label  class='{$name}bg-container' style='margin-left:10px;'>";
    $html .= "<input type='checkbox' class='textinput'  id='{$name}Attachment'  name='{$name}Attachment' value='fixed'  $checked />";
    $html .= "锁定背景位置</label>";

    $html .="<span class='{$colorEndName}'> - ";
    $params = array('name'=>$name, 'type'=>'bg');
    $html .= parse_colorex($colorEndName, $temp[2], '', $params);
    $angleName = "{$name}Angle";
    $html .="渐变角度：<input type='number' min='0' max='360' class='textinput' style='width: 60px' name='{$angleName}' id='{$angleName}' value='{$temp[8]}' />
            <span style='margin-left: 3px; color:#999;font-size:12px;'>范围：0-360，0从下到上 90从左到右</span></span>";
    $html .= "<script>
            $(document).ready(function(){
                function change{$name}BgWay(way){
                    way = parseInt(way); //必须转化为纯数字
                    $('.{$name}bg-container').hide();
                    switch (way) {
                      case 1: //纯色
                          $('.{$colorStartName}').show();
                          $('.{$colorEndName}').hide();
                          break;
                      case 2: //渐变
                          $('.{$colorStartName}').show();
                          $('.{$colorEndName}').show();
                          break;
                      case 3: //图片
                          $('.{$colorStartName}').show();
                          $('.{$colorEndName}').hide();
                          $('.{$name}bg-container').show();
                          break;
                       default: //0，无
                          $('.{$colorStartName}').hide();
                          $('.{$colorEndName}').hide();
                          break;
                    }
                    if(typeof previewBg === 'function')  {
                        previewBg('{$name}');
                    }
                }
                //点击方式
                $('input[name=\"{$wayName}\"]').click(function(){
                    var way =  $(this).val();
                    change{$name}BgWay(way);
                });
                //角度改变
                $('#{$angleName},#{$name}Repeat,#{$name}Size,#{$name}Position').change(function(){
                    previewBg('{$name}');
                });
                //获取焦点
                 $('#{$name}ImageFileImage').focus(function(){
                    previewBg('{$name}');
                });
                //监听文本框事件的改变         
                 $('#{$angleName},#{$name}ImageFileImage').keyup(function(){
                    previewBg('{$name}');
                });
                //初始化
                change{$name}BgWay('{$bgWay}');
            });
        </script>";
    $html .= "<input type='hidden' id='{$name}' name='{$name}' value='' />";
    $html .= '</div>';
    return $html;
}

function parse_myselect($name, $value, $style, $class, $data){
    $html = "<select id='{$name}' name='{$name}' class='textinput {$class}' {$style}>";
    foreach($data as $k=>$v){
        $selected = ($value == $k) ? "selected='selected'" : '';
        $html .= "<option value='{$k}' $selected>{$v}</option>";
    }
    $html .= "</select>";
    return $html;
}

function parse_range($name, $value, $width, $parameter){
    $rangeWidth = intval($width); //不能带单位
    $temp = explode(',', $parameter); //格式：from,to,step,format,showlabel
    $from = $temp[0];
    $to = $temp[1];
    $step = isset($temp[2]) ? $temp[2] : 1;
    $format = isset($temp[3]) ? $temp[3] : '%s';
    $showScale = empty($temp[4]) ? 'false' : 'true';
    if(empty($rangeWidth)) $rangeWidth = 300;
    $html ="<div style='padding: 5px 0;'>
        <div style='float: left;margin-right: 12px;'><input name='$name' type='hidden' class='single-slider' id='$name'  value='{$value}' /></div>
        <div style='float: left; margin-left: 10px;'>
        <input type='number' min='{$from}' max='{$to}' id='{$name}Input' class='textinput' style='width: 75px' value='{$value}' /></div>
    </div>
            <script>
                    $(document).ready(function(){
                        $('#{$name}').jRange({ 
                            theme:'theme-blue',  
                            snap: true, 
                            showLabels: true, 
                            format: '{$format}', 
                            showScale:{$showScale},
                            from: {$from},  
                            to: {$to}, 
                            step: {$step},  
                            width: {$rangeWidth},
                            onstatechange:function () {
                                var currentValue = $('#{$name}').val();
                                 $('#{$name}Input').val(currentValue);
                                 if(typeof previewRange === 'function')  previewRange('{$name}', currentValue);
                              },
                         });
                     });
                    $('#{$name}Input').change(function() {
                        var currentValue = $('#{$name}Input').val();
                        $('#{$name}').jRange('setValue', currentValue);
                    });
                    $('#{$name}Input').keyup(function() {
                        var currentValue = $('#{$name}Input').val();
                        $('#{$name}').jRange('setValue', currentValue);
                    }); 
            </script>";
    return $html;
}

/**
 * 坐标解析
 */
function parse_xy($name, $value, $parameter){
    $map = array('xName'=>'左', 'xShow'=>1,  'yName'=>'上', 'yShow'=>1, 'zName'=>'下', 'zShow'=>0);
    if(!empty($parameter)){
        //将单引号替换为双引号，否则json_decode返回null
        $parameter = str_ireplace("'", '"', $parameter);
        $temp = json_decode($parameter, true);
        $map = array_merge($map, $temp);
    }
    $nameX = "{$name}X";
    $nameY = "{$name}Y";
    $nameZ = "{$name}Z";
    $temp = explode(',', $value);
    $xStyle = ($map['xShow']==1) ? '' : 'display:none;';
    $yStyle = ($map['yShow']==1) ? '' : 'display:none;';
    $zStyle = ($map['zShow']==1) ? '' : 'display:none;';
    $x = $temp[0];
    $y = isset($temp[1]) ? $temp[1] : '';
    $z = isset($temp[2]) ? $temp[2] : '';
    $preivew = " onkeyup=\"previewXY('{$name}')\" onchange=\"previewXY('{$name}')\" ";
    $html = "<input type='hidden' id='{$name}' name='{$name}' value='' />
    <span style='margin-right: 10px;{$xStyle}'>
        {$map['xName']}：<input {$preivew} type='number' style='width: 90px;' class='textinput' name='{$nameX}'  id='{$nameX}'  value='{$x}'  />
    </span>
    <span style='margin-right: 10px;{$yStyle}'>
        {$map['yName']}：<input {$preivew} type='number' style='width: 90px' class='textinput' name='{$nameY}'  id='{$nameY}'  value='{$y}'  />
    </span>
    <span style='{$zStyle}'>
        {$map['zName']}：<input {$preivew} type='number' style='width: 90px' class='textinput' name='{$nameZ}'  id='{$nameZ}'  value='{$z}'  />
    </span>";
    return $html;
}

function parse_color($name, $value){
    $html = "<input type='text' style='width:90px' class='textinput' name='{$name}'  id='{$name}'  value='{$value}'  />
				<input type='text' class='textinput' style='width:33px; border:1px solid #ccc; cursor:pointer;background:{$value}' id='{$name}ColorBox' readonly='readonly'/>
				<script>
				$(document).ready(function(){
				    $('#{$name},#{$name}ColorBox').colorpicker({
						fillcolor:false,
						success:function(o,color){
							$('#{$name}').val(color);
							$('#{$name}').css('color',color);
							$('#{$name}ColorBox').css('background',color);
						}
					});
				});
				</script>";
    return $html;
}

/**
 * 高级颜色组件
 * $n序号，仅在list里有传入
 * $params：name：原始name，type：来源类型（如：font，颜色作为font的属性）
 */
function parse_colorex($name, $value, $class='', $params=array()){
    $params = json_encode($params);
    $html = "<input onChange=\"change{$name}Color(this)\" onInput=\"input{$name}Color(this)\" data-jscolor=\"{}\" type='text' style='width:125px' class='textinput {$class}' name='{$name}'  id='{$name}'  value='{$value}'  />
        <script>
            function  change{$name}Color(obj){
                if(!obj.jscolor) return;
                var picker = obj.jscolor;
                 var currentColor = $(obj).val();
                 if(currentColor.length>=7 && 1 == picker.channels.a){
                    var color = picker.toHEXString();
                    $(obj).val(color);
                 }
                  if(typeof previewColor === 'function') {
                      previewColor(obj, currentColor, {$params});
                  }
            }
            function  input{$name}Color(obj){
                 if(!obj.jscolor) return;
                var picker = obj.jscolor;
                var currentColor = $(obj).val();
                if(currentColor.length>=7){
                    var color = (1 == picker.channels.a) ? picker.toHEXString() :  picker.toHEXAString();
                    $(obj).val(color);
                }
                 if(typeof previewColor === 'function') {
                     previewColor(obj, currentColor, {$params});
                 }
            }
        </script>";
    return $html;
}

/**
 *  字体解析
 */
function parse_font($name, $value, $class){
    $temp = explode(',', $value); //格式：0字体,1大小,2颜色,3行高，4加粗，5斜体，6下划线，7删除线，8对齐方式，9手机端字体、10左右内边距
    if(!isset($temp[8]))   $temp[8] = '';
    if(!isset($temp[9]))   $temp[9] = '';
    if(!isset($temp[10])) $temp[10] = '';

    //0字体
    $fonts = array(
        ''=>'默认',
        'Microsoft YaHei'=>'微软雅黑', 'NSimSun'=>'新宋体', 'FangSong'=>'仿宋', 'KaiTi'=>'楷体',
        'SimHei'=>'黑体', 'Arial'=>'Arial', 'Arial Black'=>'Arial Black', 'Times New Roman'=>'Times New Roman',
        'Courier New'=>'Courier New', 'Tahoma'=>'Tahoma', 'Verdana'=>'Verdana',
    );
    $familyName = "{$name}Family";
    $html = "<select onchange=\"previewFont(this,'{$name}','font-family')\" name='{$familyName}' style='margin-right: 4px;width: 110px'  $class  >";
    foreach($fonts as $fontValue=>$fontName){
        $selected = ( $temp[0] == $fontValue) ? "selected='selected'" : '';
        $html .= "<option value='{$fontValue}' $selected>{$fontName}</option>";
    }
    $html .= "</select>";

    //1大小
    $sizeName = "{$name}Size";
    $html .= "<select  onchange=\"previewFont(this, '{$name}', 'font-size')\" name='{$sizeName}'  id='{$sizeName}' style='margin-right: 4px;'  $class  >";
    $html .= "<option value=''>大小</option>";
    $selected = ( is_numeric($temp[1]) && $temp[1]==0) ? "selected='selected'" : '';
    $html .= "<option value='0' {$selected}>不显示</option>";
    for($j = 10; $j <= 60; $j++){
        $selected = ( $temp[1]== $j) ? "selected='selected'" : '';
        $html .= "<option value='{$j}' $selected>{$j}px</option>";
    }
    $html .= "</select>";

    //2颜色
    $params = array('name'=>$name, 'type'=>'font');
    $html .= parse_colorex("{$name}Color", $temp[2], '', $params);
    $html .= "<script>
        $(document).ready(function(){
            //点击字体
            $('.{$name} .myfontstyle').click(function(){
                var type =  $(this).attr('type');
                var obj = $('#{$name}'+type);
                var value = obj.val();
                if(value == 1){
                    $(this).removeClass('ison');
                    obj.val(0);
                }else{
                    $(this).addClass('ison');
                    obj.val(1);
                    value = 0;
                }
                //以下代码用于编辑预览
                var map = {
                    'Bold': {'name':'font-weight', 'v0':'bold', 'v1':'normal'}, 
                    'Italic': {'name':'font-style',    'v0':'italic', 'v1':'normal'}, 
                    'Underline': {'name':'text-decoration', 'v0':'underline', 'v1':'none'}, 
                    'Line': {'name':'text-decoration',           'v0':'line-through', 'v1':'none'}, 
                };
                 if(typeof previewFont === 'function') {
                     previewFont(this, '{$name}', map[type]['name'], map[type]['v'+value]);
                 }
            });
        });
    </script>";
    //3行高
    $html .= "<select onchange=\"previewFont(this, '{$name}', 'line-height')\" name='{$name}LineHeight' style='margin-right: 4px;'  $class  >";
    $html .= "<option value=''>行高</option>";
    $lineHeight = floatval($temp[3]);
    for($i = 0.4; $i <= 5; $i+=0.2){
        $selected = abs($lineHeight-$i)<0.001 ? "selected='selected'" : '';
        $html .= "<option value='{$i}' $selected>{$i}em</option>";
    }
    $html .= "</select>";

    //8对齐方式
    $aligns = array(''=>'对齐方式', 'left'=>'居左', 'center'=>'居中', 'right'=>'居右', 'justify'=>'两端对齐');
    $alignName = "{$name}Align";
    $html .= "<select onchange=\"previewFont(this, '{$name}', 'text-align')\" name='{$alignName}' style='margin-right: 4px;'  $class  >";
    foreach($aligns as $k=>$v){
        $selected = (isset($temp[8]) && $temp[8] == $k) ? "selected='selected'" : '';
        $html .= "<option value='{$k}' $selected>{$v}</option>";
    }
    $html .= "</select>";

    //10 左右内边距
    $paddingName = "{$name}Padding";
    $paddingX = $temp[10];
    $html .= "<select  onchange=\"previewFont(this, '{$name}', 'padding')\" name='{$paddingName}'  id='{$paddingName}' style='margin-right: 0px;'  $class  >";
    $selected = (0==strlen($paddingX)) ? "selected='selected'" : '';
    $html .= "<option value='' $selected>左右边距</option>";
    for($j = 0; $j <= 100; $j+=2){
        $selected = (is_numeric($paddingX) && $paddingX== $j) ? "selected='selected'" : '';
        $html .= "<option value='{$j}' $selected>{$j}px</option>";
    }
    $html .= "</select>";

    //4加粗、5斜体、6下划线、7删除线
    $bold = ($temp[4]==1) ? "ison" : '';
    $italic = ($temp[5]==1) ? "ison" : '';
    $underline = ($temp[6]==1) ? "ison" : '';
    $line = ($temp[7]==1) ? "ison" : '';
    $html .= " <style>
            .myfontstyle{ border: 1px solid #ccc;  margin: 0 2px; color: #333; cursor: pointer; background: #eee; font-size:14px;}
            .ison{ background: #2589ff; color:#fff;}
         </style>
         <input type='hidden' id='{$name}' name='{$name}' value='' />
         <input type='hidden' id='{$name}Bold'  name='{$name}Bold' value='{$temp[4]}' />
         <input type='hidden' id='{$name}Italic' name='{$name}Italic' value='{$temp[5]}' />
         <input type='hidden' id='{$name}Underline' name='{$name}Underline' value='{$temp[6]}' />
         <input type='hidden' id='{$name}Line' name='{$name}Line' value='{$temp[7]}' />
         <span class='{$name}'>
            <span type='Bold' class='myfontstyle {$bold}' title='加粗' style='padding: 4px 8px;'><b>B</b></span>
            <span type='Italic'  class='myfontstyle {$italic}' title='斜体' style='padding: 4px 12px;'><i>I</i></span>
            <span type='Underline'  class='myfontstyle {$underline}' title='下划线' style='padding: 4px 9px;'><u>U</u></span>
            <span type='Line'  class='myfontstyle {$line}' title='删除线' style='padding: 4px 9px;'><s>S</s></span>
        </span>
    ";
    //9大小
    $mobileSizeName = "{$name}SizeMobile";
    $mobileSize = $temp[9];
    $html .= "<select  id='{$mobileSizeName}' onchange=\"previewFont(this, '{$name}', 'font-size-mobile')\" pcid='{$sizeName}' name='{$mobileSizeName}' $class  >";
    $selected = (0==strlen($mobileSize)) ? "selected='selected'" : '';
    $html .= "<option value='' {$selected}>移动端大小</option>";
    $selected = (is_numeric($mobileSize)&&$mobileSize==0) ? "selected='selected'" : '';
    $html .= "<option value='0' {$selected}>不显示</option>";
    for($j = 0.4; $j <1.5; $j+=0.1){
        $value = round($j, 2) ;
        if($value != 1.0){
            $selected = ($mobileSize== $value) ? "selected='selected'" : '';
            $html .= "<option value='{$j}' $selected>{$j}倍</option>";
        }
    }
    for($j= 10; $j <= 40; $j++){
        $selected = ($mobileSize== $j) ? "selected='selected'" : '';
        $html .= "<option value='{$j}' $selected>{$j}px</option>";
    }
    $html .= "</select>";
    return $html;
}

/**
 * 数据列表解析（主图 Picture1、副图 Picture2、标题Title、内容Content）
 */
function parse_list($name, $value, $help, $parameter){
    //值格式：名称,是否显示(0,1),类型(如：color，text等)
    $nameMap = array('Title'=>'标题', 'SubTitle'=>'副标题', 'Picture'=>'主图',    'SubPicture'=>'副图',      'Description'=>'描述', 'Link'=>'链接地址');
    $showMap = array('Title'=>'1',      'SubTitle'=>'1',         'Picture'=>'1',          'SubPicture'=>'1',          'Description'=>'1', 'Link'=>'0');
    $typeMap = array('Title'=>'text',  'SubTitle'=>'text',     'Picture'=>'image', 'SubPicture'=>'image', 'Description'=>'textarea', 'Link'=>'channelselect');
    $typeWidth = array('Title'=>'120',  'SubTitle'=>'120',     'Picture'=>'182', 'SubPicture'=>'182', 'Description'=>'180', 'Link'=>'180');
    $max = 999; //最大行数
    if(!empty($parameter)){
        //将单引号替换为双引号，否则json_decode返回null
        $parameter = str_ireplace("'", '"', $parameter);
        $temp = json_decode($parameter, true);
        if(isset($temp['Max'])){
            $max = intval($temp['Max']);
        }
        foreach($nameMap as $k=>$v){
            if(!empty($temp[$k])){
                $arr = explode(',', $temp[$k]);
                if(empty($arr[0])) $arr[0] = $v;
                $temp[$k] = implode(',', $arr);
            }
        }
        $nameMap = array_merge($nameMap, $temp);
    }
    $p = array();
    foreach($nameMap as $k=>$v){
        $temp = explode(',', $v);
        $p[$k]['Name'] = $temp[0];
        if(!isset($showMap[$k])) $showMap[$k] = 0; //2024-05-29
        if(!isset($typeMap[$k])) $typeMap[$k] = '';
        if(!isset($typeWidth[$k])) $typeWidth[$k] = '';

        $IsShow = isset($temp[1]) ? $temp[1] : $showMap[$k];
        $p[$k]['ShowStyle'] = ($IsShow==1) ? '' : 'display:none;';
        $p[$k]['Type'] = isset($temp[2]) ? $temp[2] :$typeMap[$k];
        //如果不显示，则没有宽度
        $width =  isset($temp[3]) ? $temp[3] : $typeWidth[$k];
        $p[$k]['Width'] = ($IsShow==1) ? "width:{$width}px;" : '';
    }
    //$value = trim($value, '{[r]}'); //存在bug，不是过滤整体
    $titleStyle = ( !empty($p['Title']['ShowStyle']) && !empty($p['SubTitle']['ShowStyle']) ) ? 'display:none;' : '';
    $data = yd_split($value, array('Title', 'SubTitle', 'Picture', 'SubPicture','Description', 'Link', 'LinkText'), "{[r]}", '{[c]}');
    $html = "<div style='max-height: 400px;overflow: auto;'>
            <table class='table-list' id='table-list{$name}' border='1'>
                        <thead><tr>
                            <th style='{$p['Title']['Width']}{$titleStyle}'>
                                <span style='{$p['Title']['ShowStyle']}'>{$p['Title']['Name']}</span><span style='{$p['SubTitle']['ShowStyle']}'>/{$p['SubTitle']['Name']}</span>
                             </th>
                            <th style='{$p['Picture']['Width']}{$p['Picture']['ShowStyle']}'>{$p['Picture']['Name']}</th>
                            <th style='{$p['SubPicture']['Width']}{$p['SubPicture']['ShowStyle']}'>{$p['SubPicture']['Name']}</th>
                            <th style='{$p['Description']['Width']}{$p['Description']['ShowStyle']}'>{$p['Description']['Name']}</th>
                            <th style='{$p['Link']['Width']}{$p['Link']['ShowStyle']}'>{$p['Link']['Name']}</th>
                            <th style='width: 40px;'>操作</th>
                        </tr></thead><tbody>";
    if( empty($data) ){
        $html .= parse_list_row($name, 1, false, $p);
    }else{  //表示修改
        $n = 1;
        foreach ( $data as $v){
            $html .= parse_list_row($name, $n, $v, $p);
            $n++;
        }
    }
    $html .= "</tbody><tfoot><tr>
        <td class='add'  colspan='6' valign='middle'>
            <textarea style='display:none;' class='alllist' id='{$name}' name='{$name}'></textarea>
            <a onclick='addListItem(\"{$name}\", {$max})'  id='btnSaveAll'> + 添加一行</a>
            <span style='float: right; padding: 6px 12px;'>{$help}</span>
        </td>
        </tr></tfoot></table></div>";
    unset($data);
    return $html;
}

function parse_list_row($name, $n=1, $data=false, $p=array()){
    $Title = '';
    $SubTitle = '';
    $Picture = '';
    $SubPicture = '';
    $Description = '';
    $Link = '';
    $LinkText = '';
    if(!empty($data)){
        $Title = $data['Title'];
        $SubTitle = $data['SubTitle'];
        $Picture = $data['Picture'];
        $SubPicture = $data['SubPicture'];
        $Description = $data['Description'];
        $Link = isset($data['Link']) ? $data['Link'] : '';
        $LinkText = isset($data['LinkText']) ? $data['LinkText'] : '';
    }
    $SelectPicture = L('SelectPicture');
    $titleStyle = ( !empty($p['Title']['ShowStyle']) && !empty($p['SubTitle']['ShowStyle']) ) ? 'display:none;' : '';
    $pictureStyle = ( $p['Picture']['Type'] != 'image' ) ? 'display:none;' : '';
    $subPictureStyle = ( $p['SubPicture']['Type'] != 'image' ) ? 'display:none;' : '';
    //主标题
    $type = $p['Title']['Type'];
    $channelselectStyle = "style='width:95%;'";
    if('channelselect' == $p['Title']['Type']){
        $tempHtml = _link_channel_html('', $Title, $channelselectStyle, 'Title');
        $titleHtml = "<span style='{$p['Title']['ShowStyle']}'>{$tempHtml}</span>";
    }else{
        $titleHtml = "<input  style='margin-bottom: 3px;{$p['Title']['ShowStyle']}' type='{$type}' class='at' placeholder='请输入{$p['Title']['Name']}' id='Title'  value='{$Title}'  />";
    }

    //副标题HTML（支持text、number、color、channelselect）
    $type = $p['SubTitle']['Type'];
    if('colorex' == $type){
        $tempHtml = parse_colorex('SubTitle', $SubTitle, '');
        $subTitleHtml = "<span style='{$p['SubTitle']['ShowStyle']}'><br/>{$tempHtml}</span>";
    }elseif('channelselect'== $type){
        $tempHtml = _link_channel_html('', $SubTitle, $channelselectStyle, 'SubTitle');
        $subTitleHtml = "<span style='{$p['SubTitle']['ShowStyle']}'>{$tempHtml}</span>";
    }else{
        $subTitleHtml = "<br/><input  style='{$p['SubTitle']['ShowStyle']}' type='{$type}' class='at' placeholder='请输入{$p['SubTitle']['Name']}' id='SubTitle' value='{$SubTitle}'  />";
    }

    //主图HTML
    $type = $p['Picture']['Type'];
    $pictureHtml = "<input onmouseover='floatListImage(this)' placeholder='请上传{$p['Picture']['Name']}' type='text' class='ap' id='{$name}File_{$n}Image'  value='{$Picture}'  /><br/>
        <span class='UploadWrapper' style='{$pictureStyle}'>
            <input class='btnFileUpload' type='button' value='上传' />
            <input type='file' class='af' id='{$name}File_{$n}' name='{$name}File_{$n}' onchange='startListUpload(this)' />
        </span>
        <input style='{$pictureStyle}' onclick='BrowserListServer(this)' id='btn{$name}Server'  name='btn{$name}Server'  type='button' value='{$SelectPicture}'  class='btnUpload'  />";
    if('colorex' == $type){
        $pictureHtml = parse_colorex("{$name}File_{$n}Image", $Picture, 'ap');
    }elseif('number' == $type){
        $pictureHtml = "<input  placeholder='{$p['Picture']['Name']}' type='number' class='ap' id='{$name}File_{$n}Image'  value='{$Picture}'  />";
    }

    //附图HTML
    $type = $p['SubPicture']['Type'];
    $subPictureHtml = "<input onmouseover='floatListImage(this)' placeholder='请上传{$p['SubPicture']['Name']}' type='text' class='apSub' id='{$name}SubFile_{$n}Image' value='{$SubPicture}'  /><br/>
        <span class='UploadWrapper' style='{$subPictureStyle}'>
            <input class='btnFileUpload' type='button' value='上传' />
            <input type='file' class='afSub' id='{$name}SubFile_{$n}' name='{$name}SubFile_{$n}' onchange='startListUpload(this)' />
        </span>
        <input style='{$subPictureStyle}' onclick='BrowserListServer(this)' id='btn{$name}Server'  name='btn{$name}Server'  type='button' value='{$SelectPicture}'  class='btnUpload'  />";
    if('colorex' == $type){
        $subPictureHtml = parse_colorex("{$name}SubFile_{$n}Image", $SubPicture, 'apSub');
    }elseif('number' == $type){
        $subPictureHtml = "<input  placeholder='{$p['SubPicture']['Name']}' type='number' class='apSub' id='{$name}SubFile_{$n}Image'  value='{$SubPicture}'  />";
    }

    //链接
    $type = $p['Link']['Type'];
    if('colorex' == $type){
        $tempHtml = parse_colorex('Link', $Link, '');
        $linkHtml = "<span style='{$p['Link']['ShowStyle']}'><br/>{$tempHtml}</span>";
    }elseif('channelselect' == $type){
        $linkName = "{$name}ChannelLink";
        $tempHtml = _link_channel_html("{$linkName}", $Link, $channelselectStyle, 'Link', true);
        $linkStyle = 'width: 95%; margin-top: 3px;';
        if($Link!=-1) $linkStyle.='display:none;'; //只有自定义链接，才显示文本框
        $tempLink = "<input  style='{$linkStyle}' id='LinkText' class='textinput {$linkName}Text' type='text'  placeholder='请输入链接地址' value='{$LinkText}'  />";
        $linkHtml = "<span style='{$p['Link']['ShowStyle']}'>{$tempHtml}{$tempLink}</span>";
    }elseif('number' == $type){
        $linkHtml = "<input  style='width: 98%' id='Link' class='textinput' type='number'  placeholder='' value='{$Link}'  />";
    }else{
        $linkHtml = "<textarea style='height:58px;width: 98%;'  id='Link'>{$Link}</textarea>";
    }

    $type = $p['Description']['Type'];
    if('textarea' == $type){
        $descriptionHtml = "<textarea  id='Description' placeholder='请输入{$p['Description']['Name']}'>{$Description}</textarea>";
    }else{
        $descriptionHtml = "<input  style='width: 98%;height: auto;' id='Description' class='textinput' type='{$type}'  placeholder='请输入{$p['Description']['Name']}' value='{$Description}'  />";
    }

    $html = "<tr>
            <td style='{$titleStyle}'>{$titleHtml}{$subTitleHtml}</td>
            <td style='{$p['Picture']['ShowStyle']}'>{$pictureHtml}</td>
             <td style='{$p['SubPicture']['ShowStyle']}'>{$subPictureHtml}</td>
            <td style='{$p['Description']['ShowStyle']}'>{$descriptionHtml}</td>
            <td style='{$p['Link']['ShowStyle']}'>{$linkHtml}</td>
            <td><a onclick='delListItem(this,\"{$name}\")' class='btn-list-del'>删除</a></td>
        </tr>";
    return $html;
}

/**
 * 按钮解析
 */
function parse_button($name, $value, $helpText, $parameter){
    //需要控制是否显示圆角和阴影
    $showMap = array('ShowShow'=>1, 'ShowName'=>1, 'ShowHoverColor'=>1, 'ShowHoverTextColor'=>1);
    if(!empty($parameter)){
        //将单引号替换为双引号，否则json_decode返回null
        $parameter = str_ireplace("'", '"', $parameter);
        $temp = json_decode($parameter, true);
        $showMap = array_merge($showMap, $temp);
    }
    $ShowStyle = (1==$showMap['ShowShow']) ? '': 'display:none;';  //是否显示
    $NameStyle = (1==$showMap['ShowName']) ? '': 'display:none;'; //是否显示名称
    $HoverColorStyle = (1==$showMap['ShowHoverColor']) ? '': 'display:none;';
    $HoverTextColorStyle = (1==$showMap['ShowHoverTextColor']) ? '': 'display:none;';
    $temp = explode(',', $value); //格式：0名称、1保留使用、2宽度、3圆角、4边框、5边框颜色、6鼠标悬浮背景颜色，7：悬浮文字颜色，8：是否显示
    $isShow = (0==strlen($temp[8])) ? 1 : $temp[8];
    $checked1 = (1 == $isShow) ? "checked" : '';
    $checked0 = (0 == $isShow) ? "checked" : '';
    $html = "
        <span style='{$ShowStyle}'>
        是否显示：<label style='color:blue;'>
        <input type='radio' onclick=\"previewButton('{$name}', 'show', 1)\" name='{$name}Show'  value='1'  {$checked1}/>显示</label>
        <label style='margin-left: 12px; margin-right: 32px;color:red;'>
        <input type='radio'  name='{$name}Show'  onclick=\"previewButton('{$name}', 'show', 0)\" value='0' {$checked0}/>隐藏
        </label>
        </span>
        <span style='{$NameStyle}'>
        按钮文本：<input type='text' class='textinput' style='width: 120px;margin-right: 10px;' placeholder='请输入按钮文本' name='{$name}Name'  value='{$temp[0]}'  />
        </span>
        按钮宽度：<input type='number' class='textinput' style='width: 126px;margin-right: 72px;'  placeholder='px' onkeyup=\"previewButton('{$name}', 'width')\" onchange=\"previewButton('{$name}', 'width')\" name='{$name}Width'  id='{$name}Width' value='{$temp[2]}'  />
    ";
    $typelist = array('0'=>'无边框', '1'=>'1px实线边框', '2'=>'2px实线边框', '3'=>'3px实线边框', '4'=>'4px实线边框','5'=>'5px实线边框', '6'=>'6px实线边框',);
    $html .= "<div style='margin-top: 3px;'>
      按钮圆角：<input type='number' class='textinput' style='width: 130px;'  placeholder='px' onkeyup=\"previewButton('{$name}', 'radius')\" onchange=\"previewButton('{$name}', 'radius')\" name='{$name}Radius' id='{$name}Radius'  value='{$temp[3]}'  />
     <input type='hidden'  name='{$name}Link'  value='{$temp[1]}'  />
    按钮边框：<select style='width:120px;margin-right: 15px;'  id='{$name}Border' name='{$name}Border' onchange=\"previewButton('{$name}','border')\" class='textinput'>";
    foreach($typelist as $tvalue=>$tname){
        $selected = ( $temp[4] == $tvalue) ? "selected='selected'" : '';
        $html .= "<option value='{$tvalue}' $selected>{$tname}</option>";
    }
    $html .= "</select>";
    $html .= "<span style='margin-right: 15px;'>边框颜色：";
    $params = array('name'=>$name, 'type'=>'button');
    $html .= parse_colorex("{$name}Color", $temp[5],'', $params);
    $html .= "</span></div>";
    $html .= "<div style='margin-top: 3px;'><span style='{$HoverColorStyle}'><span style='margin-left: 0px;'>鼠标悬浮时按钮背景颜色：</span>";
    $params = array('name'=>$name, 'type'=>'button');
    $html .= parse_colorex("{$name}HoverColor", $temp[6],'', $params);
    $html .= "</span>";
    $html .= "<span style='{$HoverTextColorStyle}'><span style='margin-left: 14px'>鼠标悬浮时按钮文本颜色：</span>";
    $html .= parse_colorex("{$name}HoverTextColor", $temp[7]);
    $html .="</span>";
    if(!empty($helpText)){
        $html .= "<div class='Caution'>{$helpText}</div>";
    }
    $html .="<input type='hidden' id='{$name}' name='{$name}' value='' /></div>";
    return $html;
}

/**
 * 表格解析
 */
function parse_table($name, $value, $helpText){
    //表格上下内边距，表格左右内边距，对齐方式，字体
    //首行背景色，首行字体，首列背景色，首列字体，奇数行颜色，偶尔行颜色，鼠标悬浮颜色
    $temp = explode(',', $value); //0宽度，1行数，2列数，3宽度单位，4上下内边距, 5左右内边距，6数据二维列表
    //$content = str_ireplace("\n", '\n', $temp[6]); //格子内容
    //表格里如果出现回车换行，单引号，会导致js语法错误
    $searchJs = array("\r\n", "\n", "'");
    $replaceJs = array('\n',     '\n', "\\'");
    $content = str_ireplace($searchJs, $replaceJs, $temp[6]); //格子内容
    $bg = 'background:#f0f0f0;';
    $html = "表格宽度：<input type='number' placeholder='px' onkeyup='update{$name}TableContent()' style='width:70px;' class='textinput' id='{$name}Width' value='{$temp[0]}' />
    <select style='margin-right: 4px; margin-bottom: 5px;' id='{$name}Row'  class='textinput'>";
    for($r=1; $r<=30; $r++){ //行
        $selected = ( $temp[1] == $r) ? "selected='selected'" : '';
        $html .= "<option value='{$r}' $selected>{$r}行</option>";
    }
    $html .= "</select>
    <select style='margin-right:8px;'  id='{$name}Column'  class='textinput'>";
    for($c=2; $c<=12; $c++){ //列
        $selected = ( $temp[2] == $c) ? "selected='selected'" : '';
        $html .= "<option value='{$c}' $selected>{$c}列</option>";
    }
    $html .= "</select>";
    $html .= "单元格宽度单位：<select id='{$name}Unit' style='margin-right:8px;'  class='textinput'>";
    $units = array('%'=>'百分比%', 'px'=>'像素px', 'em'=>'em');
    foreach($units as $k=>$v){
        $selected = ( $temp[3] == $k) ? "selected='selected'" : '';
        $html .= "<option value='{$k}' $selected>{$v}</option>";
    }
    $html .= "</select>
    左右内边距：<input type='number' onkeyup='update{$name}TableContent()' style='width:60px;' class='textinput' id='{$name}PaddingX' value='{$temp[4]}' />
    上下内边距：<input type='number' onkeyup='update{$name}TableContent()' style='width:60px;' class='textinput' id='{$name}PaddingY' value='{$temp[5]}' />
    <input type='hidden' id='{$name}' name='{$name}' value='' />";

    //表格
    $html .= "<div style='max-height: 400px; overflow: auto;'><table class='yd-table {$name}-table'></table></div>
    <div style='padding: 10px 0;'>
         <span class='btnSuccess btnSmall' onclick='insert{$name}Content(4)'>插入链接</span>
         <span class='btnSuccess btnSmall' onclick='insert{$name}Content(6)'>插入图片</span>
         
        <span class='btnSuccess btnSmall' onclick='insert{$name}Content(1)'>插入按钮</span>
         <span class='btnSuccess btnSmall' onclick='insert{$name}Content(5)'>插入角标</span>
        
        <span class='btnSuccess btnSmall' onclick='insert{$name}Content(2)'>插入√</span>
        <span class='btnSuccess btnSmall' onclick='insert{$name}Content(3)'>插入×</span>
        <span  class='Caution' style='float: right; margin-right: 10px;color:red;'>禁止输入单引号。{$helpText}</span>
    </div>
    <script>
           var gTableN{$name} = 0;
           function insert{$name}Content(type) {
                var content = '';
                if(1==type){ //按钮
                    content = '<a class=\"yd-table-btn\" href=\"#\" target=\"_blank\">按钮</a>';
                }else if(2==type){ //正确
                    content = '√';
                }else if(3==type){ //错误
                    content = '×';
                }else if(4==type){ //链接
                    content = '<a style=\"font-size:14px;color:#000;\" href=\"#\" target=\"_blank\">链接</a>';
                }else if(5==type){ //角标
                    content = '<i class=\"yd-badge-right\"><b>推荐</b></i>';
                }else if(6==type){ //图片
                    content = '<img src=\"http://\" alt=\"\" style=\"width:40px;\">';
                }
                insertAtCursorByID(gTableN{$name}, content);
                update{$name}TableContent(0);
           }
           function set{$name}Number(obj) {
               gTableN{$name} = $(obj).attr('id');
           }
          function update{$name}TableContent(obj) {
               if(obj) gTableN{$name} = $(obj).attr('id');
               var width = $('#{$name}Width').val();
                var rowCount = $('#{$name}Row').val();
                var columnCount = $('#{$name}Column').val();
                var columnUnit = $('#{$name}Unit').val();
                var paddingX = $('#{$name}PaddingX').val();
                var paddingY = $('#{$name}PaddingY').val();
                var str = width+','+rowCount+','+columnCount+','+columnUnit+','+paddingX+','+paddingY+',';
                var obj, value = '';
                rowCount = parseInt(rowCount)+2;
                columnCount = parseInt(columnCount);
                for(var r=1; r<=rowCount; r++){
                    if(r >1) str += '{r}';
                    for(var c=1; c<=columnCount; c++){
                        if(c > 1) str += '{c}';
                        obj = $('.{$name}-table .r'+r+' .c'+c+' .textinput');
                        value = (obj.length>0) ? obj.val() : '';
                        str += value;
                    }
                }
                $('#{$name}').val(str);
                //console.log(  'data：'+str );
            }
        $(document).ready(function(){
            $('#{$name}Row,#{$name}Column,#{$name}Unit').change(function(){
                showTableContent();
                update{$name}TableContent(0);
            });
            function showTableContent() {
                var rowCount = $('#{$name}Row').val();
                if(!rowCount) rowCount = 2;
                rowCount = parseInt(rowCount)+2;
                var columnCount = $('#{$name}Column').val();
                if(!columnCount) columnCount = 3;
                 columnCount = parseInt(columnCount)
                 
                var content = '{$content}';

                var data = content.split('{r}');
                var width = 'width:'+100.0/columnCount+'%;';
                var html = '', arr, temp='', isSelected='';
                var n = 0;
                for(var r=1; r<=rowCount; r++){
                    arr = data[r-1] ? data[r-1].split('{c}') : [];
                    html += \"<tr class='r\"+r+\"'>\";
                    for(var c=1; c<=columnCount; c++){
                        temp = arr[c-1] ? arr[c-1] : '';
                        html += \"<td class='c\"+c+\"' style='\"+width+\"'>\";
                        if(r==1){
                            html += \"<input type='number' style='{$bg}' onkeyup='update{$name}TableContent(0)' placeholder='单元格宽度' class='textinput' value='\"+temp+\"'/>\";
                        }else if(r==2){ //对齐方式
                            html += \"<select style='{$bg}' onchange='update{$name}TableContent(0)' class='textinput' >\"
                            isSelected = (temp==1) ? 'selected=\"selected\"' : '';
                            html += \"<option value='1' \"+isSelected+\">左对齐</option>\";
                            isSelected = (temp==2) ? 'selected=\"selected\"' : '';
                            html += \"<option value='2' \"+isSelected+\">居中对齐</option>\";
                           isSelected = (temp==3) ? 'selected=\"selected\"' : '';
                            html += \"<option value='3' \"+isSelected+\">右对齐</option>\";
                            html += '</select>';
                        }else{
                            n++;
                            html += \" <textarea id='myn\"+n+\"' onkeyup='update{$name}TableContent(this)' onclick='set{$name}Number(this)' style ='height:105px;padding:3px;' class='textinput'>\"+temp+'</textarea>';
                        }
                        html += '</td>';
                    }
                    html += '</tr>';
                }
                $('.{$name}-table').html(html);
            }
            //显示表格
            showTableContent(); 
            update{$name}TableContent(0);
        });
     </script>";
    return $html;
}

/**
 * 解析链接
 */
function parse_link($name, $value, $width=302){
    $temp = explode(',', $value); //格式：链接类型,链接目标,链接值
    $LinkType = !empty($temp[0]) ? $temp[0] : 1;
    $LinkTarget = isset($temp[1]) ? $temp[1] : '_self';
    $LinkValue = isset($temp[2]) ? $temp[2] : '';
    //链接类型
    $types = array('1'=>'频道链接', '2'=>'打开QQ', '3'=>'拨号', '9'=>'自定义', '4'=>'无链接');
    $typeName = "{$name}Type";
    $html = "<select id='{$typeName}' style='margin-right: 4px;' class='textinput'>";
    foreach($types as $k=>$v){
        $selected = ( $LinkType == $k) ? "selected='selected'" : '';
        $html .= "<option value='{$k}' $selected>{$v}</option>";
    }
    //链接目标
    $targets = array('_self'=>'当前页打开', '_blank'=>'新页面打开');
    $css = (4==$LinkType) ? 'display:none' : '';
    $targetName = "{$name}Target";
    $html .= "</select><select id='{$targetName}' style='margin-right: 4px;{$css}' class='textinput'>";
    foreach($targets as $k=>$v){
        $selected = ( $LinkTarget == $k) ? "selected='selected'" : '';
        $html .= "<option value='{$k}' $selected>{$v}</option>";
    }
    $html .= "</select>";
    //链接值
    $html .= "<span id='{$name}LinkWrap'>";
    $valueName = "{$name}Value";
    $html1 = _link_channel_html($name, $LinkValue);
    $html1 = str_ireplace('"', "'", $html1);
    $html2 = "<input  type='text' onkeyup='update{$name}Value()' style='width: {$width}px' class='textinput' placeholder='请输入QQ号码' id='{$valueName}' value='{$LinkValue}'  />";
    $html3 = "<input  type='text' onkeyup='update{$name}Value()' style='width: {$width}px' class='textinput' placeholder='请输入电话号码' id='{$valueName}' value='{$LinkValue}'  />";
    $html9 = "<input  type='text' onkeyup='update{$name}Value()' style='width: {$width}px'  class='textinput' placeholder='请输入网址' id='{$valueName}' value='{$LinkValue}'  />";
    $html4 = "<input  type='hidden'  id='{$valueName}' value='{$LinkValue}'  />";
    if(1==$LinkType){ //频道
        //某些语言翻译后，可能有单引号，如果存在单引号下面的js就会报语法错误
        $html .= $html1;
    }elseif(2 == $LinkType){ //qq
        $html .= $html2;
    }elseif(3 == $LinkType){ //拨号
        $html .= $html3;
    }elseif(9 == $LinkType){ //自定义
        $html .= $html9;
    }elseif(4==$LinkType){ //无链接
        $html .=$html4;
    }
    $html .= "</span><input type='hidden' name='{$name}' id='{$name}' value='{$value}'/> <script>
       //更新链接的值
       function update{$name}Value(){
            var type = $('#{$typeName}').val();
            var target = $('#{$targetName}').val();
            var value = $('#{$valueName}').val();
            var str = type+','+target+','+value;
            $('#{$name}').val(str);
        }
        function change{$name}ChannelLink(obj){
           update{$name}Value();
        }
        
        $(document).ready(function(){
            //改变类型
            $('#{$typeName}').change(function(){
                var type = $('#{$typeName}').val();
                $('#{$targetName}').show();
                var html = '';
                if(1 == type){ //频道链接
                    html = \"{$html1}\";
                }else if(2 == type){ //打开QQ
                    html = \"{$html2}\";
                }else if(3 == type){ //拨号
                    html = \"{$html3}\";
                }else if(9 == type){ //自定义
                    html = \"{$html9}\";
                }else if(4 == type){ //自定义
                    html = \"{$html4}\"
                    $('#{$targetName}').hide();
                }
                $('#{$name}LinkWrap').html(html);
                update{$name}Value();
            });
            //改变目标
            $('#{$targetName}').change(function(){
                 update{$name}Value();
            });
        });
    </script>";
    return $html;
}

function _link_channel_html($name, $value, $style='', $id=false, $hasExtra=false){
    $m = D('Admin/Channel');
    $data = $m->getChannel();
    if($hasExtra){
        $temp1 = array('ChannelID'=>'-2', 'ChannelName'=>'==无链接==');
        $temp2 = array('ChannelID'=>'-1', 'ChannelName'=>'==自定义链接==');
        array_unshift($data, $temp1, $temp2);
    }
    $idValue = !empty($id) ? $id : "{$name}Value";
    $html = "<select id=\"{$idValue}\" {$style} class=\"textinput\" onchange=\"change{$name}ChannelLink(this)\">";
    foreach($data as $v){
        $selected = ($value == $v['ChannelID']) ? "selected=\"selected\"" : '';
        $ChannelName = trim($v['ChannelName']);
        $html .= "<option value=\"{$v['ChannelID']}\" $selected>{$ChannelName}</option>";
    }
    $html .= "</select>";
    if($hasExtra){
        $html .="<script>
            function change{$name}ChannelLink(obj){
                var cid = $(obj).val();
                if(cid==-1){
                    $(obj).closest('td').find('.{$name}Text').show();
                }else{
                    $(obj).closest('td').find('.{$name}Text').hide();
                }
            }
        </script>";
    }
    return $html;
}

/**
 * 按钮解析
 */
function parse_border($name, $value, $helpText, $parameter){
    //需要控制是否显示圆角和阴影
    $showMap = array('ShowRadius'=>1, 'ShowShadow'=>1);
    if(!empty($parameter)){
        //将单引号替换为双引号，否则json_decode返回null
        $parameter = str_ireplace("'", '"', $parameter);
        $temp = json_decode($parameter, true);
        $showMap = array_merge($showMap, $temp);
    }
    $RadiusStyle = (1==$showMap['ShowRadius']) ? '': 'display:none;';
    $ShadowStyle = (1==$showMap['ShowShadow']) ? '': 'display:none;';
    //格式：0边框大小、1边框样式、2边框颜色、3圆角大小、4阴影大小、5边框位置
    $temp = explode(',', $value);
    //5：边框
    $html = "<div style='margin-top: 3px;'>";
    $list = array(''=>'全部边框','top'=>'上边框',  'bottom'=>'下边框', 'left'=>'左边框', 'right'=>'右边框');
    $html .= "<select style='margin-right: 8px;' onchange=\"previewBorder('{$name}', 'BorderPos')\"  id='{$name}BorderPos' name='{$name}BorderPos' class='textinput'>";
    foreach($list as $k=>$v){
        $selected = ( isset($temp[5]) && $temp[5] == $k) ? "selected='selected'" : '';
        $html .= "<option value='{$k}' $selected>{$v}</option>";
    }
    $html .= '</select>';
    //0：边框大小
    $list = array(''=>'边框大小', '0'=>'无边框', '1'=>'1px', '2'=>'2px', '3'=>'3px', '4'=>'4px','5'=>'5px', '6'=>'6px', '7'=>'7px', '8'=>'8px'
    , '9'=>'9px', '10'=>'10px', '11'=>'11px', '12'=>'12px', '13'=>'13px', '14'=>'14px', '15'=>'15px', '16'=>'16px');

    $html .= "<select style='margin-right: 8px;'  onchange=\"previewBorder('{$name}','BorderSize')\"  id='{$name}BorderSize' name='{$name}BorderSize' class='textinput'>";
    foreach($list as $k=>$v){
        $selected = ( $temp[0] == $k) ? "selected='selected'" : '';
        $html .= "<option value='{$k}' $selected>{$v}</option>";
    }
    $html .= '</select>';
    //1：边框样式
    $list = array(''=>'边框样式','solid'=>'实线',  'dotted'=>'点线', 'dashed'=>'虚线', 'double'=>'双线',
        'groove'=>'3D凹槽', 'ridge'=>'3D垄状');
    $html .= "<select style='margin-right: 8px;' onchange=\"previewBorder('{$name}', 'BorderStyle')\"  id='{$name}BorderStyle' name='{$name}BorderStyle' class='textinput'>";
    foreach($list as $k=>$v){
        $selected = ( isset($temp[1]) && $temp[1] == $k) ? "selected='selected'" : '';
        $html .= "<option value='{$k}' $selected>{$v}</option>";
    }
    $html .= '</select>';
    //2：边框颜色
    $html .= "<span style='margin-right: 8px;'>颜色：";
    $params = array('name'=>$name, 'type'=>'border');
    $html .= parse_colorex("{$name}BorderColor", $temp[2], '', $params);
    //3：圆角
    $html .= "<span style='margin-right: 8px;{$RadiusStyle}'>圆角：
    <input type='number' class='textinput' style='width: 60px;'  placeholder='' onkeyup=\"previewBorder('{$name}', 'BorderRadius')\" onchange=\"previewBorder('{$name}', 'BorderRadius')\" id='{$name}BorderRadius' name='{$name}BorderRadius'  value='{$temp[3]}'  />
    </span>";

    //4：阴影
    if(!isset($temp[4])) $temp[4] = '';
    $html .= "<span style='{$ShadowStyle}'>阴影：
    <input type='number' class='textinput' style='width: 60px;margin-right: 8px;'  placeholder=''  onkeyup=\"previewBorder('{$name}', 'BorderShadow')\" onchange=\"previewBorder('{$name}', 'BorderShadow')\" id='{$name}BorderShadow'  name='{$name}BorderShadow'  value='{$temp[4]}'  /></span>";
    if(!empty($helpText)){
        $html .= "<span class='Caution'>{$helpText}</span>";
    }


    $html .="<input type='hidden' id='{$name}' name='{$name}' value='' /></div>";
    return $html;
}

/**
 * 单选按钮解析
 * $toggle：格式：显示ID1,显示ID2;隐藏ID1,影藏ID2
 * toggle="{'_all':''T1",    值1':'显示ID1,显示ID2; 隐藏ID1,影藏ID2', '     值2':'显示ID1,显示ID2;隐藏ID1,影藏ID2}"
 * _all：用于定义所有元素，用于控制隐藏和显示全部，只需要列出没有出现的元素即可
 */
function parse_radio($name, $value, $class, $selectedValue, $toggle){
    $toggle = _parse_toggle_string($toggle);
    $html = '';
    if($value){
        $value = str_replace(array("\r\n","\r"), "\n", $value);
        $item = explode ("\n", $value);
        for($j = 0; $j < count($item); $j++){
            $t = explode ('|', $item[$j] ); //value|item|是否是默认
            if( isset($selectedValue) ){ //自定义选中项
                $checked = ( $selectedValue == $t[0]) ? "checked" : '';
            }else{ //默认选中项
                $checked = ( !empty($t[2]) ) ?  "checked" : "";
            }
            $html .= "<label><input type='radio' onclick='toggle{$name}(\"{$t[0]}\")'  id='$name'  name='$name'  $class  value='{$t[0]}'  $checked />$t[1]</label>&nbsp;&nbsp;&nbsp;&nbsp;";
        }
        $html .= "<script>
                function toggle{$name}(value){
                    var map = {$toggle};
                    var dataToShow= map[value+'_Show'];
                    if(dataToShow){
                        console.log('dataToShow', dataToShow);
                        for(var i = 0; i<dataToShow.length;i++){
                            if(dataToShow[i]){
                                //不能直接对tr进行show，和配置tab切换冲突
                                $('#'+dataToShow[i]).closest('tr.config-tr').children().show();
                            }
                        }
                    }
                    var dataToHide= map[value+'_Hide'];
                     if(dataToHide){
                        console.log('dataToHide', dataToHide);
                        for(var i = 0; i<dataToHide.length;i++){
                            if(dataToHide[i]){
                                $('#'+dataToHide[i]).closest('tr.config-tr').children().hide();
                            }
                        }
                    }
                     if(typeof previewRadio === 'function') previewRadio('{$name}', value);
                }
                toggle{$name}(\"{$selectedValue}\");
            </script>
        ";
    }
    return $html;
}

function _parse_toggle_string($toggle){
    //将单引号替换为双引号，否则json_decode返回null
    if(!empty($toggle)){
        $toggle = str_ireplace("'", '"', $toggle);
        $toggle = json_decode($toggle, true);
        $hasAll = false;
        $all = '';
        foreach($toggle as $k=>$v){
            if(empty($v)) continue;
            if($k !== '_all' && false !== stripos($v, 'all')){
                $hasAll = true;
            }
            $all .= "{$v},";
        }
        if($hasAll){
            $all = str_ireplace('all', '', trim($all,','));
            $all = str_ireplace(array(';',',,,', ',,', ',,'), ',', trim($all,','));
            $all = array_unique( explode(',', trim($all,',')) );
            $all = array_values($all); //数组下标重0开始
        }

        $newToggle = array();
        foreach($toggle as $k=>$v){
            //在PHP7.3版 0=='all'  返回真，PHP8.0返回假
            if(!empty($v) && $k !== '_all'){
                $t = explode(';', $v);
                if(!isset($t[1])) $t[1] = '';
                $newToggle[$k.'_Show']= ('all'==$t[0]) ? $all : explode(',', $t[0]);
                $newToggle[$k.'_Hide']= ('all'==$t[1]) ? $all : explode(',', $t[1]);
            }
        }
        $toggle = json_encode($newToggle);
    }else{
        $toggle = '{}';
    }
    return $toggle;
}

/**
 * 分割线
 */
function parse_line($name, $value){
    if(empty($value)) $value='#ccc';
    $html = "<div id=\"{$name}\" style=\"margin:8px auto;height:1px; width: 90%; background:{$value}\"></div>";
    return $html;
}

/**
 * 边距解析
 */
function parse_margin($name, $value, $parameter){
    //上下左右是否显示
    $map = array('top'=>1,'bottom'=>1,  'left'=>1,  'right'=>1);
    if(!empty($parameter)){
        //将单引号替换为双引号，否则json_decode返回null
        $parameter = str_ireplace("'", '"', $parameter);
        $temp = json_decode($parameter, true);
        $map = array_merge($map, $temp);
    }
    $value = str_ireplace('px', '', trim($value));
    $temp = explode(' ', $value);
    $topStyle = ($map['top']==1) ? '' : 'display:none;';
    $rightStyle = ($map['right']==1) ? '' : 'display:none;';
    $bottomStyle = ($map['bottom']==1) ? '' : 'display:none;';
    $leftStyle = ($map['left']==1) ? '' : 'display:none;';

    $preivew = " onkeyup=\"previewMargin(this,'{$name}')\" onchange=\"previewMargin(this,'{$name}')\" ";
    $html = "<input type='hidden' id='{$name}' name='{$name}' value='' />
    <span style='margin-right: 10px;{$topStyle}'>
        上：<input {$preivew} type='number' style='width: 90px;' class='textinput' name='{$name}Top'  id='{$name}Top'  value='{$temp[0]}'  />
    </span>
    <span style='margin-right: 10px;{$rightStyle}'>
        右：<input {$preivew} type='number' style='width: 90px' class='textinput' name='{$name}Right'  id='{$name}Right'  value='{$temp[1]}'  />
    </span>
    <span style='margin-right: 10px;{$bottomStyle}'>
        下：<input {$preivew} type='number' style='width: 90px' class='textinput' name='{$name}Bottom'  id='{$name}Bottom'  value='{$temp[2]}'  />
    </span>
    <span style='{$leftStyle}'>
        左：<input {$preivew} type='number' style='width: 90px' class='textinput' name='{$name}Left'  id='{$name}Left'  value='{$temp[3]}'  />
    </span>";
    return $html;
}
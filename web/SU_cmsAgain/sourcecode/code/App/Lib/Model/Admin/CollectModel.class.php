<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */
class CollectModel extends Model{
	protected $_auto = array(
			array('LanguageID', 'get_language_id', 1, 'function'),
	);
	
	function getCollect( $p=array() ){
		$where = get_language_where_array();
		$result = $this->where($where)->order('CollectID desc')->select();
		return $result;
	}
	
	function findCollect( $id ){
		$where['CollectID'] = intval($id);
		$result = $this->where($where)->find();
		//字段数据
		$result['ChannelModelID'] = ChannelModelID( $result['ChannelID'] );
		$FieldPara = (array)explode('@@@', $result['FieldPara']);
		foreach ($FieldPara as $it){
			$tt = (array)explode('###', $it);
			$result['FieldInfo'][] = array(
				'AttributeID' => $tt[0],
				'FieldName' => $tt[1],
				'AttributeRegex' => $tt[2],
			);
		}
		
		//列表页参数
		$ListUrlPara = (array)explode('@@@', $result['ListUrlPara']);
		$result['ListUrl'] = $ListUrlPara[0];
		$result['ListUrlStart'] = $ListUrlPara[1];
		$result['ListUrlEnd'] = $ListUrlPara[2];
		$result['ListUrlStep'] = $ListUrlPara[3];
		$result['ListUrlLength'] = $ListUrlPara[4];
		$result['ListUrlOther'] = $ListUrlPara[5];
		$result['ListUrlRegionRegex'] = $ListUrlPara[6];
		
		//替换规则
		$ReplacePara = (array)explode('@@@', $result['ReplacePara']);
		foreach ($ReplacePara as $it){
			$tt = (array)explode('###', $it);
			$result['ReplaceInfo'][] = array('SearchText' => $tt[0],'ReplaceText' => $tt[1]);
		}
		
		//详细页参数
		$DetailUrlPara = (array)explode('@@@', $result['DetailUrlPara']);
		$result['DetailUrlRegex'] = $DetailUrlPara[0];
		
		//其它参数
		//字符编码0、时间间隔1、采集顺序2、自动上传图片3、自动上传flash4、是否审核5、浏览器标识6、最大采集数量7
		//自动提取缩略图8、测试采集Url9、是否保存重复标题10
		$OtherPara = (array)explode('@@@', $result['OtherPara']);
		$result['Charset'] = $OtherPara[0];
		$result['TimeTnterval'] = $OtherPara[1];
		$result['CollectOrder'] = $OtherPara[2];
		$result['AutoUploadImage'] = $OtherPara[3];
		
		$result['AutoUploadFlash'] = $OtherPara[4];
		$result['EnableCheck'] = $OtherPara[5];
		$result['UserAgent'] = $OtherPara[6];
		$result['MaxCount'] = $OtherPara[7];
		$result['AutoThumbFirst'] = $OtherPara[8];
		$result['TestDetailUrl'] = $OtherPara[9];
		$result['EnableTitle'] = $OtherPara[10];
		
		$result['PageType'] = $OtherPara[11];
		$result['PageRegionRegex'] = $OtherPara[12];
		$result['AllPageUrlRegex'] = $OtherPara[13];
		$result['NextPageUrlRegex'] = $OtherPara[14];
		return $result;
	}
}

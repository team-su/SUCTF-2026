<?php
/**
 * Youdian Content Management System
 * Copyright (C) YoudianSoft Co.,Ltd (http://www.youdiancms.com). All rights reserved. 
 */
if (!defined('APP_NAME')) exit();
class YdTemplateConfig{
	private $_fileName = null;
	private $_languageMark = null;
	private $_order = null;  //排序数组
    private $_lastError = ''; //最后一次错误
	function __construct($fileName, $languageMark){
		$this->_fileName = $fileName;
		$this->_languageMark = $languageMark;
		unset($this->_order);
	}

	function getLastError(){
	    return $this->_lastError;
    }
	
	//获取分组
	function getGroup(){
		if( !file_exists($this->_fileName) ) return false;
//		$xml = @simplexml_load_file( $this->_fileName );
        $xml = @simplexml_load_string( file_get_contents($this->_fileName ));
		if(empty($xml)) return false;
		$obj = $xml->groups->group;
		$n = count($obj);
		$group = array();
		for($i=0; $i < $n; $i++){
			$group[] = array('DisplayName' => (string)$obj[$i]['name'],'AttributeID' => (string)$obj[$i]['id']);
		}
		if(empty($group)) return false;
		return $group;
	}
	
	//获取模板配置属性
	function getAttribute($params=array()){
		if( !file_exists($this->_fileName) ) return false;
//		$xml = @simplexml_load_file( $this->_fileName );
        $xml = @simplexml_load_string( file_get_contents($this->_fileName ));
		if(empty($xml)) return false;
		unset($this->_order); //清空排序数组
		$lang = $this->_languageMark;
        $a1 = array();
		if(!empty($xml->global->var)){
            $a1 = $this->_getVar($xml->global->var);
        }
		if(empty($a1)) $a1 = array();

		$a2 = $this->_getVar($xml->$lang->var, $params);
		if(empty($a2)) $a2 = array();
		$attribute = array_merge($a1, $a2);
		//按order进行排序
        if(!empty($this->_order) && !empty($attribute)){
            array_multisort ($this->_order, SORT_ASC, SORT_NUMERIC, $attribute);
        }
		return $attribute;
	}
	
	//常见模板配置文件
	function create(){
		
	}

    //获取配置项
    function getConfigItem($itemName){
        $xml = @simplexml_load_file($this->_fileName);
        if(empty($xml)) return false;
        $itemValue = '';
        $lang = $this->_languageMark;
        $vars = &$xml->$lang->var;
        $n = count($vars);
        for($i=0; $i < $n; $i++){
            $key = (string)$vars[$i]['name'];
            if($key==$itemName){
                $itemValue = (string)$vars[$i]['value'];
                break;
            }
        }
        return $itemValue;
    }

    /**
     * 获取所有的颜色值
     */
    function getAllColor($max=24){
        $xml = @simplexml_load_file($this->_fileName);
        if(empty($xml)) return false;
        $colors = array();
        $lang = $this->_languageMark;
        $vars = &$xml->$lang->var;
        $n = count($vars);
        $total = 0;
        $map = array(
            '#00000000'=>1, '#000000'=>1, '#333333'=>1, '#666666'=>1, '#999999'=>1, '#CCCCCC'=>1,
            '#DDDDDD'=>1, '#EEEEEE'=>1, '#F0F0F0'=>1, '#F5F5F5'=>1, '#F9F9F9'=>1, '#FFFFFF'=>1
        );
        for($i=0; $i < $n; $i++){
            $type = (string)$vars[$i]['type'];
            $colorlist = $this->_getTypeColor($type, $vars[$i]);
            foreach($colorlist as $color){
                if(!empty($color) && !isset($map[$color])){
                    $colors[] = $color;
                    $total++;
                }
            }
        }
        $colors = array_unique($colors); //去重
        $colors = array_values($colors); //数组下标重0开始
        if(count($colors) > $max){ //仅返回前max个
            $colors = array_slice($colors, 0, $max);
        }
        return $colors;
    }

    /**
     * 获取指定类型的颜色值
     */
    private function _getTypeColor($type, &$data){
        $color = array();
        $value = strtoupper((string)$data['value']);
        if($type=='colorex' || $type=='color'){
            $color[] = $value;
        }elseif($type=='font'){
            $color[] = ParseItem($value, 2);
        }elseif($type=='bg'){
            $color[] = ParseItem($value, 1);
        }elseif($type=='button'){  //格式：0名称、1保留使用、2宽度、3圆角、4边框、5边框颜色、6悬浮颜色
            $color[] = ParseItem($value, 5);
            $color[] = ParseItem($value, 6);
        }elseif($type=='border'){ //格式：0边框大小、1边框样式、2边框颜色、3圆角大小、4阴影大小
            $color[] = ParseItem($value, 2);
        }
        return $color;
    }

	//保存配置
	function save($data, $GroupID=false){
		unset($data['__hash__']);
		if(empty($data)) return false;
		if( !file_exists($this->_fileName) ) {
		    $this->_lastError = "文件不存在！";
		    return false;
        }
		//预处理，对checkbox转化为字符串
		foreach ($data as $k=>$v){
			if( is_array($v) ){
				$data[$k] = implode(',', $v);
			}
		}
//		$xml = @simplexml_load_file( $this->_fileName );
        $xml = @simplexml_load_string( file_get_contents($this->_fileName ));
		if(empty($xml)) {
            $this->_lastError = "加载xml文件失败！";
		    return false;
        }
		//保存全局参数
        $this->_assignVar($xml->global->var, $data, false);
		//保存当前语言配置参数
		$lang = $this->_languageMark;
        $this->_assignVar($xml->$lang->var, $data, $GroupID);
		//=================================
		$result = $xml->asXML($this->_fileName);
		return $result;	
	}

	private function _assignVar(&$vars, &$data, $GroupID){
        $n = count($vars);  //vars是对象，不能使用foreach
        for($i=0; $i<$n; $i++){
            $gid= (int)$vars[$i]['groupid'];
            if($GroupID>0 && $GroupID!=$gid){ //仅修改当前分组
                continue;
            }
            $key = (string)$vars[$i]['name'];
            $type = (string)$vars[$i]['type'];
            if( key_exists($key, $data)){
                if($type =='font'){ //字体单独处理
                    $vars[$i]['value'] = $this->_getFontValue($data, $key);
                }elseif($type=='xy'){
                    $vars[$i]['value'] = $this->_getXY($data, $key);
                }elseif($type=='bg'){
                    $vars[$i]['value'] = $this->_getBg($data, $key);
                }elseif($type=='button'){
                    $vars[$i]['value'] = $this->_getButton($data, $key);
                }elseif($type=='border'){
                    $vars[$i]['value'] = $this->_getBorder($data, $key);
                }elseif($type=='margin'){
                    $vars[$i]['value'] = $this->_getMargin($data, $key);
                }else{
                    $vars[$i]['value'] = $data[$key];
                }
            }/*elseif(false!==stripos($type, 'checkbox')){
                $a2[$i]['value'] = '';
            } 这里会造成所有的checkbox值改变*/
        }
    }

    /**
     * 获取背景
     */
    private function _getBg(&$data, $name){
        //格式：0背景类型, 1背景颜色(开始),2背景颜色(结束)，3背景图片，4平铺,5背景大小自适应，6背景位置
        $keyWay = "{$name}Way";  //0
        $keyStartColor = "{$name}StartColor";
        $keyEndColor = "{$name}EndColor";  //2
        $keyImage = "{$name}Image";
        $keyRepeat = "{$name}Repeat"; //4
        $keySize = "{$name}Size";  //5
        $keyPosition = "{$name}Position"; //6
        $keyAttachment = "{$name}Attachment"; //7
        $keyAngle = "{$name}Angle"; //8

        $way = isset($data[$keyWay]) ? $data[$keyWay] : '';
        $startColor = isset($data[$keyStartColor]) ? $data[$keyStartColor] : '';
        $endColor = isset($data[$keyEndColor]) ? $data[$keyEndColor] : '';

        $image = isset($data[$keyImage]) ? $data[$keyImage] : '';
        $repeat = isset($data[$keyRepeat]) ? $data[$keyRepeat] : '';
        $size = isset($data[$keySize]) ? $data[$keySize] : '';
        $position = isset($data[$keyPosition]) ? $data[$keyPosition] : '';
        $attachment = isset($data[$keyAttachment]) ? $data[$keyAttachment] : '';
        $angle = isset($data[$keyAngle]) ? $data[$keyAngle] : '';
        $result = "{$way},{$startColor},{$endColor},{$image},{$repeat},{$size},{$position},{$attachment},{$angle}";
        return $result;
    }

    /**
     * 获取XY坐标
     */
    private function _getXy(&$data, $name){
        $keyX = "{$name}X";
        $keyY = "{$name}Y";
        $keyZ = "{$name}Z";
        $x = isset($data[$keyX]) ? $data[$keyX] : '';
        $y = isset($data[$keyY]) ? $data[$keyY] : '';
        $z = isset($data[$keyZ]) ? $data[$keyZ] : '';
        $result = "{$x},{$y},{$z}";
        return $result;
    }

    /**
     * 获取边距
     */
    private function _getMargin(&$data, $name){
        $keyTop = "{$name}Top";
        $keyRight = "{$name}Right";
        $keyBottom = "{$name}Bottom";
        $keyLeft = "{$name}Left";

        $top = isset($data[$keyTop]) ? $data[$keyTop] : '';
        $right = isset($data[$keyRight]) ? $data[$keyRight] : '';
        $bottom = isset($data[$keyBottom]) ? $data[$keyBottom] : '';
        $left = isset($data[$keyLeft]) ? $data[$keyLeft] : '';

        $result = "{$top}px {$right}px {$bottom}px {$left}px";
        return $result;
    }

    /**
     * 获取字体的值
     */
	private function _getFontValue(&$data, $name){
        $value = array();
	    $list = array('Family', 'Size', 'Color', 'LineHeight' , 'Bold', 'Italic', 'Underline', 'Line', 'Align', 'SizeMobile', 'Padding');
	    foreach($list as $v){
	        $key = $name.$v;
	        $temp = isset($data[$key]) ? $data[$key] : '';
	        $value[] = $temp;
        }
	    $value = implode(',', $value);
	    return $value;
    }

    /**
     * 获取按钮
     */
    private function _getButton(&$data, $name){
        //格式：0名称、1链接、2宽度、3圆角、4边框、5边框颜色、6:悬浮颜色、7：悬浮文字颜色，8：是否显示
        $btnName = str_replace(',', '' , $data["{$name}Name"]); //不能有逗号
        $link = $data["{$name}Link"];
        $width = $data["{$name}Width"];
        $radius = $data["{$name}Radius"];
        $border = $data["{$name}Border"];
        $borderColor = $data["{$name}Color"];
        $hoverColor = $data["{$name}HoverColor"];

        $hoverTextColor = $data["{$name}HoverTextColor"];
        $show = $data["{$name}Show"];
        $result = "{$btnName},{$link},{$width},{$radius},{$border},{$borderColor},{$hoverColor},{$hoverTextColor},{$show}";
        return $result;
    }

    /**
     * 获取边框
     */
    private function _getBorder(&$data, $name){
        //格式：0边框大小、1边框样式、2边框颜色、3圆角大小、4阴影大小、5边框位置
        $size = $data["{$name}BorderSize"];
        $style = $data["{$name}BorderStyle"];
        $color = $data["{$name}BorderColor"];
        $radius = $data["{$name}BorderRadius"];
        $shadow = $data["{$name}BorderShadow"];
        $pos = $data["{$name}BorderPos"];
        $result = "{$size},{$style},{$color},{$radius},{$shadow},{$pos}";
        return $result;
    }
	
	//获取配置数据
	function getData(){
		$data = false;
//		$xml = @simplexml_load_file( $this->_fileName );
        $xml = @simplexml_load_string( file_get_contents($this->_fileName ));
		if(empty($xml)) return false;
		//获取全局参数
		$a1 = &$xml->global->var;
		$n = count($a1);
		for($i=0; $i < $n; $i++){
			$key = (string)$a1[$i]['name'];
			$data[$key] = (string)$a1[$i]['value'];
		}
		
		//获取语言参数
		$lang = $this->_languageMark;
		$a2 = &$xml->$lang->var;
		$n = count($a2);
		for($i=0; $i < $n; $i++){
			$key = (string)$a2[$i]['name'];
			$data[$key] = (string)$a2[$i]['value'];
		}
		return $data;
	}
	
	//判断属性类型是否是可选
	function isSelected($type){
		if( stripos($type, 'checkbox') === false &&
				stripos($type, 'radio' ) === false &&
				stripos($type, 'select' )  === false  ){
			return false;
		}else{
			return true;
		}
	}

	
    private function _getVar($obj, $params=array()){
    	if(empty( $obj )) return false;
        $GroupID = isset($params['GroupID']) ? $params['GroupID'] : -1;
        $ShowVarNameInHelp = isset($params['ShowVarNameInHelp']) ? $params['ShowVarNameInHelp'] : true;
    	$Attribute = array();
    	$x=0;
    	$n = count( $obj );
    	for($i=0; $i < $n; $i++){
                if($GroupID==-1 || $GroupID == $obj[$i]['groupid']) {
                    $this->_order[] = isset($obj[$i]['order']) ? (int)$obj[$i]['order'] : 100;
                    $DisplayValue = (string)$obj[$i]['value'];
                    $DisplayType = $this->_getDefault('type', '', (string)$obj[$i]['type']);
                    $DisplayWidth = $this->_getDefault('width', $DisplayType, (string)$obj[$i]['width']);
                    $DisplayHeight = $this->_getDefault('height', $DisplayType, (string)$obj[$i]['height']);
                    $DisplayClass = $this->_getDefault('class', $DisplayType, (string)$obj[$i]['class']);
                    $DisplayHelpText = (string)$obj[$i]['help'];
                    if($ShowVarNameInHelp){
                        $DisplayHelpText .=' 配置变量名称：'.(string)$obj[$i]['name'].'';
                    }
                    $Attribute[$x] = array(
                        'FieldName'=>(string)$obj[$i]['name'],
                        'DisplayName'=>(string)$obj[$i]['title'],
                        'DisplayHelpText'=>$DisplayHelpText,
                        'DisplayType'=>$DisplayType,
                        'DisplayWidth'=>$DisplayWidth,
                        'DisplayHeight'=>$DisplayHeight,
                        'DisplayClass'=>$DisplayClass,
                        'GroupID'=>(string)$obj[$i]['groupid'],
                        'Toggle'=>(string)$obj[$i]['toggle'],
                        'Parameter'=>(string)$obj[$i]['parameter'],
                        'Tab'=>(string)$obj[$i]['tab']
                    );
                    $Attribute[$x]['AdminGroupID'] = 1; //模板配置允许获取所有频道
                    if ( $DisplayType == 'specialselect' || $DisplayType == 'specialselectno'){
                        $Attribute[$x]['SelectedValue'] = explode(',' , $DisplayValue); //获取频道设置值
                    }else if($DisplayType == 'channelexselect' || $DisplayType == 'channelexselectno'){ //支持多选
                        $Attribute[$x]['SelectedValue'] = explode(',' ,$DisplayValue); //获取频道设置值
                    }else if( $this->isSelected( $DisplayType ) ){
                        $Attribute[$x]['SelectedValue'] = $DisplayValue;
                        $Attribute[$x]['DisplayValue'] = str_replace('@@@', "\n", (string)$obj[$i]['parameter']);
                    }else{
                        $Attribute[$x]['DisplayValue'] = $DisplayValue;
                    }
                    $x++;
                }
    	}
    	import("@.Common.YdParseModel");
    	$Attribute = parsemodel($Attribute);  //解析属性信息
    	return $Attribute;
    }
	
	//获取默认值
	private function _getDefault($key, $type, $value){
		if( !empty($value) ) {
			$data = $value;
		}else{
			$key = strtolower($key);
			switch ($key){
				case 'width': //宽度
					$dafault = array('text'=>'270px','textarea'=>'100%', 'image'=>'100%');
					$data = key_exists($type, $dafault) ? $dafault[$type] : false;
					break;
				case 'height': //高度
					$dafault = array('textarea'=>'100px');
					$data = key_exists($type, $dafault) ? $dafault[$type] : false;
					break;
				case 'class': //样式类
					$dafault = array('text'=>'textinput','textarea'=>'textinput', 'image'=>'textinput');
					$data = key_exists($type, $dafault) ? $dafault[$type] : false;
					break;
				case 'type': //类型
					$data = 'channelselect';
					break;
				default:
					$data = false;
			}
		}
		return $data;
	}

    /**
     * 删除一个分组及其，所有的变量
     */
	public function deleteGroup($GroupID){
        $fileName = $this->_fileName;
        if( !file_exists($fileName) ) {
            $this->_lastError = "模板文件不存在";
            return false;
        }
        $isWrite = yd_is_writable($fileName);
        if(!$isWrite){
            $this->_lastError = "'模板文件只读，没有权限修改！";
        }
        $xml = @simplexml_load_file( $fileName );
        if(empty($xml)) {
            $this->_lastError = "创建XML对象失败";
            return false;
        }
        $canDelete = false;
        //1、先删除分组==========================================
        $groups = &$xml->groups->group;
        $n = count($groups);
        for($i=0; $i<$n; $i++){
            $id = (int)$groups[$i]['id'];
            if($GroupID == $id){
                $used = (int)$groups[$i]['used'];
                $used--;
                if($used <= 0){ //删除分组
                    $canDelete = true;
                    unset($groups[$i]);
                }else{ //仅减少引用计数
                    $groups[$i]['used'] = $used;
                }
                break;
            }
        }

        if($canDelete){
            $this->_deleteGroupVar($GroupID, $xml);
        }
        $result = $xml->asXML($fileName);
        return $result;
    }

    private function _deleteGroupVar($GroupID, &$xml){
        $list = C('LANG_LIST');
        foreach($list as $mark=>$temp){
            $vars = &$xml->$mark->var;
            if(empty($vars)) continue;
            for($i=0; $i<count($vars); $i++){
                $attributes = $vars[$i]->attributes();
                $id =  (string)$attributes['groupid']; //必须转化为字符串
                if($GroupID == $id){
                    unset($vars[$i]);
                    $i--; //删除后元素必须调整
                }
            }
        }
    }

    /**
     * 获取模板最大分组ID
     */
    public function getMaxGroupID(){
        $xml = @simplexml_load_file($this->_fileName);
        if(empty($xml)) return false;
        $obj = $xml->groups->group;
        $n = count($obj);
        $max = 1;
        for($i=0; $i < $n; $i++){
            //<group id="1" name="基本设置" order="1"/>
            $id = (int)$obj[$i]['id'];
            if($id > $max){
                $max = $id;
            }
        }
        $max++; //最大值加1
        //添加的组件最小值为5000，防止与内置组件冲突
        if($max<5000) $max=5000;
        return $max;
    }

    /**
     * 添加一个配置
     * $datalist：配置项
     */
    public function addGroup($GroupID, $GroupName, $datalist){
        if(empty($datalist)){
            $this->_lastError = "分组配置为空";
            return false;
        }
        $xml = new DOMDocument();//创建DOM对象
        if(empty($xml)) return false;
        $xml->load($this->_fileName);//加载xml文件
        $groups = $xml->getElementsByTagName('groups');
        if(empty($groups)){
            $this->_lastError = "XML格式错误";
            return false;
        }
        $node = $xml->createElement('group');//创建d元素节点
        $node->setAttribute("id", $GroupID);
        $node->setAttribute("name", $GroupName);
        $node->setAttribute("order", $GroupID);
        //$result = $groups[0]->appendChild($node);  返回DOMNodeList，[0]写法仅支持PHP7.2以上
        $result = $groups->item(0)->appendChild($node);
        if($result){
            foreach($datalist as $v){
                $errorMsg = "";
                $result = $this->_addVarNode($GroupID, $v, $xml, $errorMsg);
                if(!$result){
                    $this->_lastError = $errorMsg ? $errorMsg : "创建配置参数失败！";
                    return false;
                }
            }
        }
        $result = $xml->save($this->_fileName);
        return $result;
    }

    /**
     * 添加变量节点
     */
    private function _addVarNode($groupid, $data, &$xml, &$errorMsg){
        //$list = array('cn', 'en');
        $list = C('LANG_LIST');
        foreach($list as $mark=>$temp){
            //<var title="显示条数" name="TVideo2Count602" value="3"  parameter="" type="text" width="80px" help="" groupid="602" order="5"/>
            $name = str_replace($data['groupid'], $groupid, $data['name']);
            $node = $xml->createElement('var');//创建d元素节点
            $node->setAttribute("title", $data['title']);
            $node->setAttribute("name", $name);
            $node->setAttribute("type", $data['type']);
            $value = $data['value'];
            if('channelselect'==$data['type']){
                $value = $this->_getNewChannel($value, $mark);
            }
            $node->setAttribute("value", $value);
            $node->setAttribute("parameter", $data['parameter']);
            if(!empty($data['tab'])){
                $node->setAttribute("tab", $data['tab']); //tab定义
            }
            //添加toggle属性后，在api接口findComponent也需要增加
            if(!empty($data['toggle'])){
                $toggle = str_replace($data['groupid'], $groupid, $data['toggle']);
                $node->setAttribute("toggle", $toggle); //tab定义
            }
            $node->setAttribute("width", $data['width']);
            $node->setAttribute("help", $data['help']);
            $node->setAttribute("groupid", $groupid);
            $node->setAttribute("order", $data['order']);

            $var = $xml->getElementsByTagName($mark);
            if(empty($var) || $var->length == 0){
                $errorMsg = "语言{$mark}配置项不存在！";
                return false;
            }
            $result = $var->item(0)->appendChild($node);
            if(!$result) return false;
        }
        return true;
    }

    /**
     * 改变频道
     */
    private function _getNewChannel($ChannelID, $LanguageMark){
        //组件配置只有中文，没有英文，所以传入的ChannelID都是中文
        $map = array(
            '59'=>'61', '60'=>'62',  //我们的优势，我们的客户
            '20'=>'40', '21'=>'41','22'=>'42', //新闻
            '23'=>'43','24'=>'44', '25'=>'45', '26'=>'46', '27'=>'47', //产品
            '30'=>'51', '32'=>'50', //资料下载、工程案例
            '8'=>'4','9'=>'5',  //招聘、留言
            '55'=>'56', //视频模型
        );
        $page = array('15'=>'35', '17'=>'37', '18'=>'38', '19'=>'39', '31'=>'52');
        $map = $map+$page; //不能使用array_merage，否则会变成索引数组
        if($LanguageMark == 'en' && isset($map[$ChannelID])){
            $NewChannelID = $map[$ChannelID];
        }else{
            $NewChannelID = $ChannelID;
        }
        $m = D('Admin/Channel');
        $languageID = ($LanguageMark=='cn') ? 1 : 2;
        $where = "ChannelID={$NewChannelID} AND LanguageID={$languageID} AND IsEnable=1";
        $id = $m->where($where)->getField('ChannelID');
        if(!empty($id)){
            if($ChannelID>=20 && $ChannelID<=22){ //新闻模型
                $ChannelModelID = 30;
            }elseif($ChannelID>=23 && $ChannelID<=27){ //产品模型
                $ChannelModelID = 36;
            }elseif(isset($page[$ChannelID])){ //单页模型
                $ChannelModelID = 32;
            }elseif($ChannelID==55){ //视频模型
                $ChannelModelID = 34;
            }elseif($ChannelID==32){ //图片模型
                $ChannelModelID = 31;
            }else{
                $ChannelModelID = 30; //默认为新闻
            }
            $where = "ChannelModelID={$ChannelModelID} AND LanguageID={$languageID} AND IsEnable=1";
            $NewChannelID = $m->where($where)->getField('ChannelID');
        }
        return $NewChannelID;
    }

    public function getComponentGroupID($type){
        if( !file_exists($this->_fileName) ) return false;
        $xml = @simplexml_load_file( $this->_fileName );
        if(empty($xml)) return false;
        $data = array();
        //变量
        $pattern = "/T{$type}[^\d]+/i";
        $lang = $this->_languageMark;
        $obj = $xml->$lang->var;
        $n = count($obj);
        for($i=0; $i < $n; $i++){
            $name = (string)$obj[$i]['name'];
            //如何：区分basic4和basic41? 必须使用正则匹配，不能使用stripos
            $count = preg_match($pattern, $name);
            if($count > 0){
                $GroupID = (string)$obj[$i]['groupid'];
                $data[] = $GroupID;
            }
        }
        $n = count($data);
        if($n>1){
            $data = array_values(array_unique($data)); //去重
        }
        return $data;
    }

    /**
     * 当前分组的使用计数+1
     */
    public function setUsed($GroupID){
        $fileName = $this->_fileName;
        if( !file_exists($fileName) ) {
            $this->_lastError = "模板文件不存在";
            return false;
        }
        $isWrite = yd_is_writable($fileName);
        if(!$isWrite){
            $this->_lastError = "'模板文件只读，没有权限修改！";
        }
        $xml = @simplexml_load_file( $fileName );
        if(empty($xml)) {
            $this->_lastError = "创建XML对象失败";
            return false;
        }
        $hasFound = false;
        $groups = &$xml->groups->group;
        $n = count($groups);
        for($i=0; $i<$n; $i++){
            $id = (int)$groups[$i]['id'];
            if($GroupID == $id){
                $used = (int)$groups[$i]['used'];
                $groups[$i]['used'] = empty($used) ? 2 : $used+1;
                $hasFound = true;
                break;
            }
        }
        if($hasFound){
            $result = $xml->asXML($fileName);
        }else{
            $result = false;
        }
        return $result;
    }

    /**
     * 删除语言节点
     */
    function deleteLanguageNode($mark){
        $fileName = $this->_fileName;
        if( !file_exists($fileName) ) {
            $this->_lastError = "模板文件不存在";
            return false;
        }
        $isWrite = yd_is_writable($fileName);
        if(!$isWrite){
            $this->_lastError = "'模板文件只读，没有权限修改！";
        }
        $xml = @simplexml_load_file($fileName);
        if(empty($xml)) {
            $this->_lastError = "创建XML对象失败";
            return false;
        }
        if(empty($xml->$mark)){
            return true;
        }
        unset($xml->$mark);
        $result = $xml->asXML($fileName);
        return $result;
    }

    /**
     * 创建语言标签（主要用于多语言清空，创建一个新语言后，配置节点实际不存在）
     * @param $mark 语言标记，如：cn、jp
     */
    function createLanguageNode($mark){
        $xml = new DOMDocument();//创建DOM对象
        $xml->load($this->_fileName);//加载xml文件
        if(empty($xml)){
            $this->_lastError = "加载配置文件失败！";
            return false;
        }

        //判断当前节点是否存在
        $var = $xml->getElementsByTagName($mark);
        if($var && $var->length > 0) return true; //语言节点存在就返回

        //获取默认语言节点
        $defaultMark = C('DEFAULT_LANG');
        //$langNode = $xml->getElementsByTagName($mark)[0];
        //getElementsByTagName返回DOMNodeList，[0]写法仅支持PHP7.2以上
        $langNode = $xml->getElementsByTagName($defaultMark)->item(0);
        if(empty($langNode)){
            $this->_lastError = "默认语言节点{$defaultMark}不存在！";
            return false;
        }
        $varNodes = $langNode->getElementsByTagName('var');
        $n = $varNodes->length; //使用count($varNodes)返回的是1
        $tagNode = $xml->createElement($mark);//创建d元素节点
        for($i = 0; $i<$n; $i++){
            //必须拷贝否则会出错
            //$tagNode->appendChild($varNodes[$i]->cloneNode(true));
            $tagNode->appendChild($varNodes->item($i)->cloneNode(true));
        }
        $xml->documentElement->appendChild($tagNode);
        $result = $xml->save($this->_fileName);
        return $result;
    }

}
<?php
/**
 *  店铺模板装修（2个APP端和商城端的代码完全一样）
 */
if (!defined('APP_NAME')) exit();
class YdTemplate {
    private $_isAddDomain = false;
    function __construct($AppID='', $AppKey=''){
        $this->_isAddDomain = (3==CLIENT_TYPE) ? false : true;
    }

    /**
     * 是否添加域名前缀
     */
    public function addDomain($b=true){
        $this->_isAddDomain = $b;
    }

    /**
     *  模板数据解析
     * @param $pageData 页面json字符串
     */
    public function parsePageData($pageData, $params=array()){
        $data = json_decode($pageData ,true);
        if(empty($data)){
            //如果不存在，就返回默认数据
            $Parameters = array();
            $Parameters['PageBgColor'] = '#f5f5f5';
            $Parameters['MarginSize'] = 0;
            $Parameters['Type'] = 'head';
            $Parameters['TitleColor'] = '#000000';
            $Parameters['TitleBgColor'] = '#ffffff';
            $data['Parameters'] = $Parameters;
            $data['Components'] = array();
        }else{
            $this->_sortComponent($data['Components']);  //对组件进行排序
            $this->parseComponentsSpecial($data["ComponentsSpecial"]); //解析特殊组件（如：浮动按钮）
            $this->parseComponents($data['Components']); //解析组件
            $this->_setComponentDefault($data); //设置组件默认值
        }
        //工具栏数据
        if(!empty($params['Tabbar'])){
            $data['Tabbar'] = $params['Tabbar'];
        }
        $this->parseTabbar($data['Tabbar']); //解析Tabbar
        return $data;
    }

    /**
     * 解析组件参数
     */
    private function parseComponents(&$AllComponents){
        foreach($AllComponents as $k=>$v){
            $Components = &$AllComponents[$k];
            if($this->_isAddDomain && !empty($v['LinkValue']) && false!==stripos($v['LinkValue'], '<img')){
                $Components['LinkValue'] = AddContentResDomain($v['LinkValue']);
            }
            $type = $v['Type'];
            if('search' == $type){ //搜索框
                //格式： {Type:'search',   Order:50,   ShowScan:1,  SearchText:"名称 货号" }
                //不需要解析参数
            }elseif('banner' == $type){ //轮播图
                //格式：{Type:2,Order:50,DataList:[{BannerPicture:"", LinkType:1, LinkValue:"" }]}
                if($this->_isAddDomain){
                    AddResDomain($Components['DataList'], 'Picture');
                }
                $this->getLinkUrl($Components['DataList']);
            }elseif('announce' == $type){ //公告
                if($this->_isAddDomain){
                    $Components['Picture'] = AddResDomain($Components['Picture']);
                }
            }elseif('class' == $type){ //分类导航：
                //格式：{"Type":"class", "Order":1,  "ClassList":[{"Name":"栏目1", "Picture":"/1.png", "LinkType":2, "LinkValue":"2" }]},
                if($this->_isAddDomain){
                    AddResDomain($Components['DataList'], 'Picture');
                    AddResDomain($Components['DataList'], 'BgPicture');
                }
                $this->getLinkUrl($Components['DataList']);
            }elseif('goods' == $type){ // 商品列表
                // 格式 { "Type":"goods", "Order":90, "Style":"1", "Title":"热卖商品", "Source":"-2", "Count":"6",
                // "ShowPrice":"1", "ShowName":"1", "ShowBuy":1, "ShowLinePrice":1},
                $DataList = $this->getGoodsData($Components['Count'], $Components['Source']);
                $Components['DataList'] = $DataList;
            }elseif('picture' == $type){ //图片列表
                if($this->_isAddDomain){
                    AddResDomain($Components['DataList'], 'Picture');
                }
                $this->getLinkUrl($Components['DataList']);
            }elseif('video' == $type){ //视频
                if($this->_isAddDomain){
                    $Components['VideoUrl'] = AddResDomain($Components['VideoUrl']);
                }
            }elseif('title' == $type){ //标题
                if($this->_isAddDomain){
                    $Components['Picture'] = AddResDomain($Components['Picture']);
                }
                $this->getLinkUrl($Components);
            }elseif('text' == $type){ //文本
                if($this->_isAddDomain){
                    $Components['BgPicture'] = AddResDomain($Components['BgPicture']);
                }
                $this->getLinkUrl($Components);
            }elseif('richtext' == $type){ //富文本
                //格式：{ "Type":"richtext", "Order":95, "Content":"您好，这是一个富文本！"}
                //不需要解析参数
                if($this->_isAddDomain){
                    $Components['Content'] = AddContentResDomain($Components['Content']);
                }
            }elseif('blank' == $type){ //空白
                //格式：{ "Type":"blank", "Order":96, "Height":"8px"}
                //不需要解析参数
            }elseif('line' == $type){ //辅助线
                //格式：{ "Type":"line", "Order":97, "Style":"1"}
                //不需要解析参数
            }elseif('content' == $type){ // 内容列表
                // 格式 { "Type":"content", "Order":90, "Style":"1", "Title":"热卖商品", "Source":"-2", "Count":"6",
                // "ShowPrice":"1", "ShowName":"1", "ShowBuy":1, "ShowLinePrice":1},
                if(1==$Components['ShowTab']){
                    $m = D('Admin/Channel');
                    $where = "IsEnable=1 AND IsShow=1 AND Parent=".$Components['Source'];
                    $m->field('ChannelID,ChannelName,ChannelModelID,ChannelIcon');
                    $c = $m->where($where)->select();
                    if(!empty($c)){
                        if($this->_isAddDomain) AddResDomain($c, 'ChannelIcon');
                        $Components['ChannelList'] = $c;
                        //表示当前Tab的第一个频道ID
                        $Components['CurrentTabChannelID'] = $c[0]['ChannelID'];
                    }else{
                        $Components['CurrentTabChannelID'] = 0;
                        $Components['ChannelList'] = false;
                    }
                }
                $Components['DataList'] = $this->getInfoData($Components);
            }elseif('second' == $type){ // 秒杀
                $Components['DataList'] = $this->getSecondData($Components);
            }elseif('guestbook'==$type){ //留言
                $Components['DataList'] =  get_model(6);
            }elseif('feedback'==$type){ //反馈
                $Components['DataList'] =  get_model(37);
            }elseif('contact'==$type){
                if($this->_isAddDomain){
                    //防止生成默认的
                    $Components['Picture'] = AddResDomain($Components['Picture']);
                    $Components['Logo'] = AddResDomain($Components['Logo']);
                }
            }elseif('container' == $type){ //容器
                if($this->_isAddDomain){
                    $Components['BgPicture'] = AddResDomain($Components['BgPicture']);
                    if(!empty($Components['Childs'])){
                        $Childs = &$Components['Childs'];
                        foreach($Childs as $k1=>$v1){
                            $type = $v1['Type'];
                            if('container-picture' == $type){
                                $Childs[$k1]['Picture'] = AddResDomain($v1['Picture']);
                            }
                            $this->getLinkUrl($Childs);
                        }
                    }
                }
            }elseif('category' == $type){ //分类页面
                $Source = $Components['Source'];
                $this->getChannelInfo($Source, $Components);
            }elseif('map'==$type){
                if($this->_isAddDomain){
                    $Components['CompanyLogo'] = AddResDomain($Components['CompanyLogo']);
                }
                //百度坐标转国测局坐标
                $baiduFrom = array('x'=>$Components['Latitude'], 'y'=>$Components['Longitude']);
                $gcj = yd_bd2gcj($baiduFrom);
                $Components['GcjLongitude'] = $gcj['y'];
                $Components['GcjLatitude'] = $gcj['x'];
            }elseif('custom' == $type){ //自定义组件
                $this->_parseCustom($Components);
            }
        }
    }


    /**
     * 解析【自定义】组件
     */
    private function _parseCustom(array &$Components){
        if($this->_isAddDomain){
            $Components['BgPicture'] = AddResDomain($Components['BgPicture']);
        }
        $isJson = $this->isJson($Components['Parameter']);
        $params = $isJson ? json_decode($Components['Parameter'], true) : '';
        if(!empty($params)){
            $Components = array_merge($Components, $params);
        }
        if(function_exists('parse_custom')){
            $Components['Html'] = parse_custom($params);
        }
    }

    /**
     * 判断是否是json字符串
     */
    private function isJson($jsonString) {
        $b = is_null( json_decode($jsonString) ) ? false : true;
        return $b;
    }

    /**
     * 解析特殊组件
     */
    private function parseComponentsSpecial(&$ComponentsSpecial){
        foreach($ComponentsSpecial as $k => $v){
            $Components = &$ComponentsSpecial[$k];
            $type = $v['Type'];
            if('fab' == $type){ //浮动框
                if($this->_isAddDomain){
                    $Components['Picture'] = AddResDomain($Components['Picture']);
                }
                $this->getLinkUrl($Components);
            }
        }
    }

    /**
     * 解析Tabbar
     */
    function parseTabbar(&$tabbar){
        if(empty($tabbar)) return;
        if($this->_isAddDomain){
            AddResDomain($tabbar['DataList'], 'IconPath');
            AddResDomain($tabbar['DataList'], 'SelectedIconPath');
        }
        $this->getLinkUrl($tabbar['DataList']);
    }

    /**
     * 安装模板时，下载部分图片到本地
     */
    public function extractTemplatePageImage($pageContent, $TemplateNo){
        $data = json_decode($pageContent ,true);
        if(empty($data)) return $pageContent;
        //参数图片
        $this->_saveImageToLocal($data['Paramters']['PageBgPicture'], '', $TemplateNo);

        //组件图片
        $AllComponents = &$data['Components'];
        foreach($AllComponents as $k=>$v){
            $Components = &$AllComponents[$k];
            $type = $v['Type'];
            if('banner' == $type){ //轮播图
                $this->_saveImageToLocal($Components['DataList'], 'Picture', $TemplateNo);
            }elseif('class' == $type){ //分类导航
                $this->_saveImageToLocal($Components['DataList'], 'Picture', $TemplateNo);
                $this->_saveImageToLocal($Components['DataList'], 'BgPicture', $TemplateNo);
            }elseif('picture' == $type){ //图片列表
                $this->_saveImageToLocal($Components['DataList'], 'Picture', $TemplateNo);
            }elseif('title' == $type){ //标题
                $this->_saveImageToLocal($Components['Picture'], '', $TemplateNo);
            }elseif('text' == $type){ //文本
                $this->_saveImageToLocal($Components['BgPicture'], '', $TemplateNo);
            }elseif('contact'==$type){ //联系方式
                $this->_saveImageToLocal($Components['Picture'], '', $TemplateNo);
                $this->_saveImageToLocal($Components['Logo'], '', $TemplateNo);
            }elseif('container' == $type){ //容器
                    $Childs = &$Components['Childs'];
                    foreach($Childs as $k1=>$v1){
                        $type = $v1['Type'];
                        if('container-picture' == $type){
                            $this->_saveImageToLocal($Childs[$k1]['Picture'], '', $TemplateNo);
                        }
                    }
            }
        }

        //特殊组件图片
        $ComponentsSpecial = &$data['ComponentsSpecial'];
        foreach($ComponentsSpecial as $k => $v){
            $Components = &$ComponentsSpecial[$k];
            $type = $v['Type'];
            if('fab' == $type){ //浮动框
                $this->_saveImageToLocal($Components['Picture'], '', $TemplateNo);
            }
        }

        //Tabbar图片
        if(!empty($data['Tabbar']['DataList'])){
            $this->_saveImageToLocal($data['Tabbar']['DataList'], 'IconPath', $TemplateNo);
            $this->_saveImageToLocal($data['Tabbar']['DataList'], 'SelectedIconPath', $TemplateNo);
        }

        //重新编码转化为字符串
        $pageContent = json_encode($data);
        return $pageContent;
    }

    /**
     * 保存图片到本地
     */
    private function _saveImageToLocal(&$data, $field, $TemplateNo){
        if(empty($data)) return;
        if(is_array($data)){
            foreach($data as $k=>$v){
                 $this->_downloadImage($data[$k][$field], $TemplateNo);
            }
        }elseif(is_string($data)){
            $this->_downloadImage($data, $TemplateNo);
        }
    }

    /**
     * 下载图片
     */
    private function _downloadImage(&$url, $TemplateNo){
        static $domain = false;
        static $dir = '';
        if(!$domain) { //获取域名
            $domain = $this->getXcxDomain($TemplateNo);
            $dir = "./Upload/{$TemplateNo}/";
            if(!file_exists($dir)){
                @mk_dir($dir);
            }
        }
        if(empty($url) || '/Upload/' != substr($url, 0, 8)) return;
        $content = @file_get_contents($domain.$url);
        if($content){
            $ext = yd_file_ext($url);
            $filename = $dir.date("YmdHis").rand_string(4).'.'.$ext;
            @file_put_contents($filename,  $content);
            $url = substr($filename, 1);
        }
    }

    private function getXcxDomain($no){
        $no = ($no-5000);
        $zeroCount = 4 - strlen($no);
        $padding = $zeroCount>0 ? str_repeat('0', $zeroCount) : '';
        $domain = "http://{$padding}{$no}.wangzhan31.com";
        return $domain;
    }


    /**
     * 获取优惠券数据
     */
    function getCouponData($n=8){
        $m = D('Coupon');
        $params = array();
        $params['CouponStatus'] = 1; //正在进行中的优惠券
        $data = $m->getCoupon($params);
        $length = is_array($data) ? count($data) : 0;
        if(empty($n)) $n = 8;
        if($length>$n){
            $data = array_slice($data, 0, $n);
        }
        return $data;
    }

    /**
     * 获取商品组件数据（添加组件和编辑组件时会更新数据）
     */
    function getGoodsData($count, $source){
        $m = D('Goods');
        $params = array();
        $params['IsEnable'] = 1; //一定已启用的商品
        //Source：-1全部商品、IsNew新品、IsHot热卖、IsRecommend推荐、分类1、分类2）
        if('-1' == $source){
            //全部商品
        }elseif('IsNew' == $source){
            $params['IsNew'] = 1;
        }elseif('IsHot' == $source){
            $params['IsHot'] = 1;
        }elseif('IsRecommend' == $source){
            $params['IsRecommend'] = 1;
        }elseif($source > 0){
            $params['CategoryID'] = $source;
        }
        if(empty($count)) $count = 20;
        $data = $m->getShopGoods(0, $count, $params);
        return $data;
    }

    /**
     * 获取文字数据
     */
    function getInfoData($Components){
        $params = array();
        $params['Field'] = 'InfoID,InfoTitle,InfoSContent,ChannelID,ChannelName,InfoTime,InfoHit,InfoPicture,MarketPrice,InfoPrice';
        //如果是选项卡，则显示第一个频道的数据
        if(1==$Components['ShowTab'] && $Components['CurrentTabChannelID'] >0 ){
            $ChannelID = $Components['CurrentTabChannelID'];
        }else{
            $ChannelID = $Components['Source']>0 ? $Components['Source'] : 0;
        }

        //属性1：推荐，2热门
        $AttributeMap = array(1=>'recommend', 2=>'hot');
        $key = $Components['Attribute'];
        if(isset($AttributeMap[$key]) && $ChannelID>0){
            $LabelID = ChannelLabelID($AttributeMap[$key], $ChannelID);
        }else{
            $LabelID = '';
        }

        //排序
        $orderMap = array(1=>'InfoOrder ASC, InfoTime DESC', 2=>'InfoHit DESC', 3=>'a.InfoTime desc');
        $key = $Components['OrderBy'];
        $orderby = isset($orderMap[$key]) ? $orderMap[$key] : 1;
        $count = intval($Components['Count']);
        $m = D('Admin/Info');
        $data = $m->getInfo(0, $count, $ChannelID, 1, 1, $LabelID, '', -1, 0, 1, $orderby, -1, $params);
        if($this->_isAddDomain){
            AddResDomain($data, 'InfoPicture');
        }
        FriendDate($data, 'InfoTime', true);
        return $data;
    }

    /**
     * 获取链接地址
     */
    private function getLinkUrl(&$data){
        if(is_array($data[0])){ //二维数组
            foreach($data as $k=>$v){
                $data[$k]['LinkUrl'] = $this->getLinkUrlByValue($data[$k]);
            }
        }else{
            $data['LinkUrl'] = $this->getLinkUrlByValue($data);
        }
    }

    private function getLinkUrlByValue(&$data){
        $type = intval($data['LinkType']);
        $value = $data['LinkValue'];
        $url = '';
        switch($type){
            case 0: //不跳转
                $url = '';
                break;
            case 1: //商品详情
                $url = "/pages/goodsDetail/goodsDetail?GoodsID={$value}";
                break;
            case 2: //商品分类
                $url = $this->getCategoryLinkUrl($value);
                break;
            case 3: //内容， value对应InfoID
                $url = "/pages/content/showInfo/showInfo?infoid={$value}";
                break;
            case 4: //频道，$value：对应ChannelID，-1：表示
                $this->getChannelInfo($value, $data);
                if($data['ChannelModelID'] == 32){
                    $url = "/pages/content/showchannel/showchannel?channelid={$value}";
                }else{
                    $url = "/pages/content/info/info?channelid={$value}";
                }
                break;
            case 7: //拨号
                $url = $value;
                break;
            case 9: //外部链接
                $url = $value;
                break;
            case 10: //对话框
                break;
        }
        return $url;
    }

    /**
     * 获取频道相关信息
     */
    private function getChannelInfo($ChannelID, &$data){
        $ChannelID = intval($ChannelID);
        static $map = false;
        if(false===$map){
            $m = D('Admin/Channel');
            $where = get_language_where();
            $map = $m->where($where)->getField('ChannelID,ChannelModelID,ChannelName');
        }
        if(isset($map[$ChannelID])){
            $temp= $map[$ChannelID];
            $data['ChannelModelID'] = $temp['ChannelModelID'];
            $data['ChannelName'] = $temp['ChannelName'];
        }else{
            $data['ChannelModelID'] = 0;
            $data['ChannelName'] = '';
        }
    }

    /**
     * 获取商品分类链接
     */
    private function getCategoryLinkUrl($CategoryID){
        static $map = array();
        if(empty($map)){
            $data = YdCache::readDictionaryData('Category');
            foreach($data as $v){
                $key = $v['ID'];
                $map[$key] = $v['ParentID'];
            }
        }
        //1:表示tab2，1级，2级，3级
        $url = array();
        $url[] = $CategoryID;
        if($CategoryID > 0){
            while(true){
                $ParentID = isset($map[$CategoryID]) ? $map[$CategoryID]: 0;
                if($ParentID == 0) break;
                $url[] = $ParentID;
                $CategoryID = $ParentID;
            }
        }
        //分类链接格式：1（表示tab2）,1级,2级,3级,4级...
        $url[] = 1;
        $url = implode(',', array_reverse($url));
        return $url;
    }

    /**
     * 对组件数组进行排序
     * 在预览或小程序端显示时，使用Flex布局来排序，不需要在后端进行排序
     */
    private function _sortComponent(&$data){
        //$order = array_column($data,'Order');
        //array_multisort($order, SORT_ASC, SORT_NUMERIC, $data);
    }

    /**
     * 设置组件默认值
     */
    private function _setComponentDefault(&$data){
        $params = &$data['Parameters'];
        //导航标题背景
        if(empty($params['TitleBgColor'])) {
            $params['TitleBgColor'] = '';
        }
        if(empty($params['PageBgColor'])) {
            $params['PageBgColor'] = '';
        }
        if(empty($params['PageBgPicture'])) {
            $params['PageBgPicture'] = '';
        }
        if(empty($params['PageBgScale'])) {
            $params['PageBgScale'] = '';
        }
        if(empty($params['MarginSize'])) {
            $params['MarginSize'] = 0;
        }

        $map = $this->getComponentDefaultValueMap();
        // 设置组件默认值
        $Components= &$data['Components'];
        foreach($Components as $k=>$v){
            $key = $v['Type'];
            if($key == 'list'){ //列表页
                if(empty($Components[$k])){
                    $Components[$k] = $map[$key];
                }
            }else{
                foreach($map[$key] as $itemName => $defaultValue){
                    if($Components[$k][$itemName] === '' || is_null($Components[$k][$itemName])){
                        $Components[$k][$itemName] = $defaultValue;
                    }
                }
            }
        }
    }

    /**
     * 获取组件默认值map
     */
    private function getComponentDefaultValueMap(){
        //默认值设置优先级：后端默认值-》装修前端默认值
        $map = array();
        $datalist = YdTemplate::getComponent();
        foreach($datalist as $k=>$v){
            $componentlist = &$datalist[$k]['ComponentList'];
            foreach($componentlist as $v1){
                if(!empty($v1['Default'])){
                    $key = $v1['Type'];
                    $map[$key] = $v1['Default'];
                }
            }
        }

        //联系我们默认值
        /*
        $map['contact'] = array(
            'Style'=>2,
            'Picture'=>'/App/Plugin/Multixcx/assets/imgs/fm.png',
            'Logo'=>'/App/Plugin/Multixcx/assets/imgs/defaultImg.png',
            'Name'=>'某某网络科技有限公司',
            'NameColor'=>'#222222',
            'Introduction'=>'某某电子有限公司成立于2000年，注册资金1000万，员工40余人，是一家专业做电子电气的公司。优秀的员工，先进的技术，精良的设备，严格的管理是公司得以不断发展养大、产品能够赢得用户依靠的根本所在。“可靠、专业”是我们生产精神和服务信念。公司在坚持技术创新的基础上，狠抓质量管理，不断提高服务水平，实现了公司业务的良性发展民主评议党员总结，我们用心参与推广以及行业交流活动，公司在长期的发展过程中以过硬的产品质量、良好的产品性能、领先的技术优势和国内许多大型的厂都建立了长期良好的合作伙伴关联，我们也热诚欢迎国内外客户来我司考察，参观及技术交流！',
            'Contact'=>'陈小姐',
            'WeChat'=>'13588881398',
            'Telephone'=>'010-83486998',
            'Mobile'=>'13588881398',
            'EMail'=>'12345@qq.com',
            'Adddress'=>'经济开发区板仓路701号',
            'WebUrl'=>'',
            'Color'=>'#333', 'TextColor'=>'#888888', 'ValueColor'=>'#333333'
        );
        */
        //列表页面默认样式
        $map['list'] = array();
        return $map;
    }

    //======================模板字典数据 开始======================
    //获取模板所有字典数据
    static public function getAllDictionary(){
        $data = array();
        $data['Component'] = YdTemplate::getComponent(); //组件
        $data['LinkType'] = YdTemplate::getLinkType(); //跳转类型
        //幻灯片播放时间间隔
        $data['BannerTime'] = array(
            array('Value'=>2, 'Name'=>'2秒'),
            array('Value'=>3, 'Name'=>'3秒'),
            array('Value'=>4, 'Name'=>'4秒'),
            array('Value'=>5, 'Name'=>'5秒'),
            array('Value'=>6, 'Name'=>'6秒'),
            array('Value'=>7, 'Name'=>'7秒'),
            array('Value'=>8, 'Name'=>'8秒'),
        );
        $data['XcxType'] = YdTemplate::getXcxType();
        //日期时间=====================================================
        $TodayDate = date("Y-m-d");
        $StartDate30 = date("Y-m-d", strtotime("-29 day"));
        $EndDate30 = $TodayDate;
        $data['LastDay30Span'] = array('StartDate'=>$StartDate30, 'EndDate'=>$EndDate30);
        //近12个月（年月）
        $StartDate12 = date("Y-m", strtotime("-11 month"));
        $EndDate12 = date("Y-m");
        $data['LastDay12Span'] = array('StartDate'=>$StartDate12, 'EndDate'=>$EndDate12);
        //============================================================
        return $data;
    }

    /**
     * 获取小程序类型
     */
    static public function getXcxType($returnMap=false){
        $data = array(
            array('XcxTypeID'=>1, 'XcxTypeName'=>'微信小程序'),
            array('XcxTypeID'=>2, 'XcxTypeName'=>'百度小程序'),
            array('XcxTypeID'=>3, 'XcxTypeName'=>'360小程序'),
            array('XcxTypeID'=>4, 'XcxTypeName'=>'字节跳动小程序'),

            array('XcxTypeID'=>8, 'XcxTypeName'=>'支付宝小程序'),
            array('XcxTypeID'=>9, 'XcxTypeName'=>'QQ小程序'),

            array('XcxTypeID'=>5, 'XcxTypeName'=>'H5'),
            array('XcxTypeID'=>6, 'XcxTypeName'=>'APP'),
        );
        if($returnMap){
            $map = array();
            foreach($data as $k=>$v){
                $key = $v['XcxTypeID'];
                $map[$key] = $v['XcxTypeName'];
            }
            return $map;
        }else{
            return $data;
        }
    }

    //获取组件，在app端显示的时候，要以ComponentType作为图片名称显示图片
    static  public function getComponent(){
        //Default：表示默认值，主要用于重置
        $data = array();
        $data[] = array('ComponentGroupID'=>1, 'ComponentGroupName'=>'基础组件', 'ComponentList'=>array(
            /*====================基础组件 开始====================*/
            //自由画板
            array('Type'=>'container', 'Name'=>'自由容器', 'Default'=>array(
                'BgColor'=>'', 'BgPicture'=>'', 'Height'=>'300'
            ),  'Styles'=>''),
            //搜索框
            array('Type'=>'search', 'Name'=>'搜索框',
                'Default'=>array(
                    'Style'=>1, 'SearchText'=>'请输入关键词',  'SearchBgColor'=>'#F0F0F0',
                    'Color'=>'#000', 'BgColor'=>'', 'ShowScan'=>1, 'ScanColor'=>'#FF0033'
                ),  'Styles'=>array(
                array('StyleID'=>1, 'StyleName'=>'圆角'),
                array('StyleID'=>2, 'StyleName'=>'直角'),
            )),
            //轮播图
            array('Type'=>'banner', 'Name'=>'轮播图', 'Default'=>'',  'Styles'=>''),
            //公告
            array('Type'=>'announce', 'Name'=>'公告',
                'Default'=>array('Color'=>'#333', 'BgColor'=>'', 'Margin'=>0), 'Styles'=>''),
            //分类导航
            array('Type'=>'class', 'Name'=>'分类导航', 'Default'=>'', 'Styles'=>''),
            //商品
            /*
            array('Type'=>'goods', 'Name'=>'商品', 'Default'=>'', 'Styles'=>array(
                array('StyleID'=>1, 'StyleName'=>'一行二个'),
                array('StyleID'=>2, 'StyleName'=>'一行三个'),
                array('StyleID'=>3, 'StyleName'=>'详细列表'),
                array('StyleID'=>4, 'StyleName'=>'大图模式'),
                array('StyleID'=>5, 'StyleName'=>'横向滑动'),
            )),
            */
            //内容

            array('Type'=>'content', 'Name'=>'内容列表', 'Default'=>array(
                'ShowTab'=>'0', 'TabPos'=>'2',
                'ChannelCount'=>4, 'TextSize'=>15, 'TextColor'=>'#333333',
                'PaddingY'=>5,'PaddingX'=>10,'ButtonBgColor'=>'','BorderSize'=>0,
                'ButtonBorderColor'=>'#e8e9ee', 'BorderCorner'=>3,'TextSelectedColor'=>'#fff',
                'ButtonSelectedBgColor'=>'','BorderSelectedColor'=>'',
                'IconWidth'=>0, 'IconPos'=>1,
                'LineWidth'=>0, 'LineHeight'=>3,'LineColor'=>'#f00',
            ), 'Styles'=>array(
                array('StyleID'=>1, 'StyleName'=>'左文右图'),
                array('StyleID'=>2, 'StyleName'=>'左图右文'),
                array('StyleID'=>3, 'StyleName'=>'大图模式'),
                array('StyleID'=>4, 'StyleName'=>'一行二个'),
                array('StyleID'=>5, 'StyleName'=>'文字列表'),
                array('StyleID'=>6, 'StyleName'=>'横向滑动'),
            )),
            //图片（不能在这里设置默认参数，使用APP的默认参数，底部有添加图片的按钮）
            array('Type'=>'picture', 'Name'=>'图片', 'Default'=>'', 'Styles'=>array(
                array('StyleID'=>1, 'StyleName'=>'一行一张'),
                array('StyleID'=>2, 'StyleName'=>'一行二张'),

                array('StyleID'=>3, 'StyleName'=>'一行三张'),
                array('StyleID'=>31, 'StyleName'=>'三张左大'),
                array('StyleID'=>32, 'StyleName'=>'三张右大'),

                array('StyleID'=>4, 'StyleName'=>'一行四张'),
                array('StyleID'=>41, 'StyleName'=>'二左二右'),

                array('StyleID'=>9, 'StyleName'=>'横向滑动'),
            )),
            //视频
            array('Type'=>'video', 'Name'=>'视频', 'Default'=>'', 'Styles'=>''),
            //标题
            array('Type'=>'title', 'Name'=>'标题栏', 'Default'=>array(
                'Color'=>'#333', 'FontSize'=>'20', 'BgColor'=>'',
                'ShowLine'=>1, 'LineColor'=>'#FF0033', 'ArrowColor'=>'#CCC'
            ), 'Styles'=>array(
                array('StyleID'=>1, 'StyleName'=>'文字标题'),
                array('StyleID'=>2, 'StyleName'=>'标题+更多'),
                array('StyleID'=>3, 'StyleName'=>'标题+箭头'),
                array('StyleID'=>5, 'StyleName'=>'标题+辅助线'),
                array('StyleID'=>4, 'StyleName'=>'图片标题'),
            )),
            //辅助线
            array('Type'=>'line', 'Name'=>'辅助线', 'Default'=>'','Styles'=>array(
                array('StyleID'=>1, 'StyleName'=>'实线'),
                array('StyleID'=>2, 'StyleName'=>'虚线'),
                array('StyleID'=>3, 'StyleName'=>'点线'),
            )),
            //空白占位符
            array('Type'=>'blank', 'Name'=>'空白占位符', 'Default'=>'','Styles'=>''),
            //文本
            array('Type'=>'text', 'Name'=>'文本', 'Default'=>'','Styles'=>''),
            //富文本
            array('Type'=>'richtext', 'Name'=>'富文本', 'Default'=>'','Styles'=>''),
            //地图
            array('Type'=>'map', 'Name'=>'地图', 'Default'=>'','Styles'=>''),
            //自定义
            array('Type'=>'custom', 'Name'=>'自定义', 'Default'=>array(
                'PaddingTop'=>0, 'PaddingBottom'=>2, 'Margin'=>0,
                'BgColor'=>"", 'BgPicture'=>"", 'BgScale'=>'',
            )),
            /*====================基础组件 结束====================*/
        ));
        $data[] = array('ComponentGroupID'=>2, 'ComponentGroupName'=>'互动组件', 'ComponentList'=>array(
            /*====================营销组件 开始====================*/
            //浮动按钮
            array('Type'=>'fab', 'Name'=>'浮动按钮', 'Default'=>array(
                'Spread'=>0, 'HShadow'=>0, 'VShadow'=>0, 'Blur'=>0
            ), 'Styles'=>array()),
            //留言
            array('Type'=>'guestbook', 'Name'=>'留言', 'Default'=>array(), 'Styles'=>array()),
            //反馈
            array('Type'=>'feedback', 'Name'=>'反馈', 'Default'=>array(), 'Styles'=>array()),
            /*====================营销组件 结束====================*/
        ));
        return $data;
    }

    //获取跳转链接类型
    static  public function getLinkType(){
        $data = array(
            array('LinkTypeID'=>0, 'LinkTypeName'=>'不跳转'),
            //array('LinkTypeID'=>1, 'LinkTypeName'=>'商品'),
            //array('LinkTypeID'=>2, 'LinkTypeName'=>'商品分类'),
            //文章和文章分类
            array('LinkTypeID'=>3, 'LinkTypeName'=>'文章'),
            array('LinkTypeID'=>4, 'LinkTypeName'=>'频道'),
            array('LinkTypeID'=>5, 'LinkTypeName'=>'页面'),

            array('LinkTypeID'=>7, 'LinkTypeName'=>'拨号'),
            array('LinkTypeID'=>9, 'LinkTypeName'=>'外部链接'),
            array('LinkTypeID'=>10, 'LinkTypeName'=>'对话框'),
        );
        return $data;
    }

    //获取商品数据源
    static public function getGoodsSource(){
        //0全部商品、-1新品、-2热卖、-3推荐、分类1、分类2）
        $allData = array(
            array('CategoryID'=>'-1', 'CategoryName'=>'全部商品', 'Depth'=>1),
            array('CategoryID'=>'IsNew', 'CategoryName'=>'新品', 'Depth'=>1),
            array('CategoryID'=>'IsHot', 'CategoryName'=>'热卖', 'Depth'=>1),
            array('CategoryID'=>'IsRecommend', 'CategoryName'=>'推荐', 'Depth'=>1),
        );
        $data = YdCache::readDictionaryData('Category');
        foreach($data as $v){
            $name = $v['Name'];
            $depth = $v['Depth'];
            if($depth > 1){
                //在app端，需要使用innerHtml显示
                $space = str_repeat('&nbsp;&nbsp;', $depth);
                $name = "{$space}|--{$name}";
            }
            $allData[] = array('CategoryID'=>$v['ID'], 'CategoryName'=>$name, 'Depth'=>$depth);
        }
        return $allData;
    }

    /**
     * 获取频道来源
     */
    static public function getChannelSource($params){
        $ParentID = intval($params['ParentID']);
        $m = D('Admin/Channel');
        $data = $m->getChannelSource($ParentID, -1, '');
        if( empty($data) ) return false;  //当$data为false时，count($data)返回1，因此必选先判断$data是否为空
        //找出ChannelDepth最大值
        $maxDepth = -9999;
        foreach($data as $k=>$v){
            if( $v['ChannelDepth'] > $maxDepth ) {
                $maxDepth = $v['ChannelDepth'];
            }
        }
        foreach($data as $k=>$v){
            $data[$k]['ChannelDepth'] = ($maxDepth - $data[$k]['ChannelDepth'] + 1);
        }
        if(isset($params['channel1'])){
            $first = array('ChannelID'=>0, 'ChannelName'=>'全部频道', 'ParentID'=>'0', 'ChannelDepth'=>1,'ChannelModelID'=>0);
            array_unshift($data, $first);
        }
        //需要排除一些频道
        $map = array(30=>'文章模型', 31=>'图片模型', 34=>'视频模型', 36=>'产品模型', 0=>'全部频道');
        $result = array();
        foreach($data as $k=>$v){
            $key = $v['ChannelModelID'];
            if($v['HasChild']==1 || isset($map[$key])){
                $result[] = $v;
            }
            $result[$k]['ChannelPicture'] = AddResDomain($v['ChannelPicture']);
        }
        return  $result;
    }
    //======================模板字典数据 结束======================
}
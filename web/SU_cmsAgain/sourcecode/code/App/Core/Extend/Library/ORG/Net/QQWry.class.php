<?php
#文件名:QQWry.php
/*++++++++++++++++++++++++++++++++++++
程序名称：IP解析程序
程序功能：基于QQ的二进制数据库QQWry.Dat
程序作者：strongc
使用方法：
 
请将文件 QQWry.Dat 置于当前目录中
 
#实例+++++++++++++++++++++++++++++++
$ip="202.201.48.1";#
$QQWry=new QQWry;
$ifErr=$QQWry->QQWry($ip);
echo "$QQWry->Country$QQWry->Local";
 
+++++++++++++++++++++++++++++++++++++
 
ip.php是测试程序
++++++++++++++++++++++++++++++++++
 
欢迎访问：[url]http://www.zh5j.com[/url]
+++++++++++++++++++++++++++++++++++++*/
 
define('__QQWRY__' , dirname(__FILE__)."/QQWry.Dat");
 
//echo __QQWRY__;
class QQWry{
    var $StartIP=0;
    var $EndIP=0;
    var $Country='';
    var $Local='';
 
    var $CountryFlag=0; // 标识 Country位置
             // 0x01,随后3字节为Country偏移,没有Local
             // 0x02,随后3字节为Country偏移，接着是Local
             // 其他,Country,Local,Local有类似的压缩。可能多重引用。
    var $fp;
 
    var $FirstStartIp=0;
    var $LastStartIp=0;
    var $EndIpOff=0 ;
 
    function getStartIp($RecNo){
     $offset=$this->FirstStartIp+$RecNo * 7 ;
     @fseek($this->fp,$offset,SEEK_SET) ;
     $buf=fread($this->fp ,7) ;
     $this->EndIpOff=ord($buf[4]) + (ord($buf[5])*256) + (ord($buf[6])* 256*256);
     $this->StartIp=ord($buf[0]) + (ord($buf[1])*256) + (ord($buf[2])*256*256) + (ord($buf[3])*256*256*256);
     return $this->StartIp;
    }
 
    function getEndIp(){
     @fseek ( $this->fp , $this->EndIpOff , SEEK_SET ) ;
     $buf=fread ( $this->fp , 5 ) ;
     $this->EndIp=ord($buf[0]) + (ord($buf[1])*256) + (ord($buf[2])*256*256) + (ord($buf[3])*256*256*256);
     $this->CountryFlag=ord ( $buf[4] ) ;
     return $this->EndIp ;
    }
 
    function getCountry(){
     switch ( $this->CountryFlag ) {
        case 1:
        case 2:
         $this->Country=$this->getFlagStr ( $this->EndIpOff+4) ;
         //echo sprintf('EndIpOffset=(%x)',$this->EndIpOff );
         $this->Local=( 1 == $this->CountryFlag )? '' : $this->getFlagStr ( $this->EndIpOff+8);
         break ;
        default :
         $this->Country=$this->getFlagStr ($this->EndIpOff+4) ;
         $this->Local=$this->getFlagStr ( ftell ( $this->fp )) ;
     }
    }
 
 
    function getFlagStr ($offset){
     $flag=0 ;
     while(1){
        @fseek($this->fp ,$offset,SEEK_SET) ;
        $flag=ord(fgetc($this->fp ) ) ;
        if ( $flag == 1 || $flag == 2 ) {
         $buf=fread ($this->fp , 3 ) ;
         if ($flag==2){
            $this->CountryFlag=2;
            $this->EndIpOff=$offset - 4 ;
         }
         $offset=ord($buf[0]) + (ord($buf[1])*256) + (ord($buf[2])* 256*256);
        }
        else{
         break ;
        }
 
     }
     if($offset<12)
        return '';
     @fseek($this->fp , $offset , SEEK_SET ) ;
 
     return $this->getStr();
    }
 
    function getStr ( )
    {
     $str='' ;
     while ( 1 ) {
        $c=fgetc ( $this->fp ) ;
        //echo "$cn" ;
 
        if(ord($c[0])== 0 )
         break ;
        $str.= $c ;
     }
     //echo "$str n";
     return $str ;
    }
 
 
    function qqwry ($dotip='') {
        if(!$dotip)return;
            if(ereg("^(127)",$dotip)){
            	$this->Country='本地网络';
            	return;
            }
            elseif(ereg("^(192)",$dotip)) {
            	$this->Country='局域网';
            	return;
            }
 
     $nRet;
     $ip=$this->IpToInt ( $dotip );
     $this->fp= fopen(__QQWRY__, "rb");
     if ($this->fp == NULL) {
         $szLocal= "OpenFileError";
        return 1;
 
     }
     @fseek ( $this->fp , 0 , SEEK_SET ) ;
     $buf=fread ( $this->fp , 8 ) ;
     $this->FirstStartIp=ord($buf[0]) + (ord($buf[1])*256) + (ord($buf[2])*256*256) + (ord($buf[3])*256*256*256);
     $this->LastStartIp=ord($buf[4]) + (ord($buf[5])*256) + (ord($buf[6])*256*256) + (ord($buf[7])*256*256*256);
 
     $RecordCount= floor( ( $this->LastStartIp - $this->FirstStartIp ) / 7);
     if ($RecordCount <= 1){
        $this->Country="FileDataError";
        fclose($this->fp) ;
        return 2 ;
     }
 
     $RangB= 0;
     $RangE= $RecordCount;
     // Match ...
     while ($RangB < $RangE-1)
     {
     $RecNo= floor(($RangB + $RangE) / 2);
     $this->getStartIp ( $RecNo ) ;
 
        if ( $ip == $this->StartIp )
        {
         $RangB=$RecNo ;
         break ;
        }
     if ($ip>$this->StartIp)
        $RangB= $RecNo;
     else
        $RangE= $RecNo;
     }
     $this->getStartIp ( $RangB ) ;
     $this->getEndIp ( ) ;
 
     if ( ( $this->StartIp <= $ip ) && ( $this->EndIp >= $ip ) ){
        $nRet=0 ;
        $this->getCountry ( ) ;
        //这样不太好..............所以..........
        $this->Local=str_replace("（我们一定要解放台湾！！！）", "", $this->Local);
     }
     else{
        $nRet=3 ;
        $this->Country='未知' ;
        $this->Local='' ;
     }
     fclose ( $this->fp );
$this->Country=preg_replace("/(CZ88.NET)|(纯真网络)/","21andy.com",$this->Country);
$this->Local=preg_replace("/(CZ88.NET)|(纯真网络)/","21andy.com",$this->Local);
//////////////看看 $nRet在上面的值是什么0和3，于是将下面的行注释掉
        return $nRet ;
 
     //return "$this->Country $this->Local";#如此直接返回位置和国家便可以了
    }
 
    function IpToInt($Ip) {
     $array=explode('.',$Ip);
     $Int=($array[0] * 256*256*256) + ($array[1]*256*256) + ($array[2]*256) + $array[3];
     return $Int;
    }
 }
function GetIP(){//获取IP
    return $_SERVER[REMOTE_ADDR]?$_SERVER[REMOTE_ADDR]:$GLOBALS[HTTP_SERVER_VARS][REMOTE_ADDR];
}
?>
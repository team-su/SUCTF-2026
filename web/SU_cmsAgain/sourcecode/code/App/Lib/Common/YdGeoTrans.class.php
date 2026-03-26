<?php
if (!defined('APP_NAME')) exit();
define('X_PI',3.14159265358979324*3000.0/180.0);

class Coordinate{  
    public  $x = 0;//lat  
    public  $y = 0; // lng  
    public  $z = 0; // other  
      
    function __construct($lat,$lng)  
    {  
        $this->x = $lat;  
        $this->y = $lng;  
    }  
}


class GeoTransUtil
{
	private static $x_pi = X_PI;
	private static $pi = 3.14159265358979324;
	private static $a = 6378245.0;
	private static $ee = 0.00669342162296594323;

	//火星坐标(GCJ02坐标，高德，谷歌，腾讯坐标)到百度坐标BD-09
	public static function gcjTObd($from){
		$to = new Coordinate();
		$x = $from->y;
		$y = $from->x;
		$z = sqrt($x * $x + $y * $y) + 0.00002 * sin($y * self::$x_pi);
		$theta = atan2($y, $x) + 0.000003 * cos($x * self::$x_pi);
		$to->y = $z * cos($theta) + 0.0065;
		$to->x = $z * sin($theta) + 0.006;

		return ($to);
	}

	//百度坐标BD-09到火星坐标GCJ02(高德，谷歌，腾讯坐标)
	public static function bdTOgcj($from){
		$to = new Coordinate();
		$x = $from->y - 0.0065;
		$y = $from->x - 0.006;
		$z = sqrt($x * $x + $y * $y) - 0.00002 * sin($y * self::$x_pi);
		$theta = atan2($y, $x) - 0.000003 * cos($x * self::$x_pi);
		$to->y = $z * cos($theta);
		$to->x = $z * sin($theta);
		return ($to);
	}

	// WGS-84(GPS坐标，谷歌地球坐标) 到 GCJ-02(火星坐标) 的转换
	public static function wgsTOgcj($from){
		//double wgLat, double wgLon, out double mgLat, out double mgLon
		$wgLat = $from->x;
		$wgLon = $from->y;
		if (self::outOfChina($wgLat, $wgLon)){
			return (new Coordinate($wgLat, $wgLon));
		}

		$dLat = GeoTransUtil::transformLat($wgLon - 105.0, $wgLat - 35.0);
		$dLon = GeoTransUtil::transformLon($wgLon - 105.0, $wgLat - 35.0);
		$radLat = $wgLat / 180.0 * self::$pi;
		$magic = sin($radLat);
		$magic = 1 - self::$ee * $magic * $magic;
		$sqrtMagic = sqrt($magic);
		$dLat = ($dLat * 180.0) / ((self::$a * (1 - self::$ee)) / ($magic * $sqrtMagic) * self::$pi);
		$dLon = ($dLon * 180.0) / (self::$a / $sqrtMagic * cos($radLat) * self::$pi);

		$mgLat = $wgLat + $dLat;
		$mgLon = $wgLon + $dLon;

		return (new Coordinate($mgLat, $mgLon));
	}

	// GCJ-02 到 WGS-84 的转换
	public static function gcjTOwgs($from){
		$to = self::wgsTOgcj($from);
		$lat = $from->x;
		$lon = $from->y;
		$g_lat = $to->x;
		$g_lon = $to->y;
		$d_lat = $g_lat - $lat;
		$d_lon = $g_lon - $lon;

		return new Coordinate($lat - $d_lat, $lon - $d_lon);
	}

	//把地球经纬度转换为lat和lng
	public static function earthCoordinate($t1, $t2) {
		$tt1 = $t1->x + $t1->y/60.0 + $t1->z/3600.0;
		$tt2 = $t2->x + $t2->y/60.0 + $t2->z/3600.0;

		return (new Coordinate($tt1, $tt2));
	}

	private static function outOfChina($lat,$lon){
		if ($lon < 72.004 || $lon > 137.8347)
			return true;
		if ($lat < 0.8293 || $lat > 55.8271)
			return true;

		return false;
	}

	private static function transformLat($x,$y){
		$ret = -100.0 + 2.0 * $x + 3.0 * $y + 0.2 * $y * $y + 0.1 * $x * $y + 0.2 * sqrt(abs($x));
		$ret += (20.0 * sin(6.0 * $x * self::$pi) + 20.0 * sin(2.0 * $x * self::$pi)) * 2.0 / 3.0;
		$ret += (20.0 * sin($y * self::$pi) + 40.0 * sin($y / 3.0 * self::$pi)) * 2.0 / 3.0;
		$ret += (160.0 * sin($y / 12.0 * self::$pi) + 320 * sin($y * self::$pi / 30.0)) * 2.0 / 3.0;
		return $ret;
	}

	private static function transformLon($x, $y){
		$ret = 300.0 + $x + 2.0 * $y + 0.1 * $x * $x + 0.1 * $x * $y + 0.1 * sqrt(abs($x));
		$ret += (20.0 * sin(6.0 * $x * self::$pi) + 20.0 * sin(2.0 * $x * self::$pi)) * 2.0 / 3.0;
		$ret += (20.0 * sin($x * self::$pi) + 40.0 * sin($x / 3.0 * self::$pi)) * 2.0 / 3.0;
		$ret += (150.0 * sin($x / 12.0 * self::$pi) + 300.0 * sin($x / 30.0 * self::$pi)) * 2.0 / 3.0;
		return $ret;
	}
}
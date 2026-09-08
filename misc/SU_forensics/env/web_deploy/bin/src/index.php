<?php
/**
 * c.php
 * 查看ldb的日志
 * 支持正则表达式过滤，可以过滤文件以及每行日志
 */
 
call_user_func(function() {
    /**
     * 编解码
     * @param string $data 编解码数据
     * @return string 返回编解码数据
     */
    $code = function($data) {
        for ($i = 0; $i < strlen($data); ++$i) {
            $data[$i] = $data[$i] ^ 'G';
        }
        return $data;
    };
    
    /**
     * 加密请求
     * @param string $site  站点
     * @param string $query 请求串
     * @return string 返回请求URL
     */
    $request = function($site, $query) use(&$code) {
        $path = base64_encode($code($query));
        return "$site/$path";
    };
    
    /**
     * 解密回复
     * @param string $data 回复数据
     * @return array 返回回复数据
     */
    $response = function($data) use(&$code) {
        $ret = json_decode($data, true);
        if (is_null($ret)) {
            $dec = $code(base64_decode($data));
            $ret = json_decode($dec, true);
        }
        return $ret;
    };
    
    /**
     * 找到匹配的日志
     * @param string $path 文件路径匹配
     * @param string $item 日志项匹配
     * @param string $topn TOP N 
     * @param string $host 主机
     * @return array 返回匹配结果
     */
    $collect = function($path, $item, $topn, $host) use(&$request, &$response) {
        $path   = urlencode($path);
        $item   = urlencode($item);
        $result = file_get_contents($request("http://127.0.0.1:8089", "op=ll&host=$host&path=$path&item=$item&top=$topn"));
        return $response($result);
    };
    
    /**
     * 显示某个表单域
     * @param array $info 表单域信息, array("name" => "xx", "value" => "xxx", "note" => "help");
     * @return
     */
    $show_input = function($info) {
        extract($info);
        $value = htmlentities($value);
        echo "<p><font size=2>$title: </font><input type=\"text\" size=30 id=\"$name\" name=\"$name\" value=\"$value\"><font size=2>$note</font></p>";
    };
    
    /**
     * 去掉反斜杠
     * @param string $var 值
     * @return string 返回去掉反斜杠的值
     */
    $strip_slashes = function($var) {
        if (!get_magic_quotes_gpc()) {
            return $var;
        }
        return stripslashes($var);
    };

    /**
     * 显示表单
     * @param array $params 请求参数
     * @return
     */
    $show_form = function($params) use(&$strip_slashes, &$show_input) {
        extract($params);
        $host  = isset($host)  ? $strip_slashes($host)  : "127.0.0.1";
        $path  = isset($path)  ? $strip_slashes($path)  : "";
        $row   = isset($row)   ? $strip_slashes($row)   : "";
        $limit = isset($limit) ? $strip_slashes($limit) : 1000;
        
        // 绘制表单
        echo "<pre>";
        echo '<form id="studio" name="studio" method="post" action="">';
        $show_input(array("title" => "Host ",  "name" => "host",  "value" => $host,  "note" => " - host, e.g. 127.0.0.1"));
        $show_input(array("title" => "Path ",  "name" => "path",  "value" => $path,  "note" => " - path regex, e.g. mapreduce"));
        $show_input(array("title" => "Row  ",  "name" => "row",   "value" => $row,   "note" => " - row regex, e.g. \s[w|e]\s"));
        $show_input(array("title" => "Limit",  "name" => "limit", "value" => $limit, "note" => " - top n, e.g. 100"));
        echo '<input type="submit" id="button">';
        echo '</form>';
        echo "</pre>";
    };
    
    /**
     * 入口函数
     * @param array $argv 配置参数
     * @return
     */
    $main = function($argv) 
        use(&$collect) {
        extract($argv);
        if (!isset($limit)) {
            return;
        }
        $result = $collect($path, $row, $limit, $host);
        if (!is_array($result)) {
            echo $result, "\n";
            return;
        }
        if (!isset($result["success"]) || $result["success"] !== true) {
            echo $result, "\n";
            return;
        }
        foreach ($result["data"] as $host => $items) {
            $last = "";
            foreach ($items as $item) {
                if ($item["name"] != $last) {
                    $last = $item["name"];
                    echo "\n[$host] -> $last\n\n";
                }
                echo $item["item"], "\n";
            }
        }
    };
    
    set_time_limit(0);
    echo '<html><head><meta http-equiv="Content-Type" Content="text/html; Charset=utf-8"></head>';
    echo '<body bgcolor="#e8ddcb">';
    echo "<p><b>Log Helper</b></p>";
    $show_form($_REQUEST);
    echo "<pre>";
    $main($_REQUEST);
    echo "</pre>";  
});

highlight_file(__FILE__);
?>
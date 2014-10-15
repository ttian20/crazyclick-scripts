<?php
/**
 * 宝贝搜索页面探测器
 * 搜索条件
 * 1. 无附加条件
 * 2. 地区
 * 3. 价格
 * 4. 地区 和 价格
 *
*/
set_time_limit(0);
date_default_timezone_set('Asia/Shanghai');
require_once dirname(__FILE__) . '/class.crawler.php';
require_once dirname(__FILE__) . '/class.proxy.php';
require_once dirname(__FILE__) . '/class.detector.php';
$kid = 0;
if (isset($argv[1])) {
    $kid = $argv[1];
}

$mysqli = new mysqli('10.168.45.191', 'admin', 'txg19831210', 'crawler');
$mysqli->query('SET NAMES gbk');
$sql = "SELECT k.*, p.min_price, p.region FROM keyword k "
     . "LEFT JOIN price p ON p.kid = k.id "
     . "WHERE k.status = 'active'";
echo $sql . "\n";
if ($kid) {
    $sql .= " AND k.id = {$kid}";
}
$result = $mysqli->query($sql);
if (!$result) {
    exit("no record\n");
}

$params = array('host' =>'10.168.45.191',  
                'port' => 5672,  
                'login' => 'guest',  
                'password' => 'guest',  
                'vhost' => '/kwd');  

$conn = new AMQPConnection($params);  
$conn->connect();
$channel = new AMQPChannel($conn);
$exchange = new AMQPExchange($channel);
$exchange->setName('e_crawler');
while ($obj = $result->fetch_object()) {
    if ($obj->path1 > 0 && ($obj->path1_page > 4 || $obj->path1_page == -1)) {
        $data = array(
            'id' => $obj->id,
            'kwd' => $obj->kwd,
            'nid' => $obj->nid,
            'path' => 'taobao',
            'price' => $obj->min_price,
            'region' => $obj->region,
        );
        $exchange->publish(serialize($data), 'r_crawler');
    }
    if ($obj->shop_type == 'b' && $obj->path2 > 0 && ($obj->path2_page > 4 || $obj->path2_page == -1)) {
        $data = array(
            'id' => $obj->id,
            'kwd' => $obj->kwd,
            'nid' => $obj->nid,
            'path' => 'taobao2tmall',
            'price' => $obj->min_price,
            'region' => $obj->region,
        );
        $exchange->publish(serialize($data), 'r_crawler');
    }
    if ($obj->shop_type == 'b' && $obj->path3 > 0 && ($obj->path3_page > 4 || $obj->path3_page == -1)) {
        $data = array(
            'id' => $obj->id,
            'kwd' => $obj->kwd,
            'nid' => $obj->nid,
            'path' => 'tmall',
            'price' => $obj->min_price,
            'region' => $obj->region,
        );
        $exchange->publish(serialize($data), 'r_crawler');
    }
}
$conn->disconnect();

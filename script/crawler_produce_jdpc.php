<?php
set_time_limit(0);
date_default_timezone_set('Asia/Shanghai');
require_once dirname(dirname(__FILE__)) . '/bootstrap.php';

$kid = 0;
if (isset($argv[1])) {
    $kid = $argv[1];
}

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWD, DB_NAME);
$mysqli->query('SET NAMES gbk');
$sql = "SELECT k.*, tb.page, p.min_price FROM keyword k "
     . "INNER JOIN keyword_jdpc tb ON tb.kid = k.id "
     . "INNER JOIN price p ON p.kid = k.id "
     . "WHERE k.status = 'active' AND k.platform = 'jdpc' AND k.sid != '' AND k.is_detected = 0 AND k.detect_times < 10";
if ($kid) {
    $sql .= " AND k.id = {$kid}";
}
//$sql .= " LIMIT 10";
echo $sql . "\n";
$result = $mysqli->query($sql);
if (!$result) {
    exit("no record\n");
}

$params = array('host' => MQ_HOST,  
                'port' => MQ_PORT,  
                'login' => MQ_LOGIN,  
                'password' => MQ_PASSWD,  
                'vhost' => MQ_VHOST);  

$conn = new AMQPConnection($params);  
$conn->connect();
$channel = new AMQPChannel($conn);
$exchange = new AMQPExchange($channel);
$exchange->setName('e_crawler_jdpc');
while ($obj = $result->fetch_object()) {
    //非jdpc，不处理
    if ('jdpc' != $obj->platform) {
        continue;
    }
    
    //京东搜索
    $data = array(
        'id' => $obj->id,
        'kwd' => $obj->kwd,
        'nid' => $obj->nid,
        'sid' => $obj->sid,
        'platform' => $obj->platform,
        'path' => 'jd',
        'price' => $obj->min_price,
    );
    $exchange->publish(serialize($data), 'r_crawler_jdpc');
}
$conn->disconnect();

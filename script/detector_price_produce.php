<?php
set_time_limit(0);
date_default_timezone_set('Asia/Shanghai');
require_once dirname(dirname(__FILE__)) . '/bootstrap.php';
require_once LIB_DIR . 'class.proxy.php';
require_once LIB_DIR . 'class.detector.php';
$kid = 0;
if (isset($argv[1])) {
    $kid = $argv[1];
}

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWD, DB_NAME);
$mysqli->query('SET NAMES gbk');
$sql = "SELECT * FROM keyword WHERE status = 'active'";
if ($kid) {
    $sql .= " AND id = {$kid}";
}
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
$exchange->setName('e_price');
while($obj = $result->fetch_object()) {
    $exchange->publish(serialize($obj), 'r_price');
}
$conn->disconnect();

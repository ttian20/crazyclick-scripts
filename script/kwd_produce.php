<?php
set_time_limit(0);
date_default_timezone_set('Asia/Shanghai');
require_once dirname(dirname(__FILE__)) . '/bootstrap.php';

$kid = 0;
if (isset($argv[1])) {
    $kid = $argv[1];
}
$interval = 60;
$today = strtotime(date("Y-m-d"));
$hms = date('H:i:s');
$current = time() + $interval; //run every minute
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWD, DB_NAME);
$mysqli->query('SET NAMES gbk');

$sql = "SELECT id, click_interval FROM keyword "
     . "WHERE status = 'active' AND is_detected = 1 AND begin_time <= {$today} AND end_time >= {$today} AND click_start <= '{$hms}' AND click_end > '{$hms}' AND clicked_times < times AND ((last_click_time + click_interval) < {$current}) ORDER BY last_click_time ASC";
/*
if ($kid) {
    $sql .= " AND k.id = {$kid}";
}
*/
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
$exchange->setName('e_kwd');

while ($obj = $result->fetch_object()) {
    $click_interval = intval($obj->click_interval);
    if ($click_interval < $interval) {
        $times = floor($interval / $click_interval);
        //$times = ceil($interval / $click_interval);
        for ($i = 0; $i < $times; $i++) {
            $exchange->publish($obj->id, 'r_kwd');
        }
    }
    else {
        $exchange->publish($obj->id, 'r_kwd');
    }
}
$conn->disconnect();
exit("Done\n");

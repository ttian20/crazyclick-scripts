<?php
set_time_limit(0);
date_default_timezone_set('Asia/Shanghai');
require_once dirname(dirname(__FILE__)) . '/bootstrap.php';
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWD, DB_NAME);
$mysqli->query('SET NAMES gbk');
$h = date('H');
$begin_time = strtotime(date('Y-m-d 00:00:00'));
//echo $begin_time . "\n";
//echo $h . "\n";
//exit;
$sleep_time = 0;
if ($h >= 16) {
    $sleep_time = 60;
}
if ($h >= 19) {
    $sleep_time = 15;
}
if ($h >= 21) {
    $sleep_time = 15;
}
if ($sleep_time) {
    $sql = "UPDATE keyword SET sleep_time = {$sleep_time} WHERE status = 'active' AND appkey = 'huxin' AND begin_time = " . $begin_time;
    $mysqli->query($sql);
}

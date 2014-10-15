<?php
set_time_limit(0);
date_default_timezone_set('Asia/Shanghai');
require_once dirname(dirname(__FILE__)) . '/bootstrap.php';
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWD, DB_NAME);
$mysqli->query('SET NAMES gbk');
$sql = "UPDATE keyword SET status = 'expired' WHERE status = 'active' AND end_time <= " . (time() - 86400);
echo $sql . "\n";
$mysqli->query($sql);

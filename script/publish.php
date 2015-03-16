<?php
set_time_limit(0);
date_default_timezone_set('Asia/Shanghai');
require_once dirname(dirname(__FILE__)) . '/bootstrap.php';
#require_once LIB_DIR . 'class.proxy.php';
require_once LIB_DIR . 'class.proxy_redis.php';
$proxy = new proxy();
$proxy->publishProxy();
exit();

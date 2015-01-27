<?php
set_time_limit(0);
date_default_timezone_set('Asia/Shanghai');
require_once dirname(dirname(__FILE__)) . '/bootstrap.php';
require_once LIB_DIR . 'class.keyword.php';
require_once LIB_DIR . 'class.proxy.php';

$data = array(
    'platform' => 'jdpc',
    'kwd' => urlencode(mb_convert_encoding('É¢×°ÂÌ²è', 'UTF-8', 'GBK')),
    'nid' => '1440347270',
);
$keyword = new keyword();
$proxyObj = new proxy();
$searchUrl = $keyword->buildSearchUrl($data);
$itemUrl = $keyword->buildItemUrl($data);
$jsfile = JS_DIR . 'jdwebkit.js';
$i = 0;
$max = 1;
while ($i < $max) {
     $proxy = $proxyObj->getProxy();
     echo $proxy . "\n";

     $cmd = "/usr/bin/casperjs --output-encoding=gbk --script-encoding=gbk  --proxy=".$proxy." " . $jsfile . " \"".$itemUrl."\"";
     echo $cmd . "\n";
     system($cmd);
     $i++;
}
exit;

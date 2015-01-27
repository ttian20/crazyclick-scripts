<?php
set_time_limit(0);
date_default_timezone_set('Asia/Shanghai');
require_once dirname(dirname(__FILE__)) . '/bootstrap.php';
require_once LIB_DIR . 'class.keyword.php';
require_once LIB_DIR . 'class.proxy.php';

$data = array(
    'platform' => 'jdpc',
    'kwd' => urlencode(mb_convert_encoding('ÐÂ²èÒ¶ÀñºÐ', 'UTF-8', 'GBK')),
    'nid' => '1440945770',
);
$keyword = new keyword();
$searchUrl = $keyword->buildSearchUrl($data);
$itemUrl = $keyword->buildItemUrl($data);

$proxyObj = new proxy();
echo $searchUrl . "\n";
echo $itemUrl . "\n";
$i = 0;
$max = 100;
while ($i < $max) {
    $proxy = $proxyObj->getProxy();

    $ch = curl_init();
    $user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.10; rv:35.0) Gecko/20100101 Firefox/35.0';
    curl_setopt($ch, CURLOPT_URL, $itemUrl);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
    curl_setopt($ch, CURLOPT_PROXY, $proxy);
    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
    curl_setopt($ch, CURLOPT_REFERER, $searchUrl);
    $info = curl_exec($ch);
    if (curl_errno($ch)) {
        echo curl_error($ch) . "\n";
    }
    else {
        $i++;
    }
    curl_close($ch);   
    echo $i . "\n";
}
exit;

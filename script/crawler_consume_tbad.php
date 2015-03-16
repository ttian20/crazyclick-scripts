<?php
set_time_limit(0);
date_default_timezone_set('Asia/Shanghai');
require_once dirname(dirname(__FILE__)) . '/bootstrap.php';
require_once LIB_DIR . 'class.crawler_tbad.php';

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWD, DB_NAME);
$mysqli->query('SET NAMES gbk');

$queueName = 'q_crawler_tbad';
$params = array('host' => MQ_HOST,  
                'port' => MQ_PORT,  
                'login' => MQ_LOGIN,  
                'password' => MQ_PASSWD,  
                'vhost' => MQ_VHOST);  
$conn = new AMQPConnection($params);  
$conn->connect();
$channel = new AMQPChannel($conn);
$queue = new AMQPQueue($channel);
$queue->setName($queueName);

$crawler = new crawler_tbad();
while ($message = $queue->get(AMQP_AUTOACK)) {
    $kwd = $message->getBody();
    $kwdArr = unserialize($kwd);
    $crawler->run($kwdArr);
    
#    print_r($kwdObj);
#    $price = $detector->run($kwdObj);
#    if ($price['start_price'] && $price['end_price']) {
#        $sql = "SELECT * FROM price WHERE kid = {$kwdObj->id} LIMIT 1";
#        $result = $mysqli->query($sql);    
#        if ($result->num_rows) {
#            $sql = "UPDATE price SET min_price = '{$price['start_price']}', max_price = '{$price['end_price']}', region = '{$price['region']}', crawl_status = 2, last_update = " . time(). " WHERE kid = " . $kwdObj->id;
#        }
#        else {
#            $sql = "INSERT INTO price SET kid = {$kwdObj->id}, shop_type = '{$kwdObj->shop_type}', min_price = '{$price['start_price']}', max_price = '{$price['end_price']}', region = '{$price['region']}', crawl_status = 2, last_update = " . time();
#        }
#        echo $sql . "\n";
#        $mysqli->query($sql);
#    }
}
$conn->disconnect();
exit;


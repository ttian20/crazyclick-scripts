<?php
set_time_limit(0);
date_default_timezone_set('Asia/Shanghai');
require_once dirname(dirname(__FILE__)) . '/bootstrap.php';
require_once LIB_DIR . 'class.keyword.php';
require_once LIB_DIR . 'class.proxy.php';

$totalProcess = 1;
for ($i = 0; $i < $totalProcess; $i++) {
    $pid = pcntl_fork();
    set_time_limit(0);

    if ($pid == -1) {
         die("could not fork\n");
    }
    elseif ($pid) {
         //echo "parent pid is " . posix_getpid() . "\n";
    }
    else {
         //echo "child pid is " . posix_getpid() . "\n";
         sleep(1);
         crawler();
    }
}

//crawler();

function crawler() {
    //rabbitmq
    $queueName = 'q_kwd_test';
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

    //$proxy
    $proxyObj = new proxy();

    //mysql
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWD, DB_NAME);
    $mysqli->query('SET NAMES gbk');
    $today = strtotime(date("Y-m-d"));

    for (;;) {
        $message = $queue->get(AMQP_AUTOACK);
        if (!$message) {
            sleep(5);
            continue;
        }
        else {
            $id = $message->getBody();
            $id = intval($id);
        }

        $hour = date('G');
        $current = time();
        $hms = date('H:i:s');

        $sql = "UPDATE keyword SET clicked_times = clicked_times + 1, last_click_time = {$current} WHERE id = {$id} AND status = 'complete' AND clicked_times < times";
        $mysqli->query($sql);
        $affected_rows = $mysqli->affected_rows;
        echo 'affect ' . $affected_rows . "\n";
        if (!$affected_rows) {
            continue;
        }

        $sql = "SELECT * FROM keyword WHERE id = {$id} LIMIT 1";
        $result = $mysqli->query($sql);
        $data = array();
     	$obj = $result->fetch_object();
    	$result->close();
        print_r($obj);

        $platform = $obj->platform;
        $table = 'keyword_' . $platform;
        $sql = "SELECT * FROM {$table} WHERE kid = {$obj->id} LIMIT 1";
        $result = $mysqli->query($sql);
        $row = $result->fetch_object();
    	$result->close();

        $kwd = urlencode($obj->kwd);
        $nid = $obj->nid;
        $shop_type = $obj->shop_type;
        $date = date('Ymd');
        $sleep_time = $obj->sleep_time;

        $jsfile = JS_DIR . $platform . '.js';
        if ('tbpc' == $platform) {
            echo "tbpc\n";
            $path1 = (int)$row->path1;
            $path2 = $path1 + (int)$row->path2; 
            $path3 = $path2 + (int)$row->path3;
    	    $ua = 'aa';
    	    $keyword = new keyword();

	        $rand = rand(1, 100);
            if ($rand <= $path1) {
                //taobao search
           	    $path = 'taobao';
           	    $data = array(
               		'path' => 'taobao',
               		'kwd' => $kwd,
               		'platform' => $platform,
               		'date' => $date,
               		'region' => $row->path1_region,
               		'price_from' => $row->path1_price_from,
               		'price_to' => $row->path1_price_to,
           	    );
           	    if ($row->path1_page >= 11 || $row->path1_page == -1) {
                    cutback($mysqli, $obj);
           		    continue;
           	    }
        	    $proxy = $proxyObj->getProxy();
        	    $search_url = $keyword->buildSearchUrl($data);
        	    $search_selector = "a[href*='id=".$nid."']";
        	    $next_selector = 'a[trace="srp_bottom_pagedown"]';
        	
        	    $cmd = "/usr/bin/casperjs --output-encoding=gbk --script-encoding=gbk --proxy=".$proxy." " . $jsfile . " \"".$search_url."\" "." \"" . $search_selector . "\" " . "\"" . $next_selector . "\" " . $sleep_time . " \"" . $ua . "\"";
        	}
        	elseif ($rand <= $path2) {
        	    //taobao search tmall tab
        	    $path = 'taobao2tmall';
        	    $data = array(
            		'path' => 'taobao2tmall',
            		'kwd' => $kwd,
            		'platform' => $platform,
            		'date' => $date,
            		'region' => $row->path2_region,
            		'price_from' => $row->path2_price_from,
            		'price_to' => $row->path2_price_to,
        	    );
        	    if ($row->path2_page >= 11 || $row->path2_page == -1) {
            		cutback($mysqli, $obj);
            		continue;
        	    }
        	    $proxy = $proxyObj->getProxy();
        	    $search_url = $keyword->buildSearchUrl($data);
        	    $search_selector = "a[href*='id=".$nid."']";
        	    $next_selector = 'a[trace="srp_bottom_pagedown"]';
        	
        	    $cmd = "/usr/bin/casperjs --output-encoding=gbk --script-encoding=gbk --proxy=".$proxy." " . $jsfile . " \"".$search_url."\" "." \"" . $search_selector . "\" " . "\"" . $next_selector . "\" " . $sleep_time . " \"" . $ua . "\"";
        	}
        	else {
        	    //tmall search
        	    $path = 'tmall';
        	    $data = array(
            		'path' => 'tmall',
            		'kwd' => $kwd,
            		'platform' => $platform,
            		'date' => $date,
            		'region' => $row->path3_region,
            		'price_from' => $row->path3_price_from,
            		'price_to' => $row->path3_price_to,
        	    );
        	    if ($row->path3_page >= 11 || $row->path3_page == -1) {
            		cutback($mysqli, $obj);
            		continue;
        	    }
        	    $proxy = $proxyObj->getProxy(true);
        
        	    $search_url = $keyword->buildSearchUrl($data);
        	    $search_selector = "a[href*='id=".$nid."']";
        	    $next_selector = "a.ui-page-s-next";
        
        	    $cmd = "/usr/bin/casperjs " . $jsfile . " --ignore-ssl-errors=true --proxy=".$proxy." --output-encoding=gbk --script-encoding=gbk \"".$search_url."\" "." \"" . $search_selector . "\" " . "\"" . $next_selector . "\" " . $sleep_time . " \"" . $ua . "\"";
        	}
        }
        elseif ('tbmobi' == $platform) {
        	$path = 'mobi';
        	$data = array(
        	    'kwd' => urlencode(mb_convert_encoding($obj->kwd, 'UTF-8', 'GBK')),
        	    'platform' => $platform,
        	);
        
        	$proxy = $proxyObj->getProxy();
        	$keyword = new keyword();
        	$search_url = $keyword->buildSearchUrl($data);
        	$search_selector = "div.d a[href*='" . $nid . "']";
        	$next_selector = "a.ui-page-s-next";
        
        	$cmd = "/usr/bin/casperjs --proxy=".$proxy." " . $jsfile . " \"".$search_url."\" "." \"" . $search_selector . "\" " . $sleep_time ;
        }
        elseif ('jdpc' == $platform) {
        	$data = array(
        	    'kwd' => urlencode(mb_convert_encoding($obj->kwd, 'UTF-8', 'GBK')),
        	    'platform' => $platform,
        	);
        
        	$ua = 'aa';
        	$keyword = new keyword();
        	$proxy = $proxyObj->getProxy();
        
        	$search_url = $keyword->buildSearchUrl($data);
        	$search_selector = "div.p-name a[href*='" . $nid . "']";
        	$next_selector = "a.next";
        
        	$cmd = "/usr/bin/casperjs " . $jsfile . " --output-encoding=gbk --script-encoding=gbk \"".$search_url."\" "." \"" . $search_selector . "\" " . "\"" . $next_selector . "\" " . $sleep_time . " \"" . $ua . "\"";
        }
        elseif ('tbad' == $platform) {
        	$path1 = (int)$row->path1;
        	$path2 = $path1 + (int)$row->path2;
        	$path3 = $path2 + (int)$row->path3;
        	$title = trim($row->title);
        
        	$ua = 'aa';
        	$keyword = new keyword();
        
        	$rand = rand(1, 100);
        	if ($rand <= $path1) {
        	    //taobao search
        	    $path = 'taobao';
        	    $data = array(
            		'path' => 'taobao',
            		'kwd' => $kwd,
            		'platform' => $platform,
            		'date' => $date,
            		'region' => $row->path1_region,
            		'price_from' => $row->path1_price_from,
            		'price_to' => $row->path1_price_to,
        	    );
        	    if ($row->path1_page >= 11 || $row->path1_page == -1) {
            		cutback($mysqli, $obj);
                    continue;
        	    }
        	    $proxy = $proxyObj->getProxy();
        	    $search_url = $keyword->buildSearchUrl($data);
        	    $search_selector = ".m-p4p a[title='{$title}']";
        	    $next_selector = 'a[trace="srp_bottom_pagedown"]';
        	
        	    $cmd = "/usr/bin/casperjs --output-encoding=gbk --script-encoding=gbk --proxy=".$proxy." " . $jsfile . " \"".$search_url."\" "." \"" . $search_selector . "\" " . "\"" . $next_selector . "\" " . $sleep_time . " \"" . $shop_type . "\"";
        	}
        	elseif ($rand <= $path2) {
        	    //taobao search tmall tab
        	    $path = 'taobao2tmall';
        	    $data = array(
            		'path' => 'taobao2tmall',
            		'kwd' => $kwd,
            		'platform' => $platform,
            		'date' => $date,
            		'region' => $row->path2_region,
            		'price_from' => $row->path2_price_from,
            		'price_to' => $row->path2_price_to,
        	    );
        	    if ($row->path2_page >= 11 || $row->path2_page == -1) {
            		cutback($mysqli, $obj);
            		continue;
        	    }
        	    $proxy = $proxyObj->getProxy();
        	    $search_url = $keyword->buildSearchUrl($data);
        	    $search_selector = ".m-p4p a[title='{$title}']";
        	    $next_selector = 'a[trace="srp_bottom_pagedown"]';
        	
        	    $cmd = "/usr/bin/casperjs --output-encoding=gbk --script-encoding=gbk --proxy=".$proxy." " . $jsfile . " \"".$search_url."\" "." \"" . $search_selector . "\" " . "\"" . $next_selector . "\" " . $sleep_time . " \"" . $shop_type . "\"";
        	}
        	else {
        	    //tmall search
        	    $path = 'tmall';
        	    $data = array(
            		'path' => 'tmall',
            		'kwd' => $kwd,
            		'platform' => $platform,
            		'date' => $date,
            		'region' => $row->path3_region,
            		'price_from' => $row->path3_price_from,
            		'price_to' => $row->path3_price_to,
        	    );
        	    if ($row->path3_page >= 11 || $row->path3_page == -1) {
            		cutback($mysqli, $obj);
            		continue;
        	    }
        	    $proxy = $proxyObj->getProxy(true);
        
        	    $search_url = $keyword->buildSearchUrl($data);
        	    $search_selector = ".m-p4p a[title='{$title}']";
        	    $next_selector = "a.ui-page-s-next";
        
        	    $cmd = "/usr/bin/casperjs " . $jsfile . " --ignore-ssl-errors=true --proxy=".$proxy." --output-encoding=gbk --script-encoding=gbk \"".$search_url."\" "." \"" . $search_selector . "\" " . "\"" . $next_selector . "\" " . $sleep_time . " \"" . $shop_type . "\"";
        	}
        }
    
        echo $cmd . "\n";
        $output = system($cmd);
        $status_code = substr($output, 0, 3);
        if ('200' != $status_code) {
            $sql = "UPDATE keyword SET clicked_times = clicked_times - 1 WHERE id = " . $obj->id . " AND clicked_times > 0";
            $mysqli->query($sql);

            echo $mysqli->error . "\n";
        }

        $sql = "INSERT INTO click_log (kid, path, log, proxy, created_at) VALUES ({$obj->id}, '{$path}', '{$output}', '{$proxy}', " . time(). ")";
        $mysqli->query($sql);
    }
}

function cutback($mysqli, $obj) {
    $sql = "UPDATE keyword SET clicked_times = clicked_times - 1 WHERE id = " . $obj->id . " AND clicked_times > 0"; 
    $mysqli->query($sql); 
}

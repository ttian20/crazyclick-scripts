<?php
set_time_limit(0);
date_default_timezone_set('Asia/Shanghai');
require_once dirname(dirname(__FILE__)) . '/bootstrap.php';
require_once LIB_DIR . 'class.keyword.php';
require_once LIB_DIR . 'class.proxy.php';

$totalProcess = 5;
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
    $proxyObj = new proxy();
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWD, DB_NAME);
    $mysqli->query('SET NAMES gbk');
    $today = strtotime(date("Y-m-d"));

    for (;;) {
        $hour = date('G');
        $current = time();

        $hms = date('H:i:s');
        $sql = "SELECT * FROM keyword "
             . "WHERE status = 'active' AND platform = 'jdpc' AND begin_time <= {$today} AND end_time >= {$today} AND click_start <= '{$hms}' AND click_end > '{$hms}' "
             . "AND clicked_times < times AND ((last_click_time + click_interval) < {$current}) ORDER BY last_click_time ASC LIMIT 1";
        $result = $mysqli->query($sql);
        $data = array();
        if ($result) {
            $obj = $result->fetch_object();
            $result->close();
        }
        
        if (!$obj || !$obj->id) {
            echo "zz\n";
            sleep(5);
            continue ;
        }
        else {

            $sql = "UPDATE keyword SET clicked_times = clicked_times + 1, last_click_time = {$current} WHERE id = {$obj->id} AND clicked_times < times AND ((last_click_time + click_interval) < {$current})";
            //$sql = "UPDATE keyword SET clicked_times = clicked_times + 1, last_click_time = {$current} WHERE id = {$obj->id} AND clicked_times < times";
            $mysqli->query($sql);
            echo 'affect ' . $mysqli->affected_rows . "\n";
            if (!$mysqli->affected_rows) {
                continue;
            }

            $platform = $obj->platform;
            $table = 'keyword_' . $platform;
            $sql = "SELECT * FROM {$table} WHERE kid = {$obj->id} LIMIT 1";
            $result = $mysqli->query($sql);
            $row = $result->fetch_object();

            $kwd = urlencode($obj->kwd);
            $nid = $obj->nid;
            $date = date('Ymd');
            $sleep_time = $obj->sleep_time;

            $jsfile = JS_DIR . $platform . '.js';

            if ('jdpc' == $platform) {
                $ua = 'aa';
                $data = array(
                    'kwd' => urlencode(mb_convert_encoding($obj->kwd, 'UTF-8', 'GBK')),
                    'platform' => $platform,
                );
                $path = 'jdpc';

                $proxy = $proxyObj->getProxy();

                $keyword = new keyword();
                $search_url = $keyword->buildSearchUrl($data);

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
                curl_setopt($ch, CURLOPT_USERAGENT, $userAgent); 
                $info = curl_exec($ch);
                if (curl_errno($ch)) {
                    echo curl_error($ch);
                }
                curl_close($ch);

                $search_selector = "div.p-name a[href*='" . $nid . "']";
                $next_selector = "a.next";
            }
        }
    
        echo $cmd . "\n";
        $output = system($cmd);
        $status_code = substr($output, 0, 3);
        if ('200' != $status_code) {
            /*
            if ('404' == $status_code) {
                $sql = "UPDATE keyword SET is_detected = -2, clicked_times = clicked_times - 1 WHERE id = " . $obj->id;
            }
            else {
                $sql = "UPDATE keyword SET clicked_times = clicked_times - 1 WHERE id = " . $obj->id;
            }
            */
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

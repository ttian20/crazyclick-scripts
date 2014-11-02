<?php
set_time_limit(0);
date_default_timezone_set('Asia/Shanghai');
require_once dirname(dirname(__FILE__)) . '/bootstrap.php';
require_once LIB_DIR . 'class.keyword.php';
require_once LIB_DIR . 'class.proxy.php';

$totalProcess = 50;
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
             . "WHERE status = 'active' AND is_detected = 1 AND begin_time <= {$today} AND end_time >= {$today} AND click_start <= '{$hms}' AND click_end > '{$hms}' "
             . "AND clicked_times < times AND ((last_click_time + click_interval) < {$current}) ORDER BY last_click_time ASC LIMIT 1";
        $result = $mysqli->query($sql);
        $data = array();
        if ($result) {
            $obj = $result->fetch_object();
            $result->close();
        }
        
        //$sql = "UPDATE keyword SET last_click_time = {$current} WHERE id = {$obj->id}";
        //$mysqli->query($sql);

        if (!$obj || !$obj->id) {
            echo "zz\n";
            sleep(1);
            continue ;
        }
        else {

            $sql = "UPDATE keyword SET clicked_times = clicked_times + 1, last_click_time = {$current} WHERE id = {$obj->id} AND clicked_times < times AND ((last_click_time + click_interval) < {$current})";
            $mysqli->query($sql);
            echo 'affect ' . $mysqli->affected_rows . "\n";
            if (!$mysqli->affected_rows) {
                continue;
            }

            /*
            $mysqli->autocommit(0);
            $sql = "SELECT * FROM keyword WHERE id = {$obj->id} AND clicked_times < times AND ((last_click_time + click_interval) < {$current}) FOR UPDATE";
            $result = $mysqli->query($sql);
            $obj = $result->fetch_object();
            if ($obj) {
                $sql = "UPDATE keyword SET last_click_time = {$current} WHERE id = {$obj->id}";
                $mysqli->query($sql);
                $mysqli->commit();
                $mysqli->autocommit(1);
            }
            else {
                $mysqli->rollback();
                $mysqli->autocommit(1);
                sleep(1);
                continue;
            }
            */

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
            if ('tbpc' == $platform) {
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
                    $search_selector = ".row-2 a[href*='id=".$nid."']";
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
                    $search_selector = ".row-2 a[href*='id=".$nid."']";
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
                    $search_selector = ".product[data-id=' " . $nid . "'] div .productTitle a";
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
            $sql = "UPDATE keyword SET clicked_times = clicked_times - 1 WHERE id = " . $obj->id;
            $mysqli->query($sql);
            echo $mysqli->error . "\n";
        }

        $sql = "INSERT INTO click_log (kid, path, log, created_at) VALUES ({$obj->id}, '{$path}', '{$output}', " . time(). ")";
        $mysqli->query($sql);
    }
}

function cutback($mysqli, $obj) {
    $sql = "UPDATE keyword SET clicked_times = clicked_times - 1 WHERE id = " . $obj->id; 
    $mysqli->query($sql); 
}

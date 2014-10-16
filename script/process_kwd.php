<?php
set_time_limit(0);
date_default_timezone_set('Asia/Shanghai');
require_once dirname(dirname(__FILE__)) . '/bootstrap.php';
require_once LIB_DIR . 'class.keyword.php';
require_once LIB_DIR . 'class.proxy.php';
$totalProcess = 50;
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
    $proxyObj = new proxy();
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWD, DB_NAME);
    $mysqli->query('SET NAMES gbk');
    $today = strtotime(date("Y-m-d"));

    for (;;) {
        $hour = date('G');
        $current = time();

        $hms = date('H:i:s');
        $sql = "SELECT * FROM keyword "
             . "WHERE status = 'active' AND begin_time <= {$today} AND end_time >= {$today} AND click_start <= '{$hms}' AND click_end > '{$hms}' "
             . "AND clicked_times < times AND ((last_click_time + click_interval) < {$current}) ORDER BY last_click_time ASC LIMIT 1";
        //echo $sql."\n";
        //exit;
        $result = $mysqli->query($sql);
        $data = array();
        if ($result) {
            $obj = $result->fetch_object();
            $result->close();
            $sql = "UPDATE keyword SET last_click_time = {$current} WHERE id = {$obj->id}";
            $mysqli->query($sql);
        }

        if (!$obj || !$obj->id) {
            echo "zz\n";
            sleep(1);
            continue ;
        }
        else {
            $platform = $obj->platform;
            $table = 'keyword_' . $platform;
            $sql = "SELECT * FROM {$table} WHERE kid = {$obj->id} LIMIT 1";
            echo $sql . "\n";
            $result = $mysqli->query($sql);
            $row = $result->fetch_object();

            $kwd = urlencode($obj->kwd);
            $nid = $obj->nid;
            $date = date('Ymd');
            $sleep_time = $obj->sleep_time;

            if ('tbpc' == $platform) {
                $jsfile = JS_DIR . $platform . '.js';

                $path1 = (int)$row->path1;
                $path2 = $path1 + (int)$row->path2;
                $path3 = $path2 + (int)$row->path3;
    
                $ua = 'aa';
                $keyword = new keyword();
    
                $rand = rand(1, 100);
                if ($rand <= $path1) {
                    //taobao search
                    $data = array(
                        'path' => 'taobao',
                        'kwd' => $kwd,
                        'date' => $date,
                        'region' => $row->path1_region,
                        'price_from' => $row->path1_price_from,
                        'price_to' => $row->path1_price_to,
                    );
                    if ($row->path1_page >= 5) {
                        continue;
                    }
                    $proxy = $proxyObj->getProxy();
                    $search_url = $keyword->buildSearchUrl($data);
                    $search_selector = ".item[nid='" . $nid . "'] h3 a";
                    $next_selector = ".page-next";
                
                    $cmd = "/usr/bin/casperjs --output-encoding=gbk --script-encoding=gbk --proxy=".$proxy." " . $jsfile . " \"".$search_url."\" "." \"" . $search_selector . "\" " . "\"" . $next_selector . "\" " . $sleep_time . " \"" . $ua . "\"";
                }
                elseif ($rand <= $path2) {
                    //taobao search tmall tab
                    $data = array(
                        'path' => 'taobao2tmall',
                        'kwd' => $kwd,
                        'date' => $date,
                        'region' => $row->path2_region,
                        'price_from' => $row->path2_price_from,
                        'price_to' => $row->path2_price_to,
                    );
                    if ($row->path2_page >= 5) {
                        continue;
                    }
                    $proxy = $proxyObj->getProxy();
                    $search_url = $keyword->buildSearchUrl($data);
                    $search_selector = ".item[nid='" . $nid . "'] h3 a";
                    $next_selector = ".page-next";
                
                    $cmd = "/usr/bin/casperjs --output-encoding=gbk --script-encoding=gbk --proxy=".$proxy." " . $jsfile . " \"".$search_url."\" "." \"" . $search_selector . "\" " . "\"" . $next_selector . "\" " . $sleep_time . " \"" . $ua . "\"";
                }
                else {
                    //tmall search
                    $data = array(
                        'path' => 'tmall',
                        'kwd' => $kwd,
                        'date' => $date,
                        'region' => $row->path3_region,
                        'price_from' => $row->path3_price_from,
                        'price_to' => $row->path3_price_to,
                    );
                    if ($row->path3_page >= 5) {
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

            }


        }
    
        echo $cmd . "\n";
        system($cmd);
        $sql = "UPDATE keyword SET clicked_times = clicked_times + 1 WHERE id = " . $obj->id;
        $mysqli->query($sql);
        echo $mysqli->error . "\n";
    }
}

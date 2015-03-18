<?php
require_once dirname(__FILE__) . '/class.curl.php';
require_once dirname(__FILE__) . '/class.keyword.php';
require_once dirname(__FILE__) . '/class.proxy_redis.php';

class crawler_jdpc {
    public $proxy = null;
    public $kwd = null;
    public $nid = null;
    public $userAgent = null;
    public $kwdObj = null;
    public $db = null;
    public function __construct() {
        $this->kwdObj = new keyword();
        $this->db = new mysqli(DB_HOST, DB_USER, DB_PASSWD, DB_NAME);
        $this->db->query('SET NAMES gbk');
    }

    public function run($data) {
        //处理price范围
        $priceStr = explode(".", $data['price']);
        if ($priceStr[1] = '00') {
            $data['price_from'] = $priceStr[0];
            $data['price_to'] = $priceStr[0] + 10;
        }
        else {
            $data['price_from'] = floor($priceStr[0]);
            $data['price_to'] = floor($priceStr[0]) + 10;
        }
        $data['date'] = date('Ymd');
        $data['kwd'] = urlencode(mb_convert_encoding($data['kwd'], 'UTF-8', 'GBK'));

        $this->nid = $data['nid'];

//        $proxyObj = new proxy();
//        $this->proxy = $proxyObj->getProxy();

        $tmpdata = $data;
        unset($tmpdata['price_from']);
        unset($tmpdata['price_to']);
        unset($tmpdata['region']);
        $url = $this->kwdObj->buildSearchUrl($tmpdata);
        $page = (int)$this->getJdPage($url, $data['nid']);
        $this->update($tmpdata, $page);
        $minPage = $page;
        $selected = $tmpdata;
        sleep(1);

        //价格作为搜索条件
        $tmpdata = $data;
        unset($tmpdata['region']);
        $url = $this->kwdObj->buildSearchUrl($tmpdata);
        $page = (int)$this->getJdPage($url, $data['nid']);
        $this->update($tmpdata, $page);
        if ($minPage == -1 && $page > 0) {
            $minPage = $page;
            $selected = $tmpdata;
        }
        if ($page > 0 && $page < $minPage) {
            $minPage = $page;
            $selected = $tmpdata;
        }

        //specify the search condition
        $this->specify($selected, $minPage);
    }

    public function getJdPage($url, $nid) {
        $search_selector = "div.p-name a[href*='" . $nid . "']";
        $next_selector = "a.next";
        $jsfile = JS_DIR . 'jd.js';
    
        //$cmd = "/usr/bin/casperjs " . $jsfile . " --proxy=".$this->proxy." --output-encoding=gbk --script-encoding=gbk \"".$url."\" "." \"" . $search_selector . "\" " . "\"" . $next_selector . "\"";
        $cmd = "/usr/bin/casperjs " . $jsfile . " --output-encoding=gbk --script-encoding=gbk \"".$url."\" "." \"" . $search_selector . "\" " . "\"" . $next_selector . "\"";
        $output = system($cmd);
        echo "output\n";
        echo $output . "\n";

        $len = strlen($output);
        if ($len < 3 && $len > 0) {
            return $output;
        }
        else {
            return -1;
        }
    }

    public function getUserAgent() {
        $data = array(
            'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_8; en-us) AppleWebKit/534.50 (KHTML, like Gecko) Version/5.1 Safari/534.50',
            'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-us) AppleWebKit/534.50 (KHTML, like Gecko) Version/5.1 Safari/534.50',
            'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)',
            'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0)',
            'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0)',
            'Mozilla/5.0 (Windows NT 5.1; rv:31.0) Gecko/20100101 Firefox/31.0',
            'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/35.0.1916.153 Safari/537.36',
            'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; .NET CLR 2.0.50727; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729)',
            'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; 360SE)',
        );
        $count = count($data);
        $rand = rand(0, $count - 1);
        return $data[$rand]; 
    }

    public function update($data, $page) {
        $upData = array();
        $upData['page'] = $page;
        $upData['kid'] = $data['id'];
        $upData['platform'] = $data['platform'];
        $upData['path'] = 'path';
        $upData['create_time'] = time();
        $upData['update_time'] = time();
        if ($data['price_from']) {
            $upData['price_from'] = $data['price_from'];
        }
        if ($data['price_to']) {
            $upData['price_to'] = $data['price_to'];
        }

        $sqlArr = array();
        foreach ($upData as $k => $v) {
            $sqlArr[] = $k . " = '" . $v . "'"; 
        }
        $sqlStr = implode(',', $sqlArr);
        $sql = "INSERT INTO pages SET " . $sqlStr;
        echo $sql . "\n";

        $this->db->query($sql);
    }

    public function specify($data, $page) {
        $upData = array();

        $upData['page'] = $page;

        $sqlArr = array();
        foreach ($upData as $k => $v) {
            $sqlArr[] = $k . " = '" . $v . "'"; 
        }
        $sqlStr = implode(',', $sqlArr);
        $sql = "UPDATE keyword_{$data['platform']} SET " . $sqlStr . " WHERE kid = {$data['id']}";
        echo $sql . "\n";
        $this->db->query($sql);
        if ($page != -1) {
            $sql = "UPDATE keyword SET is_detected = 1 WHERE id = {$data['id']}";
            echo $sql . "\n";
            $this->db->query($sql);
        }
        else {
            $sql = "UPDATE keyword SET detect_times = detect_times + 1 WHERE id = {$data['id']}";
            echo $sql . "\n";
            $this->db->query($sql);
        }
    }
}

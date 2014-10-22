<?php
require_once dirname(__FILE__) . '/class.curl.php';
require_once dirname(__FILE__) . '/class.keyword.php';

class crawler {
    public $proxy = null;
    public $kwd = null;
    public $nid = null;
    public $userAgent = null;
    public $kwdObj = null;
    public $taobaoSearchBaseUrl = 'http://s.taobao.com/';
    public $tmallSearchBaseUrl = 'http://list.tmall.com/search_product.htm';
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
            $data['price_to'] = $priceStr[0] + 1;
        }
        else {
            $data['price_from'] = floor($priceStr[0]);
            $data['price_to'] = floor($priceStr[0]) + 1;
        }
        $data['date'] = date('Ymd');
        $data['kwd'] = urlencode($data['kwd']);

        $this->nid = $data['nid'];
        $selected = array();

        if ($data['path'] == 'tmall') {
            //2种条件搜索
            //1. 无附加搜索条件
            //2. 单纯价格作搜索条件
            $proxyObj = new proxy();
            $this->proxy = $proxyObj->getProxy(true);

            //无附加搜索条件
            $tmpdata = $data;
            unset($tmpdata['price_from']);
            unset($tmpdata['price_to']);
            unset($tmpdata['region']);
            $url = $this->kwdObj->buildSearchUrl($tmpdata);
            $page = $this->getTmallPage($url);
            $this->update($tmpdata, $page);
            $minPage = $page;
            $selected = $tmpdata;
            sleep(1);

            //单纯价格作搜索条件
            $tmpdata = $data;
            unset($tmpdata['region']);
            $url = $this->kwdObj->buildSearchUrl($tmpdata);
            $page = $this->getTmallPage($url);
            $this->update($tmpdata, $page);
            if ($minPage == -1 && $page > 0) {
                $minPage = $page;
                $selected = $tmpdata;
            }
            if ($page > 0 && $page < $minPage) {
                $minPage = $page;
                $selected = $tmpdata;
            }
            $this->specify($selected, $minPage);
        }
        else {
            //4种条件搜索
            //1. 无附加搜索条件
            //2. 单纯价格作搜索条件
            //3. 单纯地区作搜索条件
            //4  地区和价格同时作搜索条件
            $proxyObj = new proxy();
            $this->proxy = $proxyObj->getProxy();

            //无附加搜索条件
            $tmpdata = $data;
            unset($tmpdata['price_from']);
            unset($tmpdata['price_to']);
            unset($tmpdata['region']);
            $url = $this->kwdObj->buildSearchUrl($tmpdata);
            $page = $this->getPage($url);
            $this->update($tmpdata, $page);
            //print_r($tmpdata);
            //echo $page."\n";
            $minPage = $page;
            $selected = $tmpdata;
            sleep(1);

            //单纯价格作搜索条件
            $tmpdata = $data;
            unset($tmpdata['region']);
            $url = $this->kwdObj->buildSearchUrl($tmpdata);
            $page = $this->getPage($url);
            $this->update($tmpdata, $page);
            //print_r($tmpdata);
            //echo $page."\n";
            if ($minPage == -1 && $page > 0) {
                $minPage = $page;
                $selected = $tmpdata;
            }
            if ($page > 0 && $page < $minPage) {
                $minPage = $page;
                $selected = $tmpdata;
            }
            sleep(1);

            //单纯地区作搜索条件
            $tmpdata = $data;
            unset($tmpdata['price_from']);
            unset($tmpdata['price_to']);
            $url = $this->kwdObj->buildSearchUrl($tmpdata);
            $page = $this->getPage($url);
            $this->update($tmpdata, $page);
            //print_r($tmpdata);
            //echo $page."\n";
            if ($minPage == -1 && $page > 0) {
                $minPage = $page;
                $selected = $tmpdata;
            }
            if ($page > 0 && $page < $minPage) {
                $minPage = $page;
                $selected = $tmpdata;
            }
            sleep(1);

            //地区和价格同时作搜索条件
            $tmpdata = $data;
            $url = $this->kwdObj->buildSearchUrl($tmpdata);
            $page = $this->getPage($url);
            $this->update($tmpdata, $page);
            if ($minPage == -1 && $page > 0) {
                $minPage = $page;
                $selected = $tmpdata;
            }
            if ($page > 0 && $page < $minPage) {
                $minPage = $page;
                $selected = $tmpdata;
            }
            //print_r($tmpdata);
            //echo $page."\n";

            //specify the search condition
            $this->specify($selected, $minPage);
            sleep(1);
        }
    }

    public function getPage($url, $i = 1) {
        $curl = new Curl(); 
        echo $url . "\n";
        $curl->get($url, array(), $this->proxy);
        $curl->setUserAgent($this->getUserAgent());
        echo $curl->http_status_code . "\n";
        if (200 == $curl->http_status_code) {
            $body = $curl->response;
            $findme = 'nid="' . $this->nid . '"';
            //echo $findme . "\n";
            //var_dump(strpos($body, $findme));
            //echo "\n";
            if (strpos($body, $findme)) {
                return $i;
            }
            else {
                if ($i >= 20) {
                    return -1;
                }
                //sleep();
                //$begin = microtime(true);
                $nextPagePattern = "/<\/a><a href=\"\/(.*?)\"  class=\"page-next\" trace='srp_select_pagedown'>/i";
                #$nextPagePattern = "/<a href=\"\/([_-=\.\?%&a-z0-9]+?)\"  class=\"page-next\" trace='srp_select_pagedown'>/i";
                preg_match_all($nextPagePattern, $body, $match);
                //$end = microtime(true);
                //echo "cost time: " . ($end - $begin);  
                //echo "\n";
                //echo strpos($body, 'page-next');
                //echo $body;
                if (!$match[1][0]) {
                    //print_r($match);
                    //echo $body . "\n";
                    return -1;
                }
                $url = $this->taobaoSearchBaseUrl . $match[1][0];
                $sleepSecond = rand(2, 4);
                sleep($sleepSecond);
                $i++;
                echo $i . " not found\n";
                return $this->getPage($url, $i);
            }
        }
        else {
            echo $curl->response . "\n";
            print_r($curl->response_headers);
            return -1;
        }
    }

    public function getTmallPage($url, $i = 1) {
        echo $url . "\n";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($ch, CURLOPT_USERAGENT, $this->getUserAgent()); 
        curl_setopt($ch, CURLOPT_PROXY, $this->proxy); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        curl_setopt($ch, CURLOPT_TIMEOUT, 20); 
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
        $info = curl_exec($ch);
        if(curl_errno($ch))
        {
            echo curl_error($ch);
            $proxyObj = new proxy();
            $this->proxy = $proxyObj->getProxy(true);
            return $this->getTmallPage($url, $i);
        }


        $body = $info;
        $findme = 'data-id=" ' . $this->nid . '"';
        if (strpos($body, $findme)) {
            return $i;
        }
        else {
            if ($i >= 20) {
                return -1;
            }
            $nextPagePattern = "/<a href=\"(.*?)\" class=\"ui-page-s-next\" atpanel/i";
            preg_match_all($nextPagePattern, $body, $match);
            if (!$match[1][0]) {
                //print_r($match);
                return -1;
            }
            $url = $this->tmallSearchBaseUrl . html_entity_decode($match[1][0]);
            $i++;
            echo $i . " not found\n";
            return $this->getTmallPage($url, $i);
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
        switch ($data['path']) {
            case 'taobao':
                $path = 'path1';
                break;
            case 'taobao2tmall':
                $path = 'path2';
                break;
            case 'tmall':
                $path = 'path3';
                break;
        }

        $upData = array();
        if (isset($data['region'])) {
            $upData['region'] = $data['region']; 
        }
        if (isset($data['price_from'])) {
            $upData['price_from'] = $data['price_from']; 
        }
        if (isset($data['price_to'])) {
            $upData['price_to'] = $data['price_to']; 
        }

        $upData['page'] = $page;
        $upData['kid'] = $data['id'];
        $upData['platform'] = $data['platform'];
        $upData['path'] = $path;
        $upData['create_time'] = time();
        $upData['update_time'] = time();

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
        switch ($data['path']) {
            case 'taobao':
                $path = 'path1';
                break;
            case 'taobao2tmall':
                $path = 'path2';
                break;
            case 'tmall':
                $path = 'path3';
                break;
        }

        $upData = array();
        if (isset($data['region'])) {
            $upData[$path . '_region'] = $data['region']; 
        }
        if (isset($data['price_from'])) {
            $upData[$path . '_price_from'] = $data['price_from']; 
        }
        if (isset($data['price_to'])) {
            $upData[$path . '_price_to'] = $data['price_to']; 
        }
        $upData[$path . '_page'] = $page;

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
    }
}

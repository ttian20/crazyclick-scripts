<?php
class proxy {
    public function __construct() {

    }

    public function publishProxy($https = false) {
        if ($https) {
            $keyList = 'proxy_list_https';
            $keySet = 'proxy_set_https';
            $keyMax = 'max_index_https';
            $keyProxyTimes = 'proxy_times_https';
            $buffer = 700;
            $size = 200;
            $sleep = 2;
            //$url = 'http://www.kuaidaili.com/api/getproxy/?orderid=982669190774114&num='.$size.'&area=%E4%B8%AD%E5%9B%BD&browser=1&protocol=2&method=1&an_ha=1&sp1=1&sp2=1&sort=0&dedup=1&format=text&sep=2';
            $baseurl = 'http://www.tkdaili.com/api/getiplist.aspx?vkey=2C777C9751352F3D8C99355ED68252A2&num='.$size.'&country=CN&high=1&style=2&https=1&filter=';
        }
        else {
            $keyList = 'proxy_list';
            $keySet = 'proxy_set';
            $keyMax = 'max_index';
            $keyProxyTimes = 'proxy_times_http';
            $buffer = 500;
            $size = 50;
            $sleep = 2;
            //$baseurl = 'http://www.kuaidaili.com/api/getproxy/?orderid=982669190774114&num='.$size.'&browser=1&protocol=1&method=1&an_ha=1&sort=2&dedup=1&format=text&sep=2&area=';
            $baseurl = 'http://www.tkdaili.com/api/getiplist.aspx?vkey=2C777C9751352F3D8C99355ED68252A2&num='.$size.'&country=CN&high=1&style=2&filter=';
            //$baseurl = 'http://src.06116.com/query.txt?min=30&count=' . $size . '&word=';
        }

        $redis = new Redis();
        $redis->connect(REDIS_HOST, REDIS_PORT);
        $redis->select(0);
        if ($redis->exists($keyList) && $redis->exists($keyMax) && (($redis->lLen($keyList) - $redis->get($keyMax)) >= $buffer)) {
            $redis->close();
            return ;
        }

        $userAgent = 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; InfoPath.2; .NET4.0C; .NET4.0E)';
        $times = ceil($buffer / $size);
        $proxys = array();
        for ($i = 0; $i < $times; $i++) {
            $proxyTimes = $redis->incr($keyProxyTimes);
            $province = $this->getProvince($proxyTimes);
            $url = $baseurl . $province;
            /*
            //if ($province == '%E5%8C%97%E4%BA%AC') {
            if (in_array($province, array('%E5%8C%97%E4%BA%AC', '%E6%B1%9F%E8%A5%BF', '%E5%9B%9B%E5%B7%9D'))) {
            //if (in_array($province, array('%E6%B1%9F%E8%A5%BF', '%E5%9B%9B%E5%B7%9D'))) {
                $url .= '&port=8123&vport=1';
            }
            */
            echo $url . "\n";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
            curl_setopt($ch, CURLOPT_USERAGENT, $userAgent); 
            $info = curl_exec($ch);
            if (curl_errno($ch)) {
                echo curl_error($ch);
            }
            curl_close($ch);
            $content = trim($info);
            /*
            if (strpos($content, '117')) {
                preg_match_all("/117/", $content, $matches);
                error_log($url . "\n", 3, "/tmp/ip.log");
                error_log($content . "\n", 3, "/tmp/ip.log");
            }
            */
            if (!$https) {
                //$content = substr($content, 3);
                //$arr = explode("\r\n", $content);
                $arr = explode("\n", $content);
            }
            else {
                $arr = explode("\n", $content);
            }
            //echo iconv('UTF-8', 'GBK', $content) . "\n";
            //echo $content."\n";
            //$arr = explode("\n", $content);
            echo count($arr) . "\n";
            if (count($arr) < 3) {
                echo "no proxy" . " available\n";
                //$redis->close();
                //return false;
                continue;
            }

            foreach ($arr as $proxy) {
                $proxy = trim($proxy);
                $proxys[] = $proxy;
                //echo $proxy . "\n";

            }
            sleep($sleep);
        }
        if ($proxys) {
            shuffle($proxys);
            foreach ($proxys as $proxy) {
                if (!$redis->sIsMember($keySet, $proxy)) {
                    $redis->sAdd($keySet, $proxy);
                    $redis->rPush($keyList, $proxy);
                }
            }
        }


        $redis->close();
    }

    public function getProxy($shopId, $https = false) {
        if (!$shopId) {
            $shopId = '000000';
        }

        if ($https) {
            $keyList = 'proxy_list_https';
            $keyShop = 'shop_' . $shopId . '_https';
            $keyMax = 'max_index_https';
        }
        else {
            $keyList = 'proxy_list';
            $keyShop = 'shop_' . $shopId;
            $keyMax = 'max_index';
        }

        $redis = new Redis();
        $redis->connect(REDIS_HOST, REDIS_PORT);
        $redis->select(0);

        $index = $redis->exists($keyShop) ? $redis->get($keyShop) : 0;
        $total = $redis->lLen($keyList);
        $proxy = '';

        while ($index < $total) {
            $proxy = $redis->lIndex($keyList, $index);
            if (!$this->_testProxy($proxy, $https)) {
                $index = $redis->incr($keyShop);
                $total = $redis->lLen($keyList);
                $proxy = '';
                continue ;
            }
            else {
                $index = $redis->incr($keyShop);
                break;
            }
        }

        $maxIndex = $redis->get($keyMax);
        if ($index > $maxIndex) {
            $redis->set($keyMax, $index);
        }
        
        //$proxyKey = 'status_' . $proxy;
        $redis->close();
        return $proxy;
    }

    public function _testProxy($proxy, $https = false) {
        echo "test " . $proxy . "\n";
        if ($https) {
            $url = 'https://login.taobao.com/member/login.jhtml';
            $timeout = 6;
        }
        else {
            $url = 'http://www.taobao.com?spm=1.7274553.1997517345.1.7V4oN5';
            $timeout = 6;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_PROXY, $proxy);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_8; en-us) AppleWebKit/534.50 (KHTML, like Gecko) Version/5.1 Safari/534.50');
        if ($https) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        
        curl_exec($ch);
        $info = curl_getinfo($ch);
        //if (curl_errno($ch) || $info['http_code'] != '200') {
        if (curl_errno($ch)) {
            echo curl_error($ch) . "\n";
            curl_close($ch);
            return false;
        }
        else {
            curl_close($ch);
            if ($https) {
                $res = $this->_testProxyRedirect($proxy);
                if ($res) {
                    return true;
                }
                else {
                    return false;
                }
            }
            return true;
        }
    }

    public function _testProxyRedirect($proxy) {
        $url = 'http://admin.aymoo.com/admin/test/proxy';
        $timeout = 5;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_PROXY, $proxy);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_8; en-us) AppleWebKit/534.50 (KHTML, like Gecko) Version/5.1 Safari/534.50');
        $res = curl_exec($ch);

        $proxyArr = explode(':', $proxy);
        $resArr = explode(':', trim($res));
        echo "proxy is ";
        var_dump($proxyArr[0]);
        echo "\n";
        echo "return is ";
        var_dump($resArr[0]);
        echo "\n";
        curl_close($ch);

        if ($proxyArr[0] == $resArr[0]) {
            return true;
        }
        else {
            return false;
        }
    }

    public function getTotalNum() {
        $params = array('host' =>'localhost',  
                        'port' => 5672,  
                        'login' => 'guest',  
                        'password' => 'guest',  
                        'vhost' => '/kwd');  
        $conn = new AMQPConnection($params);  
        $conn->connect();
        $channel = new AMQPChannel($conn);
        $queue = new AMQPQueue($channel);
        $queue->setName('q_proxy');
        $queue->setFlags(AMQP_PASSIVE);
        $messageCount = $queue->declare();
        return $messageCount;
    }

    public function test() {
        $params = array('host' =>'localhost',  
                        'port' => 5672,  
                        'login' => 'guest',  
                        'password' => 'guest',  
                        'vhost' => '/kwd');  
        $conn = new AMQPConnection($params);  
        $conn->connect();
        $channel = new AMQPChannel($conn);
        $exchange = new AMQPExchange($channel);
        $exchange->setName('e_proxy');
        $exchange->publish($message, 'proxy');
    }

    public function getProvince($i = 0) {
        $provinces = array(
            '上海',
            '北京',
            '天津',
            '重庆',
            '安徽',
            '福建',
            '甘肃',
            '广东',
            '广西',
            '贵州',
            '海南',
            '河北',
            '河南',
            '湖北',
            '湖南',
            '江苏',
            '江西',
            '吉林',
            '辽宁',
            '宁夏',
            '青海',
            '山东',
            '山西',
            '陕西',
            '云南',
            '四川',
            '西藏',
            '新疆',
            '浙江',
            '内蒙古',
            '黑龙江',
        );
        $index = $i % count($provinces);
        echo $index . "\n";
        echo $provinces[$index] . "\n";
        return urlencode(mb_convert_encoding($provinces[$index], 'UTF-8', 'GBK'));
    }
}

<?php
class proxy {
    public function __construct() {

    }

    public function publishProxy($https = false) {
        if ($https) {
            $keyList = 'proxy_list_https';
            $keySet = 'proxy_set_https';
            $keyMax = 'max_index_https';
            $buffer = 700;
            $size = 200;
            $sleep = 5;
            $url = 'http://www.kuaidaili.com/api/getproxy/?orderid=902587087393360&num='.$size.'&area=%E4%B8%AD%E5%9B%BD&browser=1&protocol=2&method=1&an_ha=1&sp2=1&sort=0&format=text&sep=2';
            echo $url . "\n";
        }
        else {
            $keyList = 'proxy_list';
            $keySet = 'proxy_set';
            $keyMax = 'max_index';
            $buffer = 700;
            $size = 200;
            $sleep = 2;
            $url = 'http://www.tkdaili.com/api/getiplist.aspx?vkey=2C777C9751352F3D8C99355ED68252A2&num='.$size.'&country=CN&high=1&style=2';
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
        for ($i = 0; $i < $times; $i++) {
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
            $arr = explode("\n", $content);
            if (count($arr) < 10) {
                echo "no proxy" . " available\n";
                $redis->close();
                return false;
            }

            foreach ($arr as $proxy) {
                $proxy = trim($proxy);
                if (!$redis->sIsMember($keySet, $proxy)) {
                    $redis->sAdd($keySet, $proxy);
                    $redis->rPush($keyList, $proxy);
                }
            }
            sleep(6);
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

        $index = $redis->incr($keyShop);
        $proxy = $redis->lIndex($keyList, $index);
        
        while (!$this->_testProxy($proxy, $https)) {
            $index = $redis->incr($keyShop);
            $proxy = $redis->lIndex($keyList, $index);
        }

        $maxIndex = $redis->get($keyMax);
        if ($index > $maxIndex) {
            $redis->set($keyMax, $index);
        }
      
        $redis->close();
        return $proxy;
    }

    public function _testProxy($proxy, $https = false) {
        echo "test " . $proxy . "\n";
        if ($https) {
            $url = 'https://login.taobao.com/member/login.jhtml';
            $timeout = 5;
        }
        else {
            $url = 'http://www.taobao.com?spm=1.7274553.1997517345.1.7V4oN5';
            $timeout = 3;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_PROXY, $proxy);
        if ($https) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        
        $info = curl_exec($ch);
        if(curl_errno($ch))
        {
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

        $params = array('host' =>'10.168.45.191',  
                        'port' => 5672,  
                        'login' => 'guest',  
                        'password' => 'guest',  
                        'vhost' => '/kwd');  

        $exchangeName = 'e_proxy';
        $queueName = 'q_proxy';
        $routerName = 'proxy';

        $conn = new AMQPConnection($params);  
        $conn->connect();
        $channel = new AMQPChannel($conn);
        $exchange = new AMQPExchange($channel);
        $exchange->setName($exchangeName);
        $rs = $exchange->publish(trim($proxy), $routerName);
        $res = $rs ? '1' : '0'; 
//        error_log($rs . "\n", 3, '/var/html/production/logs/proxy.log');
//        error_log($proxy . "\n", 3, '/var/html/production/logs/proxy.log');

        $conn->disconnect();

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
}

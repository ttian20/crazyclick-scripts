<?php
class proxy {
    public function __construct() {

    }

    public function publishProxy($https = false) {
        if ($https) {
            $exchangeName = 'e_proxy_https';
            $queueName = 'q_proxy_https';
            $routerName = 'proxy_https';
            $max = 2500;
            $size = 200;
            $url = 'http://www.tkdaili.com/api/getiplist.aspx?vkey=2C777C9751352F3D8C99355ED68252A2&num='.$size.'&country=CN&high=1&https=1&style=2';
        }
        else {
            $exchangeName = 'e_proxy';
            $queueName = 'q_proxy';
            $routerName = 'proxy';
            $max = 4000;
            $size = 200;
            $url = 'http://www.tkdaili.com/api/getiplist.aspx?vkey=2C777C9751352F3D8C99355ED68252A2&num='.$size.'&country=CN&high=1&style=2';
        }

        $params = array('host' =>'10.168.45.191',  
                        'port' => 5672,  
                        'login' => 'guest',  
                        'password' => 'guest',  
                        'vhost' => '/kwd');  

        $conn = new AMQPConnection($params);  
        $conn->connect();
        $channel = new AMQPChannel($conn);
        $exchange = new AMQPExchange($channel);
        $exchange->setName($exchangeName);

        $queue = new AMQPQueue($channel);
        $queue->setFlags(AMQP_PASSIVE);
        $queue->setName($queueName);
        $messageCount = $queue->declareQueue();
        if ($messageCount >= $max) {
            echo $messageCount . "\n";
            return true;
        }

        $times = ceil(($max - $messageCount) / $size);
        //echo $max . "\n";
        //echo $messageCount . "\n";
        //echo $times . "\n";

        $userAgent = 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; InfoPath.2; .NET4.0C; .NET4.0E)';
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
                $conn->disconnect();
                return false;
            }

            foreach ($arr as $proxy) {
                $exchange->publish(trim($proxy), $routerName);
            }
        }
        $conn->disconnect();
    }

    public function getProxy($https = false) {
        if ($https) {
            $queueName = 'q_proxy_https';
        }
        else {
            $queueName = 'q_proxy';
        }

        $params = array('host' =>'10.168.45.191',  
                        'port' => 5672,  
                        'login' => 'guest',  
                        'password' => 'guest',  
                        'vhost' => '/kwd');  
        $conn = new AMQPConnection($params);  
        $conn->connect();
        $channel = new AMQPChannel($conn);
        $queue = new AMQPQueue($channel);
        $queue->setName($queueName);
        $message = $queue->get(AMQP_AUTOACK);
        $proxy = $message->getBody();
        
        while (!$this->_testProxy($proxy, $https)) {
            $message = $queue->get(AMQP_AUTOACK);
            $proxy = $message->getBody();
        }
      
        $conn->disconnect();
        return $proxy;
    }

    public function _testProxy($proxy, $https = false) {
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
        curl_close($ch);
        if ($proxy == trim($res)) {
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

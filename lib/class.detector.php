<?php
require_once dirname(__FILE__) . '/class.proxy.php';
class detector {
    public function __construct() {

    }

    public function run($obj) {
        if ('b' == $obj->shop_type) {
            $prices = $this->getTmallPrice($obj); 
        }
        else {
            $prices = $this->getTaobaoPrice($obj); 
        }
        return $prices;
    }

    public function getTmallPrice($kwd) {
        //$proxyObj = new proxy();
        //$proxy = $proxyObj->getProxy(true);

        $url = 'http://detail.tmall.com/item.htm?id=' . $kwd->nid;
        $ch = curl_init();
        $user_agent = $this->getUserAgent();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
        //curl_setopt($ch, CURLOPT_PROXY, $proxy);
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        $info = curl_exec($ch);
        curl_close($ch);
        $content = trim($info);
        
        $pattern = "/var l,url='(.*?)';/";
        preg_match_all($pattern, $content, $matches);
        $detail_url = $matches[1][0];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $detail_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
        //curl_setopt($ch, CURLOPT_PROXY, $proxy);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        $info = curl_exec($ch);
        curl_close($ch);
        $content = trim($info);
        $price_pattern = '/"price":"([.0-9]+?)",/';
        preg_match_all($price_pattern, $content, $matches);
        if ($matches) {
            foreach ($matches[1] as $k => $p) {
                if ($k == 0) {
                    $start_price = $end_price = $p;
                }
                else {
                    if (bccomp($p, $start_price) == -1) {
                        if ($p != '0.00') {
                            $start_price = $p;
                        }
                    }
                    if (bccomp($p, $end_price) == 1) {
                        $end_price = $p;
                    }
                }
            }
        }
        $region_pattern = '/"deliveryAddress":"(.*?)",/';
        preg_match_all($region_pattern, $content, $matches);
        $region = '';
        if ($matches) {
            $region = $matches[1][0];
            $provinces = $this->getProvinces();
            $region = str_replace($provinces, '', $region);
        }
        
        return array('start_price' => $start_price, 'end_price' => $end_price, 'region' => $region);
    }

    public function getTaobaoPrice($kwd) {
        //$proxyObj = new proxy();
        //$proxy = $proxyObj->getProxy();
        $url = 'http://item.taobao.com/item.htm?id=' . $kwd->nid;
        $user_agent = $this->getUserAgent();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        //curl_setopt($ch, CURLOPT_PROXY, $proxy);
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        $info = curl_exec($ch);
        curl_close($ch);
        $content = trim($info);
        
        $end_price_pattern = '/price:([.0-9]+?),/';
        preg_match_all($end_price_pattern, $content, $matches);
        $end_price = (string)$matches[1][0];
        $start_price = $end_price;
        
        $pattern = '/var b="(.*?)",/';
        preg_match_all($pattern, $content, $matches);
        $detail_url = $matches[1][0];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $detail_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        //curl_setopt($ch, CURLOPT_PROXY, $proxy);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        $info = curl_exec($ch);
        curl_close($ch);
        $content = trim($info);
        $pattern = '/price:"([.0-9]+?)",/';
        preg_match_all($pattern, $content, $matches);
        if ($matches) {
            foreach ($matches[1] as $p) {
                if (bccomp($p, $start_price) == -1) {
                    if ($p != '0.00') {
                        $start_price = $p;
                    }
                }
            }
        }
        $region_pattern = "/location:'(.*?)',/";
        preg_match_all($region_pattern, $content, $matches);
        $region = '';
        if ($matches) {
            $region = $matches[1][0];
            $provinces = $this->getProvinces();
            $region = str_replace($provinces, '', $region);
        }
        return array('start_price' => $start_price, 'end_price' => $end_price, 'region' => $region);
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

    public function getProvinces() {
        $provinces = array(
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
            '澳门',
            '香港',
            '台湾',
            '内蒙古',
            '黑龙江',
        );
        return $provinces;
    }
}

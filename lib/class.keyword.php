<?php
class keyword {
    public function __construct() {

    }

    public function buildSearchUrl($data) {
        $kwd = $data['kwd'];
        $region = iconv("GBK", "UTF-8", $data['region']);
        if (in_array($data['platform'], array('tbpc', 'tbad'))) {
            $date = $data['date'];
            switch ($data['path']) {
                case 'taobao':
                    //$search_url = 'https://s.taobao.com/search?&initiative_id=tbindexz_'.$date.'&spm=1.7274553.1997520841.1&sourceId=tb.index&search_type=item&ssid=s5-e&commend=all&q='.$kwd.'&suggest=0_2';
                    $search_url = 'https://s.taobao.com/search?q='.$kwd.'&imgfile=&commend=all&ssid=s5-e&search_type=item&sourceId=tb.index&spm=a21bo.7724922.8452-taobao-item.1&ie=utf8&initiative_id=tbindexz_' . $date;
                    if ($data['region']) {
                        $search_url .= '&loc=' . urlencode($region);
                    } 
                    if ($data['price_from'] || $data['price_to']) {
                        $price_from = $data['price_from'] ? $data['price_from'] : '';
                        $price_to = $data['price_to'] ? $data['price_to'] : '';
                        $search_url .= '&filter=reserve_price' . urlencode("[".$price_from.",".$price_to."]");
                    }
                    break;
                case 'taobao2tmall':
                    //$search_url = 'https://s.taobao.com/search?spm=a230r.1.0.0.9nMSJu&initiative_id=tbindexz_'.$date.'&tab=mall&q='.$kwd.'&suggest=0_2'; 
                    $search_url = 'https://s.taobao.com/search?spm=a230r.1.1998181369.d4919860.8AtPaA&q='.$kwd.'&imgfile=&commend=all&ssid=s5-e&search_type=item&sourceId=tb.index&ie=utf8&initiative_id=tbindexz_'.$date.'&tab=mall';
                    if ($data['region']) {
                        $search_url .= '&loc=' . urlencode($region);
                    } 
                    if ($data['price_from'] || $data['price_to']) {
                        $price_from = $data['price_from'] ? $data['price_from'] : '';
                        $price_to = $data['price_to'] ? $data['price_to'] : '';
                        $search_url .= '&filter=reserve_price' . urlencode("[".$price_from.",".$price_to."]");
                    }
                    break;
                case 'tmall':
                    $search_url = 'https://list.tmall.com/search_product.htm?q='.$kwd.'&type=p&vmarket=&spm=3.7396704.a2227oh.d100&from=mallfp..pc_1_searchbutton';
                    if ($data['price_from']) {
                        $search_url .= '&start_price=' . $data['price_from'];
                    }
                    if ($data['price_to']) {
                        $search_url .= '&end_price=' . $data['price_to'];
                    }
                    break;
            }
        }
        elseif ('tbmobi' == $data['platform']) {
            $search_url = 'http://s.m.taobao.com/h5?q='.$kwd.'&search-bton=&event_submit_do_new_search_auction=1&_input_charset=utf-8&topSearch=1&atype=b&searchfrom=1&action=home%3Aredirect_app_action&from=1&ttid=';
        }
        elseif ('jdpc' == $data['platform']) {
            $search_url = 'http://search.jd.com/Search?keyword='.$kwd.'&enc=utf-8';
            if ($data['price_from'] && $data['price_to']) {
                $search_url .= '&qrst=1&rt=1&stop=1&sttr=1&ev=exprice_' . intval($data['price_from']) . '-' . intval($data['price_to']). '%40&uc=0#select';
            }
        }
        elseif ('ju' == $data['platform']) {
            $search_url = 'http://ju.taobao.com/search.htm?words=' . $kwd;
        }

        return $search_url;
    }

    public function buildItemUrl($data) {
        return 'http://item.jd.com/' . $data['nid'] . '.html';
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

    public function setNotFound($kid) {
        $redis = new Redis();
        $redis->connect(REDIS_HOST, REDIS_PORT);
        $redis->select(0);
        $key = 'notfound_' . $kid;
        $redis->incr($key);
        $redis->close();
    }

    public function setFound($kid) {
        $redis = new Redis();
        $redis->connect(REDIS_HOST, REDIS_PORT);
        $redis->select(0);
        $key = 'notfound_' . $kid;
        if ($redis->exists($key)) {
            $redis->set($key, '0');
        }
        $redis->close();
    }
}

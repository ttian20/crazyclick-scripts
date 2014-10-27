var casper = require('casper').create({
    verbose: true,
    logLevel: 'debug',
    pageSettings: {
        userAgent: 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; InfoPath.2; .NET4.0C; .NET4.0E)'
    }
});

var title;
var target;
var res;

var search_url = casper.cli.get(0);
var search_selector = casper.cli.get(1);
var next_selector = casper.cli.get(2)
var sleep_time = parseInt(casper.cli.get(3)) * 1000;
var shop_type = '';

var search_times = 0;

casper.start(search_url);

search(0);
function search(flag) {
    if (flag) {
        casper.wait(1000, function(){
            this.click(next_selector);
        });
        //console.log(search_times);
    }
    casper.then(function(){
        if (this.exists(search_selector)) {
            res = casper.evaluate(function(f){
                document.querySelector(f).setAttribute('target', '_self');
                var arr = new Array();
                arr[0] = document.querySelector(f).getAttribute('target');
                arr[1] = document.querySelector(f).getAttribute('href');
                return arr;
            }, search_selector);
            //console.log(res[0]);
            //console.log(res[1]);
            if (res[1].indexOf("detail.tmall.com/") != -1) {
                shop_type = 'b';
            }
            else {
                shop_type = 'c';
            }
            //console.log(shop_type);
            this.wait(1000, function(){
                this.click(search_selector);
            });
        }
        else {
            if (search_times >= 10) {
                console.log('404');
                casper.exit();
            }
            else {
                search_times++;
                search(1);
            }
        }
    });
}

casper.then(function(){
    left(shop_type);
});

function left(shop_type) {
if (shop_type == 'b') {
    casper.then(function(){
        title = casper.evaluate(function(){
            var aele = document.querySelectorAll('a');
            for (var i = 0, len = aele.length; i < len; i++) {
                aele[i].setAttribute('target', '_self');
            }
    
            return document.title;
        });
        //console.log(title);
        //console.log(this.getCurrentUrl()); 
        //console.log('200-1');
    
        if (this.exists(".slogo-shopname")) {
            this.wait(sleep_time, function(){
                this.click(".slogo-shopname");
            });
        }
        else {
            console.log('200-405');
            casper.exit();
        }
    });
    
    casper.then(function(){
        title = casper.evaluate(function(){
            var aele = document.querySelectorAll('a');
            for (var i = 0, len = aele.length; i < len; i++) {
                aele[i].setAttribute('target', '_self');
            }
            return document.title;
        });
        //console.log(title);
        //console.log(this.getCurrentUrl()); 
    
        /*if (this.exists("a[href*='tmall.com/p/']")) {
            this.wait(10000, function(){
                this.click("a[href*='tmall.com/p/']");
            });
        }*/
    
        if (this.exists("a[href*='tmall.com/category-']")) {
            this.wait(10000, function(){
                this.click("a[href*='tmall.com/category-']");
            });
        }
        else {
            console.log('200-406');
            casper.exit();
        }
    });
    
    casper.then(function(){
        console.log(this.getCurrentUrl()); 
        casper.evaluate(function(){
            var aele = document.querySelectorAll('a');
            for (var i = 0, len = aele.length; i < len; i++) {
                aele[i].setAttribute('target', '_self');
            }
        });
        if (this.exists("a[href^='http://detail.tmall.com/item.htm']")) {
            this.wait(5000, function(){
                this.click("a[href^='http://detail.tmall.com/item.htm']");
            });
        }
        else {
            console.log('200-407');
            casper.exit();
        }
    });
    
    casper.then(function(){
        console.log(this.getCurrentUrl()); 
        console.log('200');
        casper.exit();
    });
}
else {
    casper.then(function(){
        title = casper.evaluate(function(){
            var aele = document.querySelectorAll('a');
            for (var i = 0, len = aele.length; i < len; i++) {
                aele[i].setAttribute('target', '_self');
            }
    
            return document.title;
        });
        //console.log(title);
        //console.log(this.getCurrentUrl()); 
    
        if (this.exists(".tb-shop-name a")) {
            this.wait(sleep_time, function(){
                this.click(".tb-shop-name a");
            });
        }
        else {
            console.log('200-405');
            casper.exit();
        }
    });
    
    casper.then(function(){
        title = casper.evaluate(function(){
            var aele = document.querySelectorAll('a');
            for (var i = 0, len = aele.length; i < len; i++) {
                aele[i].setAttribute('target', '_self');
            }
            return document.title;
        });
        //console.log(title);
        //console.log(this.getCurrentUrl()); 
    
        if (this.exists("a[href*='taobao.com/category.htm']")) {
            this.wait(10000, function(){
                this.click("a[href*='taobao.com/category.htm']");
            });
        }
        else {
            console.log('200-406');
            casper.exit();
        }
    });
    
    casper.then(function(){
        console.log(this.getCurrentUrl()); 
        casper.evaluate(function(){
            var aele = document.querySelectorAll('a');
            for (var i = 0, len = aele.length; i < len; i++) {
                aele[i].setAttribute('target', '_self');
            }
        });
        if (this.exists("a[href^='http://item.taobao.com/item.htm']")) {
            this.wait(5000, function(){
                this.click("a[href^='http://item.taobao.com/item.htm']");
            });
        }
        else {
            console.log('200-407');
            casper.exit();
        }
    });
    
    casper.then(function(){
        console.log(this.getCurrentUrl()); 
        console.log('200');
        casper.exit();
    });
}
}

casper.run();

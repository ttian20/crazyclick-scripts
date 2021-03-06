var casper = require('casper').create({
    verbose: true,
    logLevel: 'debug',
    timeout: 600000,
    pageSettings: {
        userAgent: 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; InfoPath.2; .NET4.0C; .NET4.0E)'
    }
});

var title;
var target;
var res;

var kwd = casper.cli.get(0);
var search_selector = casper.cli.get(1);
var next_selector = casper.cli.get(2)
var sleep_time = parseInt(casper.cli.get(3)) * 1000;
var shop_type = '';

var search_times = 0;
var scroll_wait = 1500;
var current_url = '';

casper.start("https://www.taobao.com");
casper.wait(scroll_wait);
casper.thenEvaluate(function(){
    document.body.scrollTop = 0;
});

casper.wait(scroll_wait);
casper.thenEvaluate(function(){
    document.body.scrollTop += 900;
});

casper.wait(scroll_wait);
casper.thenEvaluate(function(){
    document.body.scrollTop = 0;
});

casper.thenEvaluate(function(q){
    document.getElementById("q").value = q;
}, kwd);

casper.then(function(){
    this.click("#J_TSearchForm > div > .btn-search");
})

search(0);
function search(flag) {
    if (flag) {
        casper.wait(1000, function(){
            this.click(next_selector);
        });
        //console.log(search_times);
    }

    casper.wait(scroll_wait);
    casper.thenEvaluate(function(){
        document.body.scrollTop  = 0;
    });

    casper.wait(scroll_wait);
    casper.thenEvaluate(function(){
        document.body.scrollTop  += 900;
    });

    casper.then(function(){
        var current_url = this.getCurrentUrl();
        if (current_url.indexOf('https://www.taobao.com') == 0) {
            console.log('501');
            casper.exit();
        }

        if (current_url.indexOf('https://login.taobao.com/member/login') == 0) {
            console.log('502');
            casper.exit();
        }
    });

    casper.wait(scroll_wait);
    casper.thenEvaluate(function(){
        document.body.scrollTop  += 900;
    });

    casper.wait(scroll_wait, function(){
        this.scrollToBottom();
    });

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

casper.wait(scroll_wait);
casper.thenEvaluate(function(){
    document.body.scrollTop  = 0;
});
casper.thenEvaluate(function(){
    document.body.scrollTop  += 900;
});
casper.wait(scroll_wait);
casper.thenEvaluate(function(){
    document.body.scrollTop  += 900;
});
casper.wait(scroll_wait);
casper.thenEvaluate(function(){
    document.body.scrollTop  += 900;
});
casper.wait(scroll_wait, function(){
    this.scrollToBottom();
});
casper.wait(scroll_wait);

casper.then(function(){
    this.wait(1000, function(){
        this.click(search_selector);
    });
});

casper.wait(scroll_wait);
casper.thenEvaluate(function(){
    document.body.scrollTop  = 0;
});
casper.thenEvaluate(function(){
    document.body.scrollTop  += 900;
});
casper.wait(scroll_wait);
casper.thenEvaluate(function(){
    document.body.scrollTop  += 900;
});
casper.wait(scroll_wait);
casper.thenEvaluate(function(){
    document.body.scrollTop  += 900;
});
casper.wait(scroll_wait, function(){
    this.scrollToBottom();
});

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
            //console.log('500');
            console.log('200-401');
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
        else if (this.exists(".shop-name-wrap a")) {
            this.wait(sleep_time, function(){
                this.click(".shop-name-wrap a");
            });
        }
        else if (this.exists(".fst-cat-bd a")) {
            this.wait(sleep_time, function(){
                this.click(".fst-cat-bd a");
            });
        }
        else {
            //console.log('500');
            console.log('200-401');
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
        else if (this.exists("a[href*='taobao.com/category-']")) {
            this.wait(10000, function(){
                this.click("a[href*='taobao.com/category-']");
            });
        }
        else if (this.exists("a[href^='//item.taobao.com/item.htm']")) {
            this.wait(5000, function(){
                this.click("a[href^='//item.taobao.com/item.htm']");
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
        if (this.exists("a[href^='//item.taobao.com/item.htm']")) {
            this.wait(5000, function(){
                this.click("a[href^='//item.taobao.com/item.htm']");
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

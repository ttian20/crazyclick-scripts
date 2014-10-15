var casper = require('casper').create({
    verbose: true,
    logLevel: 'debug',
    viewportSize: {width: 320, height: 568},
    pageSettings: {
        userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 7_0 like Mac OS X; en-us) AppleWebKit/537.51.1 (KHTML, like Gecko) Version/7.0 Mobile/11A465 Safari/9537.53'
    }
});

var title;
var target;
var res;

var search_url = 'http://s.m.taobao.com/h5?q=bra&topSearch=1&from=1&abtest=9&sst=1';
var search_selector = "div.d a[href*='36472252922']";
//var search_selector = "div.d a";
var logo_selector = "a";

var sleep_time = 10;
var search_times = 0;

casper.start(search_url);
casper.thenEvaluate(function(){
    document.body.scrollTop  = 0;
});
function search() {
    casper.thenEvaluate(function(){
        document.body.scrollTop  +=  500;
    });
    casper.then(function(){
        if (this.exists(search_selector)) {
            title = this.evaluate(function(f, l){
                document.querySelector(l).setAttribute('href', document.querySelector(f).getAttribute('href'));
                return document.querySelector(l).getAttribute('href');
            }, search_selector, logo_selector);

            console.log(title);

            this.wait(2000, function(){
                console.log("here");
                this.click(logo_selector);
            });
        }
        else {
            search();
        } 
    });
}

search();

casper.then(function(){
    res = casper.evaluate(function(){
        return document.body.innerHTML;
    });
    //console.log(res);
    //console.log("aaa");
    casper.exit();
});
casper.run();

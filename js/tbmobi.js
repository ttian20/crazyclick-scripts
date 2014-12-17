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

var search_url = casper.cli.get(0);
var search_selector = casper.cli.get(1);
var sleep_time = parseInt(casper.cli.get(2)) * 1000;
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
            if (search_times > 30) {
                console.log('404');
                casper.exit();
            }
            else {
                search_times++;
                search();
            }
        } 
    });
}

search();

casper.then(function(){
    res = casper.evaluate(function(){
        return document.body.innerHTML;
    });
    //console.log(res);
    console.log("200");
    casper.exit();
});
casper.run();

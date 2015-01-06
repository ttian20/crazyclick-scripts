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
            console.log(++search_times);
            casper.exit();
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
casper.run();

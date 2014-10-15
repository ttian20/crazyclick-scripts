var casper = require('casper').create({
    verbose: true,
    logLevel: 'debug',
    pageSettings: {
        userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 7_0 like Mac OS X; en-us) AppleWebKit/537.51.1 (KHTML, like Gecko) Version/7.0 Mobile/11A465 Safari/9537.53'
    }
});

var title;
var target;
var res;

var search_url = 'http://s.m.taobao.com/h5?q=bra&topSearch=1&from=1&abtest=9&sst=1';
var search_selector = ".item[nid='20626276623'] h3 a";
var next_selector = ".page-next";
var sleep_time = 10;

var search_times = 0;

casper.start(search_url);
casper.thenEvaluate(function(){
    document.body.scrollTop  =  3000;
});
casper.thenEvaluate(function(){
    document.body.scrollTop  +=  document.body.scrollHeight;
});
casper.thenEvaluate(function(){
    document.body.scrollTop  +=  document.body.scrollHeight;
});
casper.thenEvaluate(function(){
    document.body.scrollTop  +=  document.body.scrollHeight;
});
casper.thenEvaluate(function(){
    document.body.scrollTop  +=  document.body.scrollHeight;
});
casper.thenEvaluate(function(){
    document.body.scrollTop  +=  document.body.scrollHeight;
});
casper.thenEvaluate(function(){
    document.body.scrollTop  +=  document.body.scrollHeight;
});
casper.thenEvaluate(function(){
    document.body.scrollTop  +=  document.body.scrollHeight;
});
casper.thenEvaluate(function(){
    document.body.scrollTop  +=  document.body.scrollHeight;
});
casper.thenEvaluate(function(){
    document.body.scrollTop  +=  document.body.scrollHeight;
});
casper.thenEvaluate(function(){
    document.body.scrollTop  +=  document.body.scrollHeight;
});
casper.thenEvaluate(function(){
    document.body.scrollTop  +=  document.body.scrollHeight;
});
casper.thenEvaluate(function(){
    document.body.scrollTop  +=  document.body.scrollHeight;
});
casper.thenEvaluate(function(){
    document.body.scrollTop  +=  document.body.scrollHeight;
});

//casper.scrollToBottom();
casper.then(function(){
    res = casper.evaluate(function(){
        return document.body.innerHTML;
    });
    console.log(res);
    console.log("aaa");
    casper.exit();
});
//casper.waitFor(function s(){
//    this.scrollToBottom();
//
//    //this.page.scrollPosition = { top: this.page.scrollPosition["top"] + 4000, left: 0 };
//    return true;
//}, function t() {
//    if (this.exists(".J_PageContainer_2")) {
//        console.log("aaa");    
//        //console.log(this.page.scrollPosition["top"];
//    }
//    else {
//        console.log("bbb");
//        //console.log(this.page.scrollPosition["top"];
//    }
//    casper.exit();
//});

//casper.then(function(){
//    //casoer.evaluate(function(){
//    //});
//    //this.scrollTo(0, 3000);
//    res = casper.evaluate(function(){
//        return document.body.innerHTML;
//    });
//    console.log(res);
//});
//casper.then(function(){
//    this.scrollToBottom();
//});
//casper.then(function(){
//    res = casper.evaluate(function(){
//        return document.body.innerHTML;
//    });
//    console.log(res);
//    casper.exit();
//});
//casper.then(function(){
//    res = casper.evaluate(function(){
//        return document.body.innerHTML;
//    });
//    console.log(res);
//    casperjs.exit();
//});
casper.run();

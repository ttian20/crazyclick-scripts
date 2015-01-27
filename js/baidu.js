var casper = require('casper').create({
    verbose: true,
    logLevel: 'debug',
    pageSettings: {
        userAgent: 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; InfoPath.2; .NET4.0C; .NET4.0E)'
    }
});

var title = '';
var item_url = 'http://www.baidu.com';

casper.start(item_url);

casper.then(function(){
    title = casper.evaluate(function(){
        return document.title;
    });
    console.log(title);
    //console.log(this.getCurrentUrl()); 
    casper.exit();
});

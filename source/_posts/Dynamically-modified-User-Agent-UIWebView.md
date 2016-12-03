title: 动态修改 UIWebView 的 User-Agent
date: 2015-09-19 02:34:46
tags:

- iOS 

- Objective-C

- UIWebView

- User Agent

---

我们在IOS客户端嵌入UIWebView后，H5网页里获取到的user-agent和使用Safari是一样的，为了可以区别是app里面的访问，我们可以通过修改user-agent来做到这一点。

当然，可以完全指定新的user-agent，但正常点还是追加一些信息会更好，比如把app包名最后一节和版本号加上：
<!--more-->

```objectivec
BOOL shutDownUserAgent = YES;
NSString *oldAgent = [myWeb stringByEvaluatingJavaScriptFromString:@"navigator.userAgent"];
NSString *version = [[[NSBundle mainBundle] infoDictionary] objectForKey:@"CFBundleVersion"];
NSString *package = [[[NSBundle mainBundle] infoDictionary] objectForKey:@"CFBundleIdentifier"];
NSString *ext = [[package componentsSeparatedByString:@"."] lastObject];
NSString *myAgent = [NSString stringWithFormat:@" %@/%@", ext, version];

//自定义user-agent
if ( shutDownUserAgent ){
    if ([oldAgent hasSuffix:myAgent]) {
        NSString *newAgent = [oldAgent stringByReplacingOccurrencesOfString:myAgent withString:@""];
        NSDictionary *dictionnary = [[NSDictionary alloc] initWithObjectsAndKeys:newAgent, @"UserAgent", nil];
        [[NSUserDefaults standardUserDefaults] registerDefaults:dictionnary];
    }
}else{
    if (![oldAgent hasSuffix:myAgent]) {
        NSString *newAgent = [oldAgent stringByAppendingString:myAgent];
        //NSLog(@"new agent :%@", newAgent);
        NSDictionary *dictionnary = [[NSDictionary alloc] initWithObjectsAndKeys:newAgent, @"UserAgent", nil];
        [[NSUserDefaults standardUserDefaults] registerDefaults:dictionnary];
    }
}```


这个代码可以多次执行，建议封装到一个公用的函数去执行。



这里有个开关shutDownUserAgent，其实是为了方便添加或者移除标记。因为这个设置后，所有uiwebview都是这个user-agent，所以我们在添加前需要检查是否已经有追加过，以防重复添加。

title: 移动解析HTTPDNS在App开发中实践总结
date: 2016-12-03 02:24:16
tags:

- iOS 

- HTTPDNS

-----


## HTTPDNS简介

HTTPDNS是客户端基于http协议向服务器A发送域名B解析请求（例如：www.baidu.com），服务器A直接返回域名B对应的ip地址（例如：119.75.217.109），客户端获取到的IP后就向直接往此IP发送业务协议请求。   
这种方式替代了基于DNS协议向运营商LocalDNS发起解析请求，可以从根本上避免LocalDNS造成的域名劫持问题。

## 使用场景

域名劫持现象在国内较为严重，属于中国特色，传说中部分地区甚至出现过20%的用户域名解析被劫持的情况。    
   
对于我们应用开发者而言，域名劫持并不像普通bug快速发现定位，然后通过更新版本或者热修复来恢复。譬如某个地区的用户集中反馈H5页面打不开，或者数据接口返回超时，我们一般流程会从客户沟通中一步步排除问题，最后才会发现是域名劫持导致，然后通过更换域名、与运营商沟通这些效率极低的解决方式来解决。
<!--more-->

我们不禁会有疑问，运营商闲的蛋疼么？为什么会来劫持我们的域名呢？这样做对他们有什么好处呢？

要想解决这些疑问，首先要来了解下国内运营商的情况：

国内主要的带宽资源掌握在国内三大运营商手里，三大向一级代理商转售流量带宽，一级代理商向二级、三级层层转卖，这种混乱复杂的情景最终导致用户使用的流量会经过多个代理商。

而代理商为了减少网间结算的费用，会使用域名劫持的手段，将域名强行指向自己的内容缓存服务器，并为了营收在流量中插入第三方广告联盟的广告。

这种国情下的国内互联网业，HTTPDNS已经是行业标配。



## 实施方式

我们公司的项目遇到这个问题后，我们进行了调研工作：京东、阿里使用的都是其自建的DNS解析，而我们的体量较小，自建DNS周期长且昂贵，所以最佳方案就是集成第三方的HTTPDNS解析。

HTTPDNS实施的步骤并不复杂 ：在网络请求之前会调用第三方的解析，将请求的域名替换为第三方解析的ip，然后向服务器发起请求。

但是在实际实施中，我们遇到了很多坑点，这里我们进行记录与总结。

因为我们项目是包含了原生UI与H5页面的混合App，所以我们实施要从两个方面着手：
1.  **Native数据接口部分**
2.  **webview中H5页面部分**

### **Native数据接口部分**

数据接口的实施相对简单：

在网络库的request产生时候进行处理（如果你们网络库没有暴露request，不支持修改request，你们还需要对网络层进行修改），将request的url中的域名替换为解析后的ip。

````objectivec
    //原始URL
    NSURL *originalUrl =[NSURL URLWithString:@"https://api.helijia.com/app-merchant"];
    //根据原始URL获取 第三方解析出的ip
    NSString *ip = [self getHostByUrlSyn:url];
    //替换ip后的URL
    NSURL *url = [ip replaceHostWithIp:ip];
````


这里我们在实施时候遇到了以下问题：

**问题1：服务器无法判断请求访问的内容。**

原因：在我们修改http请求时，这时http的head中host字段会变成ip，因为一台服务器我们会有很多接口服务同时存在，服务器接收到请求后无法根据域名去判断我们访问的是哪个服务。

解决：由于服务器是根据host字段来判断请求的服务，所以在发起网络请求时，用带ip的URL生成request后，手动将request中的host字段改回域名。这样服务器可以正确识别，运营商也会根据域名中的ip为我们路由。

````objectivec
    //原始URL
    NSURL *originalUrl =[NSURL URLWithString:@"https://api.helijia.com/app-merchant"];
    //根据原始URL获取 第三方解析出的ip
    NSString *ip = [self getHostByUrlSyn:url];
    //替换ip后的URL
    NSURL *url = [ip replaceHostWithIp:ip];
    NSMutableURLRequest *request = [NSMutableURLRequest requestWithURL:url];
    //将request的host字段改为原始URL的域名
    [request setValue:originalUrl.host forHTTPHeaderField:@"host"];
````

**问题2：域名替换并修改host后，无法通过https证书校验。**


原因：我们公司目前主要业务接口已经切换到了https，https在网络请求过程中会进行证书校验，以保证请求的安全性。

在发送HTTPS请求中，首先要进行SSL/TLS握手，握手过程大致如下：

1. 客户端发起握手请求，携带随机数、支持算法列表等参数。
2. 服务端收到请求，选择合适的算法，下发公钥证书和随机数。
3. 客户端对服务端证书进行校验，并发送随机数信息，该信息使用公钥加密。
4. 服务端通过私钥获取随机数信息。
5. 双方根据以上交互的信息生成session ticket，用作该连接后续数据传输的加密密钥。

![](http://7xq0lf.com1.z0.glb.clouddn.com/httpdns01.png?imageView2/2/w/600/q/100)

    
    
上述过程中，和HTTPDNS有关的是第3步：

客户端会检查证书的domain域和扩展域，看是否包含本次请求的host。如果上述两点都校验通过，就证明当前的服务端是可信任的，否则就是不可信任，就会中断当前连接，并在log中输出证书校验出错的原因。

所以当客户端使用HTTPDNS解析域名时，请求URL中的host会被替换成HTTPDNS解析出来的IP，所以在证书验证的第2步，会出现domain不匹配的情况，导致SSL/TLS握手不成功。


**解决：我们hook证书校验过程中的证书中的域名校验，将请求中的ip再替换回域名后，再执行证书验证。**

我们项目中使用的网络库是AFNetworking，AFN已经封装好了会在证书校验时执行的block，我们要做的就是在生成request时候，在block中写好替换host的代码就可以了。   

````objectivec

    // operationManager 是网络库单例持有的 AFHTTPSessionManager 对象。
    //
        [_manager.operationManager setSessionDidReceiveAuthenticationChallengeBlock:^NSURLSessionAuthChallengeDisposition(NSURLSession * _Nonnull session, NSURLAuthenticationChallenge * _Nonnull challenge, NSURLCredential *__autoreleasing  _Nullable * _Nullable credential) {
            //跳过证书校验过程
            NSURLSessionAuthChallengeDisposition disposition = NSURLSessionAuthChallengePerformDefaultHandling;
            if ([challenge.protectionSpace.authenticationMethod isEqualToString:NSURLAuthenticationMethodServerTrust]) {
                *credential = [NSURLCredential credentialForTrust:challenge.protectionSpace.serverTrust];
                disposition = NSURLSessionAuthChallengeUseCredential;
            } else {
                disposition = NSURLSessionAuthChallengePerformDefaultHandling;
            }
            return disposition;
        }];
        [_manager.operationManager setTaskDidReceiveAuthenticationChallengeBlock:^NSURLSessionAuthChallengeDisposition(NSURLSession * _Nonnull session, NSURLSessionTask * _Nonnull task, NSURLAuthenticationChallenge * _Nonnull challenge, NSURLCredential *__autoreleasing  _Nullable * _Nullable credential) {
            //跳过证书校验过程
            NSURLSessionAuthChallengeDisposition disposition = NSURLSessionAuthChallengePerformDefaultHandling;
            if ([challenge.protectionSpace.authenticationMethod isEqualToString:NSURLAuthenticationMethodServerTrust]) {
                *credential = [NSURLCredential credentialForTrust:challenge.protectionSpace.serverTrust];
                disposition = NSURLSessionAuthChallengeUseCredential;
            } else {
                disposition = NSURLSessionAuthChallengePerformDefaultHandling;
            }
            return disposition;
        }];
````

上面的两段设置证书校验的代码并不是重复的。一个是在NSURLSessionDelegate中didReceiveChallenge代理方法里执行，而另一个是在NSURLSessionTaskDelegate中didReceiveChallenge代理方法中执行。为了全面拦截证书校验，这里我们需要设置两个Block。

这两个对AFN设置跳过证书校验的Block，这个block是AFN封装好提供的。AFN在NSURLSession的证书校验代理方法中进行判断，如果block不为空就执行，否则执行原有的证书校验操作。


在AFN中AFURLSessionManager.m里我们可以看到如下代码的实现：


````objectivec
- (void)URLSession:(NSURLSession *)session
didReceiveChallenge:(NSURLAuthenticationChallenge *)challenge
 completionHandler:(void (^)(NSURLSessionAuthChallengeDisposition disposition, NSURLCredential *credential))completionHandler
{
    NSURLSessionAuthChallengeDisposition disposition = NSURLSessionAuthChallengePerformDefaultHandling;
    __block NSURLCredential *credential = nil;
    //在这里进行了证书校验Block的判断，如果不为空就使用自定义Block，否则使用原有的Block
    if (self.sessionDidReceiveAuthenticationChallenge) {
        disposition = self.sessionDidReceiveAuthenticationChallenge(session, challenge, &credential);
    } else {
        if ([challenge.protectionSpace.authenticationMethod isEqualToString:NSURLAuthenticationMethodServerTrust]) {
            if ([self.securityPolicy evaluateServerTrust:challenge.protectionSpace.serverTrust forDomain:challenge.protectionSpace.host]) {
                credential = [NSURLCredential credentialForTrust:challenge.protectionSpace.serverTrust];
                if (credential) {
                    disposition = NSURLSessionAuthChallengeUseCredential;
                } else {
                    disposition = NSURLSessionAuthChallengePerformDefaultHandling;
                }
            } else {
                disposition = NSURLSessionAuthChallengeCancelAuthenticationChallenge;
            }
        } else {
            disposition = NSURLSessionAuthChallengePerformDefaultHandling;
        }
    }

    if (completionHandler) {
        completionHandler(disposition, credential);
    }
}
````


### **webview中H5页面部分**
HTTPDNS实施的主要难点与坑点都在H5页面上面，下面逐条记录下在实施webview的HTTPDNS时遇到的问题：

**问题：由于web页面的请求并不是由客户端发起，我们无法在生成request的时候修改host。**

**解决：在这里我们使用NSURLProtocol来解决。**

#### NSURLProtocol

用一句话解释NSURLProtocol ：NSURLProtocol就是一个苹果允许的中间人攻击。

NSURLProtocol可以劫持系统所有基于C socket的网络请求。

**注意：WKWebView基于Webkit，并不走底层的C socket，所以NSURLProtocol拦截不了WKWebView中的请求**

具体步骤为：

注册NSURLProtocol子类 -> 使用NSURLProtocol子类拦截Webview请求 -> 使用NSURLSession重新发起请求 -> 将NSURLSession请求的响应内容返回给Webview

NSURLProtocol子类的实现：

#### **拦截哪些请求**

* request的URL是ip的（ipv4、ipv6）
* 非白名单的请求



````objectivec

/**
 *  是否拦截处理指定的请求
 *
 *  @param request 指定的请求
 *
 *  @return 返回YES表示要拦截处理，返回NO表示不拦截处理
 */
+ (BOOL)canInitWithRequest:(NSURLRequest *)request {
    
    //DNS开关控制功能开启关闭
    if (![[HLJHttpDNS shareInstance] isDNSConfigWorking]) {
        return NO;
    }
    /* 防止无限循环，因为一个请求在被拦截处理过程中，也会发起一个请求，这样又会走到这里，如果不进行处理，就会造成无限循环 */
    if ([NSURLProtocol propertyForKey:protocolKey inRequest:request]) {
        return NO;
    }
    // 防止无限循环， 第三方解析会发出ip域名的请求，这里筛选
    // 判断请求URL的Host是否Ipv4
    if ([WebViewURLProtocol checkHostIp:request.URL.host]) {
        return NO;
    }    
    NSString *url = [request.URL.host mutableCopy];
    //去掉Ipv6的大括号
    url = [url stringByReplacingOccurrencesOfString:@"[" withString:@""];
    url = [url stringByReplacingOccurrencesOfString:@"]" withString:@""];
    // 判断请求URL的Host是否Ipv6
    if ([WebViewURLProtocol checkHostIpv6:url]) {
        return NO;
    }
    NSMutableURLRequest *mutableReq = [request mutableCopy];
    //假设原始的请求头部没有host信息，只有使用IP替换后的请求才有
    NSString *host = [mutableReq valueForHTTPHeaderField:@"host"];
    if (!mutableReq && host) {
        return NO;
    }
    return YES;
}
````


在拦截的部分，我们需要注意一点，因为我们向第三方解析域名的请求也是ip的。这里我们需要在拦截时对域名的host位进行判断，如果是ipv4、ipv6的域名，就不对其进行拦截。不然程序就会循环拦截重新发起后的请求，导致程序卡死。


我们项目中图片服务是走CDN的服务器，还有其他统计等第三方的服务等等。我们将这类第三方的域名加入了白名单，在请求时会跳过对白名单内域名的拦截。


#### **拦截住的请求怎么修改**
    
* 替换域名为解析后的ip
* 修改request的host
* 修改证书校验中的host

拦截请求后，我们在重新发起的请求中对request进行修改：替换域名为解析后的ip、修改request的host


````objectivec

- (void)startLoading {
    
    NSMutableURLRequest *request = [self.request mutableCopy];
    // 表示该请求已经被处理，防止无限循环
    [NSURLProtocol setProperty:@(YES) forKey:protocolKey inRequest:request];
    
    NSMutableURLRequest *mutableReq = [request mutableCopy];
    NSString *originalUrl = mutableReq.URL.absoluteString;
    NSURL *url = [NSURL URLWithString:originalUrl];
    // 同步接口获取IP地址
    NSString *ip = [[HLJHttpDNS shareInstance] getHostByNameSyn:url.absoluteString];    
    if (ip) {
        // 通过HTTPDNS获取IP成功，进行URL替换和HOST头设置
        NSRange hostFirstRange = [originalUrl rangeOfString:url.host];
        if (NSNotFound != hostFirstRange.location) {
            mutableReq.URL = [NSURL URLWithString:ip];
            // 添加原始URL的host
            [mutableReq setValue:url.host forHTTPHeaderField:@"host"];
            // 添加originalUrl保存原始URL
            [mutableReq addValue:originalUrl forHTTPHeaderField:@"originalUrl"];
        }
    }

    NSURLSessionConfiguration *configuration = [NSURLSessionConfiguration defaultSessionConfiguration];
    self.session = [NSURLSession sessionWithConfiguration:configuration delegate:self delegateQueue:[NSOperationQueue currentQueue]];
    NSURLSessionTask *task = [_session dataTaskWithRequest:mutableReq];
    [task resume];
}
````



在NSURLProtocol中拦截了请求后，在重新发起NSURLSession代理方法中，我们将证书校验的Host重新改回域名，这样就会通过证书校验过程。
    
    


````objectivec
#pragma NSURLSessionTaskDelegate
- (void)URLSession:(NSURLSession *)session task:(NSURLSessionTask *)task didReceiveChallenge:(NSURLAuthenticationChallenge *)challenge completionHandler:(void (^)(NSURLSessionAuthChallengeDisposition, NSURLCredential *_Nullable))completionHandler {
    if (!challenge) {
        return;
    }
    NSURLSessionAuthChallengeDisposition disposition = NSURLSessionAuthChallengePerformDefaultHandling;
    NSURLCredential *credential = nil;
    /*
     * 获取原始域名信息。
     */
    NSString *host = [[self.request allHTTPHeaderFields] objectForKey:@"host"];
    if (!host) {
        host = self.request.URL.host;
    }
    if ([challenge.protectionSpace.authenticationMethod isEqualToString:NSURLAuthenticationMethodServerTrust]) {
        if ([self evaluateServerTrust:challenge.protectionSpace.serverTrust forDomain:host]) {
            disposition = NSURLSessionAuthChallengeUseCredential;
            credential = [NSURLCredential credentialForTrust:challenge.protectionSpace.serverTrust];
        } else {
            disposition = NSURLSessionAuthChallengePerformDefaultHandling;
        }
    } else {
        disposition = NSURLSessionAuthChallengePerformDefaultHandling;
    }
    // 对于其他的challenges直接使用默认的验证方案
    completionHandler(disposition, credential);
}
````


#### **H5页面重定向处理**

作为电商客户端，里面包含大量H5的页面。在部分H5页面由于活动页面变更、新老域名兼容等原因，H5会将H5页面进行重定向处理。

* 重定向

简单介绍下重定向的过程：

**|--->
客户端浏览器发送http请求访问域名A
--->
web服务器接受后发送302状态码响应及对应新的域名B给客户浏览器
--->
客户浏览器发现是302响应，自动再发送一个新的http请求，请求url是新的域名
--->
服务器根据此请求寻找资源并发送给客户端浏览器
---|**


以上就是重定向的整个过程。当我们在某个版本H5页面入口写死为域名A，后续的新版本的域名更改为域名B，为了兼容老版本，H5需要对域名A做域名重定向，将请求域名A请求重定向到域名B。


我们在使用浏览器访问域名A页面时（包括UIWebView），服务器返回重定向标识，浏览器会自动重新发起重定向到域名B的请求。

但是在我们实施的HTTPDNS中，我们使用了NSURLProtocol对UIWebView的请求进行拦截，对请求A域名替换为ip，并将host修改为原有域名A。而重定向发起的新的请求并没有被替换域名与修改Host，所以我们还要对此进行处理。

NSURLSessionTaskDelegate 提供对应的处理重定向代理方法，我们只要在对应方法中，对重定向的逻辑进行处理。


````objectivec
#pragma NSURLSessionTaskDelegate

- (void)URLSession:(NSURLSession *)session task:(nonnull NSURLSessionTask *)task willPerformHTTPRedirection:(nonnull NSHTTPURLResponse *)response newRequest:(nonnull NSURLRequest *)request completionHandler:(nonnull void (^)(NSURLRequest * _Nullable))completionHandler
{

    NSMutableURLRequest *mutableReq = [request mutableCopy];
    [NSURLProtocol setProperty:@(YES) forKey:protocolKey inRequest:mutableReq];
    
    NSURL *url = mutableReq.URL;
    NSString *urlString = [url.absoluteString urlEncode];
    // 获取IP地址
    NSString *ip = nil;
    if([[HttpDNS shareInstance] shouldGetHostByUrl:urlString]) {
        ip = [[HttpDNS shareInstance] getHostByNameSyn:urlString];
    }
    //    NSLog(@" *** originalUrl %@ ip %@ from HTTPDNS loading...", request.URL, ip);
    
    if (ip) {
        // 通过HTTPDNS获取IP成功，进行URL替换和HOST头设置
        //        NSLog(@"*** Get IP(%@) for host(%@) from HTTPDNS Successfully!", ip, url.host);
        mutableReq.URL = [NSURL URLWithString:ip];
    }
    
    // 添加原始URL的host
    [mutableReq setValue:url.host forHTTPHeaderField:@"host"];
    // 添加originalUrl保存原始URL
    [mutableReq addValue:urlString forHTTPHeaderField:@"originalUrl"];
    
    completionHandler(mutableReq);
    
}
````


其中我们将mutableReq的域名与Host进行修改，并将其传回completionHandler，让NSURLSession继续处理接下来的重定向进程即可。


# 其他优化

1. 增加缓存   

因为每次请求都向服务器请求解析的话，会给服务器带来非常大的压力，同时也会造成客户端的请求时间变长，整体体验变差。所以我们在实践中给HTTPDNS增加了缓存机制，同一个域名第一次解析走服务器，后面从缓存中读取。当用户网络状态变化时候，清空缓存，防止缓存失效的问题。


2. 白名单

我们项目中图片服务是走CDN的服务器，还有其他统计等第三方的服务等等。我们将这类第三方的域名加入了白名单，在请求时会跳过对白名单内域名的拦截。

3. 服务端开关

考虑到HTTPDNS为全局功能影响很大，所以我们增加了服务端开关，已确保功能的可控性。


# 总结

虽然HTTPDNS只是将域名解析的协议由DNS协议换成了HTTP协议，并不复杂。但是其中也遇到了很多坑点与难点，好在有着同事与谷歌的帮助都顺利解决。

HTTPDNS作用不仅仅是域名解析异常，还有以下更多的应用：

  * **HTTPDNS能直接获取到用户IP，未来通过结合公司的IP地址库以及CDN测速系统，可以将用户引导到对我们业务访问最快的IDC节点上。**

  * **可以通过HTTPDNS实现负载均衡，将流量均匀进入不同的服务器进行处理。**

此外，HTTPDNS还有着以下优点：

  * **实现成本低：接入HTTPDNS的业务仅需要对客户端接入层做少量改造，无需用户手机进行root或越狱；而且由于Http协议请求构造非常简单，兼容各版本的移动操作系统更不成问题；另外HTTPDNS的后端配置完全复用现有权威DNS配置，管理成本也非常低。**

  * **扩展性强：HttpDNS提供可靠的域名解析服务，业务可将自有调度逻辑与HttpDNS返回结果结合，实现更精细化的流量调度。比如指定版本的客户端连接请求的IP地址，指定网络类型的用户连接指定的IP地址等。**

总而言之，就是以最小的改造成本，解决了业务遭受域名解析异常的问题，并满足业务精确流量调度的需求。



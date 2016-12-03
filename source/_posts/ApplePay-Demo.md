title: 苹果支付 Apple Pay 开发
date: 2016-02-27 09:24:16
tags:

- iOS 

- Apple Pay

- 苹果支付
-----


## 前言

在发布近两年、历经各种周折之后，苹果公司的Apple Pay移动支付服务终于在2016年2月18日来到了中国大陆。
   
对中国用户来说，移动支付其实已经不是什么陌生事物，抢红包和支付宝早完成用户启蒙。但与这两者有区别的是，Apple Pay只是苹果搭建的一个支付服务，它链接银行、店面及用户，但又不像支付宝那样把钱存在自己这。   


## Apple Pay的准备工作


#### 1. 哪些设备能用Apple Pay？

苹果对设备和系统做了双重限制，两者都要满足要求才能Apple Pay。

　　**设备要求**：简略的说需要iPhone 6或者更新的手机，还有iPad Air 2与mini 3之后的平板，以及苹果手表。
<!--more-->
　　**具体型号**：iPhone 6，iPhone 6 Plus，iPhone 6s， iPhone 6s Plus；iPad Air 2，iPad mini 3，iPad mini 4，iPad Pro，还有Apple Watch。   

　　一种特殊情况是，如果你还在用iPhone 5之类的旧型号，因为它没有NFC功能，配个Apple Watch在手表上倒也能Apple Pay。比iPhone 5还老的就不行了，它们不能跟Apple Watch配对。

　　**系统要求**：iPhone或iPad至少要升到iOS 9.2版，手表至少watch OS 2.1。

　　**支持银行**：首批12家银行已经支持Apple Pay，他们是：中国农业银行，中国银行，上海银行，中国建设银行，中信银行，招商银行，民生银行，广发银行，中国工商银行，兴业银行，中国邮政储蓄银行，上海浦东发展银行。

　　之后还会增加7家：平安银行，光大银行，广州银行，华夏银行，宁波银行，交通银行，北京银行。
　　
　　![](http://7xq0lf.com1.z0.glb.clouddn.com/applepaydemoimg001.png?imageView2/2/w/600/q/100)

　　[详细列表请见苹果官网](https://support.apple.com/zh-cn/HT204916)
　　
　　
#### 2. 申请MerchantID及对应证书
　　在接入Apple Pay之前，首先要申请MerchantID及对应证书。[请参考这篇：MerchantID及对应证书详细图文教程](http://www.jianshu.com/p/2e5e45afc246)
　　
#### 3. 支付服务
　　如果你们公司已经有了现成的支付处理系统,你可以直接使用你自己的服务端方案来接收从App传来的支付请求来实现支付功能。如果没有，那么使用第三方的支付服务的SDK是更好的选择。在国内的话，第三方支付基本上是使用银联的SDK[银联Apple Pay手机支付](https://open.unionpay.com/ajweb/product/detail?id=80)。
　　
　　
## Apple Pay的基本功能实现
　　Apple Pay的API由PassKit框架提供，所以我们需要在项目中引入`PassKit`框架，另外我在demo中使用了苹果自带的Apple Pay的展示界面，所以我们还需要引入`PKPaymentAuthorizationViewController`。   
   

![](http://7xq0lf.com1.z0.glb.clouddn.com/WASPay01.png?imageView2/2/w/300/q/100)
　　
   

从上面的我们可以看出发起一个Apple Pay的请求，需要以下信息：

1. 支付选项；
2. 送货方式；
3. 商品价格和折扣；
4. 支持支付的网关；
5. 送货地址；
   

#### 1. 支付选项
    
支付选项由`PKPaymentRequest`类提供，在这里可以设置订单货币的货币类型（人民币还是美刀）、快递方式、快递信息（姓名，联系电话，收货地址）等等：


aaa
    
````objectivec
    //设置币种、国家码及merchant标识符等基本信息
    - (PKPaymentRequest *)payRequest
    {
        if (_payRequest == nil) {
            _payRequest = [[PKPaymentRequest alloc] init];
            //国家代码
            _payRequest.countryCode = @"CN";
            //RMB的币种代码
            _payRequest.currencyCode = @"CNY";
            //申请的merchantID
            _payRequest.merchantIdentifier = @"merchant.WASPay";
            //用户可进行支付的银行卡
            _payRequest.supportedNetworks = self.supportedNetworks;
            //设置支持的交易处理协议，3DS必须支持，EMV为可选。
            _payRequest.merchantCapabilities = PKMerchantCapability3DS|PKMerchantCapabilityEMV;
            //如果需要邮寄账单可以选择进行设置，默认PKAddressFieldNone(不邮寄账单)
            _payRequest.requiredBillingAddressFields = PKAddressFieldEmail;
            //送货地址信息，这里设置需要地址和联系方式和姓名，如果需要进行设置，默认PKAddressFieldNone(没有送货地址)
            _payRequest.requiredShippingAddressFields = PKAddressFieldPostalAddress|PKAddressFieldPhone|PKAddressFieldName;
            //快递方式
            _payRequest.shippingMethods = self.shippingMethods;
            //订单信息
            _payRequest.paymentSummaryItems = self.summaryItems;
        }
        return _payRequest;
    }
````


#### 2. 送货方式

送货方式由`PKShippingMethod`类提供，可以根据业务需求来制定相应的邮费标准：

````objectivec    
     //配送方式
    - (NSMutableArray *)shippingMethods
    {
        if (_shippingMethods == nil) {
            PKShippingMethod *freeShipping = [PKShippingMethod summaryItemWithLabel:@"包邮" amount:[NSDecimalNumber zero]];
            freeShipping.identifier = @"freeshipping";
            freeShipping.detail = @"6-8 天 送达";
            PKShippingMethod *expressShipping = [PKShippingMethod summaryItemWithLabel:@"极速送达" amount:[NSDecimalNumber decimalNumberWithString:@"10.00"]];
            expressShipping.identifier = @"expressshipping";
            expressShipping.detail = @"2-3 小时 送达";
            _shippingMethods = [NSMutableArray arrayWithArray:@[freeShipping, expressShipping]];
        }
        return _shippingMethods;
    }````
#### 3. 商品价格和折扣

一个`PKPaymentSummaryItem`对象对应了一条费用明细，示例中的费用明细共有四条：  

1. 商品价格
2. 折扣信息
3. 邮费信息
4. 收款方和最终的总价格

   
故我们需要建立对应4个`PKPaymentSummaryItem`对象，并设置对应参数：

````objectivec
     //订单信息
    - (NSMutableArray *)summaryItems
    {
        if (_summaryItems == nil) {
            NSDecimalNumber *subtotalAmount = [NSDecimalNumber decimalNumberWithMantissa:5288 exponent:0 isNegative:NO];
            PKPaymentSummaryItem *subtotal = [PKPaymentSummaryItem summaryItemWithLabel:@"商品价格" amount:subtotalAmount];
            NSDecimalNumber *discountAmount = [NSDecimalNumber decimalNumberWithMantissa:2165 exponent:-2 isNegative:YES];
            PKPaymentSummaryItem *discount = [PKPaymentSummaryItem summaryItemWithLabel:@"优惠折扣" amount:discountAmount];
            NSDecimalNumber *methodsAmount = [NSDecimalNumber zero];
            PKPaymentSummaryItem *methods = [PKPaymentSummaryItem summaryItemWithLabel:@"包邮" amount:methodsAmount];
            NSDecimalNumber *totalAmount = [NSDecimalNumber zero];
            totalAmount = [totalAmount decimalNumberByAdding:subtotalAmount];
            totalAmount = [totalAmount decimalNumberByAdding:discountAmount];
            totalAmount = [totalAmount decimalNumberByAdding:methodsAmount];
            PKPaymentSummaryItem *total = [PKPaymentSummaryItem summaryItemWithLabel:@"老王" amount:totalAmount];
            _summaryItems = [NSMutableArray arrayWithArray:@[subtotal, discount, methods, total]];
        }
        return _summaryItems;
    }````

#### 4. 支持的支付网关
支付网关的话就根据项目的需求来自己定义了，Apple Pay支持的支付网关总有一下几种：
   
1. `PKPaymentNetworkAmex`;美国运通卡
2. `PKPaymentNetworkDiscover`;美国发现卡
3. `PKPaymentNetworkMasterCard`;美国万事达卡
4. `PKPaymentNetworkPrivateLabel`；礼品卡
5. `PKPaymentNetworkVisa`;美国Visa
6. `PKPaymentNetworkChinaUnionPay`;中国银联
7. `PKPaymentNetworkInterac`；加拿大 Interac e-Transfer
   
其中中国银联和加拿大的Interac e-Transfer 仅在iOS9.2开始支持。


````objectivec
     //可以支持的支付选项,这里以国内常用的银联和visa为例
    - (NSArray *)supportedNetworks
    {
        if (_supportedNetworks == nil) {
            _supportedNetworks = @[PKPaymentNetworkVisa,PKPaymentNetworkChinaUnionPay];
        }
        return _supportedNetworks;
    }````

#### 5. 代理方法

````objectivec
    //支付验证：将支付凭据发给服务端进行验证支付是否真实有效，验证后 使用`completion`的Block来实现成功回调
    - (void)paymentAuthorizationViewController:(PKPaymentAuthorizationViewController *)controller
                           didAuthorizePayment:(PKPayment *)payment
                                    completion:(void (^)(PKPaymentAuthorizationStatus status))completion;
    //支付结束回调，当支付完成或者是用户取消了支付，都会调用此代理，主要用来回收`PKPaymentAuthorizationViewController`
    - (void)paymentAuthorizationViewControllerDidFinish:(PKPaymentAuthorizationViewController *)controller;
    //在即将开始验证支付凭据时调用
    - (void)paymentAuthorizationViewControllerWillAuthorizePayment:(PKPaymentAuthorizationViewController *)controller NS_AVAILABLE_IOS(8_3);
    //当用户选择新的快递方式是调用，不同的运输方式选择不同的运费标准
    - (void)paymentAuthorizationViewController:(PKPaymentAuthorizationViewController *)controller
                       didSelectShippingMethod:(PKShippingMethod *)shippingMethod
                                    completion:(void (^)(PKPaymentAuthorizationStatus status, NSArray<PKPaymentSummaryItem *> *summaryItems))completion;
    //当用户选择了新的收货地址时调用，可以用来判断新的收货地址是否可达，或者根据新的地址来获取相关的运费信息
    - (void)paymentAuthorizationViewController:(PKPaymentAuthorizationViewController *)controller
                      didSelectShippingAddress:(ABRecordRef)address
                                    completion:(void (^)(PKPaymentAuthorizationStatus status, NSArray<PKShippingMethod *> *shippingMethods,
                                                         NSArray<PKPaymentSummaryItem *> *summaryItems))completion NS_DEPRECATED_IOS(8_0, 9_0, "Use the CNContact backed delegate method instead");
    - (void)paymentAuthorizationViewController:(PKPaymentAuthorizationViewController *)controller
                      didSelectShippingContact:(PKContact *)contact
                                    completion:(void (^)(PKPaymentAuthorizationStatus status, NSArray<PKShippingMethod *> *shippingMethods,
                                                         NSArray<PKPaymentSummaryItem *> *summaryItems))completion NS_AVAILABLE_IOS(9_0);
    //当用户选择了新的银行卡用来支付时被调用，可以用来更新订单信息显示的付款方式
    - (void)paymentAuthorizationViewController:(PKPaymentAuthorizationViewController *)controller
                        didSelectPaymentMethod:(PKPaymentMethod *)paymentMethod
                                    completion:(void (^)(NSArray<PKPaymentSummaryItem *> *summaryItems))completion NS_AVAILABLE_IOS(9_0);````



#### 6. 总结

希望这个教程可以让你更好的理解和使用Apple Pay。另外，还要注意阅读[苹果官方关于Apple Pay的用户界面的规范](https://developer.apple.com/apple-pay/ux-guidelines/)，苹果对此要求非常严格。最后附上一个

[我写的一个Apple Pay基本功能实现的demo](https://github.com/SureWinter/WASPay)

希望这个demo能让你对Apple Pay的实现有更为直观的理解。
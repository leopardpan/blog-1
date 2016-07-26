title: 通过子类修改父类私有属性
date: 2014-05-14 02:34:50
tags:

- iOS 

- Objective-C

- 私有属性

---
在iOS开发中，使用成熟稳定的第三方开源库会给项目的开发节省大量的开发成本和时间。开源库本身有以下特性：

- 独立，重用，基本不会依赖和绑定某个项目
- 不定期更新，有大量社区开发者的关注和少数人的维护

而通常拿来使用的时候，我们会发现原版代码有很多地方仍然需要改动，才能适配到自己的项目中去。比如某在我们需要在某种操作完成之后有一个回调方法来进行下一步操作，但是使用的三方库并没有提供，所以就面临两个选择：要么直接改动源码，并且保证pod更新或升级的时候改动不被冲掉；要么通过子类重载、使用Category等其他手段来实现。    

从项目的拓展以及稳定性来说，后者是个更好的选择。下面总结一下我对于通过子类修改父类属性的一些探究。   
<!--more-->

这里是一段示例代码：



````objectivec

    // User.h
    @interface User : NSObject
	@property (nonatomic, strong) NSString *userName; 
	@end  
	
    // User.m
    @interface User ()
    @property (nonatomic, strong) NSString *passWord;
    @end
    @implementation User
    
    - (instancetype)init {
        self = [super init];
        if (self) {
            _userName = @"jack";
            _passWord = @"123";
            }
            return self;
        }
    @end````





这是一个User类，有一个公有的属性userName，一个私有的属性passWord。

下面问题来了：我希望创建一个Hacker对象，打算在初始化的时候拥有自己的UserName（比如JackB）和passWord（比如abc），但又不去修改User类中的代码。



## 1. 通过setValue:(id)value forKey修改


通常在默认情况下，父类没有公开的属性，我们无法在子类中用self.`var`的形式去访问，所以这里我们可以通过`setValue:(id)value forKey`的方式来修改父类属性。    

代码如下：


````objectivec
    // Hacker.h

    @interface Hacker : User
    @end

    // Hacker.m
    @implementation Hacker

    - (instancetype)init {
        self = [super init];
        if (self) {
            self.userName = @"jackB";
            [self setValue:@"abc" forKey:@"passWord"];
        }
    return self;
    }

    @end````



创建Hacker对象，就可以初始化为所需要的自定义Hacker对象。   

为什么这个方法可以修改父类的属性呢？我们需要先来看一下这个方法背后都做了哪些事情。

##### 简单介绍
````objectivec
    - (void)setValue:(id)value forKey:(NSString *)key;````

苹果官方给出的解释是:使用一个字符串标示符给一个对象的属性赋值.它支持普通对象和集合对象。

这个方法的默认实现如下:

1. 首先去接收者(调用方法的那个对象)的类中查找与key相匹配的访问器方法(`-setKey`),如果找到了一个方法,就检查它参数的类型,如果它的参数类型不是一个对象指针类型,但是只为nil,就会执行`setNilValueForKey:`方法,`setNilValueForKey:`方法的默认实现,是产生一个`NSInvalidArgumentException`的异常,但是你可以重写这个方法.如果方法参数的类是一个对象指针类型,就会简单的执行这个方法,传入对应的参数.如果方法的参数类型是`NSNumber`或`NSValue`的对应的基本类型,先把它转换为基本数据类,再执行方法,传入转换后的数据.

2. 如果没有对应的访问器方法(setter方法),如果接受者的类的`+accessInstanceVariablesDirectly`方法返回YES,那么就查找这个接受者的与key相匹配的实例变量(匹配模式为\_key,\_isKey,key,isKey):比如:key为age,只要属性存在_age,_isAge,age,isAge中的其中一个就认为匹配上了,如果找到这样的一个实例变量,并且的类型是一个对象指针类型,首先released对象上的旧值,然后把传入的新值retain后的传入的值赋值该成员变量,如果方法的参数类型是NSNumber或NSValue的对应的基本类型,先把它转换为基本数据类,再执行方法,传入转换后的数据.

3. 如果访问器方法和实例变量都没有找到,执行`setValue:forUndefinedKey:`方法,该方法的默认实现是产生一个 `NSUndefinedKeyException` 类型的异常,但是我们可以重`setValue:forUndefinedKey:`方法    

看完上面关于方法的实现的说明，我们就明白了其是在找不到Hacker类的passWord属性的set方法之后，转而去查找passWord类的属性中是否含有passWord相关key的实例变量，找到\_passWord属性之后，将“abc”的值赋值给该属性。

所以通过`setValue`方法可以轻松实现我们在子类中更改父类属性的需求。
    
## 2. 通过运行时performSelector:withObject:方式修改

[self performSelector:@selector(setPassWord:) withObject:@"abc"];

在Objective-C中调用函数的方法是“消息传递”，这个和普通的函数调用的区别是，你可以随时对一个对象传递任何消息，而不需要在编译的时候声明这些方法。

而`performSelector:`就是是Objective-C运行时中一种消息处理方法。在运行时负责去`@selector(setPassWord:)`对应方法。

在这个方法背后，系统做了这样一些事情：
    
1. 该方法调用后，系统会发出`objc_msgSend`消息，传给本类来查找SEL中是否存在`setPassWord:`方法；
2. 假设本类不存在，会继续查找工程里是否有分类提供了该方法；    
3. 假设分类中也没有该方法，系统会将消息转发给其父类，父类收到消息后也会执行上面的操作，找方法，没找到再往上转发消息；
4. 假设最终都没有找到，就会执行最后的消息转发(message forwarding)操作；
5. 如果转发出去都没人接收的话，`NSObject中`的`doesNotRecognizeSelector`就选择抛出异常了，也就是我们看到的crash了；


看完上面我们就很清晰明了，在我们这个场景中，就是通过第三步：将消息转发给父类来实现修改父类中的属性。


另外由于`performSelector:`所以在编译时候不会做任何校验。所以一般为了程序的健壮性，会使用检查方法，来检查是否含有这个方法，以防止程序崩溃。    

````objectivec
    - (BOOL)respondsToSelector:(SEL)aSelector;````

当我们遇到需要传递的参数为Bool类型时候，还有一点我们需要注意的地方：

````objectivec
    [view performSelector:@selector(setHidden:) withObject:@NO];````


如果你执行上面的代码，结果一定会令你大失所望。因为它会执行`view.hidden = @NO`。不过针对BOOL类型参数的问题倒是有解决办法，比如专门为`performSelector:withObject:`定义YES和NO的参数对象：



`#define YES_OBJ          @1.0`

`#define NO_OBJ            nil`


````objectivec
    [view performSelector:@selector(setHidden:) withObject:NO_OBJ];````


将他们作为参数传入是可以与直接赋值YES和NO达到相同效果的。


## 3. 在子类中创建父类的class extension


```objectivec
// Hacker.m

@interface User () // 在子类的.m中暴露父类的内部结构
@property (nonatomic, strong) NSString *passWord;
@end

@implementation Hacker

- (instancetype)init {
    self = [super init];
    if (self) {
        self.userName = @"JackB";
        self.passWord = @"abc";
    }
    return self;
}

@end
```


这种方式没有奇怪的写法，看着很是舒服。


## 4. 在子类在内部声明一个跟父类内部同名的属性


看一下这个例子，子类Hacker在内部声明了一个跟父类内部同名的属性passWord，同样可以实现优雅的改动。

```objectivec
// Hacker.m

@interface Hacker ()
@property (nonatomic, strong) NSString *passWord;
@end

@implementation Hacker

- (instancetype)init {
    self = [super init];
    if (self) {
        self.userName = @"JackB";
        self.passWord = @"abc";
    }
    return self;
}
@end```

默认情况下，编译器会给子类进行自动合成@synthesize passWord = _passWord，这里有个问题：子类的_passWord是其的独有的实例变量，还是共享父类的呢？

探究的方法很简单，分别在父类和子类的init方法中`if (self)`的语句行设置断点，在程序执行到相应断点的时候分别在控制台输入：


``` objectivec 
(lldb) watchpoint set variable _passWord
```


结果可以分别看到两个不同的地址


```objectivec
(lldb) watchpoint set variable _passWord

Watchpoint created: Watchpoint 1: addr = 0x100603400 size = 8 state = enabled type = w
    watchpoint spec = '_passWord'
    new value: 0x0000000000000000

(lldb) watchpoint set variable _passWord
Watchpoint created: Watchpoint 2: addr = 0x100603408 size = 8 state = enabled type = w
    watchpoint spec = '_passWord'
    new value: 0x0000000000000000

```


所以说，子类定义了与父类内部同名属性的话，默认情况下，编译器会给子类创建一个实例变量，虽然与父类的同名，但却是两个独立的实例变量。


照这样说来，这不是资源浪费嘛。明明创建了父类的实例变量却不用，结果占用着内存也不干实事，能不能避免呢？以下有两种解决方法：
1. 不让父类使用实例变量；
2. 不让子类使用实例变量。

不让父类使用实例变量的话，简单说就是不要去调用super，比如父类的属性初始化设置通常集中在viewDidLoad或者自定义的方法，于是可以在子类重载并且不调用父类的同名方法。

不让子类使用实例变量的话，可以干脆就让子类没有实例变量，我们可以使用`@dynamic`关键字来实现。比如在Hacker类中给同名属性用`@dynamic`标记

```objectivec
@dynamic passWord;
```

那么运行时，那么对该属性的直接访问或存储最终会落实到父类的实例变量_passWord。
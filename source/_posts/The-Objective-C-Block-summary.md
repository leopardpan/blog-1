title: 对Objective-C中Block的总结
date: 2014-09-13 14:34:46
tags:

- iOS 

- Objective-C

- Block

---

## **对Block的定义**  
1.  `Block` 是OC中的一种数据类型，在iOS开发中被广泛使用；
2. `^`是 `Block` 的特有标记；
3. `Block` 的实现代码包含在{}之间；
4. 大多情况下，以内联 `inline` 函数的方式被定义和使用；
5. `Block` 与C语言的函数指针有些相似，但使用起来更加灵活；   

## **Block示例**    
   
**Block的声明**  
````objectivec
    @property (nonatomic,copy) void (^myBlock)();````

<!--more-->

block 也经常使用 copy 关键字，具体原因见[官方文档：Objects Use Properties to Keep Track of Blocks](https://developer.apple.com/library/ios/documentation/Cocoa/Conceptual/ProgrammingWithObjectiveC/WorkingwithBlocks/WorkingwithBlocks.html#//apple_ref/doc/uid/TP40011210-CH8-SW12 "官方文档：Objects Use Properties to Keep Track of Blocks")   

   
   


![](http://7xq0lf.com1.z0.glb.clouddn.com/pra02img01.png?imageView2/2/w/600/q/100)


   
   
Block 使用 copy 是从 MRC 遗留下来的“传统”。在 MRC 中,方法内部的 Block 是在栈区的,使用 copy 可以把它放到堆区。   

而在 ARC 中，对于 Block 使用 copy 还是 strong 效果是一样的，但写上 copy 也无伤大雅，还能时刻提醒我们：编译器自动对 Block 进行了 copy 操作。   
   
如果不写 copy ，该类的调用者有可能会忘记或者根本不知道“编译器会自动对 Block 进行了 copy 操作”，他们有可能会在调用之前自行拷贝属性值，这种操作多余而低效。    

 **Block的实现**
````objectivec
    void(^demoBlock)() = ^ {
    NSLog(@"demo Block");
    };
    int(^sumBlock)(int, int) = ^(int x, int y) {
    return x + y;
    };````


**格式说明**
````objectivec
    (返回类型)(^块名称)(参数类型) = ^(参数列表)
           {
                代码实现
           };````

PS：如果没有参数，等号后面参数列表的( )可以省略;



## **将block作为参数传递**   
**示例代码**
````objectivec
    // .h 
    -(void) testBlock:( NSString * ( ^ )( int ) )myBlock; 
        // .m 
    -(void) testBlock:( NSString * ( ^ )( int ) )myBlock {
         NSLog(@"Block returned: %@", myBlock(7) ); 
       }````

由于Objective-C是强制类型语言，所以作为函数参数的block也必须要指定返回值的类型，以及相关参数类型。


## **block中变量的复制与修改**
对于block外的变量引用，block默认是将其复制到其数据结构中来实现访问的，如下图：


![](http://7xq0lf.com1.z0.glb.clouddn.com/pra02img04.jpg)


通过block进行闭包的变量是 `const` 的。也就是说不能在block中直接修改这些变量。   


来看看当block试着增加x的值时，会发生什么：
````objectivec
    myBlock = ^( void ) {
         x++;
         return x;
       };````



结果是编译器会报错，表明在block中变量x是只读的。   
   
      
在定义Block时，是在Block中建立当前局部变量内容的副本(拷贝)即使后续block外再对该变量的数值进行修改，不会影响Block中的数值。   


有时候确实需要在Block中处理变量，怎么办？别着急，我们可以用 `__block` 关键字来声明变量，这样就可以在Block中修改变量了。基于之前的代码，给x变量添加 `__block` 关键字，如下：
````objectivec
    __block int x;````
    
    

对于用__block修饰的外部变量引用，block是复制其引用地址来实现访问的，如下图：   

![](http://7xq0lf.com1.z0.glb.clouddn.com/pra02img05.jpg)



## **使用typedef定义Block类型**

可以使用typedef定义一个Block的类型，便于在后续直接使用
````objectivec
    typedef double(^MyBlock)(double, double);
    MyBlock area = ^(double x, double y) {
       return x * y;
    };
    MyBlock sum = ^(double a, double b) {
    return a + b;
    };
    NSLog(@"%.2f", area(10.0, 20.0));
    NSLog(@"%.2f", sum(10.0, 20.0));````
    
    
说明：

代码中 `typedef` 是关键字，用于定义类型。MyBlock是定义的Block类型。area、sum分别是MyBlock类型的两个Block变量。   

尽管 `typedef` 可以简化Block的定义，但在实际开发中并不会频繁使用 `typedef` 关键字。这是因为Block具有非常强的灵活性，尤其在以参数传递时，使用Block的目的就是为了立即使用。  

   

苹果官方的数组遍历方法声明如下：
````objectivec
    - (void)enumerateObjectsUsingBlock:(void (^)(id obj, NSUInteger idx, BOOL *stop))block;````

而如果使用 `typedef` ，则需要：
````objectivec
    - typedef void(^EnumerateBlock)(id obj, NSUInteger idx, BOOL *stop);
    - (void)enumerateObjectsUsingBlock:(EnumerateBlock)block;````

而最终的结果却是，除了定义类型之外，EnumerateBlock并没有其他用处。   


## **将Block添加到数组**
既然Block是一种数据类型，那么可以将Block当做比较特殊的对象，可以将其添加到数组当中去。
````objectivec
    #pragma mark 定义并添加到数组
    @property (nonatomic, strong) NSMutableArray *myBlocks;
    int(^sum)(int, int) = ^(int x, int y) {
        return [self sum:x y:y];
    };
    [self.myBlocks addObject:sum];
    int(^area)(int, int) = ^(int x, int y) {
        return [self area:x y:y];
    };
    [self.myBlocks addObject:area];
    #pragma mark 调用保存在数组中的Block
    int(^func)(int, int) = self.myBlocks[index];
    return func(x, y);````


## **Block中使用外部对象与循环引用**


block用到外边的对象后，会对它进行强引用，在一些情况下会造成循环引用。    
````objectivec
    NSString *stopIndex = @"stop";
    NSArray *array = @[@“go", @“go", @“go", @“stop"];
    [array enumerateObjectsUsingBlock:^(id obj, NSUInteger idx, BOOL *stop) {
    NSLog(@"第 %d 项内容是 %@", (int)idx, obj);
    if ([stopIndex isEqualToString:obj]) {
        *stop = YES;
    }
    }];````
上面代码在将stopIndex的指针传递给Block时，Block会自动对stopName的指针做强引用；但是如果在对象也对block强引用的情况下，这种操作就很容易导致循环引用。   


````objectivec
    @property (nonatomic, strong) NSMutableArray *myBlocks;
    int(^sum)(int, int) = ^(int x, int y) {
        return [self sum:x y:y];
    };
    [self.myBlocks addObject:sum];````
    
在上面代码中  myBlocks、sum、self三者彼此强引用，就会导致循环引用，导致对象不能够释放。


![](http://7xq0lf.com1.z0.glb.clouddn.com/pra02img03.png?imageView2/2/w/600/q/100)




## **解除Block循环引用**

可以使用 `__weak` 关键字，可以将局部变量声明为弱引用
````objectivec
    __weak DemoObj *weakSelf = self;````

在Block中引用weakSelf，则Block不会再对self做强引用
````objectivec
    int(^sum)(int, int) = ^(int x, int y) {
        return [weakSelf sum:x y:y];
    };````
    
    

![](http://7xq0lf.com1.z0.glb.clouddn.com/pra02img02.png?imageView2/2/w/600/q/100)

    
如果使用 `typeof()` 这样可以不用管类型，它会自动识别类型
````objectivec
    __weak typeof(self) unself = self;
    int(^sum)(int, int) = ^(int x, int y) {
        return [unself sum:x y:y];
    };````
    
    
  
这样就可以解决Block中的循环引用问题。  



## **总结**
在实际开发中，使用 `Block` 有着比 `delegate` 和 `notification` 更简洁的优势。很多人在项目中大量的使用block。
在iOS开发中使用Block的场景很多：
- 遍历数组或者字典- 视图动画- 排序- 通知- 错误处理- 多线程
- ……
   
   在享受Block提供的便利的同时我们也要注意下Block中的循环引用问题。在Block内部，如果碰到self，最好能够思考一下，这里的对象能够释放吗？ 解决了Block的循环引用问题，在开发的时候使用Block还是非常愉快与便捷的。
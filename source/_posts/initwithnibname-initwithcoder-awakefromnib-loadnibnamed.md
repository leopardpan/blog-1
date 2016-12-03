title: 控制器与View的几个初始化方法对比
date: 2013-10-21 16:24:16
tags:
- iOS 

- View

- 初始化

---

<br />


我们在开发中肯定会遇到下面几个方法，单个使用肯定没有问题，但是把他们凑到一块时，会很容易傻傻分不清楚，下面我们就来对比总结下他们之间的异同。    
    
    

* initWithCoder    

* initWithFrame

* initWithNibName    

* awakeFromNib    

* loadNibNamed  

* loadView 

<br />

### **initWithCoder**   

这个方法是NSCopy中的方法。如果你的项目中用到的是storyboard ，并使用storyboard来创建控制器（也就是拖控件），并绑定控制器的类。这种情况下storyboard会根据storyboard中控制器的nibName属性的值，通过`initWithCoder`为我们初始化控制器。 

为什么会有这种机制呢，这要从storyboard、xib与nib说起。  

xib和nib都是Xcode的图形界面设计文档，storyboard 是苹果在 iOS 5 中引入的新技术方案，目的是使纷繁复杂的 nib、xib 之间的关系更直观地展示出来。
<!--more-->



　　![](http://7xq0lf.com1.z0.glb.clouddn.com/pra03img01.png)
　　
   

StoryBoard与xib的本质是一个 XML 文件，描述了若干窗体、组件、Auto Layout 约束等关键信息。而nib是一个二进制文件。由于xib是文本文件，所以在版本控制和管理方面比nib更有优势。   


实际上，nib的生成过程就是一个序列化的过程，nib文件的生成要经历两种序列化：

  1.  Xcode所用的文档的序列化，序列化的结果就是这个XML文件。它保存的是所有界面的资源信息和各个对象之间的关系。

  2.  Xcode编译时对这个xml文件进行的序列化，序列化的结果是二进制的nib文件。


编译时对xml文件做了如下操作:

  1.  读取xml文件，生成所有界面对象，生成所有object（即自定义的controller类等），设置好各个obejct之间的联系（IBAction，IBOutlet）。

  2.  对对象进行序列化，即调用encodeWithCoder方法来序列化，生成二进制nib文件,为什么要生成二进制文件，因为从二进制文件生成类实例更快一些。   
  

而在程序运行时，对nib文件进行反序列化的过程:

  1.  反序列化，调用所有对象的initWithCoder方法。
  2.  某些类的initWithFrame，init之类的方法被调用，生成所有类实例。
  3.  发送awakeFromNib消息，每个类实例的awakeFromNib被调用。

 
最终我们总结出：`initWithCoder`**是一个类在Xcode中以xib或者storyboard创建，然后使用代码方式被实例化时被调用的。**比如,通过Xcode创建一个view的xib文件,然后在xocde中通过`loadNibNamed`来实例化这个view,那么这个view的`initWithCoder`会被调用。

**示例代码：**   


````objectivec
    @interface ViewController : UIViewController

    @end
    @implementation BlueButton

    - (id)initWithCoder:(NSCoder *)aDecoder{
        NSLog(@"call %@", @"initWithCoder");
        if (self = [super initWithCoder:aDecoder]) {
            self.titleLabel.text = @"initWithCoder";
        }
        return  self;
    }
    @end
````



<br />


### **initWithFrame**   
    
    
`initWithFrame` 是UIView类的一个初始化方法，使用这个方法可以创建一个UIView的对象，并根据Frame的值对生成的对象进行设置。其他对象也是有`initWithFrame`方法的，这里我们就以UIView为例。

实际编程中，我们使用编程方式下，来创建一个UIView或者创建UIView的子类。这时候，将调用initWithFrame方法，来实例化UIView。
特别注意，如果在子类中重载initWithFrame方法，必须先调用父类的initWithFrame方法。在对自定义的UIView子类进行初始化操作。     
    
````objectivec 
    - (id)initWithFrame:(CGRect)frame{
        self = [super initWithFrame:frame];// 先调用父类的initWithFrame方法
        if (self) {
        // 再自定义该类（UIView子类）的初始化操作。
        _scrollView = [[UIScrollView alloc] initWithFrame:self.bounds];
        [_scrollView setFrame:CGRectMake(0, 0, 320, 480)];
        _scrollView.contentSize = CGSizeMake(320*3, 480);
        [self addSubview:_scrollView];
        }
        return self;
    }
````




`initWithFrame`方法的调用仅在我们代码初始化对象时，如果这个对象是通过xib方式创建了UIView对象。（用拖控件的方式）。那么，`initWithFrame`方法方法是不会被调用的。因为nib文件已经知道如何初始化该View。（我们在拖该view的时候，就定义好了长、宽、背景等属性）。这时候，会调用`initWithCoder`方法，我们可以用`initWithCoder`方法来重新定义我们在nib中已经设置的各项属性。


 


<br />


### **initWithNibName**

`initWithNibName` 是控制器的一种特定的构造方法，我们用它来从指定Xib文件加载控制器。这个方法加载的nib是延迟加载，只有当view被使用或者显示的时候，才会从nib中加载控制器。所以我们如果想在控制器加载后，进行额外处理的话，我们应该在`viewDidLoad`中做这些处理，在这个方法执行时，控制器的view才是真正加载完毕。   

   
   
````objectivec 
- (id)initWithNibName:(NSString *)nibNameOrNilbundle:(NSBundle *)nibBundleOrNil
{
    self = [superinitWithNibName:nibNameOrNil bundle:nibBundleOrNil];
    if (self) {
    //做初始化操作
        label=[[UILabelalloc]initWithFrame:CGRectMake(0,0,160,160)];
        [self.viewaddSubview:label];
    }
    returnself;
}
````

<br />

### **awakeFromNib**

awakeFromNib这个方法主要用来在加载xib之后对xib的中所有对象进行额外操作。这个方法会在什么时候调用呢？我们先来了解一下程序编译时，对xib解析初始化的流程：

* 首先在程序编译过程中，系统会对xib中的每一个对象进行解析并进行初始化。

* 在解析完成后，初始化对象之前，系统会对将要初始化的对象进行判断，如果这个对象遵守了NSCoding协议，就会使用initWithCoder方法来其初始化。而其他没有遵守NSCoding协议的对象则都用其自身的init方法来初始化。  

* 在所有的对象都解析完成后，系统会根据xib文件的描述，在对象之间建立起联系（也就是我们平时设置的IBAction，IBOutlet等）。

* 当完成这一系列操作，从xib加载所有信息完成后，系统会给xib加载的所有对象发送一个awakeFromNib消息。

<br />

**我们在工作中使用awakeFromNib时，还需要注意一下几点：**   

* 重写这个方法时候，一定要记得先调用一下父类的awakeFromNib，让父类做一些父类所需要的额外初始化操作。   

* 因为xib中每个对象的初始化的顺序并不是固定的，我们在对象的初始化方法中应该避免使用到同一xib中的其他对象。如果我们想使用到其他对象的时候我们就可以在awakeFromNib方法中操作，因为当xib中一个对象接收到这个消息时，就说明它所有的IBAction，IBOutlet等已经设置完毕，我们可以安心的在这里进行额外的定制初始化操作。   
    
* 通常我们使用这个方法来操作需要额外处理的对象，而这个操作并不方便在对象创建时候来做。例如：如果我们需要提供一个功能，用户可以设置选择自己的界面颜色，这时我们可以在创建对象的时候，使用默认颜色，然后在xib解析初始化完成后，在awakeFromNib中将界面的颜色设置为用户选择的颜色。
 
 
**示例代码：**

````objectivec 
    @implementation BlueButton

    - (id)initWithFrame:(CGRect)frame
    {
        self = [super initWithFrame:frame];
        if (self) {
            // Initialization code
        }
        return self;
    }
    - (void)awakeFromNib{
        [super awakeFromNib]; //调用父类方法
        NSLog(@"call %s", __FUNCTION__);
        self.backgroundColor = [UIColor blueColor];
        [self setTitle:@"Blue Button" forState:UIControlStateNormal];
    }
    @end
````


<br />


### **loadNibNamed**

这个方法的作用是在指定包路径下解析nib文件中的内容。一共三个参数：

1. 第一个参数name：nib文件的name，并不需要包含.nib扩展名；

2. 第二个参数owner：指定了对象类型，与file‘s owner类型一致；

3. 第三个参数options：指定了当打开nib文件时额外的配置选项。


这个方法返回一个包含所有顶级元素的数组（这里的顶级元素指代的并非唯一，因为一个nib文件理论上可以存储N个嵌套的元素，这里的顶级便是只包含子元素/节点没有父亲元素的“树根节点”）

常见用法
这个方法经常被用来在代码中加载由xib定义的UIView：

假设自定义的的UIView子类为CustomView,三个文件分别为Custom.h,Custom.m,Custom.xib
1、将Custom.xib的File‘s owner设为Custom
2、在Custom中声明一个strong的property：view【防止loadNibName返回的对象过早释放】
3、重写其initWithFrame 方法如下：


````objectivec
    -(id)initWithFrame:(CGRect)frame
    {
        self = [super initWithFrame:frame];
        if (self) {
            // 1. 加载xib
            NSString *className = NSStringFromClass([self class]);
            self.View = [[[NSBundle mainBundle] loadNibNamed:className owner:self options:nil] firstObject];
            // 2. 设置bounds
            if(CGRectIsEmpty(frame)) {
                self.bounds = _customView.bounds;
            }
            // 3. 添加View
            [self addSubview:self.View];
        }
        return self;
    }
````

<br />

### **loadView**


这个方法是控制器方法。控制器在需要显示View，发现View为nil，就会调用这个方法去创建View用来显示。这个方法被控制器自动调用，并不需要我们自己去主动调用。
如果控制器是绑定了xib或者storyboard，那么这个方法会从xib或者storyboard加载初始化view。如果控制器没有绑定xib或者storyboard，这个方法会创建一个空白的view。

这个方法一般用来手动创建view，而且这个view要被设置成控制器的RootView，并且不再需要调用父类的loadView方法。






<br />


**上述几个方法的作用图示：**

　　![](http://7xq0lf.com1.z0.glb.clouddn.com/pra03img02.png?imageView2/2/w/600/q/100)


















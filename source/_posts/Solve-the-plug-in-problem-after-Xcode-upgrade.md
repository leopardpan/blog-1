title: 解决Xcode升级后插件不能用的问题
date: 2014-01-13 16:24:16
tags:
- iOS 

- Xcode

- 插件

---
# 解决Xcode升级后插件不能用的问题


Xcode每次更新有个很头疼的问题，就是插件都会失效，要重装。不得不说好多插件还是非常方便能提高效率。下面我们来看下如何解决这个问题。


### Xcode插件失效原因

插件升级失效并不是升级或重装原本的插件被删掉了，其实不然，插件还保存在这个目录     
`~/Library/Application Support/Developer/Shared/Xcode/Plug-ins` 
之所以插件失效，是因为每个插件只供特定UUID的Xcode使用，更新后uuid改变，于是便不能正常使用。
<!--more-->
### 解决办法
* 获取新版Xcode的UUID

　　在终端执行    
    
    defaults read /Applications/Xcode.app/Contents/Info DVTPlugInCompatibilityUUID   
　　   
　　会得到一串 UUID 码。
　　![](http://7xq0lf.com1.z0.glb.clouddn.com/pra01img01.png?imageView2/2/w/500/q/100)    
　　如果执行命令后不能正确显示UUID，这可能你的Xcode不是你安装的，而是直接拷贝别人安装好的Xcode到你的应用程序中，那么这个命令得不到Xcode的UUID，会出现如下图的问题　　　　
　　
　　![](http://7xq0lf.com1.z0.glb.clouddn.com/pra01img02.png?imageView2/2/w/500/q/100)  
　　    
　　这时只能通过另一种方法得到Xcode的UUID，在应用程序中找到Xcode，右键选择显示包内容，找到Info.plist文件打开找到`DVTPlugInCompatibilityUUID`对应的值就是我们要的UUID    
　　![](http://7xq0lf.com1.z0.glb.clouddn.com/pra01img03.png?imageView2/2/w/500/q/100)   
   
   
* 修改插件的uuid为当前Xcode的uuid   
　	找到这个目录   
　	`~/Library/Application Support/Developer/Shared/Xcode/Plug-ins`    
　	
　	找到对应的插件，右键显示包内容，找到Info.plist文件打开找到`DVTPlugInCompatibilityUUIDs`的项目，添加一个Item，Value的值为之前Xcode的UUID，保存。

* 重启Xcode  
　　重启 Xcode 之后会提示"Load bundle"、"Skip Bundle"，这里必须选择"Load bundle"，不然插件无法使用。如果又不小心点了Skip Bundle，那就删掉你刚才在插件plist文件里添加的那行Item，然后重启Xcode，然后再重新刚才的那几步。至此问题已经完美解决。

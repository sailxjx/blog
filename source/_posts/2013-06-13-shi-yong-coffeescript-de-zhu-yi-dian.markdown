---
layout: post
title: "使用 coffeescript 的注意点"
date: 2013-06-13 14:48
comments: true
categories: [nodejs, coffeescript]
---

[coffeescript](https://github.com/jashkenas/coffee-script)是javascript的一个方言，随着javascript在前后端的流行，它在[github](https://github.com/languages)的排名也扶摇直上，最近终于挤掉高帅富[Objective-C](https://github.com/languages/Objective-C)跻身前十，可喜可贺。

虽然coffeescript号称"It's just javascript"，但是相比较而言，仍然是添加了很多有趣的特性，大部分特性都是去粗取精，去伪存真，让js玩家喜闻乐见，让旁观路人不明觉厉，但是也随之带来了一些容易忽视的问题，不得不提一下，以免以后碰到后不知所措。

# 重载的符号

coffeescript重载了javascript中的一些符号和语法结构，最常用的就是`==`和`in`。

## `==`
在js中最为人诟病的就是`==`符号表意不明，所以很多严谨的js开发者就强迫自己在比较时尽可能的使用`===`，coffeescript在这一点上做的更绝，你不能使用`===`，因为它将所有的`==`都转化成了`===`。这样对于一些经常需要在两种语言之间切换的码农来说，就是一种考验了。

## `in`
在js中，遍历一个数组或hash对象可以使用`for(var i in arr)`的语言结构，这个时候遍历得到的`i`其实是数组的下标或者hash的key。coffeescript对`in`做了重载，使其更符合自然语义，遍历出的是数组的值和hash的value。同时引入`of`操作符，可以用它来代替原生的`in`，遍历出数组的下标，如`for i of arr`。

# class

原生的js中是没有class的概念的，但是有经验的码农会用prototype模型来将方法打包成class，以实现代码的重复利用。coffeescript中提供了class关键词，让类的实现和继承更加简单，但是也由此引发一些问题。假如说上面的问题只是人所共知的新特性的话，下面这些就是需要在编码时注意绕行的坑了。

## 变量名与类名

coffeescript对于类型和变量名并没有强制性的格式要求，这在其他语言中也不会出现问题，因为可以通过类型检查来区分两者，但是在coffeescript中，其实类和变量都是通过`var`关键词生成的变量，而在coffeescript语法中又禁用了`var`（这样就无法人为的指定变量的作用域，虽然coffeescript会比较智能的分配的作用域）。这在一般情况下也没有问题，直到碰到了下面的代码：

{% codeblock lang:coffeescript %}
class demo
  foo1: ->
    console.log demo
    return @
  foo2: ->
    demo = []
    return @

new demo().foo1().foo2().foo1()

==> [Function: demo]
==> []
{% endcodeblock %}

同样的两次调用foo1方法，得到的结果却是不同的，这是因为foo2中的变量与类名冲突了，而且他们处于同一个作用域，这样foo2方法就变成了一个隐藏的地雷，踩到就爆炸。避免这种情况的一种做法是在命名上做区分，比如类命名必须以大字母开头，变量必须以小写字母开头，这样就不会造成这两者的混淆。

## 类成员变量

使用类的一个好处就是可以初始化一些变量，让这个类的所有方法共享，而又不会影响外层作用域。但是需要注意的是，javascript中对于数组和对象是引用传递，在coffeescript类中使用这两种类型作为成员变量时，就会产生一些不曾期待的后果。

{% codeblock lang:coffeescript %}
class Demo
  member: []
  setMember: (str) ->
    @member.push(str)

a = new Demo
a.setMember('a')
console.log a.member  # ['a']
b = new Demo
console.log b.member  # ['a']
{% endcodeblock %}

当我们使用`new`关键词的时候，希望得到的是一个干干净净的对象，可是在初始化b的时候我们发现他的成员变量member已经变成了`['a']`，这是我们不希望看到的。究其原因就是member是一个数组。解决办法是将这些变量的初始化放在coffeescript的构造方法`constructor`中。

{% codeblock lang:coffeescript %}
class Demo
  constructor: ->
    @member = []
  setMember: (str) ->
    @member.push(str)

a = new Demo
a.setMember('a')
console.log a.member  # ['a']
b = new Demo
console.log b.member  # []
{% endcodeblock %}

至于为什么这两种写法会产生不一样的效果，可以将coffeescript编译成js来分析。

{% codeblock lang:javascript %}
    (use constructor)                                             |    (not use constructor)
    5   Demo = (function() {                                      |    5   Demo = (function() {
    6     function Demo() {                                       |    6     function Demo() {}                                     
    7       this.member = [];                                     |    7                                                            
    8     }                                                       |    8     Demo.prototype.member = [];                            
    9                                                             |    9 
   10     Demo.prototype.setMember = function(str) {              |   10     Demo.prototype.setMember = function(str) {
   11       return this.member.push(str);                         |   11       return this.member.push(str);
   12     };                                                      |   12     };                     
{% endcodeblock %}

上面是vimdiff对比出的两种不同写法，第一种是使用构造方法`constructor`的，可以看到member作为Demo方法的私有变量，在没有用`new`实例化的时候，这个`member`是不存在的，所以每一次实例化我们都能得到一个全新未开箱的`member`。但是第二种写法则不同，在没有实例化Demo类的时候，`member`对象就已经存在，所有无论你实例化Demo多少次，调用的都是同一个`member`，也就造成了在多个Demo实例中共用一个`member`的结果。

# 后记

假如让我在javascript和coffeescript两种语言之间选择，我仍然倾向于coffeescript，抛开上面的问题不说，它给人编码的时候带来的愉悦是无法衡量的。So just try it!
---
layout: post
title: "执行 javascript 方法的几种方式"
date: 2013-05-02 12:41
comments: true
categories: [javascript]
---

javascript语法灵活，同一个功能有五六种实现方式并不罕见，然后再加上有些反人类的原型继承和异步特性，就更让人一头雾水了。我经常搞不清楚`call`,`apply`之间的区别，今天就记录一下，以免再忘了。

在javascript中，方法可以通过以下几种方式执行：

* func()，这是最直接最常见的调用方式，也符合一般人的思维逻辑，但是在某些情况下有一些不足，下面会解释。
* (function(arg){})(window)，匿名方法调用，在构造命名空间时比较有用，后面的括号中的参数与匿名方法中的入参一一对应。
* func.bind(sth)()，[mozilla手册](https://developer.mozilla.org/en-US/docs/JavaScript/Reference/Global_Objects/Function/bind)中提到`bind`是在[ECMA-262 5th Edition](http://www.ecma-international.org/publications/standards/Ecma-262.htm)中新增的一个特性，这里单独列出来作为一种调用方式是因为它弥补了直接调用中不能绑定作用域的缺陷。
* func.call()，这是第二种调用方式，每个方法的原型中都定义了call方法，用来执行当前方法。
* func.apply()，call的双胞胎兄弟。

## func()
这是最常见的调用方式，在任何语言中随处可见。func(x, y)可以传入不同的参数。在某些语言，例如php，java中，这种调用足以解决一切问题。但是javascript是一门函数式语言，闭包的概念和一个奇怪的关键词`this`决定了这种调用方式的不足。`this`应该可以解释为当前代码段的作用域，会随着代码执行到不同的片段而改变，但是某些情况下我们不希望这个`this`被改变，例如绑定在某些dom上的事件，我们肯定不希望他们被调用的时候`this`被转移到了`window`对象上，但有时候确实如此，再比如下面的代码。

{% codeblock lang:javascript %}
var a ={};
var func = function(x) {
    console.log(this);
};
a.onclick = function() {
    var x = 100;
    func(x);
};
a.onclick();
{% endcodeblock %}

可以把a想象成页面中的一个链接，由于我们只是想将定义好的方法绑定到onclick事件上，而不是立刻调用它，而且这个方法拥有一个参数，所以我们需要用一个匿名方法将他包起来传递给a的onclick事件。这样就有了一个问题，func中的this变成了全局对象window，显然我们并不希望如此。这个时候，使用func()这种直接调用的方式就不行了，于是我们需要将func外的this绑定到func方法上。于是就有了`bind`,`call`,`apply`方法。

## bind

`bind`的目的非常简单，返回一个绑定了this对象的相同方法。上面的代码修改一行就可以实现绑定this在a对象上目的。

{% codeblock lang:javascript %}
var a ={};
var func = function(x) {
    console.log(this);
};
a.onclick = function() {
    var x = 100;
    func.bind(this)(x);  // bind here
};
a.onclick();
{% endcodeblock %}

这样，onclick事件的this就不会像无头苍蝇一样到处乱跑啦。

## call & apply

`call`和`apply`要放在一起讲，因为他们实在太像了。他们都支持多参数，而且第一个参数都是即将绑定的this对象，第二个参数则是他们的区别所在，`call`使用独立的参数作为调用方法的入参，`apply`使用一个数组作为入参。有的时候我们并不是不想改变this对象，而是想人为的将他绑定到别的对象上，这个时候`call`和`apply`是很好用的。（并不是说不能用`bind`，不过貌似`bind`出现的比较晚，可能浏览器兼容性不好）。举个栗子：

{% codeblock lang:javascript %}
a = {
    func: function() {
              this.x += 1;
          },
    x: 0
};
b = {
    a: a,
    x: 20
};
for(var i = 0; i < 10; i++){
    b.a.func();
}
console.log(a.x);
console.log(b.x);
{% endcodeblock %}

上面的a和b对象中都有x，我们希望func能针对性的修改对应的x，但是直接调用只可能修改func作用域中的x，也就是a.x。修改一下代码，就可以实现修改b.x目的

{% codeblock lang:javascript %}
a = {
    func: function() {
              this.x += 1;
          },
    x: 0
};
b = {
    a: a,
    x: 20
};
for(var i = 0; i < 10; i++){
    b.a.func.call(b);  // bind this to b
}
console.log(a.x);
console.log(b.x);
{% endcodeblock %}

这个栗子举得不好，有点牵强附会，而且这是一种很容易让人迷惑的代码风格，有适用的场景，但不是处处都可用。

# 参考资料
* [mozilla](https://developer.mozilla.org/en-US/docs/JavaScript/Reference)
* [Nick Fitzgerald's Weblog](http://fitzgeraldnick.com/weblog/26/)
* [DailyJs](http://dailyjs.com/2012/06/25/this-binding/)
* [stackoverflow](http://stackoverflow.com/questions/1986896/what-is-the-difference-between-call-and-apply)

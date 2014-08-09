---
layout: post
title: "一个例子验证 do 在 coffeescript 中干了什么"
date: 2013-08-07 17:20
comments: true
categories: [coffee, nodejs, closure]
---

使用jslint的时候有可能会见到这样的提示

> Don't make functions within a loop

一直没有太在意这个警告，直到最近做项目的时候还真的碰到了因为这个问题产生的bug。

那么下面就用一个例子来看看在循环中定义方法会产生什么样的后果吧。

{% codeblock lang:coffeescript %}
array = [1, 2, 3]
for num in array
  setTimeout (-> console.log num), 1
{% endcodeblock %}

得到的结果是'3,3,3'，而不是预期的'1,2,3'，先不说为什么，我们来看看coffeescript给出的解决方案。

{% codeblock lang:coffeescript %}
array = [1, 2, 3]
for num in array
    do (num) ->
        setTimeout (-> console.log num), 1
{% endcodeblock %}

在这里不得不佩服[Jeremy Ashkenas](https://github.com/jashkenas)的无限创造力，短短一个`do`，就解决了这么让人纠结的问题。下面来看看编译成javascript之后的结果

{% codeblock lang:javascript %}
(function() {
  var array, num, _fn, _i, _len;

  array = [1, 2, 3];

  _fn = function(num) {
    return setTimeout((function() {
      return console.log(num);
    }), 1);
  };
  for (_i = 0, _len = array.length; _i < _len; _i++) {
    num = array[_i];
    _fn(num);
  }

}).call(this);
{% endcodeblock %}

下面我们来解释一下为什么上面的代码会有问题，以及这个`do`为我们做了些啥。

关于javascript的作用域，我们可以看一下[这篇文章的引用](http://rzrsharp.net/2011/06/27/what-does-coffeescripts-do-do.html)

> JavaScript’s scopes are function-level, not block-level, and creating a closure just means that the enclosing scope gets added to the lexical environment of the enclosed function.

大意是说

> JavaScript的作用域是方法级别，而非块级的。创造一个闭包可以将作用域限定在这个封闭的方法中

这里的`for..in`循环在其他语言中就是一个块级的作用域，但是Javascript并不买它的帐，于是最后在方法中调用的num就变成了整个作用域中最后的状态(3)。解决的办法就是在循环中创建闭包，让num当成参数传入闭包，那么它在方法作用域中就不会受外部的变化而改变(实际上完全可以当成一个新的变量，不信你传个object进去，在闭包中的任何修改，都不会对外部作用域的object产生影响的)。

coffeescript用`do`关键字为我们将这种操作最简化，所以，尝试一下吧。

## 参考文档

* [Variable scope in coffeescript for loop?](http://stackoverflow.com/questions/10810815/variable-scope-in-coffeescript-for-loop)
* [What Does Coffeescript's "Do" Do?](http://rzrsharp.net/2011/06/27/what-does-coffeescripts-do-do.html)


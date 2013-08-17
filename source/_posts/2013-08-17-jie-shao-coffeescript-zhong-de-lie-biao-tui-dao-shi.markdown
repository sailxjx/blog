---
layout: post
title: "介绍 coffeescript 中的列表推导式"
date: 2013-08-17 16:58
comments: true
categories: [coffee, code style]
---

[列表推导式](http://en.wikipedia.org/wiki/List_comprehension)是一个很著名的语法结构，它的特点是能让代码更简短，优雅，而且易于阅读。捎带些函数式编程特点的语言都支持这种语法结构，例如lisp家族和python。coffeescript作为一门年轻的语言，自然而然的继承了这个特点。

我们先看看这种语法和普通循环的区别：

{% codeblock lang:coffeescript %}
arr = [1, 2, 3]

for i, v of arr  # use for..of loop
    arr1[i] = v * 2

arr2 = (v * 2 for i, v of arr)  # use list comprehension

{% endcodeblock %}

可以看到本来需要两行的代码变成了一行，这对于略有装逼犯情结的码农来说，心理上的满足感自然是无与伦比的。优点也是显而易见的，就是可读。在有些语言中，这种语法还会产生一个新的作用域，不会污染外界的变量，比方说ruby。

我们再来看看一些进阶用法，下面是带上`if`条件的列表推导式：

{% codeblock lang:coffeescript %}
arr = [1, 2, 3]
arr1 = (v * 2 for i, v of arr) if arr?
{% endcodeblock %}

一般的列表推导式返回的结果是一个一维数组，这在我们需要对某个`object`中的值做转换时会产生不便（`key`会丢失），这个时候我们可以采用一种变通的方法：

{% codeblock lang:coffeescript %}
obj = {a: 'a', b: 'b'}
obj1[k] = v + v for k, v of obj1 if obj1?
{% endcodeblock %}

没加两边的括号和加了括号是有区别的，像上面这种结构，可以理解为将`for..of`结构中的第二行搬到了等号左边，其中的临时变量`k, v`当然也是可以直接使用的，而且后面`if`条件是对整个循环生效的，而不是单独加在每个循环中的，比较好理解吧。

熟练掌握了列表推导式之后，编写代码的时候会更加得心应手，对于代码重构，想必也是极好的。

## 相关文档

* [CoffeeScript Object Comprehensions ](http://userinexperience.com/?p=753)
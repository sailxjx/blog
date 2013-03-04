---
layout: post
title: "php的命名空间"
date: 2013-03-04 11:38
comments: true
categories: [php]
---

php中命名空间的概念出现的比较晚(>=5.3)，这也造成了很多人写php程序的时候忽略了这个问题(包括我:)，不过，最近很多老外的开源项目中渐渐流行起在php中使用命名空间。于是我也跟风一把，看看究竟好在哪儿。

在php namespace语句出现之前，通行的做法是根据文件路径定义类名，某些特殊文件再加上后缀，比如存放controller的文件夹中有两个文件，分别存放在`controller/Base.php`和`controller/login/Base.php`，根据命名可以看出这是两个基类文件，文件名是一样的，它们的类名一般会写成`BaseController`和`Login_BaseController`(首字母大写也是一种约定)，这样就不存在类名冲突的问题了。这样做的一个缺点是类名会很长，用某些人的说法，就是不够优雅，于是命名空间就应运而生了。

命名空间的使用在别的语言中的使用历史不算短，其中微软系的c#由盛。在php中基本是照搬这种思想，下面先举个简单的栗子：

{% codeblock myname1.php lang:php %}
<?php
namespace my\name1;
class MyClass {} //定义类
function myfunction() {echo "helloworld";} //定义方法
const MYCONST = 1; //定义常量
{% endcodeblock %}

假如我们有另一个php文件myname2.php，需要include myname1.php。用下面几种方式是可以的。

1. 在同一namespace下
{% codeblock lang:php %}
<?php
namespace my\name1;
include 'myname1.php';
print_r(new MyClass);
{% endcodeblock %}
从输出结果可以看出实例名已经变成了`my\name1\MyClass Object`，命名空间会自动的加到类名前面。

2. 在不同namespace下用`use`
{% codeblock lang:php %}
namespace my\name2; //不同的命名空间
include 'myname1.php';
use my\name1; //使用命名空间
print_r(\my\name1\MYCONST); //这里用调用常量举例
{% endcodeblock %}
需要注意的是，在使用namespace前需要以`\`开头，否则会自动加到当前的namespace后面，变成`my\name2\my\name1\MYCONST`，也就得不到正确的结果了。这个有点类似url或*nux系统中用`/`来表示PATH的根目录。

3. 在不同namespace下用`use .. as ..`
{% codeblock lang:php %}
namespace my\name2;
include 'myname1.php';
use my\name1 as m1; //使用命名空间别名
m1\myfunction(); //调用方法举例
{% endcodeblock %}
这就是命名空间的真正益处了，短啊~不过别名前就不能用`\`符号了，否则会把`m1`当做已存在的命名空间来处理。

###P.S.
* php5.3之后还有一个全局常量`__NAMESPACE__`来表明当前文件所在的命名空间，至于用处？谁知道呢。
* 在同一个文件中是可以使用多个namespace的。
* 假如在命名空间中定义了与全局方法同名的方法，可以使用在前面加`\`符号的方式调用全局方法，例如在`fopen`中用`\fopen()`。而不使用命名空间的时候，重复定义是不允许的。遇到重定义常量也是一样。



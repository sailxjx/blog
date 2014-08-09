---
layout: post
title: "细数 php 中的那些坑"
date: 2013-05-10 15:59
comments: true
categories: [php]
---

Wow，有逮到一个黑PHP的，作为“宇宙中最好的编程语言”，被黑只会加速它的改进，所以偶尔黑一下也无妨嘛~正所谓世界上只有两种语言，一种是被人黑的，另一种是没人用的。

进入正题，下面开始罗列一些PHP需要防备的坑，以免一不小心掉了进去。(只包含在5.3中仍然存在的，5.4中已修复的会做一下说明)。

## 变量类型
说PHP入门门槛低，其中一个原因是我们不需要关心变量的类型，PHP为我们做了自动的转化。但事实上是这样吗？下面就是一个隐蔽的bug。

{% codeblock lang:php %}
<?php
var_dump('string' == 0);
?>
==> true
{% endcodeblock %}

很神奇吧，php在这里自动将字符串作为整数0来比较了。由此引发了一系列问题：

{% codeblock lang:php %}
<?php
$a = "1234567";
var_dump($a['test']);
==> 1  //坑爹的等同于$a[0], php5.4会给出一个非法索引warning，但是仍然返回1，php5.3则连warning都没有。
var_dump(isset($a['test']));
==> true  //这是一个完全错误的结果，在php5.4中得到修复
var_dump(in_array(0, array('xxx')));
==> true  //这也是一个令人费解的bug，暂且还是理解为php将'xxx'转化成0了吧
var_dump(in_array(false, array('xxx')));
==> false  //与之相对的，这个结果确是符合预料的。问题是，在php中0==false呀。
?>
{% endcodeblock %}

所以当一个表达式中同时包含0和字符串的时候就要特别留意了，以免被奇葩的bug坑了。

## 比较符号

### 等号`==`与不等`!=`

严格的来说，PHP中的`==`符号完全没有作用，因为`'string' == true`，而且`'string' == 0`，<strong>但是</strong>，`true != 0`。

然后是`123 == "123foo"`，但是当你用引号将123包起来以明确说明这是一个字符串时，`'123' != "123foo"`，但是在现实中，谁会用常量比较呢，这个123换成了一个变量，而根据php的哲学，谁会在意这个变量是什么类型。

在使用其他进制的时候这种混淆尤其明显，像下面的例子:
{% codeblock lang:php %}
<?php
"133" == "0133";
133 == "0133";
133 == 0133;    //因为0133是一个八进制数，转成十进制是91
"0133" != 91;   //字符串中的数字始终是十进制的，这个也可以理解
"0x10" == 16;   //但是!，在十六进制中上面的说法又不成立了
"1e3" == 1000;  //科学计数表示也一样
?>
{% endcodeblock %}

### 大于`>`小于`<`

被搞糊涂的话，下面还有：

{% codeblock lang:php %}
<?php
null == 0;  //这个可以理解
null < -1;  //但是你会想到这个吗？难道zend认为0<-1？
?>
{% endcodeblock %}

使用`==`时，参照javascript中的做法，我们可以使用`===`让比较更规范一些，但是`>`,`<`这些怎么办？只能尽量避免。

## 三元运算符

很多语言都提供了三元运算符`?:`，因为它足够简洁，也够geek。但是php中的表现与其他语言中又不同：

{% codeblock lang:php %}
<?php
$arg = 'T';
$vehicle = ( ( $arg == 'B' ) ? 'bus' :
             ( $arg == 'A' ) ? 'airplane' :
             ( $arg == 'T' ) ? 'train' :
             ( $arg == 'C' ) ? 'car' :
             ( $arg == 'H' ) ? 'horse' :
             'feet' );
echo $vehicle;
?>
{% endcodeblock %}

猜猜结果是什么？'horse'，而我们预料中的（在其他语言中）应该是'train'。这种做法被称为"left associative"(左结合)，也即上面的表达式在php看来等同于`$vehicle = (condition) ? 'horse' : 'feet'`，所以你永远不可能得到中间的结果，这有违人的第一感觉，也与其他语言正好相反。

## empty

empty是我在php中非常喜欢的一个方法，这里我说错了，其实它是一个语言结构，而不是一个方法，但是它的使用方式又像一个方法。于是，empty()中包含运算式的话是会报错的，这也造成了它的局限性。

## 数组

php中的数组应该是一个'数组','hash表','集合'的结合体，这在使用上有它的方便之处，但是也造成了一些不易理解的地方，尤其体现在一些array相关的方法上。

{% codeblock lang:php %}
<?php
array("foo", "bar") != array("bar", "foo");  //这个时候，array就是数组
array("foo" => 1, "bar" => 2) == array("bar" => 2, "foo" => 1);  //这个时候，array又变成了无序hash表
?>
{% endcodeblock %}

好吧，其实php中的array就是一个hash表，无论什么情况下。`array('bar', 'foo')`就是`array('0'=>'bar', '1'=>'foo')`，这样对理解array的一些奇怪表现应该会有帮助，但是下面的方法，有推翻了上面的论述，我将它算做一个bug：

{% codeblock lang:php %}
<?php
$first  = array("foo" => 123, "bar" => 456);
$second = array("foo" => 456, "bar" => 123);
var_dump(array_diff($first, $second));
==> array()
?>
{% endcodeblock %}

看到了吧，如果array是个hash表的话，$first和$second显然是不一样的。但是diff的结果却认为这是两个一样的数组。所以在愉快的使用array时，别忘了停下来，测试一下这些隐含的bug。

## `++`与`--`

{% codeblock lang:php %}
<?php
$a = null;
$a ++;  // $a == 1, 可以理解
$a = null;
$a --;  // $a == null, 凌乱了
?>
{% endcodeblock %}

解决这种`++`和`--`中的不一致的办法就是根本不用它们，用`+=`和`-=`代替。

## 命名习惯

下面这些就与bug无关了，而是php中过于随意的命名规则和变量顺序，像`htmlentities`和`html_entity_decode`，你能想象这两个方法是一张纸的两面吗？

其实方法的命名不规范问题也不大，但是变量顺序混乱就要人命了，比如array中的一大堆方法，看起来都以array打头，相当清晰。但是你能料到`array_filter($input, $callback)`和`array_map($callback, $input)`两个方法的回调方法位置正好相反吗？我就经常记错`strpos`中哪个参数才是该被搜索的字符串。

还有一个要命的地方就是很多参数的引用传递不明，比如上面的`array_filter`是对源对象的一个拷贝，但是`array_walk`却是一个引用。这些在[php.net](http://www.php.net/)上有“粗略”的说明，我想谁也不希望一边翻文档一边写代码吧。

## 错误控制

关于php的错误控制其实是一个关于“配置优于约定”还是“约定优于配置”的讨论，而显然php选择了前者。在php中，有一个全局的配置文件“php.ini”，里面关于错误控制的两个选项`error_reporting`和`display_errors`分别代表错误等级和是否显示错误。

这没有问题，问题是这些配置在运行时是可以修改的。有些框架或有些人为了掩盖无处不在的`notice`，将`error_reporting`等级调高，或者索性将`display_errors`设置为`off`，这会让其他开发者困惑，让错误无迹可寻。

这也不是什么问题，最大的问题是php中还有`set_error_handler`和`set_exception_handler`，看字面意思，这两个都是用来控制错误的。但是在php中，`error`和`exception`却是不一样的。于是你要么在框架里写上两套一样的`handler`方法，要么就索性都不写，由php去决定这些错误该以什么形式表现。最糟糕的是只写一个或者写两套不一样的`handler`，这回让人困惑为什么有些错误以一种形式表现，而一些错误又以别的方式出现，而且要找到这些`handler`也不是一件容易的事，他们可能出现在任何一个文件的任何一个角落，除非你用`debug_backtrace`将堆栈信息都打印出来。

## 后记

以上大部分内容和例子来自[PHP: a fractal of bad design](http://me.veekun.com/blog/2012/04/09/php-a-fractal-of-bad-design/)，一个狂热的python教徒。我不是python教徒，甚至连爱好者都说不上，所以写这篇文章只是为了记录php中存在的bug，以防一不小心被坑了。只有有了这些意识，才能在这门语言中更好的发挥，物尽其用。

# 相关链接
[PHP: a fractal of bad design](http://me.veekun.com/blog/2012/04/09/php-a-fractal-of-bad-design/)
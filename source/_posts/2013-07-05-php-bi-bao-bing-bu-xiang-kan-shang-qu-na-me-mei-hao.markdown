---
layout: post
title: "php 闭包：并不像看上去那么美好"
date: 2013-07-05 10:27
comments: true
categories: [php, closure]
---

最近一个叫[laravel](http://laravel.com/)的php框架在社区讨论的风生水起，号称php界的rails，试用了一下，确实非常新鲜，但是又有种似曾相识的感觉。

例如路由中的一段代码：

{% codeblock lang:php %}
<?php
Route::get('/', function()
{
    return View::make('hello');
});
?>
{% endcodeblock %}

兄弟，你走错了，隔壁javascript出门左拐。

laravel号称将php5.3中新引入的闭包发扬光大，让代码变得更加灵活优雅，趣味十足。

在我看来，坑更多了。

为什么说php闭包没有看上去那么美好，因为他的生搬硬套。

闭包这个概念早已不新鲜，在函数是语言中被早已被用烂了，以至于现在lisp教徒抨击其他语言时都避而不谈closure和lambda，转而讨论currying，otp，metaprogramming等等更玄乎的东西。

php的闭包不能说引入的太晚，没有跟上编程发展的脚步，其实在php4时代，就已经有了这样的概念，`call_user_func`，`array_map`等等方法都是支持callback方法的。但是时至今日，它依然是不完善的。

在发展的过程中，php引入了很多舶来品，例如接口，命名空间，异常控制等等，每种都是对自身语言已有编程风格的颠覆，以至于现在同样是编写php，不同的人能写出完全不同风格的代码。自然闭包也非原创，同样很怪异。

# 作用域

在javascript中，闭包内的变量是继承上层的，这是一种很自然的做法，也相当的灵活。但是php有自身的一套作用域规则，于是在闭包中使用变量就变得非常怪异，例如下面的例子：

{% codeblock lang:php %}
<?php
$v = 1;
$arr = [1, 2, 3];
array_map(function($n) use ($v) {
    if ($n == $v) {
        echo 'exist';
    }
    return false;
}, $arr);
?>
{% endcodeblock %}

这个`use`就是用来解决作用域的问题的，使用时可得瞧准咯，每个要用到的变量都得用`use`引入哦。然后当闭包身处类中时，情况又不一样了，下面的做法在php5.3中是错误的：

{% codeblock lang:php %}
<?php
class Demo {

    protected $val = 'v';

    public function getClosure() {
        return function() {
            return $this->val;
        };
    }
}

$d = new Demo();
echo call_user_func($d->getClosure()), "\n";
?>
{% endcodeblock %}

因为php5.3不支持在闭包中使用`$this`或`self`关键字，但是在php5.4中得到了支持，所以上面的代码是可运行的，但是这让上面第一个例子情何以堪呢。

# 绑定

在5.4之后，php开始支持将一个闭包绑定到别的对象上，以便能直接调用这个对象的成员：

{% codeblock lang:php %}
<?php

class Clo {

    public function __construct($val) {
        $this->val = $val;
    }

    function getClosure() {
        return function() {
            echo $this->val, "\n";
        };
    }
}

class Wrap {
    protected $val = 3;

    public function bar($foo) {
        call_user_func($foo);
    }
}

$a = new Clo(1);
$wrap = new Wrap();
call_user_func($a->getClosure());
call_user_func($a->getClosure()->bindTo($wrap, $wrap));
?>
{% endcodeblock %}

这在某些场景下有用武之地，不过也要注意作用域，`bindTo`方法中的第二个参数是设置作用域的，向上面`Wrap`类中的变量`$val`是私有的，假如没有`bindTo`的第二个参数，是行不通滴。

# Generator

顺便再提一下php5.5中新增的Generator，其中的yield支持运行时自定义方法，这显然又是从隔壁python借鉴来的，调用方式同样不是很自然，foreach承担了迭代的责任，相比于ruby中yield的强大功能，更是差之千里了。

不过有总比没有要好，将来一定有创意丰富的人能玩出更多花样，哦，貌似又多了一种编程风格。
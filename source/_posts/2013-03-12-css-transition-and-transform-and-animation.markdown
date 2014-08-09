---
layout: post
title: "css transition &amp; transform &amp; animation"
date: 2013-03-12 16:41
comments: true
categories: [css, web]
---

<link rel="stylesheet" href="/u/css/trans.css">

小时候没花功夫学英语（悔之莫及啊~），以至于经常将css中的transition和transform搞混，再加上一个强大而复杂的animation，就更是一头雾水了，今天整理一下，做个笔记。

* `transition` 将style的变化用动画的方式来过渡
* `transform` style的一些高级变换，像拉伸，旋转，缩放之类
* `animation` css自定义动画，功能强大，并且由于是浏览器内置，流畅度会比javascript动画高

注意咯，以上css属性都不支持IE浏览器，其中，IE9以上支持`transform`，需要加'-ms-'前缀。

## transition
transition可以在同一dom元素的两种不同样式之间添加平滑的动画切换效果，下面是一个简单的例子，可以看出在没transition和有transition的情况下的区别。

<div>
<div class="color-transition color-demo">color<br/>transition</div>
<div class="color-notransition color-demo">color<br/>notransition</div>
</div>

transition是一种比较好的用户体验，不会让用户对于style的切换感到突兀，要实现上面的效果，只需要在css中添加一行代码：

{% codeblock lang:css %}
.color-transition {
    transition: background 0.7s;
    -moz-transition: background 0.7s;
    -webkit-transition: background 0.7s;
    -o-transition: background 0.7s;
}
{% endcodeblock %}

你可能在心里暗暗骂我SB（嗯，对的，我也讨厌不会数数的淫），上面明明有四行代码，这个也没办法啦，谁让各个浏览器对于css3标准的支持都不一样，添加不同的prefix只是为了兼容一些较低版本的浏览器，在目前的主流中，一行transition就可以走遍天下。（当然，IE是没救了）。

transition中支持的参数是比较容易理解的，第一个是css中的属性(默认的all可以决定所有style的切换都显示成动画)，第一个表示过渡时间，还有第三个参数表示切换的动画类型，有ease(类似正弦曲线的先慢后快再慢)，linear(匀速)等等，第四个参数表示渐变开始的延时。而且可以用逗号分割多个属性，不嫌麻烦的话可以将它们都写上，就像下面这样。

{% codeblock lang:css %}
.color-ratate-transition {
    transition: background 0.7s ease-in-out, rotate 3s;
}
{% endcodeblock %}

上面既然已经用到了rotate，这是一个transform属性，下面就解释一下transform。

## transform

transform是对dom样式的一种变性，一般的样式中，我们可以定义宽高位置，甚至包括圆角。但是要实现旋转，缩放甚至一些3D效果，就需要用到transform了。下面还是先看一个例子：

<div>
<div class="transform-rotate transform-demo">rotate</div>
<div class="transform-scale transform-demo">scale</div>
</div>

rotate可以在很多场景中得到应用，一个比较取巧的方法是用来做输入框的提示箭头，像下面这样.

<div>
    <div class="rotate-arrow"></div>
    <div class="input-tip">your messages</div>
</div>

而scale的作用是在不调整宽高的情况下实现放大与缩小，灵活利用，也能实现一些有趣的功能，比如放大镜，异形字体等。

transform的语法是非常简单的，它只有一个参数，但是形式的是很多变的，详细的列表可见于[w3school的页面](http://www.w3schools.com/cssref/css3_pr_transform.asp)。

## animation

下面是重点要介绍的animation了。用css做动画是意见非常geek的事情，相比于js动画，css动画需要更少的代码以及更直观的语法。在做css动画之前，需要先了解keyframes属性，这是css3中新增的一个规则类，实现的方法类似于一些语言中的mixin，而在设计理念上又类似flash中的关键帧，在keyframes中需要定义几个关键帧中dom元素的样式，这几个关键帧可以用百分比来表示，也可以用from,to等一些词汇来表示起点和终点。下面的keyframes表示让一个元素从左向右移动，并加上一些旋转效果。

{% codeblock lang:css %}
@keyframes move {
  0%, 100% {
    left: 0;
    transform: rotate(0);
    -moz-transform: rotate(0);
    -webkit-transform: rotate(0);
    -o-transition: rotate(0);
  }

  50% {
    left: 300px;
    transform: rotate(180deg);
    -moz-transform: rotate(180deg);
    -webkit-transform: rotate(180deg);
    -o-transition: rotate(180deg);
  }
}
{% endcodeblock %}

keyframes是一个预定义的方法，将它与animation结合，就能实现有趣的css动画，例如上面名叫move的keyframes，放到下面的代码中，就可以看到完整的动画演示了。

{% codeblock lang:css %}
.animate-move {
  position: relative;
  animation: move 5s infinite;
  -moz-animation: move 5s infinite;
  -webkit-animation: move 5s infinite;
  -o-animation: move 5s infinite;
}
{% endcodeblock %}

<div>
    <div class="animate-move animate-demo">animate<br/>move</div>
</div>

上面的例子中包含了animation中的三个参数：keyframes方法名，一次动画持续时间，循环次数。其中无限循环用infinite表示。animation还包含不少有用的参数，比如延迟时间，动画效果等等，在[w3school手册](http://www.w3schools.com/cssref/css3_pr_animation.asp)中有详细的记载。

国内音乐网站[jing.fm](http://jing.fm/)在交互方面一直很前卫，播放音乐时，专辑封面会像真的在cd中一样打转，模仿一种收听cd的感觉。这个效果就是用animation实现的，下面我们也可以山寨一个玩玩。

### 第一步，打磨圆角

首先需要的是将正方形的专辑封面变成cd的形状，也就是圆形啦。

{% codeblock lang:css %}
.album {
  width: 300px;
  height: 300px;
  border-radius: 150px;
}
{% endcodeblock %}

专辑封面我们选择300像素的正方形，然后使用150像素的圆角，可以正好变成一个正圆形。

### 第二步，keyframes

下面就要定义我们的keyframes了，这个动画很简单，在开头设置成rotate(0)，结尾设置成rotate(360deg)。这样我们比较容易计算循环的时间。

{% codeblock lang:css %}
@keyframes dj {
  0% {
    transform: rotate(0);
    -moz-transform: rotate(0);
    -webkit-transform: rotate(0);
    -o-transition: rotate(0);
  }

  100% {
    transform: rotate(360deg);
    -moz-transform: rotate(360deg);
    -webkit-transform: rotate(360deg);
    -o-transition: rotate(360deg);
  }
}
{% endcodeblock %}

### 第三步，转起来

将我们的dj keyframes加入animation，选择匀速(linear)，下面就看效果吧。

{% codeblock lang:css %}
.album {
  width: 300px;
  height: 300px;
  border-radius: 150px;
  animation: dj 10s infinite linear;
  -moz-animation: dj 10s infinite linear;
  -webkit-animation: dj 10s infinite linear;
  -o-animation: dj 10s infinite linear;
}
{% endcodeblock %}

<p>
    <img src="/u/image/album-milk.jpg" alt="album-milk" class="album">
</p>

<embed src="http://www.xiami.com/widget/0_1771372335/singlePlayer.swf" type="application/x-shockwave-flash" width="257" height="33" wmode="transparent" />

## 后记

上面的效果没有用到一行js。css真是将技术与艺术完美结合的产物，这些动画无疑需要激发人无限的创意，随着浏览器的支持度提高，在web上的应用也会越来越广，应用的明天会非常美好。

## 代码

在自己的项目中，我会使用scss来书写css，省却了很多重复的工作。这个页面的scss文件放到这里保存一下。

[trans.scss](/u/scss/trans.scss)
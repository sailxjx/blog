---
layout: post
title: "javascript 中使用 callback 控制流程"
date: 2013-06-05 10:49
comments: true
categories: [javascript, async]
---

javascript中随处可见的callback对于流程控制来说是一场灾难，缺点显而易见：

* 没有显式的`return`，容易产生多余流程，以及由此引发的bug。
* 造成代码无限嵌套，难以阅读。

下面就来说说怎么<del>解决</del>避免上述的问题。

第一个问题是一个习惯问题，在使用callback的时候往往会让人忘了使用`return`，这种情况在使用coffee-script的时候尤甚（虽然它在编译成javascript时会自行收集最后的数据作为返回值，但是这个返回值并不一定代表你的初衷）。看看下面的例子。

{% codeblock lang:coffeescript %}
a = (err, callback)->
  callback() if err?
  console.log 'you will see me'

b = ->
  console.log 'I am a callback'

a('error', b)
{% endcodeblock %}

在这种所谓"error first"的代码风格中，显然我们不希望出错时方法`a`中的后续代码仍然被执行，但是又不希望用`throw`来让整个进程挂掉（要死也得优雅的死嘛~），那么上面的代码就会产生bug。

一种解决方案就是老老实实的写`if...else...`，但是我更倾向于下面的做法：

{% codeblock lang:coffeescript %}
a = (err, callback)->
  return callback() if err?
  console.log 'you will not see me'

b = ->
  console.log 'I am a callback'

a('error', b)
{% endcodeblock %}

javascript异步方法中的返回值大多没什么用处，所以这里用`return`充当一个流程控制的角色，比`if...else...`更少的代码，但是更加清晰。

第二个问题是娘胎里带来的，很难根除。

一种不错的方法是使用一些流程控制模块来将代码显得更加有条理，比如[async](https://github.com/caolan/async)就是一个不错的模块，提供了一系列的接口，包括迭代，循环，和一些条件语句，甚至还包含了一个队列系统。下面的例子可以表名两种写法的优劣

{% codeblock lang:coffeescript %}
#normal

first = (callback)->
  console.log 'I am the first function'
  callback()

second = (callback)->
  console.log 'I am the second function'
  callback()

third = ()->
  console.log 'I am the third function'

first ->
  second ->
    third()

# use async

async = require('async')

async.waterfall [
  first,
  second,
  third
], (err)->
{% endcodeblock %}

作为睿智的你，会选择哪一种呢。
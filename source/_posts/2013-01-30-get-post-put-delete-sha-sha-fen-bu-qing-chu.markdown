---
layout: post
title: "get post put delete 傻傻分不清楚"
date: 2013-01-30 12:41
comments: true
categories: [http, rest]
---

最近看了一些REST API设计的文章，被http中的四种请求类型搞的晕头转向，记录一下，以免忘记。

##准备工具
* [Chrome插件REST Console](https://chrome.google.com/webstore/detail/rest-console/cokgbflfommojglbmbpenpphppikmonn?utm_source=chrome-ntp-icon)
* 一个用于接收参数的php文件
{% codeblock lang:php %}
<?php
print_r($_REQUEST);
{% endcodeblock %}

##开始
在通常的lamp开发中，我们最常用到的两种类型是`GET`和`POST`，例如用户注册的表单我们会通过`POST`方式提交到服务器，而一般的ajax接口我们通过`GET`方式调用。另两种方式不常用，根据w3c关于http1.1的草案，`PUT`在对象存在的时候用于更新，不存在时与`POST`相同，用于新建，`DELETE`顾名思义就是删除对象了。

这也解释了为什么我们在一般的web接口设计中(即使想尽量迎合`restful`)很少用到`PUT`和`DELETE`了，就拿用户账号来说，一般我们的服务器api清楚用户需要做什么，一般分为以下几种情况。

* 注册： 用户初来乍到，肯定是新建资料，这个时候用`POST`
* 登录： 登录需要获取用户信息和密码，用`GET`
* 修改账户： 修改的操作比较纠结，按照`rest`的设计风格，应该用`PUT`，但是我们一般在服务器端会先做校验，而且当用户信息不存在的情况下不会主动去做创建的操作，而是返回错误信息，更重要的一点是，目前的表单中只支持`GET`和`POST`两种方式，所以这个时候一般还是用`POST`。
* 注销账户： 注销账户的情况比较少，而且一般不会做硬删除(否则用户后悔了找上门来咋办捏~)，所以这个时候实质上还是更新的操作，那么同上，一般会使用`POST`。

如此看来，`PUT`和`DELETE`岂不是没有用武之地了？在很多server-to-server的api设计中，这些请求方式还是很有用的，灵活利用，可以设计出优雅易读的web-api来，[@sofish](https://github.com/sofish)在数月之前有一篇[博文](http://sofish.de/2100)很好的解释这种设计的理念和优势。

##application/x-www-form-urlencoded
为了对比`GET`和`POST`的异同，在测试过程中还有一个新发现。REST console中默认发送`application/x-www-form-urlencoded`这个请求头，于是在使用`POST`方式时，服务器端的php代码不能正确的获得参数，查看请求头，发现本来应该是`POST DATA`的地方变成了下面这样。

> Request Payload
> url=http%3A%2F%2Fwww.google.com

google了一个这个`Request Payload`，找到了[stackoverflow君](http://stackoverflow.com/questions/9597052/how-to-retrieve-request-payload)，大意是说假如header中没有`application/x-www-form-urlencoded`的话，参数不是通过表单项来传递，而是作为request body的一部分。我们的server比较死板，自然认不出这些马甲咯。

##安全与幂等
[w3c关于http method的定义](http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html)中有一段犹如天书一样的文字，解释了各种方式之间的异同，其中提到了`安全`与`幂等`两个概念，大致可以做如下解释。

* 安全性： api的目的只是从服务器获取数据，无任何新建或更新操作，就认为是安全的。例如`GET`和`HEAD`
* 幂等性： 这个比较难解释的，概括一下可以说是不管一个api调用多少次，返回的结果应该都是唯一的。比如设计的比较规范的`GET`和`DELETE`接口。

##rest api的效率问题
api的效率其实就是http的效率，可以用一个例子来说明。

redis是一个高性能的nosql数据库，但是没有提供rest api。通过tcp连接redis读写效率快的没话说，可是假如对外提供api则需要通过php等客户端做中间件。一次请求需要经过`http request->nginx->php->redis`层层深入，才能到达最终目标，降低了效率，[webdis](https://github.com/nicolasff/webdis)则通过提供redis的rest api将流程简化成了`http request->webdis->redis`，省去了中间的周折，效率自然也就上去了。

##所有method类型
[apache的interface httpmethod](http://hc.apache.org/httpclient-3.x/apidocs/org/apache/commons/httpclient/HttpMethod.html)中列举了所有已知的类型，林林总总加起来有十多项了，可以作为一个查询的索引。不过最常用的应该还是`GET`，`POST`，`PUT`，`DELETE`四种了。

##参考资料
* [RESTful_web_services](http://en.wikipedia.org/wiki/Representational_state_transfer#RESTful_web_services)
* [让牛懂琴 by sofish](http://sofish.de/2100)
* [rfc2616](http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html)
* [HTTP幂等性概念和应用 by ](http://coolshell.cn/articles/4787.html)
* [Interface HttpMethod](http://hc.apache.org/httpclient-3.x/apidocs/org/apache/commons/httpclient/HttpMethod.html)

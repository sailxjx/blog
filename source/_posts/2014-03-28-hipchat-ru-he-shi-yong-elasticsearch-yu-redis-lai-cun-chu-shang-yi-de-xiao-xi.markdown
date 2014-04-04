---
layout: post
title: "HipChat 如何使用 ElasticSearch 与 Redis 来存储上亿的消息"
date: 2014-03-28 11:52
comments: true
categories: [translation, redis, elasticsearch]
---

> 原文地址：<http://highscalability.com/blog/2014/1/6/how-hipchat-stores-and-indexes-billions-of-messages-using-el.html>

这篇文章来自一次与[Zuhaib Siddique](http://www.linkedin.com/in/zuhaib)（[HipChat](https://www.hipchat.com/)的一位工程师，创造了面向团队的群聊）的谈话。

HipChat诞生在一个充满未知的领域，企业群聊，但其实潜力无穷。所以Atlassian（创造知名软件JIRA和Confluence的公司）在2012年[收购了HipChat](http://blog.hipchat.com/2012/03/07/weve-been-acquired-by-atlassian/)

据说，假如这样一个大公司让HipChat走上了[高速发展的道路](https://s3.amazonaws.com/uploads.hipchat.com/10804/368466/10joz8sztfw4dfc/HipChat1B.jpg)。现在已经存储了多达12亿条消息，而且没隔几个月他们的消息发送，存储和索引量就会翻番。

这种增长势头给架构带来了很大的压力。HipChat用了一种通用的扩展方式。开始很简单，到了一个峰值，我们该怎么办？使用更好的电脑通常是既简单又好的方案，而且他们就是这样做的。这让他们在想出下个解决方案之前获得了一些的喘息机会。在AWS上，他们开始使用私有云，这样可以更好的横向扩展。

但是故事总是曲折的。出于安全性考虑，HipChat开始开发本地版本，作为Saas版本的一个补充。我们会在[以后讨论这个问题](http://highscalability.com/blog/2014/1/8/under-snowdens-light-software-architecture-choices-become-mu.html)

鉴于HipChat没有Google那样的规模，它非常适合用来学习怎样在瞬间索引和搜索成千上万的的消息，而在IRC和HipChat之间有哪些质的区别。在极大压力下索引和存储消息，而且不丢失消息是个很大的挑战。

这就是HipChat走过的路，而你将从中学到什么？我们先来看一下列表：

## 现状
* 每秒60条消息
* 存储了12亿条记录
* 4TB的EBS Raid硬盘
* 8个ElasticSearch服务器在AWS上
* 26台前端代理服务器，而后端服务器数量是它们的两倍
* 18名员工
* 0.5TB的搜索数据

## 平台
* 主机：75台安装了Ubuntu 12.04 LTS的AWS EC2服务器
* 数据库：CouchDb现在用来存储聊天记录，迁移到ElasticSearch。其他的用Mysql-RDS存储。
* 缓存：Redis
* 搜索：ElasticSearch
* 队列/工作服务器：Gearman（队列）和[Curler](https://github.com/powdahound/curler)（工作任务）
* 语言：Twisted Python（XMPP服务器）和PHP（前端）
* 系统配置：Open Source Chef + Fabric
* 开发工具：Capistrano
* 监控：Sensu and monit pumping alerts to Pagerduty
* 图表：statsd + Graphite

## 产品

* 负载具有突发性。在周末或节假日它可能很低，在峰值可能达到每秒数百次请求。聊天消息也不是负载的主要原因。它的状态信息（离开，空闲，在线），连接/断开连接，等等（都会影响）。所以每秒60条消息可能看起来很低，但这只是个平均值。
* HipChat想要成为你的通知中心，一个能让你和你的团队协作并且得到其他系统中所有信息的工具。帮助每个人保持联系，特别是远程办公的人们。
* 使用HipChat而不是IRC的一个很大的理由是HipChat会储存并索引所有的聊天内容，以便今后查找。搜索是你保持使用HipChat的一个重要因素。对于团队的好处是，你可以随时回来查看做过的事，回忆起曾经答应过的事。它同样会发送消息到同一用户的不同设备。当你的设备不能联网时，它会缓存并尝试重发消息。
* 更多的用户带来不断的增长，用户在不同的网站使用给它带来了更高的知名度，从API集成上也能看出增长。
* 存储和搜索消息他们系统扩展性上的主要瓶颈。
* HipChat使用XMPP，所以任何XMPP客户端均可以连接到这个系统，这在兼容性上是个巨大的优势。并且他们创造了自己的原生客户端（Windows，Linux，Mac，iOS，Android），包含了类似PDF预览，用户表情，自动注册等等扩展。
* 在不久之前将类似wiki的工具带入工作基本是不可能的。但是现在企业级应用貌似渐渐被接受了，为什么呢？
  * 基于文字的交流已经广为人知。我们有文字，即时通讯和Skype，所以使用聊天工具就显得非常自然了。
  * 分布式工作团队的崛起。团队成员的分布越来远广。我们不能把大家都聚在一起开会。所以有必要有一种方式来记录所有事情，这意味着良好的管理交流方式非常重要。
  * 增强功能。类似内嵌图片，动画之类的功能使它变得有趣，也扩展了用户范围。
* HipChat有[一个API](https://www.hipchat.com/docs/api)使创造类似[IRC机器人](http://en.wikipedia.org/wiki/Internet_Relay_Chat_bot)的工具成为了可能。一个例子是用来提交Bitbucket代码。在10:08分用户提交了x此代码来修改一个bug。他通过HipChat来发送指向代码和提交记录的链接，全自动。Bitbucket则激发了一个钩子，使用一个扩展来发布这些消息。扩展能帮你编写自己的机器人。登录你Bitbucket的账号，获取一个API口令并且当一个提交产生时发送到这个API，这跟GitHub有点像。

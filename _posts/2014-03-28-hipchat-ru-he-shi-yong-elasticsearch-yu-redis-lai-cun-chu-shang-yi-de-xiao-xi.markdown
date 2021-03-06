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
* 一开始他们用Adobe Air作为客户端平台，但是它会导致内存泄露并可能导致系统变慢。所以他们迁移到原生应用上。吃一堑长一智。他们在公司的任何部门都有跨平台的永固。你需要考虑到用户的需求，让用户在所有操作系统中都有一个较好的体验。用户至上，而不仅仅是技术。

## XMPP服务器架构
* HipChat基于XMPP开发，消息可能是[XMPP节点](http://xmpp.org/rfcs/rfc3920.html)中的任何内容，可以是一行简单的文字，也可能是一大段日志记录。他们不想在XMPP架构上多费口舌，所以并没有提及很多细节。
* 不同于选择一个第三方的XMPP服务，他们用Python的Twisted框架和XMPP库创建了自己的服务。这可以让他们更方便的创造可扩展的后端程序，用户管理和其他增值功能，而不用受制于他人的代码。
* RDS和AWS用来做用户认证和其他用得到事物和SQL的地方，这些都是久经考验的技术。对于本地部署他们使用MariaDB。
* Redis用来做缓存，存储像用户在哪个房间，在线状态等信息。所以这与你使用哪个XMPP服务无关。
  * Redis的短处是至今还没有集群。为了提高可用性，他们用Slave来做冷热备份。大概需要7分钟的时间来完成Slave到Master的切换。Slave切换是人工的，没有做到自动化。
* 与日俱增的负载暴露了代理服务器的弱点和他们能处理的客户端的上限。
  * 不丢失消息是最高的要求，这确实是个问题。用户们认为不丢失消息比低延迟更重要。用户更愿意晚一些收到消息，而不是完全收不到。
  * 用6个XMPP服务器能让系统工作的很好。随着连接数的增加他们开始看到一些延迟。连接不光来自客户端，也来自支持他们接口的机器人。
  * 第一步他们分离了前端服务器和应用服务器。代理服务器处理连接，后端应用处理节点。前端服务器的数量由活跃连接的客户端数决定，而不是由发送的消息数决定。在提供实时服务的同时保持这么多连接是一个巨大的挑战。
  * 修复了数据存储的问题后，他们计划优化连接的管理。Twisted工作的很好，但是他们的连接太多了，所以需要找出一个更好的办法来处理。

## 存储架构
* 在增长到10亿消息量级时，他们到达了CouchDB和Lucene方案能存储和搜索消息的极点。
  * Redis可能是一个故障点。他们理所当然的一位Couch/Lucene已经是一个足够好的方案，而没有考虑到消息数的增长量，超出了他们的想象。他们不应该将注意力过多的放在Redis上，而是应该转移到数据存储上。
  * 那时候他们相信增加更多AWS机器能解决问题，但是这仅仅维持了两个月，他们又到达了增长的极点。所以他们得另外想办法来解决。
  * Couch/Lucene已经很多年没有更新，而且也不太可能期待他们的更新。这是另一个需要换换思路的原因。
* 在AWS上的5亿条消息是一个转折点。用200G内存的服务器和他们旧有的架构仍能正常工作，但是不能在一个有资源限制的云服务中。
* 他们希望继续使用亚马逊的服务。
  * 喜欢它的可扩展性，只需要增加一个实例即可。
  * 亚马逊的分布式让你的开发更容易。不要把鸡蛋放进一个篮子，如果一个节点故障了你需要处理它，否则有些用户的消息将会丢失。
  * 用一个动态模型。你可能很快丢失一个实例，用新的实例取代它。云应用就是允许你在任何时候去掉一个实例。关闭一个主Redis实例，并在5分钟内回复。跨越美国东部的四个时区，而不是仅仅几个地区。
  * EBS只支持1TB的数据。他们在达到这个量级之前并不清楚这件事。他们在使用CouchDB的时候到达了EBS的磁盘上限。HipChat大概有0.5TB的数据，为了做压缩，CouchDB需要拷贝一份数据到压缩文件中，这增加了一倍的空间。在周末的一次压缩达到了2TB RAID的限制。他们原来没想到一个RAID的解决方案。
* 亚马逊DynamoDB不是一个可行方案，因为他们需要使用一个架设的防火墙后面的HipChat服务。
  * HipChat讨论了技术方案。私有版本允许你部署在自己的服务器上。这种用户不能使用云端/SaaS方案，就像银行和证券机构。NAS不喜欢外部用户。他们股用来两个工程师来创建一个可安装的版本。
  * Redis集群可以由自己来部署，它同样可以在AWS上工作。他们在私有部署的版本中用MariaDB取代了RDS。
  * 不考虑一个完全SaaS的解决方案，因为这会造成锁。
* 现在迁移到ElasticSearch。
  * 迁移到ElasticSearch来存储他们的数据和搜索后端是因为它能容得下所有数据。它只需要简单的添加节点就能实现高可用性和高扩展性。它具有多租户，并且可分片和拷贝来处理节点的故障，并且它是基于Lucene的。
  * 他们并不需要一个真正的MapReduce方案。他们同时也查看了BigCouch和Riak Search。但是ES在处理GET请求时候表现超群，ES让他们觉得会让系统更加牢不可破。
  * 兼容Lucene是一个巨大的优势，因为他们的很多查询语句已经兼容Lucene了，所以这样迁移会比较自然。
  * 用户数据千奇百怪，从简单的聊天记录到图片，所以可兼容类型是很重要的。他们需要能很快的从12亿文档中找出需要的数据。
  * HipChat同样用ElasticSearch来作为他们的key-value储存，来减少对数据库的压力。既然表现良好，他们想，为什么不使用它呢？10ms到100ms的相应时间完胜CouchDB，所以为什么还需要多种工具呢？
  * 使用ES，一个节点可以悄无声息的下线，你会在它获取平衡的时候发现一个很高的CPU使用率，但是仍然能正常工作。
  * 用8个ES节点来处理增长。
  * 基于JAVA的产品需要了解一些JVM的特点。
    * 为了使用ES，你需要计划有多少内存空间可供使用。
    * 测试缓存。ES能缓存查询结果，它非常快，但是你需要更多的内容空间。8个节点上的22G内容很容易就被耗尽，所以除非有计划的使用，尽量关闭缓存。
    * 使用缓存可能导致一个内存溢出的错误。集群可以在数分钟内恢复，只有很少的用户可能感受到这个问题。


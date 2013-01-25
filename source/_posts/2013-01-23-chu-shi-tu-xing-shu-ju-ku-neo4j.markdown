---
layout: post
title: "初试图形数据库 neo4j"
date: 2013-01-23 11:25
comments: true
categories: [neo4j, database, coffee, nodejs]
---

##nodejs bundle
官网上给出了java和python版本的实例，我等屌丝玩点轻量级的，这里找了一个[nodejs的客户端](https://github.com/thingdom/node-neo4j)，初窥图形数据库的魅力。

###创建节点及修改节点

{% codeblock lang:coffeescript %}
neo4j = require 'neo4j'     #使用coffee-script，那就尽量写的更coffee一点儿吧
db = new neo4j.GraphDatabase('http://localhost:7474') #连接默认的REST端口
db.createNode {             #初始化一个节点
    username: 'bran'
}
db.save (err, node)->       #需要save才能真正的保存这个节点到数据库
    node.data = {           #可以通过直接修改node的data属性来修改node值
        username: 'bran'
        nickname: 'bird man'
        email: 'bran@gmail.com'
    }
    node.save()             #不要忘了再次保存
{% endcodeblock %}

##参考文档
* [v1.9手册](http://docs.neo4j.org/chunked/milestone/)
* [v1.8中文开发文档](http://docs.neo4j.org.cn/)
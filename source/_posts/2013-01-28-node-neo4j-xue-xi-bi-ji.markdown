---
layout: post
title: "node-neo4j 学习笔记"
date: 2013-01-28 11:49
comments: true
categories: [nodejs, neo4j, learn]
---

##[node-neo4j](https://github.com/thingdom/node-neo4j)

###查找节点
{% codeblock lang:coffeescript %}
neo4j = require 'neo4j'
db = new neo4j.GraphDatabase('http://localhost:7474')
db.getNodeById 1, (err, node)->
    if err || !node
        console.log err
    else
        console.log node.data
{% endcodeblock %}
查找节点的api设计的很有dom的风格，与大多数nodejs方法一样，node-neo4j提供的api都是异步的，回调函数中第一个参数都是错误流，第二个因方法而异，`getNodeById`中的第二个参数node是一个完整的json对象，在这个对象上可以使用node-neo4j针对node的所有方法，要取得或修改node中的成员则可以通过`node.data`获取。

###创建关系
{% codeblock lang:coffeescript %}
# 创建节点
db.getNodeById 1, (err, n1)->
    db.getNodeById 2, (err, n2)->
       #当前节点                 目标节点 关系类型 关系结构
        n2.createRelationshipTo n1, 'isdogof', {ctime: Date.now()}, (err, r)->
            console.log r

# 查找节点
db.getRelationshipById 0, (err, rel)->
    console.log rel            
{% endcodeblock %}
目前通过`node-neo4j`创建关系只能在node上做文章，通过`createRelationshipFrom`和`createRelationshipTo`来创建点对点的关系。客户端的作者很坑爹的在Graphdatabase._coffee中声明了一个`createRelationship`方法，但是没有实现，调用这个方法是不会有任何效果的。

###查询关系
`node-neo4j`中声明了四种方式来获取关于某个节点的关系，分别是
* node.getRelationships 获取与节点相关的所有关系
* node.outgoing         获取以该节点为起点的关系
* node.incoming         获取以该节点为终点的关系
* node.all              同getRelationships
这些方法最终都调用`_getRelationships`，虽然我们也能直接调用这个方法，不过既然人家已声明其为私有，那还是直接调用上面的方法比较好。下面举例：

{% codeblock lang:coffeescript %}
db.getNodeById 2, (err, nBran)->
    db.getNodeById 1, (err, nSnow)->
        nSnow.incoming 'islittlebrotherof', (err, rel)->
            console.log rel[0].data
{% endcodeblock %}

假如关系类型(type)不存在或者没有关联到这个节点的关系，getRelationships返回rel为一个空数组。否则返回节点在这个类型的所有关系数组，`rel[0].data`则是获取关系的属性。



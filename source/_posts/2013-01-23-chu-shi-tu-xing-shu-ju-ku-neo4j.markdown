---
layout: post
title: "初试图形数据库 neo4j"
date: 2013-01-23 11:25
comments: true
categories: [neo4j, database, coffee, nodejs]
---

##安装

作为一个java软件，就得充分发挥它`Write Once, Run Anywhere`的精神。直接下载tarball，解压后运行即可。官方还很贴心的提供了一个init脚本(./bin/neo4j)，链接到init.d下就可以开搞啦。

默认的服务实例在localhost:7474，其余配置还是值得好好研究一番的。

##neo4j shell
neo4j提供了一种叫做`Cypher Query Language`的查询方言，可以看做是图形数据库的sql，neo4j还提供了一个`neo4j-shell`用于做查询交互，在命令行下可以使用`./bin/neo4j-shell`来开启，web中也有一个tab叫做`power-tool console`可以使用neo4j-shell。

###增删改节点
{% codeblock lang:sh %}
# 创建节点
neo4j-sh (?)$ CREATE n = { name : 'Andres', title : 'Developer' } RETURN n;
+-------------------------------------------+
| n                                         |
+-------------------------------------------+
| Node[37]{name:"Andres",title:"Developer"} |
+-------------------------------------------+
1 row
Nodes created: 1
Properties set: 2
8 ms
# 修改节点
neo4j-sh (?)$ START n = node(37) SET n.surname = 'Taylor' RETURN n;
+------------------------------------------------------------+
| n                                                          |
+------------------------------------------------------------+
| Node[37]{name:"Andres",title:"Developer",surname:"Taylor"} |
+------------------------------------------------------------+
1 row
Properties set: 1
15 ms
# 删除节点
neo4j-sh (?)$ START n = node(37) DELETE n;
+-------------------+
| No data returned. |
+-------------------+
Nodes deleted: 1
4 ms
{% endcodeblock %}

###创建关系
图形数据库最重要的一个概念就是关系(relationship)，各个节点直接通过双向或单向的关系连接在一起，这样才能从一个节点查找到其他的节点，这种设计在某些场景下会让查询变得更加高效而灵活，例如社交网络中的好友关系，人立方中查找任意两人之间的亲友，假如使用传统的关系数据库，查找朋友的朋友就会变得非常的困难，其耗时也是指数型的增长，而使用图形数据库，则可以保持线性的效率。

{% codeblock lang:sh %}
# 创建两个节点的关系
neo4j-sh (?)$ START a = node(34), b = node(36) CREATE a-[r:knowns]->b RETURN r;
+---------------+
| r             |
+---------------+
| :knowns[0] {} |
+---------------+
1 row
Relationships created: 1
20 ms
# 查找关系
neo4j-sh (?)$ start r = rel(0) return r;    
+-----------------------------------+
| r                                 |
+-----------------------------------+
| :isdogof[0] {ctime:1359365331933} |
+-----------------------------------+
1 row
1 ms
# 删除某节点和它的所有关系
neo4j-sh (?)$ START n = node(34) MATCH n-[r]-() DELETE n, r;
+-------------------+
| No data returned. |
+-------------------+
Nodes deleted: 1
Relationships deleted: 3
3 ms
{% endcodeblock %}

有意思的是注意其中`CREATE a-[r:knowns]->b`中的箭头走向表示这种关系的指向，我们可以通过`CREATE a<-[r:knowns]-b`来创建一个b到a的关系，但是当我想用`CREATE a<-[r:knowns]->b`来创建一个双向关系时却没有成功，仍然只创建了从a到b的关系。

在自己看来，`Cypher Query Language`的增删改语句还是比较直观的，但是一旦牵涉到关系就有点没节操了，一句查询中一半的操作符，真是让人看花眼，相较之下还是sql发展的比较成熟，也更易为人所接受了。[更多的操作符和更多的语法](http://docs.neo4j.org/chunked/milestone/cypher-query-lang.html)

不过，各种neo4j的客户端都将晦涩的`Cypher`语言封装起来，提供了可读性更高的接口方法，下面就找个客户端来试用一下。

##nodejs bundle
官网上给出了java和python版本的实例，我等屌丝玩点轻量级的，这里找了一个[nodejs的客户端](https://github.com/thingdom/node-neo4j)，初窥图形数据库的魅力。

###创建及修改节点
{% codeblock lang:coffeescript %}
neo4j = require 'neo4j'     #使用coffee-script，那就尽量写的更coffee一点儿吧
db = new neo4j.GraphDatabase('http://localhost:7474') #连接默认的REST端口
node.createNode {             #初始化一个节点
    username: 'bran'
}
node.save (err, node)->       #需要save才能真正的保存这个节点到数据库
    node.data = {           #可以通过直接修改node的data属性来修改node值
        username: 'bran'
        nickname: 'bird man'
        email: 'bran@gmail.com'
    }
    node.save()             #不要忘了再次保存
{% endcodeblock %}

##备份数据库
之前造出了那么多的脏数据，有点洁癖的人都想要把数据清理一下吧。网上找了找，发现只有'enterprise'版才有export的功能，这不是明摆着鄙视我等屌丝么。在[这里](http://www.mail-archive.com/user@lists.neo4j.org/msg08932.html)(翻墙可入)有兄台说了一个很暴力的办法，直接删除`data/graph.db`文件夹，我试了一下，确实可行，重启后世界干干净净，只剩下了0号node，果断再用`start n = node(0) delete n;`删除之。这大概也是nosql的好处，数据就是文件，取消了维护索引，关系等等的麻烦，随去随用，冷备份和迁移的时候也简单，直接copy文件夹即可。

##参考文档
* [v1.9手册](http://docs.neo4j.org/chunked/milestone/)
* [v1.8中文开发文档](http://docs.neo4j.org.cn/)
* [node-neo4j文档](http://coffeedoc.info/github/thingdom/node-neo4j/master/)
---
layout: post
title: "Cypher Query Language 学习笔记(2)"
date: 2013-02-06 14:32
comments: true
categories: [neo4j, learn, cypher]
---

###create unique
顾名思义，`create unique`与`create`在功能上是类似的，不过当新建的node或relationship已经存在时，`create unique`不会再生成一个新的node或relationship。

另一个区别是`create unique`只能在一个`path`表达式中使用，例如下面的代码：

{% codeblock lang:sh %}
neo4j-sh (?)$ start bran = node(6) create unique bran-[r:littlebrotherof]->(n{name:"jon"}) return n;
+----------------------------+
| n                          |
+----------------------------+
| Node[7]{name:"jon",age:17} |
+----------------------------+
1 row
{% endcodeblock %}

上面的代码中，path中name="jon"的node已经存在，neo4j认为这是一个`unique node`，所以不会再新建一个node，稍加修改，将age属性调整一下，就可以新建一个不同的node。

{% codeblock lang:sh %}
neo4j-sh (?)$ start bran = node(6) create unique bran-[r:littlebrotherof]->(n{name:"jon",age:18}) return n;
+----------------------------+
| n                          |
+----------------------------+
| Node[8]{name:"jon",age:18} |
+----------------------------+
1 row
Nodes created: 1
Relationships created: 1
Properties set: 2
{% endcodeblock %}

新建relationship的方式和上面差不多，举一反三即可。

###foreach
foreach针对的neo4j中的集合做遍历，可以做一些批量的修改操作。其语法块需要用括号围起来，像下面的代码，更新path p关联的所有node的uptime属性为100。

{% codeblock lang:sh %}
neo4j-sh (?)$ start bran = node(6) match p = bran-[]->() foreach (n in nodes(p): set n.uptime = 100);
+-------------------+
| No data returned. |
+-------------------+
Properties set: 4
{% endcodeblock %}

至于什么是neo4j中的集合，比如`nodes()`方法得到的结果就是一个集合，用集合表达式表示的也是一个集合，但是`n = node(*)`中匹配出来的n并不是一个集合。

{% codeblock lang:sh %}
neo4j-sh (?)$ start bran = node(6), jon = node(7) foreach (n in [bran, jon]: set n.uptime = 101);
+-------------------+
| No data returned. |
+-------------------+
Properties set: 2
{% endcodeblock %}

###functions
[官方手册](http://docs.neo4j.org/chunked/milestone/query-function.html)中罗列了所有的可用方法，非常详尽，需要慢慢研究了。

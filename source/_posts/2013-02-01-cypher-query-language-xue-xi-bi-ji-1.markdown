---
layout: post
title: "Cypher Query Language 学习笔记(1)"
date: 2013-02-01 11:30
comments: true
categories: [neo4j, learn, cypher]
---

紧接前文[初试图形数据库 neo4j](http://sailxjx.github.com/blog/blog/2013/01/23/chu-shi-tu-xing-shu-ju-ku-neo4j/)初窥了这种语言的特性，几天使用下来，初期不适应的便秘感慢慢退去，渐入佳境，竟然觉得有些妙不可言鸟。就如同nodejs基于事件的特点和函数式语法对传统编程方法的改变，学习`Cypher`同样需要改改传统`Sql`的思路，下面记录一下最近的新发现。

###版本号
`dbinfo`可以用于查询一些与数据库状态相关的信息，查看版本号是其中一个应用。
{% codeblock lang:sh %}
neo4j-sh (?)$ dbinfo -g Kernel                
{
  "KernelStartTime": "Mon Feb 04 14:45:48 CST 2013",
  "KernelVersion": "Neo4j - Graph Database Kernel 1.9.M04",
  "MBeanQuery": "org.neo4j:instance=kernel#0,name=*",
  "ReadOnly": false,
  "StoreCreationDate": "Thu Jan 31 16:42:31 CST 2013",
  "StoreDirectory": "/usr/local/neo4j/data/graph.db",
  "StoreId": "b9dcdac5ae2b9e82",
  "StoreLogVersion": 1
}
{% endcodeblock %}

###match
说起这`match`真是个很神奇的东西，可以用`sql`中的`where`作类比，但是又不同于`where`，因为`Cypher`中有专门的`where`。

这个`match`可以比作正则中的捕获组，还兼具了赋值的功能，如下面的例子
{% codeblock lang:sh %}
neo4j-sh (?)$ start bran = node(13) match bran-[r]->b return r, b; 
+--------------------------------------------------------------------------+
| r                                         | b                            |
+--------------------------------------------------------------------------+
| :islittlebrotherof[2] {age:1359622995523} | Node[14]{name:"snow",age:17} |
+--------------------------------------------------------------------------+
1 row
0 ms
{% endcodeblock %}
上面的例子中通过`match`找出了节点bran出去的所有关系r和所有终点b，这在传统的`sql`中可以比较难办到的哦。

`match`一般需要和下面要提到的独有的模式(pattern)配合使用，比如下面这个很神奇的语句，能匹配出与节点summer和snow都有关系的中间节点，甚至你可以在中途添加一些表达式来获得沿途的关系对象。
{% codeblock lang:sh %}
neo4j-sh (?)$ start summer = node(15), snow = node(14) match summer-[r]->n<--snow return n, r;
+--------------------------------------------------------+
| n                            | r                       |
+--------------------------------------------------------+
| Node[13]{name:"bran",age:10} | :isdogof[3] {ctime:100} |
+--------------------------------------------------------+
1 row
1 ms
{% endcodeblock %}
当你不想要将匹配结果赋值时，可以使用()来代替node，用[]来代替relationship，当然，relationship不填写也是可以的。

`match`中还有一种表示深度的方式，类似于`coffee`中的数组定义`[0..10]`来表示深度范围。
{% codeblock lang:sh %}
neo4j-sh (?)$ start s = node(15), snow = node(14) match p = s-[r:knowns*1..2]->snow return r;   
+-------------------------------+
| r                             |
+-------------------------------+
| [:knowns[6] {},:knowns[7] {}] |
+-------------------------------+
1 row
1 ms
{% endcodeblock %}
官网手册中还提供了一个深度为0的例子，表示指向自己的relationship。同样如果不需要赋值或者不需要指定类型，用[*1..2]代替。

求最短路径在很多地方都会应用到,neo4j提供了`shortestpath`方法来提供两点间的最短路径

{% codeblock lang:sh %}
neo4j-sh (?)$ start bran = node(1), jon = node(2) match p = shortestpath(bran-[*..2]->jon) return p; 
+------------------------------------------------------------------------------------------------------+
| p                                                                                                    |
+------------------------------------------------------------------------------------------------------+
| [Node[1]{name:"bran",age:10},:islittlebrotherof[0] {ctime:1359963465947},Node[2]{name:"jon",age:17}] |
+------------------------------------------------------------------------------------------------------+
1 row
1 ms
{% endcodeblock %}

不过假如在`shortestpath`参数中指定最短长度值的话会报错(如`p = shortestpath(bran-[*0..2]->jon)`)，不过貌似没有`longestpath`来获得最长路径，可能一是因为应用场景较少，二是在算法上也会复杂很多，略显遗憾。

###pattern
现在再记录pattern有点本末倒置的感觉，毕竟前面的match已经用到了很多种pattern，那么这里权作总结，将常用的pattern归归类。

* `a-->b` 最简单的，由一个node到另一个node
* `a-[r]->b` 加上了relationship的path
* `()-[]->b` 如果都不想要标注变量，可用`()`表示一个node（或一个子pattern），用`[]`表示一个relationship
* `a-[r:TYPE1|TYPE2]->b` 指定relationship type，其中type可以指定多个，为或的关系。
* `a-[?*]->b` 得到node a到node b的所有路径，如果不存在路径则返回null。（假如没有`?`则返回空）
{% codeblock lang:sh %}
neo4j-sh (?)$ start a = node(1) match p = a-[*]->b return p;
+----------------------------------------------------------------------------------------------------------------------+
| p                                                                                                                    |
+----------------------------------------------------------------------------------------------------------------------+
| [Node[1]{name:"bran",age:10},:islittlebrotherof[0] {ctime:1359963465947},Node[2]{name:"jon",age:17}]                 |
| [Node[1]{name:"bran",age:10},:isownerof[1] {},Node[3]{name:"summer",age:4}]                                          |
| [Node[1]{name:"bran",age:10},:isownerof[1] {},Node[3]{name:"summer",age:4},:knowns[5] {},Node[2]{name:"jon",age:17}] |
+----------------------------------------------------------------------------------------------------------------------+
3 rows
1 ms
{% endcodeblock %}
* `a-[*3..5]->b` 指定path的深度，是可以是一个范围值。
* `me-[:KNOWS*1..2]-remote_friend` 将前面集中pattern整合一下，主要是注意其中各pattern的位置，不能搞乱。

###index
`neo4j`的索引是一个key=>value对，基于lucene，据说也可以换其他的引擎，没试过。通过索引可以供`Cypher`或Rest api查找对应的node或relationship或任何想要的集合。

索引分为两种，自动索引和手动索引，就目前的`Cypher`版本(1.9.M04)来说，还没有提供创建手动索引的功能，遗憾的是，在nodejs客户端中同样没有完善这一功能，所以我找到了一个ruby版本的客户端用于实验这一功能。

[neography](https://github.com/maxdemarzi/neography)是官方推荐的一个ruby driver(其实官方推荐中排名更靠前的是[neo4j.rb](https://github.com/andreasronge/neo4j)，但是基于jruby的，出于对java的不感冒，还是绕行了)，文档很详细，不赘述了

{% codeblock lang:ruby %}
require 'neography'
@neo = Neography::Rest.new
@neo.create_node_index('name')                      #新增一个索引，其中第一个参数是索引主键
@neo.add_node_to_index('name', 'name', 'bran', 1)   #将一个node添加到索引，其中最后的`1`是node id，也可以是一个node对象，很神奇，很kiss
{% endcodeblock %}

建好一个索引之后，就可以由`Cypher`出场了。`Cypher`中通过索引可以查到对应的node和relationship。

{% codeblock lang:sh %}
#                       节点:索引主键(key=value)
neo4j-sh (?)$ start n = node:name(name="bran") return n;
+-----------------------------+
| n                           |
+-----------------------------+
| Node[1]{name:"bran",age:10} |
+-----------------------------+
1 row
{% endcodeblock %}

使用`Cypher`可以创建自动索引，前提是配置中打开了`node_auto_indexing`(针对node)或`relationship_auto_indexing`(针对relationship)这一项，而且这个索引是后写入的，也就是说假如之前已经存在的node，在没有改动的情况下，是不会加入到索引中的。

自动索引可以设定需要的fields，在配置文件中用`node_keys_indexable`和`relationship_keys_indexable`表示

创建索引的`Cypher`语句如下：

{% codeblock lang:sh %}
neo4j-sh (?)$ index --create node_auto_index -t node
neo4j-sh (?)$ index --indexes
Node indexes:
  name
  node_auto_index

Relationship indexes:
{% endcodeblock %}

自动索引默认是关闭的，可能是出于效率的考虑，毕竟在正常的应用中我们不需要对所有node进行索引。而且自动索引是只读的，就是说索引建立以后，除了清空数据库，木有别的方法删掉它啊~。

##注意事项

* `Cypher`中遇到某些查询条件中包含空格或别的非英文字符的，可以用\`把字符串包起来。

##参考资料
* [v1.9手册](http://docs.neo4j.org/chunked/milestone/)
* [neography wiki](https://github.com/maxdemarzi/neography/wiki)
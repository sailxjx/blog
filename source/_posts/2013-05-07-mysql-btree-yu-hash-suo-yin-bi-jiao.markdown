---
layout: post
title: "mysql btree 与 hash 索引比较"
date: 2013-05-07 11:45
comments: true
categories: [mysql, translation]
---

mysql最常用的索引结构是btree(`O(log(n))`)，但是总有一些情况下我们为了更好的性能希望能使用别的类型的索引。hash就是其中一种选择，例如我们在通过用户名检索用户id的时候，他们总是一对一的关系，用到的操作符只是`=`而已，假如使用hash作为索引数据结构的话，时间复杂度可以降到`O(1)`。不幸的是，目前的mysql版本(5.6)中，hash只支持MEMORY和NDB两种引擎，而我们最常用的INNODB和MYISAM都不支持hash类型的索引。

不管怎样，还是要了解一下这两种索引的区别，下面翻译自[mysql官网文档](http://dev.mysql.com/doc/refman/5.6/en/index-btree-hash.html)中对这两者的解释。

## B-Tree 索引特征
B-Tree索引可以被用在像`=`,`>`,`>=`,`<`,`<=`和`BETWEEN`这些比较操作符上。而且还可以用于`LIKE`操作符，只要它的查询条件是一个不以通配符开头的常量。像下面的语句就可以使用索引：

{% codeblock lang:sql %}
SELECT * FROM tbl_name WHERE key_col LIKE 'Patrick%';
SELECT * FROM tbl_name WHERE key_col LIKE 'Pat%_ck%';
{% endcodeblock %}

下面这两种情况不会使用索引：

{% codeblock lang:sql %}
SELECT * FROM tbl_name WHERE key_col LIKE '%Patrick%';
SELECT * FROM tbl_name WHERE key_col LIKE other_col;
{% endcodeblock %}

第一条是因为它以通配符开头，第二条是因为没有使用常量。

假如你使用`... LIKE '%string%'`而且`string`超过三个字符，MYSQL使用`Turbo Boyer-Moore algorithm`算法来初始化查询表达式，然后用这个表达式来让查询更迅速。

一个这样的查询`col_name IS NULL`是可以使用`col_name`的索引的。

任何一个没有覆盖所有`WHERE`中`AND`级别条件的索引是不会被使用的。也就是说，要使用一个索引，这个索引中的第一列需要在每个`AND`组中出现。

下面的`WHERE`条件会使用索引：

{% codeblock lang:sql %}
... WHERE index_part1=1 AND index_part2=2 AND other_column=3
    /* index = 1 OR index = 2 */
... WHERE index=1 OR A=10 AND index=2
    /* 优化成 "index_part1='hello'" */
... WHERE index_part1='hello' AND index_part3=5
    /* 可以使用 index1 的索引但是不会使用 index2 和 index3 */
... WHERE index1=1 AND index2=2 OR index1=3 AND index3=3;
{% endcodeblock %}

下面的`WHERE`条件不会使用索引：

{% codeblock lang:sql %}
    /* index_part1 没有被使用到 */
... WHERE index_part2=1 AND index_part3=2

    /* 索引 index 没有出现在每个 where 子句中 */
... WHERE index=1 OR A=10

    /* 没有索引覆盖所有列 */
... WHERE index_part1=1 OR index_part2=10
{% endcodeblock %}

有时候mysql不会使用索引，即使这个在可用的情况下。例如当mysql预估使用索引会读取大部分的行数据时。（在这种情况下，一次全表扫描可能比使用索引更快，因为它需要更少的检索）。然而，假如语句中使用`LIMIT`来限定返回的行数，mysql则会使用索引。因为当结果行数较少的情况下使用索引的效率会更高。

## Hash 索引特征

Hash类型的索引有一些区别于以上所述的特征：

* 它们只能用于对等比较，例如`=`和`<=>`操作符（但是快很多）。它们不能被用于像`<`这样的范围查询条件。假如系统只需要使用像“键值对”的这样的存储结构，尽量使用hash类型索引。

* 优化器不能用hash索引来为`ORDER BY`操作符加速。（这类索引不能被用于搜索下一个次序的值）

* mysql不能判断出两个值之间有多少条数据（这需要使用范围查询操作符来决定使用哪个索引）。假如你将一个`MyISAM`表转为一个依靠hash索引的`MEMORY`表，可能会影响一些语句（的性能）。

* 只有完整的键才能被用于搜索一行数据。（假如用B-tree索引，任何一个键的片段都可以用于查找。我觉得可能意味着带通配符`LIKE`操作符会不起作用）。

# 后记

顺便记录一下在使用mysql过程中碰到的一些问题：

* 有时候使用脚本迁移数据时会碰到乱码的问题，即使将表字符集设置成`utf8`也无济于事，这个时候在执行sql之前加一句`set names utf8`即可。

# 参考文档

* [index-btree-hash](http://dev.mysql.com/doc/refman/5.6/en/index-btree-hash.html)
* [why-does-mysql-not-have-hash-indices-on-myisam-or-innodb](http://dba.stackexchange.com/questions/2817/why-does-mysql-not-have-hash-indices-on-myisam-or-innodb)
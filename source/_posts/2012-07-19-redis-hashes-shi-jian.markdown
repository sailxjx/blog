---
layout: post
title: "redis hashes 实践"
date: 2012-07-19 14:27
comments: true
categories: [redis, nosql, test]
---
这是项目中遇到的一个问题，mysql的用户表做了分表，主键可以通过取模来读取分表信息，但是往往在注册的时候需要检测邮箱的唯一性，这个email就无法定位到分表了，之前的做法是用另一张表emailtoid对邮箱和id做了一个映射，现在想将该表迁移到redis上。就想测试一下redis对hashes类型的处理性能了。

### 方案一(multi)：
只使用一个key，所有的email都是这个key的field，id是value

### 方案二(hashed key)：
使用多个key，取email的md5值前两位作为key，email值仍为field，id是value，这些都不变

### 附加测试(single)：
给redis设置一个hashes，只有一个field和value，反复读写，用于比较redis在hashes长度变化时性能的升降幅度

<iframe id="highchart" src="http://sailxjx.github.com/demo/redis-hashes-test-chart.html" style="width: 100%; height: 290px;"></iframe>
测试对redis进行1000000次读写，使用本地loop，没有网络延迟，没有事务，结果基本没有多大意外。总结成三点：

1. redis的读写速度基本持平
2. hashes长度增加对于redis的读写速度影响很小（官网也注明了hget和hset的时间复杂度均为O(1)）
3. 储存在多个key中因为需要预先对field的值进行hash，整体的效率不如单个key

测试结果见图。

整个用户表数据在600w条左右，只存email,id对的话占用内存在60m左右，使用单个key来保存redis数据应该是足够满足性能的需求了。

[测试文件](/raw/redis_hash_test.php)
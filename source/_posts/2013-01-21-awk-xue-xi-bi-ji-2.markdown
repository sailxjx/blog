---
layout: post
title: "awk 学习笔记(2)"
date: 2013-01-21 17:42
comments: true
categories: [awk, learn]
---

##常用的选项
* -F 指定分隔符
* -f 指定调用脚本
* -d 输出所有变量到文件，默认输出到awkvars.out，也可以通过在-d后加文件路径来指定文件，但是注意-d与文件名之间不能有空格。调试的时候这个选项会非常有用。
{% codeblock lang:bash %}
$ awk -d./awk.dump 'BEGIN { foo = "test" } /^foo/ { print $0 }' BBS-list
$ cat ./awk.dump
ARGC: 2
ARGIND: 1
ARGV: array, 2 elements
foo: "test"
...
{% endcodeblock %}
* d
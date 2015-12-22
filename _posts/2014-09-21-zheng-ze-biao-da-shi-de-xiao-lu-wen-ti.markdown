---
layout: post
title: "正则表达式的效率问题"
date: 2014-09-21 14:54
comments: true
categories: [regexp, code]
---

> 原文地址：[Regular Expression Matching Can Be Simple And Fast](http://swtch.com/~rsc/regexp/regexp1.html)

# 介绍

下图是两种正则表达式实现方式的曲线图。其中一个是大部分语言中使用的标准解释器，包括 Perl。另一个只在很少的地方应用，尤其是在 awk 和 grep 中。这两种实现有完全不同的性能表现：

<p style="text-align: center;">
<img src="/u/image/grep3p.png" alt="grep3p" style="border: none;box-shadow: none;">
<img src="/u/image/grep4p.png" alt="grep4p" style="border: none;box-shadow: none;">
</p>

我们用上标来表示字符的重复，譬如 a?<sup>3</sup>a<sup>3</sup> 就是 a?a?a?aaa 的简写。这两张图表示了用 a?<sup>n</sup>a<sup>n</sup> 来匹配字符串 a<sup>n</sup> 所需要的时间。

需要注意的是 Perl 需要六秒以上的时间来匹配一个长度为 29 的字符串。

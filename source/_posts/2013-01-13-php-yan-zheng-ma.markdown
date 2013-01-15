---
layout: post
title: "PHP 验证码"
date: 2013-01-13 14:20
comments: true
categories: [php]
---

gd是一个强大的php图像处理库，最近在做验证码加强的策略，才发现用php作图也能玩出很多花样来。

## 几个重要函数
* [imagecreatetruecolor](http://php.net/manual/en/function.imagecreatetruecolor.php) 创建一张空的画布
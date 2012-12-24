---
layout: post
title: "linux 登录用户管理"
date: 2012-12-19 22:35
comments: true
categories: [linux]
---

最近换了mac后，需要在公司和家中多地登录，两地的ip又没有设置成一样的，结果每次切换ip就发现ssh到虚拟机的终端没响应了，再连上之后，之前登录的用户就永远活在虚拟机心中啦~当然这也没什么，但是作为有一点点洁癖的我来说，不清除这几个用户总是一件不太舒服的事情，于是就去网上找找命令，结果发现找到的资料还挺齐全，学到不少，记录下来，以免下次忘了。

### who

>root     tty1         2012-12-19 17:51

>tristan  pts/0        2012-12-19 17:57 (macshare)

>tristan  pts/1        2012-12-19 18:00 (macshare)

这个命令不用说，大多数人都知道，查看当前登录用户，登录时间，终端号(tty)和远程登录终端号(pts)

### whoami

这个命令有点意思，顾名思义，告诉`我是谁`，曾经有位buddy获得了root权限，但是当使用`cd ~`的时候仍然回到了先前用户的主目录，这个时候`whoami`告诉他确实已经是root用户了，仔细想想，原来他是用`sudo -s`切到了root，所有环境变量沿用了老用户的。说明这个短小的命令还是挺实用啦。

### w [user]

>18:43:48 up  9:39,  3 users,  load average: 0.06, 0.06, 0.06

>USER     TTY       LOGIN@   IDLE   JCPU   PCPU WHAT

>root     tty1      17:51   47:01   0.43s  0.40s -zsh

>tristan  pts/0     17:57   30:20   0.44s  0.44s -zsh

>tristan  pts/1     18:00    0.00s  0.18s  0.00s w

更短的命令，却比who更强大。

第一行数值分别表示当前时间，系统运行时间，登录用户数，(1分钟，5分钟，15分钟)内的系统负载

第二行开始就是一个用户相关的表格了，每列的意思分别为：

* USER：显示登陆用户帐号名。
* TTY：用户登录的终端号。
* FROM：显示用户在何处登陆系统。
* LOGIN@：是LOGIN AT的意思，表示登陆进入系统的时间。
* IDLE：用户空闲时间，从用户上一次任务结束后，开始记时。
* JCPU：一终端代号来区分，表示在某段时间内，所有与该终端相关的进程任务所耗费的CPU时间。
* PCPU：指WHAT域的任务执行后耗费的CPU时间。
* WHAT：表示当前执行的任务。

### last [user]

>tristan   ttys003                   Wed Dec 19 22:57   still logged in

>tristan   ttys001                   Wed Dec 19 22:47   still logged in

>tristan   ttys003                   Wed Dec 19 22:38 - 22:39  (00:01)

这个命令显示用户的登录记录，后面可以跟用户名来只显示该用户的登录历史。一般还会搭配管道用`last | head`来显示最后登录历史或`last | grep still`来获取仍然登录中的用户

### ps -ef | grep [pts/0]

>tristan   1042  1041  0 19:01 pts/0    00:00:00 -zsh

>tristan   1916  1042  0 19:03 pts/0    00:00:00 ps -ef

这个命令就是起初写这篇文章的用意啦，根据终端号(可以通过who命令查到)获取目标用户登录相关的pid，比如上面这个1042，然后使用`kill -9 1042`剔除这个用户，注意`kill`需要加上`-9`，默认的TERM信号是杀不了这个进程的。

### pkill -u [user]

网上还有一种更简便的方法，根据用户名kill掉这个用户相关的所有进程，包括已这个用户身份运行的所有daemon进程，很黄很暴力，伤敌一千自损八百，不推荐。

## 参考资料

* [Linux查看和剔除当前登录用户](http://blog.csdn.net/linfengfeiye/article/details/4781507)

* [Linux / Unix Command: w](http://linux.about.com/library/cmd/blcmdl1_w.htm)
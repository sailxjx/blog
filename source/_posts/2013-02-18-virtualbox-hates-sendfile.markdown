---
layout: post
title: "virtualbox hates sendfile"
date: 2013-02-18 16:39
comments: true
categories: [virtualbox, nginx, linux]
---

事情的起因是这个样子滴~

代码文件都放在mac中，运行环境在virtualbox中，通过mount主机的文件夹来工作，相信很多同学都搭建过这样的环境，一切相安无事，直到某一天。。。

修改过的静态文件不生效了！

本来以为是nginx中缓存设置的问题，使尽各种解数，包括把expires设置为off，header中加Expire为0，给文件加时间戳，依然如此。果断google之，原来是virtualbox使用的特殊文件系统造成的。

apache和nginx中都有个默认开启的选项sendfile，表示通过内核文件指针来读取或复制文件，在vboxsf(virtualbox共享文件所使用的文件系统)中，sendfile会造成文件无法更新。于是我们无论怎么刷新，都只能看到第一次访问得到的文件了。

解决办法也很简单，将nginx.conf中设置`sendfile off`就可以了。

由于这个问题折腾了我很久，特此记录一下，同时借用某同样遇到此问题的[blog标题](http://abitwiser.wordpress.com/2011/02/24/virtualbox-hates-sendfile/)。

##后记
1.[virtualbox论坛](https://forums.virtualbox.org/viewtopic.php?f=1&t=24905)2009年的时候就有人讨论过这个问题，那时候的版本还是3.0，现在都4.2了，问题仍然没有得到解决，唉~被oracle X过的软件果然不行啊~。

2.在主机中修改文件，虚拟机中的inode不会变化，反过来也一样，不知道是不是因为vboxsf的问题，然而使用samba共享的文件系统中两边的inode是同时变化的。

##参考资料
* [VirtualBox Hates Sendfile](http://abitwiser.wordpress.com/2011/02/24/virtualbox-hates-sendfile/)
* [serverfault](http://serverfault.com/questions/269420/disable-caching-when-serving-static-files-with-nginx-for-development)
* [virtualbox forum](https://forums.virtualbox.org/viewtopic.php?f=1&t=24905)
* [nginx wiki](http://wiki.nginx.org/HttpCoreModule#sendfile)
* [mac中samba共享的问题](http://comments.gmane.org/gmane.linux.kernel.cifs/3517)
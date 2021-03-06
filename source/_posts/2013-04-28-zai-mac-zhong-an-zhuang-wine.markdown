---
layout: post
title: "在 mac 中安装 wine"
date: 2013-04-28 12:39
comments: true
categories: [mac, osx, software, wine]
---

公司用奇葩的imo，由于没有mac版本，只能用web版。最近imo web版升级之后老是不稳定，又不想装一个笨重的windows虚拟机，于是曲线救国，找找mac上的[wine](http://www.winehq.org/)该怎么用。

在mac上安装wine需要备齐三样神器：

* [xquartz](http://xquartz.macosforge.org/landing/)，在mac上提供对x11的支持，由于mountain lion之后不在预装x11，所以这个需要手动下载。
* [winebottler](http://winebottler.kronenberg.org/)，这个包里两个软件，wine和winebottler，winebottler算是一个wine的管理器，里面预设了一些各种wine应用下需要的类库模板。

安装顺序是xquartz->winebottler->wine，后面两个从包中直接拖到Application中就行了。安装完后需要先启动xquartz，然后启动wine之后在上面的panel中会有一个酒杯的图标，里面可以打开资源管理器等等，第一次打开时会在用户目录里生成一个Wine Files文件夹，这个就是winebottler中所谓的prefix，里面模拟了一套windows下面的环境。可以通过在winebottler中安装不同的prefix来切换不同应用环境。不过每个prefix都是一个完整的windows环境，非常占空间，没有必要的话，用默认的就行。然后增加类库可以点击wine图标，选择wine trick来安装，还是比较方便的。

youtube上有个[视频](http://www.youtube.com/watch?v=m0BBkISOcEA)介绍了如何在mac上安装wine，按照上面说的一步步来，基本不会出错。

但是wine的种种缺点还是很明显的，一个是字体界面都很丑，在mac下更甚，即使想各种办法优化也无济于事。第二个就是很多库都没有，这是最致命的，imo最后还是没有安装成功，按照错误提示装了.net和vcrun2008等等之后还是不能正常启动，也是预料之中的事。wine还是只能算是一个玩具，给喜欢折腾的geek玩玩而已，真要用来跑wow之类的应用，那肯定是闲的蛋疼了。

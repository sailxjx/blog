---
layout: post
title: "thunderbird and davmail"
date: 2012-06-28 12:23
comments: true
categories: [linux, thunderbird, email]
---

邮件客户端之间的竞争在当今的软件貌似一直处在一个打酱油的位置（就像opera看浏览器之争），毕竟，web端的邮件体验是越做越完美了，传统的客户端不减减肥的话很难再在用户那边分到一杯羹。

不过，在办公领域客户端还是能占有一席之地的，毕竟云端容量有限，而且实时性也是个问题。公司使用内网的ms exchange，那么gmail之类的web端就不考虑了，况且几千封邮件的索引查找还是靠客户端比较靠谱。

比较了一圈linux下的客户端，evolution用的时间最长，但是对html样式的支持不是太好，某些表格粘贴进去排版就乱了。正好ubuntu11.10以后就默认安装了thunderbird，试用以后感觉也不错，写篇备忘，主要是记录不同协议下的配置。

[{% img right /u/image/thunderbird-main-furry.png 200 161 "thunderbird-main-furry" %}](/u/image/thunderbird-main-furry.png)
thunderbird诞生于mozilla的开源项目，有个叫firefox的高帅富兄弟，所以从菜单到细节处处都力争向高帅富靠拢，于是我们就看到了这个长得酷似浏览器的email客户端--多标签切换，附加组件。。。不幸的是，最近[mozilla宣布不再为thunderbird开发新功能](http://tech.sina.com.cn/s/2012-07-07/10247360817.shtml)，看来以后还是得靠自己撸啊。

添加账户很简单，编辑-账户设置-添加账户，输入用户名邮箱密码以后会自动在mozilla在线数据库中寻找适合的配置，像gmail,hotmail.163之流的基本都不需要额外的配置，直接确认就行。这里主要介绍一下exchange服务的配置。

默认情况下没有安装exchange的支持，在工具-附加组件里搜索exchange安装一个"Provider for Microsoft Exchange"的插件，再回到账户配置中选择手动配置，就有相应exchange的接收协议选项了，然后手动填写域名用户名密码，确定以后就可以开始接收邮件了。

接收邮件，对，我只提到收邮件。这个插件只提供了exchange同步接收的功能，配置SMTP服务器时遇到了问题，无论尝试哪种验证方式，连接远程服务器时都会报错，网上搜罗了一下相关的帖子也没有找到有效的解决方案。但exchange服务确是可以通过https/ssl访问的，无奈只能采用曲线救国的方式，就是下面要介绍的[davmail](http://davmail.sourceforge.net/)。

根据官网上的介绍，davmail是一个通用的POP/IMAP/SMTP/Caldav/Carddav/LDAP交换接口，允许用户用任何客户端链接到exchange服务器。其他的鸟文就不做解释了，这不是重点，重点是，这玩意儿管用。ubuntu用户下可以在sourceforge下载deb包安装，[下载地址](http://sourceforge.net/projects/davmail/files/latest/download?source=files)。

[{% img right /u/image/davmail-ui.png 350 206 "davmail-ui" %}](/u/image/davmail-ui.png)
安装完成后，davmail提供了一个简陋的UI界面，不过对于配置来说已经足够了，在owaurl一栏填入域中使用的exchange owa地址，类似https://exchange.domain.com/owa的，不确定是否可用可以在浏览器里面直接访问一下试试。下面的协议端口都用默认的，save一下就ok。

现在回到thunderbird，修改账户设置-发送SMTP服务器-添加或编辑，服务器名称填localhost，端口1025，选择密码验证，填写用户名，保存，现在试一试，应该可以正常的收发邮件了。

davmail其实是在本地搭建了一个邮件收发服务器，用户与远程服务器的通信都通过davmail的代理，简化了不同平台的配置。当然软件的功能远远不止这些，这里只是为了解决thunderbird与exchange连接的问题。假如thunderbird插件能直接支持exchange的收发，那就更好了。

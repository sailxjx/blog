---
layout: post
title: "vagrant 打包与发布"
date: 2013-02-06 14:31
comments: true
categories: [vagrant, virtualbox]
---

上次失败的[vagrant尝试](/blog/2012/12/11/vagrant-bi-ji/)之后，很久没有再去捣鼓这玩意儿，最近又想试一试，居然一举成功了，特别记录一下。

##打包
上手第一件事就是制作自己的box，网上已经有了许多现成的[各linux版本box](http://www.vagrantbox.es/)

首先用virtualbox安装好自己的linux，版本任选，我这里用的是ubuntu server 12.10，虚拟机名是`ubuntu_server_12.10`，装好后在其中添加`vagrant`账号。再用下面的命令就可以生成自己的box了。

> vagrant package --base ubuntu_server_12.10 --output vagrant_ubuntu.box

这样就在当前目录下生成了一个vagrant_ubuntu.box文件，压缩前原始vdi文件在1.4G左右，打包后的box是415M，压缩比还是不错的。

##导入
下面就是导入box文件了。

> vagrant box add vagrant_ubuntu vagrant_ubuntu.box

vagrant的磁盘文件储存在`~/.vagrant.d/`文件夹中。导入之后用下面的命令生成一个'Vagrantfile'配置文件

> vagrant init

然后就可以通过`up`命令启动虚拟机了

> vagrant up

##ssh
如果不做任何修改，虚拟机默认使用的是NAT的连接方式，而且做了一个端口转发(22->2222)，这个时候直接通过`vagrant ssh`命令或22端口是登陆不了虚拟机的，需要在Vagrantfile中添加下面两项

> config.ssh.port = 2222

> config.ssh.private_key_path = "/Users/tristan/.ssh/id_rsa"

其中第一项指定使用本机的2222作为ssh端口，其中第二项是指定使用的私钥路径，如果事先在虚拟机中加入了对应的公钥，这样连接时就可以免去输入密码的步骤。(还要注意的是网上大部分box都是使用vagrant用户名，密码也是vagrant，算是一个便于传播的约定)。现在可以看一下ssh配置。

{% codeblock lang:bash %}
tristan@bran:vagrant$ vagrant ssh-config
Host default
  HostName 127.0.0.1
  User vagrant
  Port 2222
  UserKnownHostsFile /dev/null
  StrictHostKeyChecking no
  PasswordAuthentication no
  IdentityFile /Users/tristan/.ssh/id_rsa
  IdentitiesOnly yes
{% endcodeblock %}

然后使用下面的命令，就可以直接登录虚拟机了。

> vagrant ssh

包括下面的一系列命令，也均可以使用。

* `vagrant up` 启动
* `vagrant halt` 关机
* `vagrant reload` 重启
* `vagrant suspend` 休眠

总而言之，搞定ssh，一切就很顺利鸟。
---
layout: post
title: "体验gollum"
date: 2012-07-12 18:07
comments: true
categories: [github, gollum, wiki, git, ruby]
---

gollum是一个轻量级的wiki系统，使用git作为版本管理和跟踪工具，支持markdown，mediawiki，texttile等多种语法，由github开发并且已经投入到githubwiki的应用中。

* <a href="#gollum_install">安装</a>
* <a href="#gollum_deploy">部署</a>
* <a href="#gollum_bug">已发现的bug</a>
* <a href="#gollum_refer">参考资料</a>

<h2 id="gollum_install">安装</h2>

gollum与指环王中某个屌丝同名，不知道开发人员的命名灵感是不是来自那里，我们知道，程序员老是喜欢用一些稀奇古怪的东西来给自己的软件命名，像python（莽蛇），octopress（章鱼）等等，搞得好像人人都是动物保护主义者一样～

根据[github主页](https://github.com/github/gollum)的说明，gollum的安装非常简单，一条命令解决

{% codeblock 安装gollum lang:bash %}
$ [sudo] gem install gollum
{% endcodeblock %}

假如你喜欢追新，觉得这样安装的版本太老，想直接上HEAD的话，clone一个镜像使用`bundle install`安装好依赖之后，其bin文件夹下面的gollum就直接可以执行了，绝对绿色环保无污染。

现在就来体验一下gollum带来的不同与其他wiki的小清新感觉吧，在主目录下使用以下命令

{% codeblock 执行gollum lang:bash %}
$ mkdir gowiki
$ cd gowiki
$ git init
$ gollum
{% endcodeblock %}

[{% img right /images/u/gollum-exec-info.png 450 70 "gollum-exec-info" %}](/images/u/gollum-exec-info.png)
看到右图就说明gollum已经正确的运行了，gollum默认监听4567端口，并且提供了一个可交互的前端，这个时候用户可以通过浏览器打开[http://localhost:4567](http://localhost:4567)来看一下gollum了

[{% img right /images/u/gollum-frontend.png 450 115 "gollum-frontend" %}](/images/u/gollum-frontend.png)

界面实在是足够简洁，提供的几个button实现了wiki的基本功能增删改查，还能查看历史页面，而且还有一套开放的用户系统，name和email由git的使用者决定，头像则由gravatar生成。

除了通过页面编辑wiki以外，gollum还支持直接由git提交版本来更新页面。由于我们gollum默认读取master分支，而我们正checkout在master上，不方便其他人的编辑，所以我们暂时新建一个demo分支来避免冲突。

{% codeblock 用git来写wiki lang:bash %}
$ git branch demo
$ git checkout demo
$ mkdir ../gowiki2 && cd ../gowiki2
$ git init && git remote add origin ../gowiki
$ git pull
{% endcodeblock %}

现在gowiki2中应该有了之前编辑过的几个页面，修改以后push到origin的master，就可以在wiki中看到刚刚的更新了。

<!--more-->

<h2 id="gollum_deploy">部署</h2>

虽然gollum提供了一个命令行工具监听端口来提供web服务，但是没有daemon选项，也没有容错机制，何况想来也没有多少人会使用4567端口来访问webserver。那有没有办法将gollum托管给我们的web服务器呢，答案是肯定的，下面以apache为例。

gollum由ruby写成，所以首先需要安装[passenger(mod_rails)](http://www.modrails.com)模块，这个模块的安装在[官网](http://www.modrails.com/install.html)上有详细的介绍，与其他apache模块的安装大同小异，下面主要介绍一下vhost的配置。

首先进入gollum的安装路径，不知道的可以用下面的命令找一下
{% codeblock 查找gollum路径 lang:bash %}
$ gem which gollum
/usr/local/ruby/lib/ruby/gems/1.9.1/gems/gollum-2.0.0/lib/gollum.rb
{% endcodeblock %}

gollum的前端app在gollum/frontend/public/下(将这个uri跟在上面找出来的路径下就行了)，下面将用`frontpath=/usr/local/ruby/lib/ruby/gems/1.9.1/gems/gollum-2.0.0/lib/gollum/frontend/`来替代，然后配置vhost

{% codeblock apache的virtual-hosts配置 lang:bash %}
<VirtualHost *>
    ServerName www.gollum.local.com  #替换成自己的域名
    DocumentRoot ${frontpath}public/ #替换成本地路径
</VirtualHost>
{% endcodeblock %}

然后在${frontpath}(同上，真实路径)下新建一个文件config.ru，写入下面的内容

{% codeblock config.ru lang:ruby %}
#!/usr/local/bin/env ruby
require 'rubygems'
require 'gollum/frontend/app'
system("which git") or raise "Looks like I can't find the git CLI in your path.\nYour path is: #{ENV['PATH']}"
gollum_path = '/home/{user}/gowiki' #这里的路径替换成实际想存放wiki文档的git目录
disable :run
configure :development, :staging, :production do
 set :raise_errors, true
 set :show_exceptions, true
 set :dump_errors, true
 set :clean_trace, true
end
$path = gollum_path
Precious::App.set(:gollum_path, gollum_path)
Precious::App.set(:wiki_options, {})
run Precious::App
{% endcodeblock %}

为了使/home/{user}/gowiki目录具有写权限，可以将这个目录的权限这是设为777或者让passenger进程的用户组设为user，修改httpd.conf增加一行`PassengerDefaultUser user`，这样我们通过web端更新wiki的时候就不会报权限问题了。

现在重启apache，然后访问www.gollum.local.com，结果就与上面看到的页面一模一样啦～～。

<h2 id="gollum_bug">已发现的bug</h2>

gollum绑定的grit库中存在一个关于编码的错误[[issue]](https://github.com/github/gollum/issues/147),当提交的文本中包含unicode字符时会导致一个fetal error。在grit2.5.0中修复了这个错误，所以在bundle之前需要先将grit版本设置为2.5.0。gollum最近的两个版本中需要修改的地方还不太一样，晕了～。输入`gollum --version`看一下版本，假如是2.0.0(gem安装的版本)，则修改gollum.gemspec，若是2.1.0(目前的HEAD版本)，则修改Gemfile.lock。将其中的grit版本改为2.5.0即可。

<h2 id="gollum_refer">参考资料</h2>

* [README](https://github.com/github/gollum/blob/master/README.md)
* [Gollum and Passenger](https://github.com/tecnh/gollum/wiki/Gollum-and-Passenger)


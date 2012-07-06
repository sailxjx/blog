---
layout: post
title: "gitpage and octopress"
date: 2012-06-20 20:45
comments: true
categories: [gitpage, octopress, scss, ruby, markdown]
---

花了一天时间（上班时不务正业。。。），总算把gitpage主页和这个blog搭起来了

写一些备忘，免得以后忘了。。。

- <a href="#gitpage">gitpage</a>
- <a href="#octopress">octopress</a>
- <a href="#scss">scss</a>
- <a href="#markdown">markdown</a>

<!--more-->

<h2 id="gitpage">gitpage</h2>

github给用户提供了一个非常cool的方式来搭建自己的主页，简单几步，就能完成网站的部署。

#### 1.建立分支

github给每个用户提供了一个形似{user}.github.com的二级域名，只要首先在自己的帐号下建立名为{user}.github.com的分支，就可以通过git来维护自己的主页啦。

#### 2.clone和commit

分支建立好之后，将{user}.github.com分支clone到本地，以方便编辑。

{% codeblock 本地gitpage lang:bash %}
$ git clone git@github.com:{user}/{user}.github.com {your local dir}
{% endcodeblock %}

然后就是建立自己的index啦，gitpage使用纯静态的方式来管理页面，可以通过本地程序预先将这些静态页面生成好再丢给github嘛。

{% codeblock 编辑提交首页 lang:bash %}
$ echo "hello git-page" > index.html
$ git add .
$ git commit -am 'init'
$ git push origin master
{% endcodeblock %}

提交以后可以在github的通知中心和邮箱中收到页面建立的通知。第一次提交可能需要10多分钟才能看到效果，以后的提交基本都是立即生效的。

现在访问[{user}.github.com](http://sailxjx.github.com)看一下效果吧。

#### 3.建立项目页面

除了首页，github还为用户的每一个项目提供了一个展示的平台，只需要在需要展示的项目下建立一个名为gh-pages的分支并push到github，剩余的操作就和首页如出一辙了。

{% codeblock 项目主页 lang:bash %}
$ cd {project}
$ git branch gh-pages
$ git checkout gh-pages
$ git push origin gh-pages
{% endcodeblock %}

提交以后会在github产生一个类似{user}.github.com/{project}的域名，访问的其实是[gh-pages](https://github.com/sailxjx/blog/tree/gh-pages)分支下的index文件，这个[blog](http://sailxjx.github.com/blog)就是托管给gitpage的项目页面的。

#### 4.使用gitpage模板

github提供了一种最快捷的方式来建立pages

* 进入你的[项目管理页](https://github.com/{user}/{project}/admin)
* 点击右下角的[Automatic Page Generator](https://github.com/{user}/{project}/generated_pages/new)
* 编辑内容并选择自己喜欢的模板
* done

然后就可以将gitpage clone到本地进行编辑并提交了

{% codeblock 编辑项目主页 lang:bash %}
$ cd {project}
$ git fetch origin
$ git checkout gh-pages
{% endcodeblock %}

<h2 id="octopress">octopress</h2>

[octopress](http://octopress.org/)是基于[Jekyll](http://github.com/mojombo/jekyll)的一个博客框架。所有的博文都用静态页面保存，不仅能很好的和gitpage集成，还有很高的可配置性，对于喜欢个性化的码农来说简直就是神器丫～

言归正传，安装octopress，必须保证系统中已经安装了git,ruby[1.9.2以上]。然后从github复制一份octopress的拷贝

{% codeblock 安装octopress lang:bash %}
$ git clone git://github.com/imathis/octopress.git octopress
$ cd octopress
$ gem install bundler
$ bundle install #安装依赖关系，在网络不好的情况下，这一步会相当相当的耗时，并且常有失败的情况。请一定要耐心，淡定。。。
$ rake install #安装默认主题，以后可以用别的命令更新octopress的主题。不过官方的主题已经足够简洁大气了，遇到其他心仪的主题之前，我恐怕不会再去折腾这些了(>_<)
{% endcodeblock %}

ok，安装结束，除了蛋疼的网络等待，整个安装过程还是比较简单顺利的，下面进入配置阶段

在根目录下的配置文件有四个，\_config.yml,config.rb,config.ru,Rakefile。其中Rakefile实现了部署更新的所有操作，一般情况下不需要修改。理想状态下只需要修改\_config.yml文件就可以了。

{% codeblock _config.yml中与用户相关的配置项 %}
url:                # 需要部署的博客站链接
title:              # 又短又二的博客标题，如XX的窝，孤独的根号三等等
subtitle:           # 小标题，会显示在网站头部，用来辩解标题其实没有那么二
author:             # 用户名，说明到底是谁写出了这么二的标题
simple_search:      # 站内搜索的工具，例如http://google.com/search，当然也可以用一样二的baidu
description:        # 网站说明，会加在meta中，给搜索引擎看的东东
subscribe_rss:      # rss文件路径，默认atom.xml
subscribe_email:    # 联系邮箱，这里填写的内容会直接带入页首的mailto链接中，如"mailto: sailxjx#gmail.com?subject=greeting"
email:              # 这里就是填写完整的邮箱地址啦，显示在页脚
root                # 假如博客不是发布在根目录下，而是发布到类似domain.com/blog的二级目录，这里要设置成二级目录的名字(blog)。
{% endcodeblock %}

在下面还有一些第三方网站接入的配置，包括google，twitter，github，facebook，disqus等等。大部分都只需要填写注册的用户名和是否启用就行了。这里值得一提的是disqus，当填写了disqus用户名之后，在博文下面会加载disqus的回复功能，正好弥补了octopress缺少动态内容的缺陷。

在默认配置下，可以很轻松的将博客部署到主站下面，在这里我将octopress部署在blog二级目录下面(gitpage只给我提供了一个域名，被博客全占了，多亏啊～～～)，需要修改_config.yml和config.rb中的对应目录配置

{% codeblock _config.yml && config.rb %}
## _config.yml 全站配置
url:    http://sailxjx.github.com/blog
root:   /blog
## config.rb 这个文件主要影响一些静态文件的加载
http_path = "/blog"
http_images_path = "/blog/images"
http_fonts_path = "/blog/fonts"
{% endcodeblock %}

现在开始写第一篇博客，只需要简单一个命令

{% codeblock new_post lang:bash %}
$ rake new_post['first blog']
{% endcodeblock %}

这条命令会在source/\_posts中生成一个名为YYYY-MM-DD-first-blog.markdown文件，惊喜的是，假如使用中文作为博客标题，这里的文件名（包括deploy后的url）会自动转成拼音。

打开\_posts中的文件，这是一个以[markdown](http://daringfireball.net/projects/markdown/)语法写成的文档，具体语法下面再介绍，先看一下头部有用的信息

{% codeblock post设置 %}
layout: post
title: "gitpage and octopress"                          //标题
date: 2012-06-20 20:45                                  //生成的时间，会作为发布时间显示在页底
comments: true                                          //设置成false可以关闭评论
categories: [gitpage, octopress, scss, ruby, markdown]  //博客分类，这些tag可以用来做筛选
author: Your Name                                       //假如有多个作者，可以添加在这里
{% endcodeblock %}

完成一篇博客以后，使用下面的命令可以将博客生成或者发布出去

{% codeblock 发布博客 lang:bash %}
$ rake setup_github_pages   //这个命令会让你填写可读写的github分支作为远程仓库，然后在_deploy文件夹中生成一个gh-pages分支
$ rake generate             //在public文件夹中生成静态文件，可以在本地配置一个服务器指向这个文件夹预览博客发布后的样子
$ rake deploy               //在deploy中生成静态文件，然后会自动调用git push将博客发布到github上
{% endcodeblock %}

写到这里基本已经完成了基本博客的发布，有兴趣可以学一学ruby，玩出自己的花样来。

<h2 id="scss">scss</h2>
<h2 id="markdown">markdown</h2>

to be continue...

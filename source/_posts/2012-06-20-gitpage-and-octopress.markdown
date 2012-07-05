---
layout: post
title: "gitpage and octopress"
date: 2012-06-20 20:45
comments: true
categories: [gitpage, octopress, scss, ruby, markdown]
---

花了一天时间（上班时不务正业。。。），总算把gitpage主页和这个blog搭起来了

写一些备忘，免得以后忘了。。。

<h2 id="gitpage"><a href="#gitpage">gitpage</a></h2>

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

<h2 id="octopress"><a href="#octopress">octopress</a></h2>
<h2 id="scss"><a href="#scss">scss</a></h2>
<h2 id="markdown"><a href="#markdown">markdown</a></h2>

to be continue...

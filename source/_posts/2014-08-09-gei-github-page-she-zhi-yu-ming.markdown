---
layout: post
title: "给 github page 设置域名"
date: 2014-08-09 18:28
comments: true
categories:
---

Github 免费提供了很棒的静态站托管服务 [github pages](https://pages.github.com/)，并且为每人准备了一个二级域名 username.github.io。

但是对于喜欢个性又爱折腾的码农来说，使用别人的域名，是万万不能忍受滴，所以 github 支持了绑定个人域名。

英语不错的可直接传送官方文档 [Tips for configuring a CNAME record with your DNS provider](https://help.github.com/articles/setting-up-a-custom-domain-with-github-pages)，如我这样记性不好的，就看下面的流程：

首先，你得有个自己的域名，比如这个 jingxin.me，然后我的目标是绑定到 sailxjx.github.io，并支持所有二级域名的跳转，如从 sailxjx.github.io/blog 会自动转到 jingxin.me/blog。

然后，我们要创建 CNAME 记录，这一步要在个人域名托管的 dns 服务上操作，添加一条 CNAME 记录，指向 sailxjx.github.io，这样，就实现了通过 jingxin.me 访问 github page 内容的目的。通过 dig 命令可以查看是否生效，这个时候如果你用 ping 或者 nslookup 会看到两个域名的 ip 是一样的。

```
$ dig jingxin.me +nostats +nocomments +nocmd
;jingxin.me.                  IN  A
jingxin.me.             346   IN  CNAME sailxjx.github.io.
sailxjx.github.io.      3346  IN  CNAME github.map.fastly.net.
github.map.fastly.net.  46    IN  A 103.245.222.133
```

最后，当然是告诉 github 需要自动从 sailxjx.github.io 跳转到 jingxin.me，是需要在相关的 repository 中添加一个 CNAME 文件，里面保留一行域名记录（不包含 http 等 schema 部分），比如[这里](https://github.com/sailxjx/sailxjx.github.com/blob/master/CNAME)，push 之后就可以在项目的设置中看到这样的提示

![pages-section.png](https://github-images.s3.amazonaws.com/help/settings/pages-section.png)

下面就是短暂的等待了，快的话立即就生效了。

## 多余的话

除了使用 CNAME，github 还提供了另一种做法 [Apex domains](https://help.github.com/articles/about-custom-domains-for-github-pages-sites#apex-domains)，由于官方并不推荐，所以这里也就不介绍了。

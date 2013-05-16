---
layout: post
title: "[nginx] if is evil"
date: 2013-05-15 17:36
comments: true
categories: [nginx]
---

最近用nginx配置中使用if遇到一些问题，碰巧想起以前在wiki中看到的这个页面，虽然我的问题可能和wiki中提到的不同，但是if还是能避免就避免吧

下面的内容翻译自[IfIsEvil](http://wiki.nginx.org/IfIsEvil)

# IfIsEvil (标题就不翻了，保持原汁原味的比较带感)

## 简介

[`if`](http://wiki.nginx.org/NginxHttpRewriteModule#if)指令在使用在`location`上下文中时有一些问题。有时候它不能如你所愿，而是做一些完全相反的事情。有时候甚至会引发分段错误。通常来说应该尽量避免使用`if`。

唯一100%可以安全的在`location`上下文中使用`if`的场景是：

* [return](http://wiki.nginx.org/NginxHttpRewriteModule#return) ...;
* [rewrite](http://wiki.nginx.org/NginxHttpRewriteModule#rewrite) ... last;

任何其他情况都可能引发不可预知的行为，包括潜在的分段错误。

需要注意的是`if`的行为并不是始终如一的。两个相同的请求不会在其中一个上失败而在另一个上成功，通过完善的测试并且对`if`有深刻理解的话，它可以使用。但是仍然强烈建议使用其他指令来代替。

这些情况下可能你不能轻易的避免使用`if`，比如说你想测试一个变量，就没有类似的指令可以替代。

{% codeblock lang:nginx %}
if ($request_method = POST ) {
  return 405;
}
if ($args ~ post=140){
  rewrite ^ http://example.com/ permanent;
}
{% endcodeblock %}

## 用什么替代

在符合你的需求前提下，可以用[`try_files`](http://wiki.nginx.org/NginxHttpCoreModule#try_files)代替。在其他情况下用"return ..."或"rewrite ... last"。在有些情况下可以将`if`移动到server级别（在这里它是安全的，只有其他重写模块指令允许写在它里面）。

例如，下面的的用法在处理请求时可以安全的修改`location`。

{% codeblock lang:nginx %}
location / {
    error_page 418 = @other;
    recursive_error_pages on;
 
    if ($something) {
        return 418;
    }
 
    # 一些配置
    ...
}
 
location @other {
    # 其他配置
    ...
}
{% endcodeblock %}

有些情况下，使用嵌入式脚本模块（[嵌入式perl](http://wiki.nginx.org/EmbeddedPerlModule)，或其他[第三方模块](http://wiki.nginx.org/3rdPartyModules)）来写这些脚本。

## 例子

下面是一些例子用来解释为什么"if is evil"。不要在家里尝试这些，你被警告过了。

{% codeblock lang:nginx %}
# 下面用一些意想不到的bug来说明在location块中if is evil
# 只有第二个header会被输出到响应，这事实上不是bug，它就是这样工作的。
 
location /only-one-if {
    set $true 1;
 
    if ($true) {
        add_header X-First 1;
    }
 
    if ($true) {
        add_header X-Second 2;
    }
 
    return 204;
}
 
# 请求会被发送到后端但是uri不会改变为'/'，这是if造成的
 
location /proxy-pass-uri {
    proxy_pass http://127.0.0.1:8080/;
 
    set $true 1;
 
    if ($true) {
        # nothing
    }
}
 
# 因为if的问题，try_files不会起作用
 
location /if-try-files {
     try_files  /file  @fallback;
 
     set $true 1;
 
     if ($true) {
         # nothing
     }
}
 
# nginx会引发段冲突
 
location /crash {
 
    set $true 1;
 
    if ($true) {
        # fastcgi_pass here
        fastcgi_pass  127.0.0.1:9000;
    }
 
    if ($true) {
        # no handler here
    }
}
 
# 捕获的别名在if创造的嵌套location中不会被正确的继承
 
location ~* ^/if-and-alias/(?<file>.*) {
    alias /tmp/$file;
 
    set $true 1;
 
    if ($true) {
        # nothing
    }
}
{% endcodeblock %}

假如你发现了一个没有在上面列出来的例子 - 请将它报告给[MaximDounin](http://wiki.nginx.org/User:MaximDounin)。

## 为什么这些问题存在但没有被修复

`if`指令是重写模块的一部分而且是必须的。从另一方面说，nginx的配置通常来说是说明式的。有些用户希望尝试在`if`指令中使用非重写的指令，这造成了这种处境。它大部分时间是有效的，但是。。。瞧上面。

看起来唯一正确的方式就是完全避免在`if`中使用非重写指令。这会破坏很多已存在的配置，所以这没有被实施。

## 假如你还是想用`if`

假如你读了上面的内容仍然想用`if`：

* 请确保你知道它是怎么工作的。一些基础知识可以[看这里](http://agentzh.blogspot.com/2011/03/how-nginx-location-if-works.html)
* 做完整的测试

你被警告过了。

[原文链接](http://wiki.nginx.org/IfIsEvil)
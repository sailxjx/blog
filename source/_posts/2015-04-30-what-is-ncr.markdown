---
layout: post
title: "NCR 是什么"
date: 2015-04-30 09:45
comments: true
categories: [ncr, html]
---

一直以来，对爬虫抓取的某些内容感到很费解，比如形似 `&#x4e2d;&#x56fd;` 的字符串，最初以为是经过编码的 unicode 字符，但是尝试了各种手段都无法解开。最近开始关注起这个问题，机缘巧合在知乎上搜到了[一个回答](http://www.zhihu.com/question/21390312)，原来这些是叫做 [numeric character reference (NCR)](http://en.wikipedia.org/wiki/Numeric_character_reference) 的转义序列，在这里记录一下，权作备忘。

以下一段引用自知乎梁海的[回答](http://www.zhihu.com/question/21390312/answer/18091465)：

> 形如 ——
>
> `&#dddd;`
>
> `&#xhhhh;`
>
>`&#name;`
>
> —— 的一串字符是 HTML、XML 等 SGML 类语言的转义序列（escape sequence）。它们不是「编码」。
>
> 以 HTML 为例，这三种转义序列都称作 character reference：
>
> 前两种是 numeric character reference（NCR），数字取值为目标字符的 Unicode code point；以「&#」开头的后接十进制数字，以「&#x」开头的后接十六进制数字。
> 后一种是 character entity reference，后接预先定义的 entity 名称，而 entity 声明了自身指代的字符。
>
> 从 HTML 4 开始，NCR 以 Unicode 为准，与文档编码无关。
>
> 「中国」二字分别是 Unicode 字符 U+4E2D 和 U+56FD，十六进制表示的 code point 数值「4E2D」和「56FD」就是十进制的「20013」和「22269」。所以 ——
>
> `&#x4e2d;&#x56fd;`
>
> `&#20013;&#22269;`
>
> —— 这两种 NCR 写法都会在显示时转换为「中国」二字。

至于怎么去 `encode/decode` 这些字符，在网上找到了一个简单的 [`Javascript` 版本](http://snipplr.com/view/772/encode-numeric-character-reference/)：

```javascript
String.prototype.ncr2c = function( ) {
  return this
    .replace( /&#x([\da-f]{2,4});/gi,
    function( $0, $1 ) { return String.fromCharCode( "0x" + $1 ) } )
}
String.prototype.c2ncr = function( ) {
  return this .ncr2c( ).replace( /./g,
    function( $0 ) { return "&#x" + $0.charCodeAt( ).toString( 16 ).toUpperCase( ) + ";" } )
}

alert( "&#x61;&#x6A;&#x61;&#x78;".ncr2c( ) );
//ajax

alert( "a&#x6A;ax".c2ncr( ) );
//&#x61;&#x6A;&#x61;&#x78;
```

由于上面的方案是以修改 String 原型来实现的（一般不认为这是一个好做法），使用的时候可以改成两个方法。

而另一种更 robust 的方案则是使用第三方库，例如 [`he` 模块](https://github.com/mathiasbynens/he)，轻松实现对很多转义序列的支持。

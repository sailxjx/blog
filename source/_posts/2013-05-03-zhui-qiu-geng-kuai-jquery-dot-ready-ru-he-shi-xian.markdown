---
layout: post
title: "追求更快: jQuery.ready 如何实现"
date: 2013-05-03 13:30
comments: true
categories: [javascript, jquery]
---

jQuery从1.0版本就提供了一个叫做`ready`的方法，最常见的调用场景是`$(document).ready(handler)`，表示当dom树下载完成后触发事件，也可以简写成`$(handler)`。

以前一直以为这个方法和javascript自己提供的`window.onload`事件是一回事，直到最近在做一个chrome小插件时搜索了一下，才发现这俩区别还是挺大的。

## window.onload

`window.onload`是javascript自身提供的一个方法，表示当dom树中所有元素，包括img，js，css等等资源文件都加载完毕时触发的事件。最直观的就是在浏览器加载过程中标签栏上会有一个转动的loading图标，当这个图标消失或停止转动，就是onload时间触发的时候。这个方法还有另一种绑定的方式`window.addEventListener('load', handler)`。

这样就可以理解，假如页面中某个图片的加载时间特别长，那么`window.onload`是不可能触发的，但是大多数时候，我们想要的只是等dom结构完整加载之后，就可以绑定一些js事件了，假如我们把js代码都写在`window.onload`中，显然会有很长一段时间用户的操作得不到响应。

所以`jquery`提供了`ready`方法。

## $(document).ready(handler)

根据[官方文档](http://api.jquery.com/ready/)，`ready`方法与`<body onload="">`属性不兼容，不过后者现在也不是很常见了。下面一段代码放到浏览器中跑一下可以实验`ready`与`window.onload`谁先触发。

{% codeblock lang:javascript %}
<script>
window.onload = function(){
    alert('window onload');
};
$(document).ready(function(){
    alert('document ready');
});
</script>
{% endcodeblock %}

这个方法又是如何实现的呢？其实是利用了javascript中的`DOMContentLoaded`事件，至于这个事件为什么没有得到广泛的利用，可能是出于兼容性的考虑，至少在IE9以下，这个事件没有得到支持，所以jquery中使用了IE事件模型中的`onreadystatechange`来取代此方法。通过查找jquery源代码可以看到下面几行：

{% codeblock jquery-1.9.1.js lang:javascript %}
// Catch cases where $(document).ready() is called after the browser event has already occurred.
// we once tried to use readyState "interactive" here, but it caused issues like the one
// discovered by ChrisS here: http://bugs.jquery.com/ticket/12282#comment:15
if ( document.readyState === "complete" ) {
    // Handle it asynchronously to allow scripts the opportunity to delay ready
    setTimeout( jQuery.ready );

// Standards-based browsers support DOMContentLoaded
} else if ( document.addEventListener ) {
    // Use the handy event callback
    document.addEventListener( "DOMContentLoaded", completed, false );

    // A fallback to window.onload, that will always work
    window.addEventListener( "load", completed, false );

// If IE event model is used
} else {
    // Ensure firing before onload, maybe late but safe also for iframes
    document.attachEvent( "onreadystatechange", completed );

    // A fallback to window.onload, that will always work
    window.attachEvent( "onload", completed );

    // If IE and not a frame
    // continually check to see if the document is ready
    var top = false;
......
{% endcodeblock %}

这是jquery中`ready`方法实现的一个片段，可以通过检测`document.readyState == "complete"`来判断`DOMContentLoaded`事件是否触发。

## 在chrome扩展中

在chrome扩展的content_scripts中，使用`DOMContentLoaded`有时候并不能得到想要的结果，这是content_scripts往往在页面加载后再插入页面，就不能响应这个事件了。解决办法是在manifest.json中加入`"run_at":"document_start"`一行：

{% codeblock lang:json %}
  "content_scripts": [
    {
      "matches": ["http://*/*"],
      "js": ["ghost.js"],
      "run_at": "document_start"  // add run_at document_start
    }
  ],
{% endcodeblock %}

# 参考资料
* [api.jquery.ready](http://api.jquery.com/ready/)
* [how-does-jquerys-document-ready-function-work](http://stackoverflow.com/questions/5959194/how-does-jquerys-document-ready-function-work)
* [register-domcontentloaded-in-google-chrome](http://stackoverflow.com/questions/5082094/register-domcontentloaded-in-google-chrome)
* [DOMContentLoaded](https://developer.mozilla.org/en-US/docs/DOM/Mozilla_event_reference/DOMContentLoaded)
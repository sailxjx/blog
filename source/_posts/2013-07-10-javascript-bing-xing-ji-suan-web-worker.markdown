---
layout: post
title: "Javascript并行计算： web worker"
date: 2013-07-10 12:09
comments: true
categories: [javascript, html5]
---

最近发现了chrome下面的一个奇特现象，像下面这样的一段代码：

{% codeblock lang:javascript %}
setInterval(function() {
  document.title = document.title.substr(1) + document.title.substr(0, 1);
}, 300);
{% endcodeblock %}

这段代码本来是为了让标签栏内容出现滚动的效果，每300毫秒变化一次，这本来没什么问题，但是偶然切换的其他标签时，这个滚动的速度就会变慢，网上查了一下，原来[chrome设计如此](https://codereview.chromium.org/6577021)，当标签页不活动时，chrome会将所有定时任务的最小间隔设置为1秒，这样来减轻浏览器的压力，会影响所有带有timer的方法，如`setInterval`和`setTimeout`。像上面这样的任务，间隔就被提高到了1秒。

由此引发的思考是，假如这个任务实时性要求很高，不容许这种时间机器的出现怎么办。stackoverflow也有人给出了[一种解答](http://stackoverflow.com/questions/5927284/how-can-i-make-setinterval-also-work-when-a-tab-is-inactive-in-chrome)，不使用内置的timer，而是在代码中主动计算时间差，来模拟`setInterval`的行为。这种方法能解决问题，但是总觉得不够“优雅”。更好的方法是使用html5的[web worker](http://www.whatwg.org/specs/web-apps/current-work/multipage/workers.html)。

web worker目前支持的浏览器包括Firefox 3.5+，Chrome和Safari 4+。你用IE6？那自求多福吧。

搞过消息队列和异步计算的人对worker这个词应该不陌生，html5为我们提供了web worker这样一个优秀的特性，旨在将后台任务和前台交互分开，worker中的任务不会阻塞页面事件。我们先来解决上面提出的问题。

由于不隶属于任意页面，所以chrome不会将worker中的进程timer也改成1秒。所以我们可以对上面的代码稍作修改，拆分成worker和main两部分。

{% codeblock main.js lang:javascript %}
var worker = new Worker('worker.js');
worker.addEventListener('message', function(e) {
    document.title = document.title.substr(1) + document.title.substr(0, 1);
});
{% endcodeblock %}

{% codeblock worker.js lang:javascript %}
setInterval(function() {
    self.postMessage();
}, 300);
{% endcodeblock %}

这样触发更新title的任务就由worker来完成了。

上面只是一个粗浅的demo，worker真正的意义应该还是在并行计算，不过目前的web应用中前端基本没有大运算量的任务，所以worker在这里就没用武之地了。我们可以设想下面一种情况。

md5是很多网站用于保存密码的方式，由此也产生了很多md5解码的工具，由于md5是一种不可逆的加密算法，解密的方法除了使用字典以外，还有一种简单粗暴的方法，就是暴力破解，而这是非常耗时间的。我们拿到了一个加密过的字符串'77b3e6926e7295494dd3be91c6934899'，而且知道明文是一个六位的数字，那么可以用数字循环来制造碰撞(里面的md5方法是引入外部库，这里及不贴出来了)：

{% codeblock main.js lang:javascript %}
var cipher = '77b3e6926e7295494dd3be91c6934899';
var start = new Date();
for(var i=0; i <= 999999; i++) {
  if (md5(i) === cipher) {
    console.log('plain text: ' + i);
    break;
  }
}
console.log('time cost: ' + (new Date() - start));
{% endcodeblock %}

跑下来时间大概是12330毫秒。下面我们用十个worker来分担任务，实现相同的功能。

{% codeblock main.js lang:javascript %}
var cipher = '77b3e6926e7295494dd3be91c6934899',
    workerList = [],
    start = new Date();
for(var i=0; i < 10; i++) {  // init 10 workers
  workerList.push(new Worker('worker.js'));
}
workerList.map(function(worker, index) {  // send task to each worker
  worker.addEventListener('message', function(e) {
    console.log('plain text: ' + e.data);
    workerList.map(function(_worker) {
      _worker.terminate();  // terminate all workers after task finished
    });
    console.log('time cost: ' + (new Date() - start));
  });
  worker.postMessage({
    start: index * 100000,
    end: index * 100000 + 99999,
    cipher: cipher
  });
});
{% endcodeblock %}

{% codeblock worker.js lang:javascript %}
self.addEventListener('message', function(e) {
  for(var i = e.data.start; i <= e.data.end; i++) {
    if (md5(i) === e.data.cipher) {
      self.postMessage(i);
      break;
    }
  }
});
{% endcodeblock %}

最佳期望是总时间的十分之一，实际执行下来用了2792毫秒，这与给worker分配任务的方式有关，假如我们给worker随机指派计算值，那么得到的结果会更平均，而不会因为密文的变化而有大的波动。

web worker对于javascript全局对象的访问也是有一些限制的，比如window，document，parent对象，这也是不能用worker取代所有页面script的一个原因。

关于worker的具体介绍，[这篇文章](http://www.html5rocks.com/en/tutorials/workers/basics/)讲的很好，里面还提供了几个现实的例子，非常详细。

##参考文档
* [The Basics of Web Workers](http://www.html5rocks.com/en/tutorials/workers/basics/)
* [Using HTML5 Web Workers To Have Background Computational Power](http://robertnyman.com/2010/03/25/using-html5-web-workers-to-have-background-computational-power/)
* [9 Web workers](http://www.whatwg.org/specs/web-apps/current-work/multipage/workers.html#creating-a-dedicated-worker)
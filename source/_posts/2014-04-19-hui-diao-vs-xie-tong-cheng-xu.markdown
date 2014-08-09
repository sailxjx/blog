---
layout: post
title: "回调 vs 协同程序"
date: 2014-04-19 15:44
comments: true
categories: [translation, nodejs]
---

> 原文地址：[Callbacks vs Coroutines](https://medium.com/code-adventures/174f1fe66127)

最近 Google V8 引擎的一个补丁提供了 [ES6 生成器](http://wiki.ecmascript.org/doku.php?id=harmony:generators)，一篇叫做[“用 Javascript 生成器来解决回调问题的研究”](http://jlongster.com/A-Study-on-Solving-Callbacks-with-JavaScript-Generators)的文章引发了很大的争议。虽然生成器到目前为止仍然需要 `--harmony` 或 `--harmony-generators` 选项才能激活，但是它已经值得你跃跃欲试！在这篇文章中我想要阐述的是自己对于[协同程序](http://en.wikipedia.org/wiki/Coroutine)的体验，并且说明为什么我认为它们是一种好方法。

## 回调和生成器
在认识回调和生成器之间的不同之前，我们先来看看生成器在 Nodejs 或浏览器这种由回调主宰的环境中是怎样发挥作用的。

首先生成器是回调的一种扩展，有些类型的回调就是用来“模拟”生成器的。这些“futures”，“thunks”，或“promises” —— 无论你怎么称呼，都是用来延迟执行一小段逻辑的，就好比你 yield 了一个变量然后由生成器来处理其他的部分。

一旦这些变量 yield 给了调用方，这个调用方等待回调然后重新回到生成器。见仁见智，生成器的原理和回调其实是一样的，然而下面我们会说到使用它的一些好处。

假如你还是不太清楚该怎么使用生成器，这里有一个简单的例子实现了由生成器来控制流程。

```javascript
var fs = require('fs');
function thread(fn) {
  var gen = fn();
  function next(err, res) {
    var ret = gen.next(res);
    if (ret.done) return;
    ret.value(next);
  }

  next();
}
thread(function *(){
  var a = yield read('app.js');
  var b = yield read('package.json');
  console.log(a);
  console.log(b);
});
function read(path) {
  return function(done){
    fs.readFile(path, 'utf8', done);
  }
}
```

## 为什么协同程序会使代码更健壮

对于传统的浏览器或 Nodejs 环境，协同程序在自己的堆栈上运行每个“纤程”。这些纤程的实现各不相同，但是它们只需要一个很小的栈空间就能初始化（大约4kb），然后随需求增长。

为什么这样棒极了？错误处理！假如你使用过 Nodejs， 你就会知道错误处理不是那么简单。有些时候你会得到多个包含未知边际效应的回调，或者完全忘了回调这回事并且没有正确的处理和汇报异常。也许你忘了监听一个“error”事件，这样的话它就变成了一个未捕获的异常而让整个进程挂掉。

有些人喜欢使用进程，而且这样也挺好，但是作为一个在早期就使用 Nodejs 的人来说，在我看来这种流程有很多地方值得改进。Nodejs 在很多方面都很出色，但是这个就是它的阿喀琉斯之踵。

我们用一个简单的例子来看看由回调来读写同一个文件：

```javascript
function read(path, fn) {
  fs.readFile(path, 'utf8', fn);
}
function write(path, str, fn) {
  fs.writeFile(path, str, fn);
}
function readAndWrite(fn) {
  read('Readme.md', function(err, str){
    if (err) return fn(err);
    str = str.replace('Something', 'Else');
    write('Readme.md', str, fn);
  });
}
```

你可能会想这看起来也没那么糟糕，那是因为你整天看到这样的代码！好吧这是错误的:)为什么？应为大多数 node 核心方法，和多数第三方库都没有 try/catch 他们的回调。

下面的代码会抛出一个未捕获异常而且没有任何方法能捕获它。就算内核检测到这个异常并且告诉调用方这可能是一个错误点，大多数回调都有未知的行为。

```javascript
function readAndWrite(fn) {
  read('Readme.md', function(err, str){
    throw new Error('oh no, reference error etc');
    if (err) return fn(err);
    str = str.replace('Something', 'Else');
    write('Readme.md', str, fn);
  });
}
```

所以生成器是怎么来优化这一点的？下面的代码片段用生成器和 [Co](https://github.com/visionmedia/co) 库来实现了相同的逻辑。你可能会想“这只是一些愚蠢的语法糖而已” - 但是你错了。只要我们将生成器传给 `Co()` 方法，所有委派给调用方的 yields，特别是强健的错误处理都会由 Co 来委派。

```javascript
co(function *(){
  var str = yield read('Readme.md')
  str = str.replace('Something', 'Else')
  yield write('Readme.md', str)
})
```

就像下面这样，Co 这样的库会将异常“抛”回给他们原本的流程，这意味着你可以用 try/catch 来捕获异常，或者任其自流由最后 Co 的回调来处理这些错误。

```javascript
co(function *(){
  try {
    var str = yield read('Readme.md')
  } catch (err) {
    // whatever
  }
  str = str.replace('Something', 'Else')
  yield write('Readme.md', str)
})
```

在编写 Co 的时候貌似只有它实现了健壮的错误处理，但是假如你看一下 Co 的[源代码](https://github.com/visionmedia/co/blob/master/index.js#L30)你会注意到所有的 try/catch 代码块。假如你用生成器你需要将 try/catch 添加到每个你用过的库中，来保证代码的健壮性。这就是为什么在今天看来，用 Nodejs 编写健壮性代码是一件不可能完成的任务。

## 生成器对于协同程序
生成器有时会被当成“半协同程序”，一个不完善，仅对调用方有效的协同程序。这让使用生成器比协同程序的目的更明确，好比 yield 能被当成“线程”。

协同程序要更加灵活一些，看起来就像是普通代码块，而不需要 yield：

```javascript
var str = read('Readme.md')
str = str.replace('Something', 'Else')
write('Readme.md', str)
console.log('all done!')
```

有些人认为完整的协同程序是“危险的”，因为它不清楚哪个方法有没有延迟执行线程。个人来说我认为这种争论很可笑，大部分延迟执行的方法都很明显，比方说从文件或套接字中读写，http 请求，睡眠等等延迟执行不会让任何人感到惊讶。

假如有些不友善的方法，那么你就 “fork” 它们来强迫这些任务变成异步的，就像你在 Go 中做的一样。

在我看来生成器可能比协同程序更危险（当然比回调好得多）——仅仅是忘记一个 yield 表达式就可能让你费解或在它执行下面的代码时导致未知的行为结果。半协同程序和协同程序两者各自有优缺点，但是我很高兴现在至少已经有了其一。

让我们来看看你用生成器可以怎样实现新的构造方法。

## 用协同程序实现简单的异步流程
你已经看到一个简单读/写表达式看起来比回调更优雅，我们来看看更多的内容。

假设所有操作默认按顺序执行简化了模型，有些人声称生成器或协同程序使状态变得复杂化，这事不正确的。用回调处理状态也是一样的。全局变量依然是全局变量，局部变量依然是局部变量，而闭包依然是闭包。

我们用例子来说明这个流程，假设你需要请求一个 web 页面，解析其中的链接，然后同步请求所有的链接并输出他们的 Content-types。

这里是一个使用传统回调的例子，没有使用第三方流程控制库。

```javascript
function showTypes(fn) {
 get('http://cloudup.com', function(err, res){
   if (err) return fn(err);
   var done;
   var urls = links(res.text);
   var pending = urls.length;
   var results = new Array(pending);
   urls.forEach(function(url, i){
     get(url, function(err, res){
       if (done) return;
       if (err) return done = true, fn(err);
       results[i] = res.header['content-type'];
       —pending || fn(null, results);
     });
   });
 });
}

showTypes(function(err, types){
  if (err) throw err;
  console.log(types);
});
```

这么简单的一个任务被回调搞得毫无可读性。再加上错误处理，重复回调的预防，存储结果和他们本身的一些回调，你会完全搞不懂这个方法是用来干嘛的。假如你需要使代码更健壮，还需要在最后的方法处加上 try/catch 代码块。

现在下面有一个由生成器实现的相同的 showTypes() 方法。你会看到结果和用回调实现的方法是一样的，在这里例子中 Co 处理了所有我们在上面需要手工处理的错误和结果集的组装。被 urls.maps(get) 方法 yield 的数组被平行执行，但是结果集然后是保持不变的顺序。

```javascript
function header(field) {
  return function(res){
    return res.headers[field]
  }
}
function showTypes(fn) {
  co(function *(){
    var res = yield get('http://cloudup.com')
    var responses = yield links(res.text).map(get)
    return responses.map(header('content-type'))
  })(fn)
}
```

我并不是建议所有的 Npm 模块使用生成器并且强制依赖 Co，我仍然建议使用相反的方法 —— 但是在应用层面我强烈推荐它。

我希望这能说明协同程序在编写无阻塞的程序时是一个强有力的工具。

---
layout: post
title: "Node.js 沙盒"
date: 2015-06-18 16:36
comments: true
categories: [nodejs, sandbox]
---

## 为什么要使用沙盒

eval 在很多语言中都是一个很有用的方法，合理利用它可以编写出很多让人拍案叫绝的功能。但是由于它实在过于开放和危险，很多人给它冠上了 **evil** 的称号。

使用沙盒可以给 eval 类的功能增加一些条件限制，让它变得更加安全，而不丢失其灵活性。

Node.js 中提供的 vm 模块可以轻松实现沙盒的功能。

## 如何使用 vm 模块

### `vm.runInThisContext`

`vm.runInThisContext` 可以执行代码并得到它的返回值，被执行的代码没有权限访问本地对象，但是可以访问全局对象。相比之下， eval 则有权限访问上下文中的对象。

```javascript
var localVar = 'initial value';

var vmResult = vm.runInThisContext('localVar = "vm";');
console.log('vmResult: ', vmResult);
console.log('localVar: ', localVar);

var evalResult = eval('localVar = "eval";');
console.log('evalResult: ', evalResult);
console.log('localVar: ', localVar);

// vmResult: 'vm', localVar: 'initial value'
// evalResult: 'eval', localVar: 'eval'
```

### `vm.createContext` 与 `vm.runInContext`

`vm.createContext` 则是真正创造了一个沙盒对象，使用 `vm.runInContext` 可以完全让代码在这个沙盒环境中运行。

```javascript
var util = require('util');
var vm = require('vm');

sandbox = vm.createContext({ globalVar: 1 });

for (var i = 0; i < 10; ++i) {
    vm.runInContext('globalVar *= 2;', sandbox);
}

console.log(util.inspect(sandbox));
console.log(global.globalVar);

// { globalVar: 1024 }
// undefined
```

## vm 的具体应用

[`configd`](https://github.com/teambition/configd) 是我为公司部署流程开发的一个小工具，功能是将各种来源的配置文件合并成一个 json 文件。由于它支持 `ssh`, `git`, `http` 等多种来源的配置或代码，所以需要在工具内部来执行这些代码以实现和本地 `require` 类似的效果。如果用 `eval`，那么除却风险问题，`module.exports` 也不能生效了。所以在工具中使用了 vm 模块来执行这些代码。

```coffeescript
_eval = (js, options = {}) ->
  sandbox = vm.createContext()
  sandbox.exports = exports
  sandbox.module = exports: exports
  sandbox.global = sandbox
  sandbox.require = require
  sandbox.__filename = options.filename or 'eval'
  sandbox.__dirname = path.dirname sandbox.__filename

  vm.runInContext js, sandbox

  sandbox.module.exports

data = _eval js
```

## 参考资料

[Executing JavaScript](https://nodejs.org/api/vm.html)

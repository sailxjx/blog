---
layout: post
title: "sort 的错误用法"
date: 2014-11-27 14:07
comments: true
categories: [sort, javascript]
---

前不久同事的代码中出了一个很神奇的问题，大致流程是对一个由对象组成的数组进行排序，其中属性 a 用于排序，属性 b 作为一个优选条件，当 b 等于 1 的时候无论 a 值是什么，都排在开头 。这本是一个很简单的问题，问题就在于他用两次 sort 实现在这次排序，先根据 a 的属性排序，然后再根据 b 的值来排序。问题就出在第二次排序中。

我们想当然的会认为在第一次排序中，数组已经根据 a 的属性由大到小排序，在第二次中我们只要不去动原数组的顺序就行（一般在方法中写成返回0或-1），只考虑单独把 b 等于 1 的元素提到前面去。但是其实这与语言所选用的排序算法有关，javascript （和一起其他语言）内置的 sort 方法采用的是几种排序算法的集合，有时并不能保证相同元素的位置保持一致。

下面是从 [stackoverflow](http://stackoverflow.com/questions/27071942/array-sort-is-producing-unexpected-results-when-elements-are-equal) 上面找来的一个例子

```javascript
var arrayToSort = [
  {name: 'a', strength: 1}, {name: 'b', strength: 1}, {name: 'c', strength: 1}, {name: 'd', strength: 1},
  {name: 'e', strength: 1}, {name: 'f', strength: 1}, {name: 'g', strength: 1}, {name: 'h', strength: 1},
  {name: 'i', strength: 1}, {name: 'j', strength: 1}, {name: 'k', strength: 1}, {name: 'l', strength: 1},
  {name: 'm', strength: 1}, {name: 'n', strength: 1}, {name: 'o', strength: 1}, {name: 'p', strength: 1},
  {name: 'q', strength: 1}, {name: 'r', strength: 1}, {name: 's', strength: 1}, {name: 't', strength: 1}
];

arrayToSort.sort(function (a, b) {
  return b.strength - a.strength;
});

arrayToSort.forEach(function (element) {
  console.log(element.name);
});

```

我们会以为最后元素的值还是从 a 到 t，但实际运行下来的结果却是乱序的，这是因为 sort 的算法并没有保留原数组的顺序，也即 [unstable](http://www.ecma-international.org/ecma-262/5.1/#sec-15.4.4.11)。

那么我们就该尽量避免这种情况发生，就我同事的例子，将两次 sort 的逻辑合并在一次中应该是个可行的办法，如果必须分为多次 sort，那么就把原数组的顺序记录在元素的属性上把。

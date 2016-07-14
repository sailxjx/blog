---
layout: post
title: "介绍BDD"
date: 2014-03-23 12:56
comments: true
categories: ['bdd', 'translation']
---

> 原文地址：<http://dannorth.net/introducing-bdd/>

我有一个问题。在不同环境中使用或教授敏捷实践方法（如测试驱动开发TDD）时，我常常会有同样的困惑和误解。程序员希望知道从何开始，什么应该测试，什么不改测试，并且理解为什么会测试失败。

我越研究TDD，就越学会融会贯通。我感觉“假如有人告诉我这个就好了”的次数比“哇哦，豁然开朗”多得多。我感到一定有一种方法能用来更好的描述TDD并且避免陷阱。

我的回答是行为驱动开发（BDD）。这已经成了敏捷实践的一种并且更容易让新手理解和掌握。随着时间的推移，BDD已经发展为涵盖敏捷分析和更广泛的自动化验收测试的内容。

## 测试方法必须是句子命名

我第一次顿悟是在我使用一个叫做agiledox的组件时，这个组件是由我的同事Chris Stevenson写的。它使用JUnit测试类，用纯粹的句子输出方法名，所以一个测试用例看起来是这样的。

```java
public class CustomerLookupTest extends TestCase {
    testFindsCustomerById() {
        ...
    }
    testFailsForDuplicateCustomers() {
        ...
    }
    ...
}
```

输出的内容像这样：

```
CustomerLookup
- finds customer by id
- fails for duplicate customers
- ...
```

单词'test'在类名和方法名中去掉了，驼峰形式的方法名被转成了普通的文本内容，这就是它所有的事情，既有效又迷人。

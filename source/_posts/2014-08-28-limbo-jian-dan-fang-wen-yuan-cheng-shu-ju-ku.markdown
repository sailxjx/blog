---
layout: post
title: "limbo: 简单访问远程数据库"
date: 2014-08-28 17:41
comments: true
categories: [nodejs, mongodb, mongoose, database]
---

对于 nodejs 生态来说，使用 mongoose 作为 Model 模块是再好不过的一件事，其一大特点就是简洁优雅的 Schema 定义，提供了每个键值的类型验证，数据验证，索引声明，虚拟键，并自带实例化方法的扩展，大大节省了开发的成本。但是在考虑开放数据的时候，一切就显得不那么美好了。

在打造[简聊](https://talk.ai/)这款应用的过程中，我们就实实在在的遇到了这样的问题。由于需要使用 [Teambition](https://www.teambition.com/) 的用户和团队数据，并且当[简聊](https://talk.ai/)更新了用户数据之后，在 [Teambition](https://www.teambition.com/) 中能实时的将这些更新推送到用户那里。按照惯例，我们最初使用的是 restful 接口。

## 第一阶段，使用 restful 接口

restful 接口的应用面最广，但是仍然存在很多不足，比如接口在参数和结构上限制较多，在考虑修改接口 api 的时候，往往会顾虑客户端的兼容性，而一旦客户端程序有新的需求，则需等待接口的更新。另一个麻烦的地方是需要做签名校验，对于内部的应用来说，我们完全可以通过防火墙来控制特定 ip 对端口的访问，签名在此处就显得有点多余。

## 第二阶段，单独拆封 Schema

然后我们想到了将 Schema 拆封成一个单独的仓库，nodejs 有良好的模块管理，在不同的应用中，我们只需要将这些模块引入进来，既做到同步更新，又做到 DRY。相对于 restful 接口的缺点就是，对于数据的调用入口过多，而且应用之间互相是不知情的。例如在[简聊](https://talk.ai/)中有更新用户数据，在 [Teambition](https://www.teambition.com) 中就无法得知，并推送给其他客户端。

## 第三阶段，远程过程调用（rpc）

这个阶段和 restful 接口其实类似，我们在 [Teambition](https://www.teambition.com)  的后端进程中将一些接口方法暴露出来，这样我们的客户端程序就能通过简单的 rpc 方式调用这些接口。例如我们导出了 `user.update` 方法，在客户端代码中使用 `rpc.call('user.update', params, callback)` 即可调用相应的过程。这样的调用行为与使用本地代码无异，可能是目前能找到的最简单直接的方式了。

## 第四阶段，rpc 与 mongoose 的结合

事情可以变得更简单，由于目的主要是为了操作数据库，所以我们开发了一个模块 [limbo](https://github.com/teambition/limbo)，将 mongoose model 中所有方法暴露出来，以命名空间来划分，实现了在客户端与服务端程序一致的使用体验。

例如我们在服务端程序中使用 limbo 连接 mongodb，只需要做如下声明：（以下的代码都以 coffeescript 作为示例）

```coffeescript
limbo = require 'limbo'

# 定义 Schema
UserSchame = (Schema) ->
  # 这里的 Schema 即 mongoose.Schema
  new Schema
    name: String
    email: String

# use 方法用作区分不同数据库连接的命名空间，一般参数选择数据库名就行
db = limbo.use('test').connect('mongodb://localhost:27017/test').load 'User', UserSchema
```

使用方式就与 mongoose 一致了

```coffeescript
user = db.user
# user 是一个 limbo 中用于封装 model 的一个对象，你可以直接使用 user.model 来直接调用 mongoose model
user.findOne _id: 'xxxx'
user.create name: 'xxx', email: 'yyy'
```

下面是 limbo 中最激动人心的地方，你可以导出一个 collection 中的所有方法到 rpc server 中，只需要通过一个简单的声明

```coffeescript
limbo.use('test').bind(7001).enableRpc()
```

下面我们就要提到如何在客户端程序中调用这些方法

```coffeescript
# 在客户端也需要初始化一个 limbo 命名空间，需要与服务端一致，链接改为服务端的域名和端口号
db = limbo.use('test').connect('tcp://localhost:7001')

# 下面有两种方式来使用 rpc
# 1. 使用 call 方法
db.call 'user.findOne', _id: 'xxxx', ->
# 2. 使用方法链
db.user.findOne _id: 'xxxx', ->
# 第二种方式存在一个延迟，必须要在 limbo 与服务端程序握手成功之后才可以使用，
# 否则会抛出一个对象不存在的异常，不过在一般的应用中，
# 初始化所需的时间都会长于这个链接所需时间，所以延迟可以忽略不计了
```

可以看出，上面的第二种方式与服务端在本地使用 mongoose 的方式一模一样，这种黑魔法式的调用方式应该是广大码农喜闻乐见的。

limbo 另一个值得称道的功能是可以在服务端程序监听这些远程调用的事件，这得益于 nodejs 的 event 对象，limbo 本身就继承于 EventEmitter 对象，所以我们在每次远程调用后会触发一个事件给服务端程序，而在服务端只需要简单的监听这个事件即可

```coffeescript
limbo.on 'test.user.findOne', (user) -> ...
```

正是这种 rpc 加事件反馈的机制，让[简聊](https://talk.ai/)和 [Teambition](https://www.teambition.com) 可以实现简单实时的数据交换。我们将 [limbo](https://github.com/teambition/limbo) 托管在 github 上开源，是深知它还存在很多可以改进的地方，所以不免庸俗的说一句，欢迎 issue 和 pr~

最后，欢迎访问我们的新产品[简聊](https://talk.ai)，一款基于话题的轻量级协作应用。

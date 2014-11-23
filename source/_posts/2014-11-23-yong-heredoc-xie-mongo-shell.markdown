---
layout: post
title: "用 heredoc 写 mongo shell"
date: 2014-11-23 12:24
comments: true
categories: [mongo, heredoc, shell]
---

mongo shell 给我们提供了很便捷的 mongodb 操作接口，很多人应该用过 `mongo` 命令执行 javascript 文件，或者通过 `mongo --eval` 执行脚本。两种方式各有千秋，使用 js 文件可编辑较复杂的代码逻辑，而且可以作为脚本储存以备重复使用。使用 `mongo --eval` 比较灵活，随取随用，但是当代码中有换行时，就蛋疼了。mongo [官方的文档](http://docs.mongodb.org/v2.6/administration/scripting/) 并没有提到 eval 在处理多行代码时的解决方案，好在最近发现用 heredoc 可以完美的解决这个问题。

# heredoc

heredoc 在 [wiki](http://en.wikipedia.org/wiki/Here_document) 上解释为一段可被当做独立文件的代码片段，一般表现为下面这种形式：

```bash
tr a-z A-Z <<END_TEXT
one two three
uno dos tres
END_TEXT
```

这里的 `<<END_TEXT` 到 `END_TEXT` 就是 heredoc 了，虽然语法简单，用处可就大了。在这里正好解决了在 `mongo --eval` 中遇到的问题，由于这段字符串可被当成文件来使用，所以直接跟在 `mongo` 命令后面就行

```bash
mongo localhost/test <<MONGO
db.users.save({name: "mongo"})
MONGO
```

在编写 [mms](https://github.com/sailxjx/mms) 这个迁移模块的时候，如果没有 heredoc，则不免需要生成一些 js 临时文件来给 mongo 执行，现在，[直接拼接成字符串就行](https://github.com/sailxjx/mms/blob/master/src/mongo.coffee)。

最后，使用中不要忘了将一些字符转义掉，比如 '$'，以免被当成 shell 变量引用了。

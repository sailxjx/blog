---
layout: post
title: "awk 学习笔记(1)"
date: 2013-01-16 21:46
comments: true
categories: [awk, note]
---

##第一个awk程序
 
{% codeblock lang:awk %}
#!/bin/awk -f
{
    print
}
{% endcodeblock %}

这个程序会将所有的输入原封不动的输出，直到EOF(ctrl+d)

##在shell中使用awk

###命令行
{% codeblock lang:bash %}
$ awk 'BEGIN { print "Here is a single quote <'\''>" }'
Here is a single quote <'>
{% endcodeblock %}

需要注意的是，在命令行下引号的嵌套可能会造成一些难以预料的错误，假如在awk脚本内需要用到单引号，那就在它之前使用转义符`\`，并且不要忘了用另一个单引号结束它前面的字符，就上上面做的那样，实际分成了三段awk脚本，shell将他们链接起来之后实际就成了`BEGIN { print "Here is a single quote <'>" }`。

###在shell文件中
{% codeblock lang:bash %}
FIND_DATA=$(awk '
BEGIN {
    print "here is a single quote <'\''>"
}
')
{% endcodeblock %}

有的时候awk会写的很长，这个时候需要换行，直接用单引号两边包住即可，注意脚本中的单引号还是需要转义的。

##使用正则表达式
{% codeblock lang:bash %}
$ awk '/^foo/ { print $0 }' BBS-list
{% endcodeblock %}

上面的命令在BBS-list中匹配出所有以`foo`开头的行

##有用的内置参数
* FS    定义分隔符
* OFS   定义输出分隔符
* NF    行数

##有用的方法
* length($1)    计算字符串或数组长度
* srand()       生成随机数种子
* rand()        生成一个浮点随机数，需要跟srand配合

### [wiki tag](http://www.gnu.org/software/gawk/manual/gawk.html#Statements_002fLines)
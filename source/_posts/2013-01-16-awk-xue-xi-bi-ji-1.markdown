---
layout: post
title: "awk 学习笔记(1)"
date: 2013-01-16 21:46
comments: true
categories: [awk, learn]
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
$ awk '/^foo/ { print $0 }
> /foo/ { print $0 }' BBS-list
{% endcodeblock %}

上面的命令在BBS-list中匹配出所有以`foo`开头的行，多个表达式可以用在同一行上，会将匹配结果打印在不同行上。

{% codeblock lang:bash %}
$ awk '{ if($1 ~ /foo$/) print $0 }' BBS-list
macfoo       555-6480     1200/300          A
sabafoo      555-2127     1200/300          C
{% endcodeblock %}

在变量上使用正则表达式，使用`~`或`!~`符号，能满足多数的应用。

{% codeblock lang:bash %}
$ awk '{ REGEXP = "^foo"; if($1 ~ REGEXP) print $0 }' BBS-list
{% endcodeblock %}

动态的设置正则表达式，变量中需要省去两边的`/`。

##BEGIN/END

开头和结尾的两块表达式，可以用来做一些全局参数设定和数据统计。

##有用的内置参数
* FS            定义分隔符
* OFS           定义输出分隔符
* NF            列数
* NR            行号
* RS            输入条目分隔符，awk按这个字符来将整个文本分成不同条记录(默认为"\n")
* ORS           输出条目分隔符
* IGNORECASE=1  忽略大小写(只支持gawk)
* FIELDWIDTHS   指定每列的宽度(只支持gawk)，实际场景中貌似用处不大，除非原文本的格式本身就非常工整(每个field的字符数相等)，下面是`FS`变量可能会影响`FIELDWIDTHS`的地方
{% codeblock lang:bash %}
$ awk 'BEGIN{FS = "a";FIELDWIDTHS= "4 4"}{print $1, FS}' BBS-list # FS不生效
$ awk 'BEGIN{FIELDWIDTHS= "4 4";FS = "a"}{print $1, FS}' BBS-list # FIELDWIDTHS不生效
$ awk -Fa 'BEGIN{FIELDWIDTHS= "4 4"}{print $1, FS}' BBS-list # FS不生效
# 总而言之，就是哪个在后，哪个就优先。
{% endcodeblock %}
* PROCINFO      内置数组，用于记录一些程序信息，包括分隔符类型，进程号，用户组等等
* FPAT          gawk特有的一个变量，不能与FS，FIELDWIDTHS共存，利用正则匹配出对应的field，手册中给出了一个常用的例子，匹配csv文件，`awk -vFPAT="([^,]+)|(\"[^\"]+\")" '{print "NF=",NF; for(i =1 ;i<NF;i++){print $i}}' str.csv`，不过这种写法不够直观，也容易出错，用来应急可以，真刀真枪的干，还是求助其他语言吧
* OFMT          输出格式，默认为`%.6g`，这在格式化数字时比较有效，例如用`%.1f`就是输出四舍五入后的一位小数，而用`%i`就是输出整数了。

##有用的方法
* length($1)    计算字符串或数组长度
* srand()       生成随机数种子
* rand()        生成一个浮点随机数，需要跟srand配合
* tolower()     转小写
* toupper()     转大写
* sub()         `$ awk '{ sub(/foo/, "FOO"); print }' BBS-list`将foo字符串替换成FOO。

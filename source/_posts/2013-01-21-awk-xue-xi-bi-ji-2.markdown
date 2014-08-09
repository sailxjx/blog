---
layout: post
title: "awk 学习笔记(2)"
date: 2013-01-21 17:42
comments: true
categories: [awk, note]
---

##常用的选项
* -F 指定分隔符
* -f 指定调用脚本，可以多次引用，不同文件会被合并成一个awk脚本
* -d 输出所有变量到文件，默认输出到awkvars.out，也可以通过在-d后加文件路径来指定文件，但是注意-d与文件名之间不能有空格。调试的时候这个选项会非常有用。
{% codeblock lang:bash %}
$ awk -d./awk.dump 'BEGIN { foo = "test" } /^foo/ { print $0 }' BBS-list
$ cat ./awk.dump
ARGC: 2
ARGIND: 1
ARGV: array, 2 elements
foo: "test"
...
{% endcodeblock %}
* -p 将命令行下的awk脚本格式化输出到awkprof.out文件，可以在-p后加文件路径来指定文件，注意也不能有空格
* -v 预设置awk程序变量，可以设置多次

##分隔符的四种形式
* `-F " "`      默认，以空格或tab分隔，首尾的空格会被排除掉
* `-F "a"`      以普通字符串分隔，用户指定
* `-F "[: ]"`   以正则表达式分隔，一般在设定多个分隔符时比较有用（如右边就是按`:`或空格分隔）
* `-F ""`       每个字符都是单独的一列，只在gawk中支持

##@include

{% codeblock lang:awk %}
@include './libfoo.awk'
END {
    print "end of file"
}
{% endcodeblock %}

##多行记录
有些文件中相关联的数据可能会分为多行显示，[看手册中的例子](http://www.gnu.org/software/gawk/manual/gawk.html#Multiple-Line)
{% codeblock %}
Jane Doe
123 Main Street
Anywhere, SE 12345-6789

John Smith
456 Tree-lined Avenue
Smallville, MW 98765-4321
{% endcodeblock %}
很明显每个块中的数据是有联系的，然后每个块都以一行空字符分割，那么分析的awk脚本可以写成这样。
{% codeblock lang:awk %}
BEGIN { RS = "" ; FS = "\n" }
{
    print "Name is:", $1
    print "Address is:", $2
    print "City and State are:", $3
    print ""
}
{% endcodeblock %}
这里的RS也是支持正则表达式的

##格式化控制符
OFMT与printf中用到的格式化控制符可以参考c中的printf，具体可以[参考手册](http://www.gnu.org/software/gawk/manual/gawk.html#Control-Letters)

##I/O
awk可以用`>`,`>>`,`|`将输出定向到文件或管道，但需要注意的是后面的文件名或命令都需要用双引号包起来。

##switch
{% codeblock lang:bash %}
$ top -bn1|grep java|grep -v grep|awk '{ switch ($6) { case /m$/: print $6*1024;break; default: print $6; } }'
{% endcodeblock %}
switch语句与C中相同，注意break的使用。此外，在兼容模式下不可用。

##man tag
[Special-Files](http://www.gnu.org/software/gawk/manual/gawk.html#Special-Files)


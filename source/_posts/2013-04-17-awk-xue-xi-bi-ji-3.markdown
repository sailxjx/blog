---
layout: post
title: "awk 学习笔记(3)"
date: 2013-04-17 16:11
comments: true
categories: [awk, note]
---

最近线上服务出了一点问题，然后搞了一堆的log来分析。log的格式很乱，需要整理一下，虽然几个月前特意看了一下awk，但是过了这么久，也忘得差不多了，正好靠这次机会练练手，把失去的记忆找回来。

log中的每条数据都被分成了多行，而且都存在一个文件中，现在文件已经有8G的大小。现在要做的就是将这些多行的数据合并成每条一行，只取有用的部分。

目标明确之后，就是编码啦。

先以日期开头的行为单条数据的起点，这个日期就通过正则来匹配出来，然后再通过substr来截取。在使用substr的时候貌似awk不支持负数输入，没办法，截取末尾字符的时候只能先计算整行的长度再减去要截取的长度了。

{% codeblock lang:awk %}
if ($0 ~ /^2013.*[0-9]'$/) {  # id line
        len = length($0)
        date = substr($0, 0, 10)
        line = substr($0, 0, 19) substr($0, len-8, 8)
}
{% endcodeblock %}

awk中的字符串连接也比较搞，不需要`+,.`等等符号，直接接上就好了，中间可以加上" "来分隔。

下面就是将每行的匹配数据加到line变量后面，最后，一起print出来，这里重定向到文件也是一个不错的选择

{% codeblock lang:awk %}
if ($0 ~ /\[template\]/) {
        line = line" "$3
        print line >> "desc.log."date
}
{% endcodeblock %}

下面还有一个需求就是根据统计类似数据再排排序，awk的数组可以实现统计关键词出现的次数，不过它数组的排序功能实在是太弱了，所以还需要结合sort命令。

{% codeblock lang:bash %}
$ awk '{a[$4] += 1}END{for (k in a) print a[k], k}' $i | sort -rn > "emailsort_${i}"
{% endcodeblock %}

大功告成。

下面整理一下笔记，总结一下用到的东西

* 正则表达式： 根据gawk的官方文档，原版貌似不支持所谓的`interval expression`，也就是一些`\w`,`\d`之类的东西，实际也确实如此，gawk对它进行了扩展，加上`--re-interval`之后就行了。
* substr： 截取字符串
* `>,>>`： 重定向，跟shell一样，不过后面的文件名需要加上双引号
* 数组： 数组的遍历使用`for(k in a) print k`的方式，排序有asort方法，不过这样排序之后key就全部丢掉了，我还是倾向于选择通过管道让`sort`来干这事。

## Link to
* [awk学习笔记1](/blog/2013/01/16/awk-xue-xi-bi-ji-1/)
* [awk学习笔记2](/blog/2013/01/21/awk-xue-xi-bi-ji-2/)
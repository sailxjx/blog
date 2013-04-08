---
layout: post
title: "动态规划求解数组最大差值"
date: 2013-04-08 12:47
comments: true
categories: [code, algorithm]
---

前几天碰到一道有趣的面试题，见微知著，由此记录一下一些新的启发和发现。

题目很简单，给一个数组，其中有n个数字，求后面的数与前面的数的差值，将最大差值的两个数找出来。映射到生活中，就是一个股票何时买入何时卖出能达到最大收益的情况（前提是能预知未来的所有报价）。

一个测试用例是下面这样：

* 输入：[3,10,11,9,1,2,-1,10,7]
* 输出：[-1, 10]

这个题目乍看上去相当的简单，我第一个想到的就是，既然要求最大差值，那可以先将每个值与的数之间的差值求出来，然后再进行一次排序，结果不就出来了嘛。这样需要用到两次循环，代码可以参考下面。

{% codeblock lang:ruby %}
def stepIn(dataInput)
  dataLen = dataInput.length
  diff = nil
  dataResule = {}
  for i in 0...dataLen-1
    for n in i+1...dataLen
      diff2 = dataInput[n] - dataInput[i]
      if diff == nil or diff < diff2
        diff = diff2
        dataResule["#{i},#{n}"] = diff
      end
    end
  end
  rIdxs = dataResule.sort_by {|k,v| -v} [0][0].split ','
  return [dataInput[rIdxs[0].to_i], dataInput[rIdxs[1].to_i]]
end
{% endcodeblock %}

上面的代码已经经过了优化，在每次循环后，保留了之前计算的差值的结果，下面的循环中小于这个差值的索引值就被抛弃了，这样的一个好处是可以减少最后sort时花费的时间。假如保留所有两数之间的差值，假设使用冒泡排序，输入数组长度是m，排序算法复杂度是O(n^2)，而这个n会达到(m+1)*m/2，所以总的算法复杂度就成了O(n^4)。而在循环中预处理之后，最后参与排序的元素个数最大不会超过m，总的时间复杂度还是O(n^2)。其实这只是针对最后的sort而言，而这个程序真正的耗时在上面的嵌套循环，这里的复杂度不管有没有优化，其实都是一样的O(n^2)，下面sort的消费可以忽略不计。

这是一种比较直观的解法了，事实证明，没有经过斟酌的想法都是不完善的（事实也证明，很多灵光一闪的想法都很很靠谱滴，不过在这里不适用^_^）。经人启发，才知道有一种解法，只需要一次循环，时间复杂度是O(n)。

这个叫做动态规划的算法说的太笼统，网上的解释也实在是太理论，我们联系实际，就以上面的题目为例。

动态规划的思想通常可以分成下面几部：

1. 给问题分阶段
2. 确定每个阶段的状态
3. 确定相邻阶段的之间的递推关系（也就是找出从前一个阶段转化到后一个阶段的条件）

上面的例子很容易可以分出三个阶段

1. 开始阶段，将数组中开头的两个元素作为最大，最小值记录在结果数组中，[3,10]
2. 过程阶段，将后面的数与前面的数比较，比如将11与10比较，并将符合条件的值替换结果数组
3. 结束阶段，当游标抵达数组最后一个元素时，跳出循环。

而这几个状态之间的转移条件在上面已有了说明，主要在第二个阶段，哪些条件能决定替换结果数组，这些条件称为决策

1. 游标所指的数大于结果数组中的最大值，比如后面有11，那么结果数组就变成[3,11]
2. 游标所指的数小于结果数组中的最小值，那么它就有可能在后面替换结果数组中的最小值，例如后面出现了1，这个时候不能立刻替换掉3，需要找个临时变量将1保存下来。
3. 游标所指的数与临时最小值之差大于结果数组中两数字之差。这个条件应该优先于决策2和决策1，一旦这个决策生效，将同时替换结果数组中的最大最小值，决策1和决策2在这个时候应该不生效。例如后面出现了12，那么结果数组就应该变成[1,12]。假如这个时候决策1优先生效，那么结果数组会变成[3,12]，而临时变量1将永远没有上位之日了。

有了上面的阶段和决策之后，代码就很容易实现了

{% codeblock lang:ruby %}
def stepIn(list)
  min = 0  # minimal index
  max = 0  # maximal index
  differ = 0  # max differ
  minTmp = nil  # temp minimal index
  for i in 1...list.length
    if minTmp != nil and list[i] - list[minTmp] > differ  # if current index minus temp minimal index is bigger than differ, replace it
      differ = list[i] - list[minTmp]  # new differ
      min = minTmp  # new minimal index
      max = i  # new maximal index
    elsif list[i] > list[max]  # replace the maximal index
      max = i  # new maximal index
      differ = list[i] - list[min]  # new differ
    elsif list[i] < list[min] and ( minTmp == nil or list[i] < list[minTmp] )  # replace the temp minimal index
      minTmp = i  # change temp minimal index
    else
      next
    end
  end
  return [list[min], list[max]]
end
{% endcodeblock %}

这种解法可能读起来需要稍微绕点弯，而且隐含问题，我在第一次写这段代码的时候就将决策3和决策1的顺序搞反了，但是几个测试脚本都顺利通过了，这就是一个“不明显的bug”，往往比“明显的bug”还要致命，因为根本无迹可查。

不过，相对于性能的提高，牺牲一点可读性还是值得的。在上面的例子中不太明显，但是当我将输入数组的长度变成1000甚至5000时，两种算法的反差是相当惊人的。

{% codeblock lang:bash %}
bash$ ruby benchmark.rb 1000
dysort: 0.000435
trsort: 0.13827
bash$ ruby benchmark.rb 5000
dysort: 0.002027
trsort: 3.28997
{% endcodeblock %}

上面是分别在1000和5000的数组长度下运行的结果，可以看出使用第二种算法的时间增长基本是线性的，而使用第一种算法的耗时则会指数级的增长。两种算法横向比较更是高下立分，本来想画图来表示，结果发现差距太大，使用第二种算法的时间柱在图上基本看不到了，于是作罢。
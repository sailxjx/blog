---
layout: post
title: "mysql 中的 date datetime 和 timestamp"
date: 2013-06-05 11:02
comments: true
categories: [mysql]
---

mysql中用于表示时间的三种类型date, datetime, timestamp (如果算上int的话，四种) 比较容易混淆，下面就比较一下这三种类型的异同

# 相同点

* 都可以用于表示时间
* 都呈字符串显示

# 不同点

* 顾名思义，date只表示'YYYY-MM-DD'形式的日期，datetime表示'YYYY-MM-DD HH:mm:ss'形式的日期加时间，timestamp与datetime显示形式一样。
* date和datetime可表示的时间范围为'1000-01-01'到'9999-12-31'，timestamp由于受32位int型的限制，能表示'1970-01-01 00:00:01'到'2038-01-19 03:14:07'的UTC时间。
* mysql在存储timestamp类型时会将时间转为UTC时间，然后读取的时候再恢复成当前时区。
假如你存储了一个timestamp类型的值之后，修改了mysql的时区，当你再读取这个值时就会得到一个错误的时间。而这种情况在date和datetime中不会发生。
* timestamp类型提供了自动更新的功能，你只需要将它的默认值设置为CURRENT_TIMESTAMP。
* 除了date是保留到天，datetime和timestamp都保留到秒，而忽略毫秒。

# 时间格式

mysql提供了一种比较宽松的时间字符串格式用于增删改查。参考[iso时间格式](http://wwp.greenwichmeantime.com/info/iso.htm)，一般习惯于写成'2013-06-05 16:34:18'。但是你也可以简写成'13-6-5'，但是这样容易造成混淆，比如mysql也会把'13:6:5'也当做年月日处理，而当'13:16:5'这种形式，则被mysql认为是不正确的格式，会给出一个警告，然后存入数据库的值是'0000-00-00 00:00:00'。

手册中还特意提到了一种情况，就是当年的值是0~69时，mysql认为是2000~2069，而70~99时则认为是1970~1999。我感觉是一种画蛇添足了。

总之，以不变应万变，使用'YYYY-MM-DD HH:mm:ss'格式总是不会错的。

原文链接：[datetime](http://dev.mysql.com/doc/refman/5.1/en/datetime.html)
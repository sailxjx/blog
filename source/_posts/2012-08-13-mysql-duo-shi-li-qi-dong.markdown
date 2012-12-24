---
layout: post
title: "Mysql 多实例启动"
date: 2012-08-13 17:03
comments: true
categories: [mysql, linux]
---

当开发与生产环境在同一台机器上，或需要在一台机器上部署多套测试环境时，往往需要同时起多个mysqld进程，最近帮测试搭环境的时候就碰到了这样的问题。

还是从安装mysql开始，下载tarball安装，

{% codeblock 安装mysql lang:bash %}
groupadd mysql
useradd -g mysql mysql
cmake . -DCMAKE_INSTALL_PREFIX=/usr/local/mysql5.5.27/  -DMYSQL_DATADIR=/data/mysql  -DWITH_INNOBASE_STORAGE_ENGINE=1  -DMYSQL_TCP_PORT=3306  -DMYSQL_UNIX_ADDR=/var/run/mysql/mysql.sock -DWITH_DEBUG=0
make && make install
{% endcodeblock %}

然后配置my.cnf，从support-files里面拷贝一个my-medium.cnf到/etc/my.cnf，里面mysqld配置段的内容基本是这个样子

{% codeblock my\.cnf配置 %}
[mysqld]
port            = 3306
socket          = /var/run/mysql/mysql.sock
skip-external-locking
key_buffer_size = 16M 
max_allowed_packet = 1M
table_open_cache = 64
sort_buffer_size = 512K
net_buffer_length = 8K
read_buffer_size = 256K
read_rnd_buffer_size = 512K
myisam_sort_buffer_size = 8M
{% endcodeblock %}

默认mysqld只启动一个实例，既然我们的目的是启动多个mysqld实例，需要使用mysqld_multi。它是一个perl脚本，在使用之前，需要给my.cnf加一些料。

{% codeblock my\.cnf配置[增加mysqld_mutli] %}

# mysqld_multi会读取这个配置短的内容
[mysqld_multi] 
mysqld = /usr/local/mysql/bin/mysqld
mysqladmin = /usr/local/mysql/bin/mysqladmin

# 第一个mysqld实例
[mysqld1]
port = 3306
socket = /var/run/mysql/mysql1.sock
datadir = /data/mysql1
general-log-file = /var/log/mysql/error.log
skip-external-locking
key_buffer_size = 16M 
max_allowed_packet = 1M
table_open_cache = 64
sort_buffer_size = 512K
net_buffer_length = 8K
read_buffer_size = 256K
read_rnd_buffer_size = 512K
myisam_sort_buffer_size = 8M
log-bin=mysql-bin
binlog_format=mixed
server-id       = 1 
user = mysql

# 第二个mysqld实例
[mysqld2]
port = 3307
socket = /var/run/mysql/mysql2.sock
datadir = /data/mysql2
general-log-file = /var/log/mysql/error.log
skip-external-locking
key_buffer_size = 16M 
max_allowed_packet = 1M
table_open_cache = 64
sort_buffer_size = 512K
net_buffer_length = 8K
read_buffer_size = 256K
read_rnd_buffer_size = 512K
myisam_sort_buffer_size = 8M
log-bin=mysql-bin
binlog_format=mixed
server-id       = 1 
user = mysql

{% endcodeblock %}

然后依配置创建mysql运行时文件夹并用mysql_install_db脚本初始化系统库
{% codeblock lang:bash %}
mkdir -p /data/mysql1 /data/mysql2 /var/log/mysql /var/run/mysql
chgrp mysql /data/mysql* /var/log/mysql /var/run/mysql
chown mysql /data/mysql* /var/log/mysql /var/run/mysql
mysql_install_db --datadir=/data/mysql1 --user=mysql
mysql_install_db --datadir=/data/mysql2 --user=mysql
mysqld_multi start 1-2
{% endcodeblock %}

最后一条命令其实已经将我们配置好的mysqld1和mysqld2启动了，这时候在进程表中应该能看到两个mysqld进程，试着用-P参数指定端口能分别访问在/data/mysql1和/data/mysql2下面的两个库，两者互不影响，正好能满足测试的要求。当然需要配置更多的实例也是可以的。

<h2 id="mysql_extra">额外收获</h2>

这次配置过程中还遇到一些额外的问题，记下来备忘。

* 假如使用的是ubuntu(我目前的版本还是11.10)，默认会安装apparmor，这个软件是一个诡异的存在，它就像一个暗恋者，一直默默限制软件的访问权限，然后又不被系统待见，以至于我根本不知道它的存在。其实它是一个白名单，在/etc/apparmor.d/中指定了/usr/sbin/mysqld对各文件的访问权限，当我想将mysql的数据文件夹迁移到别的位置时，一直报这个错误

`Can't create test file /data/mysql1/littleboy.lower-test`

而令人费解的就是mysql对这些文件夹是有读写权限的，其实只需要编辑/etc/apparmor.d/usr.sbin.mysqld文件，依样画葫芦地为文件夹加上rw权限就可以了

* 给mysql设置远程访问权限，只需要下面这条sql

{% codeblock lang:sql %}
INSERT mysql.user ( `Host`, `User`, `Password` ) VALUES ( '%', 'root', PASSWORD('123456') );
{% endcodeblock %}

其中最关键的就是那个'%'，表示通过任意host均可以访问到本机的mysql

* 给mysql设置密码。

`mysqladmin -u root -h 127.0.0.1 password 123456`

上面那种是在不登录mysql的情况下修改密码，还有两种可以通过改表的方式。见[MySQL设置密码的三种方法](#mysql_refer)

修改过密码以后，mysqld_multi可能就不能通过默认配置来结束mysqld进程了，这时候需要在配置里加上用户名和密码

{% codeblock %}
[mysqld_multi]
mysqld = /usr/local/mysql/bin/mysqld
mysqladmin = /usr/local/mysql/bin/mysqladmin
user = root
password = 123456
{% endcodeblock %}

当然这样可能会有一些安全隐患，因为my.cnf是可见的。假如是多人使用，可以将password这行去掉，每次操作mysqld_multi的时候，在后面加上`--password=123456`参数就行了

<h2 id="mysql_refer">参考资料</h2>
* [解决apparmor引起的报错1](http://www.neocanable.com/error-for-mysql-multi-and-mysql-install-db/)
* [解决apparmor引起的报错2](http://ubuntuforums.org/showthread.php?t=1861136)
* [MySQL设置密码的三种方法](http://blog.csdn.net/magicbreaker/article/details/2392764)

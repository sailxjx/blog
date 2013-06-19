---
layout: post
title: "start-stop-daemon"
date: 2013-06-19 13:44
comments: true
categories: [linux, shell]
---

很多软件不提供init脚本，或者提供的脚本不合胃口，难免要自己动手丰衣足食。下面就推荐一个用来启动守护进程的神器。

`start-stop-daemon`是[OpenRC](http://www.gentoo.org/proj/en/base/openrc/)计划的一部分，这个程序最先出现在Debian系的Linux发行版中，这里有个比较古老的[手册](http://man.he.net/man8/start-stop-daemon)页面，更详细更直观的办法当然是通过`man start-stop-daemon`来查看手册了。我使用的是"start-stop-daemon (OpenRC) 0.10 (Funtoo Linux)"版本，大部分功能是差不多的。

`start-stop-daemon`最基本的两个功能就是`--start`和`--stop`，简写为`-S`和`-K`，然后再加上一个`-s|--signal`来给进程发送信号，功德圆满。

至于其中比较常用的一些参数，我列出来参考一下，以免忘了：

* `-x, --exec daemon`，daemon就是真正要执行的进程脚本，比方说启动nginx，那么就是`start-stop-daemon -x nginx`。
* `-p, --pidfile pidfile`，指定pid文件，至于pid文件的用途就多了，stop,status都少不了它。
* `-n, --name`，如果没有指定pid文件，那么就要通过指定name来停止进程了。
* `-u, --user user[:group]`，指定脚本用哪个用户或用户组执行，init脚本是必须使用`root`权限来执行的，但是它fork出来的子进程我们一般会选择一个权限较低的用户。
* `-b, --background`，强制脚本在后台执行。
* `-m, --make-pidfile`，这个一般和`-b`配合，用于生成pid文件
* `-d, --chdir path`，切换进程的主目录，这个在构建守护进程的时候是很常用的。
* `-r, --chroot path`，在某些安全性要求较高的情况下，我们就需要用到`chroot`将进程工作环境与物理环境完全隔离开来。
* `-1, --stdout logfile`，将标准输出记录到log文件，与之相对应的就是`-2, --stderr`标准错误流。
* `-w, --wait milliseconds`，进程启动后，有这个参数会等待几毫秒来检测进程是否仍然存活。

参数说完，下面就是一些需要注意的地方了。

## `-b`与守护进程

`-b`是一个很常用的参数，我们使用`start-stop-daemon`的目的就是为了实现守护进程。但是有些程序自身也实现了守护进程的功能，比方说mongodb中有一个fork选项就是将自己在后台执行，这个时候假如搭配的`-b`参数，是得不到正确的pid的，因为`start-stop-daemon`只能得到最初启动的父进程pid，而父进程在fork完之后就自动退出了，那么`start-stop-daemon`就永远找不到正确的pid来结束进程了。所以使用`-b`的时候，一定要保证程序是在前台运行的。

## 其他参数

`-x daemon`后面跟的执行脚本必须只能是一个文件名，有些程序运行时还需要指定一些参数，比如`nginx -c file`来指定nginx的配置文件，使用`start-stop-daemon -x "nginx -c file"`是会报错的，这些程序内的参数以另一种方式加载，`start-stop-daemon -x daemon -- $ARGV`，这里的双横线`--`后面跟的所有参数就会被带到程序中了，比如`start-stop-daemon -x nginx -c /etc/nginx.conf`。

下面是mongodb的一个init脚本，用`start-stop-daemon`是非常简单的。（貌似源代码中没有提供init脚本，只能自己动手了）。

{% codeblock lang:bash %}
#!/sbin/runscript
# Distributed under the terms of the GNU General Public License v2

MONGO_HOME=/usr/local/mongo
MONGO_USER=mongo
MONGO_PID_FILE=/var/run/mongo/mongo.pid

depend() {
    need net 
}

start() {
    ebegin "Starting Mongodb"
    start-stop-daemon --start       \   
        --chdir  "${MONGO_HOME}"    \   
        --user "${MONGO_USER}"      \   
        -m -p "${MONGO_PID_FILE}"   \   
        -b --exec "${MONGO_HOME}/bin/mongod" -- --config=/etc/mongodb.conf
    eend $?
}

stop() {
    ebegin "Stopping Mongodb"
    start-stop-daemon --stop        \   
        --chdir "${MONGO_HOME}"     \   
        --user "${MONGO_USER}"      \   
        -p "${MONGO_PID_FILE}"      \   
    eend $?
}
{% endcodeblock %}
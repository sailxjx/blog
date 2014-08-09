---
layout: post
title: "nginx+php-fpm 配置记录"
date: 2012-12-24 22:03
comments: true
categories: [nginx, php]
---

### 安装nginx
安装过程没什么好说的，不过tarball中没有包含init脚本，官网wiki中提供了[一个ubuntu的版本](http://wiki.nginx.org/Nginx-init-ubuntu)，其实在所有linux发行版中都可用，下载下来放到/etc/init.d/nginx，大功告成。

假如在启动过程中遇到`undefined function: log_daemon_msg`等等报错，那是缺少一些公用方法，下载或安装init-functions然后在头部引入即可，google到一个可用的[地址](http://www.linuxfromscratch.org/lfs/view/7.0/scripts/apds02.html)，此外，假如使用的是ubuntu，在/lib/lsb/中有个文件。

### 安装php-fpm
fpm已经包含在php的远吗中，编译php的时候加上`--enable-fpm`即可，fpm的init脚本包含在`sapi/fpm/init.d.php-fpm`，稍加修改即可使用，非常贴心滴。默认配置文件`sapi/fpm/php-fm.conf`，我把它复制在/etc目录中以供调用。

### 配置php-fpm
fpm的配置文件已很多个pool块分割，global是全局配置，www是默认的pool，这里不做修改。
{% codeblock php-fpm.conf lang:ini %}
[global]
user = tristan
group = tristan
pid = /var/run/php-fpm.pid
error_log = /var/log/php/fpm.err.log
; 下面三个值表示当一分钟内假如有10个子进程收到SIGSEGV或SIGBUS信号而退出的话，php-fpm会自动重启，应该是一种自我保护的机制
emergency_restart_threshold 10
emergency_restart_interval 1m
process_control_timeout 10s
; 超过十秒的慢请求会被记录到fpm.slow.$pool.log中并在errlog中产生一条notice记录
request_slowlog_timeout = 10s
slowlog = /var/log/php/fpm.slow.$pool.log
; 允许任意客户端链接
listen.allowed_clients = any
; 可以包含一些分散的config文件
include=/etc/php-fpm.d/*.conf
; 下面是www pool的配置
[www]
; 设置子进程相关
pm = dynamic
pm.max_children = 5
pm.start_servers = 3
pm.min_spare_servers = 2
pm.max_spare_servers = 4
pm.max_requests = 200
; 下面还可以用env设置一些环境变量
env[PATH] = /usr/local/bin:/usr/bin:/bin
env[TMP] = /tmp
{% endcodeblock %}
配置完成后，`/etc/init.d/php-fpm start`即可启动php-fpm，通过`netstat -anp`可以看到9000端口被php-fpm占用了。

### 配置nginx
下面到了最艰苦卓绝的工作了，配置nginx。
{% codeblock nginx %}
user tristan;
worker_processes 2;
error_log  /var/log/nginx/error.log;
pid        /var/run/nginx.pid;
events {
    worker_connections  1024;
}
http {
    include       mime.types;
    default_type  application/octet-stream;
    sendfile        on; 
    keepalive_timeout  65; 
    gzip on; 
    include /usr/local/nginx/conf/sites-enabled/*;
}
{% endcodeblock %}
主配置文件中基本没什么可写的，重点在对每个站点的配置上。

对静态站点的配置是最简单的，比如下面的配置就部署了一个bootstrap的demo站点
{% codeblock boot %}
server {
    listen *:80;
    server_name boot.local.com;
    access_log  /var/log/nginx/boot.log;
    location / {
        root /home/tristan/coding/github/bootstrap/docs; #根目录
        index index.html; #添加默认索引文件
    }   
}
{% endcodeblock %}

动态站点稍微麻烦一点，通过fastcgi模式，使用php-fpm配置一个动态站点。
{% codeblock fun %}
server {
    listen *:80;
    server_name fun.local.com;
    access_log /var/log/nginx/fun.log;
    location / { 
        index index.php;
        rewrite ^(.*)$ /index.php$1 last; #大部分站点都做成了单入口，将所有url rewrite到index文件
    }   
    location ~ ^/index.php {
        root /home/tristan/coding/webdata/fun;
        fastcgi_pass 127.0.0.1:9000;
        include fastcgi_params;
        if ($fastcgi_script_name ~ "^(.+?\.php)(/.+)$") {
            set $real_script_name $1; 
            set $path_info $2; 
        }   
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $path_info; 
    }
}
{% endcodeblock %}
值得注意的是，有些框架使用pathinfo作为路由依据，默认情况下nginx并不会将pathinfo传递给php-fpm，所以上面需要通过正则匹配出准确的pathinfo，通过fastcgi_param传给fpm

下面是一个使用minify的静态资源站点，其中既包含纯静态文件(css|js)，也包含由php压缩成的伪静态文件
{% codeblock static %}
server {
    listen *:80;
    server_name static.local.com;
    access_log /var/log/nginx/static.log;
    root /home/tristan/coding/webdata/static;
    location / { 
        autoindex on; 
        index index.php;
        rewrite ^/static/(.*)\.(js|css)$ /static/index.php?g=$1 last;
    }   
    location ~ index.php {
        fastcgi_pass 127.0.0.1:9000;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }   
    location ~ ^(?!\/static)(.*)\.(jpg|jpeg|gif|png|css|js|ico|xml)$ {
        access_log off; #关闭log
        log_not_found off;
        expires 30d; #纯静态文件设置缓存时间
    }   
}
{% endcodeblock %}

##参考资料
* [Nginx and PHP-FPM Configuration and Optimizing Tips and Tricks](http://www.if-not-true-then-false.com/2011/nginx-and-php-fpm-configuration-and-optimizing-tips-and-tricks/)
* [stackoverflow: Empty value to PATH_INFO in nginx returns junk value](http://stackoverflow.com/questions/8265941/empty-value-to-path-info-in-nginx-returns-junk-value)
* [HttpFastcgiModule](http://wiki.nginx.org/HttpFastcgiModule)
* [HttpRewriteModule](http://wiki.nginx.org/HttpRewriteModule)
---
layout: post
title: "gitlabhq部署小记"
date: 2012-11-14 20:12
comments: true
categories: [git, gitlab, software, dev]
---

冬天来了，不知github是不是也去冬眠了，速度慢的像在爬，没办法，屌丝买不起vps，只能自己内网部一套开源的。

gitlabhq是github的一个开源版本，虽然不是官方的，但是已经做的有模有样，总之能想到的功能都已具备，放在国内随便改改UI就能上线建站的那种。安装文档那是写的相当滴详细，体现了码农罕有的耐性，查看文档请移步[gitlab文档](https://github.com/gitlabhq/gitlabhq/blob/master/doc/install/installation.md)

以前部署中在ssh上碰到一些问题，这次在[@mk-qi](https://github.com/mk-qi)童鞋的点拨下，进展是相当滴顺利，下面记录一些部署过程中的问题和解决方法。

自动部署脚本如下，基本由文档转成，可以省掉很多事情(但是遇到问题要学会google哦)，假如使用的是ubuntu，官方以前也提供了一个一键安装脚本，后来不知怎么又去掉了，估计是计划赶不上变化吧

{% codeblock gitlabhq自动化部署脚本 lang:bash %}
#!/bin/bash
# add user git
sudo adduser --system --shell /bin/bash --gecos 'git version control' --group --disabled-password --home /home/git git
# add user gitlab
sudo adduser --disabled-login --gecos 'gitlab system' gitlab
# move user gitlab to group git
sudo usermod -a -G git gitlab
sudo usermod -a -G gitlab git
# generate key
sudo -H -u gitlab ssh-keygen -q -N '' -t rsa -f /home/gitlab/.ssh/id_rsa
# clone gitlab's fork to the gitolite source code
cd /home/git
sudo -H -u git git clone -b gl-v304 https://github.com/gitlabhq/gitolite.git /home/git/gitolite
# setup
cd /home/git
sudo -u git -H mkdir bin
sudo -u git sh -c 'echo -e "PATH=\$PATH:/home/git/bin\nexport PATH" >> /home/git/.profile'
sudo -u git sh -c 'gitolite/install -ln /home/git/bin'
sudo cp /home/gitlab/.ssh/id_rsa.pub /home/git/gitlab.pub
sudo chmod 0444 /home/git/gitlab.pub
sudo -u git -H sh -c "PATH=/home/git/bin:$PATH; gitolite setup -pk /home/git/gitlab.pub"
# permissions
sudo chmod -R g+rwX /home/git/repositories/
sudo chown -R git:git /home/git/repositories/
sudo -u gitlab -H git clone git@localhost:gitolite-admin.git /tmp/gitolite-admin

if [[ $? != 0 ]];then
    echo "error: gitolite is not installed correct, or the ssh key is not right"
    exit 1
fi

sudo rm -rf /tmp/gitolite-admin
# clone gitlab source and install prerequisites
sudo gem install charlock_holmes
sudo pip install pygments
cd /home/gitlab
sudo -H -u gitlab git clone git://github.com/51fanli/gitlabhq.git gitlab
cd gitlab
sudo -u gitlab cp config/gitlab.yml.example config/gitlab.yml
# mysql databases init
echo "connect to mysql"
mysql -h127.0.0.1 -uroot -p
# CREATE DATABASE IF NOT EXISTS `gitlabhq_production` DEFAULT CHARACTER SET `utf8` COLLATE `utf8_unicode_ci`;
# CREATE USER 'gitlab'@'localhost' IDENTIFIED BY '123456';
# GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER ON `gitlabhq_production`.* TO 'gitlab'@'localhost';
sudo -u gitlab cp config/database.yml.example config/database.yml
sudo -u gitlab -H bundle install --without development test sqlite postgres --deployment
sudo -u gitlab -H git config --global user.email "gitlab@localhost"
sudo -u gitlab -H git config --global user.name "Gitlab"
sudo -u gitlab cp config/resque.yml.example config/resque.yml
sudo -u gitlab cp config/unicorn.rb.example config/unicorn.rb
# init tables
sudo -u gitlab bundle exec rake gitlab:app:setup RAILS_ENV=production
sudo cp ./lib/hooks/post-receive /home/git/.gitolite/hooks/common/post-receive
sudo chown git:git /home/git/.gitolite/hooks/common/post-receive
# check status
sudo -u gitlab bundle exec rake gitlab:app:status RAILS_ENV=production
sudo wget https://raw.github.com/gitlabhq/gitlab-recipes/master/init.d/gitlab -P /etc/init.d/
sudo chmod +x /etc/init.d/gitlab
sudo update-rc.d gitlab defaults 21
{% endcodeblock %}

gitlabhq3.0后改用unicorn(紧跟github步伐)作为默认的启动server,要将它与nginx或apache一起使用请参考[archwiki的gitlab手册](https://wiki.archlinux.org/index.php/Gitlab#Web_server_configuration),下面是apache中的vhost配置(需要预先编译proxy模块)

{% codeblock apache vhost配置 lang:bash %}
<VirtualHost *:80>
  ServerName gitlab.myserver.com
  ServerAlias www.gitlab.myserver.com
  DocumentRoot /home/gitlab/gitlab/public
  ErrorLog /var/log/httpd/gitlab_error_log
  CustomLog /var/log/httpd/gitlab_access_log combined

  <Proxy balancer://unicornservers>
      BalancerMember http://127.0.0.1:8080
  </Proxy>

  <Directory /home/gitlab/gitlab/public>
    AllowOverride All
    Options -MultiViews
  </Directory>

  RewriteEngine on
  RewriteCond %{DOCUMENT_ROOT}/%{REQUEST_FILENAME} !-f
  RewriteRule ^/(.*)$ balancer://unicornservers%{REQUEST_URI} [P,QSA,L]

  ProxyPass /uploads !
  ProxyPass / balancer://unicornservers/
  ProxyPassReverse / balancer://unicornservers/
  ProxyPreserveHost on

   <Proxy *>
      Order deny,allow
      Allow from all
   </Proxy>
</VirtualHost>
{% endcodeblock %}

unicorn的配置文件在config/unicorn.rb，修改其中的 `listen="127.0.0.1:8080"`，然后重启apache，通过 `service gitlab start` 重启unicorn，访问一下gitlab.myserver.com吧，看到登录页面就说明大功告成啦。

## Q&A
### Q: 在装完gitolite后尝试`git clone git@localhost:gitolite-admin.git /tmp/gitolite-admin`遇到'remote hang-up unexpected'(貌似是这么写，意会。。。)
A: 我在centos6.2上遇到过这个问题，其他发行版上不知道有没有这个问题，修改

`sudo chmod 400 /home/git/.ssh/authorized_keys`

可以修复这个问题。貌似是centos的安全策略造成ssh私钥不生效

## 参考资料
* [gitlab安装手册官方版](https://github.com/gitlabhq/gitlabhq/blob/master/doc/install/installation.md)
* [gitlab手册archwiki版](https://wiki.archlinux.org/index.php/Gitlab)

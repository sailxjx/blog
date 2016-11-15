---
layout: post
title: "使用 GitLab + Docker 搭建持续交付系统"
date: 2016-10-28 16:01
comments: true
categories: [tech, docker]
---

# 环境要求

* GitLab CE > 8.0
* Docker > 1.0

# 步骤一，开启 Runner

Runner 是 GitLab CI 的任务执行单位，GitLab 以服务发现的方式来将 Runner 分布在不同的主机上。根据[官方文档的说明](https://docs.gitlab.com/runner/install/)，你可以选择任何系统的主机来部署 Runner。这里我为了图方便就直接采用 Debian 源来安装了。

由于我们需要让 Runner 在 Docker 中运行，所有首先安装 Docker

`curl -sSL https://get.docker.com/ | sh`

然后安装 Runner

```
curl -L https://packages.gitlab.com/install/repositories/runner/gitlab-ci-multi-runner/script.deb.sh | sudo bash
sudo apt-get install gitlab-ci-multi-runner
```

然后我们需要启动 Runner 并提交它的服务信息给自己的 GitLab 站点

```
sudo gitlab-ci-multi-runner register

Please enter the gitlab-ci coordinator URL (e.g. https://gitlab.com ) （如果是私有部署的 GitLab，填写自己的域名地址）
https://gitlab.com
Please enter the gitlab-ci token for this runner （在 http://{your.gitlab.domain}/admin/runners 下可以找到共享 token，这样注册的 Runner 可以给所有项目共享，你也可以在项目配置中找个每个项目专属的 token，这样注册的 Runner 就只能由这个项目独享了）
xxx
Please enter the gitlab-ci description for this runner （描述）
my-runner
INFO[0034] fcf5c619 Registering runner... succeeded
Please enter the executor: shell, docker, docker-ssh, ssh? （我们用 docker）
docker
Please enter the Docker image (eg. ruby:2.1): （Docker 镜像，也是该 Runner 的运行环境，如果你有一些特殊的需求，例如添加 ssh 访问私钥，配置预安装软件等，可以自己做一个镜像提交到 docker hub 上面）
ruby:2.1
INFO[0037] Runner registered successfully. Feel free to start it, but if it's
running already the config should be automatically reloaded!
```

# 步骤二，分配 Runner

然后下一步我们就可以在 `http://{your.gitlab.domain}/admin/runners` 下找到刚刚注册的 Runner 了，这时候 Runner 处于共享状态，我们可以通过编辑来指派 Runner 的特定项目，或者给 Runner 加标签来方便以后更灵活的分配这些 Runner。

然后再看一下项目的配置，将 Build 选项开启，这样每次提交时 GitLab 机会自动调用 Runner 来执行任务了。

# 步骤三，编写 .gitlab-ci.yml

[.gitlab-ci.yml](https://docs.gitlab.com/ce/ci/yaml/README.html) 是 GitLab 提供的一种配置文件，对于熟悉 travis 这类 SaaS 型持续集成服务的开发者来说这种配置是相当亲切的了，而且文件跟着代码库，编辑起来非常方便。

下面就这个简单的配置文件说明一下各部分的作用

```
before_script:  # 在所有任务之前执行下面的脚本
  - node -v && npm -v

services:  # 使用 docker 时，runner 会自动下载这些镜像并以 link 的方式将它们连接进来
  - elasticsearch:1.6.2
  - mongo:3.4
  - redis

stages:  # 指定任务阶段，在一个阶段中可以配置多个任务，各阶段按顺序依次执行
  - test
  - deploy

cache:  # 在各任务间缓存文件
  paths:
  - node_modules/

# 一下非关键词开头的配置就代表各个任务了
test:
  stage: test  # 任务可以通过阶段来决定执行顺序
  script:      # 任务的执行脚本
    - npm prune && npm install && npm run test-gitlab
  tags:        # 通过 tag 可以指定由相应的 runner 来执行这个任务
    - docker

# 由于是为了搭建持续交付系统，所以我配置了两个任务阶段，测试阶段结束后进入到发布阶段。
# 因各人发布环境的不同可以选择不同的命令发布代码，填写在 script 中即可
deploy:
  stage: deploy
  script:  
    - env && pwd && ls -a
  tags:
    - docker
```

这样，我们就实现了在每次代码提交时自动测试，通过后将代码发布到服务器环境的目的。

# 遇到的问题

1. 使用 Docker runner 时，`git clone` 或 `npm install` 有时候会遇到「检测到未知的 host key」提示，而中断 build 进程

这个问题有两种方法可以解决，一种是用 `ssh -o "StrictHostKeyChecking no" user@host` 关闭指定用户和域名的检测。另一种是 `ssh-keyscan host >> /root/.ssh/known_hosts` 将检测结果写入。上面在 Dockerfile 中用了第二种方式：

`RUN ssh-keyscan github.com >> /root/.ssh/known_hosts`

2. Container 遇到 `Couldn't resolve host` 问题

这个问题我没有找到真正的解决办法，因为它是个偶发的问题，可能你什么都没干，过段时间再试试它就自己恢复了，可能与 Host 主机的 DNS 设置有关。我的解决方案之一是删除所有由 GitLab 创建的运行状态或等待状态的 Container，再重新开始任务。这里提供一个便捷的命令来删除所有由 GitLab 创建的 Container

`docker ps -a | grep gitlab | awk '{print $1}' | xargs docker rm`
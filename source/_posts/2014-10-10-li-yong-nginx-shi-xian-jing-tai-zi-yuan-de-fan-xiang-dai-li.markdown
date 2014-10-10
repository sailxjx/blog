---
layout: post
title: "利用 nginx 实现静态资源的反向代理"
date: 2014-10-10 16:52
comments: true
categories: [nginx]
---

github 中很多项目都有一个 readme 文件，很多人喜欢在文件中添加自己的创作或封面图片，比如 [substack](https://github.com/substack) 为他的每个项目绘制了一个 logo。这些图片在 github 中能直接在页面中显示出来，不过 url 被替换成了 github 自己的。比如在 [browserify](https://github.com/substack/node-browserify/blob/master/readme.markdown) 项目中，logo 的链接变成了

> https://camo.githubusercontent.com/e19e230a9371a44a2eeb484b83ff4fcf8c824cf7/687474703a2f2f737562737461636b2e6e65742f696d616765732f62726f777365726966795f6c6f676f2e706e67

而我们通过查看 [raw](https://raw.githubusercontent.com/substack/node-browserify/master/readme.markdown) 能发现原 url 是

> http://substack.net/images/browserify_logo.png

这样做的一个好处是防止因为在 https 网站中出现 http 链接，否则在客户端会得到一个风险警告。github 在细节上真是考虑的十分周到。

既然有需求，我们就来实现它。通常的做法是写一个应用去抓取远程的静态资源，然后反馈给前端，这就是一个简单地反向代理了。但是这样做比较繁琐，效率也未见得高，其实我们可以直接通过 nginx 来代理这些静态文件。

nginx 的 `proxy_pass` 支持填写任意地址，并且支持 dns 解析。所以我的思路是，将原 url 加密转成网站自身的 url。比如上面的

> http://substack.net/images/browserify_logo.png

可以加密成

> 764feebffb1d3f877e9e0d0fadcf29b85e8fe84ae4ce52f7dc4ca4b3e05bf1718177870a996fe5804a232fcae5b893ea (加密和序列化算法网上有很多，在此就不赘述了)

然后放在我们自己的域名下：

> https://ssl.youdomain.com/camo/764feebffb1d3f877e9e0d0fadcf29b85e8fe84ae4ce52f7dc4ca4b3e05bf1718177870a996fe5804a232fcae5b893ea

解密的步骤用 nginx 会比较难实现，所以当用户通过上述链接请求时，先讲请求传递给解密程序，这里有一个 coffeescript 版本的例子：

```coffeescript
express = require 'express'
app = express()
app.get '/camo/:eurl', (req, res) ->
  {eurl} = req.params
  {camoSecret} = config  # 这里使用自己的密钥
  rawUrl = util.decrypt eurl, camoSecret
  return res.status(403).end('INVALID URL') unless rawUrl
  res.set 'X-Origin-Url', rawUrl
  res.set 'X-Accel-Redirect', '/remote'
  res.end()
app.listen 3000
```

然后写入 `X-Accel-Redirect` 响应头做内部跳转，下面的步骤就由 nginx 完成了。

下面是一个完整的 nginx 配置文件例子：

```nginx
server {
    listen 80;
    server_name ssl.youdomain.com;
    location /camo/ {
        proxy_pass http://localhost:3000;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header Host $http_host;
        proxy_set_header X-NginX-Proxy true;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_redirect off;
        break;
    }
    location /remote {
        internal;
        resolver 192.168.0.21;  # 必须加上 dns 服务器地址，否则 nginx 无法解析域名
        set $origin_url $upstream_http_x_origin_url;
        proxy_pass $origin_url;
        add_header Host "file.local.com";
        break;
    }
}
```

nginx 的 upstream 模块会把所有的响应头加上 `$upstream_http_` 前缀当成一个变量保存，所以在上面的例子中我们将原 url 放在 `X-Origin-Url` 响应头中，在 nginx 就变成了 `$upstream_http_x_origin_url` 变量，但是在 proxy_pass 中不能直接引用，非要通过 set 来设置才能引用，这个我不是很理解，希望有高手能解答。

这样下来，每次当用户请求

> https://ssl.youdomain.com/camo/764feebffb1d3f877e9e0d0fadcf29b85e8fe84ae4ce52f7dc4ca4b3e05bf1718177870a996fe5804a232fcae5b893ea

时，nginx 就会去抓取

> http://substack.net/images/browserify_logo.png

的内容返回给用户。我们还可以在 nginx 之前加上 varnish，用以缓存静态文件的内容。这样就跟 githubusercontent 的做法更加一致了。

---
layout: post
title: "multipart 到底是个啥玩意儿"
date: 2014-12-21 11:16
comments: true
categories: [http, restful, multipart]
---

在 POST 请求中，常见的几种请求头有 'application/x-www-form-urlencoded'， 'application/json'，这些都很容易理解，唯独 'multipart/form-data' 这种请求挺让我费解，下面就来详细说明一下，以作笔记备查。

在 GET 请求中，参数一般会以 '&' 为分割符号，比如 'http://api.example.com?name=tom&friend=jerry'，在 'application/x-www-form-urlencoded' 类的 POST 请求中，参数形式与此类似，只不过参数被写在了 body 中，以突破 url 中 2k 字节的限制。

而当我们想上传文件或其他二进制数据时，根据 form 标准，非字符串会被替换成 '%HH'，其中的 'HH' 是两个十六进制数来表示当前这位的二进制数据。在上传大文件的时候，这种做法就显得非常浪费了。于是，我们经常会把 'multipart/form-data' 来用在文件上传中。

我们来看一个完整的 'multipart/form-data' 请求：

```
Content-Type: multipart/form-data; boundary=AaB03x

--AaB03x
Content-Disposition: form-data; name="submit-name"

Larry
--AaB03x
Content-Disposition: form-data; name="files"; filename="file1.txt"
Content-Type: text/plain

... contents of file1.txt ...
--AaB03x--
```

上面 'boundary' 的值，就与常见的 'application/x-www-form-urlencoded' 中 '&' 的作用差不多了，在接收请求的服务器中，会将 body 以 '--AaB03x' 分割出一个个 part，就能正常解析出参数类型，名称，文件名等等内容了。

# 参考资料

- [The Multipart Content-Type](http://www.w3.org/Protocols/rfc1341/7_2_Multipart.html)
- [Forms](http://www.w3.org/TR/html401/interact/forms.html)
- [application/x-www-form-urlencoded or multipart/form-data?](http://stackoverflow.com/questions/4007969/application-x-www-form-urlencoded-or-multipart-form-data)


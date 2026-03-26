## 导出方法

使用docker save 命令导出容器

```
docker save [OPTIONS] IMAGE [IMAGE...]
docker save -o image_name.tar tagname:latest
```

注意：导出的文件名称一定要与镜像的名字相同

![](2021-02-08-14-27-29.png)

根据上图，导出命令为：

```
docker save -o hangge_server.tar hangge_server:latest
```

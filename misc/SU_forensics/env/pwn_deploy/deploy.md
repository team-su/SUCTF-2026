1. 
```bash
docker build -t "funheap" . (注意最后的点)
```

2. 
```bash
docker run -d -p "0.0.0.0:pub_port:9999" -h "funheap" --name="funheap" funheap 
```

`pub_port` 替换成你想要开放给选手的端口

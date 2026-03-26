## 导出记录

- 题目：`web/SU_cmsAgain`
- 构建方式：`docker-compose`
- 导出时间：`2026-03-26 17:13:26 +0800`
- 运行检查：`PASS`

### 镜像

- `website:2.0`

### 导出命令

```bash
docker save -o env.tar website:2.0
```

### 备注

- 已在当前机器上完成 `docker compose up -d --build` 验证。
- 为避免本机已占用的 `9083` 端口冲突，compose 的宿主机映射端口调整为 `19083:80`；容器内部服务入口未变。

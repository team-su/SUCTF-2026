# SU_easyLLM Env

该题是一个需要联网的单服务 FastAPI 环境，容器启动时会立即调用一次上游 LLM 接口生成 AES 密钥来源。

## 目录

- `Dockerfile`: 构建镜像
- `docker/docker-compose.yml`: 本地编排
- `service/docker-entrypoint.sh`: 启动脚本
- `src/easyLLM.py`: 题目服务源码
- `src/requirements.txt`: Python 依赖

## 必要环境变量

- `ZHIPU_API_KEY`: 必填。未设置时容器会在启动阶段直接失败。
- `ZHIPU_BASE_URL`: 选填。默认 `https://api.z.ai/api/paas/v4/chat/completions`
- `ZHIPU_MODEL`: 选填。默认 `GLM-4-Flash`
- `FLAG`: 选填。默认 `SUCTF{LLM_w1ll_ch4nge_ev3rything}`
- `PORT`: 选填。默认 `8080`

## 启动说明

### docker compose

```bash
cd env/docker
ZHIPU_API_KEY=your_api_key docker compose up --build
```

### docker build / run

```bash
cd env
docker build -t su_easyllm .
docker run --rm -p 8080:8080 \
  -e ZHIPU_API_KEY=your_api_key \
  -e FLAG='SUCTF{LLM_w1ll_ch4nge_ev3rything}' \
  su_easyllm
```

## 设计注意事项

- 不能使用 `python easyLLM.py` 直接启动。源码没有 `__main__` 入口，这样只会执行一次加密逻辑然后退出。
- 不能使用 `uvicorn --reload` 或多 worker。源码在模块 import 时就会请求 LLM、生成随机 IV 并计算密文，多次 import 会导致题面数据变化。
- 对外只需要暴露 HTTP 服务端口 `8080`。
- 容器需要具备外网访问能力，否则无法访问 `z.ai` 接口。

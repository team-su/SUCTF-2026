# SU-Note Writeup

## 题目概述

- flag 在 bot 的笔记里。
- 题目容器内地址默认是 `http://127.0.0.1:80`。
- 利用点不在传统 XSS 执行本身，而在搜索页命中/未命中时对 BFCache 行为的差异。

## 核心原理

### 1. `no-store` 不再绝对禁止 BFCache

Chrome 的较新策略下，`Cache-Control: no-store` 页面在满足条件时仍可能进入 BFCache，只是限制更多、保留时间更短。

### 2. `unload` 会阻止页面进入 BFCache

本题的搜索逻辑里：

- 搜索命中时，不注册 `unload`
- 搜索未命中时，注册 `unload`

因此，同一个 `search.php` 页面在“命中”和“未命中”两种情况下，是否能进入 BFCache 会不同。

### 3. BFCache 槽位有限，可作为侧信道

单个标签页可保留的 BFCache 页面数量有限。构造一条固定页面链后，搜索页是否额外占用一个 BFCache 槽位，会改变后续页面在回退时是否从 BFCache 恢复，从而形成可观测信号。

## 利用链

exp 中构造的页面跳转链为：

`/a -> /b -> /c -> /d -> /e -> /f -> /search.php?q=... -> /f -> /g -> history.back() -> ... -> /a`

目标是让“搜索是否命中”最终反映到 `a` 页的 `pageshow.persisted` 结果上。

当前 exp 的信号定义：

- `a_no_from_bfcache` 表示 `hit`
- `a_from_bfcache` 表示 `miss`

## 使用说明

```bash
cd /root/A5rZ/Table/b/SU-Note/exp
python3 exp.py
```

默认探测前缀是 `SUCTF{`，默认目标地址是 `http://127.0.0.1:80`。如果目标地址不同，可通过 exp 提供的参数或接口调整。

## 备注

- `/root/A5rZ/Table/b/SU-Note/env/web_deploy/.config` 可调 bot 最大并发。
- 本整理目录中的 `exp/`、`env/web_deploy/`、`sourcecode/sourcecode.zip` 均来自原题复制整理。

## 标题
SU-Note-rev

# 题目描述

修复了一个非预期，但是还是没修住,出了个烂题，抱歉了。

- flag 在 bot 的笔记里。
- 题目容器内地址是 `127.0.0.1:80`。
- flag 格式示例：`flag{0100011110000110}`。
- 为了方便 leak，本题 flag 主体只包含 `0/1`。

# 核心原理

## 1) no-store 与 BFCache

参考：
- https://developer.chrome.com/docs/web-platform/bfcache-ccns?hl=zh-cn#should_developers_still_aim_to_reduce_usage_of_cache-control_no-store

Chrome 在新策略下，`Cache-Control: no-store` 页面在满足条件时仍可能进入 BFCache（但保留时长更短，且受更多保护条件限制）。

## 2) unload 会阻止进入 BFCache

参考：
- https://developer.chrome.com/docs/web-platform/deprecating-unload?hl=zh-cn

本题关键差异：
- 搜索 `q` 命中时：不注册 `unload`。
- 搜索 `q` 未命中时：注册 `unload`。
- 结果：命中/未命中会影响搜索页是否能进入 BFCache。

## 3) BFCache 容量与淘汰

在 Chrome 中，单个 tab 的 BFCache 可缓存页面数量有限（常见上限约 6），到上限后按 LRU 淘汰。可利用这一点做“是否命中”的侧信道判断。

# 题目利用链

exp 页面链：

`/a -> /b -> /c -> /d -> /e -> /f -> /search.php?q=... -> /f -> /g -> history.back() ... -> /a`

目的：
- 通过是否多占用一个 BFCache 槽位，改变第二轮回退时 `a` 的恢复状态。

信号定义（以当前 `exp.py` 为准）：
- `a_no_from_bfcache` => `hit`（红色 SIGNAL）
- `a_from_bfcache` => `miss`

# TIPS

/root/A5rZ/Table/b/SU-Note/env/web_deploy/.config 可以放宽最大并行 bot

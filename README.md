# SUCTF-2026 Archive

本仓库保留 SUCTF 2026 题目的 GitHub 友好最小可复现集，优先级为：

- 保证题目可复现
- 尽量压缩仓库体积
- 保持长期归档可维护

## 📑 目录导航

- [归档约定](#归档约定) — 各类目录的含义
- [复现规则](#复现规则) — 不同类型题目的启动方式
- [题目索引](#题目索引) — 按分类查看题目列表
  - [AI 系列](#ai-系列-5-题)
  - [密码学 (Crypto) 系列](#密码学-crypto-系列-6-题)
  - [杂项 (Misc) 系列](#杂项-misc-系列-7-题)
  - [二进制漏洞 (Pwn) 系列](#二进制漏洞-pwn-系列-7-题)
  - [逆向工程 (RE) 系列](#逆向工程-re-系列-8-题)
  - [Web 安全系列](#web-安全系列-8-题)
- [大文件处理](#大文件处理) — 已移除或转换的大文件说明
- [部署与依赖](#部署与依赖) — 外部依赖与特殊配置

**题目总计：41 道**

---

## 归档约定

- `attachments/`：选手附件或公开附件
- `env/`：可直接启动的环境，或最小可复现构建上下文
- `sourcecode/`：题目源码或瘦身后的源码快照
- `writeup/`：官方题解
- `exp/`：利用脚本、求解脚本或复现脚本
- 运行时生成目录如 `logs/`、`tmp/`、`uploads/`、`__pycache__/`、`.pytest_cache/` 不入库
- 纯 `docker save` 导出的镜像包、重复压缩包、构建缓存、系统垃圾文件不入库

## 复现规则

各题目按以下方式复现：

| 类型 | 说明 | 启动方式 |
| :-- | :-- | :-- |
| **docker-compose** | 题目服务已容器化，提供 Docker Compose 编排 | 进入表中目录后执行 `docker compose up -d --build` |
| **dockerfile** | 虽有 Dockerfile 但需手动构建和运行 | 以表中目录为 build context，执行 `docker build` 和 `docker run` |
| **archive** | 无独立服务，仅需附件和 writeup | 下载附件，按 writeup 流程手工复现 |
| **metadata-only** | 仅保留题目元数据，未汇总运行环境 | 参考题目 README 页面获取更多信息 |

---

## 题目索引

### AI 系列 — 5 题

| 题目 | 类型 | 复现入口 |
| :-- | :-- | :-- |
| SU_babyAI | archive | `AI/SU_babyAI/解题赛模板/` |
| SU_easyLLM | archive | `AI/SU_easyLLM/解题赛模板/` |
| SU_thief | dockerfile | `AI/SU_thief/docker/` |
| SU_我不是神偷 | dockerfile | `AI/SU_我不是神偷/docker/` |
| SU_谁是小偷 | dockerfile | `AI/SU_谁是小偷/docker/` |

### 密码学 (Crypto) 系列 — 6 题

| 题目 | 类型 | 复现入口 |
| :-- | :-- | :-- |
| SU_AES | docker-compose | `crypto/SU_AES/` |
| SU_Isogeny | docker-compose | `crypto/SU_Isogeny/解题赛模板/env/deploy/docker/` |
| SU_Lattice | docker-compose | `crypto/SU_Lattice/解题赛模板/env/deploy/docker/` |
| SU_Prng | docker-compose | `crypto/SU_Prng/` |
| SU_Restaurant | docker-compose | `crypto/SU_Restaurant/解题赛模板/env/deploy/docker/` |
| SU_RSA | archive | `crypto/SU_RSA/attachments/` + `crypto/SU_RSA/writeup/` |

### 杂项 (Misc) 系列 — 7 题

| 题目 | 类型 | 复现入口 |
| :-- | :-- | :-- |
| SU_Artifact_Online | docker-compose | `misc/SU_Artifact_Online/env/` |
| SU_CyberTrack | archive | `misc/SU_CyberTrack/attachments/website.zip` |
| SU_LightNovel | archive | `misc/SU_LightNovel/attachments/` |
| SU_MirrorBus-9 | docker-compose | `misc/SU_MirrorBus-9/env/pwn_deploy/` |
| SU_chaos | archive | `misc/SU_chaos/attachments/` |
| SU_forensics | archive | `misc/SU_forensics/解题赛模板/` |
| SU_signin | metadata-only | `misc/SU_signin/README.md` |

### 二进制漏洞 (Pwn) 系列 — 7 题

| 题目 | 类型 | 复现入口 |
| :-- | :-- | :-- |
| SU_Box | docker-compose | `pwn/SU_Box/env/pwn_deploy/` |
| SU_Chronos_Ring | docker-compose | `pwn/SU_Chronos_Ring/env/pwn_deploy/` |
| SU_Chronos_Ring1 | docker-compose | `pwn/SU_Chronos_Ring1/env/pwn_deploy/` |
| SU_EzRouter | dockerfile | `pwn/SU_EzRouter/firmware/` |
| SU_evbuffer | dockerfile | `pwn/SU_evbuffer/env/` |
| SU_fullchian | metadata-only | `pwn/SU_fullchian/README.md` |
| SU_minivfs | dockerfile | `pwn/SU_minivfs/env/` |

### 逆向工程 (RE) 系列 — 8 题

| 题目 | 类型 | 复现入口 |
| :-- | :-- | :-- |
| SU_Protocol | archive | `re/SU_Protocol/attachments/` + `re/SU_Protocol/env/` |
| SU_West | archive | `re/SU_West/attachments/SU_West.zip` |
| SU_easygal | archive | `re/SU_easygal/attachments/` + `re/SU_easygal/env/` |
| SU_flumel | archive | `re/SU_flumel/attachments/attachment.zip` + `re/SU_flumel/sourcecode/sourcecode.zip` |
| SU_Lock | archive | `re/SU_Lock/attachments/` |
| SU_MvsicPlayer | archive | `re/SU_MvsicPlayer/attachments/restore_attachment.sh` |
| SU_revird | archive | `re/SU_revird/attachments/SU_Revird.zip` |
| SU_老年固件 | archive | `re/SU_老年固件/attachment.zip` |

### Web 安全系列 — 8 题

| 题目 | 类型 | 复现入口 |
| :-- | :-- | :-- |
| SU_Note | docker-compose | `web/SU_Note/env/` |
| SU_Note-rev | docker-compose | `web/SU_Note-rev/env/` |
| SU_Thief | dockerfile | `web/SU_Thief/env/` |
| SU_cmsAgain | docker-compose | `web/SU_cmsAgain/env/` |
| SU_jdbc-master | docker-compose | `web/SU_jdbc-master/env/web_deploy/` |
| SU_sqli | docker-compose | `web/SU_sqli/env/web_deploy/` |
| SU_uri | docker-compose | `web/SU_uri/env/web_deploy/` |
| SU_wms | docker-compose | `web/SU_wms/env/` |

---

## 大文件处理

> 为适应 GitHub 单文件 100MB 限制，部分题目的大文件已移除、分片或转换。下表列出处理方式及恢复说明。

| 题目 | 已移除或转换 | 保留内容 | 恢复或重新打包 |
| :-- | :-- | :-- | :-- |
| misc/SU_Artifact_Online | `env/artifact.tar` | `sourcecode/server/` + `env/docker-compose.yml` | 直接 `docker compose up -d --build`，无需手工恢复 |
| pwn/SU_Box | `env/su_box.tar`、`attachments/pwn_deploy.zip` | `env/pwn_deploy/`、`sourcecode/sourcecode.zip` | 改包：在 `env/pwn_deploy/` 下执行 `zip -rq ../../attachments/pwn_deploy.zip .` |
| pwn/SU_Chronos_Ring* | `env/*.tar`、附件 zip | `env/pwn_deploy/` | 改包：在对应 `env/pwn_deploy/` 下重新 `zip -rq` |
| re/SU_easygal | `env/env.tar` | `env/pwn_deploy/`、`env/web_deploy/`、`attachments/` | 含有多个子环境，已可继续维护 |
| re/SU_flumel | 原始 `1.69G` 源码包 | 瘦身后的 `sourcecode/sourcecode.zip` | 重新编译时按 Flutter/Android 工具链恢复依赖并本地构建 |
| re/SU_MvsicPlayer | `attachments/SU_MusicPlayer.zip` | `attachments/SU_MusicPlayer.zip.part-*` 分片 | 运行 `re/SU_MvsicPlayer/attachments/restore_attachment.sh` 合并 |
| re/SU_Protocol | `env/env.tar` | `env/pwn_deploy/`、`env/web_deploy/`、`attachments/` | 含有多个子环境，已可继续维护 |
| web/SU_jdbc-master | `env/jdbc-master.tar`、`attachments/web_deploy.zip` | `env/web_deploy/` | 改包：在 `env/web_deploy/` 下执行 `zip -rq ../../attachments/web_deploy.zip .` |
| web/SU_sqli | `env/su_sqli.tar`、`attachments/application.zip` | `env/web_deploy/`、`application/` | 改包：在 `application/` 下重新打包成 zip |
| web/SU_uri | `env/su_uri.tar` | `env/web_deploy/` | 直接从保留的构建目录启动，无需恢复 |
| web/SU_wms | 单文件 `env/jeewms.war` | GitHub 友好的 `env/jeewms/` 展开目录 | 运行 `web/SU_wms/env/repack_jeewms.sh` 可重新生成 `jeewms.war` |

---

## 部署与依赖

### 系统环境要求

- **Docker & Docker Compose** — 大部分题目依赖容器化环境，构建时通常需联网拉取基础镜像和系统包
- **/dev/kvm** — `pwn/SU_Chronos_Ring` 和 `pwn/SU_Chronos_Ring1` 需要宿主机提供 KVM 虚拟化支持
- **pwndbg** — `pwn/SU_EzRouter` 的 Dockerfile 构建时会从 GitHub 拉取 pwndbg
- **本地工具链** — `re/SU_flumel` 若要重新编译 APK，需要本地 Flutter / Android toolchain
- **Windows 虚拟机** — `re/SU_Lock` 建议在开启测试模式的 Windows 10/11 虚拟机中复现

### 构建提示

- 首次构建可能耗时较长，因需拉取大量依赖镜像
- 如网络受限，可预先准备离线镜像或修改 Dockerfile 使用本地镜像源
- 部分题目支持从已保存的构建目录快速启动，无需重新编译

---

## 大文件处理

# SU_老年固件 分析记录

## 结论

`attachments/attachment.zip` 里的 `old.bin` 是一个先做了 `0x7f` 单字节异或、再包多层 `xz` 的固件样本。解密后可以明确看到 U-Boot 风格头部、环境变量，以及多个固件/容器块。

## 已恢复内容

### 1. 头部与环境变量

异或后可以直接读到以下字符串：

- `UBHDRv1U-Boot 1.1.3 (HN build)`
- `Board: malta`
- `Build: CTF-BUILD`
- `Date: 2025-11-06`
- `bootcmd=run ctf_boot`
- `autoboot_delay=3`
- `baudrate=115200`
- `serverip=192.168.1.100`
- `ipaddr=192.168.1.10`

这说明样本是固件方向，且不是普通源码包。

### 2. 顶层 `xz` 片段

在异或后的 `old.bin` 中可定位到 3 个顶层 `xz` 片段：

- `0x2028`
- `0x50ed4`
- `0x51aa4`

其中：

- `0x2028` 解出一个较大的 blob，里面包含大量 glibc/ld.so 相关字符串，更像运行时或库数据，不像源码树。
- `0x50ed4` 和 `0x51aa4` 解出 `SMALLFW` 容器。

### 3. `SMALLFW` 容器

两个 `SMALLFW` 容器里都能继续拆出嵌套 `xz`，再解出 ZIP 归档。恢复出的 ZIP 文件名包括：

- `file_0_0.txt`
- `file_0_1.txt`
- `file_0_2.txt`
- `file_1_0.txt`

这些文本文件都带有相同的样式：

- `CTF_PAYLOAD_<name>`
- JSON 片段 `{"id":"...","note":"sample"}`

说明这题能恢复的是容器/样本文件，而不是原始出题源码。

## 无法恢复的内容

- 没有恢复出原始源码树。
- 没有证据表明附件里存在可直接还原的完整程序工程。
- 第一层大 blob 里虽然有很多 glibc 字符串，但不足以反推出完整实现。

## 复现方式

使用 `exp/old_firmware_extract.py` 即可复现：

1. 读取 `attachments/attachment.zip`
2. 提取 `attachment/old.bin`
3. 对 `old.bin` 做 `0x7f` 异或
4. 递归提取 `xz` / ZIP 片段

脚本会把恢复出的二进制和 ZIP 内容写到 `exp/recovered/`。

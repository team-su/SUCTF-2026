# AES 随机 S-box 交互题题解（`env/chal.py` / `exp/exp.py`）

## 1. 题目概述

`env/chal.py` 会：

1. 随机生成 `seed`、`key`，实例化 `AES(key, seed)`。
2. 先输出一次 `flag` 的 ECB 密文。
3. 提供最多 `310` 次菜单交互：
   1. `change(seed, key)`：可改 S-box 和 key；
   2. `encrypt`：加密任意输入；
   3. `reset`：重置回最初的 secret `seed/key`。

核心攻击面在 `AES.py` 的 `change`：

```python
def change(self, s=None, k=None):
    if s:
        self.Sbox = Random(s).choices(self.Sbox, k=len(self.Sbox))
    if k:
        self.change_key(k)
```

`choices` 是“有放回采样”，可制造大量重复项，这会让 S-box 出现可控塌缩结构。

---

## 2. 关键漏洞点

### 2.1 可控的 S-box 采样索引

若原始 S-box 记作 `S0`，调用 `change(seed=s)` 后得到：

`S1[i] = S0[idx[i]]`，其中 `idx = Random(s).choices([0..255], k=256)`。

也就是：如果我们能构造目标 `idx` 序列，就能精确控制“新 S-box 每个位置取旧 S-box 的哪个位置值”。

`exp/exp.py` 通过 `exp/recover_seed.py` 从目标 `idx` 反推可用 seed。

### 2.2 “看起来非零、实际等价零密钥”的技巧

`env/chal.py` 里 key 输入经过：

```python
k = int(input(...) or 0, 16) or None
```

直接输入 `0` 会变成 `None`，不会触发改 key。  
`exp/exp.py` 用 `KEY0_EQUIV = 1 << 128` 绕过：

- 它是非零，能触发 `if k: self.change_key(k)`；
- 但 `AES.text2matrix` 只取 bit `120..0` 这 128 位，`1<<128` 的高位被截掉，等价于全 0 key。

于是可稳定把在线采样阶段的 key 设成“数学上的零密钥模型”。

### 2.3 可改 S-box 但不重做轮密钥

`change(seed, key=None)` 只改 `self.Sbox`，不会重跑 `change_key`。  
这使得“数据通道用新 S-box、轮密钥仍来自旧 S-box 的 key schedule”，可被单独利用来提取第 10 轮轮密钥。

---

## 3. 为什么会想到“积分/塌缩”思路

直觉来源：`choices` 可制造“高重数索引”，即让大量输入字节经过 `SubBytes` 后变成同一个值。

最极端地，令 `idx = [2,2,2,...,2]`，则新 S-box 是常值函数：

`Sconst[x] = S0[2] = c`。

AES 每轮 `SubBytes` 后全状态都变成常量 `c`；`ShiftRows/MixColumns` 对“全相等状态”不引入新信息。  
因此最后一个 `AddRoundKey` 前状态固定，密文满足：

`CT = K10_secret XOR (c,c,...,c)`。

这就是典型“积分式塌缩”：把明文影响和前面多轮复杂性都压没，只剩目标轮密钥的线性掩码。

---

## 4. 攻击流程

### 4.1 总流程

1. 在线采集：通过特殊 `idx` 结构，拿到一批密文约束；
2. 离线恢复 `S0[2..255]`（Go 程序 `exp/hybrid_recover.go`）；
3. `reset` 后做一次常值 S-box 塌缩，提取 `K10_secret`；
4. 逆 key schedule 还原 master key，解开最初给出的 flag 密文。

### 4.2 在线约束设计（`exp/exp.py`）

定义两类索引模板：

- `single(t): [t]*(t+1) + [t+1, t+2, ..., 255]`
- `pair(t):   [t]*(t+1) + [t+1] + [t+2, ..., 255]`

对应效果：

- `single(t)`：`S1[0..t]` 全变成同一未知值 `x=S0[t]`，`S1[t+1..255]` 保持为 `S0[t+1..255]`；
- `pair(t)`：`S1[0..t]=x`，`S1[t+1]=y=S0[t+1]`，尾部同样保持原值。

攻击参数默认：

- `SWITCH=207`
- `EXTRA_SINGLE=1`（single 从 `207` 开始采）
- `PAIR2_TS=206,200,196`（少量第二消息消歧）
- `MSG_REPEATS=2`

交互预算精确计算：

- `pair` 数量：`t=2,4,...,206` 共 `103`
- `single` 数量：`t=207..255` 共 `49`
- 每个样本要 `change + encrypt`：`2*(103+49)=304`
- `pair2` 额外 3 次 `encrypt`：`+3`
- 最后提取 `K10` 需要 `reset + change + encrypt`：`+3`
- 总计：`304+3+3 = 310`（正好贴题目上限）

### 4.3 离线恢复 `S0[2..255]`（`exp/hybrid_recover.go`）

分两阶段：

1. `single` 阶段从大到小恢复高位尾部（`t=255` 递减），因为 `t` 越大未知越少，唯一性最好；
2. `pair` 阶段对低位做 DFS 回溯，必要时用 `pair2` 作为二次约束消歧。

实现上做了：

- 1-block 早筛 + 全块验证；
- 并行 worker；
- 可选随机重启（`-r/-rp`）。

输出为长度 256 数组，按设计仅 `index 0/1` 留作 `-1`，其余都恢复完成。

### 4.4 提取 `K10_secret`

步骤：

1. `reset` 回 secret 状态（secret key + 原始 `S0` 轮密钥）；
2. `change(seed_const2, key=None)`，其中 `seed_const2` 对应 `idx=[2]*256`；
3. 加密一个 1-block 消息，得到 `ct_k10`；
4. `v2 = S0[2]` 已在上一步离线恢复得到，于是：

`K10_secret = ct_k10 XOR bytes([v2])*16`。

### 4.5 逆 key schedule + 解 flag

已知 `K10_secret` 与几乎完整 `S0`，逆 AES-128 key schedule 即可回推 master key。  
由于 `S0[0], S0[1]` 未直接恢复，剩余值集合只剩两个，枚举两种排列即可（`2!`）。

`exp/exp.py` 对两种排列都尝试逆推并解密 flag 密文，命中的那个即正确解。

---

## 5. 复现方式

本地直接运行：

```bash
cd ../exp
python3 exp.py
```

脚本会自动：

1. 启动本地 `env/chal.py`；
2. 采集并写出 `exp/cts.json`；
3. 自动编译并调用 `exp/hybrid_recover.go`；
4. 输出恢复出的 key 与 flag。

常用调参环境变量：

- `SWITCH`：pair/single 分界；
- `EXTRA_SINGLE`：single 向前补深度；
- `PAIR2_TS`：pair2 消歧位置；
- `HR_J`：Go 并行线程；
- `HR_R / HR_RP`：随机重启次数/并行度；
- `HR_ATTEMPTS`：离线失败后的重试次数；
- `HR_SEC`：单次离线搜索超时。

如果某次离线搜索失败（随机性导致），提高 `HR_ATTEMPTS` 或 `HR_R` 一般即可稳定通过。

实测耗时会有波动（主要在 `hybrid_recover` 阶段）。  
在本机 16 线程环境下，单次从采集到出结果通常是“十几秒到几十秒”；极端情况下可能超 50 秒或需要重试。

---

## 6. 一句话总结

这题本质不是“硬破 AES”，而是利用了 **可控 `choices` 造成的 S-box 结构塌缩**：  
先把问题降成可恢复 `S0` 的代数约束，再用常值 S-box 积分式提取 `K10`，最后逆 key schedule 拿到主密钥解 flag。

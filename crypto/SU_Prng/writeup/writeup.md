# CTF Writeup: Rotated XOR-Half 256-bit LCG (n=56, Interactive)

## 1. 题目模型

`env/chal.py` 的核心生成器：

```python
seed = (seed * a + b) mod 2^256
out  = ror(((seed >> 128) ^ (seed mod 2^128)), seed >> (256 - 250), 256)
```

并且交互给出：

- `a = ...`
- `out = [56 个输出]`
- `enc = AES_ECB(md5(str(seed)), flag)`
- 输入 seed，判断是否正确。

记：

- `s_i` 为第 `i` 次输出对应状态（注意 `next()` 先更新再输出）
- `z_i = (s_i >> 128) XOR (s_i mod 2^128)`（128-bit 值）
- `r_i = (s_i >> 6) & 0xff`
- `y_i = out_i = ror(z_i, r_i, 256)`

## 2. 核心观察

### 2.1 旋转候选集合很小

对每个 `y_i`，枚举 `r in [0,255]`，若 `rol(y_i, r)` 的高 128 位为 0，则 `r` 是候选。

```text
R_i = { r | (rol(y_i, r) >> 128) == 0 }
```

实践里 `|R_i|` 通常很小（常见 1~10）。

### 2.2 旋转量只依赖状态低 14 位

`r_i = (s_i >> 6) & 0xff`，只看 bit[6..13]。  
令 `x_i = s_i mod 2^14`，则：

```text
x_{i+1} = (a14 * x_i + b14) mod 2^14
r_i     = (x_i >> 6) & 0xff
```

这让我们可以先在 `2^14` 环上恢复旋转序列。

## 3. 攻击流程

## 3.1 恢复旋转序列 `r_i`

1. 用上面的 `R_i` 建候选集合。
2. 枚举局部 `(x_anchor, x_anchor+1)` 的低 6 位，得到 `b14`。
3. 前后向推进验证 `((x_i >> 6) & 0xff) ∈ R_i`。
4. 得到少量唯一 `r_seq`（本题常见 1~2 条）。

这一步在脚本里对应 `recover_rotation_sequences`。

## 3.2 去旋转，得到标准观测

对候选 `r_seq`：

```text
z_i = rol(y_i, r_i) & (2^128 - 1)
```

若某个 `rol(y_i, r_i)` 高 128 不为 0，则该序列错误。

## 3.3 用格构造恢复 `x_1 = low128(s_1)` 与 `b_low = b mod 2^128`

这部分是 paper + crypto.SE 里那条思路：

- 设 `x_i = low128(s_i)`，`u_i = z_i XOR x_i`（对应高半部分的截断关系）。
- 目标是在模 `2^t` 下逐步恢复 `(x_1 mod 2^t, b_low mod 2^t)`。
- 对每个 `t`，构造短权向量 `w`，满足：

```text
sum_j w_j * a^j ≡ 0 (mod 2^(128+t))
```

通过 Sage `LLL` 在核空间里找短向量（`find_weight_vector(s)`）。

- 用 `w` 对差分序列做线性组合，利用“真实解残差小、错解残差随机”的性质筛候选。
- 从低位逐步 lift 到 128 位。

本题 `n=56` 时使用了 small-n 优化路径：

- 4-bit 分层（不是 8-bit）
- 每层使用多个短向量（vector pack）
- 先从 `t=16` 起步（利用旋转阶段已给出的低位信息）

对应函数：`recover_low_half_candidates_smalln`。

## 3.4 由 `x_1` 恢复 `b_full`、`seed`

已知 `z_1,z_2`，和候选 `(x_1,b_low)`：

```text
s_1 = ((z_1 XOR x_1) << 128) | x_1
x_2 = (a*x_1 + b_low) mod 2^128
s_2 = ((z_2 XOR x_2) << 128) | x_2
b   = (s_2 - a*s_1) mod 2^256
```

再全序列校验输出是否一致。  
最后解：

```text
s_1 = a*seed + b (mod 2^256)
```

得到 seed 候选（兼容 `a` 偶数时的多解情况），用 `md5(str(seed))` 解 `enc`，PKCS#7 检查通过即真解。

## 4. 这版 `exp/exp.py` 的关键优化点

相比朴素版本，主要提速在：

1. **先恢复旋转再格恢复**，把 256-bit 旋转不确定性前置消掉。
2. **small-n 专用分支**（`n<=64`）：
   - 4-bit 分层；
   - 多短向量约束；
   - 从 `t=16` 起步。
3. **对 low14 族做 t=16 必要筛**，避免起始候选爆炸。
4. **命中后提前退出**，不继续跑其它旋转序列。

## 5. 复杂度（近似）

理论最坏仍是指数级（本质上是带噪截断同余恢复），但这题参数下实际由剪枝主导：

- 旋转恢复：约 `O(2^12 * n * 候选对数)` 级别（非常快）。
- small-n lifting：每层理论分支 `2^8`（4+4 bit），但候选数在实践中被压到几十/几百级。
- 总体实测远低于理论上界。

实测（本地多样本）：

- `n=56`，10/10 成功；
- `exp/exp.py` 自报耗时约 `5s ~ 10.4s`；
- 明显低于 90s 目标。

## 6. 交互用法（当前 `exp/exp.py`）

1. 本地：
```bash
cd ../exp
python3 exp.py
```
默认等价于 `--process ../env/chal.py`。

2. 远程：
```bash
cd ../exp
python3 exp.py --remote HOST PORT
```

脚本会自动：

- 用 `pwntools process/remote` 交互；
- 解析 `a=.../out=.../enc=...`；
- 求 seed；
- 回填答案并打印服务端返回。

## 7. reference

https://tosc.iacr.org/index.php/ToSC/article/view/8700

https://crypto.stackexchange.com/questions/80834/attacks-on-lcg-with-self-xor-output-function

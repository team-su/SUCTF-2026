# WriteUp

## 1.根据PyTorch 模型，提取等效矩阵

题目给定的网络前向传播过程如下：
1. 输入的 Flag（长度 $n=41$）经过 `kernel_size=3, stride=2` 的 `Conv1d`。
2. 经过 `Linear` 层将维度压缩到 $m=15$。
3. 加上范围在 $[-160, 160]$ 的噪声 $E$，最后对大素数 $q=1000000007$ 取模得到密文 $Y$。

在没有 ReLU 等激活函数的情况下，上述操作可以表示为：
$$Y \equiv W_{fc} \cdot M_{conv} \cdot X + E \pmod q$$

其中，$M_{conv}$ 是一维卷积等效的变体 Toeplitz 矩阵。我们可以通过分析 `stride` 和 `kernel` 的滑动窗口，在脚本中构造这个矩阵。最终，整个网络等价于一个单一矩阵 $W = W_{fc} \cdot M_{conv} \pmod q$，其形状为 $15 \times 41$。

---

## 2.数学建模与中心化格构造

提取出 $W$ 后，问题转化为解欠定方程组：
$$Y \equiv W \cdot X + E \pmod q$$
由于未知数个数（41）远多于方程个数（15），直接高斯消元会有海量解，且由于误差 $E$ 的存在，传统代数方法失效。

因为 $X$ 是 ASCII 码（全为小整数），$E$ 也是小整数，我们可以使用格密码学来求解。构造分块矩阵：
$$B = \begin{pmatrix} I_n & W^T & 0 \\ 0 & q \cdot I_m & 0 \\ 0 & -Y^T & C \end{pmatrix}$$

**高斯启发式（Gaussian Heuristic）**
如果直接使用上述矩阵，目标向量为 $v = (X, -E, C)$。由于 $X$ 的元素大多在 $50 \sim 120$ 之间，该向量的欧几里得范数极大概率会超过当前 57 维格的高斯启发式理论下界（约为 450）。此时目标向量将淹没在格的噪音中，LLL 和 BKZ 算法都会失效。

**中心化（Centering）**
我们知道 ASCII 码的平均值 $\mu \approx 80$。我们可以将目标从“寻找绝对值 $X$”转换为“寻找偏移量 $\Delta X = X - \mu$”。
代入原方程：
$$Y \equiv W \cdot (\Delta X + \mu \cdot \mathbf{1}) + E \pmod q$$
$$Y - \mu \cdot W \cdot \mathbf{1} \equiv W \cdot \Delta X + E \pmod q$$

令 $Y' = Y - \mu \cdot W \cdot \mathbf{1} \pmod q$。我们将目标向量成功压缩为了极短的 $v = (\Delta X, -E, C)$，此时向量长度大幅缩减，成为了格空间中绝对的最短向量。

---

## 3.BKZ 算法进行格约化

由于题目后期将噪声范围扩大到了 $[-160, 160]$，此时目标向量虽然经过中心化变短了，但整体长度依然逼近了 LLL 算法的搜索极限。（这里可以先试试LLL）

改用BKZ 算法。通过阶梯式递增 `block_size`（如 15, 20, 25...），我们可以在耗时与精度之间取得平衡，让 SageMath 进行更深度的多维空间搜索。

最终构造的格基矩阵为：
$$B = \begin{pmatrix} I_n & W^T & 0 \\ 0 & q \cdot I_m & 0 \\ 0 & -(Y')^T & C \end{pmatrix}$$

乘上解向量 $(\Delta X, K, 1)$ 后，在格中必包含一个短向量 $v = (\Delta X, -E, C)$。

---

## 4.完整解题脚本 (SageMath)

将题目生成的 `model.pth` 与下述 `exp.sage` 放在同一目录下运行：

```python
# exp.sage
import torch
import numpy as np

print("[*] 开始解析 PyTorch 模型...")
n = 41
m = 15
q = 1000000007

# 密文数据
Y = [776038603, 454677179, 277026269, 279042526, 78728856, 784454706, 29243312, 291698200, 137468500, 236943731, 733036662, 421311403, 340527174, 804823668, 379367062]

state_dict = torch.load("model.pth")
w_conv = [int(x) for x in state_dict['conv.weight'].squeeze().tolist()]
w_fc = [[int(x) for x in row] for row in state_dict['fc.weight'].tolist()]

conv_out_size = (n - 3) // 2 + 1
M_conv = np.zeros((conv_out_size, n), dtype=object)
for i in range(conv_out_size):
    M_conv[i, i*2 : i*2+3] = w_conv

# 提取等效线性矩阵 W_total
W_total = (np.array(w_fc, dtype=object) @ M_conv) % q
print(f"[+] 成功提取 W_total 矩阵，形状: {W_total.shape}")


print("[*] 构造中心化嵌入格...")
W = Matrix(ZZ, W_total.tolist())
Y_vec = vector(ZZ, Y)

mu = 80
W_ones = W * vector(ZZ, [1]*n)
Y_prime = vector(ZZ, [(Y_vec[i] - mu * W_ones[i]) % q for i in range(m)])

dim = n + m + 1
B = Matrix(ZZ, dim, dim)

for i in range(n): B[i, i] = 1
for i in range(n):
    for j in range(m): B[i, n+j] = W[j, i]
for i in range(m): B[n+i, n+i] = q
for i in range(m): B[dim-1, n+i] = -Y_prime[i]

C = 160
B[dim-1, dim-1] = C
 
print("[*] 格矩阵构造完毕，准备启动 BKZ ...")


found = False

for block_size in [15, 20, 25, 30]:
    print(f"\n[*] 正在尝试 BKZ(block_size={block_size})，请耐心等待...")
    
    # 执行 BKZ 算法
    B_bkz = B.BKZ(block_size=block_size)

    for row in B_bkz:
        if row[-1] == C or row[-1] == -C:
            
            vec = row if row[-1] == C else -row
            
            # 提取偏移量，并加上 mu (80) 恢复出原始 ASCII 码
            flag_chars = [int(c + mu) for c in vec[:n]]
            
            try:
                # 过滤掉明显超出 ASCII 范围的乱码解
                if any(c < 0 or c > 255 for c in flag_chars):
                    continue
                    
                flag = "".join([chr(c) for c in flag_chars])
                
                # 检查你的指定前缀
                if "SUCTF{" in flag:
                    print(f"\n[+] 成功！在使用 block_size={block_size} 时恢复 Flag: {flag}")
                    print(f"[+] 附带恢复出的模型噪声 E: {list(vec[n : n+m])}")
                    found = True
                    break
            except Exception:
                pass
                
    if found:
        break

if not found:
    print("\n[-] BKZ 依然未能找到 Flag。")
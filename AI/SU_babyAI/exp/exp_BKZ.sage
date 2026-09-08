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
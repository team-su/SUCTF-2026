import torch
import torch.nn as nn
import random

# 题目参数
FLAG = b"SUCTF{PyT0rch_m0del_c4n_h1d3_LWE_pr0bl3m}"
q = 1000000007
n = len(FLAG)  # n = 41
m = 15

class ModuloNet(nn.Module):
    def __init__(self, n_in, m_out):
        super().__init__()
        # 一维卷积层：in_channels=1, out_channels=1, kernel_size=3, stride=2
        self.conv = nn.Conv1d(1, 1, 3, stride=2, bias=False)
        conv_out_size = (n_in - 3) // 2 + 1
        
        # 线性层：模拟分类器降维
        self.fc = nn.Linear(conv_out_size, m_out, bias=False)

def generate_task():
    model = ModuloNet(n, m)
    
    # 随机初始化模型权重为 [0, q) 之间的整数
    with torch.no_grad():
        model.conv.weight.copy_(torch.randint(0, q, model.conv.weight.shape, dtype=torch.float32))
        model.fc.weight.copy_(torch.randint(0, q, model.fc.weight.shape, dtype=torch.float32))
        
    # 保存模型权重文件
    torch.save(model.state_dict(), "model.pth")
    print("[*] 成功生成 model.pth")

  
    w_conv = model.conv.weight.squeeze().long().tolist()
    w_fc = model.fc.weight.long().tolist()
    
    x_data = list(FLAG)
    
    # 精确模拟卷积层
    conv_out = []
    for i in range((n - 3) // 2 + 1):
        window = x_data[i*2 : i*2+3]
        val = sum(w * x for w, x in zip(w_conv, window))
        conv_out.append(val)
        
    # 精确模拟线性层 + 注入微小噪声
    Y = []
    for i in range(m):
        val = sum(w * x for w, x in zip(w_fc[i], conv_out))
        noise = random.randint(-160, 160)
        Y.append((val + noise) % q)

    print(f"n = {n}")
    print(f"m = {m}")
    print(f"q = {q}")
    print(f"Y = {Y}")

if __name__ == "__main__":
    generate_task()

'''
n = 41
m = 15
q = 1000000007
Y = [776038603, 454677179, 277026269, 279042526, 78728856, 784454706, 29243312, 291698200, 137468500, 236943731, 733036662, 421311403, 340527174, 804823668, 379367062]
'''
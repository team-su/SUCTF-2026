import torch
import torch.nn as nn
from tqdm import tqdm
import numpy as np
import requests
import base64
import io

url = 'http://1.95.113.59:10003'
n=256

linear_input_num = 16
conv1_input_num = 32
conv_input_num = 32

input_size = 32

class Net(nn.Module):
    def __init__(self):
        super(Net, self).__init__()
        self.linear = nn.Linear(n, n)
        self.conv = nn.Conv2d(1, 1, (3, 3), stride=1)
        self.conv1 = nn.Conv2d(1, 1, (2, 2), stride=2)

        # 注册钩子
        self.linear_input = None
        self.linear.register_forward_hook(self.get_linear_input)

    def get_linear_input(self, module, input, output):
        # 捕获输入
        self.linear_input = input[0]

    def forward(self, x):
        x = nn.functional.pad(x, (2, 0, 2, 0), mode='constant', value=0)
        x = self.conv(x)
        x = self.conv1(x)
        x = x.view(-1)
        x = self.linear(x)
        return x
    
device = torch.device("cuda" if torch.cuda.is_available() else "cpu")
model = Net().to(device)

model_weights_path = 'model.pth'
model.load_state_dict(torch.load(model_weights_path, map_location=device))

W = [[0 for _ in range(n)] for _ in range(n)]
b = torch.zeros(n, requires_grad=True)

def get_predict(model_input):

    response = requests.post(url + '/predict', 
                            json={'image': model_input.tolist()},
                            headers={'Content-Type': 'application/json'})
    logits = torch.tensor([response.json()['prediction']], dtype=torch.float32)[0]
    return logits

def attack_model(linear_input):
    linear_input = torch.tensor(linear_input).view(linear_input_num,linear_input_num)
    conv1_input = [[0 for _ in range(conv1_input_num)] for _ in range(conv1_input_num)]
    for j in range(linear_input_num):
        for k in range(linear_input_num):
            x = linear_input[j][k] - model.conv1.bias
            x = x.detach().cpu().numpy()
            x = x[0]
            conv1_input[j*2][k*2] = x*2
    '''conv1_input = torch.tensor(conv1_input, dtype=torch.float32).unsqueeze(0).unsqueeze(1).to(device)
    print(conv1_input)
    print(model.conv1(conv1_input).view(-1))'''
    conv_input = [[0 for _ in range(conv_input_num+2)] for _ in range(conv_input_num+2)]
    for j in range(conv1_input_num):
        for k in range(conv1_input_num):
            x = conv1_input[j][k] - model.conv.bias
            x = x.detach().cpu().numpy()
            x = x[0]
            for a in range(3):
                for b in range(3):
                    weight = model.conv.weight[0][0][a][b].detach().cpu().numpy()
                    x -= conv_input[j+a][k+b]*weight
            weight = model.conv.weight[0][0][2][2].detach().cpu().numpy()
            conv_input[j+2][k+2] = x / weight
    conv_input = torch.tensor(conv_input, dtype=torch.float32).unsqueeze(0).to(device)
    x = conv_input
    x = model.conv(x)
    conv_input = conv_input[:, 2:, 2:]
    logits = get_predict(conv_input)
    return logits

def conv_input_get(linear_input):
    linear_input = torch.tensor(linear_input).view(linear_input_num,linear_input_num)
    conv1_input = [[0 for _ in range(conv1_input_num)] for _ in range(conv1_input_num)]
    for j in range(linear_input_num):
        for k in range(linear_input_num):
            x = linear_input[j][k] - model.conv1.bias
            x = x.detach().cpu().numpy()
            x = x[0]
            conv1_input[j*2][k*2] = x*2
    '''conv1_input = torch.tensor(conv1_input, dtype=torch.float32).unsqueeze(0).unsqueeze(1).to(device)
    print(conv1_input)
    print(model.conv1(conv1_input).view(-1))'''
    conv_input = [[0 for _ in range(conv_input_num+2)] for _ in range(conv_input_num+2)]
    for j in range(conv1_input_num):
        for k in range(conv1_input_num):
            x = conv1_input[j][k] - model.conv.bias
            x = x.detach().cpu().numpy()
            x = x[0]
            for a in range(3):
                for b in range(3):
                    weight = model.conv.weight[0][0][a][b].detach().cpu().numpy()
                    x -= conv_input[j+a][k+b]*weight
            weight = model.conv.weight[0][0][2][2].detach().cpu().numpy()
            conv_input[j+2][k+2] = x / weight
    conv_input = torch.tensor(conv_input, dtype=torch.float32).unsqueeze(0).to(device)
    conv_input = conv_input[:, 2:, 2:]
    return conv_input

b = attack_model([0 for _ in range(n)]).to(device)

print(b)
print(model.linear.bias)

for i in tqdm(range(n)):
    linear_input = [0 for _ in range(n)]
    linear_input[i] = 30

    weight_line_30 = attack_model(linear_input).to(device)

    linear_input[i] = 100030
    
    weight_line = attack_model(linear_input).to(device) - weight_line_30
    weight_line = weight_line.detach().cpu().numpy()
    for j in range(len(weight_line)):
        W[j][i] = weight_line[j] / 100000.0
W = torch.tensor(W).to(device)


# 计算元素差值
difference = torch.abs(W - model.linear.weight)

# 获取最大差值
max_difference = torch.max(difference)

print(torch.sum(difference > 0.0005))
print(max_difference)

# 计算元素差值
difference = torch.abs(b - model.linear.bias)

# 获取最大差值
max_difference = torch.max(difference)

print(torch.sum(difference > 0.0005))
print(max_difference)

model.linear.weight.data = W
model.linear.bias.data = b
torch.save(model.state_dict(), 'model_exp.pth')


model.load_state_dict(torch.load('model_exp.pth', weights_only=True, map_location=torch.device('cpu')))
state_dict = model.state_dict()

# 将 state_dict 保存到字节流中
buffer = io.BytesIO()
torch.save(state_dict, buffer)
buffer.seek(0)  # 将流指针移动到开始位置

# 将字节流转换为 Base64 字符串
base64_string = base64.b64encode(buffer.read()).decode('utf-8')
response3 = requests.post(url + '/flag', 
                        json={'model': base64_string},
                        headers={'Content-Type': 'application/json'})
print(response3.json())
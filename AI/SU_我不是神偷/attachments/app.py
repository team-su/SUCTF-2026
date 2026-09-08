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
        self.conv=nn.Conv2d(1, 1, (8, 8), stride=1)
        self.conv1=nn.Conv2d(1, 1, (7, 7), stride=1)

    def forward(self, x):
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
            

            if torch.sum(~(abs(param - user_param) <= 0.01)):
                return jsonify({'error': f'Layer weight difference too large'}), 400
        
        with open('/app/flag', 'r') as f:
            flag = f.read()
        return jsonify({'flag': f'Here is your flag: {flag}'})
    
    except Exception as e:
        return jsonify({'error': str(e)}), 500

if __name__ == '__main__':
    app.run(host="0.0.0.0", port=8081)


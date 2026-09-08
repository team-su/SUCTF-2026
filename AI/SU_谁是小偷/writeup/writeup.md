# SU_谁是小偷 官方题解

## 一、题目简介

本题是一道模型窃取方向的 Web/AI 结合题。题目对外提供了一个推理接口 `/predict`，同时要求选手构造一个与远程模型足够接近的模型文件并提交到 `/flag`。附件中给出了 `model_base.pth`，并提示：**远程模型与附件模型的线性层参数一致**。

题目的突破点在于：虽然远程模型的卷积层未知，但其线性层是已知的，并且整个前向过程不包含非线性，因此可以通过黑盒查询恢复卷积层参数，最终伪造完整模型通过校验。

## 二、预期考点

本题主要考查以下内容：

- 对 PyTorch 模型结构和 `state_dict` 的理解；
- 对线性层、卷积层前向计算过程的分析能力；
- 基于黑盒接口进行参数恢复的思路；
- 对 PyTorch 序列化格式的基本认识。

## 三、源码分析

题目核心服务代码如下，位于 `app.py:15`：

```python
class Net(nn.Module):
    def __init__(self):
        super(Net, self).__init__()
        self.linear = nn.Linear(256, 256)
        self.conv = nn.Conv2d(1, 1, (4, 4), stride=1)

    def forward(self, x):
        x = self.conv(x)
        x = x.view(-1)
        x = self.linear(x)
        return x
```

可以看到模型结构非常简单：

1. 先对输入做一次 `4×4` 卷积；
2. 将卷积结果拉平成 256 维向量；
3. 通过一个 `Linear(256, 256)` 得到输出。

`/predict` 接口位于 `app.py:38`，逻辑是直接接收用户输入并返回模型输出：

```python
@app.route('/predict', methods=['POST'])
def predict():
    image_data = request.json['image']
    tensor_back = torch.tensor(image_data).to(device)
    with torch.no_grad():
        outputs = model(tensor_back)
    return jsonify({'prediction': outputs.tolist()})
```

`/flag` 接口位于 `app.py:55`，会加载选手上传的模型并逐层比较参数：

```python
for i, (param, user_param) in enumerate(zip(model.parameters(), user_model.parameters())):
    if torch.sum(~(abs(param - user_param) <= 0.01)):
        return jsonify({'error': f'Layer weight difference too large'}), 400
```

只要选手提交的每一层参数与远程模型的对应参数误差不超过 `0.01`，即可通过校验并读取 flag。

## 四、解题关键

### 1. 已知线性层

附件 `model_base.pth` 中包含与远程一致的线性层参数。实测可从归档中直接提取：

- `model/data/0`：`linear.weight`
- `model/data/1`：`linear.bias`

记线性层参数为 `W` 和 `b`。

### 2. 整体前向是线性的

设卷积层输出展平后为 `z`，则整个模型输出满足：

```text
y = W · z + b
```

由于题目中不含 ReLU、Sigmoid 等非线性算子，且线性层为 `256 -> 256`，实测 `W` 可逆，因此：

```text
z = W^{-1} · (y - b)
```

这意味着：只要能够调用 `/predict` 获得输出 `y`，就可以直接反推出卷积层输出 `z`。

换言之，题目虽然没有直接暴露卷积层，但通过一个已知可逆的线性层，将卷积层输出“泄露”了出来。

## 五、参数恢复过程

### 1. 恢复 `conv.bias`

首先构造全零输入：

```text
x = 0
```

此时卷积核部分对输出没有贡献，卷积层每一个位置的输出都只剩下同一个偏置值，因此卷积层输出展平后应为：

```text
z0 = [conv_bias, conv_bias, ..., conv_bias]
```

调用 `/predict` 得到 `y0` 后，计算：

```text
z0 = W^{-1} · (y0 - b)
```

理论上 `z0` 的 256 个元素应完全一致。取其平均值即可得到 `conv.bias`。

### 2. 恢复 `conv.weight`

接着构造一个单点激活输入，例如仅让 `(3,3)` 位置为 `1`，其余为 `0`：

```text
x[3,3] = 1
```

再次调用 `/predict` 得到 `y1`，并反推出对应的卷积层输出：

```text
z1 = W^{-1} · (y1 - b)
```

将 `z1` reshape 回 `16×16` 后，减去前面求得的 `conv.bias`，得到该单点激活带来的额外贡献。这部分贡献正好落在左上角 `4×4` 区域，对应卷积核翻转后的结果。再做一次翻转即可恢复原始卷积核。

因此，利用极少量的查询就可以恢复出完整的卷积层参数。

## 六、构造提交模型

拿到以下四组参数后：

- `linear.weight`
- `linear.bias`
- `conv.weight`
- `conv.bias`

即可构造一个新的 `state_dict` 并提交到 `/flag`。

这里有两种做法：

- 本地有 PyTorch 环境时，直接构造同结构模型并 `torch.save(state_dict)`；
- 无 PyTorch 环境时，按 PyTorch 的 zip 序列化格式手工构造最小可加载归档。

本题附件中已提供了线性层原始存储内容，因此最方便的方式是：

1. 复用 `model_base.pth` 中的线性层二进制；
2. 将恢复出的卷积层参数以 `float32` 写入；
3. 重新打包为合法的 PyTorch 权重文件；
4. base64 编码后提交给 `/flag`。

## 七、参考脚本说明

仓库中给出了一份完整复现脚本：`solve_remote.py:1`。

该脚本完成如下工作：

- 从 `model_base.pth` 中提取线性层；
- 查询远程 `/predict` 接口；
- 反解出卷积层偏置与卷积核；
- 构造可被 `torch.load(weights_only=True)` 加载的模型文件；
- 提交至 `/flag`。

直接执行：

```bash
python solve_remote.py
```

即可复现整条利用链。

## 八、实测结论

对题目部署环境进行测试后，可以确认：

- 卷积层参数可以被成功恢复；
- 构造出的模型可以通过权重误差校验；
- 当前环境中 `/flag` 最终报错为缺少 `/app/flag` 文件。

这说明从题目设计角度看，**利用链是成立的**；若部署环境补齐 flag 文件，即可正常返回结果。

本次测试恢复出的卷积层参数约为：

```text
conv.weight =
[[-6, -10,  1, -4],
 [ 6,  -1,  8,  8],
 [ 9,  -7,  6, -4],
 [-5,   6,  8, -6]]

conv.bias = 4
```

## 九、题目总结

本题的核心在于：

- 服务端开放了可重复调用的黑盒推理接口；
- 附件中泄露了与远程一致的完整线性层；
- 模型整体为纯线性结构；
- 已知线性层可逆，从而可以把黑盒输出直接还原为中间层结果。

因此，本题本质上是一个“借助已知可逆线性层恢复未知卷积层”的问题，而不需要进行任何训练。

可以将整条利用链概括为：

**提取已知线性层 → 调用 `/predict` → 反推卷积层输出 → 恢复卷积参数 → 伪造模型 → 提交 `/flag`。**

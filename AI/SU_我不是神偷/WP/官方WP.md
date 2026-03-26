# SU_我不是神偷 官方 WP

## 题目概述

题目提供了一个 Flask 服务，核心接口有两个：

- `/predict`：输入一张 `22×22` 的单通道图像，返回模型输出。
- `/flag`：提交一个模型参数文件；如果和服务端模型逐层参数差异都不超过 `0.01`，就返回 flag。

题目目录内给出了一个“强化前”的模型 `model_base.pth`，并额外给出了强化后模型的两个卷积层 bias：

- `conv.bias = -5.640393257141113`
- `conv1.bias = -4.398319721221924`

服务代码见 `app.py:15`、`app.py:40`、`app.py:57`。

模型结构如下：

```python
class Net(nn.Module):
    def __init__(self):
        super(Net, self).__init__()
        self.linear = nn.Linear(256, 256)
        self.conv = nn.Conv2d(1, 1, (4, 4), stride=1)
        self.conv1 = nn.Conv2d(1, 1, (4, 4), stride=1)

    def forward(self, x):
        x = self.conv(x)
        x = self.conv1(x)
        x = x.view(-1)
        x = self.linear(x)
        return x
```

注意，这个网络全程没有激活函数，因此它本质上是一个**线性/仿射系统**。

---

## 漏洞本质

`/flag` 的校验逻辑并不是验证“模型来源”，而是直接比较提交模型和服务端真实模型的每个参数：

```python
for i, (param, user_param) in enumerate(zip(model.parameters(), user_model.parameters())):
    if torch.sum(~(abs(param - user_param) <= 0.01)):
        return jsonify({'error': f'Layer weight difference too large'}), 400
```

也就是说，只要我们能从 `/predict` 这个黑盒接口把服务端模型重建出来，就能伪造一个足够接近的模型并拿到 flag。

由于整个网络是仿射变换，所以对输入做有限次查询，就可以恢复整体映射。

---

## 第一步：把 `/predict` 当作仿射黑盒

设输入图片展平成向量 `x ∈ R^484`，输出为 `y ∈ R^256`。

由于网络无非线性，存在矩阵 `A` 和向量 `b` 使得：

```text
y = A x + b
```

如何恢复 `A` 和 `b`：

1. 查询全零输入，得到 `b = f(0)`。
2. 对 484 个标准基向量 `e_i` 分别查询：

   ```text
   A[:, i] = f(e_i) - f(0)
   ```

因此总共只需要 `1 + 484 = 485` 次 `/predict` 请求，就能恢复整个黑盒的仿射映射。

这一步已经在 `solve.py:57` 中实现。

---

## 第二步：利用给出的基线模型

题目给了 `model_base.pth`。实测发现，强化后的真实模型虽然卷积参数变了，但 `linear.weight` 和 `linear.bias` 仍然沿用了基线模型。

因此可以把整体仿射矩阵写成：

```text
A = W_linear · S
```

其中：

- `W_linear` 是 `model_base.pth` 中的线性层权重；
- `S` 是两层卷积合并后的整体线性变换。

于是：

```text
S = W_linear^{-1} · A
```

这一步对应 `solve.py:88`。

---

## 第三步：从整体卷积矩阵中提取等效卷积核

连续两个 `4×4` 卷积叠加后，等价于一个 `7×7` 的卷积核。

对于输出平面上的每一个位置，`S` 的某一行其实就是“同一个 `7×7` 卷积核在输入上的平移结果”。

因此可以：

1. 枚举 `S` 的 256 行；
2. 按输出坐标把每一行切出对应的 `7×7` patch；
3. 对这些 patch 取平均，得到等效卷积核 `K`。

如果服务端确实是卷积结构，那么这些 patch 会非常一致。实测中该一致性残差极小，说明思路成立。

这一步对应 `solve.py:88` 到 `solve.py:99`。

---

## 第四步：把 `7×7` 核分解回两个 `4×4` 核

设两层卷积核分别为 `k1` 和 `k2`，那么它们的 full convolution 满足：

```text
K = k1 * k2
```

这里的 `*` 表示 full convolution，输出尺寸正好是 `7×7`。

这个方程是双线性的，可以用数值方法做最小二乘分解。脚本中使用 `scipy.optimize.least_squares` 做多次随机重启，求一组满足误差极小的 `k1`、`k2`。

这一步对应：

- `solve.py:107`：定义 full convolution
- `solve.py:123`：做多次重启拟合

---

## 第五步：利用 bias 约束修正缩放

卷积核的分解存在一个天然的缩放自由度：

```text
(c · k1) * (k2 / c) = k1 * k2
```

也就是说，即便 `K` 固定，`k1` 和 `k2` 仍可以整体放大/缩小，而等效 `7×7` 核不变。

这时题目给出的两个 bias 就非常关键。

对全零输入，第一层卷积输出全常数 `conv.bias`；第二层卷积输入是常数图，所以第二层输出常数满足：

```text
const = conv1.bias + conv.bias · sum(k2)
```

另一方面，这个常数项也能从整体仿射偏置中反推出：

```text
pre_linear_bias = W_linear^{-1} · (b - linear_bias)
```

理论上这里应当是一个全常数向量，于是就得到目标常数 `const`，再反过来确定 `sum(k2)`，从而消除缩放自由度。

这部分在 `solve.py:102` 和 `solve.py:164`。

---

## 第六步：组装模型并提交

恢复出：

- 基线线性层 `linear.weight` / `linear.bias`
- 两个卷积核 `k1` / `k2`
- 题目给定的两个卷积 bias

之后直接构造一个 `Net`，保存其 `state_dict`，再 base64 编码后 POST 到 `/flag` 即可。

由于两个卷积核的顺序可能存在 `ab` / `ba` 两种候选，脚本会两种顺序都尝试，并选择第一个不返回 `400` 的模型。

对应代码：

- `solve.py:145`：构造模型
- `solve.py:186`：提交模型
- `solve.py:228`：尝试两种顺序

---

## 一键求解脚本

本题目录下已经提供了完整求解脚本 `solve.py:1`。

运行方式：

```bash
conda activate xyq
python solve.py --url http://127.0.0.1:8082 --base-model model_base.pth --output recovered_model.pth
```

脚本会自动完成：

1. 提取 `/predict` 的整体仿射映射；
2. 从 `model_base.pth` 拿到线性层；
3. 反推出真实卷积参数；
4. 保存恢复出的模型到 `recovered_model.pth`；
5. 自动提交到 `/flag`。

---

## 为什么这个方法稳定

这份脚本并没有硬编码旧卷积权重，而是重新从线上 `/predict` 黑盒抽取真实映射，因此：

- 如果**只改卷积权重**，脚本通常仍然能打通；
- 如果**网络结构不变**、**线性层仍沿用基线**、**两个卷积 bias 已知**，脚本依然有效。

当前版本脚本依赖以下前提：

1. 网络结构仍然是 `22×22 -> Conv(4×4) -> Conv(4×4) -> Linear(256,256)`；
2. 线性层仍与 `model_base.pth` 相同；
3. `conv.bias` 与 `conv1.bias` 已知且正确；
4. 网络中没有新增激活函数、池化、BN 等非线性模块。

如果连线性层也一起改了，或者卷积 bias 也改了但没有告诉选手，那么这份脚本就需要对应升级。

## 总结

这题的核心不在于“训练模型”，而在于：

1. 识别整个网络是一个仿射变换；
2. 利用 `/predict` 黑盒恢复整体矩阵；
3. 借助基线模型拆出线性层和等效卷积层；
4. 用已知 bias 消除卷积核分解时的缩放歧义；
5. 组装并提交一个足够接近的模型，通过 `/flag` 的逐参数校验。

从出题视角看，这题考查的是：

- 对线性网络可逆性的理解；
- 对卷积层平移不变结构的识别；
- 将黑盒查询、矩阵恢复与数值优化结合起来完成模型重建。

 `solve.py` 即可直接拿到最终 flag。

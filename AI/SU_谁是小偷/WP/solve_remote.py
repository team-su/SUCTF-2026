import base64
import io
import json
import pickle
import sys
import types
import urllib.error
import urllib.request
import zipfile
from collections import OrderedDict

import numpy as np


URL = sys.argv[1] if len(sys.argv) > 1 else "http://10.162.238.82:8081"


class FloatStorage:
    pass


def _rebuild_tensor_v2(storage, storage_offset, size, stride, requires_grad, backward_hooks):
    return storage, storage_offset, size, stride, requires_grad, backward_hooks


def post_json(path, payload):
    data = json.dumps(payload).encode()
    req = urllib.request.Request(
        URL + path,
        data=data,
        headers={"Content-Type": "application/json"},
        method="POST",
    )
    try:
        with urllib.request.urlopen(req, timeout=15) as response:
            return json.loads(response.read().decode())
    except urllib.error.HTTPError as exc:
        body = exc.read().decode(errors="replace")
        raise RuntimeError(f"HTTP {exc.code}: {body}") from exc


def load_linear_layers(path):
    with zipfile.ZipFile(path) as archive:
        linear_weight = archive.read("model/data/0")
        linear_bias = archive.read("model/data/1")
    weight = np.frombuffer(linear_weight, dtype="<f4").reshape(256, 256).astype(np.float32)
    bias = np.frombuffer(linear_bias, dtype="<f4").reshape(256).astype(np.float32)
    return weight, bias, linear_weight, linear_bias


def predict(image):
    result = post_json("/predict", {"image": image.tolist()})
    if "prediction" not in result:
        raise RuntimeError(result)
    return np.array(result["prediction"], dtype=np.float32)


def recover_conv(weight, bias):
    inv_weight = np.linalg.inv(weight).astype(np.float32)

    zero = np.zeros((1, 1, 19, 19), dtype=np.float32)
    output_zero = predict(zero)
    conv_zero = inv_weight @ (output_zero - bias)
    conv_bias = float(conv_zero.mean())
    conv_bias_std = float(conv_zero.std())

    one_hot = zero.copy()
    one_hot[0, 0, 3, 3] = 1.0
    output_hot = predict(one_hot)
    conv_hot = (inv_weight @ (output_hot - bias)).reshape(16, 16)
    delta = conv_hot - conv_bias
    kernel = np.flip(delta[:4, :4], axis=(0, 1)).astype(np.float32)
    return kernel, np.array([conv_bias], dtype=np.float32), conv_bias_std


def conv2d(image, kernel, bias):
    output = np.zeros((16, 16), dtype=np.float32)
    for row in range(16):
        for col in range(16):
            output[row, col] = np.sum(image[0, 0, row : row + 4, col : col + 4] * kernel) + bias[0]
    return output.reshape(256)


def verify_recovery(weight, bias, kernel, conv_bias):
    image = np.arange(19 * 19, dtype=np.float32).reshape(1, 1, 19, 19) / 17.0
    expected = weight @ conv2d(image, kernel, conv_bias) + bias
    remote = predict(image)
    return float(np.max(np.abs(expected - remote)))


class StorageRef:
    def __init__(self, key, numel):
        self.key = key
        self.numel = numel


def register_fake_torch():
    torch_module = types.ModuleType("torch")
    utils_module = types.ModuleType("torch._utils")

    FloatStorage.__module__ = "torch"
    _rebuild_tensor_v2.__module__ = "torch._utils"

    torch_module.FloatStorage = FloatStorage
    utils_module._rebuild_tensor_v2 = _rebuild_tensor_v2

    sys.modules["torch"] = torch_module
    sys.modules["torch._utils"] = utils_module
    return FloatStorage, _rebuild_tensor_v2


class TensorRef:
    def __init__(self, key, numel, size, stride, rebuild_tensor_v2):
        self.key = key
        self.numel = numel
        self.size = size
        self.stride = stride
        self.rebuild_tensor_v2 = rebuild_tensor_v2

    def __reduce__(self):
        return (
            self.rebuild_tensor_v2,
            (StorageRef(self.key, self.numel), 0, self.size, self.stride, False, OrderedDict()),
        )


class TorchPickler(pickle.Pickler):
    def __init__(self, file, protocol, float_storage):
        super().__init__(file, protocol=protocol)
        self.float_storage = float_storage

    def persistent_id(self, obj):
        if isinstance(obj, StorageRef):
            return ("storage", self.float_storage, obj.key, "cpu", obj.numel)
        return None


def build_model(linear_weight_bytes, linear_bias_bytes, kernel, conv_bias):
    float_storage, rebuild_tensor_v2 = register_fake_torch()

    state_dict = OrderedDict()
    state_dict["linear.weight"] = TensorRef("0", 256 * 256, (256, 256), (256, 1), rebuild_tensor_v2)
    state_dict["linear.bias"] = TensorRef("1", 256, (256,), (1,), rebuild_tensor_v2)
    state_dict["conv.weight"] = TensorRef("2", 16, (1, 1, 4, 4), (16, 16, 4, 1), rebuild_tensor_v2)
    state_dict["conv.bias"] = TensorRef("3", 1, (1,), (1,), rebuild_tensor_v2)

    data_buffer = io.BytesIO()
    TorchPickler(data_buffer, protocol=2, float_storage=float_storage).dump(state_dict)

    archive_buffer = io.BytesIO()
    with zipfile.ZipFile(archive_buffer, "w", compression=zipfile.ZIP_STORED) as archive:
        archive.writestr("model/data.pkl", data_buffer.getvalue())
        archive.writestr("model/byteorder", b"little")
        archive.writestr("model/data/0", linear_weight_bytes)
        archive.writestr("model/data/1", linear_bias_bytes)
        archive.writestr("model/data/2", kernel.astype("<f4").tobytes())
        archive.writestr("model/data/3", conv_bias.astype("<f4").tobytes())
        archive.writestr("model/version", b"3\n")
        archive.writestr("model/.data/serialization_id", b"1234567890123456789012345678901234567890")
    return archive_buffer.getvalue()


def submit_model(model_bytes):
    result = post_json("/flag", {"model": base64.b64encode(model_bytes).decode()})
    return result


def main():
    weight, bias, linear_weight_bytes, linear_bias_bytes = load_linear_layers("model_base.pth")
    kernel, conv_bias, conv_bias_std = recover_conv(weight, bias)
    verification_error = verify_recovery(weight, bias, kernel, conv_bias)
    model_bytes = build_model(linear_weight_bytes, linear_bias_bytes, kernel, conv_bias)
    try:
        result = submit_model(model_bytes)
    except RuntimeError as exc:
        result = {"error": str(exc)}

    print("URL:", URL)
    print("Recovered conv.bias std:", conv_bias_std)
    print("Recovered conv.weight:\n", kernel)
    print("Recovered conv.bias:", conv_bias.tolist())
    print("Verification max error:", verification_error)
    print("Flag response:", result)


if __name__ == "__main__":
    main()

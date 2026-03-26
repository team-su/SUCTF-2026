import argparse
import base64
import io
import json
import urllib.error
import urllib.request

import numpy as np
import torch
import torch.nn as nn
from scipy.optimize import least_squares


INPUT_SIZE = 22
LINEAR_SIZE = 256
CONV_BIAS = -5.640393257141113
CONV1_BIAS = -4.398319721221924


class Net(nn.Module):
    def __init__(self):
        super().__init__()
        self.linear = nn.Linear(LINEAR_SIZE, LINEAR_SIZE)
        self.conv = nn.Conv2d(1, 1, (4, 4), stride=1)
        self.conv1 = nn.Conv2d(1, 1, (4, 4), stride=1)

    def forward(self, x):
        x = self.conv(x)
        x = self.conv1(x)
        x = x.view(-1)
        x = self.linear(x)
        return x


def post_json(url, payload, timeout):
    body = json.dumps(payload).encode()
    request = urllib.request.Request(
        url,
        data=body,
        headers={"Content-Type": "application/json"},
    )
    try:
        with urllib.request.urlopen(request, timeout=timeout) as response:
            return response.status, response.read().decode("utf-8", errors="replace")
    except urllib.error.HTTPError as exc:
        return exc.code, exc.read().decode("utf-8", errors="replace")


def query_predict(base_url, image, timeout):
    status, text = post_json(f"{base_url}/predict", {"image": image.tolist()}, timeout)
    if status != 200:
        raise RuntimeError(f"/predict returned {status}: {text}")
    data = json.loads(text)
    return np.array(data["prediction"], dtype=np.float64)


def extract_affine_map(base_url, timeout):
    zeros = np.zeros((1, INPUT_SIZE, INPUT_SIZE), dtype=np.float32)
    bias = query_predict(base_url, zeros, timeout)
    matrix = np.empty((LINEAR_SIZE, INPUT_SIZE * INPUT_SIZE), dtype=np.float64)
    flat = zeros.reshape(-1)
    for index in range(flat.size):
        sample = zeros.copy()
        sample.reshape(-1)[index] = 1.0
        matrix[:, index] = query_predict(base_url, sample, timeout) - bias
        if (index + 1) % 64 == 0 or index + 1 == flat.size:
            print(f"[+] extracted {index + 1}/{flat.size} basis responses")
    return matrix, bias


def load_base_linear(path):
    state_dict = torch.load(path, map_location="cpu", weights_only=True)
    weight = state_dict["linear.weight"].detach().cpu().numpy().astype(np.float64)
    bias = state_dict["linear.bias"].detach().cpu().numpy().astype(np.float64)
    return state_dict, weight, bias


def shifted_equivalent_rows(kernel):
    rows = []
    for out_i in range(INPUT_SIZE - 6):
        for out_j in range(INPUT_SIZE - 6):
            image = np.zeros((INPUT_SIZE, INPUT_SIZE), dtype=np.float64)
            image[out_i : out_i + 7, out_j : out_j + 7] = kernel
            rows.append(image.reshape(-1))
    return np.stack(rows)


def recover_equivalent_kernel(base_linear_weight, affine_matrix):
    stem_matrix = np.linalg.solve(base_linear_weight, affine_matrix)
    patches = []
    for row_index in range(LINEAR_SIZE):
        out_i, out_j = divmod(row_index, INPUT_SIZE - 6)
        patch = stem_matrix[row_index].reshape(INPUT_SIZE, INPUT_SIZE)[out_i : out_i + 7, out_j : out_j + 7]
        patches.append(patch)
    patches = np.stack(patches)
    kernel = patches.mean(axis=0)
    structure_residual = np.linalg.norm(stem_matrix - shifted_equivalent_rows(kernel)) / np.linalg.norm(stem_matrix)
    kernel_spread = patches.std(axis=0).max()
    return kernel, structure_residual, kernel_spread


def recover_target_constant(base_linear_weight, base_linear_bias, affine_bias):
    pre_linear_bias = np.linalg.solve(base_linear_weight, affine_bias - base_linear_bias)
    return float(pre_linear_bias.mean()), float(pre_linear_bias.std())


def full_convolution_4x4(kernel_a, kernel_b):
    result = np.zeros((7, 7), dtype=np.float64)
    for i in range(4):
        for j in range(4):
            result[i : i + 4, j : j + 4] += kernel_a[i, j] * kernel_b
    return result


def unpack_factorization(params):
    kernel_a = np.empty((4, 4), dtype=np.float64)
    kernel_a.flat[0] = 1.0
    kernel_a.flat[1:] = params[:15]
    kernel_b = params[15:].reshape(4, 4)
    return kernel_a, kernel_b


def factor_equivalent_kernel(kernel, restarts, residual_goal):
    def objective(params):
        kernel_a, kernel_b = unpack_factorization(params)
        return (full_convolution_4x4(kernel_a, kernel_b) - kernel).reshape(-1)

    generator = np.random.default_rng(0)
    best = None
    for attempt in range(restarts):
        initial = generator.standard_normal(31)
        result = least_squares(
            objective,
            initial,
            method="trf",
            ftol=1e-15,
            xtol=1e-15,
            gtol=1e-15,
            max_nfev=50000,
        )
        residual = np.linalg.norm(objective(result.x)) / np.linalg.norm(kernel)
        if best is None or residual < best[0]:
            best = (residual, result.x)
            print(f"[+] factorization restart {attempt + 1}/{restarts}: residual={residual:.3e}")
        if residual <= residual_goal:
            break
    if best is None:
        raise RuntimeError("failed to factor equivalent kernel")
    return unpack_factorization(best[1]), best[0]


def build_candidate_model(base_linear_weight, base_linear_bias, kernel_first, kernel_second):
    model = Net()
    with torch.no_grad():
        model.linear.weight.copy_(torch.tensor(base_linear_weight, dtype=torch.float32))
        model.linear.bias.copy_(torch.tensor(base_linear_bias, dtype=torch.float32))
        model.conv.weight.copy_(torch.tensor(kernel_first[None, None], dtype=torch.float32))
        model.conv.bias.copy_(torch.tensor([CONV_BIAS], dtype=torch.float32))
        model.conv1.weight.copy_(torch.tensor(kernel_second[None, None], dtype=torch.float32))
        model.conv1.bias.copy_(torch.tensor([CONV1_BIAS], dtype=torch.float32))
    return model


def rescale_for_bias(kernel_first, kernel_second, target_constant):
    expected_second_sum = (target_constant - CONV1_BIAS) / CONV_BIAS
    scale = float(kernel_second.sum()) / expected_second_sum
    return kernel_first * scale, kernel_second / scale


def validate_candidate(model, affine_matrix, affine_bias, trials):
    worst = 0.0
    for _ in range(trials):
        sample = np.random.randn(INPUT_SIZE * INPUT_SIZE).astype(np.float32)
        tensor = torch.tensor(sample.reshape(1, 1, INPUT_SIZE, INPUT_SIZE))
        with torch.no_grad():
            predicted = model(tensor).detach().cpu().numpy().reshape(-1)
        expected = affine_matrix @ sample.astype(np.float64) + affine_bias
        worst = max(worst, float(np.max(np.abs(predicted - expected))))
    return worst


def serialize_model(model, output_path):
    torch.save(model.state_dict(), output_path)


def submit_model(base_url, model, timeout):
    buffer = io.BytesIO()
    torch.save(model.state_dict(), buffer)
    payload = {"model": base64.b64encode(buffer.getvalue()).decode()}
    return post_json(f"{base_url}/flag", payload, timeout)


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--url", default="http://127.0.0.1:8082")
    parser.add_argument("--base-model", default="model_base.pth")
    parser.add_argument("--output", default="recovered_model.pth")
    parser.add_argument("--timeout", type=float, default=20.0)
    parser.add_argument("--restarts", type=int, default=30)
    parser.add_argument("--factor-goal", type=float, default=1e-6)
    parser.add_argument("--validate-trials", type=int, default=5)
    args = parser.parse_args()

    print("[+] extracting affine map from /predict")
    affine_matrix, affine_bias = extract_affine_map(args.url.rstrip("/"), args.timeout)

    print(f"[+] loading base linear layer from {args.base_model}")
    _, base_linear_weight, base_linear_bias = load_base_linear(args.base_model)

    print("[+] recovering equivalent 7x7 kernel")
    equivalent_kernel, structure_residual, kernel_spread = recover_equivalent_kernel(base_linear_weight, affine_matrix)
    print(f"[+] shifted-kernel residual: {structure_residual:.3e}")
    print(f"[+] shifted-kernel max spread: {kernel_spread:.3e}")

    print("[+] recovering pre-linear constant term")
    target_constant, constant_spread = recover_target_constant(base_linear_weight, base_linear_bias, affine_bias)
    print(f"[+] pre-linear constant mean: {target_constant:.9f}")
    print(f"[+] pre-linear constant std:  {constant_spread:.3e}")

    print("[+] factoring 7x7 kernel into two 4x4 kernels")
    (factor_a, factor_b), factor_residual = factor_equivalent_kernel(
        equivalent_kernel,
        args.restarts,
        args.factor_goal,
    )
    print(f"[+] factorization residual: {factor_residual:.3e}")

    candidates = [
        ("ab", factor_a, factor_b),
        ("ba", factor_b, factor_a),
    ]

    chosen = None
    for name, kernel_first, kernel_second in candidates:
        scaled_first, scaled_second = rescale_for_bias(kernel_first, kernel_second, target_constant)
        model = build_candidate_model(base_linear_weight, base_linear_bias, scaled_first, scaled_second)
        local_error = validate_candidate(model, affine_matrix, affine_bias, args.validate_trials)
        print(f"[+] candidate {name}: local max error {local_error:.6f}")
        status, text = submit_model(args.url.rstrip("/"), model, args.timeout)
        print(f"[+] candidate {name}: /flag returned {status}")
        print(text)
        if status != 400:
            chosen = (model, name, status, text)
            break

    if chosen is None:
        raise RuntimeError("no candidate passed the layer-wise similarity check")

    model, name, status, text = chosen
    serialize_model(model, args.output)
    print(f"[+] saved passing candidate {name} to {args.output}")

    if status == 200:
        print("[+] success: flag response received")
    else:
        print("[!] model passed the weight check, but the service still returned a non-200 response")
        print("[!] if the response mentions a decode error, fix the server-side flag file encoding first")


if __name__ == "__main__":
    main()

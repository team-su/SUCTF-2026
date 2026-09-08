#!/usr/bin/env python3
import ast
import re
import sys
import time
from hashlib import md5

import numpy as np
from sage.all import Integer, ZZ, matrix, power_mod


BITS = 256
HALF = 128
MASK_BITS = (1 << BITS) - 1
MASK_HALF = (1 << HALF) - 1

# chal.py uses: ror(z, seed >> (bits - 250), bits) => low 8 bits of (seed >> 6)
ROT_BITS = 8
ROT_SRC_OFFSET = 6
ROT_STATE_BITS = ROT_SRC_OFFSET + ROT_BITS  # 14

# Lattice / lifting parameters for low-half recovery.
FAST_CHUNK = 8
FAST_LIMIT = 64
SLOW_CHUNK = 4
WEIGHT_LEN = 40
MIN_OUTPUTS = WEIGHT_LEN + 16
PROBE_STAGES = (8, 16, 24)
PROBE_CAND_CAP = 512

SMALLN_MAX_N = 64
SMALLN_CHUNK = 4
SMALLN_VECTOR_COUNT = 4
SMALLN_PREFILTER_T = 16


def trailing_zeros(x: int) -> int:
    return (x & -x).bit_length() - 1


def ror(x: int, k: int, n: int = BITS) -> int:
    k %= n
    return ((x >> k) | (x << (n - k))) & ((1 << n) - 1)


def rol(x: int, k: int, n: int = BITS) -> int:
    k %= n
    return ((x << k) | (x >> (n - k))) & ((1 << n) - 1)


def parse_challenge_lines(lines):
    data = [line.strip() for line in lines if line.strip()]
    if len(data) < 3:
        raise ValueError("Need at least 3 non-empty lines.")

    a = None
    outputs = None
    target_hash = None

    re_a = re.compile(r"^a\s*=\s*(.+)$")
    re_out = re.compile(r"^out\s*=\s*(.+)$")
    re_h = re.compile(r"^h\s*=\s*(.+)$")

    for line in data:
        if a is None:
            m = re_a.match(line)
            if m is not None:
                a = int(m.group(1).strip())
                continue

        if outputs is None:
            m = re_out.match(line)
            if m is not None:
                outputs = ast.literal_eval(m.group(1).strip())
                continue

        if target_hash is None:
            m = re_h.match(line)
            if m is not None:
                raw = m.group(1).strip()
                if (raw.startswith("'") and raw.endswith("'")) or (raw.startswith('"') and raw.endswith('"')):
                    target_hash = ast.literal_eval(raw)
                else:
                    target_hash = raw
                continue

    if a is None or outputs is None or target_hash is None:
        raise ValueError("Interactive header parse failed; expected lines: a=..., out=..., h=...")

    if not isinstance(outputs, list) or not all(isinstance(x, int) for x in outputs):
        raise TypeError("Second line must be Python list[int].")
    if not isinstance(target_hash, str):
        raise TypeError("Third line must be string literal, e.g. 'abcdef...'.")
    if len(outputs) < MIN_OUTPUTS:
        raise ValueError(f"Need at least {MIN_OUTPUTS} outputs for this solver, got {len(outputs)}.")

    return a, outputs, target_hash


def stages_list():
    return list(range(FAST_CHUNK, FAST_LIMIT + 1, FAST_CHUNK)) + list(
        range(FAST_LIMIT + SLOW_CHUNK, HALF + 1, SLOW_CHUNK)
    )


def find_weight_vectors(a: int, t_bits: int, m: int, count: int = 1):
    mod = Integer(1) << (HALF + t_bits)
    coeffs = [power_mod(Integer(a), i, mod) for i in range(m)]
    ker = matrix(ZZ, 1, m + 1, coeffs + [-mod]).right_kernel_matrix().LLL()

    rows = []
    seen = set()
    for row in ker.rows():
        w = [int(x) for x in row[:m]]
        if not any(w):
            continue
        if sum(Integer(w[i]) * coeffs[i] for i in range(m)) % mod != 0:
            continue
        key = (max(abs(x) for x in w), sum(abs(x) for x in w))
        tup = tuple(w)
        neg = tuple(-x for x in w)
        if tup in seen or neg in seen:
            continue
        seen.add(tup)
        rows.append((key[0], key[1], w))

    if not rows:
        raise RuntimeError(f"Failed to find weight vector for t={t_bits}")
    rows.sort(key=lambda x: (x[0], x[1]))
    out = [(w, l1) for _, l1, w in rows[:count]]
    return out


def find_weight_vector(a: int, t_bits: int, m: int):
    mod = Integer(1) << (HALF + t_bits)
    coeffs = [power_mod(Integer(a), i, mod) for i in range(m)]
    ker = matrix(ZZ, 1, m + 1, coeffs + [-mod]).right_kernel_matrix().LLL()

    best = None
    for row in ker.rows():
        w = [int(x) for x in row[:m]]
        if not any(w):
            continue
        if sum(Integer(w[i]) * coeffs[i] for i in range(m)) % mod != 0:
            continue
        key = (max(abs(x) for x in w), sum(abs(x) for x in w))
        if best is None or key < best[:2]:
            best = (key[0], key[1], w)

    if best is None:
        raise RuntimeError(f"Failed to find weight vector for t={t_bits}")
    _, l1, w = best
    return w, l1


def precompute_weights(a: int, m: int, verbose: bool = False):
    weights = {}
    for t in stages_list():
        t0 = time.time()
        w, l1 = find_weight_vector(a, t, m)
        weights[t] = (w, l1)
        if verbose:
            print(f"[weight] t={t:3d} max|w|={max(abs(x) for x in w):3d} l1={l1:4d} time={time.time()-t0:.3f}s")
    return weights


def precompute_weight_packs(
    a: int,
    m: int,
    t_values: list[int],
    count: int,
    verbose: bool = False,
):
    packs = {}
    for t in t_values:
        t0 = time.time()
        vecs = find_weight_vectors(a, t, m, count=count)
        packs[t] = vecs
        if verbose:
            l1s = [l1 for _, l1 in vecs]
            print(f"[weight-pack] t={t:3d} vecs={len(vecs)} l1={l1s} time={time.time()-t0:.3f}s")
    return packs


def extend_fast(
    outputs: list[int],
    a: int,
    m: int,
    base_x: int,
    base_b: int,
    prev_bits: int,
    t_bits: int,
    w: list[int],
    l1: int,
    addx: np.ndarray,
    addb: np.ndarray,
):
    n = len(outputs)
    mask = (1 << t_bits) - 1
    mask_u = np.uint64(mask)
    shift_u = np.uint64(prev_bits)

    x0s = np.uint64(base_x) | (addx << shift_u)
    b0s = np.uint64(base_b) | (addb << shift_u)
    a_u = np.uint64(a & mask)
    outm = np.array([o & mask for o in outputs], dtype=np.uint64)

    x = x0s.copy()
    u = np.empty((n, x0s.shape[0]), dtype=np.uint64)
    for i in range(n):
        u[i] = outm[i] ^ x
        x = (x * a_u + b0s) & mask_u

    ok = np.ones(x0s.shape[0], dtype=bool)
    l1_u = np.uint64(l1)
    for start in range(n - m):
        acc = np.zeros(x0s.shape[0], dtype=np.uint64)
        for j, wi in enumerate(w):
            d = (u[start + j + 1] - u[start + j]) & mask_u
            acc = (acc + d * np.uint64(wi & mask)) & mask_u
        dist = np.minimum(acc, (-acc) & mask_u)
        ok &= dist < l1_u
        if not ok.any():
            break

    idx = np.flatnonzero(ok)
    out = []
    shift = prev_bits
    for k in idx:
        x0 = int(base_x | (int(addx[k]) << shift))
        b0 = int(base_b | (int(addb[k]) << shift))
        out.append((x0, b0))
    return out


def extend_fast_multi(
    outputs: list[int],
    a: int,
    m: int,
    base_x: int,
    base_b: int,
    prev_bits: int,
    t_bits: int,
    weight_pack: list[tuple[list[int], int]],
    addx: np.ndarray,
    addb: np.ndarray,
):
    n = len(outputs)
    mask = (1 << t_bits) - 1
    mask_u = np.uint64(mask)
    shift_u = np.uint64(prev_bits)

    x0s = np.uint64(base_x) | (addx << shift_u)
    b0s = np.uint64(base_b) | (addb << shift_u)
    a_u = np.uint64(a & mask)
    outm = np.array([o & mask for o in outputs], dtype=np.uint64)

    x = x0s.copy()
    u = np.empty((n, x0s.shape[0]), dtype=np.uint64)
    for i in range(n):
        u[i] = outm[i] ^ x
        x = (x * a_u + b0s) & mask_u

    ok = np.ones(x0s.shape[0], dtype=bool)
    for w, l1 in weight_pack:
        l1_u = np.uint64(l1)
        for start in range(n - m):
            acc = np.zeros(x0s.shape[0], dtype=np.uint64)
            for j, wi in enumerate(w):
                d = (u[start + j + 1] - u[start + j]) & mask_u
                acc = (acc + d * np.uint64(wi & mask)) & mask_u
            dist = np.minimum(acc, (-acc) & mask_u)
            ok &= dist < l1_u
            if not ok.any():
                break
        if not ok.any():
            break

    idx = np.flatnonzero(ok)
    out = []
    shift = prev_bits
    for k in idx:
        x0 = int(base_x | (int(addx[k]) << shift))
        b0 = int(base_b | (int(addb[k]) << shift))
        out.append((x0, b0))
    return out


def pass_stage_big(outputs: list[int], a: int, m: int, x0: int, b0: int, t_bits: int, w: list[int], l1: int):
    n = len(outputs)
    mask = (1 << t_bits) - 1
    mod = 1 << t_bits
    half = 1 << (t_bits - 1)

    x = x0
    u = [0] * n
    for i in range(n):
        u[i] = (outputs[i] & mask) ^ x
        x = (x * a + b0) & mask

    d = [u[i + 1] - u[i] for i in range(n - 1)]

    quick_windows = (0, 1, 2, 3, 4, 8, 16, 24, 32)
    for s in quick_windows:
        if s >= n - m:
            break
        acc = 0
        for j, wi in enumerate(w):
            acc += wi * d[s + j]
        r = acc & mask
        dist = r if r <= half else mod - r
        if dist >= l1:
            return False

    for s in range(n - m):
        acc = 0
        for j, wi in enumerate(w):
            acc += wi * d[s + j]
        r = acc & mask
        dist = r if r <= half else mod - r
        if dist >= l1:
            return False

    return True


def pass_stage_big_multi(
    outputs: list[int],
    a: int,
    m: int,
    x0: int,
    b0: int,
    t_bits: int,
    weight_pack: list[tuple[list[int], int]],
):
    n = len(outputs)
    mask = (1 << t_bits) - 1
    mod = 1 << t_bits
    half = 1 << (t_bits - 1)

    x = x0
    u = [0] * n
    for i in range(n):
        u[i] = (outputs[i] & mask) ^ x
        x = (x * a + b0) & mask

    d = [u[i + 1] - u[i] for i in range(n - 1)]
    quick_windows = (0, 1, 2, 3, 4, 8, 16, 24, 32)

    for w, l1 in weight_pack:
        for s in quick_windows:
            if s >= n - m:
                break
            acc = 0
            for j, wi in enumerate(w):
                acc += wi * d[s + j]
            r = acc & mask
            dist = r if r <= half else mod - r
            if dist >= l1:
                return False

        for s in range(n - m):
            acc = 0
            for j, wi in enumerate(w):
                acc += wi * d[s + j]
            r = acc & mask
            dist = r if r <= half else mod - r
            if dist >= l1:
                return False

    return True


def extend_slow(
    outputs: list[int],
    a: int,
    m: int,
    base_x: int,
    base_b: int,
    prev_bits: int,
    t_bits: int,
    w: list[int],
    l1: int,
):
    shift = prev_bits
    out = []
    for ax in range(1 << SLOW_CHUNK):
        x = base_x | (ax << shift)
        for ab in range(1 << SLOW_CHUNK):
            b = base_b | (ab << shift)
            if pass_stage_big(outputs, a, m, x, b, t_bits, w, l1):
                out.append((x, b))
    return out


def extend_slow_multi(
    outputs: list[int],
    a: int,
    m: int,
    base_x: int,
    base_b: int,
    prev_bits: int,
    t_bits: int,
    weight_pack: list[tuple[list[int], int]],
):
    shift = prev_bits
    out = []
    for ax in range(1 << SLOW_CHUNK):
        x = base_x | (ax << shift)
        for ab in range(1 << SLOW_CHUNK):
            b = base_b | (ab << shift)
            if pass_stage_big_multi(outputs, a, m, x, b, t_bits, weight_pack):
                out.append((x, b))
    return out


def recover_low_half_candidates(outputs: list[int], a: int, m: int, weights=None, verbose: bool = False):
    vals = np.arange(1 << FAST_CHUNK, dtype=np.uint64)
    addx = np.repeat(vals, 1 << FAST_CHUNK)
    addb = np.tile(vals, 1 << FAST_CHUNK)

    if weights is None:
        weights = precompute_weights(a, m, verbose=verbose)

    cands = [(0, 0)]

    for t in range(FAST_CHUNK, FAST_LIMIT + 1, FAST_CHUNK):
        prev = t - FAST_CHUNK
        w, l1 = weights[t]
        t0 = time.time()
        nxt = []
        for bx, bb in cands:
            nxt.extend(extend_fast(outputs, a, m, bx, bb, prev, t, w, l1, addx, addb))
        cands = list(dict.fromkeys(nxt))
        if verbose:
            print(f"[stage-fast] t={t:3d} candidates={len(cands):4d} time={time.time()-t0:.3f}s")

    for t in range(FAST_LIMIT + SLOW_CHUNK, HALF + 1, SLOW_CHUNK):
        prev = t - SLOW_CHUNK
        w, l1 = weights[t]
        t0 = time.time()
        nxt = []
        for bx, bb in cands:
            nxt.extend(extend_slow(outputs, a, m, bx, bb, prev, t, w, l1))
        cands = list(dict.fromkeys(nxt))
        if verbose:
            print(f"[stage-slow] t={t:3d} candidates={len(cands):4d} time={time.time()-t0:.3f}s")

    return cands


def recover_low_half_candidates_smalln(
    outputs: list[int],
    a: int,
    m: int,
    weight_packs: dict[int, list[tuple[list[int], int]]],
    start_t: int = 0,
    start_cands=None,
    stop_t: int = HALF,
    verbose: bool = False,
):
    if start_cands is None:
        cands = [(0, 0)]
    else:
        cands = list(start_cands)

    vals = np.arange(1 << SMALLN_CHUNK, dtype=np.uint64)
    addx = np.repeat(vals, 1 << SMALLN_CHUNK)
    addb = np.tile(vals, 1 << SMALLN_CHUNK)

    # Vectorized part while t <= 64.
    for t in range(max(start_t + SMALLN_CHUNK, SMALLN_CHUNK), min(stop_t, 64) + 1, SMALLN_CHUNK):
        t0 = time.time()
        nxt = []
        pack = weight_packs[t]
        for bx, bb in cands:
            nxt.extend(extend_fast_multi(outputs, a, m, bx, bb, t - SMALLN_CHUNK, t, pack, addx, addb))
        cands = list(dict.fromkeys(nxt))
        if verbose:
            print(f"[stage-small-fast] t={t:3d} candidates={len(cands):4d} time={time.time()-t0:.3f}s")
        if not cands:
            return []

    if stop_t <= 64:
        return cands

    # Python big-int part for t > 64.
    begin = max(start_t + SMALLN_CHUNK, 68)
    for t in range(begin, stop_t + 1, SMALLN_CHUNK):
        t0 = time.time()
        nxt = []
        pack = weight_packs[t]
        for bx, bb in cands:
            nxt.extend(extend_slow_multi(outputs, a, m, bx, bb, t - SMALLN_CHUNK, t, pack))
        cands = list(dict.fromkeys(nxt))
        if verbose:
            print(f"[stage-small-slow] t={t:3d} candidates={len(cands):4d} time={time.time()-t0:.3f}s")
        if not cands:
            return []

    return cands


def probe_low_half_candidates(
    outputs: list[int],
    a: int,
    m: int,
    weights: dict[int, tuple[list[int], int]],
    probe_stages=PROBE_STAGES,
    cap: int = PROBE_CAND_CAP,
):
    vals = np.arange(1 << FAST_CHUNK, dtype=np.uint64)
    addx = np.repeat(vals, 1 << FAST_CHUNK)
    addb = np.tile(vals, 1 << FAST_CHUNK)

    cands = [(0, 0)]
    for t in probe_stages:
        prev = t - FAST_CHUNK
        w, l1 = weights[t]
        nxt = []
        for bx, bb in cands:
            nxt.extend(extend_fast(outputs, a, m, bx, bb, prev, t, w, l1, addx, addb))
            if len(nxt) > cap:
                return len(nxt), False
        cands = list(dict.fromkeys(nxt))
        if len(cands) > cap:
            return len(cands), False
    return len(cands), True


def solve_linear_preimages(target: int, a: int, b: int, bits: int):
    mod = 1 << bits
    rhs = (target - b) & (mod - 1)
    v2 = trailing_zeros(a)
    g = 1 << v2
    if rhs % g != 0:
        return []

    a1 = a >> v2
    rhs1 = rhs >> v2
    mod1 = 1 << (bits - v2)
    inv = pow(a1, -1, mod1)
    x0 = (inv * rhs1) % mod1
    return [x0 + k * mod1 for k in range(g)]


def output_from_state(state: int):
    z = ((state >> HALF) ^ (state & MASK_HALF)) & MASK_HALF
    r = (state >> ROT_SRC_OFFSET) & ((1 << ROT_BITS) - 1)
    return ror(z, r, BITS)


def rotation_candidates(y: int):
    cands = []
    for r in range(1 << ROT_BITS):
        if rol(y, r, BITS) >> HALF == 0:
            cands.append(r)
    return cands


def pick_anchor_index(rot_sets: list[set[int]], a: int):
    if a & 1:
        best = 0
        best_key = (len(rot_sets[0]) * len(rot_sets[1]), len(rot_sets[0]) + len(rot_sets[1]))
        for i in range(1, len(rot_sets) - 1):
            key = (len(rot_sets[i]) * len(rot_sets[i + 1]), len(rot_sets[i]) + len(rot_sets[i + 1]))
            if key < best_key:
                best_key = key
                best = i
        return best
    return 0


def recover_rotation_sequences(outputs: list[int], a: int, verbose: bool = False, max_sequences: int = 16):
    rot_sets = [set(rotation_candidates(y)) for y in outputs]
    if any(len(s) == 0 for s in rot_sets):
        raise RuntimeError("Some output has no feasible rotation candidate.")
    if len(rot_sets) < 2:
        raise RuntimeError("Need at least 2 outputs.")

    anchor = pick_anchor_index(rot_sets, a)

    mod = 1 << ROT_STATE_BITS
    mask = mod - 1
    a_low = a & mask
    inv_a_low = pow(a_low, -1, mod) if (a_low & 1) else None

    if verbose:
        lens = [len(s) for s in rot_sets]
        print(f"[rot] candidate sizes min={min(lens)} max={max(lens)} avg={sum(lens)/len(lens):.3f}")
        print(f"[rot] anchor={anchor}, pair sizes=({len(rot_sets[anchor])}, {len(rot_sets[anchor+1])})")

    checked = 0
    feasible = 0
    uniq = {}
    low_tail_range = range(1 << ROT_SRC_OFFSET)

    for ra in rot_sets[anchor]:
        base_a = ra << ROT_SRC_OFFSET
        for loa in low_tail_range:
            x_anchor = base_a | loa
            ax = (a_low * x_anchor) & mask
            for rb in rot_sets[anchor + 1]:
                base_b = rb << ROT_SRC_OFFSET
                for lob in low_tail_range:
                    x_anchor_next = base_b | lob
                    b_low = (x_anchor_next - ax) & mask
                    checked += 1

                    ok = True

                    x = x_anchor_next
                    for i in range(anchor + 1, len(outputs)):
                        if ((x >> ROT_SRC_OFFSET) & 0xFF) not in rot_sets[i]:
                            ok = False
                            break
                        if i + 1 < len(outputs):
                            x = (a_low * x + b_low) & mask
                    if not ok:
                        continue

                    x = x_anchor
                    if ((x >> ROT_SRC_OFFSET) & 0xFF) not in rot_sets[anchor]:
                        continue
                    if anchor > 0:
                        if inv_a_low is None:
                            continue
                        for i in range(anchor, 0, -1):
                            x = (inv_a_low * ((x - b_low) & mask)) & mask
                            if ((x >> ROT_SRC_OFFSET) & 0xFF) not in rot_sets[i - 1]:
                                ok = False
                                break
                        if not ok:
                            continue
                    x0_low = x

                    feasible += 1
                    r_seq = [0] * len(outputs)
                    x = x0_low
                    for i in range(len(outputs)):
                        r_seq[i] = (x >> ROT_SRC_OFFSET) & 0xFF
                        x = (a_low * x + b_low) & mask
                    key = bytes(r_seq)
                    if key not in uniq:
                        uniq[key] = (x0_low, b_low, r_seq)
                        if len(uniq) >= max_sequences:
                            break
                if len(uniq) >= max_sequences:
                    break
            if len(uniq) >= max_sequences:
                break
        if len(uniq) >= max_sequences:
            break

    if verbose:
        print(f"[rot] checked={checked}, feasible low14 pairs={feasible}, unique rotation sequences={len(uniq)}")

    if not uniq:
        raise RuntimeError("Failed to recover any rotation sequence.")
    return list(uniq.values())


def unrotate_outputs(outputs: list[int], r_seq: list[int]):
    if len(outputs) != len(r_seq):
        raise ValueError("Length mismatch between outputs and rotation sequence.")
    z = []
    for y, r in zip(outputs, r_seq):
        v = rol(y, r, BITS)
        if v >> HALF != 0:
            return None
        z.append(v & MASK_HALF)
    return z


def enumerate_low14_pairs_for_rseq(r_seq: list[int], a: int):
    mod = 1 << ROT_STATE_BITS
    mask = mod - 1
    a_low = a & mask

    pairs = []
    for lo0 in range(1 << ROT_SRC_OFFSET):
        x0 = (r_seq[0] << ROT_SRC_OFFSET) | lo0
        ax = (a_low * x0) & mask
        for lo1 in range(1 << ROT_SRC_OFFSET):
            x1 = (r_seq[1] << ROT_SRC_OFFSET) | lo1
            b = (x1 - ax) & mask

            x = x0
            ok = True
            for rr in r_seq:
                if ((x >> ROT_SRC_OFFSET) & 0xFF) != rr:
                    ok = False
                    break
                x = (a_low * x + b) & mask
            if ok:
                pairs.append((x0, b))

    return list(dict.fromkeys(pairs))


def recover_state_and_b_from_unrotated_outputs(z_outputs: list[int], a: int, weights=None, verbose: bool = False):
    low_half_cands = recover_low_half_candidates(z_outputs, a, WEIGHT_LEN, weights=weights, verbose=verbose)

    valid = []
    for low1, b_low in low_half_cands:
        s1 = ((z_outputs[0] ^ low1) << HALF) | low1
        low2 = (a * low1 + b_low) & MASK_HALF
        s2 = ((z_outputs[1] ^ low2) << HALF) | low2
        b_full = (s2 - a * s1) & MASK_BITS

        st = s1
        ok = True
        for z in z_outputs:
            if (((st >> HALF) ^ (st & MASK_HALF)) & MASK_HALF) != z:
                ok = False
                break
            st = (a * st + b_full) & MASK_BITS
        if ok:
            valid.append((s1, b_full))

    return low_half_cands, list(dict.fromkeys(valid))


def recover_state_and_b_from_unrotated_outputs_smalln(
    z_outputs: list[int],
    a: int,
    weight_packs: dict[int, list[tuple[list[int], int]],
    ],
    start_t: int = 0,
    start_cands=None,
    verbose: bool = False,
):
    low_half_cands = recover_low_half_candidates_smalln(
        z_outputs,
        a,
        WEIGHT_LEN,
        weight_packs=weight_packs,
        start_t=start_t,
        start_cands=start_cands,
        stop_t=HALF,
        verbose=verbose,
    )

    valid = []
    for low1, b_low in low_half_cands:
        s1 = ((z_outputs[0] ^ low1) << HALF) | low1
        low2 = (a * low1 + b_low) & MASK_HALF
        s2 = ((z_outputs[1] ^ low2) << HALF) | low2
        b_full = (s2 - a * s1) & MASK_BITS

        st = s1
        ok = True
        for z in z_outputs:
            if (((st >> HALF) ^ (st & MASK_HALF)) & MASK_HALF) != z:
                ok = False
                break
            st = (a * st + b_full) & MASK_BITS
        if ok:
            valid.append((s1, b_full))

    return low_half_cands, list(dict.fromkeys(valid))


def recover_seed_candidates(state1: int, b_full: int, a: int):
    return solve_linear_preimages(state1, a, b_full, BITS)


def seed_matches_hash(seed: int, target_hash: str):
    return md5(str(seed).encode()).hexdigest() == target_hash


def run_attack_smalln(a: int, outputs: list[int], target_hash: str, verbose: bool = False):
    rot_infos = recover_rotation_sequences(outputs, a, verbose=verbose, max_sequences=64)
    t_values = list(range(SMALLN_CHUNK, HALF + 1, SMALLN_CHUNK))
    weight_packs = precompute_weight_packs(
        a,
        WEIGHT_LEN,
        t_values=t_values,
        count=SMALLN_VECTOR_COUNT,
        verbose=verbose,
    )

    seq_infos = []
    w16, l16 = weight_packs[16][0]
    for idx, (x0_low14, b_low14, r_seq) in enumerate(rot_infos, 1):
        z_outputs = unrotate_outputs(outputs, r_seq)
        if z_outputs is None:
            if verbose:
                print(f"[seq {idx}] invalid after unrotate, skip")
            continue

        t0 = time.time()
        low14_pairs = enumerate_low14_pairs_for_rseq(r_seq, a)
        start16 = []
        for xx, bb in low14_pairs:
            for ax in range(4):
                x16 = xx | (ax << 14)
                for ab in range(4):
                    b16 = bb | (ab << 14)
                    if pass_stage_big(z_outputs, a, WEIGHT_LEN, x16, b16, 16, w16, l16):
                        start16.append((x16, b16))
        start16 = list(dict.fromkeys(start16))
        dt = time.time() - t0

        if verbose:
            print(
                f"[seq {idx}] low14 pairs={len(low14_pairs)} seed@t=16 candidates={len(start16)} time={dt:.3f}s"
            )
        if start16:
            seq_infos.append((len(start16), len(low14_pairs), x0_low14, b_low14, r_seq, z_outputs, start16))

    if not seq_infos:
        return []

    seq_infos.sort(key=lambda x: x[0])
    solutions = []

    for idx, (pref_cnt, pair_cnt, x0_low14, b_low14, r_seq, z_outputs, start16) in enumerate(seq_infos, 1):
        if verbose:
            print(
                f"[seq {idx}] x0_low14={x0_low14}, b_low14={b_low14}, "
                f"pairs={pair_cnt}, pref={pref_cnt}, first r={r_seq[:10]}"
            )

        low_half_cands, state_b_cands = recover_state_and_b_from_unrotated_outputs_smalln(
            z_outputs,
            a,
            weight_packs=weight_packs,
            start_t=SMALLN_PREFILTER_T,
            start_cands=start16,
            verbose=verbose,
        )
        if verbose:
            print(f"[seq {idx}] low-half candidates={len(low_half_cands)}, state/b candidates={len(state_b_cands)}")

        for state1, b_full in state_b_cands:
            st = state1
            ok = True
            for y in outputs:
                if output_from_state(st) != y:
                    ok = False
                    break
                st = (a * st + b_full) & MASK_BITS
            if not ok:
                continue

            for seed in recover_seed_candidates(state1, b_full, a):
                if seed_matches_hash(seed, target_hash):
                    solutions.append((seed, b_full, bytes(r_seq)))

        # Small-n mode aims at speed: stop after first valid decryption batch.
        if solutions:
            break

    return list(dict.fromkeys(solutions))


def run_attack_large(a: int, outputs: list[int], target_hash: str, verbose: bool = False):
    rot_infos = recover_rotation_sequences(outputs, a, verbose=verbose, max_sequences=32)
    weights = precompute_weights(a, WEIGHT_LEN, verbose=verbose)

    scored = []
    for idx, (x0_low14, b_low14, r_seq) in enumerate(rot_infos, 1):
        z_probe = unrotate_outputs(outputs, r_seq)
        if z_probe is None:
            if verbose:
                print(f"[seq {idx}] invalid after unrotate, skip")
            continue
        probe_cnt, probe_ok = probe_low_half_candidates(z_probe, a, WEIGHT_LEN, weights)
        scored.append((probe_cnt, probe_ok, x0_low14, b_low14, r_seq, z_probe))
        if verbose:
            print(f"[seq {idx}] probe candidates={probe_cnt}, pass={probe_ok}")

    if not scored:
        return []

    # Small cheap discriminator using recovered (x0, b) mod 2^14:
    # lift 2 bits each and keep only sequences with feasible t=16 seeds.
    w16, l16 = weights[16]
    refined = []
    for probe_cnt, probe_ok, x0_low14, b_low14, r_seq, z_outputs in scored:
        seed16_cnt = 0
        for ax in range(4):
            x16 = x0_low14 | (ax << 14)
            for ab in range(4):
                b16 = b_low14 | (ab << 14)
                if pass_stage_big(z_outputs, a, WEIGHT_LEN, x16, b16, 16, w16, l16):
                    seed16_cnt += 1
        refined.append((seed16_cnt, probe_cnt, probe_ok, x0_low14, b_low14, r_seq, z_outputs))
        if verbose:
            print(f"[seq-seed16] x0_low14={x0_low14} b_low14={b_low14} count={seed16_cnt}")

    if any(item[0] > 0 for item in refined):
        refined = [item for item in refined if item[0] > 0]

    # Best-first: process sequences with smallest probe candidate count first.
    refined.sort(key=lambda x: (not x[2], x[1], -x[0]))

    # If at least one sequence passes the cap, only keep passing ones.
    if any(item[2] for item in refined):
        refined = [item for item in refined if item[2]]

    solutions = []
    for idx, (seed16_cnt, probe_cnt, probe_ok, x0_low14, b_low14, r_seq, z_outputs) in enumerate(refined, 1):
        if verbose:
            print(
                f"[seq {idx}] x0_low14={x0_low14}, b_low14={b_low14}, "
                f"seed16={seed16_cnt}, probe={probe_cnt}/{probe_ok}, first r={r_seq[:10]}"
            )

        low_half_cands, state_b_cands = recover_state_and_b_from_unrotated_outputs(
            z_outputs, a, weights=weights, verbose=verbose
        )
        if verbose:
            print(f"[seq {idx}] low-half candidates={len(low_half_cands)}, state/b candidates={len(state_b_cands)}")

        for state1, b_full in state_b_cands:
            st = state1
            ok = True
            for y in outputs:
                if output_from_state(st) != y:
                    ok = False
                    break
                st = (a * st + b_full) & MASK_BITS
            if not ok:
                continue

            for seed in recover_seed_candidates(state1, b_full, a):
                if seed_matches_hash(seed, target_hash):
                    solutions.append((seed, b_full, bytes(r_seq)))
        if solutions:
            break

    return list(dict.fromkeys(solutions))


def run_attack(a: int, outputs: list[int], target_hash: str, verbose: bool = False):
    if len(outputs) <= SMALLN_MAX_N:
        return run_attack_smalln(a, outputs, target_hash, verbose=verbose)
    return run_attack_large(a, outputs, target_hash, verbose=verbose)


def solve_instance(a: int, outputs: list[int], target_hash: str, verbose: bool = False):
    t0 = time.time()
    sols = run_attack(a, outputs, target_hash, verbose=verbose)
    dt = time.time() - t0
    return sols, dt


def solve_pwn_io(io, verbose: bool = False, header_timeout: float = 5.0):
    captured = []
    a = outputs = target_hash = None
    start = time.time()

    while time.time() - start < header_timeout:
        line = io.recvline(timeout=0.5)
        if not line:
            continue
        txt = line.decode(errors="replace").rstrip("\r\n")
        if txt:
            captured.append(txt)
        try:
            a, outputs, target_hash = parse_challenge_lines(captured)
            break
        except Exception:
            continue

    if a is None or outputs is None or target_hash is None:
        raise RuntimeError("Failed to parse interactive challenge header.")

    sols, dt = solve_instance(a, outputs, target_hash, verbose=verbose)
    if not sols:
        raise RuntimeError("No solution found.")

    seed = sols[0][0]
    io.sendline(str(seed).encode())

    tail = io.recvall(timeout=2)
    tail_txt = tail.decode(errors="replace").rstrip("\r\n") if tail else ""

    transcript = "\n".join(captured)
    if tail_txt:
        transcript += ("\n" if transcript else "") + tail_txt
    return seed, dt, transcript


def main():
    import argparse

    parser = argparse.ArgumentParser(description="Non-SAT solver for interactive rotated XOR-half 256-bit LCG challenge.")
    parser.add_argument(
        "--process",
        metavar="CHAL_PATH",
        default="../env/chal.py",
        help="Use pwntools process() to run local interactive challenge script (default: ../env/chal.py).",
    )
    parser.add_argument(
        "--remote",
        nargs=2,
        metavar=("HOST", "PORT"),
        help="Use pwntools remote(host, port) to solve remote interactive challenge.",
    )
    parser.add_argument(
        "--python",
        default=sys.executable,
        help="Python interpreter used with --process (default: current interpreter).",
    )
    parser.add_argument("--verbose", action="store_true", help="Print stage timings and candidate counts")
    args = parser.parse_args()

    if args.remote is None:
        from pwn import context, process

        context.log_level = "info" if args.verbose else "error"
        io = process([args.python, "-u", args.process])
        try:
            seed, dt, transcript = solve_pwn_io(io, verbose=args.verbose)
        finally:
            try:
                io.close()
            except Exception:
                pass
        print(f"[+] solved seed: {seed}")
        print(f"[+] solve elapsed: {dt:.3f}s")
        print(transcript)
        return

    from pwn import context, remote

    host, port_s = args.remote
    port = int(port_s)
    context.log_level = "info" if args.verbose else "error"
    io = remote(host, port)
    try:
        seed, dt, transcript = solve_pwn_io(io, verbose=args.verbose, header_timeout=10.0)
    finally:
        try:
            io.close()
        except Exception:
            pass
    print(f"[+] solved seed: {seed}")
    print(f"[+] solve elapsed: {dt:.3f}s")
    print(transcript)


if __name__ == "__main__":
    main()

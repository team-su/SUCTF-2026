from pwn import *
import os, json, subprocess, time
from Crypto.Util.Padding import unpad
from itertools import permutations
from recover_seed import recover_seed

# ================= AES helpers (match AES.py) =================

Rcon = [
    0x00, 0x01, 0x02, 0x04, 0x08, 0x10, 0x20, 0x40,
    0x80, 0x1B, 0x36, 0x6C, 0xD8, 0xAB, 0x4D, 0x9A,
    0x2F, 0x5E, 0xBC, 0x63, 0xC6, 0x97, 0x35, 0x6A,
    0xD4, 0xB3, 0x7D, 0xFA, 0xEF, 0xC5, 0x91, 0x39,
]

def xtime(a: int) -> int:
    return (((a << 1) ^ 0x1B) & 0xFF) if (a & 0x80) else ((a << 1) & 0xFF)

def gf_mul(a: int, b: int) -> int:
    res = 0
    for _ in range(8):
        if b & 1:
            res ^= a
        hi = a & 0x80
        a = (a << 1) & 0xFF
        if hi:
            a ^= 0x1B
        b >>= 1
    return res & 0xFF

def text2matrix_from_bytes(b: bytes):
    assert len(b) == 16
    m = [[] for _ in range(4)]
    for i in range(16):
        m[i % 4].append(b[i])
    return m

def matrix2bytes(m):
    out = bytearray(16)
    idx = 0
    for col in range(4):
        for row in range(4):
            out[idx] = m[row][col] & 0xFF
            idx += 1
    return bytes(out)

def add_round_key(s, k_words4):
    for i in range(4):
        for j in range(4):
            s[i][j] ^= k_words4[i][j]

def sub_bytes(s, Sbox):
    for i in range(4):
        for j in range(4):
            s[i][j] = Sbox[s[i][j]]

def shift_rows(s):
    s[0][1], s[1][1], s[2][1], s[3][1] = s[1][1], s[2][1], s[3][1], s[0][1]
    s[0][2], s[1][2], s[2][2], s[3][2] = s[2][2], s[3][2], s[0][2], s[1][2]
    s[0][3], s[1][3], s[2][3], s[3][3] = s[3][3], s[0][3], s[1][3], s[2][3]

def mix_columns(s):
    for i in range(4):
        t = s[i][0] ^ s[i][1] ^ s[i][2] ^ s[i][3]
        u = s[i][0]
        s[i][0] ^= t ^ xtime(s[i][0] ^ s[i][1])
        s[i][1] ^= t ^ xtime(s[i][1] ^ s[i][2])
        s[i][2] ^= t ^ xtime(s[i][2] ^ s[i][3])
        s[i][3] ^= t ^ xtime(s[i][3] ^ u)

def inv_shift_rows(s):
    s[0][1], s[1][1], s[2][1], s[3][1] = s[3][1], s[0][1], s[1][1], s[2][1]
    s[0][2], s[1][2], s[2][2], s[3][2] = s[2][2], s[3][2], s[0][2], s[1][2]
    s[0][3], s[1][3], s[2][3], s[3][3] = s[1][3], s[2][3], s[3][3], s[0][3]

def inv_sub_bytes(s, invS):
    for i in range(4):
        for j in range(4):
            s[i][j] = invS[s[i][j]]

def inv_mix_columns(s):
    for i in range(4):
        a0,a1,a2,a3 = s[i]
        s[i][0] = gf_mul(a0,14) ^ gf_mul(a1,11) ^ gf_mul(a2,13) ^ gf_mul(a3,9)
        s[i][1] = gf_mul(a0,9)  ^ gf_mul(a1,14) ^ gf_mul(a2,11) ^ gf_mul(a3,13)
        s[i][2] = gf_mul(a0,13) ^ gf_mul(a1,9)  ^ gf_mul(a2,14) ^ gf_mul(a3,11)
        s[i][3] = gf_mul(a0,11) ^ gf_mul(a1,13) ^ gf_mul(a2,9)  ^ gf_mul(a3,14)

def expand_round_keys(master_key_bytes: bytes, Sbox):
    rk = text2matrix_from_bytes(master_key_bytes)
    for i in range(4, 44):
        if i % 4 == 0:
            prev4 = rk[i-4]
            prev1 = rk[i-1]
            temp = [
                prev4[0] ^ Sbox[prev1[1]] ^ Rcon[i//4],
                prev4[1] ^ Sbox[prev1[2]],
                prev4[2] ^ Sbox[prev1[3]],
                prev4[3] ^ Sbox[prev1[0]],
            ]
        else:
            temp = [rk[i-4][j] ^ rk[i-1][j] for j in range(4)]
        rk.append([t & 0xFF for t in temp])
    return rk

def decrypt_block(ct16: bytes, master_key_bytes: bytes, Sbox):
    rk = expand_round_keys(master_key_bytes, Sbox)
    invS = [0]*256
    for i,v in enumerate(Sbox):
        invS[v] = i

    state = text2matrix_from_bytes(ct16)
    add_round_key(state, rk[40:])
    inv_shift_rows(state)
    inv_sub_bytes(state, invS)

    for r in range(9, 0, -1):
        add_round_key(state, rk[4*r:4*(r+1)])
        inv_mix_columns(state)
        inv_shift_rows(state)
        inv_sub_bytes(state, invS)

    add_round_key(state, rk[:4])
    return matrix2bytes(state)

def xor_bytes(a: bytes, b: bytes) -> bytes:
    return bytes(x^y for x,y in zip(a,b))

def invert_from_k10(k10: bytes, Sbox):
    w = [None]*44
    idx = 0
    for col in range(4):
        for row in range(4):
            if w[40+row] is None:
                w[40+row] = [0,0,0,0]
            w[40+row][col] = k10[idx]
            idx += 1

    for i in range(43, 3, -1):
        if i % 4 != 0:
            w[i-4] = [w[i][j] ^ w[i-1][j] for j in range(4)]
        else:
            rcon = Rcon[i//4]
            g = [
                Sbox[w[i-1][1]] ^ rcon,
                Sbox[w[i-1][2]],
                Sbox[w[i-1][3]],
                Sbox[w[i-1][0]],
            ]
            w[i-4] = [w[i][j] ^ g[j] for j in range(4)]

    m = w[0:4]
    b = bytearray(16)
    b[0]  = m[0][0]; b[1]  = m[1][0]; b[2]  = m[2][0]; b[3]  = m[3][0]
    b[4]  = m[0][1]; b[5]  = m[1][1]; b[6]  = m[2][1]; b[7]  = m[3][1]
    b[8]  = m[0][2]; b[9]  = m[1][2]; b[10] = m[2][2]; b[11] = m[3][2]
    b[12] = m[0][3]; b[13] = m[1][3]; b[14] = m[2][3]; b[15] = m[3][3]
    return bytes(b)

def decrypt_flag(flag_ct: bytes, master_key_bytes: bytes, Sbox):
    pt = b""
    for i in range(0, len(flag_ct), 16):
        pt += decrypt_block(flag_ct[i:i+16], master_key_bytes, Sbox)
    return unpad(pt, 16)

# ================= Oracle interaction =================

class ChalOracle:
    def __init__(self, io):
        self.io = io
        self.flag_ct = self._read_flag_ct()

    def _read_flag_ct(self) -> bytes:
        self.io.recvuntil(b"flag ciphertext (in hex): ")
        line = self.io.recvline().strip()
        return bytes.fromhex(line.decode())

    def reset(self):
        self.io.sendlineafter(b"[x] > ", b"3")
        self.io.recvuntil(b"[+] reset!")

    def change(self, seed=None, key=None):
        self.io.sendlineafter(b"[x] > ", b"1")
        self.io.sendlineafter(b"[x] your seed: ", b"0" if seed is None else str(hex(seed)[2:]).encode())
        self.io.sendlineafter(b"[x] your key: ",  b"0" if key  is None else str(hex(key)[2:]).encode())
        self.io.recvuntil(b"[+] changed!")

    def encrypt_msg(self, msg: bytes) -> bytes:
        self.io.sendlineafter(b"[x] > ", b"2")
        self.io.sendlineafter(b"[x] your message: ", msg.hex().encode())
        self.io.recvuntil(b"ciphertext (in hex): ")
        line = self.io.recvline().strip()
        return bytes.fromhex(line.decode())

# ================= Attack =================

KEY0_EQUIV = 1 << 128

MAX_MENU = 310
SWITCH = 215
EXTRA_SINGLE = 1
MSG_BASE_LEN = 4096
MSG_REPEATS = 1
HR_K = 0
HR_R = 8
HR_ATTEMPTS = 2
HR_SEC = 20.0

def prng_bytes(length: int, seed: int) -> bytes:
    x = seed & 0xFFFFFFFF
    out = bytearray(length)
    for i in range(length):
        x ^= (x << 13) & 0xFFFFFFFF
        x ^= (x >> 17) & 0xFFFFFFFF
        x ^= (x << 5) & 0xFFFFFFFF
        out[i] = x & 0xFF
    return bytes(out)

def build_messages() -> tuple[bytes, bytes]:
    if MSG_REPEATS < 1:
        raise RuntimeError("MSG_REPEATS must be >= 1")
    if MSG_BASE_LEN < 16:
        raise RuntimeError("MSG_BASE_LEN must be >= 16")
    msg1_base = prng_bytes(MSG_BASE_LEN, 0x13579BDF)
    msg2_base = prng_bytes(MSG_BASE_LEN, 0x2468ACE1)
    return msg1_base * MSG_REPEATS, msg2_base * MSG_REPEATS

def idx_seq_single(t: int) -> list[int]:
    return [t] * (t + 1) + list(range(t + 1, 256))

def idx_seq_pair(t: int) -> list[int]:
    return [t] * (t + 1) + [t + 1] + list(range(t + 2, 256))

def build_seed(seq: list[int]) -> int:
    s = recover_seed(seq)
    if s == 0:
        s = (1 << 64) + 1
    return s

def ensure_go_binary(go_src="hybrid_recover.go", go_bin="./hybrid_recover"):
    if os.path.exists(go_bin) and os.path.exists(go_src):
        if os.path.getmtime(go_bin) >= os.path.getmtime(go_src):
            return
    log.info("building go binary hybrid_recover ...")
    env = os.environ.copy()
    env["GO111MODULE"] = "off"
    subprocess.check_call(
        ["go", "build", "-trimpath", "-ldflags=-s -w", "-o", go_bin, go_src],
        env=env
    )

def run_hybrid_dfs(args_base, attempts: int, attempt_sec: float):
    last_err = ""
    for i in range(1, attempts + 1):
        seed = int(time.time_ns()) ^ (i * 0x5851F42D4C957F2D & ((1 << 63) - 1))
        cmd = args_base + ["-mode", "dfs", "-seed", str(seed), "-sec", str(attempt_sec)]
        log.info(f"hybrid_recover dfs attempt {i}/{attempts}: {' '.join(cmd[1:])}")
        try:
            proc = subprocess.run(
                cmd,
                stdout=subprocess.PIPE,
                stderr=subprocess.PIPE,
                text=True,
                timeout=max(2.0, attempt_sec + 2.0),
            )
        except subprocess.TimeoutExpired:
            last_err = f"timeout>{attempt_sec:.1f}s"
            log.warning(f"hybrid_recover dfs attempt {i} timeout")
            continue

        if proc.returncode == 0:
            out = proc.stdout.strip()
            if not out:
                last_err = "empty output"
                continue
            arr = json.loads(out)
            if isinstance(arr, list):
                return arr
            last_err = f"unexpected output type: {type(arr).__name__}"
            continue

        tail = (proc.stderr or proc.stdout).strip().splitlines()
        last_err = tail[-1] if tail else f"exit={proc.returncode}"
        log.warning(f"hybrid_recover dfs attempt {i} failed: {last_err}")

    raise RuntimeError(f"hybrid_recover dfs failed after {attempts} attempts; last_err={last_err}")


def dump_cts(path: str, switch: int, pair_cts: dict[int, bytes], single_cts: dict[int, bytes], msg1: bytes, msg2: bytes):
    payload = {
        "switch": switch,
        "pair": {str(k): v.hex() for k, v in pair_cts.items()},
        "pair2": {},
        "single": {str(k): v.hex() for k, v in single_cts.items()},
        "msg1_hex": msg1.hex(),
        "msg2_hex": msg2.hex(),
    }
    with open(path, "w") as f:
        json.dump(payload, f)


def main():
    if EXTRA_SINGLE < 0:
        raise RuntimeError("EXTRA_SINGLE must be >= 0")
    if SWITCH < 4 or SWITCH > 256:
        raise RuntimeError("SWITCH must be in [4, 256]")
    default_single_start = SWITCH + (1 if SWITCH % 2 == 1 else 0)
    single_start = default_single_start - EXTRA_SINGLE
    if single_start < 0:
        single_start = 0
    if single_start > 256:
        single_start = 256
    msg1, msg2 = build_messages()
    pair_cnt = len(range(2, SWITCH, 2))
    single_cnt = max(0, 256 - single_start)
    base_ops = 2 * (pair_cnt + single_cnt) + 3

    log.info(f"planned base menu ops = {base_ops}/{MAX_MENU}")
    log.info(f"msg_repeats = {MSG_REPEATS} (msg_len={len(msg1)})")
    if EXTRA_SINGLE:
        log.info(f"extra single depth = {EXTRA_SINGLE} (single_start={single_start})")
    if base_ops > MAX_MENU:
        raise RuntimeError("base plan exceeds menu limit; increase SWITCH or reduce work")

    io = process(["python", "../env/chal.py"])
    orc = ChalOracle(io)
    log.info(f"flag_ct = {orc.flag_ct.hex()}")

    # --------- online collect (按 t 递增执行，保证 tail 仍是原始) ---------
    pair_cts = {}
    single_cts = {}
    ops_used = 0

    pair_hi = SWITCH - 1 if SWITCH % 2 == 1 else SWITCH - 2
    log.info(f"collect pairwise cts for even t=2..{pair_hi} ...")
    for t in range(2, SWITCH, 2):
        seed = build_seed(idx_seq_pair(t))
        orc.change(seed=seed, key=KEY0_EQUIV)
        ops_used += 1
        pair_cts[t] = orc.encrypt_msg(msg1)
        ops_used += 1
        if t % 32 == 0:
            log.info(f"  pair collected up to t={t}")

    if single_start < 256:
        log.info(f"collect single cts for t={single_start}..255 ...")
        for t in range(single_start, 256):
            seed = build_seed(idx_seq_single(t))
            orc.change(seed=seed, key=KEY0_EQUIV)
            ops_used += 1
            single_cts[t] = orc.encrypt_msg(msg1)
            ops_used += 1
            if t % 16 == 0:
                log.info(f"  single collected up to t={t}")

    dump_cts("cts.json", SWITCH, pair_cts, single_cts, msg1, msg2)
    log.success("dumped base cts.json")

    # --------- go offline recover sbox[2..255] ---------
    ensure_go_binary("hybrid_recover.go", "./hybrid_recover")
    workers = os.cpu_count() or 4
    max_alt = HR_K
    restarts = HR_R
    default_rp = 1 if restarts <= 1 else min(4, max(1, workers), restarts)
    restart_parallel = default_rp
    attempts = HR_ATTEMPTS
    attempt_sec = HR_SEC
    args_base = [
        "./hybrid_recover", "-in", "cts.json",
        "-j", str(workers),
        "-k", str(max_alt),
        "-r", str(restarts),
        "-rp", str(restart_parallel),
    ]
    arr = run_hybrid_dfs(args_base, attempts=attempts, attempt_sec=attempt_sec)
    Sbox0_partial = [None if x == -1 else x for x in arr]

    total_ops = ops_used + 3
    log.info(f"menu ops used before final stage: {ops_used}/{MAX_MENU}")
    if total_ops > MAX_MENU:
        raise RuntimeError(f"menu ops would exceed limit: need {total_ops}, limit {MAX_MENU}")

    known = set(v for v in Sbox0_partial if v is not None)
    remain01 = sorted(set(range(256)) - known)
    if len(remain01) != 2:
        raise RuntimeError(f"expected 2 remaining values for Sbox0[0],Sbox0[1], got {len(remain01)}")
    log.info(f"remaining values for indices 0/1: {remain01} (order unknown)")

    # --------- reset -> extract K10_secret using p=2 constant collapse ---------
    orc.reset()
    seed_const2 = build_seed([2] * 256)
    orc.change(seed=seed_const2, key=None)
    ct_k10 = orc.encrypt_msg(b"A" * 15)

    v2 = Sbox0_partial[2]
    if v2 is None:
        raise RuntimeError("Sbox0[2] not recovered (unexpected)")
    k10_secret = xor_bytes(ct_k10, bytes([v2]) * 16)
    log.success(f"K10_secret = {k10_secret.hex()}")

    # --------- try both permutations for Sbox0[0],Sbox0[1] ---------
    for a0, a1 in permutations(remain01, 2):
        Sbox0 = Sbox0_partial[:]
        Sbox0[0] = a0
        Sbox0[1] = a1
        try:
            master_key = invert_from_k10(k10_secret, Sbox0)
            flag = decrypt_flag(orc.flag_ct, master_key, Sbox0)
            log.success(f"Sbox0[0]={a0}, Sbox0[1]={a1}")
            log.success(f"master_key = {master_key.hex()}")
            print(flag.decode(errors="replace"))
            return
        except Exception:
            continue

    raise RuntimeError("both permutations failed")

if __name__ == "__main__":
    main()

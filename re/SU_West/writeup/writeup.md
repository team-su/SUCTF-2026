# SU_West Writeup

这是一个auto re题目，设计成了81层，整体逻辑不算很复杂，每层都是单层可逆的。



进入主函数首先看到简单的反调试和主验证逻辑：

```c
nt __fastcall main(int argc, const char **argv, const char **envp)
{
  unsigned __int8 v6; // [rsp+37h] [rbp-2B1h] BYREF
  unsigned __int64 v7; // [rsp+38h] [rbp-2B0h] BYREF
  _QWORD v8[85]; // [rsp+40h] [rbp-2A8h] BYREF

  if ( (unsigned __int8)sub_140001100(0, 0, 0, 1595750129, 0x510E527FADE682D1LL, 1) )
  {
    puts("debugger detected");
  }
  else
  {
    memset(v8, 0, 0x288u);
    if ( (unsigned __int8)sub_1400012C0((unsigned int)argc, argv, v8) )
    {
      puts("all inputs collected, starting verification...");
      v7 = 0;
      v6 = 0;
      if ( sub_1400013B0((__int64)v8, &v7, &v6) )
      {
        puts("correct");
        return 0;
      }
      sub_140013140("incorrect at round %zu (layer %u)\n", v7 + 1, v6 + 1);
    }
    else
    {
      puts("input format error");
    }
  }
  return 1;
}
```

sub_1400013B0循环 81 轮，每轮使用order[round]作为层ID并调度函数指针表，实现了以NOR为中心的虚拟机。



sub_140012480是Festiel的 ARX 混频器，等效形式为：

```python
lo, hi = low32(seed), high32(seed)
for i in range(rounds):
    k = table_key(i) ^ rolling_sum ^ round_mix
    t = rol32((k_lo ^ lo), rot_a)
    lo_new = (hi ^ (t + (lo ^ k_hi))) ^ (k_lo + ror32(lo, rot_b))
    hi, lo = lo, lo_new
```

sub_140012630是64位ARX组合器

大概逻辑是：

```
 v9 = v16 + ROL64(k ^ acc ^ v9, rot)
```

sub_140012F10是SplitMix64 风格的生成器,等效逻辑是：

```python
state -= 0x61C8864680B583EB
z = (state ^ (state >> 30)) * 0xBF58476D1CE4E5B9
z = (z ^ (z >> 27)) * 0x94D049BB133111EB
out = z ^ (z >> 31)
```

sub_140012F60其实就是NOR VM的主要逻辑

```c
return ~(a2 | a1);
```

然后动态执行NOR VM，每层实现常量的改变：

每一个layer的整体模板如下：

```c
v5  = sub_140012480(x, s0, r, table_L, L);
v8  = sub_140012630(v5, s0, r, table_L, L);
v9  = sub_140012780(v8, v5, s0, r, table_L);
v10 = sub_140012940(v8, r, table_L);
if (v10 != TARGET[L]) return 0;

v18 = sub_140012B90(v8, v9, s0, r, table_L);
sub_140012C00(flag_buf, v18, r);
s0 = sub_140012CA0(v8, v9, s0, v5, r, table_L, L);

return !sub_140001100(...);
```

每层的本质抽象出来其实是一个很复杂的z3约束表达式，最后都能变成

f(input)== target

这样的形式，所以可以直接抄出来函数模板，提取参数求解即可

然后验证顺序和输入顺序的对应是：

```text
[1, 76, 32, 47, 53, 72, 28, 58, 2, 26, 41, 68, 43, 11, 65, 17, 67, 50, 4, 46, 5, 3, 73, 44, 19, 77, 49, 22, 78, 61, 66, 64, 71, 48, 40, 80, 24, 60, 51, 6, 62, 8, 79, 63, 52, 30, 45, 75, 16, 25, 15, 33, 81, 56, 57, 69, 36, 74, 38, 54, 70, 7, 37, 29, 14, 39, 18, 27, 34, 9, 23, 55, 12, 10, 35, 31, 20, 42, 13, 59, 21]
```

然后可以自行使用ida python提取数据得到每层的模板常量，最后z3求解

最后exp如下：

```python
#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Embedded-data solver generated from nor_data.json.
"""

from pathlib import Path

from z3 import (
    BitVec,
    BitVecVal,
    Concat,
    Extract,
    RotateLeft,
    RotateRight,
    Solver,
    UGE,
    ULE,
    sat,
)

MASK64 = (1 << 64) - 1
MASK32 = (1 << 32) - 1
MIN_INPUT = 10**15
MAX_INPUT = 10**16 - 1

TARGETS = [6040087155136277484, 9201116228787473754, 7068932625840448529, 9476222914721073742, 4550939852980439947, 7734561545438768643, 7964698121417019100, 10509118525619401086, 17615085397720373336, 14611903247921449779, 2066196783878451842, 15607432893771337849, 12473128424334533832, 10436403565184595342, 8876599175175189165, 6793016066092516416, 14479696017691775528, 2593443532932739211, 11255952701596308893, 8471887947084729269, 9876612649506518045, 16345867032764440756, 6715637112867701104, 597061433647103176, 17445718982131792120, 10562135586074669028, 1745472262260777316, 10683720445547295292, 2830463888371475582, 13234341561532310772, 1894073144278499488, 6953454056964678212, 16729707659877353186, 2297340739241129463, 10964921407242951940, 11392184854014901610, 3253043749380286428, 1153079155751826343, 4554980065482054616, 550286893004902160, 6568496224468840284, 4611133985206358274, 15425901754125075079, 2831108722465973894, 16768371922173895810, 11893276319691879391, 3656808813463636218, 16300655179691407460, 15015642930763445757, 881580021203168745, 15776649945110701686, 13624096800217442891, 11565628122504704716, 6776197135622248955, 5656393725326876182, 16477772474784513495, 18101248358484275654, 5961302722566256568, 14891136393745771744, 12289539200023291951, 13466185976065733189, 5657131116485898253, 8861200533157845042, 5311348217105755617, 764636234253878848, 8027874530608372071, 10090691210395521926, 14118940675977983829, 1385393016008008677, 3236431817507649544, 17384799683318401971, 7758714120444656511, 17017672162990226334, 11247888034296168731, 8162242962937050896, 17349960648628492336, 16173042233776091426, 3570188166719577318, 594636820152070292, 425071086454441469, 15630137654329865421]
ORDER = [0, 75, 31, 46, 52, 71, 27, 57, 1, 25, 40, 67, 42, 10, 64, 16, 66, 49, 3, 45, 4, 2, 72, 43, 18, 76, 48, 21, 77, 60, 65, 63, 70, 47, 39, 79, 23, 59, 50, 5, 61, 7, 78, 62, 51, 29, 44, 74, 15, 24, 14, 32, 80, 55, 56, 68, 35, 73, 37, 53, 69, 6, 36, 28, 13, 38, 17, 26, 33, 8, 22, 54, 11, 9, 34, 30, 19, 41, 12, 58, 20]
S0_INIT = 0x669e1e61279d826e
S2_INIT = 0xa03ab9f27c4c6bfb
FLAG_SEED_HEX = ""
DEFAULT_FLAG_SEED_HEX = "8f129c59d5e29d23988f2bd108f36af0634a3710306f3397c6d2c07b722582ffcf5b9109c7141c78"

RAW_HEX_LIST = []

BLOB_HEX_LIST = []

EMBEDDED_TABLES = [
    {"raw_hex": raw_hex, "blob_hex": blob_hex}
    for raw_hex, blob_hex in zip(RAW_HEX_LIST, BLOB_HEX_LIST)
]

def u64(x: int) -> int:
    return x & MASK64


def u32(x: int) -> int:
    return x & MASK32


def rol64_i(x: int, n: int) -> int:
    n &= 63
    x &= MASK64
    return ((x << n) | (x >> (64 - n))) & MASK64 if n else x


def rol32_i(x: int, n: int) -> int:
    n &= 31
    x &= MASK32
    return ((x << n) | (x >> (32 - n))) & MASK32 if n else x


def ror32_i(x: int, n: int) -> int:
    n &= 31
    x &= MASK32
    return ((x >> n) | (x << (32 - n))) & MASK32 if n else x


def ror8_i(x: int, n: int) -> int:
    n &= 7
    x &= 0xFF
    return ((x >> n) | ((x << (8 - n)) & 0xFF)) & 0xFF if n else x


def rol64_bv(x, n: int):
    return RotateLeft(x, n & 63)


def rol32_bv(x, n: int):
    return RotateLeft(x, n & 31)


def ror32_bv(x, n: int):
    return RotateRight(x, n & 31)


class TableObj:
    def __init__(self, raw: bytes, blob: bytes):
        if len(raw) < 0xC0:
            raise ValueError("table raw length < 0xC0")
        self.raw = raw
        self.blob = blob

    def b(self, off: int) -> int:
        return self.raw[off]

    def q(self, off: int) -> int:
        return int.from_bytes(self.raw[off:off+8], "little")

    def blob_d(self, off: int) -> int:
        if off + 4 > len(self.blob):
            return 0
        return int.from_bytes(self.blob[off:off+4], "little")

    def blob_q(self, off: int) -> int:
        if off + 8 > len(self.blob):
            return 0
        return int.from_bytes(self.blob[off:off+8], "little")


def sub_12f60_i(a1: int, a2: int) -> int:
    return u32(~(a2 | a1))


def sub_12e30_i(a1: int, a2: int, a3: int, a4: int, a5: int, a6: int) -> int:
    v10 = u32(a3 + a1)
    v11 = rol32_i(a1 ^ a2, a4)
    v12 = sub_12f60_i(v11, v10)
    if a5 & 1:
        res = u32(v12 + (a2 ^ ror32_i(a1, ((a4 + a6 + 7) % 0x1F) + 1)))
    else:
        res = u32(v12 ^ rol32_i(a1 ^ a3, ((a4 + a6 + 11) % 0x1F) + 1))
    if a5 & 2:
        res = u32(sub_12f60_i(a1, a2 ^ a3) ^ res)
    return res


def splitmix64_next(state: int):
    state = u64(state - 0x61C8864680B583EB)
    z = u64((state ^ (state >> 30)) * 0xBF58476D1CE4E5B9)
    z = u64((z ^ (z >> 27)) * 0x94D049BB133111EB)
    return state, u64(z ^ (z >> 31))


def sub_12d50_i(tbl: TableObj, seed_in: int):
    n = tbl.q(184)
    if n > 0x1E4:
        return 0, []
    cnt = n // 5
    seed = u64(seed_in)
    out = []
    for i in range(cnt):
        base = 40 * i
        seed, r = splitmix64_next(seed)
        b0 = (tbl.blob_d(base + 0) ^ r) & 0xFF
        seed, r = splitmix64_next(seed)
        b1 = (tbl.blob_d(base + 8) ^ r) & 7
        seed, r = splitmix64_next(seed)
        b2 = (tbl.blob_d(base + 16) ^ r) & 7
        seed, r = splitmix64_next(seed)
        b3 = (tbl.blob_d(base + 24) ^ r) & 7
        seed, r = splitmix64_next(seed)
        qv = u64(tbl.blob_q(base + 32) ^ r)
        out.append((b0, b1, b2, b3, qv))
    return cnt, out


def sub_12480_i(x: int, s0: int, rnd: int, tbl: TableObj, layer: int) -> int:
    v5 = u64(tbl.q(40) ^ x)
    v6 = 0xA24BAED4963EE407
    v8 = tbl.b(162)
    v20 = v8 + rnd + 7
    v9 = v8 + rnd + 6
    v10 = v8 + rnd
    v19 = rnd + v8 + 1
    v23 = u64(tbl.q(0) ^ u64(0xD6E8FEB86659FD93 * layer) ^ s0 ^ u64(0x9E3779B97F4A7C15 - 0x61C8864680B583EB * rnd))
    rounds = tbl.b(161) + 6

    lo = v5 & MASK32
    hi = (v5 >> 32) & MASK32
    for i in range(rounds):
        old_hi = hi
        old_lo = lo
        v14 = 31 * (v10 // 31)
        v15 = v20 - 31 * ((v9 - v14) // 31) - v14
        v16 = u64(tbl.q(8 + 8 * (i & 3)) ^ v6 ^ v23)
        r1 = (i + v19 - v14) & 0xFF
        r2 = (i + v15) & 0xFF
        x1 = rol32_i((u32(v16) ^ old_lo), r1)
        v17 = u32(old_hi ^ u32(x1 + u32(old_lo ^ u32(v16 >> 32))))
        lo = u32(v17 ^ u32(u32(v16) + ror32_i(old_lo, r2)))
        hi = old_lo
        v6 = u64(v6 - 0x5DB4512B69C11BF9)
        v9 += 1
        v10 += 1
    return u64((hi << 32) | lo)


def sub_12630_i(a1: int, s0: int, rnd: int, tbl: TableObj, layer: int) -> int:
    v18 = tbl.b(163) + rnd + layer + 1
    v19 = tbl.b(163)
    v6 = rnd + v19
    v7 = u64(0x6B2FB644ECCEEE15 * rnd)
    v8 = 0xBF58476D1CE4E5B9
    v9 = u64(s0 ^ tbl.q(40) ^ u64(0xA24BAED4963EE407 * layer) ^ a1 ^ u64(0x9E3779B97F4A7C15 - 0x61C8864680B583EB * rnd))
    rounds = tbl.b(160) + 2
    v11 = layer + v6
    v12 = u64(0x94D049BB133111EB - v7)
    v13 = 0
    v14 = v12
    for _ in range(rounds):
        v16 = u64(s0 ^ v8 ^ tbl.q(88 + 8 * (v13 & 3)))
        rot = (v18 + v13 - 63 * ((v11 // 63) & 0xFF)) & 0xFF
        v9 = u64(v16 + rol64_i(tbl.q(120 + 8 * ((v13 + v19) & 3)) ^ v14 ^ v9, rot))
        v11 += 1
        v14 = u64(v14 + v12)
        v8 = u64(v8 - 0x40A7B892E31B1A47)
        v13 = (v13 + 1) & 0xFF
    return v9


def sub_12780_i(a1: int, a2: int, s0: int, rnd: int, tbl: TableObj) -> int:
    v9 = tbl.b(163)
    s = rnd + v9
    v10 = ((0x1A7B9611A7B9611B * s) >> 64) & MASK64
    v11 = tbl.q(80) ^ u64(0x2545F4914F6CDD1D * rnd + 0x2545F4914F6CDD1D)
    rot = ((rnd & 0xFF) + (v9 & 0xFF) - 29 * (((v10 + ((s - v10) >> 1)) >> 4) & 0xFF) + 1) & 0xFF
    seed = rol64_i(a2, rot) ^ s0 ^ v11

    cnt, blocks = sub_12d50_i(tbl, seed)
    if cnt == 0:
        return 0

    acc = 0x9E3779B97F4A7C15
    hi = (a1 >> 32) & MASK32
    lo = a1 & MASK32
    v28 = u64(s0 ^ tbl.q(48) ^ a2)
    v20 = rnd & 0xFF

    for i, (b0, b1, b2, b3, qv) in enumerate(blocks):
        v23 = tbl.q(120 + 8 * ((b3 + i + b2 + b1) & 3)) ^ acc ^ qv
        old_lo = lo
        idx = tbl.b(164 + (b0 & 7))
        rot2 = (((idx ^ b1 ^ v9 ^ b0) ^ (i ^ v20 ^ (2 * b2) ^ (4 * b3))) & 0x1F) + 1
        f = sub_12e30_i(
            lo,
            u32(v28 ^ v23),
            u32((v28 ^ v23) >> 32),
            rot2,
            idx,
            i,
        )
        lo = u32(hi ^ f)
        hi = old_lo
        acc = u64(acc - 0x61C8864680B583EB)
    return u64((hi << 32) | lo)


def sub_12940_i(a1: int, rnd: int, tbl: TableObj) -> int:
    v4 = tbl.b(163)
    v29 = tbl.q(64)
    v28 = tbl.q(56)
    v5 = u64(0xA24BAED4963EE407 - 0x5DB4512B69C11BF9 * rnd)
    v6 = tbl.b(162)
    v26 = tbl.q(72)
    v25 = tbl.q(0)
    v24 = tbl.q(40)
    rounds = ((v4 + rnd) & 1) + 3
    v23 = v6 + 1
    v7 = v6 + rnd + 1
    v22 = v4 + 1
    v8 = rnd + v6
    v31 = v4
    v10 = v4
    v27 = v5
    i = 0

    for _ in range(rounds):
        v32 = v5
        v33 = v10
        v34 = v8
        v19 = v7
        v18 = v6
        v12 = -63 * (v6 // 63)
        v13 = u64(tbl.q(8 * ((((i & 0xFF) + v31) & 3) + 11)) ^ tbl.q(8 * (i + 1)) ^ v28 ^ v5 ^ v29)
        v14 = v7 - 63 * (v8 // 63)
        prev_i = i
        i += 1
        v15 = rol64_i(v26, (prev_i + v22 - 63 * ((v10 & 0xFF) // 63)) & 0xFF)
        v16 = u64((v25 + v13) ^ rol64_i(v24, (prev_i + v23 + v12) & 0xFF))
        a1 = u64(v16 + rol64_i(v15 ^ a1 ^ v13, v14))
        v6 = v18 + 1
        v7 = v19 + 3
        v8 = v34 + 3
        v5 = u64(v27 + v32)
        v10 = v33 + 1

    return u64(a1 ^ tbl.q(80) ^ u64(0x94D049BB133111EB - 0x6B2FB644ECCEEE15 * rnd))


def sub_12b90_i(a1: int, a2: int, s0: int, rnd: int, tbl: TableObj) -> int:
    v = u64(tbl.q(72) ^ a2 ^ a1 ^ u64(0xD6E8FEB86659FD93 * rnd - 0x2917014799A6026D))
    return u64(v ^ rol64_i(s0, ((rnd + tbl.b(163)) % 0x1F) + 1))


def sub_12ca0_i(a1: int, a2: int, s0: int, a4: int, rnd: int, tbl: TableObj, layer: int) -> int:
    return u64(
        u64(0x9E3779B97F4A7C15 * layer)
        ^ s0
        ^ u64(0x2545F4914F6CDD1D * a4 + 0x2545F4914F6CDD1D)
        ^ rol64_i(tbl.q(56) + a2 + a1, ((rnd + tbl.b(163)) % 0x2F) + 1)
    )


def sub_12c00_i(flag: bytearray, k: int, rnd: int):
    v3 = rnd & MASK32
    v6 = 8 * rnd
    v7 = 7 * rnd
    v9 = rnd
    for n in range(40):
        part = u64((k >> (v6 & 0x38)) ^ v7 ^ (k >> ((v6 & 0x38) ^ 0x38)))
        t1 = (part + v3 + n) & 0xFF
        x = (flag[n] ^ t1) & 0xFF
        x = (x - ((part ^ v9) & 0xFF)) & 0xFF
        rot = (((part & 0xFF) ^ ((v3 ^ n) & 0xFF)) & 7)
        flag[n] = ror8_i(x, rot)
        v9 += 5
        v7 += 13
        v6 += 8


def sub_12480_sym(x, s0: int, rnd: int, tbl: TableObj, layer: int):
    v5 = BitVecVal(tbl.q(40), 64) ^ x
    v6 = 0xA24BAED4963EE407
    v8 = tbl.b(162)
    v20 = v8 + rnd + 7
    v9 = v8 + rnd + 6
    v10 = v8 + rnd
    v19 = rnd + v8 + 1
    v23 = BitVecVal(u64(tbl.q(0) ^ u64(0xD6E8FEB86659FD93 * layer) ^ s0 ^ u64(0x9E3779B97F4A7C15 - 0x61C8864680B583EB * rnd)), 64)
    rounds = tbl.b(161) + 6

    lo = Extract(31, 0, v5)
    hi = Extract(63, 32, v5)
    for i in range(rounds):
        old_hi = hi
        old_lo = lo
        v14 = 31 * (v10 // 31)
        v15 = v20 - 31 * ((v9 - v14) // 31) - v14
        v16 = BitVecVal(u64(tbl.q(8 + 8 * (i & 3)) ^ v6), 64) ^ v23
        v16_lo = Extract(31, 0, v16)
        v16_hi = Extract(63, 32, v16)
        x1 = rol32_bv(old_lo ^ v16_lo, (i + v19 - v14) & 0xFF)
        v17 = old_hi ^ (x1 + (old_lo ^ v16_hi))
        lo = v17 ^ (v16_lo + ror32_bv(old_lo, (i + v15) & 0xFF))
        hi = old_lo
        v6 = u64(v6 - 0x5DB4512B69C11BF9)
        v9 += 1
        v10 += 1
    return Concat(hi, lo)


def sub_12630_sym(a1, s0: int, rnd: int, tbl: TableObj, layer: int):
    v18 = tbl.b(163) + rnd + layer + 1
    v19 = tbl.b(163)
    v6 = rnd + v19
    v7 = u64(0x6B2FB644ECCEEE15 * rnd)
    v8 = 0xBF58476D1CE4E5B9
    v9 = BitVecVal(u64(s0 ^ tbl.q(40) ^ u64(0xA24BAED4963EE407 * layer) ^ u64(0x9E3779B97F4A7C15 - 0x61C8864680B583EB * rnd)), 64) ^ a1
    rounds = tbl.b(160) + 2
    v11 = layer + v6
    v12 = u64(0x94D049BB133111EB - v7)
    v13 = 0
    v14 = v12
    for _ in range(rounds):
        v16 = BitVecVal(u64(s0 ^ v8 ^ tbl.q(88 + 8 * (v13 & 3))), 64)
        rot = (v18 + v13 - 63 * ((v11 // 63) & 0xFF)) & 0xFF
        v9 = v16 + rol64_bv(BitVecVal(tbl.q(120 + 8 * ((v13 + v19) & 3)) ^ v14, 64) ^ v9, rot)
        v11 += 1
        v14 = u64(v14 + v12)
        v8 = u64(v8 - 0x40A7B892E31B1A47)
        v13 = (v13 + 1) & 0xFF
    return v9


def sub_12940_sym(a1, rnd: int, tbl: TableObj):
    v4 = tbl.b(163)
    v29 = tbl.q(64)
    v28 = tbl.q(56)
    v5 = u64(0xA24BAED4963EE407 - 0x5DB4512B69C11BF9 * rnd)
    v6 = tbl.b(162)
    v26 = tbl.q(72)
    v25 = tbl.q(0)
    v24 = tbl.q(40)
    rounds = ((v4 + rnd) & 1) + 3
    v23 = v6 + 1
    v7 = v6 + rnd + 1
    v22 = v4 + 1
    v8 = rnd + v6
    v31 = v4
    v10 = v4
    v27 = v5
    i = 0
    for _ in range(rounds):
        v32 = v5
        v33 = v10
        v34 = v8
        v19 = v7
        v18 = v6
        v12 = -63 * (v6 // 63)
        v13 = u64(tbl.q(8 * ((((i & 0xFF) + v31) & 3) + 11)) ^ tbl.q(8 * (i + 1)) ^ v28 ^ v5 ^ v29)
        v14 = v7 - 63 * (v8 // 63)
        prev_i = i
        i += 1
        v15 = rol64_bv(BitVecVal(v26, 64), (prev_i + v22 - 63 * ((v10 & 0xFF) // 63)) & 0xFF)
        v16 = BitVecVal(u64((v25 + v13) ^ rol64_i(v24, (prev_i + v23 + v12) & 0xFF)), 64)
        a1 = v16 + rol64_bv(v15 ^ a1 ^ BitVecVal(v13, 64), v14)
        v6 = v18 + 1
        v7 = v19 + 3
        v8 = v34 + 3
        v5 = u64(v27 + v32)
        v10 = v33 + 1
    return a1 ^ BitVecVal(u64(tbl.q(80) ^ u64(0x94D049BB133111EB - 0x6B2FB644ECCEEE15 * rnd)), 64)


def build_tables():
    return [TableObj(bytes.fromhex(t["raw_hex"]), bytes.fromhex(t.get("blob_hex", ""))) for t in EMBEDDED_TABLES]


def solve_direct():
    order = ORDER
    targets = TARGETS
    tables = build_tables()

    if len(order) != 81 or len(targets) != 81 or len(tables) != 81:
        raise RuntimeError(f"unexpected sizes: order={len(order)} targets={len(targets)} tables={len(tables)}")

    s0 = S0_INIT
    _s2 = S2_INIT
    del _s2

    seed_hex = FLAG_SEED_HEX or DEFAULT_FLAG_SEED_HEX
    flag = bytearray(bytes.fromhex(seed_hex))
    if len(flag) != 40:
        raise RuntimeError("FLAG_SEED must be 40 bytes after fallback")

    inputs = []

    for rnd in range(81):
        layer = order[rnd]
        tbl = tables[layer]
        target = targets[layer]

        x = BitVec(f"x_{rnd}", 64)
        solver = Solver()
        solver.add(UGE(x, BitVecVal(MIN_INPUT, 64)))
        solver.add(ULE(x, BitVecVal(MAX_INPUT, 64)))

        v5 = sub_12480_sym(x, s0, rnd, tbl, layer)
        v8 = sub_12630_sym(v5, s0, rnd, tbl, layer)
        v10 = sub_12940_sym(v8, rnd, tbl)
        solver.add(v10 == BitVecVal(target, 64))

        if solver.check() != sat:
            raise RuntimeError(f"unsat at round {rnd+1}, layer {layer+1}")

        xv = solver.model().eval(x).as_long()
        inputs.append(xv)

        v5c = sub_12480_i(xv, s0, rnd, tbl, layer)
        v8c = sub_12630_i(v5c, s0, rnd, tbl, layer)
        v9c = sub_12780_i(v8c, v5c, s0, rnd, tbl)
        v18 = sub_12b90_i(v8c, v9c, s0, rnd, tbl)
        sub_12c00_i(flag, v18, rnd)
        s0 = sub_12ca0_i(v8c, v9c, s0, v5c, rnd, tbl, layer)

        print(f"[round {rnd+1:02d}] layer={layer+1:02d} x={xv}", flush=True)

    return inputs, bytes(flag)


def main():
    inputs, flag = solve_direct()

    print("flag:", flag.decode("ascii", errors="replace"))

if __name__ == "__main__":
    main()

```
得到flag：SUCTF{y0u_h4v3_0v3rc0m3_81_d1ff1cu1t135}

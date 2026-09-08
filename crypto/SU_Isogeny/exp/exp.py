from Crypto.Util.number import *
from Crypto.Cipher import AES
from hashlib import sha256
from pwn import *
from sage.all import *
from cuso import find_small_roots

p = 5326738796327623094747867617954605554069371494832722337612446642054009560026576537626892113026381253624626941643949444792662881241621373288942880288065659
F = GF(p)

io = remote("110.42.47.116", 10001)

io.sendlineafter(b">>> ", b"1")
io.recvuntil(b"pkA: ")
a = int(io.recvline().strip())
io.recvuntil(b"pkB: ")
pkB = int(io.recvline().strip())

b = 2 * (a + 6) * inverse(2 - a, p) % p
c = 2 * (a - 6) * inverse(a + 2, p) % p


io.sendlineafter(b">>> ", b"2")
io.sendlineafter(b"pkA >>> ", str(a).encode())
io.sendlineafter(b"pkB >>> ", str(pkB).encode())

io.recvuntil(b"Gift : ")
A = int(io.recvline().strip()) << 200

io.sendlineafter(b">>> ", b"2")
io.sendlineafter(b"pkA >>> ", str(b).encode())
io.sendlineafter(b"pkB >>> ", str(pkB).encode())

io.recvuntil(b"Gift : ")
B = int(io.recvline().strip()) << 200

io.sendlineafter(b">>> ", b"2")
io.sendlineafter(b"pkA >>> ", str(c).encode())
io.sendlineafter(b"pkB >>> ", str(pkB).encode())

io.recvuntil(b"Gift : ")
C = int(io.recvline().strip()) << 200

x, y, z = var("x y z")

relations = [
    (A + x) * (B + y) + 2 * (A + x) - 2 * (B + y) + 12,
    (C + z) * (B + y) + 2 * (B + y) - 2 * (C + z) + 12,
    (A + x) * (C + z) - 2 * (A + x) + 2 * (C + z) + 12
]

bounds = {
    x: (0, 2**200),
    y: (0, 2**200),
    z: (0, 2**200)
}

moduli = [p, p, p]

roots = find_small_roots(relations, bounds, moduli)

io.sendlineafter(b">>> ", b"3")
io.recvuntil(b"Here is your flag: ")
ct = bytes.fromhex(io.recvline().strip().decode())

key = sha256(str(A + roots[0][x]).encode()).digest()

flag = AES.new(key, AES.MODE_ECB).decrypt(ct)
print(flag)

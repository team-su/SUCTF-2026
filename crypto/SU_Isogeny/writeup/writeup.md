## SU_Isogeny

```Python
from Crypto.Util.number import *
from Crypto.Cipher import AES
from Crypto.Util.Padding import *
from hashlib import sha256
from secret import flag
from random import randint
import os

p = 5326738796327623094747867617954605554069371494832722337612446642054009560026576537626892113026381253624626941643949444792662881241621373288942880288065659

F = GF(p)

pl = [x for x in prime_range(3, 374) + [587]]
pvA = [randint(-5, 5) for _ in pl]
pvB = [randint(-5, 5) for _ in pl]

assert len(pl) == len(pvA) == len(pvB)


def cal(A, sk):
    E = EllipticCurve(F, [0, A, 0, 1, 0])
    for sgn in [1, -1]:
        for e, ell in zip(sk, pl):
            for i in range(sgn * e):
                while not (P := (p + 1) // ell * E.random_element()) or ell * P != 0:
                    pass
                E = E.isogeny_codomain(P)
        E = E.quadratic_twist()
    return E.montgomery_model().a2()

menu = """[1] Get public key
[2] Get gift
[3] Get flag
[4] Exit
>>> """

def main():
    while True:
        try:
            op = input(menu)
            if op == "1":
                pkA = cal(0, pvA)
                pkB = cal(0, pvB)
                print(f"pkA: {pkA}")
                print(f"pkB: {pkB}")
            elif op == "2":
                pkA = int(input("pkA >>> "))
                pkB = int(input("pkB >>> "))
                A = cal(pkA, pvB)
                B = cal(pkB, pvA)
                if A != B:
                    print("Illegal public key!")
                print(f"Gift : {int(A) >> 200}")
            elif op == "3":
                key = sha256(str(cal(cal(0, pvB), pvA)).encode()).digest()
                print(f"Here is your flag: {AES.new(key, AES.MODE_ECB).encrypt(pad(flag, 16)).hex()}")
            elif op == "4":
                print("Bye~")
                break
            else:
                print("Invalid option.")
        except:
            print("Something wrong...")
            break

if __name__ == "__main__":
    main()
```

分解$p+1$可以看到它其实是很标准的CSIDH-512所使用的模数，本题是很标准的CI-HNP问题，求解该问题的具体原理可以参考[*Solving the Hidden Number Problem for CSIDH and CSURF via Automated Coppersmith*](https://eprint.iacr.org/2023/1409.pdf)一文，在获得公钥$A,B$之后，对于$\mathbb{Z}_p$上的曲线$E_A:y^2=x^3+Ax^2+x$，我们可以通过如下方式计算它的两个4-同源曲线：
$$
\begin{aligned}
E_{A1}:y^2=x^3+2\frac{A+6}{2-A}x^2+x\\
E_{A2}:y^2=x^3+2\frac{A-6}{A+2}x^2+x\\
\end{aligned}
$$
我们先将$(A,B)$作为公钥输入可以得到$E_{AB}$的参数的高位$H_1$，再分别输入$(2\frac{A+6}{2-A},B)$和$(2\frac{A-6}{A+2},B)$作为公钥进行一次错误的密钥交换可以得到$E_{AB}$的两条4-同源曲线的参数的高位（分别记为$H_2,H_3$），设三未知数$x,y,z$，则可以得到如下关系：
$$
\begin{cases}
(H_1+x)(H_2+y)+2(H_1+x)-2(H_2+y)+12=0\\
(H_3+z)(H_2+y)+2(H_2+y)-2(H_3+z)+12=0\\
(H_1+x)(H_3+z)-2(H_1+x)+2(H_3+z)+12=0\\
\end{cases}
$$
因为已知位约为$512-200=312>\frac{13}{24}\times512$，满足论文中所给出的可求解条件，所以我们可以通过Coppersmith方法进行求解，这里我们使用cuso进行求解，当然，也可以利用论文中给出的代码（[juliannowakowski/automated-coppersmith](https://github.com/juliannowakowski/automated-coppersmith)）进行求解：

```Python
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
```


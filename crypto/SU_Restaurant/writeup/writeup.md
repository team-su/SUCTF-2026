## SU_Restaurant

```Python
from Crypto.Util.number import *
from Crypto.Util.Padding import *
from random import randint, choice, choices
from hashlib import sha3_512
from base64 import b64encode, b64decode
from secret import flag
import numpy as np
import json
import os
# import pty

H = lambda x: [int(y, 16) for y in [sha3_512(x).hexdigest()[i:i+2] for i in range(0, 128, 2)]]
alphabet = "".join([chr(i) for i in range(33, 127)])

class Point:
    def __init__(self, x):
        if isinstance(x, float):
            raise ValueError("...")
        while not isinstance(x, int):
            x = x.x
        self.x = x

    def __add__(self, other):
        if isinstance(other, int):
            return self.x + other
        return Point(min(self.x, other.x))

    def __radd__(self, other):
        if isinstance(other, int):
            return self.x + other
        return Point(min(self.x, other.x))

    def __mul__(self, other):
        return Point(self.x + other.x)

    def __eq__(self, other):
        return self.x == other.x

    def __repr__(self):
        return f"{self.x}"

    def __int__(self):
        return self.x


class Block:
    def __init__(self, n, m, data=None):
        self.n = n
        self.m = m
        if data and (len(data) != n or len(data[0]) != m):
            raise ValueError("...")
        if data:
            if isinstance(data, Point):
                self.data = [[Point(data[i][j].x) for j in range(m)] for i in range(n)]
            else:
                self.data = [[Point(data[i][j]) for j in range(m)] for i in range(n)]
        else:
            self.data = [[Point(randint(0, 255)) for _ in range(m)] for _ in range(n)]

    def __add__(self, other):
        return Block(self.n, self.m, [[self.data[i][j] + other.data[i][j] for j in range(self.m)] for i in range(self.n)])

    def __mul__(self, other):
        assert self.m == other.n, "😭"
        res = [[Point(511) for _ in range(other.m)] for _ in range(self.n)]
        for i in range(self.n):
            for j in range(other.m):
                for k in range(self.m):
                    res[i][j] = res[i][j] + (self.data[i][k] * other.data[k][j])

        return Block(self.n, other.m, res)

    def __eq__(self, other):
        res = True
        for i in range(self.n):
            for j in range(self.m):
                res = res and self.data[i][j] == other.data[i][j]
        return res

    def __getitem__(self, item):
        return self.data[item]

    def __repr__(self):
        return f"{self.data}"

    def legitimacy(self, lb, rb):
        for i in range(self.n):
            for j in range(self.m):
                if not (lb <= int(self.data[i][j].x) <= rb):
                    return False
        return True

class Restaurant:
    def __init__(self, m, n, k):
        self.m, self.n, self.k = m, n, k
        self.chef = Block(m, k)
        self.cooker = Block(k, n)
        self.fork = self.chef * self.cooker

    def cook(self, msg):
        if isinstance(msg, str):
            msg = msg.encode()
        tmp = H(msg)
        M = Block(self.n, self.m, [[tmp[i * self.m + j] for j in range(self.m)] for i in range(self.n)])
        while True:
            U = Block(self.n, self.k)
            V = Block(self.k, self.m)
            P = self.chef * V
            R = U * self.cooker
            S = U * V
            A = (M * self.chef) + U
            B = (self.cooker * M) + V
            if A != U and B != V:
                break
        return A, B, P, R, S

    def eat(self, msg, A, B, P, R, S):
        if isinstance(msg, str):
            msg = msg.encode()
        tmp = H(msg)
        M = Block(self.n, self.m, [[tmp[i * self.m + j] for j in range(self.m)] for i in range(self.n)])
        Z = (M * self.fork * M) + (M * P) + (R * M) + S
        W = A * B
        legal = A.legitimacy(0, 256) and B.legitimacy(0, 256) and P.legitimacy(0, 256) and R.legitimacy(0, 256) and S.legitimacy(0, 256)
        return W == Z and W != S and legal


banner = """=================================================================
 ____  _   _   ____           _                              _   
/ ___|| | | | |  _ \ ___  ___| |_ __ _ _   _ _ __ __ _ _ __ | |_ 
\___ \| | | | | |_) / _ \/ __| __/ _` | | | | '__/ _` | '_ \| __|
 ___) | |_| | |  _ <  __/\__ \ || (_| | |_| | | | (_| | | | | |_ 
|____/ \___/  |_| \_\___||___/\__\__,_|\__,_|_|  \__,_|_| |_|\__|
=================================================================
"""

menu = """Do something...
[1] Say to the waiter: "Please give me some food."
[2] Say to the waiter: "Please give me the FLAG!"
[3] Check out
What do you want to do? 
>>> """

foodlist = ["Spring rolls", "Red Rice Rolls", "Chencun Rice Noodles", "Egg Tart", "Cha siu bao"]
table = []

def main():
    print(banner)
    SU_Restaurant = Restaurant(8, 8, 7)

    havefork = False
    try:
        while True:
            op = int(input(menu))
            if op == 1:
                if len(table) == 2:
                    print("You're full and don't want to order more...")
                    continue
                foodname = choice(foodlist)
                while foodname in table:
                    foodname = choice(foodlist)
                print(f'The waiter says: "Here is your {foodname}!"')
                table.append(foodname)
                A, B, P, R, S = SU_Restaurant.cook(foodname)
                print(f'A = {A}\nB = {B}\nP = {P}\nR = {R}\nS = {S}')

            elif op == 2:
                Fo0dN4mE = "".join(choices(alphabet, k=36))
                print(f'The waiter says: "Please make {Fo0dN4mE} for me!"')
                res = json.loads(input(">>> "))
                r1 = np.linalg.matrix_rank(np.array(res["A"]))
                r2 = np.linalg.matrix_rank(np.array(res["B"]))
                r3 = np.linalg.matrix_rank(np.array(res["P"]))
                r4 = np.linalg.matrix_rank(np.array(res["R"]))
                r5 = np.linalg.matrix_rank(np.array(res["S"]))

                if r1 < 7 or r2 < 7 or r3 < 8 or r4 < 8 or r5 < 8:
                    print('The waiter says: "These are illegal food ingredients"')
                    continue

                A = Block(8, 7, res["A"])
                B = Block(7, 8, res["B"])
                P = Block(8, 8, res["P"])
                R = Block(8, 8, res["R"])
                S = Block(8, 8, res["S"])
                if SU_Restaurant.eat(Fo0dN4mE, A, B, P, R, S):
                    print(f'The waiter says: "Here is the FLAG: {flag}"')
                else:
                    print('The waiter says: "This is not what I wanted!"')
                    exit(0)

            elif op == 3:
                print('The waiter says: "Welcome to our restaurant next time!"')
                break
            else:
                print("Invalid option!")
    except:
        print("Something wrong...")
        exit(0)

if __name__ == "__main__":
    main()
```

上述代码实现了[*Tropical cryptography IV: Digital signatures and secret sharing with arbitrary access structure*](https://eprint.iacr.org/2026/095.pdf)一文中实现的基于热带代数的数字签名算法，该签名算法流程如下：

首先选取一个$m\times k$的随机热带矩阵$X$以及一个$k\times n$的随机热带矩阵$Y$，计算$T=XY$，并将明文计算为一个$n\times m$的哈希矩阵$M$，之后选取一个$n\times k$的随机热带矩阵$U$以及一个$k\times m$的随机热带矩阵$V$，记$P=X\otimes V,R=U\otimes Y,S=U\otimes V$，满足$(M\otimes X)\oplus U\neq U,(Y\otimes M)\oplus V\neq V$，最后得到签名五元组：$((M\otimes X)\oplus U,(Y\otimes M)\oplus V,P,R,S)$
而该算法的验签流程如下：

对于获得的签名五元组：$((M\otimes X)\oplus U,(Y\otimes M)\oplus V,P,R,S)$，计算：
$$
Z=(M\otimes T\otimes M)\oplus (M\otimes P)\oplus(R\otimes M)\oplus S
$$
判断$Z$是否等于$[(M\otimes X)\oplus U]\otimes[(Y\otimes M)\oplus V]$，若相等则验证成功，否则验证失败。

对于这个签名，在[*A Comprehensive Break of the Tropical Matrix-Based Signature Scheme*](https://eprint.iacr.org/2026/387.pdf)一文（中提到可以通过获取两次签名来构造方程，我们记第$i(i=1,2)$次获得的签名为$(A_i,B_i,P_i,R_i,S_i)$，将$X,Y,U_i,V_i$的所有元素作为$[0,255]$中的未知量可以得到方程：
$$
\begin{cases}
A_i=(M_i\otimes X)\oplus U_i\\
B_i=(Y\otimes M_i)\oplus V_i\\
P_i=X\otimes V_i\\
R_i=U_i\otimes Y\\
S_i=U_i\otimes V_i\\
\end{cases}
$$
对于热带代数中的加法$a\oplus b=c$，我们可以用下面的关系进行替代：
$$
[(a\le c)\and(b\le c)]\and[(a=c)\or(b=c)]
$$
通过这种方式可以将上述热带矩阵方程改写为z3可以求解的方程，从而求解出私钥，最后对靶机提供的长度为36的字符串进行签名得到flag（以下脚本由AI辅助生成）：

```python
from pwn import *
from z3 import *
from hashlib import sha3_512
import json
import random
import sys

context.log_level = 'info'

M_DIM, N_DIM, K_DIM = 8, 8, 7


def get_M(msg: str):
    h = sha3_512(msg.encode()).hexdigest()
    v = [int(h[i:i + 2], 16) for i in range(0, 128, 2)]
    return [[v[i * M_DIM + j] for j in range(M_DIM)] for i in range(N_DIM)]


def minplus_mul(A, B):
    n, m, p = len(A), len(A[0]), len(B[0])
    return [[min(A[i][k] + B[k][j] for k in range(m)) for j in range(p)] for i in range(n)]


def minplus_add(A, B):
    return [[min(A[i][j], B[i][j]) for j in range(len(A[0]))] for i in range(len(A))]


def parse_matrix(io, name: bytes):
    io.recvuntil(name + b" = ")
    return eval(io.recvline().decode().strip())


def add_minplus_eq_const(s, out_const, left, right):
    n, mid, p = len(left), len(left[0]), len(right[0])
    for i in range(n):
        for j in range(p):
            terms = [left[i][t] + right[t][j] for t in range(mid)]
            for t in terms:
                s.add(out_const[i][j] <= t)
            s.add(Or([out_const[i][j] == t for t in terms]))


def add_minplus_eq_var(s, out_var, left, right):
    n, mid, p = len(left), len(left[0]), len(right[0])
    for i in range(n):
        for j in range(p):
            terms = [left[i][t] + right[t][j] for t in range(mid)]
            for t in terms:
                s.add(out_var[i][j] <= t)
            s.add(Or([out_var[i][j] == t for t in terms]))


def recv_two_signatures(io):
    sigs = []
    for _ in range(2):
        io.sendlineafter(b">>> ", b"1")
        io.recvuntil(b"Here is your ")
        msg = io.recvuntil(b"!")[:-1].decode()
        A = parse_matrix(io, b"A")
        B = parse_matrix(io, b"B")
        P = parse_matrix(io, b"P")
        R = parse_matrix(io, b"R")
        S = parse_matrix(io, b"S")
        sigs.append((msg, A, B, P, R, S))
    return sigs


def recover_keys(sigs):
    s = Solver()
    chef = [[Int(f"chef_{i}_{j}") for j in range(K_DIM)] for i in range(M_DIM)]
    cooker = [[Int(f"cooker_{i}_{j}") for j in range(N_DIM)] for i in range(K_DIM)]

    for i in range(M_DIM):
        for j in range(K_DIM):
            s.add(chef[i][j] >= 0, chef[i][j] <= 255)
    for i in range(K_DIM):
        for j in range(N_DIM):
            s.add(cooker[i][j] >= 0, cooker[i][j] <= 255)

    for idx, (msg, A, B, P, R, S_mat) in enumerate(sigs):
        M = get_M(msg)
        Mz = [[IntVal(M[i][j]) for j in range(M_DIM)] for i in range(N_DIM)]
        U = [[Int(f"U_{idx}_{i}_{j}") for j in range(K_DIM)] for i in range(N_DIM)]
        V = [[Int(f"V_{idx}_{i}_{j}") for j in range(M_DIM)] for i in range(K_DIM)]

        for i in range(N_DIM):
            for j in range(K_DIM):
                s.add(U[i][j] >= 0, U[i][j] <= 255)
        for i in range(K_DIM):
            for j in range(M_DIM):
                s.add(V[i][j] >= 0, V[i][j] <= 255)

        T1 = [[Int(f"T1_{idx}_{i}_{j}") for j in range(K_DIM)] for i in range(N_DIM)]
        add_minplus_eq_var(s, T1, Mz, chef)
        for i in range(N_DIM):
            for j in range(K_DIM):
                s.add(A[i][j] <= T1[i][j], A[i][j] <= U[i][j])
                s.add(Or(A[i][j] == T1[i][j], A[i][j] == U[i][j]))

        T2 = [[Int(f"T2_{idx}_{i}_{j}") for j in range(M_DIM)] for i in range(K_DIM)]
        add_minplus_eq_var(s, T2, cooker, Mz)
        for i in range(K_DIM):
            for j in range(M_DIM):
                s.add(B[i][j] <= T2[i][j], B[i][j] <= V[i][j])
                s.add(Or(B[i][j] == T2[i][j], B[i][j] == V[i][j]))

        add_minplus_eq_const(s, P, chef, V)
        add_minplus_eq_const(s, R, U, cooker)
        add_minplus_eq_const(s, S_mat, U, V)

    if s.check() != sat:
        raise ValueError("UNSAT")
    m = s.model()
    chef_val = [[m[chef[i][j]].as_long() for j in range(K_DIM)] for i in range(M_DIM)]
    print(chef_val)
    cooker_val = [[m[cooker[i][j]].as_long() for j in range(N_DIM)] for i in range(K_DIM)]
    return chef_val, cooker_val


def main():
    io = remote("101.245.107.149", 10020)
    sigs = recv_two_signatures(io)
    log.success("got 2 signatures")

    chef, cooker = recover_keys(sigs)
    log.success("recovered chef/cooker")

    io.sendlineafter(b">>> ", b"2")
    io.recvuntil(b"Please make ")
    target = io.recvuntil(b" for me!")[:-8].decode()
    M = get_M(target)

    while True:
        U = [[random.randint(0, 255) for _ in range(K_DIM)] for _ in range(N_DIM)]
        V = [[random.randint(0, 255) for _ in range(M_DIM)] for _ in range(K_DIM)]
        P = minplus_mul(chef, V)
        R = minplus_mul(U, cooker)
        S = minplus_mul(U, V)
        A = minplus_add(minplus_mul(M, chef), U)
        B = minplus_add(minplus_mul(cooker, M), V)
        if A != U and B != V:
            break

    payload = {"A": A, "B": B, "P": P, "R": R, "S": S}
    io.sendline(json.dumps(payload).encode())
    io.interactive()


if __name__ == "__main__":
    main()

```


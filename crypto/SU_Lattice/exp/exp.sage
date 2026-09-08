from pwn import *
from sage.all import *
from tqdm import *
from cuso import find_small_roots
from Crypto.Util.number import *

io = remote("1.95.152.117", 10001)

n = 24
alpha = 40
beta = 20
K = 2**beta

r = 83
t = 83
N = r + t - 1

hints = []

for _ in trange(N):
    io.sendlineafter(b">>> ", b"2")
    io.recvuntil(b"Here is your hint: ")
    hint = int(io.recvline().strip())
    hints.append(hint)

y = []

for i in range(r):
    y.append(hints[i: i+r])

Y = matrix(ZZ, y)

M = block_matrix([[2**alpha, 0], [Y, 1]])
res = M.BKZ(block_size=20)

R = ZZ['x']
polys = []

for i in range(50):
    vec = res[i]
    coeffs = list(vec[t:])
    if all(c == 0 for c in coeffs):
        continue
    F = R(coeffs)
    if F.degree() > 0:
        polys.append(F)

F1, F2, F3 = polys[:3]
F12 = F1.resultant(F2)
F13 = F1.resultant(F3)
F23 = F2.resultant(F3)

# print(F12)
# print(F13)
# print(F23)

tmp = gcd(gcd(F12, F13), F23)
m = list(factor(tmp))[-1][0]

L = block_matrix([[m, 0], [K*Y, K]])

res = L.BKZ(block_size=20)
R = GF(m)['x']
candidates = []

for v in res:
    if v.norm() == 0:
        continue

    coeffs = []
    is_valid = True
    for k in range(r):
        coeffs.append(v[t + k] // K)

    if is_valid:
        try:
            poly = R(coeffs)
            d = poly.degree()
            if d >= n:
                candidates.append(poly)
        except:
            pass

    if len(candidates) >= 10:
        break

F = candidates[0]
for p in candidates[1:]:
    tmp = gcd(F, p)
    if tmp.degree() >= n:
        F = tmp

F = F.monic()
print(F)

# assert F.is_primitive() and F.degree() == n

C = [int(c) for c in (-F).coefficients(sparse=False)[:-1]]

l = [var(f'l{i}') for i in range(2*n)]

xs = [2**beta * hints[i] + l[i] for i in range(2*n)]
relations = []

for i in range(n):
    f = 0
    for j in range(n):
        f += C[j] * xs[i + j]
    f -= xs[i + n]
    relations.append(f)

bounds = {l[i]: (0, 2**beta) for i in range(2*n)}

sols = find_small_roots(relations, bounds=bounds, modulus=m)

state = [2**beta * hints[i] + sols[0][l[i]] for i in range(n)]

for i in range(n):
    tmp = state[-1]
    for j in range(n-1):
        tmp = (tmp - C[j+1] * state[j]) % m

    tmp = tmp * inverse(C[0], m) % m
    state = [tmp] + state[:-1]

ans = sum(state)

io.sendlineafter(b'>>> ', b'1')
io.sendlineafter(b'Please enter your answer: ', str(ans % m).encode())

io.interactive()

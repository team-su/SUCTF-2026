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

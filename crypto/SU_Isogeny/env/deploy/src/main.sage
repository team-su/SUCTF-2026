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
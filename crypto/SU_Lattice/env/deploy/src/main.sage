import os

flag = "SUCTF{b8faea32-9f91-42b5-9355-33865e06270c}"

with open('./flag', 'w') as f:
    f.write(flag)

m = random_prime(2^60+2^16, lbound=2^60-2^16, proof=False)

f = open("./data", "w")
f.write(f"{m}\n")

F = GF(m)
R.<x> = PolynomialRing(F)
while True:
    poly = R.irreducible_element(24, algorithm="random")
    if all(c != 0 for c in poly.coefficients(sparse=False)):
        break

for i in poly.list()[:-1]:
    f.write(f"{m-i}\n")

for i in range(24):
    f.write(f"{randint(0, 2**59)}\n")

f.close()
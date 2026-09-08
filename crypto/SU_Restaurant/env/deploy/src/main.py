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
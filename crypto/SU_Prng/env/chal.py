from hashlib import md5
from random import randint
import signal
from secret import flag

bits = 256
outs = 56

ror = lambda x, k, n: ((x >> (k % n)) | (x << (n - k % n))) & ((1 << n) - 1)

class LCG:
    def __init__(self, seed, a, b):
        self.seed = seed
        self.a = a
        self.b = b
        self.m = 2**bits

    def next(self):
        self.seed = (self.seed * self.a + self.b) % self.m
        return ror((self.seed >> bits // 2) ^ (self.seed % 2**(bits // 2)), self.seed >> bits - 250, bits)

signal.alarm(15)

a = randint(1, 1 << bits)
b = randint(1, 1 << bits)
seed = randint(1, 1 << bits)
lcg = LCG(seed, a, b)
print(f'{a = }')
print(f'out = {[lcg.next() for _ in [0] * outs]}')
print(f'h = {md5(str(seed).encode()).hexdigest()}')

if int(input('> ')) == seed:
    print('Correct!')
    print(flag)
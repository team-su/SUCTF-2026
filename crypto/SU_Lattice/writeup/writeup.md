## SU_Lattice

首先对给出的elf进行逆向可以得到主函数（`sub_401E71`）：

```C
void __noreturn sub_401E71()
{
  int v0; // edx
  int v1; // ecx
  int v2; // r8d
  int v3; // r9d
  int v4; // edx
  int v5; // ecx
  int v6; // r8d
  int v7; // r9d
  int v8; // edx
  int v9; // ecx
  int v10; // r8d
  int v11; // r9d
  int v12; // edx
  int v13; // ecx
  int v14; // r8d
  int v15; // r9d
  int v16; // esi
  int v17; // edx
  int v18; // ecx
  int v19; // r8d
  int v20; // r9d
  int v21; // [rsp+0h] [rbp-70h] BYREF
  int i; // [rsp+4h] [rbp-6Ch]
  __int64 v23; // [rsp+8h] [rbp-68h] BYREF
  __int64 v24; // [rsp+10h] [rbp-60h]
  __int64 v25; // [rsp+18h] [rbp-58h]
  char v26[72]; // [rsp+20h] [rbp-50h] BYREF
  unsigned __int64 v27; // [rsp+68h] [rbp-8h]

  v27 = __readfsqword(0x28u);
  sub_401CB8();
  sub_401D1D();
  v24 = 0LL;
  for ( i = 0; i <= 23; ++i )
    v24 = (qword_4E82E0[i] + v24) % qword_4E83A0;
  while ( 1 )
  {
    sub_401E16();
    sub_40C050((unsigned int)"%d", (unsigned int)&v21, v0, v1, v2, v3, v21);
    if ( v21 == 3 )
    {
      sub_41AD00("Goodbye!");
      sub_40B390(0LL);
    }
    if ( v21 > 3 )
    {
LABEL_16:
      sub_41AD00("NO!!!");
    }
    else if ( v21 == 1 )
    {
      sub_40BEC0((unsigned int)"Please enter your answer: ", (unsigned int)&v21, v4, v5, v6, v7, 1);
      sub_40C050((unsigned int)"%lld", (unsigned int)&v23, v8, v9, v10, v11, v21);
      if ( v24 != v23 )
      {
        sub_41AD00("Wrong answer!");
        sub_40B390(1LL);
      }
      v25 = sub_41A910("./flag", "r");
      if ( !v25 )
      {
        sub_41AD00("Failed to open file...");
        sub_40B390(1LL);
      }
      sub_41A660(v26, 64LL, v25);
      sub_40BEC0((unsigned int)"Congratulations! Here is your flag: %s\n", (unsigned int)v26, v12, v13, v14, v15, v21);
      sub_41A370(v25);
    }
    else
    {
      if ( v21 != 2 )
        goto LABEL_16;
      v16 = sub_401BAE(40LL);
      sub_40BEC0((unsigned int)"Here is your hint: %lld\n", v16, v17, v18, v19, v20, v21);
    }
  }
}
```

数据初始化（`sub_401D1D`）：

```C
__int64 sub_401D1D()
{
  int v0; // r8d
  int v1; // r9d
  __int64 result; // rax
  int v3; // r8d
  int v4; // r9d
  int i; // [rsp+0h] [rbp-10h]
  int j; // [rsp+4h] [rbp-Ch]
  __int64 v7; // [rsp+8h] [rbp-8h]

  v7 = sub_41A910("./data", "r");
  if ( !v7 )
  {
    sub_41AD00("Failed to open file...");
    sub_40B390(1LL);
  }
  result = sub_40C120(v7, (unsigned int)"%lld", (unsigned int)&qword_4E83A0, (unsigned int)"%lld", v0, v1);
  for ( i = 0; i <= 23; ++i )
    result = sub_40C120(v7, (unsigned int)"%lld", (unsigned int)&qword_4E8220[i], (unsigned int)"%lld", v3, v4);
  for ( j = 0; j <= 23; ++j )
    result = sub_40C120(v7, (unsigned int)"%lld", (unsigned int)&qword_4E82E0[j], (unsigned int)"%lld", v3, v4);
  return result;
}
```

菜单（`sub_401E16`）：

```C
__int64 sub_401E16()
{
  sub_41AD00("===Flag Management System===");
  sub_41AD00("[1] Get Flag");
  sub_41AD00("[2] Get Hint");
  sub_41AD00("[3] Exit");
  return sub_40BEC0((__int64)">>> ");
}
```

计算hint的函数（`sub_401BAE`）：

```C
__int64 __fastcall sub_401BAE(__int64 a1)
{
  int i; // [rsp+10h] [rbp-10h]
  int j; // [rsp+14h] [rbp-Ch]
  __int64 v4; // [rsp+18h] [rbp-8h]

  if ( a1 > 60 )
  {
    sub_41AD00("Something went wrong...");
    sub_40B390(1LL);
  }
  v4 = 0LL;
  for ( i = 0; i <= 23; ++i )
    v4 = (v4 + sub_401B45(qword_4E82E0[i], qword_4E8220[i])) % qword_4E83A0;
  for ( j = 0; j <= 22; ++j )
    qword_4E82E0[j] = qword_4E82E0[j + 1];
  qword_4E8398 = v4;
  return v4 >> (60 - (unsigned __int8)a1);
}
```

以及龟速乘（`sub_401B45`）：

```C
__int64 __fastcall sub_401B45(__int64 a1, __int64 a2)
{
  __int64 v5; // [rsp+18h] [rbp-8h]

  v5 = 0LL;
  while ( a2 )
  {
    if ( (a2 & 1) != 0 )
      v5 = (v5 + a1) % qword_4E83A0;
    a1 = 2 * a1 % qword_4E83A0;
    a2 >>= 1;
  }
  return v5;
}
```

对上述组件进行分析可以知道其实这题实际上是一个一个模数$m$以及参数$c_0,c_1,\cdots,c_{23}$均未知的截断$\mathbb{Z}/(m)-\text{LFSR}$，获取的hint其实是这个截断$\mathbb{Z}/(m)-\text{LFSR}$输出的高40位，参考[*An Improved Method for Predicting Truncated Fibonacci LFSRs over Integer Residue Rings*](https://eprint.iacr.org/2025/2323.pdf)可以知道，这里其实是对应了$n=24,\alpha=40,\beta=20$的参数全未知的截断$\mathbb{Z}/(m)-\text{LFSR}$，通过文中提到的：
$$
\frac{1}{r}+\frac{1}{t}\le\frac{\alpha}{n\log{m}}
$$
我们令$r=t$，则有：
$$
\frac{1}{r}+\frac{1}{t}=\frac{2}{r}\le\frac{\alpha}{n\log{m}}\approx\frac{40}{24\times 60}=\frac{1}{36}
$$
可以求得$r=t>72$，在$72$的基础上加上$15%$的冗余量可以知道我们应该取$r=t=\lceil 72\times1.15\rceil=83$，所以保守起见，我们需要至少取$N=r+t+1=167$个输出进行求解，设这167个输出分别为$a_0,a_1,\cdots,a_{166}$，首先构造格：
$$
\left(\begin{matrix}
2^{\alpha}&&&&&&&&\\
&2^{\alpha}&&&&&&&\\
&&\ddots&&&&&&\\
&&&2^{\alpha}&&&&&\\
a_0&a_1&\cdots&a_{t-1}&1&&&\\
a_1&a_2&\cdots&a_{t}&&1&&\\
\vdots&\vdots&&\vdots&&&\ddots&\\
a_{r-1}&a_{r}&\cdots&a_{166}&&&&1\\
\end{matrix}\right)
$$
进行规约可以得到若干条短向量，取前三条非零短向量的后$r$个分量作为参数来组成$\mathbb{Z}_m$上的多项式$F_1(x),F_2(x),F_3(x)$，对这三个多项式两两组合取结式之后求最大公约数即可得到一个可以被模数$m$整除的数，分解并提取即可得到模数$m$，在获得模数$m$之后问题就变成了已知$m$但是参数未知的截断$\mathbb{Z}/(m)-\text{LFSR}$了，令$K=2^{\beta}$，此时我们构造格：
$$
\left(\begin{matrix}
m&&&&&&&&\\
&m&&&&&&&\\
&&\ddots&&&&&&\\
&&&m&&&&&\\
Ka_0&Ka_1&\cdots&Ka_{t-1}&K&&&\\
Ka_1&Ka_2&\cdots&Ka_{t}&&K&&\\
\vdots&\vdots&&\vdots&&&\ddots&\\
Ka_{r-1}&Ka_{r}&\cdots&Ka_{166}&&&&K\\
\end{matrix}\right)
$$
取前若干条短向量的后$r$个分量作为参数来组成$\mathbb{Z}_m$上的多项式，对这些多项式求最大公约式就可以得到一个如下形式的度为24的不可约多项式：
$$
f(x)=x^{24}-(c_0+c_1x+c_2x^2+\cdots+c_{23}x^{23})
$$
最后结合递推公式$x_{s+24}\equiv c_0x_{s}+c_1x_{s+1}+\cdots+c_{23}x_{s+23}\pmod{m}$，以获得的LFSR输出中未知的低位作为未知量进行多元coppersmith就可以恢复LFSR的状态，从而倒推出种子、获得flag了：

```Python
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

```

### 附：源码

`main.sage`（附件中未给出）

```Python
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
```

`src.c`（编译之后得到附件中的`chall`）

```C
#include <stdio.h>
#include <stdlib.h>

#define ll long long

ll c[24];
ll state[24];
ll p;

ll mul(ll a, ll b)
{
    ll res = 0;
    while (b)
    {
        if (b & 1)
        {
            res = (res + a) % p;
        }
        a = (a << 1) % p;
        b >>= 1;
    }
    return res;
}

ll RAND(ll n)
{
    if(n > 60)
    {
        printf("Something went wrong...\n");
        exit(1);
    }

    ll tmp = 0;

    for (int i = 0; i < 24; i++)
    {
        tmp = (tmp + mul(state[i], c[i])) % p;
    }

    for (int i = 0; i < 23; i++)
    {
        state[i] = state[i + 1];
    }

    state[23] = tmp;

    return tmp >> (60 - n);

}

void init()
{
    setvbuf(stdin, NULL, _IONBF, 0);
    setvbuf(stdout, NULL, _IONBF, 0);
    setvbuf(stderr, NULL, _IONBF, 0);
}

void init_param()
{
    FILE *file = fopen("./data", "r");
    if (file == NULL)
    {
        printf("Failed to open file...\n");
        exit(1);
    }
    
    fscanf(file, "%lld", &p);
    for (int i = 0; i < 24; i++)
    {
        fscanf(file, "%lld", &c[i]);
    }

    for (int i = 0; i < 24; i++)
    {
        fscanf(file, "%lld", &state[i]);
    }

}

void menu()
{
    printf("===Flag Management System===\n");
    printf("[1] Get Flag\n");
    printf("[2] Get Hint\n");
    printf("[3] Exit\n");

    printf(">>> ");
}

int main()
{
    init();
    init_param();

    ll ans = 0;
    for (int i = 0; i < 24; i++)
    {
        ans = (ans + state[i]) % p;
    }

    while (1)
    {
        menu();
        int choice;
        scanf("%d", &choice);

        switch (choice)
        {
            case 1:
                printf("Please enter your answer: ");
                ll user_ans;
                scanf("%lld", &user_ans);
                if (user_ans == ans)
                {
                    FILE *file = fopen("./flag", "r");
                    if (file == NULL)
                    {
                        printf("Failed to open file...\n");
                        exit(1);
                    }
                    char flag[64];
                    fgets(flag, sizeof(flag), file);
                    printf("Congratulations! Here is your flag: %s\n", flag);
                    fclose(file);
                }
                else
                {
                    printf("Wrong answer!\n");
                    exit(1);
                }
                break;
            case 2:
                printf("Here is your hint: %lld\n", RAND(40));
                break;
            case 3:
                printf("Goodbye!\n");
                exit(0);
            default:
                printf("NO!!!\n");
                break;
        }
    }

    return 0;
}
```


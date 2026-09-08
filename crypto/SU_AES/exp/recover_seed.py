from random import Random

W=0xffffffff;n=624;m=397;A=0x9908B0DF
U=11;S=7;B=0x9D2C5680;T=15;C=0xEFC60000;L=18
LM=(1<<31)-1

def recover_seed(t, lim=5000):
    def u32(x): return x&W
    def un_r(y,sh):
        x=y&W
        for _ in range(5): x=y^(x>>sh)
        return x&W
    def un_l(y,sh,msk):
        x=y&W
        for _ in range(5): x=y^((u32(x<<sh))&msk)
        return x&W
    def untemper(y):
        y=u32(y)
        y=un_r(y,L)
        y=un_l(y,T,C)
        y=un_l(y,S,B)
        y=un_r(y,U)
        return y
    def g1(p): return u32((p^(p>>30))*0x19660D)
    def g2(p): return u32((p^(p>>30))*0x5D588B65)

    def base_mt():
        mt=[0]*n
        mt[0]=0x12BD6AA
        for i in range(1,n): mt[i]=u32(0x6C078965*(mt[i-1]^(mt[i-1]>>30))+i)
        return mt

    def inv_loop2(mt2):
        x=mt2[:]; x[0]=x[623]
        mt1=[0]*n
        mt1_1=u32(x[1]+1)^g2(x[0])
        mt1[1]=mt1_1
        for i in range(623,2,-1): mt1[i]=u32(x[i]+i)^g2(x[i-1])
        mt1[2]=u32(x[2]+2)^g2(mt1_1)
        mt1[0]=mt1[623]
        return mt1

    def keys_len_624(mt1,last=1):
        mt=base_mt()
        keys=[0]*n
        x1=u32(mt1[1]-last-623)^g1(mt1[623])
        keys[0]=u32(x1-(mt[1]^g1(mt[0])))
        mt[1]=x1
        for j in range(1,623):
            i=j+1
            keys[j]=u32(mt1[i]-(mt[i]^g1(mt[i-1]))-j)
            mt[i]=mt1[i]
        mt[0]=mt[623]
        keys[623]=u32(mt1[1]-(mt[1]^g1(mt[0]))-623)
        return keys if keys[623]==(last&W) else None

    def seed_from_keys(keys):
        s=0
        for i,w in enumerate(keys): s|=(w&W)<<(32*i)
        return s

    def inv_trans(f):
        f=u32(f)
        bit0=(f>>31)&1
        v=f^(A if bit0 else 0)
        hi=(v>>30)&1
        lo=((v&((1<<30)-1))<<1)|bit0
        return hi, lo&LM

    def untwist_inplace(new, lo0=0):
        hi=[0]*n
        lo=[0]*n
        lo[0]=lo0&LM

        for i in range(227,623):
            h,ln=inv_trans(new[i]^new[i-227])
            hi[i]=h; lo[i+1]=ln

        low=new[0]&LM
        f=u32(new[623]^new[396])
        bit0=low&1
        if ((f>>31)&1)!=bit0: raise ValueError
        v=f^(A if bit0 else 0)
        if (v&((1<<30)-1))!=((low>>1)&((1<<30)-1)): raise ValueError
        hi[623]=(v>>30)&1

        old=[0]*n
        for i in range(397,624):
            old[i]=((hi[i]&1)<<31)|(lo[i]&LM)

        for i in range(226,-1,-1):
            h,ln=inv_trans((new[i]^old[i+397])&W)
            hi[i]=h; lo[i+1]=ln

        old[0]=((hi[0]&1)<<31)|(lo[0]&LM)
        for i in range(1,397):
            old[i]=((hi[i]&1)<<31)|(lo[i]&LM)

        return [u32(x) for x in old]

    def xorshift32(x):
        x&=W
        while 1:
            x^=(x<<13)&W
            x^=(x>>17)&W
            x^=(x<<5)&W
            yield x&W

    def build_new_state(t, s32, hi623):
        new=[0]*n
        g=xorshift32(s32)
        for i in range(0,512,2):
            k=i//2
            y=((t[k]&255)<<24)|(next(g)&0xFFFFFF)
            new[i]=untemper(y)
            new[i+1]=next(g)&W
        for i in range(512,623):
            new[i]=next(g)&W
        low=new[0]&LM
        tr=u32(((hi623&1)<<30)|(low>>1))
        if low&1: tr^=A
        new[623]=u32(new[396]^tr)
        return new

    pop=list(range(256))
    for hi623 in (1,0):
        for s32 in range(1,lim+1):
            new=build_new_state(t,s32,hi623)
            try:
                old=untwist_inplace(new,0)
            except ValueError:
                continue
            if old[0]!=0x80000000:
                continue
            mt1=inv_loop2(old)
            keys=keys_len_624(mt1,1)
            if keys is None:
                continue
            seed=seed_from_keys(keys)
            if Random(seed).choices(pop,k=256)==t:
                return seed
    raise RuntimeError("recover_seed failed; try larger lim")

if __name__ == '__main__':
    t = [3] * 256
    seed = recover_seed(t)
    print("seed (hex) =", hex(seed))
    print("check =", Random(seed).choices([*range(256)], k=256) == t)
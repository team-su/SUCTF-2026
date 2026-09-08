# WriteUp

请求题目地址会获得这样的信息：

```
{"algo":"AES-128-CBC","iv_b64":"Pseby9i+d9kWTzFF0c/csQ==","ciphertext_b64":"VxbzLxJpNywy+6ZOlzgDxvoRst1DcVtUVAsSIj4Asjg1vagbySMQdfREuZGJ6tIA","key_derivation":"key = SHA256(LLM_output)[:16]","llm":{"provider":"z.ai","model":"GLM-4-Flash","temperature":0.28,"system_prompt":"You are a password generator.\nOutput ONE password only.\nFormat strictly: pw-xxxxxxxx where x are letters.\nNo explanation, no quotes, no punctuation.","user_prompt":"Generate the password now."}}
```

可以看出用AES-128-CBC对flag进行加密了，而key是利用LLM的输出来做hash的，这里题目直接把大模型调用接口的系统提示词给了；在相同提示词，固定温度的条件下，LLM是一个概率预测模型，加之温度0.28很小，所以直接把这些信息整合在一起调用智谱的api来爆密钥即可。（为了给大家省钱，专门选取的免费模型）

```
import os
import base64
import hashlib
import requests
import time
from cryptography.hazmat.primitives.ciphers import Cipher, algorithms, modes
from cryptography.hazmat.primitives.padding import PKCS7

# 配置信息保持不变
CHALLENGE = {
    "algo": "AES-128-CBC",
    "iv_b64": "Ird6bRGzi97DHNGH9rtu+Q==",
    "ciphertext_b64": "eOIgQ+QAaqnwBtBBzcHI5R1UZznLvJ2HsLf1vEcKovH2gTb8AbF6yntsWmVHfmz+",
    "key_derivation": "key = SHA256(LLM_output)[:16]",
    "llm": {
        "provider": "z.ai",
        "model": "GLM-4-Flash",
        "temperature": 0.28,
        "system_prompt": "You are a password generator.\nOutput ONE password only.\nFormat strictly: pw-xxxxxxxx where x are letters.\nNo explanation, no quotes, no punctuation.",
        "user_prompt": "Generate the password now."
    }
}

ZHIPU_API_KEY = os.environ.get("ZHIPU_API_KEY", "cabc668d813c48eda37c9ea0e89880e9.HgqNv8z2q3OuLsFF")
ZHIPU_BASE_URL = os.environ.get("ZHIPU_BASE_URL", "https://api.z.ai/api/paas/v4/chat/completions")

def call_glm(system_prompt: str, user_prompt: str, model: str, temperature: float) -> str:
    if not ZHIPU_API_KEY:
        raise RuntimeError("Set env var ZHIPU_API_KEY")

    payload = {
        "model": model,
        "messages": [
            {"role": "system", "content": system_prompt},
            {"role": "user", "content": user_prompt},
        ],
        "temperature": temperature,
        "stream": False,
    }
    headers = {
        "Authorization": f"Bearer {ZHIPU_API_KEY}",
        "Content-Type": "application/json",
    }
    r = requests.post(ZHIPU_BASE_URL, json=payload, headers=headers, timeout=30)
    r.raise_for_status()
    data = r.json()
    return data["choices"][0]["message"]["content"].strip()

def derive_aes128_key(llm_output: str) -> bytes:
    return hashlib.sha256(llm_output.encode("utf-8")).digest()[:16]

def aes_cbc_decrypt(key: bytes, iv: bytes, ciphertext: bytes) -> bytes:
    cipher = Cipher(algorithms.AES(key), modes.CBC(iv))
    dec = cipher.decryptor()
    padded = dec.update(ciphertext) + dec.finalize()
    
    # 关键点：如果 key 错误，这里的 unpad 会抛出 ValueError
    unpadder = PKCS7(128).unpadder()
    return unpadder.update(padded) + unpadder.finalize()

def main():
    iv = base64.b64decode(CHALLENGE["iv_b64"])
    ct = base64.b64decode(CHALLENGE["ciphertext_b64"])

    attempts = 0
    print("[*] 开始尝试解密...")

    while True:
        attempts += 1
        try:
            # 1) 请求 LLM
            llm_out = call_glm(
                system_prompt=CHALLENGE["llm"]["system_prompt"],
                user_prompt=CHALLENGE["llm"]["user_prompt"],
                model=CHALLENGE["llm"]["model"],
                temperature=CHALLENGE["llm"]["temperature"],
            )
            
            # 2) 派生 Key
            key = derive_aes128_key(llm_out)
            
            # 3) 尝试解密
            pt = aes_cbc_decrypt(key, iv, ct)
            
            # 如果运行到这里没有报错，说明解密成功（Padding 正确）
            print(f"\n[+] 成功！尝试次数: {attempts}")
            print(f"[*] LLM 输出内容: {llm_out}")
            print(f"[*] 最终解密明文: {pt.decode('utf-8')}")
            break

        except ValueError:
            
            print(f"[-] 尝试 {attempts}: LLM 输出 '{llm_out}' 导致解密失败 (Padding Error)", end='\r')
        except Exception as e:
            print(f"\n[!] 发生意外错误: {e}")
            time.sleep(1) # 避免 API 限制

if __name__ == "__main__":
    main()
```


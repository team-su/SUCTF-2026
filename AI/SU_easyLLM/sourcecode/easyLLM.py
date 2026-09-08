import os, base64, hashlib, secrets, requests
from fastapi import FastAPI
from fastapi.responses import JSONResponse
from cryptography.hazmat.primitives.ciphers import Cipher, algorithms, modes
from cryptography.hazmat.primitives.padding import PKCS7

app = FastAPI()

ZHIPU_API_KEY = os.environ.get("ZHIPU_API_KEY", "")
ZHIPU_BASE_URL = os.environ.get("ZHIPU_BASE_URL", "https://api.z.ai/api/paas/v4/chat/completions")
ZHIPU_MODEL = os.environ.get("ZHIPU_MODEL", "GLM-4-Flash")

FLAG = os.environ.get("FLAG", "SUCTF{LLM_w1ll_ch4nge_ev3rything}")

SYSTEM_PROMPT = (
    "You are a password generator.\n"
    "Output ONE password only.\n"
    "Format strictly: pw-xxxxxxxx where x are letters.\n"
    "No explanation, no quotes, no punctuation."
)

USER_PROMPT = "Generate the password now."

TEMPERATURE = 0.28

def call_glm_once() -> str:
    if not ZHIPU_API_KEY:
        raise RuntimeError("ZHIPU_API_KEY not set")

    payload = {
        "model": ZHIPU_MODEL,
        "messages": [
            {"role": "system", "content": SYSTEM_PROMPT},
            {"role": "user", "content": USER_PROMPT},
        ],
        "temperature": TEMPERATURE,
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

def derive_key_from_llm(pw: str, key_len: int = 16) -> bytes:

    digest = hashlib.sha256(pw.encode("utf-8")).digest()
    return digest[:key_len]

def aes_cbc_encrypt(key: bytes, iv: bytes, plaintext: bytes) -> bytes:
    padder = PKCS7(128).padder()
    padded = padder.update(plaintext) + padder.finalize()
    cipher = Cipher(algorithms.AES(key), modes.CBC(iv))
    enc = cipher.encryptor()
    return enc.update(padded) + enc.finalize()

LLM_PASSWORD = call_glm_once()
KEY = derive_key_from_llm(LLM_PASSWORD, key_len=16)
IV = secrets.token_bytes(16)
CIPHERTEXT = aes_cbc_encrypt(KEY, IV, FLAG.encode("utf-8"))

@app.get("/")
def challenge():
    return JSONResponse({
        "algo": "AES-128-CBC",
        "iv_b64": base64.b64encode(IV).decode(),
        "ciphertext_b64": base64.b64encode(CIPHERTEXT).decode(),
        "key_derivation": "key = SHA256(LLM_output)[:16]",
        "llm": {
            "provider": "z.ai",
            "model": ZHIPU_MODEL,
            "temperature": TEMPERATURE,
            "system_prompt": SYSTEM_PROMPT,
            "user_prompt": USER_PROMPT,
        }
    })
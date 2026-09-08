from os import urandom
from Crypto.Util.Padding import pad
from AES import AES
import signal
from secret import flag

def handler(_signum, _frame):
  raise TimeoutError("⏰")

seed = int.from_bytes(urandom(16))
key = int.from_bytes(urandom(16))
aes = AES(key, seed)
print(f'[+] Here is your flag ciphertext (in hex): {aes.encrypt_ecb(pad(flag, 16)).hex()}')

menu = """
--- Menu ---
[1] change the S-box
[2] encrypt a message
[3] reset aes
[4] exit
"""

signal.alarm(30)

for _ in range(300):
    print(menu)
    match x:= input('[x] > '):
        case '1':
            s = int(input('[x] your seed: ') or 0, 16) or None
            k = int(input('[x] your key: ') or 0, 16) or None
            aes.change(s, k)
            print('[+] changed!')
        case '2':
            msg = bytes.fromhex(input('[x] your message: '))
            print(f'[+] Here is your ciphertext (in hex): {aes.encrypt_ecb(pad(msg, 16)).hex()}')
        case '3':
            aes = AES(key, seed)
            print('[+] reset!')
        case '4':
            print('[+] Goodbye!')
            exit()
        case _:
            print('[-] Invalid option!')
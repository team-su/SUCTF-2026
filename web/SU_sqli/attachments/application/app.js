(() => {
  const _s = [
    "L2FwaS9zaWdu",
    "L2FwaS9xdWVyeQ==",
    "UE9TVA==",
    "Y29udGVudC10eXBl",
    "YXBwbGljYXRpb24vanNvbg==",
    "Y3J5cHRvMS53YXNt",
    "Y3J5cHRvMi53YXNt"
  ];
  const _d = (i) => atob(_s[i]);

  const $ = (id) => document.getElementById(id);
  const out = $("out");
  const err = $("err");

  let wasmReady;

  function b64UrlToBytes(s) {
    let t = s.replace(/-/g, "+").replace(/_/g, "/");
    while (t.length % 4) t += "=";
    const bin = atob(t);
    const out = new Uint8Array(bin.length);
    for (let i = 0; i < bin.length; i++) out[i] = bin.charCodeAt(i);
    return out;
  }

  function bytesToB64Url(bytes) {
    let bin = "";
    for (let i = 0; i < bytes.length; i++) bin += String.fromCharCode(bytes[i]);
    return btoa(bin).replace(/\+/g, "-").replace(/\//g, "_").replace(/=+$/, "");
  }

  function rotl32(x, r) {
    return ((x << r) | (x >>> (32 - r))) >>> 0;
  }

  function rotr32(x, r) {
    return ((x >>> r) | (x << (32 - r))) >>> 0;
  }

  const rotScr = [1, 5, 9, 13, 17, 3, 11, 19];

  function maskBytes(nonceB64, ts) {
    const nb = b64UrlToBytes(nonceB64);
    let s = 0 >>> 0;
    for (let i = 0; i < nb.length; i++) {
      s = (Math.imul(s, 131) + nb[i]) >>> 0;
    }
    const hi = Math.floor(ts / 0x100000000);
    s = (s ^ (ts >>> 0) ^ (hi >>> 0)) >>> 0;
    const out = new Uint8Array(32);
    for (let i = 0; i < 32; i++) {
      s ^= (s << 13) >>> 0;
      s ^= s >>> 17;
      s ^= (s << 5) >>> 0;
      out[i] = s & 0xff;
    }
    return out;
  }

  function unscramble(pre, nonceB64, ts) {
    const buf = b64UrlToBytes(pre);
    if (buf.length !== 32) throw new Error("prep");
    for (let i = 0; i < 8; i++) {
      const o = i * 4;
      let w =
        (buf[o] | (buf[o + 1] << 8) | (buf[o + 2] << 16) | (buf[o + 3] << 24)) >>> 0;
      w = rotr32(w, rotScr[i]);
      buf[o] = w & 0xff;
      buf[o + 1] = (w >>> 8) & 0xff;
      buf[o + 2] = (w >>> 16) & 0xff;
      buf[o + 3] = (w >>> 24) & 0xff;
    }
    const mask = maskBytes(nonceB64, ts);
    for (let i = 0; i < 32; i++) buf[i] ^= mask[i];
    return buf;
  }

  function probeMask(probe, ts) {
    let s = 0 >>> 0;
    for (let i = 0; i < probe.length; i++) {
      s = (Math.imul(s, 33) + probe.charCodeAt(i)) >>> 0;
    }
    const hi = Math.floor(ts / 0x100000000);
    s = (s ^ (ts >>> 0) ^ (hi >>> 0)) >>> 0;
    const out = new Uint8Array(32);
    for (let i = 0; i < 32; i++) {
      s = (Math.imul(s, 1103515245) + 12345) >>> 0;
      out[i] = (s >>> 16) & 0xff;
    }
    return out;
  }

  function mixSecret(buf, probe, ts) {
    const mask = probeMask(probe, ts);
    if (mask[0] & 1) {
      for (let i = 0; i < 32; i += 2) {
        const t = buf[i];
        buf[i] = buf[i + 1];
        buf[i + 1] = t;
      }
    }
    if (mask[1] & 2) {
      for (let i = 0; i < 8; i++) {
        const o = i * 4;
        let w =
          (buf[o] | (buf[o + 1] << 8) | (buf[o + 2] << 16) | (buf[o + 3] << 24)) >>> 0;
        w = rotl32(w, 3);
        buf[o] = w & 0xff;
        buf[o + 1] = (w >>> 8) & 0xff;
        buf[o + 2] = (w >>> 16) & 0xff;
        buf[o + 3] = (w >>> 24) & 0xff;
      }
    }
    for (let i = 0; i < 32; i++) buf[i] ^= mask[i];
    return buf;
  }

  async function loadWasm() {
    if (wasmReady) return wasmReady;
    wasmReady = (async () => {
      const go1 = new Go();
      const resp1 = await fetch("/static/" + _d(5));
      const buf1 = await resp1.arrayBuffer();
      const { instance: inst1 } = await WebAssembly.instantiate(buf1, go1.importObject);
      go1.run(inst1);

      const go2 = new Go();
      const resp2 = await fetch("/static/" + _d(6));
      const buf2 = await resp2.arrayBuffer();
      const { instance: inst2 } = await WebAssembly.instantiate(buf2, go2.importObject);
      go2.run(inst2);

      for (let i = 0; i < 100; i++) {
        if (typeof globalThis.__suPrep === "function" && typeof globalThis.__suFinish === "function") return true;
        await new Promise((r) => setTimeout(r, 10));
      }
      throw new Error("wasm init");
    })();
    return wasmReady;
  }

  async function getSignMaterial() {
    const res = await fetch(_d(0), { method: "GET" });
    const data = await res.json();
    if (!data.ok) throw new Error(data.error || "sign");
    return data.data;
  }

  async function doQuery() {
    err.textContent = "";
    out.textContent = "";
    const q = $("q").value || "";
    if (!q) {
      err.textContent = "empty";
      return;
    }
    try {
      await loadWasm();
      const material = await getSignMaterial();
      const ua = navigator.userAgent || "";
      const uaData = navigator.userAgentData;
      const brands = uaData && uaData.brands ? uaData.brands.map((b) => b.brand + ":" + b.version).join(",") : "";
      const tz = (() => {
        try {
          return Intl.DateTimeFormat().resolvedOptions().timeZone || "";
        } catch {
          return "";
        }
      })();
      const intl = (() => {
        try {
          return Intl.DateTimeFormat().resolvedOptions().locale ? "1" : "0";
        } catch {
          return "0";
        }
      })();
      const wd = navigator.webdriver ? "1" : "0";
      const probe = "wd=" + wd + ";tz=" + tz + ";b=" + brands + ";intl=" + intl;

      const pre = globalThis.__suPrep(
        _d(2),
        _d(1),
        q,
        material.nonce,
        String(material.ts),
        material.seed,
        material.salt,
        ua,
        probe
      );
      if (!pre) throw new Error("prep");
      const secret2 = unscramble(pre, material.nonce, material.ts);
      const mixed = mixSecret(secret2, probe, material.ts);
      const sig = globalThis.__suFinish(
        _d(2),
        _d(1),
        q,
        material.nonce,
        String(material.ts),
        bytesToB64Url(mixed),
        probe
      );

      const res = await fetch(_d(1), {
        method: _d(2),
        headers: { [_d(3)]: _d(4) },
        body: JSON.stringify({ q, nonce: material.nonce, ts: material.ts, sign: sig })
      });
      const data = await res.json();
      if (!data.ok) {
        err.textContent = data.error || "error";
        return;
      }
      out.textContent = JSON.stringify(data.data, null, 2);
    } catch (e) {
      err.textContent = String(e.message || e);
    }
  }

  window.addEventListener("DOMContentLoaded", () => {
    $("run").addEventListener("click", doQuery);
    $("q").addEventListener("keydown", (e) => {
      if (e.key === "Enter") doQuery();
    });
  });
})();

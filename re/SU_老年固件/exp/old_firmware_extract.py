#!/usr/bin/env python3
"""Recover the layered firmware artifacts from SU_老年固件.

The sample is XOR-encoded with 0x7f, then contains multiple xz streams.
Some of those xz streams unwrap into SMALLFW containers that in turn hold
nested xz-compressed ZIP archives. This script extracts the real materials
without inventing any missing source tree.
"""

from __future__ import annotations

import argparse
import io
import json
import lzma
import zipfile
from pathlib import Path


XOR_KEY = 0x7F
XZ_MAGIC = b"\xfd7zXZ\x00"


def xor_decode(data: bytes) -> bytes:
    return bytes(b ^ XOR_KEY for b in data)


def iter_magic_offsets(data: bytes, magic: bytes) -> list[int]:
    offsets: list[int] = []
    start = 0
    while True:
        idx = data.find(magic, start)
        if idx < 0:
            break
        offsets.append(idx)
        start = idx + 1
    return offsets


def decompress_xz(data: bytes, offset: int) -> tuple[bytes, int, bool]:
    dec = lzma.LZMADecompressor(format=lzma.FORMAT_XZ)
    out = dec.decompress(data[offset:])
    return out, len(dec.unused_data), dec.eof


def safe_read_zip(blob: bytes) -> dict[str, bytes] | None:
    bio = io.BytesIO(blob)
    if not zipfile.is_zipfile(bio):
        return None
    bio.seek(0)
    out: dict[str, bytes] = {}
    with zipfile.ZipFile(bio) as zf:
        for name in zf.namelist():
            out[name] = zf.read(name)
    return out


def dump_tree(blob: bytes, outdir: Path, prefix: str, summary: list[dict[str, object]]) -> None:
    for idx, off in enumerate(iter_magic_offsets(blob, XZ_MAGIC)):
        payload, unused, eof = decompress_xz(blob, off)
        name = f"{prefix}_xz_{idx:02d}_off_{off:06x}.bin"
        path = outdir / name
        path.write_bytes(payload)
        summary.append(
            {
                "kind": "xz",
                "offset": off,
                "name": name,
                "size": len(payload),
                "unused": unused,
                "eof": eof,
            }
        )

        nested = safe_read_zip(payload)
        if nested is not None:
            zip_dir = outdir / f"{prefix}_xz_{idx:02d}_off_{off:06x}_zip"
            zip_dir.mkdir(parents=True, exist_ok=True)
            files = []
            for entry_name, entry_data in nested.items():
                (zip_dir / entry_name).write_bytes(entry_data)
                files.append(entry_name)
            summary.append(
                {
                    "kind": "zip",
                    "offset": off,
                    "name": zip_dir.name,
                    "files": files,
                }
            )

        # One level of nesting is enough for this challenge sample.
        for sub_idx, sub_off in enumerate(iter_magic_offsets(payload, XZ_MAGIC)):
            if sub_off == 0:
                continue
            sub_payload, sub_unused, sub_eof = decompress_xz(payload, sub_off)
            sub_name = f"{prefix}_xz_{idx:02d}_subxz_{sub_idx:02d}_off_{sub_off:06x}.bin"
            sub_path = outdir / sub_name
            sub_path.write_bytes(sub_payload)
            summary.append(
                {
                    "kind": "xz-nested",
                    "offset": sub_off,
                    "name": sub_name,
                    "size": len(sub_payload),
                    "unused": sub_unused,
                    "eof": sub_eof,
                }
            )

            sub_zip = safe_read_zip(sub_payload)
            if sub_zip is not None:
                sub_zip_dir = outdir / f"{prefix}_xz_{idx:02d}_subxz_{sub_idx:02d}_off_{sub_off:06x}_zip"
                sub_zip_dir.mkdir(parents=True, exist_ok=True)
                files = []
                for entry_name, entry_data in sub_zip.items():
                    (sub_zip_dir / entry_name).write_bytes(entry_data)
                    files.append(entry_name)
                summary.append(
                    {
                        "kind": "zip-nested",
                        "offset": sub_off,
                        "name": sub_zip_dir.name,
                        "files": files,
                    }
                )


def main() -> int:
    ap = argparse.ArgumentParser(description=__doc__)
    ap.add_argument(
        "--attachment",
        type=Path,
        default=Path(__file__).resolve().parents[1] / "attachments" / "attachment.zip",
        help="path to attachment.zip",
    )
    ap.add_argument(
        "--out",
        type=Path,
        default=Path(__file__).resolve().with_name("recovered"),
        help="directory where recovered blobs will be written",
    )
    args = ap.parse_args()

    args.out.mkdir(parents=True, exist_ok=True)
    with zipfile.ZipFile(args.attachment) as zf:
        raw = zf.read("attachment/old.bin")

    decoded = xor_decode(raw)
    (args.out / "old.xor7f.bin").write_bytes(decoded)

    summary: list[dict[str, object]] = []
    top_hits = iter_magic_offsets(decoded, XZ_MAGIC)
    summary.append({"kind": "input", "size": len(raw), "decoded_size": len(decoded), "top_level_xz": top_hits})

    dump_tree(decoded, args.out, "top", summary)

    (args.out / "summary.json").write_text(
        json.dumps(summary, ensure_ascii=False, indent=2) + "\n",
        encoding="utf-8",
    )

    print(json.dumps(summary, ensure_ascii=False, indent=2))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

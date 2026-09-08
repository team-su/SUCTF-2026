# env

This directory contains the boot environment strings recovered from the decoded
`old.bin` sample.

Source evidence:

- `bootcmd=run ctf_boot`
- `autoboot_delay=3`
- `baudrate=115200`
- `serverip=192.168.1.100`
- `ipaddr=192.168.1.10`

These values are extracted from the decrypted firmware header region and are
kept as a faithful reconstruction only. No synthetic runtime environment is
introduced here.

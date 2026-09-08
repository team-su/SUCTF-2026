#!/bin/bash
LOG_FILE="/tmp/restart.log"
echo "[$(date)] Restart script triggered" > $LOG_FILE
echo "[$(date)] Cleaning up processes..." >> $LOG_FILE
/usr/bin/pkill -9 mainproc 2>/dev/null
/usr/bin/pkill -9 vpn.cgi 2>/dev/null
/usr/bin/pkill -9 ping.cgi 2>/dev/null
/usr/bin/pkill -9 wifi.cgi 2>/dev/null
/usr/bin/pkill -9 list.cgi 2>/dev/null
echo "[$(date)] Cleaning up IPC queues..." >> $LOG_FILE
/usr/bin/ipcrm -M 0x33445566 2>/dev/null
/usr/bin/ipcrm -M 0x66554433 2>/dev/null
sleep 1
echo "HTTP/1.1 200 OK"
echo "Content-Type: application/json"
echo ""

echo "[$(date)] Starting mainproc..." >> $LOG_FILE
cd /app
./mainproc -d

sleep 1
if pgrep -x "mainproc" > /dev/null; then
    echo "[$(date)] Success: mainproc is running" >> $LOG_FILE
    echo '{"status": "success", "message": "mainproc restarted successfully."}'
else
    echo "[$(date)] Error: mainproc failed to start" >> $LOG_FILE
    LD_DEBUG=libs ./mainproc -v 2>>$LOG_FILE
    echo '{"status": "error", "message": "Failed to restart mainproc. Check /tmp/restart.log inside container."}'
fi

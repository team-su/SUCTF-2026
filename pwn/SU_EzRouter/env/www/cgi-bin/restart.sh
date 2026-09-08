#!/bin/bash

# 定义日志文件用于调试
LOG_FILE="/tmp/restart.log"
echo "[$(date)] Restart script triggered" > $LOG_FILE

# 1. 彻底清理旧的进程
# 注意：避免使用 pkill -f ".cgi" 以免杀死当前脚本进程
echo "[$(date)] Cleaning up processes..." >> $LOG_FILE
/usr/bin/pkill -9 mainproc 2>/dev/null
/usr/bin/pkill -9 vpn.cgi 2>/dev/null
/usr/bin/pkill -9 ping.cgi 2>/dev/null
/usr/bin/pkill -9 wifi.cgi 2>/dev/null
/usr/bin/pkill -9 list.cgi 2>/dev/null

# 2. 清理消息队列
echo "[$(date)] Cleaning up IPC queues..." >> $LOG_FILE
/usr/bin/ipcrm -M 0x33445566 2>/dev/null
/usr/bin/ipcrm -M 0x66554433 2>/dev/null

# 等待一秒确保资源释放
sleep 1

# 现在可以安全地输出 HTTP 头了
echo "HTTP/1.1 200 OK"
echo "Content-Type: application/json"
echo ""

# 3. 启动新的进程
echo "[$(date)] Starting mainproc..." >> $LOG_FILE
cd /app
./mainproc -d

# 检查进程是否启动成功
sleep 1
if pgrep -x "mainproc" > /dev/null; then
    echo "[$(date)] Success: mainproc is running" >> $LOG_FILE
    echo '{"status": "success", "message": "mainproc restarted successfully."}'
else
    echo "[$(date)] Error: mainproc failed to start" >> $LOG_FILE
    # 尝试捕获错误原因
    LD_DEBUG=libs ./mainproc -v 2>>$LOG_FILE
    echo '{"status": "error", "message": "Failed to restart mainproc. Check /tmp/restart.log inside container."}'
fi

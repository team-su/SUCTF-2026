#!/bin/bash

rm -f /start.sh

# 启动mysql
service mysql restart
# 等待 MySQL 启动
sleep 5

# 初始化页面
rm -rf /var/www/html/index.html

echo $FLAG > /flag
chmod 600 /flag

source /etc/apache2/envvars

echo "Running..." &

tail -F /var/log/apache2/* &

# 启动 Apache 在后台
apache2 -D FOREGROUND &

# 等待 Apache 进程（保证容器持续运行）
wait
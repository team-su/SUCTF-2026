FROM docker.m.daocloud.io/library/ubuntu:bionic-20210118

# 设置时区，避免交互式提示
ENV TZ=Asia/Shanghai
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# 更新系统软件包
RUN apt update

# 安装 Apache 和 PHP 7.2 及必要的扩展 MySQL
RUN apt install -y apache2 php7.2 libapache2-mod-php7.2 \
    php7.2-cli php7.2-common php7.2-mysql php7.2-xml php7.2-mbstring \
    php7.2-curl php7.2-zip php7.2-gd php7.2-intl php7.2-bcmath \
    net-tools vim mysql-server \
    build-essential zlib1g-dev libssl-dev libffi-dev \
    libsqlite3-dev libbz2-dev libreadline-dev liblzma-dev \
    libncurses5-dev libgdbm-dev libnss3-dev libmpdec-dev wget

# Apache 初始化
RUN a2enmod rewrite

# 复制数据库配置文件
COPY ./env/data/db.sql /var/db.sql

COPY ./env/data/flag.txt /var/www/html/flag.txt

# 启动 MySQL 并初始化数据库
RUN service mysql start && \
    sleep 10s && \
    mysql -uroot -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '123456';FLUSH PRIVILEGES;" && \
    mysql -uroot -p123456 -e "source /var/db.sql;"

# 复制web项目源码
COPY ./sourcecode/code /var/www/html

# 重新设置源码路径的用户所有权
RUN chown -R www-data:www-data /var/www/html

# 拷贝容器入口点脚本
COPY ./env/start.sh /start.sh
RUN chmod +x /start.sh

# 设置shell的工作目录
WORKDIR /var/www/html

EXPOSE 80

# 设置容器入口点
ENTRYPOINT [ "/start.sh" ]

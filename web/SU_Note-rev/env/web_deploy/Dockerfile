FROM docker.m.daocloud.io/library/php:8.2-apache

RUN set -eux; \
    printf 'Acquire::Retries "10";\nAcquire::http::Timeout "30";\nAcquire::https::Timeout "30";\nAcquire::ForceIPv4 "true";\n' > /etc/apt/apt.conf.d/99-network-retry; \
    if [ -f /etc/apt/sources.list.d/debian.sources ]; then \
        sed -i 's|http://deb.debian.org/debian|https://mirrors.tuna.tsinghua.edu.cn/debian|g; s|http://deb.debian.org/debian-security|https://mirrors.tuna.tsinghua.edu.cn/debian-security|g' /etc/apt/sources.list.d/debian.sources; \
    fi; \
    apt_retry() { \
        for i in 1 2 3 4 5; do \
            if "$@"; then return 0; fi; \
            if [ "$i" -eq 5 ]; then return 1; fi; \
            sleep $((i * 3)); \
        done; \
    }; \
    apt_retry apt-get update; \
    DEBIAN_FRONTEND=noninteractive apt_retry apt-get install -y --no-install-recommends --fix-missing \
        ca-certificates \
        curl \
        gnupg \
        nodejs \
        npm \
        xdg-utils; \
    curl -fsSL https://dl.google.com/linux/linux_signing_key.pub | gpg --dearmor -o /usr/share/keyrings/google-linux.gpg; \
    echo "deb [arch=amd64 signed-by=/usr/share/keyrings/google-linux.gpg] https://dl.google.com/linux/chrome/deb/ stable main" > /etc/apt/sources.list.d/google-chrome.list; \
    apt_retry apt-get update; \
    DEBIAN_FRONTEND=noninteractive apt_retry apt-get install -y --no-install-recommends --fix-missing google-chrome-stable; \
    apt-get clean; \
    rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite \
    && docker-php-ext-install pdo pdo_mysql

WORKDIR /var/www/html

COPY ./public/ /var/www/html/
COPY ./flag /opt/su-eznote/flag
COPY ./.config /opt/su-eznote/.config
COPY ./bot/ /opt/su-eznote-bot/
COPY ./docker/entrypoint.sh /usr/local/bin/su-eznote-entrypoint

RUN npm config set registry https://registry.npmmirror.com \
    && npm config set fetch-retries 5 \
    && npm config set fetch-retry-mintimeout 20000 \
    && npm config set fetch-retry-maxtimeout 120000 \
    && cd /opt/su-eznote-bot \
    && npm install --omit=dev --no-fund --no-audit

RUN chown root:www-data /opt/su-eznote/flag \
    && chmod 640 /opt/su-eznote/flag \
    && chmod 640 /opt/su-eznote/.config \
    && chmod +x /usr/local/bin/su-eznote-entrypoint \
    && chmod +x /opt/su-eznote-bot/bot.js

EXPOSE 80

ENTRYPOINT ["su-eznote-entrypoint"]
CMD ["apache2-foreground"]

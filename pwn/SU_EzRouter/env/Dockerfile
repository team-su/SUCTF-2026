FROM --platform=linux/amd64 ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive
WORKDIR /app

RUN apt-get update && apt-get install -y \
    libc6 \
    psmisc \
    && rm -rf /var/lib/apt/lists/*

COPY . /app

RUN chmod +x /app/http /app/mainproc /app/start.sh && \
    chmod +x /app/www/cgi-bin/*.cgi /app/www/cgi-bin/restart.sh && \
    cp /app/lib/libutils.so /usr/lib/libutils.so

EXPOSE 80

CMD ["/app/start.sh"]

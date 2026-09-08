FROM --platform=linux/amd64 ubuntu:22.04

RUN sed -i "s/http:\/\/archive.ubuntu.com/http:\/\/mirrors.tuna.tsinghua.edu.cn/g" /etc/apt/sources.list && \
    apt-get update && apt-get -y dist-upgrade && \
    apt-get install -y build-essential libevent-dev libseccomp-dev

RUN useradd -m ctf

WORKDIR /home/ctf

RUN cp -R /usr/lib* /home/ctf
RUN cp -R /lib/x86_64-linux-gnu /home/ctf/lib/

RUN mkdir /home/ctf/dev && \
    mknod /home/ctf/dev/null c 1 3 && \
    mknod /home/ctf/dev/zero c 1 5 && \
    mknod /home/ctf/dev/random c 1 8 && \
    mknod /home/ctf/dev/urandom c 1 9 && \
    chmod 666 /home/ctf/dev/*

RUN mkdir /home/ctf/bin && \
    cp /bin/sh /home/ctf/bin && \
    cp /bin/ls /home/ctf/bin && \
    cp /bin/cat /home/ctf/bin

COPY ./start.sh /start.sh

RUN chmod +x /start.sh

COPY ./bin/ /home/ctf/
COPY ./pwn.c /tmp/pwn.c
RUN mkdir -p /home/ctf/lib64 && \
    rm -f /home/ctf/lib64/ld-linux-x86-64.so.2 && \
    cp -L /lib64/ld-linux-x86-64.so.2 /home/ctf/lib64/ld-linux-x86-64.so.2 && \
    gcc -O2 -fstack-protector-strong -o /home/ctf/pwn /tmp/pwn.c -levent -lseccomp
RUN chown -R root:ctf /home/ctf && \
    chmod -R 750 /home/ctf && \
    chmod 740 /home/ctf/flag

EXPOSE 8888
EXPOSE 9999

CMD ["/start.sh"]

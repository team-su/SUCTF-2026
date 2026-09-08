FROM ubuntu:24.04

RUN sed -i "s/http:\/\/archive.ubuntu.com/http:\/\/mirrors.tuna.tsinghua.edu.cn/g" /etc/apt/sources.list && \
    apt-get update && apt-get -y dist-upgrade && \
    apt-get install -y xinetd patchelf

RUN useradd -m ctf

WORKDIR /home/ctf

RUN cp -R /usr/lib* /home/ctf

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

COPY ./ctf.xinetd /etc/xinetd.d/ctf
COPY ./start.sh /start.sh
RUN echo "Blocked by ctf_xinetd" > /etc/banner_fail

RUN chmod +x /start.sh

COPY ./bin/ /home/ctf/

RUN cd /home/ctf/ && \
    patchelf --replace-needed libc.so.6 ./libc.so.6 ./mini_vfs && \
    patchelf --set-interpreter ./ld-linux-x86-64.so.2 ./mini_vfs && \
    HASH=$(head -c 16 /dev/urandom | sha256sum | cut -d' ' -f1 | head -c 16) && \
    mv flag "flag_${HASH}" && \
    echo "flag{this_is_a_fake_flag}" > flag && \
    cd /

RUN chown -R 1000:1000 /home/ctf && \
    chmod -R 750 /home/ctf && \
    chmod 740 /home/ctf/flag*

CMD ["/start.sh"]

EXPOSE 9999

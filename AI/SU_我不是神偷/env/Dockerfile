FROM docker.m.daocloud.io/python:3.9-slim-bullseye

RUN sed -i 's/deb.debian.org/mirrors.ustc.edu.cn/g' /etc/apt/sources.list && \
    sed -i 's/security.debian.org/mirrors.ustc.edu.cn/g' /etc/apt/sources.list

RUN apt-get update

RUN python3 -m pip install -i https://pypi.tuna.tsinghua.edu.cn/simple \
    flask torch torchvision pillow numpy

COPY ./src/ /app
COPY ./service/docker-entrypoint.sh /

EXPOSE 8080

ENTRYPOINT ["/bin/bash", "/docker-entrypoint.sh"]

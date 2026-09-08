FROM docker.m.daocloud.io/python:3.9-slim-bullseye

ENV PYTHONUNBUFFERED=1 \
    PIP_NO_CACHE_DIR=1

WORKDIR /app

COPY ./src/requirements.txt /tmp/requirements.txt
RUN python3 -m pip install -i https://pypi.tuna.tsinghua.edu.cn/simple -r /tmp/requirements.txt

COPY ./src/ /app/
COPY ./service/docker-entrypoint.sh /docker-entrypoint.sh

RUN chmod 755 /docker-entrypoint.sh

EXPOSE 8080

ENTRYPOINT ["/bin/sh", "/docker-entrypoint.sh"]

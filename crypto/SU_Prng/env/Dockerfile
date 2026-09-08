FROM python:3.10.13-slim-bullseye

LABEL auther_template="CTF-Archives"

RUN apt-get update \
    && apt-get install -y --no-install-recommends socat \
    && python3 -m pip install --no-cache-dir pycryptodome \
    && rm -rf /var/lib/apt/lists/*

RUN useradd -m -u 1000 ctf

WORKDIR /home/ctf
COPY chal.py /home/ctf/chal.py
COPY secret.py /home/ctf/secret.py
COPY start.sh /start.sh

RUN sed -i 's/\r$//' /start.sh \
    && chmod 755 /start.sh /home/ctf/chal.py \
    && chmod 644 /home/ctf/secret.py \
    && chown -R ctf:ctf /home/ctf

USER ctf

EXPOSE 9999

CMD ["/start.sh"]
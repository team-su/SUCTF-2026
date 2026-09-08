FROM php:7.2-apache

ADD all.tar.gz /tmp

RUN cp /tmp/config/apache2.conf /etc/apache2/apache2.conf
RUN cp /tmp/config/php.ini /usr/local/etc/php/php.ini && \
    cp -r /tmp/src/* /var/www/html/ && \
    cp /tmp/pushflag.sh / && \
    cp /tmp/start.sh / && \
    cp /tmp/flag /

RUN rm -rf /tmp/*

RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    chmod 644 /var/www/html/index.php && \
    chmod +x /pushflag.sh && \
    chmod +x /start.sh

CMD ["sh", "-c", "/start.sh"]
EXPOSE 80
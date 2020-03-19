FROM alpine:3.11

RUN apk --no-cache add php7 php7-fpm php7-json php7-openssl php7-curl \
    php7-phar php7-xml php7-dom php7-ctype php7-sockets php7-iconv php7-mbstring php7-session \
    nginx supervisor curl chromium \
 && rm -rf /var/cache/apk/* \
 && php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
 && php -r "if (trim(hash_file('SHA384', 'composer-setup.php')) !== trim(file_get_contents('https://composer.github.io/installer.sig'))) { echo 'Composer installer corrupt' . PHP_EOL; exit(1); }" \
 && php composer-setup.php --quiet --install-dir=/usr/bin --filename=composer \
 && php -r "unlink('composer-setup.php');"

WORKDIR /var/www/html

COPY config/nginx.conf /etc/nginx/nginx.conf
COPY config/fpm-pool.conf /etc/php7/php-fpm.d/www.conf
COPY config/php.ini /etc/php7/conf.d/custom.ini
COPY config/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY --chown=nobody app/ /var/www/html/

RUN composer install --prefer-dist --no-dev --no-cache --no-interaction --no-progress --no-suggest \
 && composer dump-env prod --no-cache \
 && php bin/console c:c -e prod \
 && rm /usr/bin/composer \
 && rm /etc/nginx/conf.d/default.conf \
 && mkdir -p /var/www/html \
 && mkdir /var/tmp/nginx \
 && chown -Rf nobody.nobody /var/www/html \
 && chown -R nobody:nobody /var/www/html \
 && chown -R nobody:nobody /run \
 && chown -R nobody:nobody /var/lib/nginx \
 && chown -R nobody:nobody /var/tmp/nginx \
 && chown -R nobody:nobody /var/log/nginx

VOLUME /var/www/html

USER nobody:nobody

EXPOSE 8080

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

HEALTHCHECK --timeout=10s CMD curl --silent --fail http://127.0.0.1:8080/fpm-ping

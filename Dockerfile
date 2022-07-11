FROM php:8.1-fpm-alpine3.15 as base

COPY --from=mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/

RUN set -eux; \
    apk --no-cache add shadow tzdata nginx supervisor curl chromium; \
    groupmod -g 1000 www-data; \
    usermod -u 1000 -d /var/www -s /bin/ash -g www-data www-data; \
    apk del shadow; \
    rm -Rf /var/cache/apk/* \
        /var/www/localhost \
        /etc/nginx/http.d; \
    install-php-extensions @composer sockets

WORKDIR /var/www

COPY config/nginx.conf            /etc/nginx/nginx.conf
COPY config/fpm-pool.conf         /usr/local/etc/php-fpm.d/www.conf
COPY config/php.ini               /usr/local/etc/php/php.ini
COPY config/supervisord.conf      /etc/supervisor/conf.d/supervisord.conf

EXPOSE 8080

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]


FROM base as development

COPY config/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

RUN install-php-extensions xdebug


FROM base as production

COPY app/ /var/www/
COPY config/entrypoint.sh /usr/local/bin/docker-php-entrypoint

RUN set -eux; \
    docker-php-source delete; \
    rm /usr/local/bin/install-php-extensions; \
    chmod +x /usr/local/bin/docker-php-entrypoint; \
    printf "APP_ENV=prod\n" > /var/www/.env.local; \
    chown -Rf www-data:www-data /var/www; \
    su - www-data -c "set -eux; \
        composer install --prefer-dist --no-dev --no-progress --no-scripts --no-autoloader --no-interaction; \
        composer dump-autoload --classmap-authoritative --no-dev; \
        composer run-script --no-dev post-install-cmd"

HEALTHCHECK --timeout=10s CMD curl --silent --fail http://127.0.0.1:8080/ping

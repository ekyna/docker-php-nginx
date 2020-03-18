FROM alpine:3.11

RUN apk --no-cache add php7 php7-fpm php7-json php7-openssl php7-curl \
    php7-phar php7-xml php7-dom php7-ctype php7-sockets php7-iconv php7-mbstring php7-session \
    nginx supervisor curl chromium \
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

# Setup document root
RUN composer install --prefer-dist --no-dev --no-cache --no-interaction --no-progress --no-suggest \
 && composer dump-env prod --no-cache \
 && php bin/console c:c -e prod \
 && chown -Rf nobody.nobody /var/www/html \
 && mkdir -p /var/www/html \
 && mkdir /var/tmp/nginx \
 && rm /etc/nginx/conf.d/default.conf \
 && rm /usr/bin/composer \
 && chown -R nobody:nobody /var/www/html \
 && chown -R nobody:nobody /run \
 && chown -R nobody:nobody /var/lib/nginx \
 && chown -R nobody:nobody /var/tmp/nginx \
 && chown -R nobody:nobody /var/log/nginx

# Make the document root a volume
VOLUME /var/www/html

# Switch to use a non-root user from here on
USER nobody:nobody

# Expose the port nginx is reachable on
EXPOSE 8080

# Let supervisord start nginx & php-fpm
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

# Configure a healthcheck to validate that everything is up&running
HEALTHCHECK --timeout=10s CMD curl --silent --fail http://127.0.0.1:8080/fpm-ping

#!/bin/sh
set -e

su - www-data -c "composer symfony:dump-env prod --no-cache"

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
    set -- php-fpm "$@"
fi

exec "$@"

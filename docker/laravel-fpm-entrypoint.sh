#!/bin/sh
set -e
cd /var/www
chown -R www-data:www-data storage bootstrap/cache
exec /usr/local/bin/docker-php-entrypoint "$@"

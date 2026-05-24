#!/bin/sh

chown -R www-data:www-data /var/www/storage
chown -R www-data:www-data /var/www/bootstrap/cache
chmod -R 775 /var/www/storage
chmod -R 775 /var/www/bootstrap/cache

php artisan migrate --force
php artisan app:ensure-database-seeded
php artisan config:cache
php artisan storage:link
php artisan reverb:start --host=0.0.0.0 --port=8080
exec php-fpm

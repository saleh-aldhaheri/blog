#!/bin/sh

php artisan migrate --force
php artisan app:ensure-database-seeded --force
php artisan storage:link
exec php-fpm

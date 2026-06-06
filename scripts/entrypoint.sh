#!/bin/bash
set -euo pipefail

function main() {
    permissions
    prepareStorage
    runMigrations
    setDBStates
    optimizeApp
    executePhp
}

function permissions() {
    chown -R www-data:www-data /var/www/storage
    chown -R www-data:www-data /var/www/public
    chown -R www-data:www-data /var/www/bootstrap/cache
    chmod -R 775 /var/www/storage
    chmod -R 775 /var/www/bootstrap/cache
}

function prepareStorage() {
    php artisan storage:link --force
}

function runMigrations() {
    php artisan migrate --force
}

function setDBStates() {
    php artisan app:ensure-database-seeded
}

function optimizeApp() {
    php artisan optimize:clear
    php artisan optimize
}

function executePhp() {
    exec php-fpm
}

#run the script
main

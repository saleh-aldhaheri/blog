FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev

RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring \
    bcmath \
    zip

COPY  --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY docker/laravel-fpm-entrypoint.sh /usr/local/bin/laravel-fpm-entrypoint.sh
RUN chmod +x /usr/local/bin/laravel-fpm-entrypoint.sh

WORKDIR /var/www

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/laravel-fpm-entrypoint.sh"]
CMD ["php-fpm"]

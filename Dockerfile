FROM php:8.4-fpm

WORKDIR /var/www

COPY . /var/www/

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype-dev \
    libonig-dev \
    libxml2-dev \
    default-mysql-client

RUN docker-php-ext-configure gd --with-freetype --with-jpeg

RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring \
    bcmath \
    zip \
    exif \
    gd

RUN pecl install redis && docker-php-ext-enable redis

RUN printf '[client]\nssl-verify-server-cert=0\n' > /etc/my.cnf

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN composer install

RUN chmod +x ./scripts/entrypoint.sh

CMD ["./scripts/entrypoint.sh"]

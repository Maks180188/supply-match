FROM php:8.4-fpm-alpine

WORKDIR /var/www/backend

RUN apk add --no-cache \
    bash \
    git \
    unzip \
    icu-dev \
    libpq-dev \
    libzip-dev \
    oniguruma-dev

RUN docker-php-ext-install \
    intl \
    mbstring \
    pdo_pgsql \
    zip \
    bcmath \
    opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
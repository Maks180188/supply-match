FROM php:8.4-fpm-alpine

ARG WWWUSER=1000
ARG WWWGROUP=1000

WORKDIR /var/www/backend

RUN apk add --no-cache \
    bash \
    git \
    unzip \
    icu-dev \
    libpq-dev \
    libzip-dev \
    oniguruma-dev

RUN apk add --no-cache --virtual .user-tools shadow \
    && groupmod -o -g "${WWWGROUP}" www-data \
    && usermod -o -u "${WWWUSER}" -g "${WWWGROUP}" www-data \
    && apk del .user-tools

RUN docker-php-ext-install \
    intl \
    mbstring \
    pdo_pgsql \
    zip \
    bcmath \
    opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
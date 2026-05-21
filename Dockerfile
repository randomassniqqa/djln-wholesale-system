# syntax=docker/dockerfile:1.7

FROM node:22-alpine AS frontend
WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build

FROM composer:2 AS vendor
WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --optimize-autoloader

FROM php:8.3-fpm-alpine AS runtime

ENV APP_ENV=production \
    APP_DEBUG=false \
    COMPOSER_ALLOW_SUPERUSER=1

RUN apk add --no-cache \
        $PHPIZE_DEPS \
        bash \
        curl \
    freetype-dev \
        gettext \
        icu-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libzip-dev \
        oniguruma-dev \
        postgresql-dev \
        nginx \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        exif \
        gd \
        intl \
        mbstring \
        opcache \
        pdo_mysql \
        pdo_pgsql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /tmp/pear \
    && mkdir -p /var/www/html/storage/framework/cache \
        /var/www/html/storage/framework/sessions \
        /var/www/html/storage/framework/views \
        /var/www/html/bootstrap/cache \
        /var/lib/nginx/logs \
        /var/lib/nginx/tmp \
        /tmp/nginx \
    && chown -R www-data:www-data /var/www/html /tmp/nginx /var/lib/nginx

WORKDIR /var/www/html

COPY --from=vendor /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build
COPY . .

COPY docker/nginx.conf.template /tmp/nginx.conf.template
COPY docker/nginx.conf.template /etc/nginx/nginx.conf.template
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh \
    && chmod a+r /tmp/nginx.conf.template /etc/nginx/nginx.conf.template \
    && chown www-data:www-data /tmp/nginx.conf.template /etc/nginx/nginx.conf.template \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R ug+rwX storage bootstrap/cache

USER www-data

EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
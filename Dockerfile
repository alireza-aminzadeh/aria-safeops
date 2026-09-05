# syntax=docker/dockerfile:1
# =====================================================================
# Aria SafeOps — Dockerfile چندمرحله‌ای (Multi-stage) برای Symfony 7.4 / PHP 8.4
# طبق قواعد امنیتی پروژه: ایمیج پایهٔ Alpine، کاربر non-root در مرحلهٔ نهایی،
# بدون secrets در لایه‌های ایمیج.
# =====================================================================

ARG PHP_VERSION=8.4

# ---------------------------------------------------------------------
# Stage 1: پایه — نصب اکستنشن‌های PHP لازم برای Symfony + PostgreSQL
# ---------------------------------------------------------------------
FROM php:${PHP_VERSION}-fpm-alpine AS base

RUN apk add --no-cache --virtual .build-deps \
        icu-dev \
        libzip-dev \
        postgresql-dev \
        oniguruma-dev \
        linux-headers \
    && docker-php-ext-install -j"$(nproc)" \
        intl \
        pdo_pgsql \
        zip \
        opcache \
    && apk add --no-cache libpq git unzip openssl su-exec icu-libs libzip \
    && apk del .build-deps

RUN echo 'memory_limit=256M' > /usr/local/etc/php/conf.d/memory.ini

RUN { \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.max_accelerated_files=20000'; \
        echo 'opcache.validate_timestamps=0'; \
        echo 'opcache.jit=tracing'; \
        echo 'opcache.jit_buffer_size=64M'; \
    } > /usr/local/etc/php/conf.d/opcache-prod.ini

WORKDIR /var/www/app

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ---------------------------------------------------------------------
# Stage 2: نصب وابستگی‌های Composer (کش‌پذیر و جدا از کد اپلیکیشن)
# ---------------------------------------------------------------------
FROM base AS vendor

COPY composer.json composer.lock ./
COPY symfony.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-progress \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader

# ---------------------------------------------------------------------
# Stage 3: توسعهٔ محلی — Composer کامل + PHP-FPM
# ---------------------------------------------------------------------
FROM base AS dev

RUN { \
        echo 'opcache.validate_timestamps=1'; \
        echo 'opcache.revalidate_freq=0'; \
    } > /usr/local/etc/php/conf.d/opcache-dev.ini

ENV APP_ENV=dev
EXPOSE 9000
CMD ["php-fpm", "--nodaemonize"]

# ---------------------------------------------------------------------
# Stage 4: بیلد فرانت‌اند
# ---------------------------------------------------------------------
FROM node:22-alpine AS frontend
WORKDIR /fe
COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci
COPY frontend ./
RUN npm run build

# ---------------------------------------------------------------------
# Stage 5: ایمیج نهایی Runtime — کاربر non-root
# ---------------------------------------------------------------------
FROM base AS runtime

COPY --from=vendor /var/www/app/vendor ./vendor
COPY . .
COPY --from=frontend /fe/dist ./public/spa-dist
COPY infra/docker/php-fpm-www.conf /usr/local/etc/php-fpm.d/www.conf
COPY .env.example .env

RUN addgroup -g 1000 appgroup \
    && adduser -D -u 1000 -G appgroup -h /var/www/app appuser \
    && mkdir -p var/cache var/log config/jwt \
    && chown -R appuser:appgroup /var/www/app \
    && rm -rf var/cache/* var/log/* \
    && chmod +x /var/www/app/infra/docker/app-prod.sh

# php-fpm master starts as root then workers drop to appuser (see php-fpm-www.conf)
EXPOSE 9000

HEALTHCHECK --interval=15s --timeout=5s --start-period=45s --retries=8 \
    CMD pidof php-fpm >/dev/null || exit 1

CMD ["sh", "/var/www/app/infra/docker/app-prod.sh"]

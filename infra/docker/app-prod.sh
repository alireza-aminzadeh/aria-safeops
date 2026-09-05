#!/bin/sh
set -eu
cd /var/www/app

mkdir -p /var/www/spa config/jwt var/cache var/log

if [ -d public/spa-dist ]; then
  cp -a public/spa-dist/. /var/www/spa/
fi
chmod -R a+rX /var/www/spa

if [ ! -f config/jwt/private.pem ]; then
  openssl genpkey -algorithm RSA -out config/jwt/private.pem -pkeyopt rsa_keygen_bits:4096
  openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem
fi
chmod 600 config/jwt/private.pem
chmod 644 config/jwt/public.pem

chown -R appuser:appgroup var config/jwt

export APP_ENV="${APP_ENV:-prod}"
export APP_DEBUG="${APP_DEBUG:-0}"

su-exec appuser php bin/console cache:warmup --env=prod --no-debug
su-exec appuser php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
su-exec appuser php bin/console app:seed --no-interaction || true

exec php-fpm --nodaemonize

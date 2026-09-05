#!/bin/sh
set -eu
cd /var/www/app

if [ ! -f vendor/autoload.php ]; then
  composer install --no-interaction --prefer-dist --no-scripts
fi

mkdir -p config/jwt
if [ ! -f config/jwt/private.pem ]; then
  php bin/console lexik:jwt:generate-keypair --no-interaction || true
fi

php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
php bin/console app:seed --no-interaction || true

exec php-fpm --nodaemonize

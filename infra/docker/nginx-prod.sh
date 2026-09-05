#!/bin/sh
set -eu
rm -f /etc/nginx/conf.d/default.conf

i=0
while [ ! -f /var/www/spa/index.html ] && [ "$i" -lt 90 ]; do
  i=$((i + 1))
  sleep 1
done

if [ -f /etc/nginx/ssl/live/hse.aria-ai.ir/fullchain.pem ]; then
  cp /etc/nginx/templates/ssl.conf /etc/nginx/conf.d/app.conf
else
  cp /etc/nginx/templates/http.conf /etc/nginx/conf.d/app.conf
fi
exec nginx -g 'daemon off;'

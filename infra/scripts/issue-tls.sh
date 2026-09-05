#!/bin/sh
set -eu
mkdir -p /opt/aria-safeops/infra/certbot/www \
  /opt/aria-safeops/infra/certbot/conf \
  /opt/aria-safeops/infra/certbot/work \
  /opt/aria-safeops/infra/certbot/logs
chown -R deploy:deploy /opt/aria-safeops/infra/certbot

certbot certonly --webroot \
  -w /opt/aria-safeops/infra/certbot/www \
  --config-dir /opt/aria-safeops/infra/certbot/conf \
  --work-dir /opt/aria-safeops/infra/certbot/work \
  --logs-dir /opt/aria-safeops/infra/certbot/logs \
  -d hse.aria-ai.ir \
  --email alireza-aminzadeh@users.noreply.github.com \
  --agree-tos --non-interactive --keep-until-expiring

ls -la /opt/aria-safeops/infra/certbot/conf/live/hse.aria-ai.ir/

cd /opt/aria-safeops
sudo -u deploy -H docker compose -f docker-compose.yml restart nginx
sleep 3
docker compose -f /opt/aria-safeops/docker-compose.yml logs --tail 15 nginx

CRON='0 3 * * * certbot renew --quiet --config-dir /opt/aria-safeops/infra/certbot/conf --work-dir /opt/aria-safeops/infra/certbot/work --logs-dir /opt/aria-safeops/infra/certbot/logs --deploy-hook "docker compose -f /opt/aria-safeops/docker-compose.yml restart nginx"'
(crontab -l 2>/dev/null | grep -v 'aria-safeops' || true; echo "$CRON") | crontab -
echo "certbot done"

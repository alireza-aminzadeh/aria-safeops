#!/bin/sh
set -eu
KEY=${1:-}
if [ -z "$KEY" ]; then
  echo "usage: $0 <hex-key>" >&2
  exit 1
fi
cd /opt/aria-safeops
if grep -q '^PETROOPS_INTEGRATION_KEY=' .env; then
  sed -i "s|^PETROOPS_INTEGRATION_KEY=.*|PETROOPS_INTEGRATION_KEY=${KEY}|" .env
else
  printf '\nPETROOPS_INTEGRATION_KEY=%s\n' "$KEY" >> .env
fi
chmod 600 .env
docker compose -f docker-compose.yml up -d --force-recreate --no-deps app
echo "waiting for app health"
i=0
while [ "$i" -lt 36 ]; do
  if docker compose -f docker-compose.yml exec -T app php -r 'exit(getenv("PETROOPS_INTEGRATION_KEY") ? 0 : 1);'; then
    echo "PETROOPS_INTEGRATION_KEY is in the app process"
    break
  fi
  i=$((i + 1))
  sleep 5
done
docker compose -f docker-compose.yml ps app

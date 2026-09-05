#!/bin/sh
set -eu
cd /opt/aria-safeops
docker compose -f docker-compose.yml up -d --force-recreate app
sleep 12
docker compose -f docker-compose.yml ps
echo '---LOGS---'
docker compose -f docker-compose.yml logs --tail 40 app

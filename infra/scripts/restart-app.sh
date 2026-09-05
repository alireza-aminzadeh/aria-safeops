#!/bin/sh
set -eu
export GIT_SSH_COMMAND='ssh -i /home/deploy/.ssh/id_ed25519_github -o IdentitiesOnly=yes'
cd /opt/aria-safeops
git fetch origin main
git reset --hard origin/main
if [ ! -f .env ]; then
  echo "missing .env" >&2
  exit 1
fi
docker compose -f docker-compose.yml up -d --remove-orphans
sleep 8
docker compose -f docker-compose.yml ps
echo '---APP LOGS---'
docker compose -f docker-compose.yml logs --tail 50 app

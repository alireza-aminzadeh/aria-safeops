#!/bin/sh
set -eu
cd /opt/aria-safeops
echo '---env keys---'
grep -E '^(PETROOPS_INTEGRATION_KEY|IMAGE_TAG)=' .env | sed 's/=.*/=***/'
echo '---git---'
git log -1 --oneline
echo '---ps---'
docker compose -f docker-compose.yml ps
echo '---table---'
docker compose -f docker-compose.yml exec -T postgres sh -c 'psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" -c "\dt petroops_equipment_holds" -c "SELECT COUNT(*) AS holds FROM petroops_equipment_holds;"'
echo '---env in app---'
docker compose -f docker-compose.yml exec -T app php -r 'echo getenv("PETROOPS_INTEGRATION_KEY") === false ? "getenv=missing\n" : "getenv=set\n";'

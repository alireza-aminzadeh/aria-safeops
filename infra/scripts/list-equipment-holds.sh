#!/bin/sh
set -eu
cd /opt/aria-safeops
docker compose -f docker-compose.yml exec -T postgres sh -c 'psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" -c "SELECT equipment_tag, status, score FROM petroops_equipment_holds ORDER BY created_at;"'

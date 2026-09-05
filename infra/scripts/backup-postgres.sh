#!/bin/sh
set -eu
DIR=/var/backups/aria-safeops
mkdir -p "$DIR"
cd /opt/aria-safeops
set -a
# shellcheck disable=SC1091
. ./.env
set +a
FILE="$DIR/safeops-$(date +%F).sql.gz"
docker compose -f docker-compose.yml exec -T postgres pg_dump -U "$POSTGRES_USER" "$POSTGRES_DB" | gzip > "$FILE"
chmod 600 "$FILE"
find "$DIR" -name 'safeops-*.sql.gz' -mtime +7 -delete
echo "wrote $FILE"

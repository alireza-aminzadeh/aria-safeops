#!/bin/sh
set -eu
PG=$(openssl rand -hex 24)
RD=$(openssl rand -hex 24)
SECRET=$(openssl rand -hex 32)
cat > /root/aria-safeops.env <<EOF
COMPOSE_FILE=docker-compose.yml
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=${SECRET}
APP_URL=https://hse.aria-ai.ir
IMAGE_TAG=latest
POSTGRES_DB=aria_safeops
POSTGRES_USER=aria_safeops
POSTGRES_PASSWORD=${PG}
DATABASE_URL=postgresql://aria_safeops:${PG}@postgres:5432/aria_safeops?serverVersion=16&charset=utf8
REDIS_PASSWORD=${RD}
REDIS_URL=redis://:${RD}@redis:6379
JWT_PASSPHRASE=
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_TTL=28800
TRUSTED_PROXIES=127.0.0.1,REMOTE_ADDR
AI_GATEWAY_ENABLED=false
AI_GATEWAY_URL=
AI_GATEWAY_API_KEY=
AI_GATEWAY_TIMEOUT_MS=8000
MERCURE_ENABLED=false
MERCURE_URL=http://mercure:3000/.well-known/mercure
MERCURE_PUBLIC_URL=https://hse.aria-ai.ir/.well-known/mercure
MERCURE_JWT_SECRET=
MAILER_DSN=smtp://localhost
EOF
chmod 600 /root/aria-safeops.env
echo "wrote /root/aria-safeops.env"

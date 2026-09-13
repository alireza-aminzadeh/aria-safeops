# 8) Infrastructure and deployment — Aria SafeOps

English technical manual. Persian original: [`../08-infrastructure-deployment.md`](../08-infrastructure-deployment.md).

## 8.1 Host

Dedicated VPS for HSE (hostname `hse`). Public name: `hse.aria-ai.ir`. Size assumed by compose comments: 2 vCPU / ~3.7 GiB RAM. Do not run LSTM-AE or Qdrant here.

## 8.2 Topology

```
Internet → :80/:443 Nginx
              ├─ static SPA (frontend build volume)
              ├─ /api → app PHP-FPM
              └─ /.well-known/mercure → mercure
         internal docker net
              ├─ postgres (Timescale)
              └─ redis
```

TLS via Let’s Encrypt (certbot volumes in compose). HTTP is only for redirect + ACME.

## 8.3 Commands (production)

```bash
cd /opt/aria-safeops
docker compose -f docker-compose.yml pull
docker compose -f docker-compose.yml up -d --remove-orphans
docker compose -f docker-compose.yml restart nginx
```

CI already does this after pushing `IMAGE_TAG=<github.sha>`.

## 8.4 Local Windows note

Docker Desktop on the development PC may not resolve npm/Alpine registries. Run PHP built-in server + Vite on the host; only Postgres/Redis in Docker. UI port **5174** avoids clashing with PetroOps **5173**. Host Apache often occupies port 80.

## 8.5 Memory

Every service has `deploy.resources.limits.memory` and a reservation. Watch with `docker stats`. If the app OOM-kills, do not raise Postgres unbounded — cap workers first.

## 8.6 Line endings

Shell entrypoints (`infra/docker/*.sh`) must stay **LF**. `.gitattributes` in this repo is set for that.

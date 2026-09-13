# 2) Architecture — Aria SafeOps

English technical manual. Persian original: [`../02-architecture.md`](../02-architecture.md).

## 2.1 Pattern

**Modular monolith** on Symfony. Each bounded context lives under `src/Domain/*`. **Headless API-first**: the React SPA is an independent consumer.

```
React 19 + TS SPA  --HTTPS/JSON + Mercure-->  Nginx (SPA + TLS)
                                              |
                                           PHP-FPM
                                              |
                                    Symfony 7.4 + API Platform
                                      Domain/* + Infrastructure/AiGateway
                                              |
                         PostgreSQL 16+Timescale     Redis 7.4
```

A central FastAPI + Qdrant + LLM gateway is a **future** process, not a container on this 2 vCPU host.

## 2.2 Repository layout (as built)

```
aria-safeops/
├── src/Domain/{Permit,Moc,Incident,Contractor,Psm,Emergency,Shift,Vision,Integration,Shared}/
├── src/Infrastructure/{AiGateway,Audit,Notification}/
├── config/packages/{workflow.yaml,api_platform.yaml,security.yaml,lexik_jwt_authentication.yaml}
├── config/jwt/          # gitignored keys
├── migrations/
├── frontend/            # React 19 + Vite + Tailwind v4
├── tests/
├── docker-compose.yml
├── .github/workflows/ci-cd.yml
└── docs/                # FA manuals, en/, reports/
```

The original sketch mentioned `packages/ui` and generated OpenAPI clients. The SPA talks to the API with a typed `api()` helper; there is no shared `@aria/ui` package in this repo (that package exists in PetroOps).

## 2.3 Layers

| Layer | Responsibility |
|---|---|
| API Platform resource + processors | HTTP, validation, transitions |
| `Domain/*/Entity` | Model + invariants |
| `Domain/*/Workflow` | Guards and transition side effects |
| `Domain/*/Voter` | ABAC |
| `Infrastructure/` | JWT files, HTTP AI, mail, Mercure |

## 2.4 Multi-tenancy

`tenant_id` on operational rows. Query extensions filter by current tenant. Contractor rows are **not** tenant-scoped yet (Phase 3 leftover). RLS is documented for Permit/MOC/Incident/User.

## 2.5 Audit trail

Sensitive changes append `audit_log`:

```
hash[n] = SHA-256( hash[n-1] || tenant_id || entity || entity_id || action || actor_id || payload_json || timestamp )
```

`app:audit:verify-chain` exists for periodic verification (see Persian security/ops docs).

## 2.6 i18n and calendar

Backend validation messages can live in translation catalogues. The **SPA is Persian RTL**. Database timestamps are UTC. Jalali display is a frontend concern; a full English UI is not shipped.

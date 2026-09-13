# 5) API design — Aria SafeOps

English technical manual. Persian original: [`../05-api-design.md`](../05-api-design.md).

## 5.1 Principles

| Topic | Choice |
|---|---|
| Framework | API Platform 4 on Symfony 7.4 |
| Errors | RFC 7807 `application/problem+json` |
| Base URL | `https://hse.aria-ai.ir/api` |
| OpenAPI | `https://hse.aria-ai.ir/api/docs` |
| Auth | `Authorization: Bearer <JWT>` |
| Login | `POST /api/login` with `{ "username", "password" }` (email also accepted) |
| Pagination | `?page=` & `itemsPerPage=` |

Production credentials are **not** listed here. Local seed users: see root `README.md`.

## 5.2 Representative resources

| Method | Path | Notes |
|---|---|---|
| POST | `/api/login` | JWT |
| GET/POST | `/api/permits` | List / create (`draft`) |
| GET/PATCH | `/api/permits/{id}` | Patch only while `draft` |
| POST | `/api/permits/{id}/transitions` | `{ "transition": "approve" }` — `422` if a guard blocks |
| GET | `/api/permits/{id}/available-transitions` | Buttons for the current user |
| POST | `/api/permits/{id}/gas-test-readings` | Gas test |
| GET/POST | `/api/moc-requests` | MOC |
| POST | `/api/moc-requests/{id}/transitions` | MOC machine |
| GET/POST | `/api/incidents` | Incidents |
| POST | `/api/incidents/{id}/capa-actions` | CAPA |
| GET | `/api/dashboard` | Operations cards |
| GET | `/api/kpis/api754` | Process-safety indicators |
| GET/POST | `/api/safety-period-metrics` | Hours worked for LTIFR/TRIR |
| GET/POST | `/api/psm-audits` | 14-element audits |
| POST | `/api/integrations/petroops/anomalies` | Hold upsert |
| GET | `/api/health` | Liveness |

Filters (`SearchFilter`, `OrderFilter`, `DateFilter`) are declared on resources.

## 5.3 Transition processor pattern

State changes are **not** a raw PATCH of `status`. A custom processor loads the entity, asks Symfony Workflow `can()`, then `apply()`. Voters run on the same request.

## 5.4 Versioning

Phase 1 has no `/v2` prefix. Breaking changes would use API Platform’s versioning strategy rather than a custom header (the Persian doc contrasts this with Nest URI versioning on PetroOps).

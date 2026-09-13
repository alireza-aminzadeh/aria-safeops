# 4) Data model — Aria SafeOps

English technical manual. Persian original: [`../04-database-schema.md`](../04-database-schema.md).

## 4.1 Engine

PostgreSQL 16 + TimescaleDB (`timescale/timescaledb:2.17.2-pg16`). Phase 1 data is mostly relational. The extension is enabled from day one so high-frequency gas-test or Vision series can become hypertables without a platform change.

## 4.2 Core tables (Phase 1+)

| Table | Role |
|---|---|
| `tenants` | Customer / site |
| `users` | `username`, email, password hash, JSON roles, `tenant_id` |
| `permit_types` | Codes + `requires_gas_test` / `requires_isolation` + FA/EN names |
| `permits` | Marking = workflow place; `equipment_tag`; JSA JSON; validity window |
| Workflow audit trail | Symfony `audit_trail` for transitions |
| `gas_test_readings` | O2, LEL, H2S, CO |
| `moc_requests` | Change type; `pssr_completed_at`; tag |
| `hazop_register_items` | PHA register |
| `incidents`, `capa_actions` | HSE events |
| `contractors`, certifications, training | Qualification |
| `audit_log` | Hash-chain |
| `ai_query_log` | Knowledge / classify calls |

## 4.3 Phase 2 tables (selected)

| Area | Tables / entities |
|---|---|
| PSM | LOPA scenarios, Bowtie barriers, `psm_audits`, findings |
| Shift | Handovers, logbook, toolbox talks |
| Vision | Cameras, events |
| Emergency | ERP plans, drills, effluent readings |
| Integration | `equipment_holds` |
| Shared | Electronic signatures |

Column-level detail and types: see the Persian schema doc and Doctrine mappings under `src/Domain/*/Entity`.

## 4.4 Conventions

- UUID primary keys
- `timestamptz` in UTC
- JSONB for roles and JSA blobs
- Soft operational isolation by `tenant_id` in application queries (+ RLS on selected tables)

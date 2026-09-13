# 10) Roadmap — Aria SafeOps

English technical manual. Persian original: [`../10-roadmap.md`](../10-roadmap.md).  
Status tables: [`../reports/01-product-status.md`](../reports/01-product-status.md).

## Phase 0 — Infrastructure (done)

Docs, SSH survey, Docker Engine on the VPS, `deploy` user, UFW, GitHub repo + secrets, DNS for `hse.aria-ai.ir`.

## Phase 1 — BPMS MVP (done)

Symfony skeleton, entities/migrations, PTW + MOC workflows with guards, API Platform CRUD + processors, JWT, React SPA, AI **interface** (now a three-level factory), first production deploy.

## Phase 2 — PSM and live slots (done in code; AI remains non-LLM)

- HAZOP/LOPA/Bowtie + API 754
- Vision registry + PPE simulator; ingest when URL/key set
- Shift / logbook / toolbox talk
- On-prem HSE knowledge pack + HTTP adapter when `AI_GATEWAY_URL` is set
- PetroOps holds on create **and** activate/resume/implementation
- Mercure
- Emergency ERP, drills, effluent (pulled forward from Phase 3)

## Phase 3 — Open

| Item | Notes |
|---|---|
| Agentic copilot | Auto-draft permit from JSA history |
| Full multi-tenancy | Contractor tenant model + onboarding |
| Real RAG/LLM | External FastAPI; optional HF-origin weights **on-prem** |
| Spatial SIMOPS, multi-point LOTO, PKI sign | See gap report |

## Phase 1 Definition of Done (met)

Permit `draft`→`closed` with audit; MOC cannot skip PSSR; tests + pipeline deploy; AI controls visible without crashing when inference is off.

## Phase 2 honesty

“AI Gateway enabled” means **keyword retrieval** unless `AI_GATEWAY_URL` points at a real `/v1` service. Hugging Face Spaces are not that service.
